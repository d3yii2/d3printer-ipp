<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\logic\AlertConfig;
use d3yii2\d3printeripp\logic\PrinterAttributes;
use d3yii2\d3printeripp\logic\PrinterConfig;
use d3yii2\d3printeripp\logic\PrinterDaemon;
use d3yii2\d3printeripp\logic\PrinterHealth;
use d3yii2\d3printeripp\logic\PrinterJobs;
use d3yii2\d3printeripp\logic\PrinterSpooler;
use d3yii2\d3printeripp\logic\PrinterSupplies;
use d3yii2\d3printeripp\logic\PrinterSystem;
use obray\ipp\Printer as IppPrinterClient;
use obray\ipp\transport\IPPPayload;
use Yii;

class PrinterData
{
    protected PrinterAttributes $printerAttributes;

    protected PrinterCache $printerCache;

    protected ?IPPPayload $responsePayload = null;

    protected ?string $lastError = null;

    public const DATA_FTP = 'ftp';
    public const DATA_DAEMON = 'daemon';
    public const DATA_SPOOLER = 'spooler';
    public const DATA_SYSTEM = 'system';
    public const DATA_SUPPLIES = 'supplies';

    public function __construct(PrinterConfig $config, IppPrinterClient $client)
    {
        $this->config = $config;
        $this->client = $client;

        $this->printerAttributes = new PrinterAttributes($this->config);
        $this->system = new PrinterSystem($this->config, new AlertConfig($this->config), $this->printerAttributes);
        $this->daemon = new PrinterDaemon($this->config);
        $this->spooler = new PrinterSpooler($this->config);
        $this->supplies = new PrinterSupplies($this->config, $this->printerAttributes);
    }

    public function getDaemon(): PrinterDaemon
    {
        return $this->daemon;
    }

    public function getSpooler(): PrinterSpooler
    {
        return $this->spooler;
    }

    public function getHealth(): PrinterHealth
    {
        return $this->system;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }


    public function __get(string $type)
    {
        $data = Yii::$app->cache->getOrSet(
            'printerCache_' . $this->config->getSlug(),
            function()  {
                return [
                    'lastRequest' => time(),
                    'data' => [
                        self::DATA_DAEMON => $this->daemon->buildStats(),
                        self::DATA_SPOOLER => $this->spooler->buildStats(),
                        self::DATA_SYSTEM => $this->system->buildStats()
                    ]
                ];
            },
            $this->config->getCacheExpire()
        );

        return $data['data'][$type] ?? [];
    }
}