<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!Loader::includeModule("iblock")) {
    return;
}

Loc::loadMessages(__FILE__);

// Получаем список инфоблоков
$arIBlocks = [];
$rsIBlocks = CIBlock::GetList(
    ["SORT" => "ASC"],
    ["ACTIVE" => "Y"]
);
while ($arIBlock = $rsIBlocks->Fetch()) {
    $arIBlocks[$arIBlock["ID"]] = "[" . $arIBlock["ID"] . "] " . $arIBlock["NAME"];
}

$arComponentParameters = array(
    "GROUPS" => array(
        "BASE" => array(
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_GROUP_BASE"),
        ),
        "CAPTCHA" => array(
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_GROUP_CAPTCHA"),
        ),
        "FILES" => array(
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_GROUP_FILES"),
        ),
        "ANALYTICS" => array(
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_GROUP_ANALYTICS"),
        ),
    ),
    "PARAMETERS" => array(
        "IBLOCK_ID" => array(
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_IBLOCK_ID"),
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
            "DEFAULT" => "",
            "REFRESH" => "Y",
        ),
        "EMAIL_TEMPLATE" => array(
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_EMAIL_TEMPLATE"),
            "TYPE" => "STRING",
            "DEFAULT" => "DDAPP_MESSAGE_FORM",
        ),
        "BUTTON_TEXT" => array(
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_BUTTON_TEXT"),
            "TYPE" => "STRING",
            "DEFAULT" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_BUTTON_DEFAULT"),
        ),
        "BUTTON_CLASS" => array(
            "PARENT" => "APPEARANCE",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_BUTTON_CLASS"),
            "TYPE" => "STRING",
            "DEFAULT" => "btn btn-primary btn-lg"
        ),

        "BUTTON_ICON" => array(
            "PARENT" => "APPEARANCE",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_BUTTON_ICON"),
            "TYPE" => "STRING",
            "DEFAULT" => "fa-solid fa-envelope"
        ),
        "MODAL_SIZE" => array(
            "PARENT" => "APPEARANCE",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_MODAL_SIZE"),
            "TYPE" => "LIST",
            "VALUES" => array(
                "modal-sm" => Loc::getMessage("DDAPP_FORM_BUTTON_MODAL_SIZE_SMALL"),
                "modal-lg" => Loc::getMessage("DDAPP_FORM_BUTTON_MODAL_SIZE_LARGE"),
                "modal-xl" => Loc::getMessage("DDAPP_FORM_BUTTON_MODAL_SIZE_EXTRA_LARGE"),
                "" => Loc::getMessage("DDAPP_FORM_BUTTON_MODAL_SIZE_DEFAULT")
            ),
            "DEFAULT" => "modal-lg"
        ),
        "USE_PRIVACY_POLICY" => array(
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_USE_PRIVACY_POLICY"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "PRIVACY_POLICY_TEXT" => array(
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_PRIVACY_POLICY_TEXT"),
            "TYPE" => "STRING",
            "DEFAULT" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_PRIVACY_POLICY_DEFAULT"),
            "COLS" => 80,
        ),

        // Настройки защиты
        "USE_BITRIX_CAPTCHA" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_USE_BITRIX_CAPTCHA"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "USE_GOOGLE_RECAPTCHA" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_USE_GOOGLE_RECAPTCHA"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "GOOGLE_RECAPTCHA_PUBLIC_KEY" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_GOOGLE_RECAPTCHA_PUBLIC_KEY"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "GOOGLE_RECAPTCHA_SECRET_KEY" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_GOOGLE_RECAPTCHA_SECRET_KEY"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "RATE_LIMIT_ENABLED" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_RATE_LIMIT_ENABLED"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "RATE_LIMIT_PER_MINUTE" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_RATE_LIMIT_PER_MINUTE"),
            "TYPE" => "STRING",
            "DEFAULT" => "5",
        ),
        "RATE_LIMIT_PER_HOUR" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_RATE_LIMIT_PER_HOUR"),
            "TYPE" => "STRING",
            "DEFAULT" => "30",
        ),

        // Настройки файлов
        "MAX_FILE_SIZE" => array(
            "PARENT" => "FILES",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_MAX_FILE_SIZE"),
            "TYPE" => "STRING",
            "DEFAULT" => "10",
        ),
        "ALLOWED_FILE_EXTENSIONS" => array(
            "PARENT" => "FILES",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_ALLOWED_FILE_EXTENSIONS"),
            "TYPE" => "STRING",
            "DEFAULT" => "jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip",
            "COLS" => 50,
        ),
        "FILE_UPLOAD_DIR" => array(
            "PARENT" => "FILES",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_FILE_UPLOAD_DIR"),
            "TYPE" => "STRING",
            "DEFAULT" => "/upload/forms/",
        ),
        "CHECK_FILE_CONTENT" => array(
            "PARENT" => "FILES",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_CHECK_FILE_CONTENT"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),

        // Настройки аналитики
        "ENABLE_ANALYTICS" => array(
            "PARENT" => "ANALYTICS",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_USE_ANALYTICS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "GA_MEASUREMENT_ID" => array(
            "PARENT" => "ANALYTICS",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_GA_MEASUREMENT_ID"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "YANDEX_METRIKA_ID" => array(
            "PARENT" => "ANALYTICS",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_YANDEX_METRIKA_ID"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "VK_PIXEL_ID" => array(
            "PARENT" => "ANALYTICS",
            "NAME" => Loc::getMessage("DDAPP_FORM_BUTTON_PARAM_VK_PIXEL_ID"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
    ),
);