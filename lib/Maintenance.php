<?php

namespace DD\Tools;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use DD\Tools\Entity\MaintenanceTable;

class Maintenance
{
    /**
     * @return array
     */
    public static function getMaintenanceItems()
    {
        $items = MaintenanceTable::getList(array(
            "select" => array("ID", "NAME", "LINK", "TYPE"),
            "filter" => array("ACTIVE" => "Y"),
            "order" => array("PRIORITY" => "ASC")
        ))->fetchAll();

        $completedItems = self::getCompletedItems();

        foreach ($items as &$item) {
            $item["COMPLETED"] = in_array($item["ID"], $completedItems);
        }

        return $items;
    }

    /**
     * @return array|string[]
     */
    public static function getCompletedItems()
    {
        $completed = Option::get("dd.tools", "maint_completed_items", "");
        return $completed ? explode(",", $completed) : array();
    }

    /**
     * @param $items
     * @return bool
     */
    public static function checkIfAllCompleted($items)
    {
        foreach ($items as $item) {
            if (!$item["COMPLETED"]) {
                return false;
            }
        }
        return count($items) > 0;
    }

    /**
     * @return bool
     */
    public static function checkLastCompletionDate()
    {
        $lastDate = Option::get("dd.tools", "maint_last_date", "");
        if ($lastDate) {
            $lastDateTime = DateTime::createFromUserTime($lastDate);
            $now = new DateTime();
            $diff = $now->getTimestamp() - $lastDateTime->getTimestamp();

            $days = Option::get("dd.tools", "maint_period", 30);

            // Если прошло время (30 дней по умолчанию)
            if ($diff > $days * 24 * 60 * 60) {
                Option::delete("dd.tools", array("name" => "maint_last_date"));
                Option::delete("dd.tools", array("name" => "maint_completed_items"));
                return true; // Данные были сброшены
            }
        }
        return false;
    }
}