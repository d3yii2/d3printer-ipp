<?php

namespace d3yii2\d3printeripp\logic\cache;

use d3yii2\d3printeripp\logic\PrinterConfig;
use yii\caching\Cache;
use Yii;

class PrinterCache
{
    private int $cacheExpire;
    private PrinterConfig $config;
    private Cache $cache;

    public const LAST_CHECKED_TIMESTAMP = 'lastCheckedDatetime';
    private const UPDATED_AT = 'updated_at';
    private const CACHE_KEY_PREFIX = 'printerCache_';
    private const DEFAULT_CACHE_DURATION = 30;

    public function __construct(PrinterConfig $config)
    {
        $this->config = $config;
        $this->cache = Yii::$app->cache;
        $this->cacheExpire = $this->config->getCacheDuration() ?? self::DEFAULT_CACHE_DURATION;
    }

    public function getLastCheckedTimestamp()
    {
        return $this->getData(self::LAST_CHECKED_TIMESTAMP);
    }

    public function getData(string $type)
    {
        $cacheData = $this->getCacheData();
        return $cacheData[$type] ?? null;
    }

    public function update(array $data)
    {
        $enrichedData = $this->enrichDataWithTimestamp($data);
        $this->cache->set($this->getCacheKey(), $enrichedData, $this->cacheExpire);
    }

    public function getCacheExpire(): int
    {
        return $this->cacheExpire;
    }

    private function getCacheKey(): string
    {
        return self::CACHE_KEY_PREFIX . $this->config->getSlug();
    }

    private function getCacheData(): array
    {
        $data = $this->cache->get($this->getCacheKey());
        return is_array($data) ? $data : [];
    }

    private function enrichDataWithTimestamp(array $data): array
    {
        //@TODO - use configured format from Yii date formatter
        $data[self::UPDATED_AT] = date('d.m.Y H:i:s');
        return $data;
    }
}