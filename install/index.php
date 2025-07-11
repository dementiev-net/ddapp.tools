<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\ModuleManager;
use DDAPP\Tools\Install\IblockInstaller;
use DDAPP\Tools\Install\DataInstaller;
use DDAPP\Tools\Install\EmailTemplateInstaller;

Loc::loadMessages(__FILE__);

class DDAPP_Tools extends CModule
{
    // переменные модуля
    public $MODULE_ID;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;
    public $SHOW_SUPER_ADMIN_GROUP_RIGHTS;
    public $MODULE_GROUP_RIGHTS;
    private const CACHE_AGENT_INTERVAL = "0";
    private const FREE_SPACE_AGENT_INTERVAL = "3600";
    private const CONNECTION_NAME = "default";
    private const LOG_FILE = "/upload/ddapp.tools.install.log";

    function __construct()
    {
        $arModuleVersion = [];
        include_once(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_ID = "ddapp.tools";
        $this->MODULE_NAME = "2Dapp Tools - Утилиты разработчика";
        $this->MODULE_DESCRIPTION = "Модуль с полезными инструментами для разработки и администрирования";
        $this->PARTNER_NAME = "2Dapp";
        $this->PARTNER_URI = "https://2dapp.ru";
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = "Y"; // На странице прав доступа будут показаны администраторы и группы
        $this->MODULE_GROUP_RIGHTS = "Y"; // На странице редактирования групп будет отображаться этот модуль
    }

    /**
     * Метод отрабатывает при установке модуля
     * @return true
     */
    function DoInstall(): bool
    {
        // С установкой в один шаг
        ////////////////////////////////////////////////
        // global $APPLICATION;
        // ModuleManager::RegisterModule("ddapp.tools");
        // $this->InstallDB();
        // $this->addData();
        // $this->InstallEvents();
        // $this->InstallFiles();
        // $this->installAgents();
        //
        // $APPLICATION->includeAdminFile(
        //     Loc::getMessage("DDAPP_TOOLS_INSTALL_TITLE"),
        //     __DIR__ . "/instalInfo.php"
        // );

        // С установкой в два шага
        ////////////////////////////////////////////////
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        global $APPLICATION;

        if ($request["step"] < 2) {
            $APPLICATION->IncludeAdminFile(Loc::getMessage("DDAPP_TOOLS_INSTALL_TITLE_STEP_1"), __DIR__ . "/instalInfo-step1.php");
        }

        if ($request["step"] == 2) {
            ModuleManager::RegisterModule("ddapp.tools");
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
            $this->installAgents();

            $this->manageIBlock(true);
            $this->manageEmailTemplate(true);

            if ($request["addapp_data"] == "Y") {
                $this->addData();
            }

            $APPLICATION->IncludeAdminFile(Loc::getMessage("DDAPP_TOOLS_INSTALL_TITLE_STEP_2"), __DIR__ . "/instalInfo-step2.php");
        }

        return true;
    }

    /**
     * Метод отрабатывает при удалении модуля
     * @return true
     */
    function DoUninstall(): bool
    {
        // С удалением в один шаг
        ////////////////////////////////////////////////
        // global $APPLICATION;
        // $this->UnInstallDB();
        // $this->UnInstallEvents();
        // $this->UnInstallFiles();
        // $this->unInstallAgents();
        // ModuleManager::UnRegisterModule("ddapp.tools");
        //
        // $APPLICATION->includeAdminFile(
        //     Loc::getMessage("DDAPP_TOOLS_DEINSTALL_TITLE"),
        //     __DIR__ . "/deInstalInfo.php"
        // );

        // С удалением в два шага
        ////////////////////////////////////////////////
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        global $APPLICATION;

        if ($request["step"] < 2) {
            $APPLICATION->IncludeAdminFile(Loc::getMessage("DDAPP_TOOLS_DEINSTALL_TITLE_STEP_1"), __DIR__ . "/deInstalInfo-step1.php");
        }
        // проверяем какой сейчас шаг, усли 2, производим удаление
        if ($request["step"] == 2) {
            if ($request["save_data"] == "Y") {
                $this->UnInstallDB();
            }
            $this->UnInstallEvents();
            $this->UnInstallFiles();
            $this->unInstallAgents();

            $this->manageIBlock(false);
            $this->manageEmailTemplate(false);

            ModuleManager::UnRegisterModule("ddapp.tools");

            $APPLICATION->IncludeAdminFile(Loc::getMessage("DDAPP_TOOLS_DEINSTALL_TITLE_STEP_2"), __DIR__ . "/deInstalInfo-step2.php");
        }

        return true;
    }

    /**
     * Метод для создания таблицы баз данных
     * @return void
     */
    function InstallDB(): void
    {
        Loader::includeModule($this->MODULE_ID);
        Loader::includeModule("iblock");

        $connection = Application::getConnection(self::CONNECTION_NAME);

        $maintenanceTableEntity = Base::getInstance("\DDAPP\Tools\Entity\MaintenanceTable");

        if (!$connection->isTableExists($maintenanceTableEntity->getDBTableName())) {
            try {
                $maintenanceTableEntity->createDbTable();
            } catch (\Exception $e) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERROR" => $e->getMessage(),
                    "TABLE" => $maintenanceTableEntity->getDBTableName()
                ], "MaintenanceTable::createDbTable", self::LOG_FILE);
            }
        }

        $dataExportTableEntity = Base::getInstance("\DDAPP\Tools\Entity\DataExportTable");

        if (!$connection->isTableExists($dataExportTableEntity->getDBTableName())) {
            try {
                $dataExportTableEntity->createDbTable();
            } catch (\Exception $e) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERROR" => $e->getMessage(),
                    "TABLE" => $dataExportTableEntity->getDBTableName()
                ], "DataExportTable::createDbTable", self::LOG_FILE);
            }
        }

        $dataImportTableEntity = Base::getInstance("\DDAPP\Tools\Entity\DataImportTable");

        if (!$connection->isTableExists($dataImportTableEntity->getDBTableName())) {
            try {
                $dataImportTableEntity->createDbTable();
            } catch (\Exception $e) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERROR" => $e->getMessage(),
                    "TABLE" => $dataImportTableEntity->getDBTableName()
                ], "DataImportTable::createDbTable", self::LOG_FILE);
            }
        }

        $dataImagesTableEntity = Base::getInstance("\DDAPP\Tools\Entity\DataImagesTable");

        if (!$connection->isTableExists($dataImagesTableEntity->getDBTableName())) {
            try {
                $dataImagesTableEntity->createDbTable();
            } catch (\Exception $e) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERROR" => $e->getMessage(),
                    "TABLE" => $dataImagesTableEntity->getDBTableName()
                ], "DataImagesTable::createDbTable", self::LOG_FILE);
            }
        }
    }

    /**
     * Метод для удаления таблицы баз данных
     * @return void
     */
    function UnInstallDB(): void
    {
        Loader::includeModule($this->MODULE_ID);
        Loader::includeModule("iblock");

        Application::getConnection(self::CONNECTION_NAME)->queryExecute("DROP TABLE IF EXISTS ddapp_migrations;");
        Application::getConnection(self::CONNECTION_NAME)->queryExecute("DROP TABLE IF EXISTS " . Base::getInstance("\DDAPP\Tools\Entity\MaintenanceTable")->getDBTableName());
        Application::getConnection(self::CONNECTION_NAME)->queryExecute("DROP TABLE IF EXISTS " . Base::getInstance("\DDAPP\Tools\Entity\DataExportTable")->getDBTableName());
        Application::getConnection(self::CONNECTION_NAME)->queryExecute("DROP TABLE IF EXISTS " . Base::getInstance("\DDAPP\Tools\Entity\DataImportTable")->getDBTableName());
        Application::getConnection(self::CONNECTION_NAME)->queryExecute("DROP TABLE IF EXISTS " . Base::getInstance("\DDAPP\Tools\Entity\DataImagesTable")->getDBTableName());

        Option::delete($this->MODULE_ID);
    }

    /**
     * Метод для создания обработчика событий
     * @return true
     */
    function InstallEvents(): bool
    {
        $eventManager = EventManager::getInstance();

        // страницы
        $eventManager->registerEventHandler("main", "OnPageStart", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnPageStartHandler");
        $eventManager->registerEventHandler("main", "OnBeforeProlog", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnBeforePrologHandler");
        $eventManager->registerEventHandler("main", "OnProlog", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnPrologHandler");
        $eventManager->registerEventHandler("main", "OnEpilog", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnEpilogHandler");
        // пользователь
        $eventManager->registerEventHandler("main", "OnAfterUserLogin", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnAfterUserLoginHandler");
        $eventManager->registerEventHandler("main", "OnBeforeUserLogin", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnBeforeUserLoginHandler");
        // формы и запросы
        $eventManager->registerEventHandler("main", "OnEndBufferContent", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnEndBufferContentHandler");
        // админка
        $eventManager->registerEventHandler("main", "OnAdminContextMenuShow", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnAdminContextMenuShowHandler");
        $eventManager->registerEventHandler("main", "OnAdminListDisplay", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnAdminListDisplayHandler");
        // модули
        $eventManager->registerEventHandler($this->MODULE_ID, "OnWebHook", $this->MODULE_ID, "\DDAPP\Tools\Main", "webhook");
        $eventManager->registerEventHandler($this->MODULE_ID, "\DDAPP\Tools\Entity::OnBeforeAdd", $this->MODULE_ID, "\DDAPP\Tools\Events", "eventHandler");
        $eventManager->registerEventHandler($this->MODULE_ID, "\DDAPP\Tools\Entity::OnBeforeUpdate", $this->MODULE_ID, "\DDAPP\Tools\Events", "eventHandler");

        return true;
    }

    /**
     * Метод для удаления обработчика событий
     * @return true
     */
    function UnInstallEvents(): bool
    {
        $eventManager = EventManager::getInstance();

        // страницы
        $eventManager->unRegisterEventHandler("main", "OnPageStart", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnPageStartHandler");
        $eventManager->unRegisterEventHandler("main", "OnBeforeProlog", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnBeforePrologHandler");
        $eventManager->unRegisterEventHandler("main", "OnProlog", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnPrologHandler");
        $eventManager->unRegisterEventHandler("main", "OnEpilog", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnEpilogHandler");
        // пользователь
        $eventManager->unRegisterEventHandler("main", "OnAfterUserLogin", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnAfterUserLoginHandler");
        $eventManager->unRegisterEventHandler("main", "OnBeforeUserLogin", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnBeforeUserLoginHandler");
        // формы и запросы
        $eventManager->unRegisterEventHandler("main", "OnEndBufferContent", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnEndBufferContentHandler");
        // админка
        $eventManager->unRegisterEventHandler("main", "OnAdminContextMenuShow", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnAdminContextMenuShowHandler");
        $eventManager->unRegisterEventHandler("main", "OnAdminListDisplay", $this->MODULE_ID, "\DDAPP\Tools\Events", "OnAdminListDisplayHandler");
        // модули
        $eventManager->unRegisterEventHandler($this->MODULE_ID, "OnWebHook", $this->MODULE_ID, "\DDAPP\Tools\Main", "webhook");
        $eventManager->unRegisterEventHandler($this->MODULE_ID, "\DDAPP\Tools\Entity::OnBeforeAdd", $this->MODULE_ID, "\DDAPP\Tools\Events", "eventHandler");
        $eventManager->unRegisterEventHandler($this->MODULE_ID, "\DDAPP\Tools\Entity::OnBeforeUpdate", $this->MODULE_ID, "\DDAPP\Tools\Events", "eventHandler");

        return true;
    }

    /**
     * Метод для копирования файлов модуля при установке
     * @return true
     */
    function InstallFiles(): bool
    {
        $docRoot = Application::getDocumentRoot();

        // Определяем, где находится модуль — local или bitrix
        $isLocalModule = strpos(__DIR__, "/local/") !== false;

        // Путь для компонентов с учётом локального расположения модуля
        $componentsPath = $isLocalModule ? $docRoot . "/local/components" : $docRoot . "/bitrix/components";

        // Массив директорий для копирования: [исходник, назначение]
        $copyDirs = [
            [__DIR__ . "/assets/images", $docRoot . "/bitrix/images/" . $this->MODULE_ID . "/"],
            [__DIR__ . "/assets/js", $docRoot . "/bitrix/js/" . $this->MODULE_ID . "/"],
            [__DIR__ . "/assets/css", $docRoot . "/bitrix/css/" . $this->MODULE_ID . "/"],
            [__DIR__ . "/admin", $docRoot . "/bitrix/admin"],
            [__DIR__ . "/components", $componentsPath],
            [__DIR__ . "/files", $docRoot . "/"],
            [__DIR__ . "/gadgets", $docRoot . "/bitrix/gadgets/" . $this->MODULE_ID . "/"],
        ];

        foreach ($copyDirs as [$src, $dst]) {
            if (is_dir($src)) {
                if (!CopyDirFiles($src, $dst, true, true)) {
                    Debug::writeToFile([
                        "DATE" => date("Y-m-d H:i:s"),
                        "ERROR" => "Ошибка копирования из $src в $dst"
                    ], "CopyDirFiles", self::LOG_FILE);
                }
            }
        }

        return true;
    }

    /**
     * Метод для удаления файлов модуля при удалении
     * @return true
     */
    function UnInstallFiles(): bool
    {
        $docRoot = Application::getDocumentRoot();

        // Проверяем, где установлен модуль: /local или /bitrix
        $isLocalModule = strpos(__DIR__, "/local/") !== false;

        // Путь к компонентам
        $componentsPath = $isLocalModule ? "/local/components/" : "/bitrix/components/";

        // Удаляем директории с ассетами
        $dirsToDelete = [
            $docRoot . "/bitrix/images/" . $this->MODULE_ID,
            $docRoot . "/bitrix/js/" . $this->MODULE_ID,
            $docRoot . "/bitrix/css/" . $this->MODULE_ID,
            $docRoot . "/bitrix/gadgets/" . $this->MODULE_ID
        ];

        foreach ($dirsToDelete as $dir) {
            if (Directory::isDirectoryExists($dir)) {
                Directory::deleteDirectory($dir);
            }
        }

        // Удаляем компоненты модуля
        $componentDir = $componentsPath . $this->MODULE_ID;
        if (is_dir($docRoot . $componentDir)) {
            DeleteDirFilesEx($componentDir);
        }

        // Удаляем admin-файлы
        DeleteDirFiles(__DIR__ . "/admin", $docRoot . "/bitrix/admin");

        // Удаляем копированные дополнительные файлы
        DeleteDirFiles(__DIR__ . "/files", $docRoot . "/");

        return true;
    }

    /**
     * Установка агентов
     * @return void
     */
    function installAgents(): void
    {
        $agent = \CAgent::AddAgent("\\DDAPP\\Tools\\cacheAgent::run();", $this->MODULE_ID, "N", self::CACHE_AGENT_INTERVAL, "", "N", "", 100);
        if (!$agent) {
            Debug::writeToFile([
                "DATE" => date("Y-m-d H:i:s"),
                "ERROR" => "Не удалось добавить агента cacheAgent"
            ], "CAgent::AddAgent", self::LOG_FILE);
        }

        $agent = \CAgent::AddAgent("\\DDAPP\\Tools\\freespaceAgent::run();", $this->MODULE_ID, "N", self::FREE_SPACE_AGENT_INTERVAL, "", "Y", "", 100);
        if (!$agent) {
            Debug::writeToFile([
                "DATE" => date("Y-m-d H:i:s"),
                "ERROR" => "Не удалось добавить агента freespaceAgent"
            ], "CAgent::AddAgent", self::LOG_FILE);
        }
    }

    /**
     * Удаление агентов
     * @return void
     */
    function unInstallAgents(): void
    {
        \CAgent::RemoveModuleAgents($this->MODULE_ID);
    }

    /**
     * Установка/Удаление Инфоблоков
     * @param $install
     * @return bool
     */
    function manageIBlock($install = true): bool
    {
        require_once __DIR__ . "/lib/IblockInstaller.php";
        $installer = new IblockInstaller($this->MODULE_ID);

        return $install ? $installer->install() : $installer->uninstall();
    }

    /**
     * Установка/Удаление Почтовых шаблонов
     * @param $install
     * @return bool
     */
    function manageEmailTemplate($install = true): bool
    {
        require_once __DIR__ . "/lib/EmailTemplateInstaller.php";
        $installer = new EmailTemplateInstaller($this->MODULE_ID);

        return $install ? $installer->install() : $installer->uninstall();
    }

    /**
     * Заполнение таблиц и инфоблоков тестовыми данными
     * @return true
     */
    function addData(): bool
    {
        require_once __DIR__ . "/lib/DataInstaller.php";
        $installer = new DataInstaller($this->MODULE_ID);

        return $installer->install();
    }
}