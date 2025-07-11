<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Iblock;

if (!Loader::includeModule("iblock")) {
    return;
}

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
    "PARAMETERS" => array(
        "IBLOCK_ID" => array(
            "PARENT" => "BASE",
            "NAME" => "ID инфоблока",
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
            "DEFAULT" => "",
        ),
        "EMAIL_TEMPLATE" => array(
            "PARENT" => "BASE",
            "NAME" => "ID шаблона письма",
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "USE_BITRIX_CAPTCHA" => array(
            "PARENT" => "BASE",
            "NAME" => "Использовать Bitrix Captcha",
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "USE_GOOGLE_RECAPTCHA" => array(
            "PARENT" => "BASE",
            "NAME" => "Использовать Google reCAPTCHA v3",
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "GOOGLE_RECAPTCHA_PUBLIC_KEY" => array(
            "PARENT" => "BASE",
            "NAME" => "Публичный ключ Google reCAPTCHA v3",
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "GOOGLE_RECAPTCHA_SECRET_KEY" => array(
            "PARENT" => "BASE",
            "NAME" => "Секретный ключ Google reCAPTCHA v3",
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
    ),
);