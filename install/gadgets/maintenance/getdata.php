<?php
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use DD\Tools\Entity\MaintenanceTable;

if (!check_bitrix_sessid() || $_SERVER["REQUEST_METHOD"] != "POST") {
    die(json_encode(array("success" => false, "error" => "Invalid request")));
}

$action = $_POST["action"] ?? "";
$response = array("success" => false);

if ($action === "toggle_item") {

    $itemId = intval($_POST["item_id"] ?? 0);
    $checked = intval($_POST["checked"] ?? 0);

    if ($itemId > 0) {
        $completedItems = Option::get("dd.tools", "maint_completed_items", "");
        $completedArray = $completedItems ? explode(",", $completedItems) : array();

        if ($checked) {
            if (!in_array($itemId, $completedArray)) {
                $completedArray[] = $itemId;
            }
        } else {
            $completedArray = array_diff($completedArray, array($itemId));
        }

        Option::set("dd.tools", "maint_completed_items", implode(",", $completedArray));

        // Проверяем, все ли элементы выполнены
        $allItems = MaintenanceTable::getList(array(
            "select" => array("ID"),
            "filter" => array("ACTIVE" => "Y")
        ))->fetchAll();

        $allItemIds = array_column($allItems, "ID");
        $allCompleted = !array_diff($allItemIds, $completedArray);

        if ($allCompleted && count($allItemIds) > 0) {
            $now = new DateTime();
            Option::set("dd.tools", "maint_last_date", $now->toString());
            $response["completion_date"] = FormatDate("d.m.Y H:i", $now->getTimestamp());
        } else {
            // Если не все выполнено, удаляем дату завершения
            Option::delete("dd.tools", array("name" => "maint_last_date"));
        }

        $response["success"] = true;
    }
}

header("Content-Type: application/json");
echo json_encode($response);