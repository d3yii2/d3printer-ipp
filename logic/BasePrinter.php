<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printer\components\Spooler;
use d3yii2\d3printeripp\logic\cache\PrinterCache;
use d3yii2\d3printeripp\interfaces\PrinterInterface;
use obray\ipp\transport\IPPPayload;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Base abstract class for printer implementations
 */
abstract class BasePrinter implements PrinterInterface
{

    public const PRINTER_SYSTEM = 'PrinterSystem';
    public const PRINTER_SUPPLIES = 'PrinterSupplies';
    public string $slug;
    public string $name;
    public ?string $daemonName = null;
    public string $host;
    public int $port;
    public ?string $username = null;
    public ?string $password = null;
    public ?string $pincode = null;
    public bool $encryption = false;
    public int $timeout;
    public int $cacheDuration;
    public int $spoolerComponentName;
    public int $alertConfigComponentName;
    public int $cacheComponentName;
    public array $curlOptions;
    public array $gatherStates = [
        self::PRINTER_SYSTEM => [PrinterSystem::STATUS_UP_DOWN],
        self::PRINTER_SUPPLIES => [PrinterSupplies::STATUS_MARKER_LEVEL],
    ];
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
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getFullStatus(bool $forceRefresh = false): array
    {
        /** @var PrinterCache $cache */
        $cache = Yii::$app->get($this->cacheComponentName, false);
        if (!$forceRefresh) {
            $cachedStats = $cache->getCacheData($this->slug);
            if ($cachedStats) {
                return $cachedStats;
            }
        }

        $stats = $this->generateCurrentStats();
        $cache->update($this->slug, $stats);
        return $stats;
    }


    public function getSystemStatus()
    {
        return $this->getStatus(self::STATUS_SYSTEM);
    }


    public function getStatusData()
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
     * @throws InvalidConfigException
     * @throws Exception
     */
    private function generateCurrentStats(): array
    {
        $attributes = new PrinterAttributes($this);
        $alertConfig = Yii::$app->get($this->alertConfigComponentName, false);
        $system = new PrinterSystem($alertConfig, $attributes);

        $this->supplies = new PrinterSupplies($attributes, $alertConfig);
//        $this->jobs = new PrinterJobs($this->config, $this->client);

        return [
            'lastChecked' => time(),
//            self::STATUS_PRINTER_ATTRIBUTES => $this->getPrinterAttributesStatus(),
            self::STATUS_SUPPLIES => $this->getStatus($this->gatherStates[self::PRINTER_SUPPLIES]),
            self::STATUS_SYSTEM => $system->getStatus($this->gatherStates[self::PRINTER_SYSTEM]),
            //self::STATUS_JOBS => $this->getJobsStatus(),
        ];
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
    public function getSpoolerComponent()
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
     * @throws InvalidConfigException
     */
    public function printToSpoolDirectory(string $filepath, int $copies = 1)
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
