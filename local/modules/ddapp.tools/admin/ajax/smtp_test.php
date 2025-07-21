<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Localization\Loc;
use DDAPP\Tools\CustomMail;
use Bitrix\Main\Web\Json;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\UserHelper;

Loc::loadMessages(__FILE__);

// Настройка логирования
LogHelper::configure();

header("Content-Type: application/json; charset=utf-8");

// Проверка сессии Bitrix
if (!check_bitrix_sessid()) {
    echo Json::encode(["success" => false, "message" => Loc::getMessage("ACCESS_DENIED")]);
    exit;
}

// Проверка доступа
if (UserHelper::hasModuleAccess("") != "W") {
    echo Json::encode(["success" => false, "message" => Loc::getMessage("ACCESS_DENIED")]);
    exit;
}

try {
    $mailer = new CustomMail();
    $result = $mailer->testConnection();

    if ($result["success"]) {
        LogHelper::info("smtp", "SMTP Test", $result["debug"]);
    } else {
        LogHelper::error("smtp", "SMTP Test Error", $result["debug"]);
    }

    echo Json::encode($result);
} catch (Exception $e) {
    LogHelper::error("smtp", "SMTP Test Error", $e->getMessage());
    echo Json::encode(["success" => false, "message" => $e->getMessage()]);
}