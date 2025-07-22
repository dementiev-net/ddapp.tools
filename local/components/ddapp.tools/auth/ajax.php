<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\AuthHelper;

Loc::loadMessages(__FILE__);
LogHelper::configure();

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die("Access denied");
}

$request = Application::getInstance()->getContext()->getRequest();

header("Content-Type: application/json; charset=utf-8");

$action = $request->getPost("action");
$componentId = $request->getPost("id");
$params = $request->getPost("auth-params");

if (!$action || !$componentId || !$params) {
    echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_AUTH_AJAX_MESSAGE_ERROR_PARAMS")]);
    exit;
}

/**
 * Загрузка формы авторизации
 */
if ($action === "load_login") {
    $arParams = $params;
    $arResult["COMPONENT_ID"] = $componentId;
    $arResult["FORM_TYPE"] = "login";
    $arResult["USE_CAPTCHA"] = $params["USE_CAPTCHA"] === "Y";

    // Генерируем CAPTCHA если нужно
    if ($arResult["USE_CAPTCHA"]) {
        $arResult["CAPTCHA_CODE"] = AuthHelper::generateCaptcha();
    }

    // Определяем путь к шаблону
    $modalTemplatePath = __DIR__ . "/templates/.default/modals/login.php";

    if (!file_exists($modalTemplatePath)) {
        echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_AUTH_AJAX_MESSAGE_ERROR_TEMPLATE")]);
        exit;
    }

    // Получаем HTML из шаблона
    ob_start();
    include($modalTemplatePath);
    $modalHtml = ob_get_clean();

    if (empty($modalHtml)) {
        echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_AUTH_AJAX_MESSAGE_ERROR_TEMPLATE_HTML")]);
        exit;
    }

    echo Json::encode(["success" => true, "html" => $modalHtml]);
    exit;
}

/**
 * Загрузка формы регистрации
 */
if ($action === "load_register") {
    $arParams = $params;
    $arResult["COMPONENT_ID"] = $componentId;
    $arResult["FORM_TYPE"] = "register";
    $arResult["USE_CAPTCHA"] = $params["USE_CAPTCHA_REGISTRATION"] === "Y";
    $arResult["REGISTRATION_FIELDS"] = $params["REGISTRATION_FIELDS"] ?? [];
    $arResult["REQUIRED_FIELDS"] = $params["REQUIRED_FIELDS"] ?? [];

    // Генерируем CAPTCHA если нужно
    if ($arResult["USE_CAPTCHA"]) {
        $arResult["CAPTCHA_CODE"] = AuthHelper::generateCaptcha();
    }

    // Определяем путь к шаблону
    $modalTemplatePath = __DIR__ . "/templates/.default/modals/register.php";

    if (!file_exists($modalTemplatePath)) {
        echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_AUTH_AJAX_MESSAGE_ERROR_TEMPLATE")]);
        exit;
    }

    // Получаем HTML из шаблона
    ob_start();
    include($modalTemplatePath);
    $modalHtml = ob_get_clean();

    if (empty($modalHtml)) {
        echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_AUTH_AJAX_MESSAGE_ERROR_TEMPLATE_HTML")]);
        exit;
    }

    echo Json::encode(["success" => true, "html" => $modalHtml]);
    exit;
}

/**
 * Загрузка формы восстановления пароля
 */
if ($action === "load_forgot") {
    $arParams = $params;
    $arResult["COMPONENT_ID"] = $componentId;
    $arResult["FORM_TYPE"] = "forgot";
    $arResult["USE_CAPTCHA"] = $params["USE_CAPTCHA"] === "Y";

    // Генерируем CAPTCHA если нужно
    if ($arResult["USE_CAPTCHA"]) {
        $arResult["CAPTCHA_CODE"] = AuthHelper::generateCaptcha();
    }

    // Определяем путь к шаблону
    $modalTemplatePath = __DIR__ . "/templates/.default/modals/forgot.php";

    if (!file_exists($modalTemplatePath)) {
        echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_AUTH_AJAX_MESSAGE_ERROR_TEMPLATE")]);
        exit;
    }

    // Получаем HTML из шаблона
    ob_start();
    include($modalTemplatePath);
    $modalHtml = ob_get_clean();

    if (empty($modalHtml)) {
        echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_AUTH_AJAX_MESSAGE_ERROR_TEMPLATE_HTML")]);
        exit;
    }

    echo Json::encode(["success" => true, "html" => $modalHtml]);
    exit;
}

/**
 * Обработка авторизации
 */
if ($action === "auth") {
    // CSRF защита
    if ($params["USE_CSRF_TOKEN"] === "Y" && !check_bitrix_sessid()) {
        echo Json::encode(["success" => false, "message" => "Ошибка безопасности. Обновите страницу и попробуйте снова"]);
        exit;
    }

    // Проверка лимитов
    $rateLimitResult = AuthHelper::validateLimits("login");
    if (!$rateLimitResult["allowed"]) {
        echo Json::encode(["success" => false, "message" => $rateLimitResult["message"], "retry_after" => $rateLimitResult["retry_after"]]);
        exit;
    }

    // Валидация формы с детальными ошибками
    $validationResult = validateFormDetailed($request, $params, "auth");

    if (!$validationResult["isValid"]) {
        AuthHelper::logUserAction($type . "_failed", null, ["errors" => $validationResult["errors"], "fieldErrors" => $validationResult["fieldErrors"]]);
        echo Json::encode([
            "success" => false,
            "message" => implode("<br>", $validationResult["errors"]),
            "fieldErrors" => $validationResult["fieldErrors"]
        ]);
        exit;
    }

//    // Валидация формы
//    $errors = AuthHelper::validateLoginForm($request, $params);
//    if (!empty($errors)) {
//        AuthHelper::logUserAction("login_failed", null, ["errors" => $errors, "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"]);
//        echo Json::encode(["success" => false, "message" => implode("<br>", $errors)]);
//        exit;
//    }

    // Авторизация
    $login = trim($request->getPost("USER_LOGIN"));
    $password = $request->getPost("USER_PASSWORD");
    $remember = $request->getPost("USER_REMEMBER") === "Y";

    $authResult = AuthHelper::authenticateUser($login, $password, $remember);

    if ($authResult['success']) {
        AuthHelper::logUserAction("login_success", $authResult['user_id']);
        $redirectUrl = !empty($params["AUTH_SUCCESS_PAGE"]) ? $params["AUTH_SUCCESS_PAGE"] : "";
        echo Json::encode([
            "success" => true,
            "message" => "Авторизация прошла успешно",
            "redirect" => $redirectUrl
        ]);
    } else {
        AuthHelper::logUserAction("login_failed", null, ["error" => $authResult['error']]);
        echo Json::encode(["success" => false, "message" => $authResult['error']]);
    }
    exit;
}

/**
 * Обработка регистрации
 */
if ($action === "register") {
    // CSRF защита
    if ($params["USE_CSRF_TOKEN"] === "Y" && !check_bitrix_sessid()) {
        echo Json::encode(["success" => false, "message" => "Ошибка безопасности. Обновите страницу и попробуйте снова"]);
        exit;
    }

    // Проверка лимитов
    $rateLimitResult = AuthHelper::validateLimits("register");
    if (!$rateLimitResult["allowed"]) {
        echo Json::encode(["success" => false, "message" => $rateLimitResult["message"], "retry_after" => $rateLimitResult["retry_after"]]);
        exit;
    }

    // Валидация формы с детальными ошибками
    $validationResult = validateFormDetailed($request, $params, "register");

    if (!$validationResult["isValid"]) {
        AuthHelper::logUserAction($type . "_failed", null, ["errors" => $validationResult["errors"], "fieldErrors" => $validationResult["fieldErrors"]]);
        echo Json::encode([
            "success" => false,
            "message" => implode("<br>", $validationResult["errors"]),
            "fieldErrors" => $validationResult["fieldErrors"]
        ]);
        exit;
    }

//    // Валидация формы
//    $errors = AuthHelper::validateRegistrationForm($request, $params);
//    if (!empty($errors)) {
//        AuthHelper::logUserAction("register_failed", null, ["errors" => $errors]);
//        echo Json::encode(["success" => false, "message" => implode("<br>", $errors)]);
//        exit;
//    }

    // Подготавливаем данные пользователя
    $userData = [];
    foreach ($request->getPostList()->toArray() as $key => $value) {
        if (strpos($key, "USER_") === 0) {
            $userData[$key] = $value;
        }
    }

    // Регистрация
    $registerResult = AuthHelper::registerUser($userData, $params);

    if ($registerResult['success']) {
        AuthHelper::logUserAction("register_success", $registerResult['user_id']);
        $redirectUrl = !empty($params["REGISTER_SUCCESS_PAGE"]) ? $params["REGISTER_SUCCESS_PAGE"] : "";
        echo Json::encode([
            "success" => true,
            "message" => "Регистрация прошла успешно",
            "redirect" => $redirectUrl
        ]);
    } else {
        AuthHelper::logUserAction("register_failed", null, ["error" => $registerResult['error']]);
        echo Json::encode(["success" => false, "message" => $registerResult['error']]);
    }
    exit;
}

/**
 * Обработка восстановления пароля
 */
if ($action === "forgot") {
    // CSRF защита
    if ($params["USE_CSRF_TOKEN"] === "Y" && !check_bitrix_sessid()) {
        echo Json::encode(["success" => false, "message" => "Ошибка безопасности. Обновите страницу и попробуйте снова"]);
        exit;
    }

    // Проверка лимитов
    $rateLimitResult = AuthHelper::validateLimits("forgot");
    if (!$rateLimitResult["allowed"]) {
        echo Json::encode(["success" => false, "message" => $rateLimitResult["message"], "retry_after" => $rateLimitResult["retry_after"]]);
        exit;
    }

    // Валидация формы с детальными ошибками
    $validationResult = validateFormDetailed($request, $params, "forgot");

    if (!$validationResult["isValid"]) {
        AuthHelper::logUserAction($type . "_failed", null, ["errors" => $validationResult["errors"], "fieldErrors" => $validationResult["fieldErrors"]]);
        echo Json::encode([
            "success" => false,
            "message" => implode("<br>", $validationResult["errors"]),
            "fieldErrors" => $validationResult["fieldErrors"]
        ]);
        exit;
    }

    // Валидация формы
//    $errors = AuthHelper::validateForgotForm($request, $params);
//    if (!empty($errors)) {
//        AuthHelper::logUserAction("forgot_failed", null, ["errors" => $errors]);
//        echo Json::encode(["success" => false, "message" => implode("<br>", $errors)]);
//        exit;
//    }

    $login = trim($request->getPost("USER_LOGIN"));
    $forgotResult = AuthHelper::forgotPassword($login);

    if ($forgotResult['success']) {
        AuthHelper::logUserAction("forgot_success", null, ["login" => $login]);
        echo Json::encode([
            "success" => true,
            "message" => $forgotResult['message']
        ]);
    } else {
        AuthHelper::logUserAction("forgot_failed", null, ["error" => $forgotResult['error'], "login" => $login]);
        echo Json::encode(["success" => false, "message" => $forgotResult['error']]);
    }
    exit;
}

/**
 * Выход из системы
 */
if ($action === "logout") {
    global $USER;

    $userId = $USER->GetID();
    $USER->Logout();

    AuthHelper::logUserAction("logout", $userId);

    echo Json::encode([
        "success" => true,
        "message" => "Вы успешно вышли из системы",
        "redirect" => !empty($params["SUCCESS_PAGE"]) ? $params["SUCCESS_PAGE"] : "/"
    ]);
    exit;
}

// Если действие не распознано
echo Json::encode(["success" => false, "message" => "Неизвестное действие: " . $action]);
exit;

/**
 * Детальная валидация с возвратом ошибок по полям
 */
function validateFormDetailed($request, $params, $type)
{
    $errors = [];
    $fieldErrors = [];

    // Валидация в зависимости от типа формы
    switch ($type) {
        case 'auth':
            $login = $request->getPost("USER_LOGIN");
            $password = $request->getPost("USER_PASSWORD");

            if (empty($login)) {
                $fieldErrors["USER_LOGIN"] = "Обязательно для заполнения";
                $errors[] = "Логин обязателен для заполнения";
            }

            if (empty($password)) {
                $fieldErrors["USER_PASSWORD"] = "Обязательно для заполнения";
                $errors[] = "Пароль обязателен для заполнения";
            }
            break;

        case 'register':
            // Добавить валидацию полей регистрации
            break;

        case 'forgot':
            $login = $request->getPost("USER_LOGIN");
            if (empty($login)) {
                $fieldErrors["USER_LOGIN"] = "Обязательно для заполнения";
                $errors[] = "Email или логин обязателен для заполнения";
            }
            break;
    }

    // Проверка капчи
    if ($params["USE_CAPTCHA"] === "Y" || ($type === 'register' && $params["USE_CAPTCHA_REGISTRATION"] === "Y")) {
        $captchaWord = $request->getPost("captcha_word");
        if (empty($captchaWord)) {
            $fieldErrors["captcha_word"] = "Введите код с картинки";
            $errors[] = "Код капчи обязателен для заполнения";
        }
    }

    return [
        "errors" => $errors,
        "fieldErrors" => $fieldErrors,
        "isValid" => empty($errors)
    ];
}