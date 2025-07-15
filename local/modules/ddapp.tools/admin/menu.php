<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use DDAPP\Tools\Main;
use DDAPP\Tools\Helpers\UserHelper;

// Проверяем, что модуль установлен
if (!CModule::IncludeModule(Main::MODULE_ID)) {
    return [];
}

Loc::loadMessages(__FILE__);

// Подключаем стили
$APPLICATION->SetAdditionalCSS("/bitrix/css/ddapp.tools/styles.css");

// Хандлер формирования меню
AddEventHandler("main", "OnBuildGlobalMenu", "OnBuildGlobalMenuHandlerDD");

function OnBuildGlobalMenuHandlerDD(&$arGlobalMenu, &$arModuleMenu)
{
    // Права доступа текущего пользователя на модуль
    if (UserHelper::hasModuleAccess("") >= "R") {

        // Меню настроек
        $arSettingsMenu = [
            "text" => Loc::getMessage("DDAPP_TOOLS_MENU_SETTINGS_TEXT"),
            "title" => Loc::getMessage("DDAPP_TOOLS_MENU_SETTINGS_TITLE"),
            "icon" => "sys_menu_icon",
            "page_icon" => "sys_menu_icon",
            "items_id" => "menu_ddapp_tools_2",
            "menu_id" => "global_menu_ddapp_tools_2",
            "url" => "settings.php?lang=" . LANG . "&mid=ddapp.tools",
            "sort" => 100,
            "items" => []
        ];

        // Логи
        $arMenuLogMenu = [
            "text" => Loc::getMessage("DDAPP_TOOLS_MENU_LOG_TEXT"),
            "title" => Loc::getMessage("DDAPP_TOOLS_MENU_LOG_TITLE"),
            "icon" => "learning_icon_tests",
            "page_icon" => "learning_icon_tests",
            "items_id" => "menu_ddapp_tools_3",
            "menu_id" => "global_menu_ddapp_tools_3",
            "url" => "ddapp_log.php?lang=" . LANG,
            "sort" => 110,
            "items" => []
        ];

        // План технического обслуживания
        $arMaintenanceMenu = [
            "text" => Loc::getMessage("DDAPP_TOOLS_MENU_MAINTENANCE_TEXT"),
            "title" => Loc::getMessage("DDAPP_TOOLS_MENU_MAINTENANCE_TITLE"),
            "icon" => "extension_menu_icon",
            "page_icon" => "extension_menu_icon",
            "items_id" => "menu_ddapp_tools_3",
            "menu_id" => "global_menu_ddapp_tools_3",
            "url" => "ddapp_maintenance_list.php?lang=" . LANG,
            "more_url" => [
                "ddapp_maintenance_edit.php", // можно также добавить GET параметры, если нужно
            ],
            "sort" => 200,
            "items" => []
        ];

        // Работа с данными
        $arMenuDataMenu = [
            "text" => Loc::getMessage("DDAPP_TOOLS_MENU_DATA_TEXT"),
            "title" => Loc::getMessage("DDAPP_TOOLS_MENU_DATA_TITLE"),
            "icon" => "workflow_menu_icon",
            "page_icon" => "workflow_menu_icon",
            "items_id" => "menu_ddapp_tools",
            "menu_id" => "global_menu_ddapp_tools",
            "sort" => 201,
            "items" => [
                [
                    "text" => Loc::getMessage("DDAPP_TOOLS_MENU_DATA_EXPORT"),
                    "title" => Loc::getMessage("DDAPP_TOOLS_MENU_DATA_EXPORT_TITLE"),
                    "url" => "ddapp_data_export.php?lang=" . LANG,
                    "sort" => 10,
                    "icon" => "imi_typography",
                    "page_icon" => "pi_typography",
                    "items_id" => "main",
                ], [
                    "text" => Loc::getMessage("DDAPP_TOOLS_MENU_DATA_IMPORT"),
                    "title" => Loc::getMessage("DDAPP_TOOLS_MENU_DATA_IMPORT_TITLE"),
                    "url" => "ddapp_data_import.php?lang=" . LANG,
                    "sort" => 10,
                    "icon" => "imi_typography",
                    "page_icon" => "pi_typography",
                    "items_id" => "main",
                ], [
                    "text" => Loc::getMessage("DDAPP_TOOLS_MENU_DATA_IMAGES"),
                    "title" => Loc::getMessage("DDAPP_TOOLS_MENU_DATA_IMAGES_TITLE"),
                    "url" => "ddapp_data_images.php?lang=" . LANG,
                    "sort" => 10,
                    "icon" => "imi_typography",
                    "page_icon" => "pi_typography",
                    "items_id" => "main",
                ],
                $arGenerate
            ],
        ];

        // Создаём глобальное меню, если ещё нет
        if (!isset($arGlobalMenu["global_menu_dd"])) {
            $arGlobalMenu["global_menu_dd"] = [
                "menu_id" => "global_menu_dd",
                "text" => Loc::getMessage("DDAPP_TOOLS_MENU_GLOBAL_TEXT"),
                "title" => Loc::getMessage("DDAPP_TOOLS_MENU_GLOBAL_TITLE"),
                "sort" => 1000,
                "items_id" => "global_menu_ddapp_items",
            ];
        }

        // Добавляем оба меню
        if (UserHelper::hasModuleAccess("main") == "W") {
            $arGlobalMenu["global_menu_dd"]["items"][Main::MODULE_ID] = $arSettingsMenu;
        }
        $arGlobalMenu["global_menu_dd"]["items"][Main::MODULE_ID . "_main"] = $arMaintenanceMenu;
        $arGlobalMenu["global_menu_dd"]["items"][Main::MODULE_ID . "_data"] = $arMenuDataMenu;
        $arGlobalMenu["global_menu_dd"]["items"][Main::MODULE_ID . "_log"] = $arMenuLogMenu;
    }
}