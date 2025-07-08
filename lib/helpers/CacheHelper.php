<?php

namespace DDAPP\Tools\Helpers;

class CacheHelper
{
    /**
     * Получает значение из кэша или выполняет callback
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @return mixed
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();

        if ($cache->initCache($ttl, $key)) {
            return $cache->getVars();
        }

        $result = $callback();

        if ($cache->startDataCache()) {
            $cache->endDataCache($result);
        }

        return $result;
    }

    /**
     * Очищает кэш по тегу
     * @param string $tag
     * @return void
     */
    public static function clearByTag(string $tag): void
    {
        $taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();
        $taggedCache->clearByTag($tag);
    }

    /**
     * Вычисляет размер папок кеша
     * @return int
     */
    public static function checkCacheSize(): int
    {
        $folderCache = self::dirSize($_SERVER['DOCUMENT_ROOT'] . "/bitrix/cache/");
        $folderManagedCache = self::dirSize($_SERVER['DOCUMENT_ROOT'] . "/bitrix/managed_cache/");
        $folderStackCache = self::dirSize($_SERVER['DOCUMENT_ROOT'] . "/bitrix/stack_cache/");

        return ($folderCache + $folderManagedCache + $folderStackCache);
    }

    /**
     * Вычисляет размер папки
     * @param $dir
     * @return int
     */
    public static function dirSize($dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}