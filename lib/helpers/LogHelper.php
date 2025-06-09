<?php

namespace DD\Tools\Helpers;

use Bitrix\Main\Web\Json;

class LogHelper
{
    /**
     * Получает путь к файлу лога
     * @param string $type
     * @return string
     */
    private static function getLogFile(string $type): string
    {
        return "/local/logs/" . DD_MODULE_NAMESPACE . ".{$type}.log";
    }

    /**
     * Записывает сообщение в лог
     * @param string $type
     * @param string $message
     * @param string $level
     * @return void
     */
    public static function write(string $type, string $message, string $level = "INFO"): void
    {
        $file = self::getLogFile($type);
        $contextInfo = self::getContextInfo();

        $logMessage = sprintf(
            "[%s] [%s] [%s] %s\n",
            date("Y-m-d H:i:s"),
            $level,
            $contextInfo,
            $message
        );

        $logDir = dirname($_SERVER["DOCUMENT_ROOT"] . $file);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($_SERVER["DOCUMENT_ROOT"] . $file, $logMessage, FILE_APPEND);
    }

    /**
     * Получает информацию о контексте (пользователь + URL)
     * @return string
     */
    private static function getContextInfo(): string
    {
        global $USER;

        // Информация о пользователе
        $userInfo = "Guest";
        if (isset($USER) && is_object($USER) && $USER->IsAuthorized()) {
            $login = $USER->GetLogin();
            $userId = $USER->GetID();
            $userInfo = "{$login} (ID: {$userId})";
        }

        // URL страницы
        $url = "CLI";
        if (isset($_SERVER['REQUEST_URI'])) {
            $url = $_SERVER['REQUEST_URI'];
        }

        return "User: {$userInfo} | URL: {$url}";
    }

    /**
     * Записывает ошибку в лог
     * @param string $type
     * @param string $message
     * @return void
     */
    public static function error(string $type, string $message): void
    {
        self::write($type, $message, "ERROR");
    }

    /**
     * Записывает предупреждение в лог
     * @param string $type
     * @param string $message
     * @return void
     */
    public static function warning(string $type, string $message): void
    {
        self::write($type, $message, "WARNING");
    }

    /**
     * Записывает отладочную информацию
     * @param string $type
     * @param $data
     * @return void
     */
    public static function debug(string $type, $data): void
    {
        $message = is_string($data) ? $data : Json::encode($data);
        self::write($type, $message, "DEBUG");
    }
}