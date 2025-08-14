<?php

namespace d3yii2\d3printeripp\logic\cache;

use d3yii2\d3printeripp\logic\PrinterConfig;

class PrinterCache
{
    private array $health = [];
    private array $daemon = [];
    private array $jobs = [];
    private array $spooler = [];
    private int $cacheExpire;

    private PrinterConfig $config;

    public function __construct(PrinterConfig $config)
    {
        $this->config = $config;
        $this->cacheExpire = $this->config->getCacheExpire() ?? 30;
    }

    public function getHealth()
    {
        return $this->health;
    }

    public function getDaemon()
    {
        return $this->daemon;
    }

    public function getJobs()
    {
        return $this->jobs;
    }

    public function getSpooler()
    {
        return $this->spooler;
    }

    public function setHealth(array $data)
    {
        $this->health = $data;
    }

    public function setDaemon(array $data)
    {
        $this->daemon = $data;
    }

    public function setJobs(array $data)
    {
        $this->jobs = $data;
    }

    public function setSpooler(array $data)
    {
        $this->spooler = $data;
    }

    public function getCacheExpire()
    {
        return $this->cacheExpire;
    }
}