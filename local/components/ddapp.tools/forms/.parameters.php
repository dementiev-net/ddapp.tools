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
    "GROUPS" => array(
        "BASE" => array(
            "NAME" => "Основные настройки"
        ),
        "CAPTCHA" => array(
            "NAME" => "Настройки защиты"
        ),
        "FILES" => array(
            "NAME" => "Настройки файлов"
        ),
        "ANALYTICS" => array(
            "NAME" => "Настройки аналитики"
        ),
    ),
    "PARAMETERS" => array(
        "IBLOCK_ID" => array(
            "PARENT" => "BASE",
            "NAME" => "ID инфоблока",
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
            "DEFAULT" => "",
            "REFRESH" => "Y",
        ),
        "EMAIL_TEMPLATE" => array(
            "PARENT" => "BASE",
            "NAME" => "ID шаблона письма",
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "BUTTON_TEXT" => array(
            "PARENT" => "BASE",
            "NAME" => "Текст кнопки открытия формы",
            "TYPE" => "STRING",
            "DEFAULT" => "Открыть форму",
        ),
        "ENABLE_ANALYTICS" => array(
            "PARENT" => "BASE",
            "NAME" => "Включить аналитику",
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),

        // Настройки защиты
        "USE_BITRIX_CAPTCHA" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => "Использовать Bitrix Captcha",
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "USE_GOOGLE_RECAPTCHA" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => "Использовать Google reCAPTCHA v3",
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "GOOGLE_RECAPTCHA_PUBLIC_KEY" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => "Публичный ключ Google reCAPTCHA v3",
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "GOOGLE_RECAPTCHA_SECRET_KEY" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => "Секретный ключ Google reCAPTCHA v3",
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "RATE_LIMIT_ENABLED" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => "Включить ограничение частоты отправки",
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "RATE_LIMIT_PER_MINUTE" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => "Максимум отправок в минуту",
            "TYPE" => "STRING",
            "DEFAULT" => "5",
        ),
        "RATE_LIMIT_PER_HOUR" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => "Максимум отправок в час",
            "TYPE" => "STRING",
            "DEFAULT" => "30",
        ),

        // Настройки файлов
        "MAX_FILE_SIZE" => array(
            "PARENT" => "FILES",
            "NAME" => "Максимальный размер файла (MB)",
            "TYPE" => "STRING",
            "DEFAULT" => "10",
        ),
        "ALLOWED_FILE_EXTENSIONS" => array(
            "PARENT" => "FILES",
            "NAME" => "Разрешенные расширения файлов (через запятую)",
            "TYPE" => "STRING",
            "DEFAULT" => "jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip",
            "COLS" => 50,
        ),
        "FILE_UPLOAD_DIR" => array(
            "PARENT" => "FILES",
            "NAME" => "Папка для загрузки файлов",
            "TYPE" => "STRING",
            "DEFAULT" => "/upload/ddapp_forms/",
        ),
        "CHECK_FILE_CONTENT" => array(
            "PARENT" => "FILES",
            "NAME" => "Проверять содержимое файлов на безопасность",
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "SECURITY_LEVEL" => array(
            "PARENT" => "FILES",
            "NAME" => "Уровень безопасности файлов",
            "TYPE" => "LIST",
            "VALUES" => array(
                "low" => "Низкий",
                "medium" => "Средний",
                "high" => "Высокий"
            ),
            "DEFAULT" => "medium",
        ),

        // Настройки аналитики
        "GA_MEASUREMENT_ID" => array(
            "PARENT" => "ANALYTICS",
            "NAME" => "Google Analytics Measurement ID (G-XXXXXXXXXX)",
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "YANDEX_METRIKA_ID" => array(
            "PARENT" => "ANALYTICS",
            "NAME" => "ID счетчика Яндекс.Метрики",
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "VK_PIXEL_ID" => array(
            "PARENT" => "ANALYTICS",
            "NAME" => "VK Pixel ID",
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
    ),
);