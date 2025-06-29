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
use DD\Tools\Entity\DataTable;
use DD\Tools\Install\IblockInstaller;
use DD\Tools\Install\DataInstaller;
use DD\Tools\Install\EmailTemplateInstaller;

Loc::loadMessages(__FILE__);

class DD_Tools extends CModule
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
    private const SUPER_AGENT_INTERVAL = "120";
    private const FREE_SPACE_AGENT_INTERVAL = "3600";

    function __construct()
    {
        $arModuleVersion = [];
        include_once(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_ID = "dd.tools";
        $this->MODULE_NAME = "DD Tools - Утилиты разработчика";
        $this->MODULE_DESCRIPTION = "Модуль с полезными инструментами для разработки и администрирования";
        $this->PARTNER_NAME = "Дмитрий Дементьев";
        $this->PARTNER_URI = "https://dementiev.net";
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = "Y"; // На странице прав доступа будут показаны администраторы и группы
        $this->MODULE_GROUP_RIGHTS = "Y"; // На странице редактирования групп будет отображаться этот модуль
    }

    /**
     * Метод отрабатывает при установке модуля
     * @return true
     */
    function DoInstall()
    {
        // С установкой в один шаг
        ////////////////////////////////////////////////
        // global $APPLICATION;
        // ModuleManager::RegisterModule("dd.tools");
        // $this->InstallDB();
        // $this->addData();
        // $this->InstallEvents();
        // $this->InstallFiles();
        // $this->installAgents();
        //
        // $APPLICATION->includeAdminFile(
        //     Loc::getMessage("DD_TOOLS_INSTALL_TITLE"),
        //     __DIR__ . "/instalInfo.php"
        // );

        // С установкой в два шага
        ////////////////////////////////////////////////
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        global $APPLICATION;

        if ($request["step"] < 2) {
            $APPLICATION->IncludeAdminFile(Loc::getMessage("DD_TOOLS_INSTALL_TITLE_STEP_1"), __DIR__ . "/instalInfo-step1.php");
        }

        if ($request["step"] == 2) {
            ModuleManager::RegisterModule("dd.tools");
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
            $this->installAgents();

            $this->manageInfoblock(true);
            $this->manageEmailTemplate(true);

            if ($request["add_data"] == "Y") {
                $this->addData();
            }

            $APPLICATION->IncludeAdminFile(Loc::getMessage("DD_TOOLS_INSTALL_TITLE_STEP_2"), __DIR__ . "/instalInfo-step2.php");
        }

        return true;
    }

    /**
     * Метод отрабатывает при удалении модуля
     * @return true
     */
    function DoUninstall()
    {
        // С удалением в один шаг
        ////////////////////////////////////////////////
        // global $APPLICATION;
        // $this->UnInstallDB();
        // $this->UnInstallEvents();
        // $this->UnInstallFiles();
        // $this->unInstallAgents();
        // ModuleManager::UnRegisterModule("dd.tools");
        //
        // $APPLICATION->includeAdminFile(
        //     Loc::getMessage("DD_TOOLS_DEINSTALL_TITLE"),
        //     __DIR__ . "/deInstalInfo.php"
        // );

        // С удалением в два шага
        ////////////////////////////////////////////////
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        global $APPLICATION;

        if ($request["step"] < 2) {
            $APPLICATION->IncludeAdminFile(Loc::getMessage("DD_TOOLS_DEINSTALL_TITLE_STEP_1"), __DIR__ . "/deInstalInfo-step1.php");
        }
        // проверяем какой сейчас шаг, усли 2, производим удаление
        if ($request["step"] == 2) {
            if ($request["save_data"] == "Y") {
                $this->UnInstallDB();
            }
            $this->UnInstallEvents();
            $this->UnInstallFiles();
            $this->unInstallAgents();

            $this->manageInfoblock(false);
            $this->manageEmailTemplate(false);

            ModuleManager::UnRegisterModule("dd.tools");

            $APPLICATION->IncludeAdminFile(Loc::getMessage("DD_TOOLS_DEINSTALL_TITLE_STEP_2"), __DIR__ . "/deInstalInfo-step2.php");
        }

        return true;
    }

    /**
     * Метод для создания таблицы баз данных
     * @return void
     */
    function InstallDB()
    {
        Loader::includeModule($this->MODULE_ID);
        Loader::includeModule("iblock");

        $connection = Application::getConnection(DataTable::getConnectionName());

        $dataTableEntity = Base::getInstance("\DD\Tools\Entity\DataTable");
        if (!$connection->isTableExists($dataTableEntity->getDBTableName())) {
            try {
                $dataTableEntity->createDbTable();
            } catch (\Exception $e) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERROR" => $e->getMessage(),
                    "TABLE" => $dataTableEntity->getDBTableName()
                ], "DataTable::createDbTable", "/upload/logs/dd.tools.install.log");
            }
        }

        $maintenanceTableEntity = Base::getInstance("\DD\Tools\Entity\MaintenanceTable");

        if (!$connection->isTableExists($maintenanceTableEntity->getDBTableName())) {
            try {
                $maintenanceTableEntity->createDbTable();
            } catch (\Exception $e) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERROR" => $e->getMessage(),
                    "TABLE" => $maintenanceTableEntity->getDBTableName()
                ], "MaintenanceTable::createDbTable", "/upload/logs/dd.tools.install.log");
            }
        }
    }

    /**
     * Метод для удаления таблицы баз данных
     * @return void
     */
    function UnInstallDB()
    {
        Loader::includeModule($this->MODULE_ID);
        Loader::includeModule("iblock");

        Application::getConnection(DataTable::getConnectionName())->queryExecute("DROP TABLE IF EXISTS " . Base::getInstance("\DD\Tools\Entity\DataTable")->getDBTableName());
        Application::getConnection(DataTable::getConnectionName())->queryExecute("DROP TABLE IF EXISTS " . Base::getInstance("\DD\Tools\Entity\MaintenanceTable")->getDBTableName());

        Option::delete($this->MODULE_ID);
    }

    /**
     * Метод для создания обработчика событий
     * @return true
     */
    function InstallEvents()
    {
        $eventManager = EventManager::getInstance();

        // страницы
        $eventManager->registerEventHandler("main", "OnPageStart", $this->MODULE_ID, "\DD\Tools\Events", "OnPageStartHandler");
        $eventManager->registerEventHandler("main", "OnBeforeProlog", $this->MODULE_ID, "\DD\Tools\Events", "OnBeforePrologHandler");
        $eventManager->registerEventHandler("main", "OnProlog", $this->MODULE_ID, "\DD\Tools\Events", "OnPrologHandler");
        $eventManager->registerEventHandler("main", "OnEpilog", $this->MODULE_ID, "\DD\Tools\Events", "OnEpilogHandler");
        // пользователь
        $eventManager->registerEventHandler("main", "OnAfterUserLogin", $this->MODULE_ID, "\DD\Tools\Events", "OnAfterUserLoginHandler");
        $eventManager->registerEventHandler("main", "OnBeforeUserLogin", $this->MODULE_ID, "\DD\Tools\Events", "OnBeforeUserLoginHandler");
        // формы и запросы
        $eventManager->registerEventHandler("main", "OnEndBufferContent", $this->MODULE_ID, "\DD\Tools\Events", "OnEndBufferContentHandler");
        // админка
        $eventManager->registerEventHandler("main", "OnAdminContextMenuShow", $this->MODULE_ID, "\DD\Tools\Events", "OnAdminContextMenuShowHandler");
        $eventManager->registerEventHandler("main", "OnAdminListDisplay", $this->MODULE_ID, "\DD\Tools\Events", "OnAdminListDisplayHandler");
        // модули
        $eventManager->registerEventHandler($this->MODULE_ID, "OnSomeEvent", $this->MODULE_ID, "\DD\Tools\Main", "get");
        $eventManager->registerEventHandler($this->MODULE_ID, "\DD\Tools\Entity::OnBeforeAdd", $this->MODULE_ID, "\DD\Tools\Events", "eventHandler");
        $eventManager->registerEventHandler($this->MODULE_ID, "\DD\Tools\Entity::OnBeforeUpdate", $this->MODULE_ID, "\DD\Tools\Events", "eventHandler");

        return true;
    }

    /**
     * Метод для удаления обработчика событий
     * @return true
     */
    function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();

        // страницы
        $eventManager->unRegisterEventHandler("main", "OnPageStart", $this->MODULE_ID, "\DD\Tools\Events", "OnPageStartHandler");
        $eventManager->unRegisterEventHandler("main", "OnBeforeProlog", $this->MODULE_ID, "\DD\Tools\Events", "OnBeforePrologHandler");
        $eventManager->unRegisterEventHandler("main", "OnProlog", $this->MODULE_ID, "\DD\Tools\Events", "OnPrologHandler");
        $eventManager->unRegisterEventHandler("main", "OnEpilog", $this->MODULE_ID, "\DD\Tools\Events", "OnEpilogHandler");
        // пользователь
        $eventManager->unRegisterEventHandler("main", "OnAfterUserLogin", $this->MODULE_ID, "\DD\Tools\Events", "OnAfterUserLoginHandler");
        $eventManager->unRegisterEventHandler("main", "OnBeforeUserLogin", $this->MODULE_ID, "\DD\Tools\Events", "OnBeforeUserLoginHandler");
        // формы и запросы
        $eventManager->unRegisterEventHandler("main", "OnEndBufferContent", $this->MODULE_ID, "\DD\Tools\Events", "OnEndBufferContentHandler");
        // админка
        $eventManager->unRegisterEventHandler("main", "OnAdminContextMenuShow", $this->MODULE_ID, "\DD\Tools\Events", "OnAdminContextMenuShowHandler");
        $eventManager->unRegisterEventHandler("main", "OnAdminListDisplay", $this->MODULE_ID, "\DD\Tools\Events", "OnAdminListDisplayHandler");
        // модули
        $eventManager->unRegisterEventHandler($this->MODULE_ID, "OnSomeEvent", $this->MODULE_ID, "\DD\Tools\Main", "get");
        $eventManager->unRegisterEventHandler($this->MODULE_ID, "\DD\Tools\Entity::OnBeforeAdd", $this->MODULE_ID, "\DD\Tools\Events", "eventHandler");
        $eventManager->unRegisterEventHandler($this->MODULE_ID, "\DD\Tools\Entity::OnBeforeUpdate", $this->MODULE_ID, "\DD\Tools\Events", "eventHandler");

        return true;
    }

    /**
     * Метод для копирования файлов модуля при установке
     * @return true
     */
    function InstallFiles()
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
                    ], "CopyDirFiles", "/upload/logs/dd.tools.install.log");
                }
            }
        }

        return true;
    }

    /**
     * Метод для удаления файлов модуля при удалении
     * @return true
     */
    function UnInstallFiles()
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
    function installAgents()
    {
        $agent = \CAgent::AddAgent("\\DD\\Tools\\superAgent::run();", $this->MODULE_ID, "N", self::SUPER_AGENT_INTERVAL, "", "Y", "", 100);
        if (!$agent) {
            Debug::writeToFile([
                "DATE" => date("Y-m-d H:i:s"),
                "ERROR" => "Не удалось добавить агента superAgent"
            ], "CAgent::AddAgent", "/upload/logs/dd.tools.install.log");
        }

        $agent = \CAgent::AddAgent("\\DD\\Tools\\freespaceAgent::run();", $this->MODULE_ID, "N", self::FREE_SPACE_AGENT_INTERVAL, "", "Y", "", 100);
        if (!$agent) {
            Debug::writeToFile([
                "DATE" => date("Y-m-d H:i:s"),
                "ERROR" => "Не удалось добавить агента freespaceAgent"
            ], "CAgent::AddAgent", "/upload/logs/dd.tools.install.log");
        }
    }

    /**
     * Удаление агентов
     * @return void
     */
    function unInstallAgents()
    {
        \CAgent::RemoveModuleAgents($this->MODULE_ID);
    }

    /**
     * Установка/Удаление Инфоблоков
     * @param $install
     * @return mixed
     */
    function manageInfoblock($install = true)
    {
        require_once __DIR__ . "/lib/IblockInstaller.php";
        $installer = new IblockInstaller($this->MODULE_ID);

        return $install ? $installer->install() : $installer->uninstall();
    }

    /**
     * Установка/Удаление Почтовых шаблонов
     * @param $install
     * @return mixed
     */
    function manageEmailTemplate($install = true)
    {
        require_once __DIR__ . "/lib/EmailTemplateInstaller.php";
        $installer = new EmailTemplateInstaller($this->MODULE_ID);

        return $install ? $installer->install() : $installer->uninstall();
    }

    /**
     * Заполнение таблиц и инфоблоков тестовыми данными
     * @return true
     */
    function addData()
    {
        require_once __DIR__ . "/lib/DataInstaller.php";
        $installer = new DataInstaller($this->MODULE_ID);

        return $installer->install();
    }
}