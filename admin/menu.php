<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

// Проверяем, что модуль установлен
if (!CModule::IncludeModule("dd.tools")) {
    return [];
}

Loc::loadMessages(__FILE__);

// Подключаем стили
$APPLICATION->SetAdditionalCSS("/bitrix/css/dd.tools/styles.css");

// Хандлер формирования меню
AddEventHandler("main", "OnBuildGlobalMenu", "OnBuildGlobalMenuHandlerDD");

function OnBuildGlobalMenuHandlerDD(&$arGlobalMenu, &$arModuleMenu)
{
    global $APPLICATION;

    // Получим права доступа текущего пользователя на модуль
    $POST_RIGHT = $APPLICATION->GetGroupRight("dd.tools");
    $POST_RIGHT_MAIN = $APPLICATION->GetGroupRight("main");

    if ($POST_RIGHT >= "R") {

        // Меню настроек
        $arSettingsMenu = [
            "text" => Loc::getMessage("DD_TOOLS_MENU_SETTINGS_TEXT"),
            "title" => Loc::getMessage("DD_TOOLS_MENU_SETTINGS_TITLE"),
            "icon" => "sys_menu_icon",
            "page_icon" => "sys_menu_icon",
            "items_id" => "menu_dd_tools_2",
            "menu_id" => "global_menu_dd_tools_2",
            "url" => "settings.php?lang=" . LANG . "&mid=dd.tools",
            "sort" => 100,
            "items" => []
        ];

        // План технического обслуживания
        $arMaintenanceMenu = [
            "text" => Loc::getMessage("DD_TOOLS_MENU_MAINTENANCE_TEXT"),
            "title" => Loc::getMessage("DD_TOOLS_MENU_MAINTENANCE_TITLE"),
            "icon" => "extension_menu_icon",
            "page_icon" => "extension_menu_icon",
            "items_id" => "menu_dd_tools_3",
            "menu_id" => "global_menu_dd_tools_3",
            "url" => "dd_maintenance_list.php?lang=" . LANG,
            "more_url" => [
                "dd_maintenance_edit.php", // можно также добавить GET параметры, если нужно
            ],
            "sort" => 100,
            "items" => []
        ];

        // Первое меню
        $arMenu1 = [
            "text" => Loc::getMessage("DD_TOOLS_MENU_DATA_TEXT"),
            "title" => Loc::getMessage("DD_TOOLS_MENU_DATA_TITLE"),
            "icon" => "workflow_menu_icon",
            "page_icon" => "workflow_menu_icon",
            "items_id" => "menu_dd_tools",
            "menu_id" => "global_menu_dd_tools",
            "sort" => 110,
            "items" => [
                [
                    "text" => Loc::getMessage("DD_TOOLS_MENU_DATA_EXPORT"),
                    "title" => Loc::getMessage("DD_TOOLS_MENU_DATA_EXPORT_TITLE"),
                    "url" => "dd_data_export.php?lang=" . LANG,
                    "sort" => 10,
                    "icon" => "imi_typography",
                    "page_icon" => "pi_typography",
                    "items_id" => "main",
                ],[
                    "text" => Loc::getMessage("DD_TOOLS_MENU_DATA_IMAGES"),
                    "title" => Loc::getMessage("DD_TOOLS_MENU_DATA_IMAGES_TITLE"),
                    "url" => "dd_data_images.php?lang=" . LANG,
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
                "text" => Loc::getMessage("DD_TOOLS_MENU_GLOBAL_TEXT"),
                "title" => Loc::getMessage("DD_TOOLS_MENU_GLOBAL_TITLE"),
                "sort" => 1000,
                "items_id" => "global_menu_dd_items",
            ];
        }

        // Добавляем оба меню
        if ($POST_RIGHT_MAIN == "W") {
            $arGlobalMenu["global_menu_dd"]["items"]["dd.tools_set"] = $arSettingsMenu;
        }
        $arGlobalMenu["global_menu_dd"]["items"]["dd.tools_main"] = $arMaintenanceMenu;

        $arGlobalMenu["global_menu_dd"]["items"]["dd.tools"] = $arMenu1;
    }
}