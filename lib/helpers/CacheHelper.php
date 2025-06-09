<?php

namespace DD\Tools\Helpers;

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
}