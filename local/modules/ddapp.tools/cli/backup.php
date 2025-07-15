<?php
/**
 * CLI скрипт для управления бэкапами Bitrix
 * Использование: php backup.php [action] [options]
 */

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/../../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\DB\Connection;

class BackupManager
{
    private $backupDir;
    private $documentRoot;
    private $maxBackups;
    private $dbConnection;
    private $config;

    public function __construct()
    {
        $this->documentRoot = Application::getDocumentRoot();
        $this->backupDir = $this->documentRoot . '/bitrix/backup';
        $this->maxBackups = 10; // Максимальное количество бэкапов
        $this->dbConnection = Application::getConnection();
        $this->config = Configuration::getValue('connections')['default'];

        // Создаем директорию для бэкапов если её нет
        $this->ensureBackupDirectory();
    }

    /**
     * Показать информацию о бэкапах
     */
    public function info()
    {
        echo "=== Информация о бэкапах Bitrix ===\n";
        echo "Путь к бэкапам: " . $this->backupDir . "\n";
        echo "Максимальное количество бэкапов: " . $this->maxBackups . "\n\n";

        $backups = $this->getBackupList();

        if (empty($backups)) {
            echo "Бэкапы не найдены.\n";
            return;
        }

        echo "Список бэкапов:\n";
        echo str_repeat("=", 80) . "\n";
        printf("%-20s %-15s %-15s %-20s\n", "Дата создания", "Размер", "Тип", "Файл");
        echo str_repeat("-", 80) . "\n";

        foreach ($backups as $backup) {
            printf("%-20s %-15s %-15s %-20s\n",
                $backup['date'],
                $backup['size'],
                $backup['type'],
                $backup['filename']
            );
        }

        echo "\nОбщий размер бэкапов: " . $this->getTotalBackupSize() . "\n";
        echo "Количество бэкапов: " . count($backups) . "\n\n";
    }

    /**
     * Создать полный бэкап (файлы + база данных)
     */
    public function createFull()
    {
        echo "Создание полного бэкапа...\n";

        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "full_backup_$timestamp";
        $backupPath = $this->backupDir . '/' . $backupName;

        // Создаем директорию для бэкапа
        $backupDir = new Directory($backupPath);
        $backupDir->create();

        // Создаем бэкап файлов
        echo "Создание архива файлов...\n";
        $filesArchive = $this->createFilesBackup($backupPath);

        // Создаем дамп базы данных
        echo "Создание дампа базы данных...\n";
        $dbDump = $this->createDatabaseBackup($backupPath);

        // Создаем итоговый архив
        echo "Создание итогового архива...\n";
        $finalArchive = $this->createFinalArchive($backupPath, $backupName);

        // Удаляем временные файлы
        $backupDir->delete();

        echo "Полный бэкап создан: " . basename($finalArchive) . "\n";
        echo "Размер: " . $this->formatBytes(filesize($finalArchive)) . "\n";

        $this->cleanupOldBackups();
    }

    /**
     * Создать бэкап только файлов
     */
    public function createFiles()
    {
        echo "Создание бэкапа файлов...\n";

        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "files_backup_$timestamp";
        $backupPath = $this->backupDir . '/' . $backupName;

        // Создаем директорию для бэкапа
        $backupDir = new Directory($backupPath);
        $backupDir->create();

        // Создаем бэкап файлов
        $filesArchive = $this->createFilesBackup($backupPath);

        // Переименовываем архив
        $finalPath = $this->backupDir . '/' . $backupName . '.tar.gz';
        rename($filesArchive, $finalPath);

        // Удаляем временную директорию
        $backupDir->delete();

        echo "Бэкап файлов создан: " . basename($finalPath) . "\n";
        echo "Размер: " . $this->formatBytes(filesize($finalPath)) . "\n";

        $this->cleanupOldBackups();
    }

    /**
     * Создать бэкап только базы данных
     */
    public function createDatabase()
    {
        echo "Создание бэкапа базы данных...\n";

        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "db_backup_$timestamp.sql";
        $backupPath = $this->backupDir . '/' . $backupName;

        $this->createDatabaseDump($backupPath);

        // Сжимаем дамп
        $compressedPath = $backupPath . '.gz';
        $this->compressFile($backupPath, $compressedPath);
        unlink($backupPath);

        echo "Бэкап базы данных создан: " . basename($compressedPath) . "\n";
        echo "Размер: " . $this->formatBytes(filesize($compressedPath)) . "\n";

        $this->cleanupOldBackups();
    }

    /**
     * Восстановить бэкап
     */
    public function restore($backupFile)
    {
        $backupPath = $this->backupDir . '/' . $backupFile;

        if (!file_exists($backupPath)) {
            echo "Ошибка: файл бэкапа не найден: $backupFile\n";
            return false;
        }

        echo "Восстановление бэкапа: $backupFile\n";

        // Определяем тип бэкапа
        $backupType = $this->detectBackupType($backupFile);

        switch ($backupType) {
            case 'full':
                $this->restoreFullBackup($backupPath);
                break;
            case 'files':
                $this->restoreFilesBackup($backupPath);
                break;
            case 'database':
                $this->restoreDatabaseBackup($backupPath);
                break;
            default:
                echo "Ошибка: не удалось определить тип бэкапа\n";
                return false;
        }

        echo "Восстановление завершено.\n";
        return true;
    }

    /**
     * Удалить старые бэкапы
     */
    public function cleanup($days = null)
    {
        $days = $days ?? 30;
        echo "Удаление бэкапов старше $days дней...\n";

        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $deleted = 0;

        $backups = $this->getBackupList();

        foreach ($backups as $backup) {
            if ($backup['timestamp'] < $cutoffTime) {
                $filePath = $this->backupDir . '/' . $backup['filename'];
                if (unlink($filePath)) {
                    echo "Удален: " . $backup['filename'] . "\n";
                    $deleted++;
                }
            }
        }

        echo "Удалено бэкапов: $deleted\n";
    }

    /**
     * Проверить целостность бэкапов
     */
    public function verify()
    {
        echo "Проверка целостности бэкапов...\n";

        $backups = $this->getBackupList();
        $errors = 0;

        foreach ($backups as $backup) {
            $filePath = $this->backupDir . '/' . $backup['filename'];

            echo "Проверка: " . $backup['filename'] . " ... ";

            if (!file_exists($filePath)) {
                echo "ОШИБКА: файл не найден\n";
                $errors++;
                continue;
            }

            if (!$this->verifyArchive($filePath)) {
                echo "ОШИБКА: архив поврежден\n";
                $errors++;
                continue;
            }

            echo "OK\n";
        }

        echo "\nПроверка завершена. Ошибок: $errors\n";
    }

    /**
     * Создать бэкап файлов
     */
    private function createFilesBackup($backupPath)
    {
        $archivePath = $backupPath . '/files.tar.gz';

        // Исключаемые директории
        $excludes = [
            'bitrix/backup',
            'bitrix/cache',
            'bitrix/managed_cache',
            'bitrix/tmp',
            'upload/tmp'
        ];

        $excludeParams = '';
        foreach ($excludes as $exclude) {
            $excludeParams .= " --exclude='$exclude'";
        }

        $cmd = "cd " . escapeshellarg($this->documentRoot) . " && tar -czf " .
            escapeshellarg($archivePath) . " $excludeParams .";

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Ошибка создания архива файлов");
        }

        return $archivePath;
    }

    /**
     * Создать дамп базы данных
     */
    private function createDatabaseBackup($backupPath)
    {
        $dumpPath = $backupPath . '/database.sql';
        $this->createDatabaseDump($dumpPath);
        return $dumpPath;
    }

    /**
     * Создать дамп базы данных
     */
    private function createDatabaseDump($dumpPath)
    {
        $host = $this->config['host'] ?? 'localhost';
        $database = $this->config['database'];
        $login = $this->config['login'];
        $password = $this->config['password'];
        $port = $this->config['port'] ?? 3306;

        $cmd = "mysqldump --host=" . escapeshellarg($host) .
            " --port=" . escapeshellarg($port) .
            " --user=" . escapeshellarg($login) .
            " --password=" . escapeshellarg($password) .
            " --single-transaction --routines --triggers " .
            escapeshellarg($database) . " > " . escapeshellarg($dumpPath);

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Ошибка создания дампа базы данных");
        }
    }

    /**
     * Создать итоговый архив
     */
    private function createFinalArchive($backupPath, $backupName)
    {
        $finalPath = $this->backupDir . '/' . $backupName . '.tar.gz';

        $cmd = "cd " . escapeshellarg($this->backupDir) . " && tar -czf " .
            escapeshellarg($finalPath) . " -C " . escapeshellarg($backupPath) . " .";

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Ошибка создания итогового архива");
        }

        return $finalPath;
    }

    /**
     * Получить список бэкапов
     */
    private function getBackupList()
    {
        $backups = [];

        if (!is_dir($this->backupDir)) {
            return $backups;
        }

        $files = scandir($this->backupDir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $this->backupDir . '/' . $file;
            if (!is_file($filePath)) {
                continue;
            }

            $backups[] = [
                'filename' => $file,
                'date' => date('Y-m-d H:i:s', filemtime($filePath)),
                'timestamp' => filemtime($filePath),
                'size' => $this->formatBytes(filesize($filePath)),
                'type' => $this->detectBackupType($file)
            ];
        }

        // Сортируем по дате создания (новые первыми)
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return $backups;
    }

    /**
     * Определить тип бэкапа
     */
    private function detectBackupType($filename)
    {
        if (strpos($filename, 'full_backup') !== false) {
            return 'full';
        } elseif (strpos($filename, 'files_backup') !== false) {
            return 'files';
        } elseif (strpos($filename, 'db_backup') !== false) {
            return 'database';
        }

        return 'unknown';
    }

    /**
     * Получить общий размер бэкапов
     */
    private function getTotalBackupSize()
    {
        $totalSize = 0;
        $backups = $this->getBackupList();

        foreach ($backups as $backup) {
            $filePath = $this->backupDir . '/' . $backup['filename'];
            $totalSize += filesize($filePath);
        }

        return $this->formatBytes($totalSize);
    }

    /**
     * Убрать старые бэкапы (по количеству)
     */
    private function cleanupOldBackups()
    {
        $backups = $this->getBackupList();

        if (count($backups) <= $this->maxBackups) {
            return;
        }

        // Удаляем самые старые бэкапы
        $toDelete = array_slice($backups, $this->maxBackups);

        foreach ($toDelete as $backup) {
            $filePath = $this->backupDir . '/' . $backup['filename'];
            if (unlink($filePath)) {
                echo "Удален старый бэкап: " . $backup['filename'] . "\n";
            }
        }
    }

    /**
     * Сжать файл
     */
    private function compressFile($source, $destination)
    {
        $cmd = "gzip -c " . escapeshellarg($source) . " > " . escapeshellarg($destination);
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Ошибка сжатия файла");
        }
    }

    /**
     * Проверить архив
     */
    private function verifyArchive($filePath)
    {
        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'gz') {
            $cmd = "gzip -t " . escapeshellarg($filePath);
        } else {
            $cmd = "tar -tf " . escapeshellarg($filePath) . " > /dev/null";
        }

        exec($cmd, $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Обеспечить существование директории бэкапов
     */
    private function ensureBackupDirectory()
    {
        if (!is_dir($this->backupDir)) {
            $dir = new Directory($this->backupDir);
            $dir->create();
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
     * Восстановить полный бэкап
     */
    private function restoreFullBackup($backupPath)
    {
        echo "Восстановление полного бэкапа...\n";

        // Создаем временную директорию
        $tempDir = sys_get_temp_dir() . '/bitrix_restore_' . time();
        mkdir($tempDir);

        // Извлекаем архив
        $cmd = "tar -xzf " . escapeshellarg($backupPath) . " -C " . escapeshellarg($tempDir);
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Ошибка извлечения архива");
        }

        // Восстанавливаем файлы
        if (file_exists($tempDir . '/files.tar.gz')) {
            echo "Восстановление файлов...\n";
            $cmd = "tar -xzf " . escapeshellarg($tempDir . '/files.tar.gz') . " -C " . escapeshellarg($this->documentRoot);
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception("Ошибка восстановления файлов");
            }
        }

        // Восстанавливаем базу данных
        if (file_exists($tempDir . '/database.sql')) {
            echo "Восстановление базы данных...\n";
            $this->restoreDatabaseFromFile($tempDir . '/database.sql');
        }

        // Удаляем временную директорию
        $this->deleteDirectory($tempDir);
    }

    /**
     * Восстановить файлы из бэкапа
     */
    private function restoreFilesBackup($backupPath)
    {
        echo "Восстановление файлов из бэкапа...\n";

        $cmd = "tar -xzf " . escapeshellarg($backupPath) . " -C " . escapeshellarg($this->documentRoot);
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Ошибка восстановления файлов");
        }
    }

    /**
     * Восстановить базу данных из бэкапа
     */
    private function restoreDatabaseBackup($backupPath)
    {
        echo "Восстановление базы данных из бэкапа...\n";

        // Если файл сжат, распаковываем его
        if (pathinfo($backupPath, PATHINFO_EXTENSION) === 'gz') {
            $tempFile = sys_get_temp_dir() . '/db_restore_' . time() . '.sql';
            $cmd = "gunzip -c " . escapeshellarg($backupPath) . " > " . escapeshellarg($tempFile);
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception("Ошибка распаковки дампа базы данных");
            }

            $this->restoreDatabaseFromFile($tempFile);
            unlink($tempFile);
        } else {
            $this->restoreDatabaseFromFile($backupPath);
        }
    }

    /**
     * Восстановить базу данных из файла
     */
    private function restoreDatabaseFromFile($dumpPath)
    {
        $host = $this->config['host'] ?? 'localhost';
        $database = $this->config['database'];
        $login = $this->config['login'];
        $password = $this->config['password'];
        $port = $this->config['port'] ?? 3306;

        $cmd = "mysql --host=" . escapeshellarg($host) .
            " --port=" . escapeshellarg($port) .
            " --user=" . escapeshellarg($login) .
            " --password=" . escapeshellarg($password) .
            " " . escapeshellarg($database) . " < " . escapeshellarg($dumpPath);

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Ошибка восстановления базы данных");
        }
    }

    /**
     * Удалить директорию рекурсивно
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}

// Обработка аргументов командной строки
function showHelp()
{
    echo "Управление бэкапами Bitrix\n";
    echo "Использование: php backup.php [команда] [опции]\n\n";
    echo "Команды:\n";
    echo "  info                    - показать информацию о бэкапах\n";
    echo "  create-full             - создать полный бэкап (файлы + БД)\n";
    echo "  create-files            - создать бэкап только файлов\n";
    echo "  create-db               - создать бэкап только базы данных\n";
    echo "  restore [файл]          - восстановить бэкап\n";
    echo "  cleanup [дни]           - удалить старые бэкапы (по умолчанию 30 дней)\n";
    echo "  verify                  - проверить целостность бэкапов\n";
    echo "  help                    - показать эту справку\n\n";
    echo "Примеры:\n";
    echo "  php backup.php info\n";
    echo "  php backup.php create-full\n";
    echo "  php backup.php create-files\n";
    echo "  php backup.php create-db\n";
    echo "  php backup.php restore full_backup_2024-01-15_10-30-00.tar.gz\n";
    echo "  php backup.php cleanup 7\n";
    echo "  php backup.php verify\n";
}

// Основная логика
try {
    $backupManager = new BackupManager();

    $action = $argv[1] ?? 'info';

    switch ($action) {
        case 'info':
            $backupManager->info();
            break;

        case 'create-full':
            $backupManager->createFull();
            break;

        case 'create-files':
            $backupManager->createFiles();
            break;

        case 'create-db':
            $backupManager->createDatabase();
            break;

        case 'restore':
            $backupFile = $argv[2] ?? '';
            if (empty($backupFile)) {
                echo "Ошибка: не указан файл бэкапа\n";
                showHelp();
                exit(1);
            }
            $backupManager->restore($backupFile);
            break;

        case 'cleanup':
            $days = isset($argv[2]) ? (int)$argv[2] : 30;
            $backupManager->cleanup($days);
            break;

        case 'verify':
            $backupManager->verify();
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

} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}