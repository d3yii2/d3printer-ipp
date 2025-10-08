<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\logic\AlertConfig;
use d3yii2\d3printeripp\logic\cache\PrinterCache;
use d3yii2\d3printeripp\logic\PrinterAttributes;
use d3yii2\d3printeripp\logic\PrinterHealth;
use d3yii2\d3printeripp\logic\PrinterSpooler;
use d3yii2\d3printeripp\types\PrinterAttributesTypes;
use obray\ipp\Attribute;
use obray\ipp\enums\PrinterState;
use obray\ipp\Printer as IppPrinterClient;
use d3yii2\d3printeripp\interfaces\PrinterInterface;
use obray\ipp\transport\IPPPayload;
use yii\base\Exception;

/**
 * Base abstract class for printer implementations
 */
abstract class BasePrinter implements PrinterInterface
{
    protected PrinterConfig $config;
    protected IppPrinterClient $client;
    protected PrinterAttributes $attributes;
    protected PrinterJobs $jobs;
    protected PrinterSystem $system;
    protected PrinterSupplies $supplies;
    protected PrinterCache $cache;
    protected ?IPPPayload $responsePayload = null;
    protected ?string $lastError = null;

    public const STATUS_PRINTER_ATTRIBUTES = 'attributes';
    public const STATUS_HEALTH = 'health';
    public const STATUS_JOBS = 'jobs';
    public const STATUS_SYSTEM = 'system';
    public const STATUS_SUPPLIES = 'supplies';
    public const STATUS_ERRORS = 'errors';

    public function __construct(PrinterConfig $config)
    {
        $this->config = $config;
        $this->initializeClient();
        $this->initializeComponents();
    }

    private function initializeClient(): void
    {
        $this->client = new IppPrinterClient(
            $this->config->getUri(),
            $this->config->getUsername(),
            $this->config->getPassword(),
            $this->config->getCurlOptions()
        );
    }

    private function initializeComponents(): void
    {
        $this->attributes = new PrinterAttributes($this->config);
        $alertConfig = new AlertConfig($this->config);
        $this->system = new PrinterSystem($this->config, $alertConfig, $this->attributes);
        $this->supplies = new PrinterSupplies($this->config, $this->attributes, $alertConfig);
        $this->jobs = new PrinterJobs($this->config, $this->client);
        $this->cache = new PrinterCache($this->config);
    }


    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getConfig(): PrinterConfig
    {
        return $this->config;
    }

    public function getJobs(): PrinterJobs
    {
        return $this->jobs;
    }

    protected function getClient(): IppPrinterClient
    {
        return $this->client;
    }

    public function getName()
    {
        return $this->config->getName();
    }


    public function updateCache()
    {
        $status = $this->getFullStatus();
        $this->cache->update($status);
    }

    public function getFullStatus(bool $forceRefresh = false)
    {
        if (!$forceRefresh) {
            $cachedStats = $this->getCachedStatsIfValid();
            if ($cachedStats !== null) {
                return $cachedStats;
            }
        }

        $stats = $this->generateCurrentStats();
        $this->cache->update(['stats' => $stats]);
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
                'label' => PrinterIPP::getLabel($systemLabels[PrinterSystem::STATUS_HOST]),
                'value' => isset($systemStatus)
                    ? ValueFormatter::coloredUpDownValue($status['system']['state'])
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

    private function getCachedStatsIfValid(): ?array
    {
        $cachedStats = $this->cache->getData('stats');
        $lastCheckedTimestamp = $cachedStats['lastChecked'] ?? null;

        if (empty($cachedStats) || !$lastCheckedTimestamp) {
            return null;
        }

        $now = time();
        $isCacheExpired = ($now - $lastCheckedTimestamp) > $this->config->getCacheDuration();

        return $isCacheExpired ? null : $cachedStats;
    }

    private function generateCurrentStats(): array
    {

        return [
            'lastChecked' => time(),
            self::STATUS_PRINTER_ATTRIBUTES => $this->getPrinterAttributesStatus(),
            self::STATUS_SUPPLIES => $this->getSuppliesStatus(),
            self::STATUS_SYSTEM => $this->getSystemStatus(),
            self::STATUS_JOBS => $this->getJobsStatus(),
        ];
    }
    
    public function getPrinterAttributesStatus(): array
    {
        return $this->getStatus(self::STATUS_PRINTER_ATTRIBUTES);
    }

    public function getJobsStatus(): array
    {
        return $this->getStatus(self::STATUS_JOBS);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function loadPrinterAttributes()
    {
        $this->attributes->getAll(); // Request and load attributes from the printer
    }

    public function getSuppliesStatus()
    {
        return $this->getStatus(self::STATUS_SUPPLIES);
    }

    private function getStatus(string $from)
    {
        return $this->{$from}->getStatus();
    }

    public function resume(int $jobId): IPPPayload
    {
        return $this->client->resumePrinter();
    }

    public function pause(int $jobId): IPPPayload
    {
        return $this->client->pausePrinter();
    }
}
