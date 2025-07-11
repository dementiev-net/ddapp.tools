<?php

namespace DDAPP\Tools;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\SiteTable;
use Bitrix\Main\IO\Directory;
use DDAPP\Tools\Main;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\FileHelper;

class freespaceAgent
{
    private const FREE_SPACE_EMAIL_TEMPLATE_CODE = "DDAPP_MESSAGE_DISK";
    private const TIME_OUT = 30;

    /**
     * Запуск
     * @return string
     */
    static public function run()
    {
        // Настройка логирования
        LogHelper::configure();

        LogHelper::info("cron", "freespaceAgent run!");

        try {
            self::doFreeSpace();
        } catch (\Exception $e) {
            LogHelper::error("cron", "freespaceAgent error: " . $e->getMessage());
        }

        return "\\DDAPP\\Tools\\freespaceAgent::run();";
    }

    /**
     * Работа агента
     * @return void
     */
    private static function doFreeSpace(): void
    {
        if (!\CModule::IncludeModule('iblock')) {
            LogHelper::error("cron", "iblock module not included");
            return;
        }

        // Получение параметров
        $config = [
            'wantSpace' => (float)Option::get(Main::MODULE_ID, "disk_free_space") * (1024 * 1024),
            'emailTo' => Option::get(Main::MODULE_ID, "disk_email"),
            'deleteCache' => Option::get(Main::MODULE_ID, "disk_delete_cache") === "Y",
            'enabled' => Option::get(Main::MODULE_ID, "disk_enabled") === "Y",
            'emailNotifier' => Option::get(Main::MODULE_ID, "disk_email_enabled") === "Y",
            'typeFilesystem' => Option::get(Main::MODULE_ID, "disk_type_filesystem"),
            'allSpace' => (float)Option::get(Main::MODULE_ID, "disk_all_space")
        ];

        // Проверка активности агента
        if (!$config['enabled']) {
            LogHelper::warning("cron", "freespaceAgent is disabled");
            return;
        }

        // Получение информации о дисковом пространстве
        $spaceInfo = self::getSpaceInfo($config);

        // Сохранение информации о занятом пространстве
        Option::set(Main::MODULE_ID, "disk_busy_place", $spaceInfo['busySpace']);

        LogHelper::info("cron", sprintf(
            "Space info - Free: %s, Total: %s, Busy: %s, Want: %s",
            FileHelper::formatBytes($spaceInfo['freeSpace']),
            FileHelper::formatBytes($spaceInfo['totalSpace']),
            FileHelper::formatBytes($spaceInfo['busySpace']),
            FileHelper::formatBytes($config['wantSpace'])
        ));

        // Проверка свободного места
        if ($spaceInfo['freeSpace'] > $config['wantSpace']) {
            LogHelper::info("cron", "Free space is sufficient");
            return;
        }

        LogHelper::warning("cron", "Free space is insufficient, starting cleanup");

        // Отправка уведомления по email
        if ($config['emailNotifier']) {
            self::sendNotification($spaceInfo, $config);
        }

        // Очистка кэша
        self::clearCache();
    }

    /**
     * Подсчет места
     * @param array $config
     * @return array
     */
    private static function getSpaceInfo(array $config): array
    {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];

        if ($config['typeFilesystem'] == 1) {
            // Ручной подсчет размера
            $busySpace = self::getDirSize($documentRoot);
            $totalSpace = $config['allSpace'];
            $freeSpace = $totalSpace - $busySpace;
        } else {
            // Системные функции
            $freeSpace = disk_free_space($documentRoot);
            $totalSpace = disk_total_space($documentRoot);
            $busySpace = $totalSpace - $freeSpace;
        }

        return [
            'freeSpace' => $freeSpace,
            'totalSpace' => $totalSpace,
            'busySpace' => $busySpace
        ];
    }

    /**
     * @param $dir
     * @return float
     */
    private static function getDirSize($dir)
    {
        $size = 0.0; // ← теперь float

        if (!is_dir($dir)) {
            return 0.0;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += (float)$file->getSize(); // ← явно приводим к float
            }
        }

        return $size;
    }

    /**
     * Отправка письма
     * @param array $spaceInfo
     * @param array $config
     * @return void
     */
    private static function sendNotification(array $spaceInfo, array $config): void
    {
        try {
            $fields = [
                "FREE_SPACE" => number_format($spaceInfo['freeSpace'], 2, '.', ' '),
                "TOTAL_SPACE" => number_format($spaceInfo['totalSpace'], 2, '.', ' '),
                "EMAIL_TO" => $config['emailTo'],
                "WANT_SPACE" => number_format($config['wantSpace'], 2, '.', ' ')
            ];

            // Получение активных сайтов
            $sites = SiteTable::getList([
                'select' => ['LID'],
                'filter' => ['ACTIVE' => 'Y']
            ]);

            $siteIds = [];
            while ($site = $sites->fetch()) {
                $siteIds[] = $site['LID'];
            }

            if (!empty($siteIds)) {
                Event::send([
                    "EVENT_NAME" => self::FREE_SPACE_EMAIL_TEMPLATE_CODE,
                    "LID" => $siteIds[0],
                    "C_FIELDS" => $fields
                ]);

                LogHelper::info("cron", "Email notification sent to: " . $config['emailTo']);
            }
        } catch (\Exception $e) {
            LogHelper::error("cron", "Failed to send email notification: " . $e->getMessage());
        }
    }

    /**
     * Очистка кеша
     * @return void
     */
    private static function clearCache(): void
    {
        if (defined('BX_CACHE_TYPE')) {
            LogHelper::info("cron", "Cache type is defined, skipping cache cleanup");
            return;
        }

        try {
            require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/cache_files_cleaner.php");

            $cacheCleaner = new \CFileCacheCleaner(false);

            if (!$cacheCleaner->InitPath("")) {
                LogHelper::error("cron", "Failed to initialize cache cleaner path");
                return;
            }

            $endTime = time() + self::TIME_OUT;
            $cacheCleaner->Start();
            $deletedFiles = 0;

            while ($file = $cacheCleaner->GetNextFile()) {
                if (is_string($file)) {
                    if (@unlink($file)) {
                        $deletedFiles++;
                    }
                }

                // Проверка таймаута (если не cron)
                if (time() >= $endTime && !(defined('BX_CRONTAB') && BX_CRONTAB)) {
                    LogHelper::info("cron", "Cache cleanup timeout reached, deleted {$deletedFiles} files");
                    return;
                }
            }

            LogHelper::info("cron", "Cache cleanup completed, deleted {$deletedFiles} files");
        } catch (\Exception $e) {
            LogHelper::error("cron", "Cache cleanup failed: " . $e->getMessage());
        }
    }
}