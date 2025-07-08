<?php

namespace DDAPP\Tools;

use Bitrix\Main\Entity\Event;

class Main
{
    const MODULE_ID = 'ddapp.tools';

    /**
     * Получает путь к модулю (local или bitrix)
     * @return string
     */
    public static function getModulePath(): string
    {
        static $path = null;

        if ($path === null) {
            if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . self::MODULE_ID . "/")) {
                $path = '/local/modules/' . self::MODULE_ID;
            } elseif (file_exists($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . self::MODULE_ID . "/")) {
                $path = '/bitrix/modules/' . self::MODULE_ID;
            } else {
                $path = '';
            }
        }

        return $path;
    }

    /**
     * Подключает JS файл модуля
     * @param string $jsFile относительный путь к JS файлу
     */
    public static function includeJS($jsFile): bool
    {
        global $APPLICATION;

        $modulePath = self::getModulePath();
        if (!$modulePath) {
            return false;
        }

        $fullPath = $modulePath . '/' . ltrim($jsFile, '/');
        if (file_exists($_SERVER["DOCUMENT_ROOT"] . $fullPath)) {
            $APPLICATION->AddHeadScript($fullPath);
            return true;
        }

        return false;
    }

    /**
     * Подключает CSS файл модуля
     * @param string $cssFile относительный путь к CSS файлу
     */
    public static function includeCSS($cssFile): bool
    {
        global $APPLICATION;

        $modulePath = self::getModulePath();
        if (!$modulePath) {
            return false;
        }

        $fullPath = $modulePath . '/' . ltrim($cssFile, '/');
        if (file_exists($_SERVER["DOCUMENT_ROOT"] . $fullPath)) {
            $APPLICATION->SetAdditionalCSS($fullPath);
            return true;
        }

        return false;
    }

    /**
     * Получает URL к AJAX файлу модуля
     * @param string $ajaxFile относительный путь к AJAX файлу
     * @return string
     */
    public static function getAjaxUrl($ajaxFile): string
    {
        $modulePath = self::getModulePath();
        if (!$modulePath) {
            return '';
        }

        return $modulePath . '/' . ltrim($ajaxFile, '/');
    }

    /**
     * Получает абсолютный путь к файлу модуля
     * @param string $file относительный путь к файлу
     * @return string
     */
    public static function getModuleFilePath($file): string
    {
        $modulePath = self::getModulePath();
        if (!$modulePath) {
            return '';
        }

        return $_SERVER["DOCUMENT_ROOT"] . $modulePath . '/' . ltrim($file, '/');
    }


    /**
     * Метод для WebHook
     * @return mixed
     */
    public static function webhook()
    {
        echo "webhook";

        return "webhook";
    }
}