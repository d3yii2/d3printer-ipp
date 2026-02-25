<?php

namespace d3yii2\d3printeripp\components;

use d3yii2\d3printeripp\interfaces\PrinterInterface;
use d3yii2\d3printeripp\types\PrinterAttributes;
use d3yii2\d3printeripp\types\PrinterAttributeValues;
use InvalidArgumentException;
use obray\ipp\exceptions\AuthenticationError;
use obray\ipp\exceptions\HTTPError;
use obray\ipp\Printer;
use obray\ipp\PrinterAttributes as IPPPrinterAttributes;
use obray\ipp\transport\IPPPayload;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\VarDumper;

/**
 * Base class for printer implementations
 * tested on HP Laser Jet Pro 3002dn
 */
class BasePrinter  extends Component implements PrinterInterface
{

    public string $printerName;
    public string $name;
    public ?string $daemonName = null;
    public string $host;
    public int $port = 631;
    public ?string $username = null;
    public ?string $password = null;
    public ?string $pincode = null;
    public bool $encryption = false;
    public int $timeout = 30;
    public int $cacheDuration;
    public string $spoolerComponentName;
    public ?string $alertConfigComponentName = null;
    public ?string $cacheComponentName = null;
    public ?string $mailerComponentName = null;
    public array $curlOptions = [];

    /** @var int
     *  @class d3yii2\d3printeripp\types\PrinterAttributeValues
     */
    public int $pageOrientation = PrinterAttributeValues::ORIENTATION_PORTRAIT;

    /**
     * @var string
     * @class d3yii2\d3printeripp\types\PrinterAttributeValues
     */
    public string $pageSize = PrinterAttributeValues::MEDIA_SIZE_A4;

    protected ?string $lastError = null;
    protected ?Spooler $printerSpooler = null;
    /**
     * @var mixed
     */
    private ?Printer $printer = null;
    /**
     * @var mixed
     */
    public ?string $printerComponentName = null;
    private ?PrinterCache $cache = null;


    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getUri(): string
    {
        return 'ipp://' . $this->host . ':' . $this->port;
    }

    /**
     * get actual status from printer
     * @param bool $withAttributes
     * @return object
     * @throws AuthenticationError
     * @throws Exception
     * @throws HTTPError
     * @throws InvalidConfigException
     */
    public function getStatusFromPrinter(bool $withAttributes = true): object
    {
        $stats = $this->generateCurrentStats($withAttributes);
        if ($cache = $this->getCache()) {
            $cache->update($this->printerName, $stats);
        }
        return $stats;
    }

    /**
     * get from cache or printer status if not cached, get from printer
     * @return object
     * @throws AuthenticationError
     * @throws Exception
     * @throws HTTPError
     * @throws InvalidConfigException
     */
    public function getStatusFromCache(): object
    {

        /** @var PrinterCache $cache */
        if (($cache = $this->getCache())
            && $cachedStats = $cache->getCacheData($this->printerName)
        ) {
                return $cachedStats;
        }
        /**
         * do not send request to printer for avoiding long time processing
         */
        return $this->getStatusFromPrinter(false);
    }

    /**
     * @throws InvalidConfigException
     */
    private function getCache(): ?PrinterCache
    {
        if (!$this->cacheComponentName) {
            return null;
        }
        if ($this->cache) {
            return $this->cache;
        }
        return $this->cache = Yii::$app->get($this->cacheComponentName, false);
    }

    /**
     * @param bool $withAttributes
     * @return object
     * @throws AuthenticationError
     * @throws Exception
     * @throws HTTPError
     * @throws InvalidConfigException
     */
    private function generateCurrentStats(bool $withAttributes = true): object
    {
        if ($withAttributes) {
            $attributes = $this->getPrinterAttributes();
        } else {
            $attributes = null;
        }
        /** @var AlertConfig $alertConfig */
        $alertConfig = Yii::$app->get($this->alertConfigComponentName, false);
        $alertConfig->loadAttributes($attributes);
        foreach ($alertConfig->loadedRule as &$rule) {
            if (property_exists($rule, 'printerComponentName')) {
                $rule->printerComponentName = $this->printerComponentName;
            }
            if (property_exists($rule, 'dataUpdatedTime')) {
                $rule->dataUpdatedTime = date('Y-m-d H:i:s');
            }if (property_exists($rule, 'countFilesInSpooler')) {
                $rule->countFilesInSpooler = $this->countSpoolDirectoryFiles();
            }
        }
        return $alertConfig;
    }


    /**
     * get all printer attributes
     * @throws AuthenticationError
     * @throws Exception
     * @throws HTTPError
     */
    public function getAllAttributes(): array
    {
        return $this->getPrinterAttributes()->getAllAttributes();
    }


    /**
     * @return Spooler
     * @throws InvalidConfigException
     */
    public function getSpoolerComponent(): Spooler
    {
        if ($this->printerSpooler !== null) {
            return $this->printerSpooler;
        }
        if (!$this->printerSpooler = Yii::$app->get($this->spoolerComponentName, false)) {
            throw new InvalidConfigException('Printer spooler component not configured');
        }
        return $this->printerSpooler;
    }

    public function getCurlOptions(): array
    {
        return $this->curlOptions;
    }

    /**
     * @throws InvalidConfigException|Exception
     */
    public function printToSpoolDirectory(string $filepath, int $copies = 1): bool
    {
        return $this
            ->getSpoolerComponent()
            ->sendToSpooler(
                $this->printerName,
                $filepath,
                $copies
            );
    }


    /**
     * create email with alert message and send it
     * @param AlertConfig $alert actual printer status
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function sendAlertEmail(AlertConfig $alert): void
    {
        $companentName = $this->mailerComponentName;
        if (!$companentName) {
            throw new InvalidConfigException('Mailer component not configured');
        }

        if (!Yii::$app->has($companentName)) {
            throw new InvalidConfigException('Mailer component name is invalid');
        }

        /** @var Mailer $mailer */
        if (!$mailer = Yii::$app->get($companentName, false)) {
            throw new InvalidConfigException('Mailer component is invalid');
        }
        if (!$mailer->send(
            $this->name,
            $alert->createEmailBody()
        )) {
            throw new Exception('Error sending alert email');
        };
    }

    /**
     * @throws Exception
     */
    public function printFile(
        string $filepath,
        int $copies = 1,
        int $requestId = 1
    ): IPPPayload
    {
        if (!file_exists($filepath)) {
            throw new InvalidArgumentException("File '{$filepath}' does not exist");
        }
        return $this->printContent(file_get_contents($filepath), $copies, $requestId);
    }

    /**
     * @throws Exception
     */
    public function printContent(
        string $content,
        int $copies = 1,
        int $requestId = 1
    ): IPPPayload
    {
        $options = [

            PrinterAttributes::COPIES => $copies,
            PrinterAttributes::ORIENTATION_REQUESTED => $this->pageOrientation,
            PrinterAttributes::MEDIA => $this->pageSize,
            // Alternative approaches:
            // 'media-size' => ['x-dimension' => 21000, 'y-dimension' => 29700], // micrometers
            //'media-size-name' => 'iso_a4_210x297mm',
        ];

        $payload =  $this->getPrinter()->printJob($content,$requestId, $options);
        $statusCode = (string)$payload->statusCode;
        if ($statusCode !== 'successful-ok') {
            throw new Exception('Error printing document. Error: ' . $statusCode);
        }
        return $payload;
    }

    public function getUncompletedJobs(int $requestId = 1): IPPPayload
    {
        return $this
            ->getPrinter()
            ->getJobs($requestId, 'not-completed');
    }

    /**
     * @return Printer
     */
    private function getPrinter(): Printer
    {
        if ($this->printer) {
            return $this->printer;
        }
        return $this->printer = new Printer(
            $this->getUri(),
            $this->username,
            $this->password,
            $this->curlOptions
        );
    }

    /**
     * @throws AuthenticationError
     * @throws Exception
     * @throws HTTPError
     */
    public function getPrinterAttributes(): IPPPrinterAttributes
    {
        $responsePayload = $this->getPrinter()->getAttributes();
        $printerAttributes = $responsePayload->printerAttributes ?? null;
        if ($printerAttributes
            &&!empty($printerAttributes[0])
            && $printerAttributes[0] instanceof IPPPrinterAttributes
        ) {
            return $printerAttributes[0];
        }
        throw new Exception('Error Requesting Printer attributes: ' . VarDumper::dumpAsString($responsePayload));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getSpoolDirectoryFiles(): array
    {
        return $this
            ->getSpoolerComponent()
            ->getSpoolDirectoryFiles($this->printerName);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function countSpoolDirectoryFiles(): int
    {
        return count($this->getSpoolDirectoryFiles());
    }

    public function deleteSpoolDirectoryFile(string $filename): bool
    {
        return $this
            ->getSpoolerComponent()
            ->deleteSpoolFile($this->printerName, $filename);
    }
}
