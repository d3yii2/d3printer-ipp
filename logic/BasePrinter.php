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

    protected  PrinterDaemon $daemon;

    protected  PrinterSpooler $spooler;

    protected  PrinterSystem $system;

    protected PrinterSupplies $supplies;

    protected PrinterCache $cache;

    protected ?IPPPayload $responsePayload = null;

    protected ?string $lastError = null;

    public const STATUS_HEALTH = 'health';
    public const STATUS_DAEMON = 'daemon';
    public const STATUS_SPOOLER = 'spooler';
    public const STATUS_SYSTEM = 'system';
    public const STATUS_SUPPLIES = 'supplies';
    public const STATUS_FTP = 'ftp';

    public function __construct(PrinterConfig $config)
    {
        $this->config = $config;

        $this->client = new IppPrinterClient(
            $this->config->getUri(),
            $this->config->getUsername(),
            $this->config->getPassword(),
            $this->config->getCurlOptions()
        );

        $this->attributes = new PrinterAttributes($this->config);

        $this->system = new PrinterSystem(
            $this->config,
            new AlertConfig($this->config),
            $this->attributes
        );

        $this->daemon = new PrinterDaemon($this->config);

        $this->spooler = new PrinterSpooler($this->config);

        //@TODO cache this
        $refreshAttributes = $this->attributes->getAll();
        $this->supplies = new PrinterSupplies($this->config, $this->attributes);

        $this->jobs = new PrinterJobs($this->config, $this->client);

        $this->cache = new PrinterCache($this->config);
    }

    public function getDaemon(): PrinterDaemon
    {
        return $this->daemon;
    }

    public function getSpooler(): PrinterSpooler
    {
        return $this->spooler;
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

    public function getFtpStatus()
    {
        return $this->spooler->existDeadFile() ? 'ok' : 'down';
    }

    public function getSpoolerStatus()
    {
        return $this->getStatus(self::STATUS_SPOOLER);
    }

    public function updateCache()
    {
        $status = $this->getFullStatus();

        $this->cache->update($status);
    }

    public function getFullStatus(bool $forceRefresh = false)
    {
        $now = time();

        $cachedStats = $this->cache->getData('stats');
        $lastCheckedTimestamp = $cachedStats['lastChecked'] ?? null;

        if (
            !$forceRefresh &&  $lastCheckedTimestamp
            || ($now - $lastCheckedTimestamp) > $this->config->getCacheDuration()
        ) {

            if (!empty($cachedStats)) {
                return $cachedStats;
            }
        }

        $stats = [
            'lastChecked' => $now,
            self::STATUS_DAEMON => $this->getDaemonStatus(),
            self::STATUS_FTP => $this->getFtpStatus(),
            self::STATUS_SPOOLER => $this->getSpoolerStatus(),
            self::STATUS_SUPPLIES => $this->getSuppliesStatus(),
            self::STATUS_SYSTEM => $this->getSystemStatus()
        ];

        $this->cache->update(['stats' => $stats]) ;

        return $stats;
    }

    public function getSystemStatus()
    {
        return $this->getStatus(self::STATUS_SYSTEM);
    }

    public function getDaemonStatus()
    {
        return $this->getStatus(self::STATUS_DAEMON);
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