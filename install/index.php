<?php

define("DD_MODULE_NAMESPACE", "dd.tools");

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\ModuleManager;
use DD\Tools\Entity\DataTable;
use DD\Tools\Install\DataInstaller;

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
    public $errors;

    function __construct()
    {
        $arModuleVersion = array();
        include_once(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_ID = DD_MODULE_NAMESPACE;
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
        // ModuleManager::RegisterModule(DD_MODULE_NAMESPACE);
        // $this->InstallDB();
        // $this->addData();
        // $this->InstallEvents();
        // $this->InstallFiles();
        // $this->installAgents();
        //
        // $APPLICATION->includeAdminFile(
        //     Loc::getMessage("DD_MODULE_INSTALL_TITLE"),
        //     __DIR__ . "/instalInfo.php"
        // );

        // С установкой в два шага
        ////////////////////////////////////////////////
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        global $APPLICATION;

        if ($request["step"] < 2) {
            $APPLICATION->IncludeAdminFile(Loc::getMessage("DD_MODULE_INSTALL_TITLE_STEP_1"), __DIR__ . "/instalInfo-step1.php");
        }

        if ($request["step"] == 2) {
            ModuleManager::RegisterModule(DD_MODULE_NAMESPACE);
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
            $this->installAgents();
            if ($request["add_data"] == "Y") {
                $this->addData();
            }

            $APPLICATION->IncludeAdminFile(Loc::getMessage("DD_MODULE_INSTALL_TITLE_STEP_2"), __DIR__ . "/instalInfo-step2.php");
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
        // ModuleManager::UnRegisterModule(DD_MODULE_NAMESPACE);
        //
        // $APPLICATION->includeAdminFile(
        //     Loc::getMessage("DD_MODULE_DEINSTALL_TITLE"),
        //     __DIR__ . "/deInstalInfo.php"
        // );

        // С удалением в два шага
        ////////////////////////////////////////////////
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        global $APPLICATION;

        if ($request["step"] < 2) {
            $APPLICATION->IncludeAdminFile(Loc::getMessage("DD_MODULE_DEINSTALL_TITLE_STEP_1"), __DIR__ . "/deInstalInfo-step1.php");
        }
        // проверяем какой сейчас шаг, усли 2, производим удаление
        if ($request["step"] == 2) {
            if ($request["save_data"] == "Y") {
                $this->UnInstallDB();
            }
            $this->UnInstallEvents();
            $this->UnInstallFiles();
            $this->unInstallAgents();
            ModuleManager::UnRegisterModule(DD_MODULE_NAMESPACE);

            $APPLICATION->IncludeAdminFile(Loc::getMessage("DD_MODULE_DEINSTALL_TITLE_STEP_2"), __DIR__ . "/deInstalInfo-step2.php");
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

        if (!Application::getConnection(DataTable::getConnectionName())->isTableExists(Base::getInstance("\DD\Tools\Entity\DataTable")->getDBTableName())) {
            Base::getInstance("\DD\Tools\Entity\DataTable")->createDbTable();
        }
        if (!Application::getConnection(DataTable::getConnectionName())->isTableExists(Base::getInstance("\DD\Tools\Entity\AuthorTable")->getDBTableName())) {
            Base::getInstance("\DD\Tools\Entity\AuthorTable")->createDbTable();
        }

        $this->createInfoblockType();
        $this->createInfoblock();
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
        Application::getConnection(DataTable::getConnectionName())->queryExecute("DROP TABLE IF EXISTS " . Base::getInstance("\DD\Tools\Entity\AuthorTable")->getDBTableName());

        $this->deleteInfoblock();
        $this->deleteInfoblockType();

        Option::delete($this->MODULE_ID);
    }

    /**
     * Метод для создания обработчика событий
     * @return true
     */
    function InstallEvents()
    {
        EventManager::getInstance()->registerEventHandler("main", "OnPageStart", $this->MODULE_ID, "\DD\Tools\Events", "OnPageStart");
        EventManager::getInstance()->registerEventHandler("main", "OnBeforeProlog", $this->MODULE_ID, "\DD\Tools\Events", "OnBeforePrologHandler");
        EventManager::getInstance()->registerEventHandler("main", "OnAdminContextMenuShow", $this->MODULE_ID, "\DD\Tools\Events", "OnAdminContextMenuShowHandler");
        EventManager::getInstance()->registerEventHandler("main", "OnAdminListDisplay", $this->MODULE_ID, "\DD\Tools\Events", "OnAdminListDisplayHandler");
        // модуля
        EventManager::getInstance()->registerEventHandler($this->MODULE_ID, "OnSomeEvent", $this->MODULE_ID, "\DD\Tools\Main", 'get');
        EventManager::getInstance()->registerEventHandler($this->MODULE_ID, "\DD\Tools\Entity::OnBeforeUpdate", $this->MODULE_ID, "\DD\Tools\Events", "eventHandler");

        return true;
    }

    /**
     * Метод для удаления обработчика событий
     * @return true
     */
    function UnInstallEvents()
    {
        EventManager::getInstance()->unRegisterEventHandler("main", "OnPageStart", $this->MODULE_ID, "\DD\Tools\Events", "OnPageStart");
        EventManager::getInstance()->unRegisterEventHandler("main", "OnBeforeProlog", $this->MODULE_ID, "\DD\Tools\Events", "OnBeforePrologHandler");
        EventManager::getInstance()->unRegisterEventHandler("main", "OnAdminContextMenuShowHandler", $this->MODULE_ID, "\DD\Tools\Events", "OnAdminContextMenuShowHandler");
        EventManager::getInstance()->unRegisterEventHandler("main", "OnAdminListDisplay", $this->MODULE_ID, "\DD\Tools\Events", "OnAdminListDisplayHandler");
        // модуля
        EventManager::getInstance()->unRegisterEventHandler($this->MODULE_ID, "OnSomeEvent", $this->MODULE_ID, "\DD\Tools\Main", 'get');
        EventManager::getInstance()->unRegisterEventHandler($this->MODULE_ID, "\DD\Tools\Entity::OnBeforeUpdate", $this->MODULE_ID, "\DD\Tools\Events", "eventHandler");

        return true;
    }

    /**
     * Метод для копирования файлов модуля при установке
     * @return true
     */
    function InstallFiles()
    {
        // Определяем, где находится модуль по текущему пути
        $isLocalModule = strpos(__DIR__, "/local/") !== false;

        // Определяем базовую папку для компонентов
        $componentsPath = $isLocalModule ?
            $_SERVER["DOCUMENT_ROOT"] . "/local/components" :
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components";

        // Копируем статические файлы
        CopyDirFiles(__DIR__ . "/assets/images", Application::getDocumentRoot() . "/bitrix/images/" . $this->MODULE_ID . "/", true, true);
        CopyDirFiles(__DIR__ . "/assets/js", Application::getDocumentRoot() . "/bitrix/js/" . $this->MODULE_ID . "/", true, true);
        CopyDirFiles(__DIR__ . "/assets/css", Application::getDocumentRoot() . "/bitrix/css/" . $this->MODULE_ID . "/", true, true);
        CopyDirFiles(__DIR__ . "/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin", true, true);

        // Копируем компоненты в соответствующую папку с учетом namespace модуля
        CopyDirFiles(__DIR__ . "/components", $componentsPath, true, true);

        CopyDirFiles(__DIR__ . "/files", $_SERVER["DOCUMENT_ROOT"] . "/", true, true);

        return true;
    }

    /**
     * Метод для удаления файлов модуля при удалении
     * @return true
     */
    function UnInstallFiles()
    {
        // Определяем, где находится модуль по текущему пути
        $isLocalModule = strpos(__DIR__, "/local/") !== false;

        // Определяем путь к компонентам с учетом namespace модуля
        $componentsPath = $isLocalModule ? "/local/components/" : "/bitrix/components/";

        // Удаляем статические файлы
        Directory::deleteDirectory(Application::getDocumentRoot() . "/bitrix/images/" . $this->MODULE_ID);
        Directory::deleteDirectory(Application::getDocumentRoot() . "/bitrix/js/" . $this->MODULE_ID);
        Directory::deleteDirectory(Application::getDocumentRoot() . "/bitrix/css/" . $this->MODULE_ID);

        // Удаляем только компоненты своего модуля
        if (is_dir($_SERVER["DOCUMENT_ROOT"] . $componentsPath . $this->MODULE_ID)) {
            DeleteDirFilesEx($componentsPath . $this->MODULE_ID);
        }

        // Удаляем админские и прочие файлы
        DeleteDirFiles(__DIR__ . "/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin");
        DeleteDirFiles(__DIR__ . "/files", $_SERVER["DOCUMENT_ROOT"] . "/");

        return true;
    }

    /**
     * Установка агентов
     * @return void
     */
    function installAgents()
    {
        \CAgent::AddAgent("\DD\Tools\Agent::superAgent();", $this->MODULE_ID, "N", 120, "", "Y", "", 100);
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
     * Заполнение таблиц и инфоблоков тестовыми данными
     * @return true
     */
    function addData()
    {
        require_once __DIR__ . "/lib/DataInstaller.php";
        $installer = new DataInstaller($this->MODULE_ID);

        return $installer->install();
    }

    /**
     * @return void
     */
    private function createInfoblockType()
    {
        $arFields = array(
            "ID" => "dd_tools_content",
            "SECTIONS" => "Y",
            "ELEMENTS" => "Y",
            "IN_RSS" => "N",
            "SORT" => 500,
            "LANG" => array(
                "ru" => array("NAME" => "DD Tools - Контент", "SECTION_NAME" => "Разделы", "ELEMENT_NAME" => "Элементы"),
                "en" => array("NAME" => "DD Tools - Content", "SECTION_NAME" => "Sections", "ELEMENT_NAME" => "Elements")
            )
        );

        $obBlocktype = new CIBlockType;
        $obBlocktype->Add($arFields);
    }

    /**
     * @return void
     */
    private function createInfoblock()
    {
        $arFields = array(
            "ACTIVE" => "Y",
            "NAME" => "DD Tools - Новости и статьи",
            "CODE" => "dd_tools_news",
            "IBLOCK_TYPE_ID" => "dd_tools_content",
            "SITE_ID" => array("s1"),
            "SORT" => 500,
            "GROUP_ID" => array("2" => "R"),
            "VERSION" => 2,
            "WORKFLOW" => "N",
            "BIZPROC" => "N",
            "INDEX_ELEMENT" => "Y",
            "INDEX_SECTION" => "Y"
        );

        $ib = new CIBlock;
        $iblockId = $ib->Add($arFields);

        if ($iblockId) {
            $this->createIblockProperties($iblockId);
        }
    }

    /**
     * @param $iblockId
     * @return void
     */
    private function createIblockProperties($iblockId)
    {
        $arPropFields = array(
            array("NAME" => "Автор", "ACTIVE" => "Y", "SORT" => "100", "CODE" => "AUTHOR", "PROPERTY_TYPE" => "S", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N", "SEARCHABLE" => "Y"),
            array("NAME" => "Источник", "ACTIVE" => "Y", "SORT" => "200", "CODE" => "SOURCE", "PROPERTY_TYPE" => "S", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N", "SEARCHABLE" => "Y"),
            array("NAME" => "Теги", "ACTIVE" => "Y", "SORT" => "300", "CODE" => "TAGS", "PROPERTY_TYPE" => "S", "MULTIPLE" => "Y", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N", "SEARCHABLE" => "Y"),
            array("NAME" => "Рейтинг", "ACTIVE" => "Y", "SORT" => "400", "CODE" => "RATING", "PROPERTY_TYPE" => "N", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N"),
            array("NAME" => "Показывать на главной", "ACTIVE" => "Y", "SORT" => "500", "CODE" => "SHOW_ON_MAIN", "PROPERTY_TYPE" => "L", "LIST_TYPE" => "C", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N",
                "VALUES" => array(
                    array("VALUE" => "Да", "DEF" => "N", "SORT" => "10"),
                    array("VALUE" => "Нет", "DEF" => "Y", "SORT" => "20")
                )
            )
        );
        $ibp = new CIBlockProperty;
        foreach ($arPropFields as $field) {
            $ibp->Add($field);
        }
    }

    /**
     * @return void
     */
    private function deleteInfoblock()
    {
        $res = CIBlock::GetList(array(), array("CODE" => "dd_tools_news", "CHECK_PERMISSIONS" => "N"));

        if ($ar_res = $res->Fetch()) {

            $iblockId = $ar_res["ID"];
            $rsElements = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $iblockId), false, false, array("ID"));

            while ($arElement = $rsElements->Fetch()) {
                CIBlockElement::Delete($arElement["ID"]);
            }

            $rsSections = CIBlockSection::GetList(array(), array("IBLOCK_ID" => $iblockId), false, array("ID"));

            while ($arSection = $rsSections->Fetch()) {
                CIBlockSection::Delete($arSection["ID"]);
            }

            CIBlock::Delete($iblockId);
        }
    }

    /**
     * @return void
     */
    private function deleteInfoblockType()
    {
        $res = CIBlock::GetList(array(), array("TYPE" => "dd_tools_content", "CHECK_PERMISSIONS" => "N"));

        if (!$res->Fetch()) {
            $obBlockType = new CIBlockType;
            $obBlockType->Delete("dd_tools_content");
        }
    }
}