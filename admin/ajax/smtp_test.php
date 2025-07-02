<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Проверка сессии Bitrix
if (!check_bitrix_sessid()) {
    echo json_encode(["success" => false, "message" => "Ошибка сессии"]);
    exit;
}

$mailer = new \DD\Tools\CustomMail();

$result = $mailer->testConnection();

echo json_encode($result);