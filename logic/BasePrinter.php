<?php

namespace d3yii2\d3printeripp\logic;


use d3yii2\d3printeripp\logic\AlertConfig;
use d3yii2\d3printeripp\logic\PrinterHealth;
use d3yii2\d3printeripp\logic\PrinterSpooler;
use d3yii2\d3printeripp\logic\PrinterAttributes;
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
    protected PrinterAttributes $printerAttributes;
    protected ?PrinterDaemon $daemon = null;
    protected ?PrinterSpooler $spooler = null;
    protected ?PrinterHealth $health = null;
    protected IppPrinterClient $client;
    protected ?PrinterJobs $jobs;

    protected ?string $lastError = null;
    protected ?string $printUri = null;
    protected ?IPPPayload $responsePayload = null;

    public function __construct(PrinterConfig $config)
    {
        $this->config = $config;
        $this->init();
    }

    public function getConfig(): PrinterConfig
    {
        return $this->config;
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
        return $this->health;
    }

    public function getJobs(): PrinterJobs
    {
        return $this->jobs;
    }

    protected function init(): void
    {
        $this->client = new IppPrinterClient(
            $this->config->getUri(),
            $this->config->getUsername(),
            $this->config->getPassword()
        );

        $this->printerAttributes = new PrinterAttributes($this->config);
        $this->daemon = new PrinterDaemon($this->config);
        $this->spooler = new PrinterSpooler($this->config);
        $this->health = new PrinterHealth($this->config, new AlertConfig($this->config), $this->printerAttributes);
        $this->jobs = new PrinterJobs($this->config, $this->client);
    }

    protected function getClient(): IppPrinterClient
    {
        return $this->client;
    }

    public function getName()
    {
        return $this->config->getName();
    }

    public function isOnline(): bool
    {
        try {
            $status = $this->getStatus();

            // idle|processing|stopped
            return $status !== 'stopped';
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getStatus(): bool
    {
        try {
            $state = $this->printerAttributes->getPrinterState();

            return $state->__toString();

        } catch (\Exception $e) {
            return false;
        }
    }

    public function getPrinterOutputTray(): string
    {
        $try = $this->printerAttributes->getPrinterOutputTray();

        return $try->getAttributeValue(); //->decode($tryAttributeValues);
    }

    public function getSuppliesStatus(): array
    {
        $markerLevels = $this->printerAttributes->getMarkerLevels();
        $markerColors = $this->printerAttributes->getMarkerColors();
        $markerNames = $this->printerAttributes->getMarkerNames();
        $markerTypes = $this->printerAttributes->getMarkerTypes();

        $nameValue = $markerNames->getAttributeValue();
        $levelValue = $markerLevels->getAttributeValue();
        $colorValue = $markerColors->getAttributeValue();
        $nameValue = $markerNames->getAttributeValue();
        $typeValue = $markerTypes->getAttributeValue();

        $supplies = [
            'name' => $nameValue ?? 'Unknown',
            'level' => $levelValue ?? -1,
            'color' => $colorValue ?? null,
            'type' => $typeValue ?? null,
            'status' => $this->getSupplyStatus($levelValue ?? -1)
        ];

        return $supplies;
    }

    public function getFtpStatus()
    {

    }

    public function getSpoolerStatus()
    {

    }

    public function getDaemonStatus()
    {
        $daemon = new Daemon($this->config);
        return $daemon->getStatus();
    }

    public function getSystemInfo(): array
    {
        $printerInfo = $this->printerAttributes->getPrinterInfo();
        $printerMakeAndModel = $this->printerAttributes->getPrinterMakeAndModel();
        $printerLocation = $this->printerAttributes->getPrinterLocation();
        $deviceUri = $this->printerAttributes->getDeviceUri();

        return [
            'info' => $printerInfo->getAttributeValue(),
            'model' => $printerMakeAndModel->getAttributeValue(),
            'location' => $printerLocation,
            'deviceUri' => $deviceUri->getAttributeValue(),
        ];
    }

    public function resume(int $jobId): IPPPayload
    {
        return $this->client->resumePrinter();
    }

    public function pause(int $jobId): IPPPayload
    {
        return $this->client->pausePrinter();
    }


    protected function getSupplyStatus(int $level): string
    {
        if ($level === -1) return 'unknown';
        if ($level === -2) return 'unknown';
        if ($level === -3) return 'unknown';
        if ($level <= 10) return 'low';
        if ($level <= 25) return 'medium';
        return 'ok';
    }


    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}