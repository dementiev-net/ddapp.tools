<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

require_once __DIR__ . '/../config.php';

// Проверяем, что модуль установлен
if (!CModule::IncludeModule(DD_MODULE_NAMESPACE)) {
    return array();
}

// Подключаем стили
$APPLICATION->SetAdditionalCSS("/bitrix/css/" . DD_MODULE_NAMESPACE . "/styles.css");

// Хандлер формирования меню
AddEventHandler("main", "OnBuildGlobalMenu", "OnBuildGlobalMenuHandlerDD");

function OnBuildGlobalMenuHandlerDD(&$arGlobalMenu, &$arModuleMenu)
{
    if (!defined("DD_MENU_INCLUDED")) {
        define("DD_MENU_INCLUDED", true);

        Loc::loadMessages(__FILE__);

        if ($GLOBALS["APPLICATION"]->GetGroupRight(DD_MODULE_NAMESPACE) >= "R") {

            // Меню настроек
            $arSettingsMenu = array(
                "text" => Loc::getMessage("DD_MODULE_MENU_SETTINGS_TEXT"),
                "title" => Loc::getMessage("DD_MODULE_MENU_SETTINGS_TITLE"),
                "icon" => "sys_menu_icon",
                "page_icon" => "sys_menu_icon",
                "items_id" => "menu_dd_tools_2",
                "menu_id" => "global_menu_dd_tools_2",
                "url" => "settings.php?lang=" . LANGUAGE_ID . "&mid=" . DD_MODULE_NAMESPACE,
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
                        "url" => "settings.phplang=" . LANGUAGE_ID . "&mid=" . DD_MODULE_NAMESPACE,
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
            $arGlobalMenu["global_menu_dd"]["items"][DD_MODULE_NAMESPACE . "_set"] = $arSettingsMenu;
            $arGlobalMenu["global_menu_dd"]["items"][DD_MODULE_NAMESPACE] = $arMenu1;
        }
    }
}