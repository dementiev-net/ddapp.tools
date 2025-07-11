<?php

namespace DDAPP\Tools\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use DDAPP\Tools\Main;
use DDAPP\Tools\Helpers\FileHelper;

class LogHelper
{
    public const LEVEL_DEBUG = "DEBUG";
    public const LEVEL_INFO = "INFO";
    public const LEVEL_WARNING = "WARNING";
    public const LEVEL_ERROR = "ERROR";
    public const LEVEL_CRITICAL = "CRITICAL";
    private const CRITICAL_EMAIL_TEMPLATE_CODE = "DDAPP_ERROR_CRITICAL";
    private static array $config = [];
    private const ITEMS_PER_PAGE = 100;

    // Приоритеты уровней логирования
    private static array $levelPriorities = [
        self::LEVEL_DEBUG => 1,
        self::LEVEL_INFO => 2,
        self::LEVEL_WARNING => 3,
        self::LEVEL_ERROR => 4,
        self::LEVEL_CRITICAL => 5,
    ];

    /**
     * Настройка конфигурации логирования
     * @param array $config
     * @return void
     */
    public static function configure(array $config = []): void
    {
        $op = Option::getForModule(Main::MODULE_ID);

        self::$config = [
            "log_enabled" => $op["log_enabled"],
            "log_min_level" => $op["log_min_level"],
            "log_path" => $op["log_path"],
            "log_date_format" => $op["log_date_format"],
            "log_max_file_size" => $op["log_max_file_size"],
            "log_max_files" => $op["log_max_files"],
            "log_critical_email" => [
                "enabled" => $op["log_email_enabled"],
                "email" => $op["log_email"],
            ],
        ];

        if (!empty($config)) {
            self::$config = array_merge(self::$config, $config);
        }
    }

    /**
     * Записывает критическую ошибку в лог
     * @param string $type
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function critical(string $type, string $message, array $context = []): bool
    {
        $result = self::write($type, $message, self::LEVEL_CRITICAL, $context);

        // Отправка уведомления по email при критической ошибке
        if ($result && self::$config["log_critical_email"]["enabled"]) {
            self::sendCriticalEmail($type, $message, $context);
        }

        return $result;
    }

    /**
     * Записывает ошибку в лог
     * @param string $type
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function error(string $type, string $message, array $context = []): bool
    {
        return self::write($type, $message, self::LEVEL_ERROR, $context);
    }

    /**
     * Записывает предупреждение в лог
     * @param string $type
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function warning(string $type, string $message, array $context = []): bool
    {
        return self::write($type, $message, self::LEVEL_WARNING, $context);
    }

    /**
     * Записывает информационное сообщение в лог
     * @param string $type
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function info(string $type, string $message, array $context = []): bool
    {
        return self::write($type, $message, self::LEVEL_INFO, $context);
    }

    /**
     * Записывает отладочную информацию в лог
     * @param string $type
     * @param mixed $data
     * @param array $context
     * @return bool
     */
    public static function debug(string $type, $data, array $context = []): bool
    {
        $message = is_string($data) ? $data : Json::encode($data);
        return self::write($type, $message, self::LEVEL_DEBUG, $context);
    }

    /**
     * Записывает исключение в лог
     * @param string $type
     * @param \Throwable $exception
     * @param array $context
     * @return bool
     */
    public static function exception(string $type, \Throwable $exception, array $context = []): bool
    {
        $message = sprintf(
            "Exception: %s in %s:%d\nStack trace:\n%s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        $context["exception_class"] = get_class($exception);
        $context["exception_code"] = $exception->getCode();

        return self::write($type, $message, self::LEVEL_ERROR, $context);
    }

    /**
     * Измеряет время выполнения кода
     * @param string $type
     * @param string $operation
     * @param callable $callback
     * @return mixed
     */
    public static function timeExecution(string $type, string $operation, callable $callback)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        try {
            $result = $callback();
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $memoryUsed = memory_get_usage() - $startMemory;

            self::info($type, "Operation \"{$operation}\" completed", [
                "execution_time_ms" => $executionTime,
                "memory_used" => FileHelper::formatBytes($memoryUsed)
            ]);

            return $result;

        } catch (\Throwable $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            self::exception($type, $e, [
                "operation" => $operation,
                "execution_time_ms" => $executionTime
            ]);
            throw $e;
        }
    }

    /**
     * Очищает старые лог-файлы
     * @param string $type
     * @param int $daysToKeep
     * @return bool
     */
    public static function cleanOldLogs(string $type, int $daysToKeep = 30): bool
    {
        try {
            $logFile = self::getLogFile($type);
            $logDir = dirname($logFile);
            $pattern = basename($logFile) . "*";

            if (!Directory::isDirectoryExists($logDir)) {
                return true;
            }

            $directory = new Directory($logDir);
            $files = $directory->getChildren();
            $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);

            foreach ($files as $file) {
                if ($file->isFile() && fnmatch($pattern, $file->getName())) {
                    if ($file->getModificationTime() < $cutoffTime) {
                        $file->delete();
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            error_log("LogHelper cleanOldLogs error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Берет лог-файлы
     * @return array
     */
    public static function getLogFiles(): array
    {
        $logPath = Option::get(Main::MODULE_ID, "log_path");
        $fullLogPath = $_SERVER["DOCUMENT_ROOT"] . $logPath;

        $files = [];
        if (is_dir($fullLogPath)) {
            $handle = opendir($fullLogPath);
            while (false !== ($entry = readdir($handle))) {
                if (preg_match("/\.log$/", $entry)) {
                    $files[] = $entry;
                }
            }
            closedir($handle);
            sort($files);
        }
        return $files;
    }

    /**
     * Парсинг лог-файла
     * @param $filename
     * @param $filters
     * @return array
     */
    public static function parseLogFile($filename, $filters = []): array
    {
        $logPath = Option::get(Main::MODULE_ID, "log_path");
        $fullLogPath = $_SERVER["DOCUMENT_ROOT"] . $logPath;

        $filepath = $fullLogPath . "/" . $filename;
        if (!file_exists($filepath)) {
            return [];
        }

        $content = file_get_contents($filepath);
        $entries = [];

        // Разбиваем по записям
        preg_match_all("/\[(\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}:\d{2})\] \[(\w+)\] \[User: ([^|]+) \| URL: ([^|]+) \| Memory: ([^|]+) \| Peak: ([^\]]+)\] (.+?)(?=\n\[|\n$|$)/s", $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $entry = [
                "datetime" => $match[1],
                "level" => $match[2],
                "user" => trim($match[3]),
                "url" => trim($match[4]),
                "memory" => trim($match[5]),
                "peak" => trim($match[6]),
                "message" => trim($match[7])
            ];

            // Применяем фильтры
            if (self::applyFilters($entry, $filters)) {
                $entries[] = $entry;
            }
        }

        // Сортируем по дате (новые сначала)
        usort($entries, function ($a, $b) {
            return strtotime($b["datetime"]) - strtotime($a["datetime"]);
        });

        return $entries;
    }

    /**
     * Получает пользователя из лог-файла
     * @param $entries
     * @return array
     */
    public static function getUsers($entries): array
    {
        $users = [];
        foreach ($entries as $entry) {
            $user = $entry["user"];
            if (!in_array($user, $users)) {
                $users[] = $user;
            }
        }
        sort($users);
        return $users;
    }

    /**
     * Получает уровень из лог-файла
     * @param $entries
     * @return array
     */
    public static function getStats($entries): array
    {
        $stats = [
            "total" => count($entries),
            "debug" => 0,
            "info" => 0,
            "warning" => 0,
            "error" => 0,
            "critical" => 0,
        ];

        foreach ($entries as $entry) {
            switch ($entry["level"]) {
                case self::LEVEL_DEBUG:
                    $stats["debug"]++;
                    break;
                case self::LEVEL_INFO:
                    $stats["info"]++;
                    break;
                case self::LEVEL_WARNING:
                    $stats["warning"]++;
                    break;
                case self::LEVEL_ERROR:
                    $stats["error"]++;
                    break;
                case self::LEVEL_CRITICAL:
                    $stats["critical"]++;
                    break;
            }
        }

        return $stats;
    }

    /**
     * Постраничная обработка лог-файла
     * @param $entries
     * @param $page
     * @return array
     */
    public static function paginate($entries, $page = 1): array
    {
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;
        $pagedEntries = array_slice($entries, $offset, self::ITEMS_PER_PAGE);

        return [
            "entries" => $pagedEntries,
            "total" => count($entries),
            "pages" => ceil(count($entries) / self::ITEMS_PER_PAGE),
            "current_page" => $page
        ];
    }

    /**
     * Применяет фильтры к лог-файлу
     * @param $entry
     * @param $filters
     * @return bool
     */
    private static function applyFilters($entry, $filters): bool
    {
        // Фильтр по уровню
        if (!empty($filters["level"]) && trim($entry["level"]) !== trim($filters["level"])) {
            return false;
        }

        // Фильтр по пользователю
        if (!empty($filters["user"]) && trim($entry["user"]) !== trim($filters["user"])) {
            return false;
        }

        // Фильтр по дате
        if (!empty($filters["date"])) {
            $entryDate = date("Y-m-d", strtotime($entry["datetime"]));
            if ($entryDate !== $filters["date"]) {
                return false;
            }
        }

        // Поиск по тексту
        if (!empty($filters["search"])) {
            $searchText = mb_strtolower(trim($filters["search"]));
            $messageText = mb_strtolower($entry["message"]);
            if (strpos($messageText, $searchText) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получает путь к файлу лога
     * @param string $type
     * @return string
     */
    private static function getLogFile(string $type): string
    {
        $logDir = Application::getDocumentRoot() . self::$config["log_path"];

        return $logDir . "ddapp.tools.{$type}.log";
    }

    /**
     * Записывает сообщение в лог
     * @param string $type
     * @param string $message
     * @param string $level
     * @param array $context Дополнительный контекст
     * @return bool
     */
    private static function write(string $type, string $message, string $level = self::LEVEL_INFO, array $context = []): bool
    {
        if (!self::$config["log_enabled"] || !self::shouldLogLevel($level)) {
            return false;
        }

        try {
            $file = self::getLogFile($type);

            // Проверяем и создаем директорию
            $logDir = dirname($file);
            if (!Directory::isDirectoryExists($logDir)) {
                Directory::createDirectory($logDir);
            }

            // Ротация логов
            self::rotateLogsIfNeeded($file);

            $contextInfo = self::getContextInfo();
            $contextString = !empty($context) ? " | Context: " . Json::encode($context) : "";

            $logMessage = sprintf(
                "[%s] [%s] [%s] %s%s\n",
                date(self::$config["log_date_format"]),
                $level,
                $contextInfo,
                $message,
                $contextString
            );

            $fileObj = new File($file);
            return $fileObj->putContents($logMessage, File::APPEND);

        } catch (\Exception $e) {
            // Fallback логирование в случае ошибки
            error_log("LogHelper error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Проверяет, нужно ли логировать сообщение данного уровня
     * @param string $level
     * @return bool
     */
    private static function shouldLogLevel(string $level): bool
    {
        $currentPriority = self::$levelPriorities[$level] ?? 0;
        $minPriority = self::$levelPriorities[self::$config["log_min_level"]] ?? 0;

        return $currentPriority >= $minPriority;
    }

    /**
     * Ротация логов при превышении размера
     * @param string $logFile
     * @return void
     */
    private static function rotateLogsIfNeeded(string $logFile): void
    {
        if (!File::isFileExists($logFile)) {
            return;
        }

        $fileObj = new File($logFile);
        if ($fileObj->getSize() < self::$config["log_max_file_size"]) {
            return;
        }

        // Сдвигаем старые файлы
        for ($i = self::$config["log_max_files"] - 1; $i > 0; $i--) {
            $oldFile = $logFile . "." . $i;
            $newFile = $logFile . "." . ($i + 1);

            if (File::isFileExists($oldFile)) {
                if ($i === self::$config["log_max_files"] - 1) {
                    File::deleteFile($oldFile);
                } else {
                    File::deleteFile($newFile);
                    (new File($oldFile))->rename($newFile);
                }
            }
        }

        // Переименовываем текущий файл
        $fileObj->rename($logFile . ".1");
    }

    /**
     * Получает информацию о контексте (пользователь + URL + память)
     * @return string
     */
    private static function getContextInfo(): string
    {
        global $USER;

        // Информация о пользователе
        $userInfo = "Guest";
        if (isset($USER) && is_object($USER) && method_exists($USER, "IsAuthorized") && $USER->IsAuthorized()) {
            $login = $USER->GetLogin() ?? "unknown";
            $userId = $USER->GetID() ?? "unknown";
            $userInfo = "{$login} (ID: {$userId})";
        }

        // URL страницы
        $url = "CLI";
        if (!empty($_SERVER["REQUEST_URI"])) {
            $url = $_SERVER["REQUEST_URI"];
        }

        // Информация о памяти
        $memory = FileHelper::formatBytes(memory_get_usage());
        $peakMemory = FileHelper::formatBytes(memory_get_peak_usage());

        return "User: {$userInfo} | URL: {$url} | Memory: {$memory} | Peak: {$peakMemory}";
    }

    /**
     * Отправляет email уведомление о критической ошибке
     * @param string $type
     * @param string $message
     * @param array $context
     * @return bool
     */
    private static function sendCriticalEmail(string $type, string $message, array $context = []): bool
    {
        try {
            $templateCode = self::CRITICAL_EMAIL_TEMPLATE_CODE;
            $email = self::$config["log_critical_email"]["email"];

            if (empty($templateCode) || empty($email)) {
                return false;
            }

            global $USER;

            // Подготавливаем данные для шаблона
            $fields = [
                "EMAIL" => $email,
                "LOG_TYPE" => $type,
                "MESSAGE" => $message,
                "CONTEXT" => !empty($context) ? Json::encode($context, JSON_PRETTY_PRINT) : "Нет дополнительного контекста",
                "DATE_TIME" => date(self::$config["log_date_format"]),
                "SERVER_NAME" => $_SERVER["SERVER_NAME"] ?? "Unknown",
                "REQUEST_URI" => $_SERVER["REQUEST_URI"] ?? "CLI",
                "USER_AGENT" => $_SERVER["HTTP_USER_AGENT"] ?? "Unknown",
                "USER_ID" => (isset($USER) && is_object($USER) && $USER->IsAuthorized()) ? $USER->GetID() : "Гость",
                "USER_LOGIN" => (isset($USER) && is_object($USER) && $USER->IsAuthorized()) ? $USER->GetLogin() : "Не авторизован",
                "MEMORY_USAGE" => FileHelper::formatBytes(memory_get_usage()),
                "PEAK_MEMORY" => FileHelper::formatBytes(memory_get_peak_usage()),
            ];

            // Отправка события
            $result = Event::send([
                "EVENT_NAME" => $templateCode,
                "LID" => ["s1"],
                "C_FIELDS" => $fields,
            ]);

            return $result->isSuccess();

        } catch (\Exception $e) {
            error_log("LogHelper sendCriticalEmail error: " . $e->getMessage());
            return false;
        }
    }
}