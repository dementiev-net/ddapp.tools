<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Components\FileSecurityValidator;
use DDAPP\Tools\Components\RateLimiter;

Loc::loadMessages(__FILE__);
Loader::includeModule("iblock");

// Настройка логирования
LogHelper::configure();

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die("Access denied");
}

$request = Application::getInstance()->getContext()->getRequest();

header("Content-Type: application/json; charset=utf-8");

// Проверяем, что это AJAX запрос
//if (!$request->isAjaxRequest()) {
//    http_response_code(400);
//    echo json_encode(["error" => Loc::getMessage("DDAPP_FORM_AJAX_MESSAGE_ERROR_REQUEST")]);
//    die();
//}

//LogHelper::error($componentId . $this->iblockId, "Form save failed", [
//    "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"
//]);

$action = $request->getPost("action");
$componentId = $request->getPost("id");
$params = $request->getPost("form-params");

if (!$action || !$componentId || !$params["IBLOCK_ID"]) {
    http_response_code(400);
    echo json_encode(["error" => Loc::getMessage("DDAPP_FORM_AJAX_MESSAGE_ERROR_PARAMS")]);
    die();
}

/**
 * Загрузка формы
 */
if ($action === "load") {

    $params["CACHE_TIME"] = isset($params["CACHE_TIME"]) ? $params["CACHE_TIME"] : 3600;
    $iblockId = (int)$params["IBLOCK_ID"];

    // Загрузка конфигурации безопасности файлов
    $fileConfig = loadFileConfig($params);

    // Инициализация компонентов
    $rateLimiter = new RateLimiter($iblockId, $params["RATE_LIMITS"] ?? []);
    $fileValidator = new FileSecurityValidator($fileConfig, $iblockId);

    $res = CIBlock::GetByID($iblockId);
    $arIblock = $res->GetNext();
    if (!$arIblock["ID"]) {
        http_response_code(400);
        echo json_encode(["error" => Loc::getMessage("DDAPP_FORM_AJAX_MESSAGE_ERROR_IBLOCK")]);
        die();
    }

    $arResult["NAME"] = $arIblock["NAME"];
    $arResult["DESCRIPTION"] = $arIblock["DESCRIPTION"];
    $arResult["PROPERTIES"] = getIblockProperties($iblockId);
    $arResult["COMPONENT_ID"] = $componentId;
    $arResult["IBLOCK_ID"] = $iblockId;
    $arResult["CAPTCHA_CODE"] = "";
    $arResult["FILE_CONFIG"] = $fileConfig;

    // Генерирование Bitrix Captcha
    if ($params["USE_BITRIX_CAPTCHA"] === "Y") {
        $arResult["CAPTCHA_CODE"] = generateCaptcha();
    }

    // Определяем путь к шаблону модального окна
    $modalTemplatePath = __DIR__ . "/templates/" . $templateName . "/modal.php";

    // Проверяем существование файла шаблона
    if (!file_exists($modalTemplatePath)) {
        $modalTemplatePath = __DIR__ . "/templates/.default/modal.php";
    }

    if (!file_exists($modalTemplatePath)) {
        http_response_code(500);
        echo json_encode(["error" => Loc::getMessage("DDAPP_FORM_AJAX_MESSAGE_ERROR_TEMPLATE")]);
        die();
    }

    // Получаем HTML из шаблона
    ob_start();
    include($modalTemplatePath);
    $modalHtml = ob_get_clean();

    if (empty($modalHtml)) {
        http_response_code(500);
        echo json_encode(["error" => Loc::getMessage("DDAPP_FORM_AJAX_MESSAGE_ERROR_TEMPLATE_HTML")]);
        die();
    }

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["status" => "success", "html" => $modalHtml]);
    die();
}

/**
 * Сохранение формы
 */
if ($action === "save") {
//    $value = trim($request->getPost("value"));
//
//    $response = array(
//        "status" => "error",
//        "message" => ""
//    );
//
//    if (empty($value)) {
//        $response["message"] = "Поле не может быть пустым!";
//    } else {
//        $response["status"] = "success";
//        $response["message"] = "Проверка прошла успешно!";
//    }
//
//    header("Content-Type: application/json; charset=utf-8");
//    echo json_encode($response);
//    die();
    /*
    // Проверяем AJAX-запрос в самом начале
    if ($request->isPost() && $request->getPost("ajax_" . $this->iblockId) === "Y") {
        global $APPLICATION;

        // CSRF защита
        if (!check_bitrix_sessid()) {
            $APPLICATION->RestartBuffer();
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode([
                "success" => false,
                "message" => "Ошибка безопасности. Обновите страницу и попробуйте снова."
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        // Проверка rate limiting
        $rateLimitResult = $this->rateLimiter->checkLimits();
        if (!$rateLimitResult["allowed"]) {
            $APPLICATION->RestartBuffer();
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode([
                "success" => false,
                "message" => $rateLimitResult["message"],
                "retry_after" => $rateLimitResult["retry_after"]
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        // Очищаем буфер
        $APPLICATION->RestartBuffer();

        $result = $this->processForm();

        // Устанавливаем правильный заголовок
        header("Content-Type: application/json; charset=utf-8");

        // Выводим JSON и завершаем
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        die();
    }

     */
}

// Если действие не распознано
http_response_code(400);
echo json_encode(["error" => "Unknown action: " . $action]);
die();


/**
 * Загрузка конфигурации файлов
 * @param $params
 * @return array
 */
function loadFileConfig($params): array
{
    $configPath = __DIR__ . "/config/file_security.php";
    $defaultConfig = file_exists($configPath) ? include($configPath) : [];

    // Переопределяем настройки из параметров компонента
    if (isset($params["MAX_FILE_SIZE"]) && (int)$params["MAX_FILE_SIZE"] > 0) {
        $defaultConfig["max_file_size"] = (int)$params["MAX_FILE_SIZE"] * 1024 * 1024;
    }

    if (!empty($params["ALLOWED_FILE_EXTENSIONS"])) {
        $defaultConfig["allowed_extensions"] = array_map("trim", explode(",", strtolower($params["ALLOWED_FILE_EXTENSIONS"])));
    }

    return $defaultConfig;
}

/**
 * Получает свойства инфоблока
 * @param $iblockId
 * @return array
 */
function getIblockProperties($iblockId): array
{
    $properties = [];
    $res = PropertyTable::getList([
        "filter" => ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"],
        "select" => ["ID", "CODE", "NAME", "PROPERTY_TYPE", "LIST_TYPE", "MULTIPLE", "IS_REQUIRED", "HINT", "USER_TYPE", "ROW_COUNT", "COL_COUNT", "LINK_IBLOCK_ID"],
        "order" => ["SORT" => "ASC"]
    ]);

    while ($property = $res->fetch()) {
        $property["LIST_VALUES"] = [];

        if ($property["PROPERTY_TYPE"] === "L") {
            $property["LIST_VALUES"] = getPropertyListValues($property["ID"]);
        }

        if ($property["PROPERTY_TYPE"] === "E" && !empty($property["LINK_IBLOCK_ID"])) {
            $property["ELEMENT_VALUES"] = getElementValues($property["LINK_IBLOCK_ID"]);
        }

        $properties[] = $property;
    }

    return $properties;
}

/**
 * Получает список свойства инфоблока
 * @param $propertyId
 * @return array
 */
function getPropertyListValues($propertyId): array
{
    $values = [];
    $res = CIBlockPropertyEnum::GetList(
        ["SORT" => "ASC"],
        ["PROPERTY_ID" => $propertyId]
    );
    while ($value = $res->fetch()) {
        $values[] = $value;
    }
    return $values;
}

/**
 * Получает элементы связанного инфоблока
 * @param $iblockId
 * @return array
 */
function getElementValues($iblockId): array
{
    $elements = [];
    $res = CIBlockElement::GetList(
        ["SORT" => "ASC", "NAME" => "ASC"],
        ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"],
        false,
        false,
        ["ID", "NAME"]
    );
    while ($element = $res->fetch()) {
        $elements[] = $element;
    }
    return $elements;
}

/**
 * Генерирование Captcha
 * @return mixed
 */
function generateCaptcha(): mixed
{
    $cpt = new CCaptcha();
    $captchaPass = Option::get("main", "captcha_password");

    // Проверка на пустоту, если пусто генерируем новый код капчи
    if (strlen($captchaPass) <= 0) {
        $captchaPass = randString(10);
        Option::set("main", "captcha_password", $captchaPass);
    }

    $cpt->SetCodeCrypt($captchaPass);
    return $cpt->GetCodeCrypt();
}