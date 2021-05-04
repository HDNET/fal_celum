<?php

declare(strict_types=1);

namespace HDNET\FalCelum;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Cache
{
    public const IDENTIFIER = 'fal_celum';

    public const DEFAULT_LIFE_TIME = 30 * 60;

    protected FrontendInterface $internalCache;

    protected static array $runTimeCache = [];

    public function __construct()
    {
        $this->internalCache = GeneralUtility::makeInstance(CacheManager::class)->getCache(self::IDENTIFIER);
    }

    public function cache(string $identifier, callable $callback)
    {
        if ($this->internalCache->has($identifier)) {
            return $this->internalCache->get($identifier);
        }
        $result = $callback();
        $this->internalCache->set($identifier, $result);
        return $result;
    }

    public function cacheRuntime(string $identifier, callable $callback)
    {
        if (array_key_exists($identifier, self::$runTimeCache)) {
            return self::$runTimeCache[$identifier];
        }

        $result = $callback();
        self::$runTimeCache[$identifier] = $result;
        return $result;
    }
}
