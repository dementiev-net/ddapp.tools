<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use DD\Tools\Helpers\ValidationHelper;
use DD\Tools\Helpers\LogHelper;

if (!Loader::includeModule("dd.tools")) {
    http_response_code(500);
    echo json_encode(["error" => "Модуль DD Tools не подключен"]);
    exit;
}

// Настройка логирования
LogHelper::configure();

$request = Application::getInstance()->getContext()->getRequest();
$data = $request->get("data");

$response = [
    "success" => true,
    "validations" => []
];

try {
    // Валидация email
    if (!empty($data["email"])) {
        $response["validations"]["email"] = ValidationHelper::isValidEmail($data["email"]);
        if (!$response["validations"]["email"]) {
            $response["success"] = false;
        }
    }

    // Валидация телефона
    if (!empty($data["phone"])) {
        $response["validations"]["phone"] = ValidationHelper::isValidPhone($data["phone"]);
        if (!$response["validations"]["phone"]) {
            $response["success"] = false;
        }
    }

    LogHelper::info("ajax", "Validation completed: " . json_encode($response["validations"]));

} catch (Exception $e) {
    LogHelper::error("ajax", "Validation error: " . $e->getMessage());
    $response = [
        "success" => false,
        "error" => "Ошибка сервера"
    ];
}

header("Content-Type: application/json");
echo json_encode($response);