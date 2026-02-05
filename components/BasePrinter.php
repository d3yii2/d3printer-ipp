<?php

namespace d3yii2\d3printeripp\components;

use d3yii2\d3printer\components\Spooler;
use d3yii2\d3printeripp\components\rules\RulesInterface;
use d3yii2\d3printeripp\interfaces\PrinterInterface;
use d3yii2\d3printeripp\logic\cache\PrinterCache;
use d3yii2\d3printeripp\logic\PrinterAttributes;
use d3yii2\d3printeripp\logic\PrinterJobs;
use d3yii2\d3printeripp\logic\PrinterSupplies;
use d3yii2\d3printeripp\logic\PrinterSystem;
use d3yii2\d3printeripp\logic\ValueFormatter;
use obray\ipp\exceptions\AuthenticationError;
use obray\ipp\exceptions\HTTPError;
use obray\ipp\transport\IPPPayload;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Base abstract class for printer implementations
 */
class BasePrinter  extends Component implements PrinterInterface
{

    public const PRINTER_SYSTEM = 'PrinterSystem';
    public const PRINTER_SUPPLIES = 'PrinterSupplies';

    public string $slug;
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
    public array $curlOptions = [];
    public array $panel;




    protected PrinterJobs $jobs;
    protected PrinterSystem $system;
    protected PrinterSupplies $supplies;
    protected PrinterCache $cache;
    protected ?IPPPayload $responsePayload = null;
    protected ?string $lastError = null;
    protected ?Spooler $printerSpooler = null;

    public const STATUS_PRINTER_ATTRIBUTES = 'attributes';
    public const STATUS_HEALTH = 'health';
    public const STATUS_JOBS = 'jobs';
    public const STATUS_SYSTEM = 'system';
    public const STATUS_SUPPLIES = 'supplies';
    public const STATUS_ERRORS = 'errors';

//    public function __construct(PrinterConfig $config)
//    {
//        $this->config = $config;
//        $this->initializeClient();
//        $this->initializeComponents();
//    }

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

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getConfigPanel(): array
    {
        return $this->panel ?? [];
    }

    public function getJobs(): PrinterJobs
    {
        return $this->jobs;
    }

    /**
     * get actual status from printer
     * @return object
     * @throws AuthenticationError
     * @throws Exception
     * @throws HTTPError
     * @throws InvalidConfigException
     */
    public function getStatusFromPrinter(): object
    {
        $stats = $this->generateCurrentStats();
        if ($this->cacheComponentName
            && ($cache = Yii::$app->get($this->cacheComponentName, false))
        ) {
            $cache->update($this->slug, $stats);
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
        if ($this->cacheComponentName
            && ($cache = Yii::$app->get($this->cacheComponentName, false))
            && $cachedStats = $cache->getCacheData($this->slug)
        ) {
                return $cachedStats;
        }
        return $this->getStatusFromPrinter();
    }


    public function getSystemStatus()
    {
        return $this->getStatus(self::STATUS_SYSTEM);
    }


    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getStatusData(): array
    {
        $systemStatus = $this->getFullStatus();
        $systemLabels = $this->system->getLabels();

        return [
            [
                'label' => 'HOST',
                'value' => isset($systemStatus)
                    ? ValueFormatter::coloredUpDownValue($systemStatus['system']['state'])
                    : '?',
            ],
            [
                'label' => Yii::t('d3printeripp', 'Cartridge'),
                'value' => isset($status['supplies']['level'])
                    ? ValueFormatter::coloredDangerLessValue(
                        $status['supplies']['level'],
                        50, //$status['supplies']['lowLevel']
                    ) . '%'
                    : '?',
            ],
            [
                'label' => Yii::t('d3printeripp', 'IP'),
                'value' => $status['system']['host'] ?? '?',
            ],
            [
                'label' => Yii::t('d3printeripp', 'Daemon Status'),
                'value' => isset($status['daemon']['status'])
                    ? ValueFormatter::coloredUpDownValue($status['daemon']['status'])
                    : '?',
            ],
        ];
    }

    /**
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     * @throws AuthenticationError
     * @throws HTTPError
     */
    private function generateCurrentStats(): object
    {
        $attributes = new PrinterAttributes($this);
        $attributes->getAll();
        /** @var AlertConfig $alertConfig */
        $alertConfig = Yii::$app->get($this->alertConfigComponentName, false);
        $alertConfig->loadAttributes($attributes);
        return $alertConfig;
    }


    /**
     * @throws AuthenticationError
     * @throws Exception
     * @throws HTTPError
     */
    public function getAllAttributes(): array
    {
        $attributes = new PrinterAttributes($this);
        $attributes = $attributes->getAll();
        return $attributes->getAllAttributes();
    }


    public function getJobsStatus(): array
    {
        return $this->getStatus(self::STATUS_JOBS);
    }


    public function getSuppliesStatus()
    {
        return $this->getStatus(self::STATUS_SUPPLIES);
    }

    private function getStatus(string $from)
    {
        return $this->{$from}->getStatus();
    }

    /**
     * @return Spooler
     * @throws InvalidConfigException
     */
    public function getSpoolerComponent(): ?Spooler
    {
        if ($this->printerSpooler) {
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
                $this->slug,
                $filepath,
                $copies
            );
    }
}
