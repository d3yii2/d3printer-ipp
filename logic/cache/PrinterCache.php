<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\logic\cache;

use d3system\helpers\D3FileHelper;
use yii\base\Component;
use yii\base\Exception;
use yii\caching\Cache;
use yii\caching\FileCache;

/**
 *
 */
class PrinterCache extends Component
{
    private const UPDATED_AT = 'updated';

    public ?string $cachePath = 'ippPrinterCache';
    public int $cacheDuration = 30;

    private Cache $cache;

    private const CACHE_KEY_PREFIX = 'printerCache_';

    /**
     * @throws Exception
     */
    public function init(): void
    {
        $this->cache = new FileCache([
            'cachePath' => D3FileHelper::getRuntimeDirectoryPath($this->cachePath),
        ]);

    }

    /**
     * @param string $printerSlug
     * @param array $data
     * @return void
     */
    public function update(string $printerSlug, array $data): void
    {
        $this->cache->set(
            $this->getCacheKey($printerSlug),
            self::enrichDataWithTimestamp($data),
            $this->cacheDuration
        );
    }

    /**
     * @param string $printerSlug
     * @return string
     */
    private function getCacheKey(string $printerSlug): string
    {
        return self::CACHE_KEY_PREFIX . $printerSlug;
    }

    /**
     * @param string $printerSlug
     * @return array
     */
    public function getCacheData(string $printerSlug): array
    {
        $data = $this->cache->get($this->getCacheKey($printerSlug));
        return is_array($data) ? $data : [];
    }

    /**
     * @param array $data
     * @return array
     */
    private static function enrichDataWithTimestamp(array $data): array
    {
        $data[self::UPDATED_AT] = date('Y-m-d H:i:s');
        return $data;
    }
}

