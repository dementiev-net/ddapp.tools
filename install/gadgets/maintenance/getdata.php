<?php
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use DDAPP\Tools\Main;
use DDAPP\Tools\Entity\MaintenanceTable;

if (!check_bitrix_sessid() || $_SERVER["REQUEST_METHOD"] != "POST") {
    die(json_encode(["success" => false, "error" => "Invalid request"]));
}

$action = $_POST["action"] ?? "";
$response = ["success" => false];

if ($action === "toggle_item") {

    $itemId = intval($_POST["item_id"] ?? 0);
    $checked = intval($_POST["checked"] ?? 0);

    if ($itemId > 0) {
        $completedItems = Option::get(Main::MODULE_ID, "maint_completed_items");
        $completedArray = $completedItems ? explode(",", $completedItems) : [];

        if ($checked) {
            if (!in_array($itemId, $completedArray)) {
                $completedArray[] = $itemId;
            }
        } else {
            $completedArray = array_diff($completedArray, [$itemId]);
        }

        Option::set(Main::MODULE_ID, "maint_completed_items", implode(",", $completedArray));

        // Проверяем, все ли элементы выполнены
        $allItems = MaintenanceTable::getList([
            "select" => ["ID"],
            "filter" => ["ACTIVE" => "Y"]
        ])->fetchAll();

        $allItemIds = array_column($allItems, "ID");
        $allCompleted = !array_diff($allItemIds, $completedArray);

        if ($allCompleted && count($allItemIds) > 0) {
            $now = new DateTime();
            Option::set(Main::MODULE_ID, "maint_last_date", $now->toString());
            $response["completion_date"] = FormatDate("d.m.Y H:i", $now->getTimestamp());
        } else {
            // Если не все выполнено, удаляем дату завершения
            Option::delete(Main::MODULE_ID, ["name" => "maint_last_date"]);
        }

        $response["success"] = true;
    }
}

header("Content-Type: application/json");
echo json_encode($response);