<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\FormHelper;

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
//    echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_FORM_AJAX_MESSAGE_ERROR_REQUEST")]);
//    exit;
//}

$action = $request->getPost("action");
$componentId = $request->getPost("id");
$params = $request->getPost("form-params");

if (!$action || !$componentId || !$params["IBLOCK_ID"]) {
    echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_FORM_AJAX_MESSAGE_ERROR_PARAMS")]);
    exit;
}

// Загрузка конфигурации безопасности файлов
$configPath = __DIR__ . "/config/file_security.php";
$fileConfig = file_exists($configPath) ? include($configPath) : [];

// Переопределяем настройки из параметров компонента
if (isset($params["MAX_FILE_SIZE"]) && (int)$params["MAX_FILE_SIZE"] > 0) {
    $fileConfig["max_file_size"] = (int)$params["MAX_FILE_SIZE"] * 1024 * 1024;
}

if (!empty($params["ALLOWED_FILE_EXTENSIONS"])) {
    $fileConfig["allowed_extensions"] = array_map("trim", explode(",", strtolower($params["ALLOWED_FILE_EXTENSIONS"])));
}

$params["CACHE_TIME"] = isset($params["CACHE_TIME"]) ? $params["CACHE_TIME"] : 3600;
$iblockId = (int)$params["IBLOCK_ID"];

/**
 * Загрузка формы
 */
if ($action === "load") {

    $res = CIBlock::GetByID($iblockId);
    $arIblock = $res->GetNext();
    if (!$arIblock["ID"]) {
        echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_FORM_AJAX_MESSAGE_ERROR_IBLOCK")]);
        exit;
    }

    $arParams = $params;
    $arResult["NAME"] = $arIblock["NAME"];
    $arResult["DESCRIPTION"] = $arIblock["DESCRIPTION"];
    $arResult["BUTTON_TEXT"] = $params["BUTTON_TEXT"];
    $arResult["PROPERTIES"] = FormHelper::getIblockProperties($iblockId);
    $arResult["COMPONENT_ID"] = $componentId;
    $arResult["IBLOCK_ID"] = $iblockId;
    $arResult["CAPTCHA_CODE"] = "";
    $arResult["FILE_CONFIG"] = $fileConfig;

    // Генерирование Bitrix Captcha
    if ($params["USE_BITRIX_CAPTCHA"] === "Y") {
        $arResult["CAPTCHA_CODE"] = FormHelper::generateCaptcha();
    }

    // Определяем путь к шаблону модального окна
    $modalTemplatePath = __DIR__ . "/templates/" . $templateName . "/modal.php";

    // Проверяем существование файла шаблона
    if (!file_exists($modalTemplatePath)) {
        $modalTemplatePath = __DIR__ . "/templates/.default/modal.php";
    }

    if (!file_exists($modalTemplatePath)) {
        echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_FORM_AJAX_MESSAGE_ERROR_TEMPLATE")]);
        exit;
    }

    // Получаем HTML из шаблона
    ob_start();
    include($modalTemplatePath);
    $modalHtml = ob_get_clean();

    if (empty($modalHtml)) {
        echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_FORM_AJAX_MESSAGE_ERROR_TEMPLATE_HTML")]);
        exit;
    }

    echo Json::encode(["success" => true, "html" => $modalHtml]);
    exit;
}

/**
 * Сохранение формы
 */
if ($action === "save") {

    // CSRF защита
//    if (!check_bitrix_sessid()) {
//        echo Json::encode(["success" => false, "message" => "Ошибка безопасности. Обновите страницу и попробуйте снова"]);
//        exit;
//    }

//    // Проверка Rate Limiting
//    $rateLimitResult = FormHelper::validateLimits($iblockId, $params["RATE_LIMITS"] ?? []);
//    if (!$rateLimitResult["allowed"]) {
//        echo Json::encode(["success" => false, "message" => $rateLimitResult["message"], "retry_after" => $rateLimitResult["retry_after"]]);
//        exit;
//    }

    // Детальная валидация формы
    $validationResult = validateFormDetailed($request, $params, $fileConfig, $iblockId);

    if (!$validationResult["isValid"]) {
        LogHelper::warning("form_" . $iblockId, "Form validation failed", ["errors" => $validationResult["errors"], "fieldErrors" => $validationResult["fieldErrors"], "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"]);
        echo Json::encode(["success" => false, "message" => implode("<br>", $validationResult["errors"]), "fieldErrors" => $validationResult["fieldErrors"]]);
        exit;
    }

//    // Валидация капчи
//    if (!FormHelper::validateCaptcha($request, $params)) {
//        echo Json::encode(["success" => false, "message" => "Неверный код капчи"]);
//        exit;
//    }
//
//    // Валидация полей формы
//    $errors = FormHelper::validateForm($request, $params, $fileConfig, $iblockId);
//    if (!empty($errors)) {
//        LogHelper::warning("form_" . $iblockId, "Form validation failed", ["errors" => $errors, "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"]);
//        echo Json::encode(["success" => false, "message" => implode("<br>", $errors)]);
//        exit;
//    }

    // Сохранение элемента инфоблока
    $elementId = FormHelper::saveElement($request, $params, $iblockId);

    if ($elementId) {
        // Отправка письма
        if (!empty($params["EMAIL_TEMPLATE"])) {
            $emailResult = FormHelper::sendEmail($elementId, $request, $iblockId, $params);
        }

        LogHelper::info("form_" . $iblockId, "Form submitted successfully", ["element_id" => $elementId, "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"]);
        echo Json::encode(["success" => true, "message" => "Форма успешно отправлена", "element_id" => $elementId]);
        exit;

    } else {
        LogHelper::error("form_" . $iblockId, "Form save failed", ["ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"]);
        echo Json::encode(["success" => false, "message" => "Ошибка сохранения данных"]);
        exit;
    }
}

// Если действие не распознано
echo Json::encode(["success" => false, "message" => "Ошибка запроса: " . $action]);
exit;


/**
 * Детальная валидация с возвратом ошибок по полям
 */
function validateFormDetailed($request, $params, $fileConfig, $iblockId)
{
    $errors = [];
    $fieldErrors = [];

    // Получаем свойства инфоблока
    $properties = FormHelper::getIblockProperties($iblockId);

    foreach ($properties as $property) {
        $fieldName = "property_" . $property["ID"];
        $value = $request->getPost($fieldName);

        // Проверка обязательных полей
        if ($property["IS_REQUIRED"] === "Y") {
            if ($property["PROPERTY_TYPE"] === "F") {
                // Для файлов проверяем $_FILES
                if (empty($_FILES[$fieldName]["name"]) ||
                    (is_array($_FILES[$fieldName]["name"]) && empty(array_filter($_FILES[$fieldName]["name"])))) {
                    $fieldErrors[$fieldName] = "Обязательно для заполнения";
                    $errors[] = "Поле \"{$property["NAME"]}\" обязательно для заполнения";
                }
            } elseif ($property["PROPERTY_TYPE"] === "L" && $property["LIST_TYPE"] === "C") {
                // Для чекбоксов проверяем массив
                if (empty($value) || !is_array($value)) {
                    $fieldErrors[$fieldName] = "Выберите хотя бы один вариант";
                    $errors[] = "Поле \"{$property["NAME"]}\" обязательно для заполнения";
                }
            } else {
                // Для остальных полей
                if (empty($value) || (is_array($value) && empty(array_filter($value)))) {
                    $fieldErrors[$fieldName] = "Обязательно для заполнения";
                    $errors[] = "Поле \"{$property["NAME"]}\" обязательно для заполнения";
                }
            }
        }

        // Валидация по типам полей (только если поле заполнено)
        if (!empty($value)) {
            switch ($property["PROPERTY_TYPE"]) {
                case "N": // Числовое поле
                    if (!is_numeric($value)) {
                        $fieldErrors[$fieldName] = "Введите корректное число";
                        $errors[] = "Поле \"{$property["NAME"]}\" должно содержать число";
                    }
                    break;

                case "S": // Строковое поле
                    // Проверка email по названию или коду свойства
                    if (stripos($property["CODE"], "email") !== false ||
                        stripos($property["NAME"], "email") !== false ||
                        stripos($property["NAME"], "почт") !== false) {
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $fieldErrors[$fieldName] = "Введите корректный email адрес";
                            $errors[] = "Поле \"{$property["NAME"]}\" должно содержать корректный email";
                        }
                    }

                    // Проверка телефона
                    if (stripos($property["CODE"], "phone") !== false ||
                        stripos($property["NAME"], "телефон") !== false ||
                        stripos($property["NAME"], "phone") !== false) {
                        $cleanPhone = preg_replace('/\D/', '', $value);
                        if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 15) {
                            $fieldErrors[$fieldName] = "Введите корректный номер телефона";
                            $errors[] = "Поле \"{$property["NAME"]}\" должно содержать корректный номер телефона";
                        }
                    }
                    break;
            }
        }
    }

    // Проверка капчи
    if ($params["USE_BITRIX_CAPTCHA"] === "Y") {
        $captchaWord = $request->getPost("captcha_word");
        $captchaCode = $request->getPost("captcha_code");

        if (empty($captchaWord)) {
            $fieldErrors["captcha_word"] = "Введите код с картинки";
            $errors[] = "Код капчи обязателен для заполнения";
        } elseif (!$GLOBALS["APPLICATION"]->CaptchaCheckCode($captchaWord, $captchaCode)) {
            $fieldErrors["captcha_word"] = "Неверный код капчи";
            $errors[] = "Неверный код капчи";
        }
    }

    // Проверка согласия с политикой
    if ($params["USE_PRIVACY_POLICY"] === "Y") {
        $privacyAgreement = $request->getPost("privacy_policy_agreement");
        if ($privacyAgreement !== "Y") {
            $fieldErrors["privacy_policy_agreement"] = "Необходимо согласие с политикой";
            $errors[] = "Необходимо согласиться с политикой обработки персональных данных";
        }
    }

    // Валидация файлов
    if (!empty($_FILES)) {
        foreach ($_FILES as $fieldName => $fileData) {
            if (!empty($fileData["name"]) && !empty(array_filter((array)$fileData["name"]))) {
                $fileErrors = FormHelper::validateFiles($fieldName, $fileConfig);
                if (!empty($fileErrors)) {
                    $fieldErrors[$fieldName] = implode(", ", $fileErrors);
                    $errors = array_merge($errors, $fileErrors);
                }
            }
        }
    }

    return [
        "errors" => $errors,
        "fieldErrors" => $fieldErrors,
        "isValid" => empty($errors)
    ];
}