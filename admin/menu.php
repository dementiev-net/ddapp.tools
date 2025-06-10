<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

// Проверяем, что модуль установлен
if (!CModule::IncludeModule("dd.tools")) {
    return array();
}

// Подключаем стили
$APPLICATION->SetAdditionalCSS("/bitrix/css/dd.tools/styles.css");

// Хандлер формирования меню
AddEventHandler("main", "OnBuildGlobalMenu", "OnBuildGlobalMenuHandlerDD");

function OnBuildGlobalMenuHandlerDD(&$arGlobalMenu, &$arModuleMenu)
{
    global $APPLICATION;

    if (!defined("DD_MENU_INCLUDED")) {
        define("DD_MENU_INCLUDED", true);

        Loc::loadMessages(__FILE__);

        // Получим права доступа текущего пользователя на модуль
        $POST_RIGHT = $APPLICATION->GetGroupRight("dd.tools");
        $POST_RIGHT_MAIN = $APPLICATION->GetGroupRight("main");

        if ($POST_RIGHT >= "R") {

            // Меню настроек
            $arSettingsMenu = array(
                "text" => Loc::getMessage("DD_MODULE_MENU_SETTINGS_TEXT"),
                "title" => Loc::getMessage("DD_MODULE_MENU_SETTINGS_TITLE"),
                "icon" => "sys_menu_icon",
                "page_icon" => "sys_menu_icon",
                "items_id" => "menu_dd_tools_2",
                "menu_id" => "global_menu_dd_tools_2",
                "url" => "settings.php?lang=" . LANGUAGE_ID . "&mid=dd.tools",
                "sort" => 100,
                "items" => array()
            );

            // Первое меню
            $arMenu1 = array(
                "text" => Loc::getMessage("DD_MODULE_MENU_TEXT"),
                "title" => Loc::getMessage("DD_MODULE_MENU_TITLE"),
                "icon" => "form_menu_icon",
                "page_icon" => "form_menu_icon",
                "items_id" => "menu_dd_tools",
                "menu_id" => "global_menu_dd_tools",
                "sort" => 110,
                "items" => array(
                    array(
                        "text" => Loc::getMessage("DD_MODULE_MENU_TEST1"),
                        "title" => Loc::getMessage("DD_MODULE_MENU_TEST1_TITLE"),
                        "url" => "hmarketing.php?lang=" . LANGUAGE_ID,
                        "sort" => 10,
                        "icon" => "imi_typography",
                        "page_icon" => "pi_typography",
                        "items_id" => "main",
                    ),
                    array(
                        "text" => Loc::getMessage("DD_MODULE_MENU_TEST2"),
                        "title" => Loc::getMessage("DD_MODULE_MENU_TEST2_TITLE"),
                        "url" => "settings.phplang=" . LANGUAGE_ID . "&mid=dd.tools",
                        "sort" => 10,
                        "icon" => "imi_typography",
                        "page_icon" => "pi_typography",
                        "items_id" => "main",
                    ),
                    $arGenerate
                ),
            );

            // Создаём глобальное меню, если ещё нет
            if (!isset($arGlobalMenu["global_menu_dd"])) {
                $arGlobalMenu["global_menu_dd"] = array(
                    "menu_id" => "global_menu_dd",
                    "text" => Loc::getMessage("DD_MODULE_MENU_GLOBAL_TEXT"),
                    "title" => Loc::getMessage("DD_MODULE_MENU_GLOBAL_TITLE"),
                    "sort" => 1000,
                    "items_id" => "global_menu_dd_items",
                );
            }

            // Добавляем оба меню
            if ($POST_RIGHT_MAIN == "W") {
                $arGlobalMenu["global_menu_dd"]["items"]["dd.tools_set"] = $arSettingsMenu;
            }
            $arGlobalMenu["global_menu_dd"]["items"]["dd.tools"] = $arMenu1;
        }
    }
}