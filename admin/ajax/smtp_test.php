<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Localization\Loc;
use DD\Tools\CustomMail;
use DD\Tools\Helpers\UserHelper;

Loc::loadMessages(__FILE__);

// Проверка сессии Bitrix
if (!check_bitrix_sessid()) {
    echo json_encode(["success" => false, "message" => Loc::getMessage("ACCESS_DENIED")]);
    exit;
}

// Проверка доступа
if (UserHelper::hasModuleAccess("") != "W") {
    echo json_encode(["success" => false, "message" => Loc::getMessage("ACCESS_DENIED")]);
    exit;
}

try {
    $mailer = new CustomMail();

    $result = $mailer->testConnection();

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}