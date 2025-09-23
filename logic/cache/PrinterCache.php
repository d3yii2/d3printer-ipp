<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\logic\cache;

use d3yii2\d3printeripp\logic\PrinterConfig;
use yii\caching\Cache;
use Yii;

/**
 *
 */
class PrinterCache
{
    private int $cacheExpire;
    private Cache $cache;
    private PrinterConfig $config;

    public const LAST_CHECKED_TIMESTAMP = 'lastCheckedDatetime';
    private const UPDATED_AT = 'updated_at';
    private const CACHE_KEY_PREFIX = 'printerCache_';
    private const DEFAULT_CACHE_DURATION = 30;

    /**
     * @param PrinterConfig $config
     */
    public function __construct(PrinterConfig $config)
    {
        $this->config = $config;
        $this->cache = Yii::$app->printerIppCache;
        $this->cache->keyPrefix = $config->getSlug();
        $this->cacheExpire = $config->getCacheDuration() ?? self::DEFAULT_CACHE_DURATION;
    }

    /**
     * @return mixed|null
     */
    public function getLastCheckedTimestamp()
    {
        return $this->getData(self::LAST_CHECKED_TIMESTAMP);
    }

    /**
     * @return mixed|null
     */
    public function getData(string $type)
    {
        $cacheData = $this->getCacheData();
        return $cacheData[$type] ?? null;
    }

    /**
     * @return void
     */
    public function update(array $data)
    {
        $enrichedData = PrinterCache::enrichDataWithTimestamp($data);
        $this->cache->set($this->getCacheKey(), $enrichedData, $this->cacheExpire);
    }

    /**
     * @return int
     */
    public function getCacheExpire(): int
    {
        return $this->cacheExpire;
    }

    /**
     * @return string
     */
    private function getCacheKey(): string
    {
        return self::CACHE_KEY_PREFIX . $this->config->getSlug();
    }

    /**
     * @return array
     */
    private function getCacheData(): array
    {
        $data = $this->cache->get($this->getCacheKey());
        return is_array($data) ? $data : [];
    }

    /**
     * @param array $data
     * @return array
     */
    private static function enrichDataWithTimestamp(array $data): array
    {
        //@TODO - use configured format from Yii date formatter
        $data[self::UPDATED_AT] = date('d.m.Y H:i:s');
        return $data;
    }
}

