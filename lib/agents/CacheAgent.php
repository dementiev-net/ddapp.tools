<?php

namespace DDAPP\Tools;

use Bitrix\Main\IO\Directory;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\FileHelper;
use DDAPP\Tools\Helpers\CacheHelper;

class cacheAgent
{
    /**
     * Запуск
     * @return string
     */
    static public function run()
    {
        // Настройка логирования
        LogHelper::configure();

        LogHelper::info("cron", "cacheAgent run!");

        try {
            self::doClearCach();
        } catch (\Exception $e) {
            LogHelper::error("cron", "cacheAgent error: " . $e->getMessage());
        }

        return "\\DDAPP\\Tools\\cacheAgent::run();";
    }

    /**
     * Работа агента
     * @return void
     */
    private static function doClearCach(): void
    {
        $documentRoot = $_SERVER["DOCUMENT_ROOT"];

        // Получаем размеры папок до очистки
        $folderCache = CacheHelper::dirSize($documentRoot . "/bitrix/cache/");
        $folderManagedCache = CacheHelper::dirSize($documentRoot . "/bitrix/managed_cache/");
        $folderStackCache = CacheHelper::dirSize($documentRoot . "/bitrix/stack_cache/");

        $totalSizeBefore = $folderCache + $folderManagedCache + $folderStackCache;

        LogHelper::info("cron", "- Cache folder: " . FileHelper::formatBytes($folderCache));
        LogHelper::info("cron", "- Managed cache folder: " . FileHelper::formatBytes($folderManagedCache));
        LogHelper::info("cron", "- Stack cache folder: " . FileHelper::formatBytes($folderStackCache));
        LogHelper::info("cron", "- Total size: " . FileHelper::formatBytes($totalSizeBefore));

        // Очистка кэша
        $cleanedFolders = [];

        // Очистка обычного кэша
        if (self::clearDirectory($documentRoot . "/bitrix/cache/")) {
            $cleanedFolders[] = "cache";
        }

        // Очистка управляемого кэша
        if (self::clearDirectory($documentRoot . "/bitrix/managed_cache/")) {
            $cleanedFolders[] = "managed_cache";
        }

        // Очистка стекового кэша
        if (self::clearDirectory($documentRoot . "/bitrix/stack_cache/")) {
            $cleanedFolders[] = "stack_cache";
        }

        // Получаем размеры после очистки
        $folderCacheAfter = CacheHelper::dirSize($documentRoot . "/bitrix/cache/");
        $folderManagedCacheAfter = CacheHelper::dirSize($documentRoot . "/bitrix/managed_cache/");
        $folderStackCacheAfter = CacheHelper::dirSize($documentRoot . "/bitrix/stack_cache/");

        $totalSizeAfter = $folderCacheAfter + $folderManagedCacheAfter + $folderStackCacheAfter;
        $freedSpace = $totalSizeBefore - $totalSizeAfter;

        LogHelper::info("cron", "Cache cleanup completed. Results:");
        LogHelper::info("cron", "- Cleaned folders: " . implode(", ", $cleanedFolders));
        LogHelper::info("cron", "- Freed space: " . FileHelper::formatBytes($freedSpace));
        LogHelper::info("cron", "- Total size after cleanup: " . FileHelper::formatBytes($totalSizeAfter));
    }

    /**
     * Очищает содержимое директории
     * @param string $dir
     * @return bool
     */
    private static function clearDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        try {
            $directory = new Directory($dir);
            if ($directory->isExists()) {
                // Получаем список файлов и папок
                $children = $directory->getChildren();

                foreach ($children as $child) {
                    if ($child->isDirectory()) {
                        // Рекурсивно удаляем папки
                        self::removeDirectory($child->getPath());
                    } else {
                        // Удаляем файлы
                        $child->delete();
                    }
                }

                return true;
            }
        } catch (\Exception $e) {
            LogHelper::error("cron", "Error clearing directory {$dir}: " . $e->getMessage());
            return false;
        }

        return false;
    }

    /**
     * Рекурсивно удаляет директорию со всем содержимым
     * @param string $dir
     * @return bool
     */
    private static function removeDirectory($dir)
    {
        try {
            $directory = new Directory($dir);
            if ($directory->isExists()) {
                $directory->delete();
                return true;
            }
        } catch (\Exception $e) {
            LogHelper::error("cron", "Error removing directory {$dir}: " . $e->getMessage());
        }

        return false;
    }
}