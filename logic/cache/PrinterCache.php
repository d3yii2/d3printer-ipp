<?php

namespace d3yii2\d3printeripp\logic\cache;

use d3yii2\d3printeripp\logic\PrinterConfig;
use yii\caching\Cache;
use Yii;

class PrinterCache
{
    private int $cacheExpire;

    private PrinterConfig $config;

    private Cache  $cache;

    public const LAST_CHECKED_TIMESTAMP = 'lastCheckedDatetime';

    public function __construct(PrinterConfig $config)
    {
        $this->config = $config;
        $this->cache = Yii::$app->cache;
        $this->cacheExpire = $this->config->getCacheDuration() ?? 30;
    }

    public function getLastCheckedTimestamp()
    {
        return $this->getData(self::LAST_CHECKED_TIMESTAMP);
    }

    public function getData(string $type)
    {
        $data = $this->cache->get($this->getCacheKey());

        return $data[$type] ?? null;
    }

    public function update(array $data)
    {
        //@TODO - use configured format from Yii date formater
        $data['updated_at']  = date('d.m.Y H:i:s');

        $this->cache->set($this->getCacheKey(), $data);
    }

    public function getCacheExpire()
    {
        return $this->cacheExpire;
    }

    private function getCacheKey()
    {
        return 'printerCache_' . $this->config->getSlug();
    }
}