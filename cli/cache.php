<?php
/**
 * CLI скрипт для управления кешем Bitrix
 * Использование: php cache.php [action] [options]
 */

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/../../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;

class CacheManager
{
    private $cacheDir;
    private $managedCacheDir;

    public function __construct()
    {
        $this->cacheDir = Application::getDocumentRoot() . '/bitrix/cache';
        $this->managedCacheDir = Application::getDocumentRoot() . '/bitrix/managed_cache';
    }

    /**
     * Показать информацию о кеше
     */
    public function info()
    {
        echo "=== Информация о кеше Bitrix ===\n";
        echo "Путь к кешу: " . $this->cacheDir . "\n";
        echo "Путь к управляемому кешу: " . $this->managedCacheDir . "\n\n";

        // Основной кеш
        $cacheSize = $this->getDirectorySize($this->cacheDir);
        $cacheFiles = $this->countFiles($this->cacheDir);

        echo "Обычный кеш:\n";
        echo "  Размер: " . $this->formatBytes($cacheSize) . "\n";
        echo "  Файлов: " . $cacheFiles . "\n\n";

        // Управляемый кеш
        if (is_dir($this->managedCacheDir)) {
            $managedCacheSize = $this->getDirectorySize($this->managedCacheDir);
            $managedCacheFiles = $this->countFiles($this->managedCacheDir);

            echo "Управляемый кеш:\n";
            echo "  Размер: " . $this->formatBytes($managedCacheSize) . "\n";
            echo "  Файлов: " . $managedCacheFiles . "\n\n";
        }

        echo "Общий размер кеша: " . $this->formatBytes($cacheSize + ($managedCacheSize ?? 0)) . "\n";
        echo "Общее количество файлов: " . ($cacheFiles + ($managedCacheFiles ?? 0)) . "\n\n";

        // Статистика по типам кеша
        $this->showCacheTypeStats();
    }

    /**
     * Очистить весь кеш
     */
    public function clear()
    {
        echo "Очистка кеша...\n";

        // Очистка через API Bitrix
        $cache = Cache::createInstance();
        $cache->cleanDir();

        // Очистка управляемого кеша
        if (class_exists('\Bitrix\Main\Data\ManagedCache')) {
            \Bitrix\Main\Data\ManagedCache::clearAll();
        }

        // Дополнительная очистка файлов
        $this->clearDirectory($this->cacheDir);

        if (is_dir($this->managedCacheDir)) {
            $this->clearDirectory($this->managedCacheDir);
        }

        echo "Кеш очищен.\n";
    }

    /**
     * Очистить кеш по типу
     */
    public function clearType($type)
    {
        echo "Очистка кеша типа: $type\n";

        $cache = Cache::createInstance();

        switch ($type) {
            case 'menu':
                $cache->cleanDir('menu');
                break;
            case 'html':
                $cache->cleanDir('html_pages');
                break;
            case 'component':
                $cache->cleanDir('component');
                break;
            case 'managed':
                if (class_exists('\Bitrix\Main\Data\ManagedCache')) {
                    \Bitrix\Main\Data\ManagedCache::clearAll();
                }
                break;
            default:
                $cache->cleanDir($type);
        }

        echo "Кеш типа '$type' очищен.\n";
    }

    /**
     * Получить размер директории
     */
    private function getDirectorySize($directory)
    {
        $size = 0;

        if (!is_dir($directory)) {
            return $size;
        }

        $dir = new Directory($directory);
        if ($dir->isExists()) {
            $children = $dir->getChildren();
            foreach ($children as $child) {
                if ($child->isDirectory()) {
                    $size += $this->getDirectorySize($child->getPath());
                } else {
                    $size += $child->getSize();
                }
            }
        }

        return $size;
    }

    /**
     * Подсчитать количество файлов в директории
     */
    private function countFiles($directory)
    {
        $count = 0;

        if (!is_dir($directory)) {
            return $count;
        }

        $dir = new Directory($directory);
        if ($dir->isExists()) {
            $children = $dir->getChildren();
            foreach ($children as $child) {
                if ($child->isDirectory()) {
                    $count += $this->countFiles($child->getPath());
                } else {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Очистить директорию
     */
    private function clearDirectory($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $dir = new Directory($directory);
        if ($dir->isExists()) {
            $children = $dir->getChildren();
            foreach ($children as $child) {
                if ($child->isDirectory()) {
                    $child->delete();
                } else {
                    $child->delete();
                }
            }
        }
    }

    /**
     * Форматировать размер в байтах
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Показать статистику по типам кеша
     */
    private function showCacheTypeStats()
    {
        echo "=== Статистика по типам кеша ===\n";

        $cacheTypes = [
            'menu' => 'Меню',
            'html_pages' => 'HTML страницы',
            'component' => 'Компоненты',
            'iblock' => 'Инфоблоки',
            'search' => 'Поиск',
            'form' => 'Веб-формы'
        ];

        foreach ($cacheTypes as $type => $name) {
            $typePath = $this->cacheDir . '/' . $type;
            if (is_dir($typePath)) {
                $size = $this->getDirectorySize($typePath);
                $files = $this->countFiles($typePath);

                if ($size > 0) {
                    echo sprintf("%-15s: %s (%d файлов)\n", $name, $this->formatBytes($size), $files);
                }
            }
        }
        echo "\n";
    }
}

// Обработка аргументов командной строки
function showHelp()
{
    echo "Управление кешем Bitrix\n";
    echo "Использование: php cache.php [команда] [опции]\n\n";
    echo "Команды:\n";
    echo "  info               - показать информацию о кеше\n";
    echo "  clear              - очистить весь кеш\n";
    echo "  clear-type [type]  - очистить кеш определенного типа\n";
    echo "  help               - показать эту справку\n\n";
    echo "Типы кеша для clear-type:\n";
    echo "  menu      - кеш меню\n";
    echo "  html      - HTML страницы\n";
    echo "  component - компоненты\n";
    echo "  managed   - управляемый кеш\n";
    echo "  iblock    - инфоблоки\n";
    echo "  search    - поиск\n";
    echo "  form      - веб-формы\n\n";
    echo "Примеры:\n";
    echo "  php cache.php info\n";
    echo "  php cache.php clear\n";
    echo "  php cache.php clear-type menu\n";
}

// Основная логика
$cacheManager = new CacheManager();

$action = $argv[1] ?? 'info';

switch ($action) {
    case 'info':
        $cacheManager->info();
        break;

    case 'clear':
        $cacheManager->clear();
        break;

    case 'clear-type':
        $type = $argv[2] ?? '';
        if (empty($type)) {
            echo "Ошибка: не указан тип кеша\n";
            showHelp();
            exit(1);
        }
        $cacheManager->clearType($type);
        break;

    case 'help':
    case '--help':
    case '-h':
        showHelp();
        break;

    default:
        echo "Неизвестная команда: $action\n";
        showHelp();
        exit(1);
}