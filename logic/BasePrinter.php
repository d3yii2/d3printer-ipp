<?php

namespace d3yii2\d3printeripp\logic;


use d3yii2\d3printeripp\logic\AlertConfig;
use d3yii2\d3printeripp\logic\PrinterData;
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
    protected PrinterJobs $jobs;
    protected PrinterData $data;

    public function __construct(PrinterConfig $config)
    {
        $this->config = $config;
        $this->client = new IppPrinterClient(
            $this->config->getUri(),
            $this->config->getUsername(),
            $this->config->getPassword(),
            $this->config->getCurlOptions()
        );

        $this->jobs = new PrinterJobs($this->config, $this->client);

        $this->data = new PrinterData($config, $this->client);
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

    public function getData(string $type)
    {
        return $this->data->{$type};
    }

    public function getFtpStatus()
    {
        return $this->getData(PrinterData::DATA_FTP);
    }

    public function getSpoolerStatus()
    {
        return $this->getData(PrinterData::DATA_SPOOLER);
    }

    public function getHealthStatus()
    {
        return $this->getData(PrinterData::DATA_HEALTH);
    }

    public function getDaemonStatus()
    {
        return $this->getData(PrinterData::DATA_DAEMON);
    }

    public function getSuppliesStatus()
    {
        return $this->getData(PrinterData::DATA_SUPPLIES);
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