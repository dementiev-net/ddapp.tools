<?php

namespace DDAPP\Tools;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use DDAPP\Tools\Main;
use DDAPP\Tools\Entity\MaintenanceTable;

class Maintenance
{
    /**
     * Берет по ID
     * @param $id
     * @return mixed
     */
    public static function getById($id): mixed
    {
        $result = MaintenanceTable::getById($id);

        return $result->fetch();
    }

    /**
     * Берет записи по условию
     * @param $request
     * @return mixed
     */
    public static function getItems($request): mixed
    {
        return MaintenanceTable::getList($request);
    }

    /**
     * Берет все активные записи
     * @return array
     */
    public static function getAllItems(): array
    {
        $items = MaintenanceTable::getList([
            "select" => ["ID", "NAME", "LINK", "TYPE"],
            "filter" => ["ACTIVE" => "Y"],
            "order" => ["PRIORITY" => "ASC"]
        ])->fetchAll();

        $completedItems = self::getCompletedItems();

        foreach ($items as &$item) {
            $item["COMPLETED"] = in_array($item["ID"], $completedItems);
        }

        return $items;
    }

    /**
     * Берет завершенные записи
     * @return array|string[]
     */
    public static function getCompletedItems(): array
    {
        $completed = Option::get(Main::MODULE_ID, "maint_completed_items");
        return $completed ? explode(",", $completed) : [];
    }

    /**
     * Проверка на завершение
     * @param $items
     * @return bool
     */
    public static function checkIfAllCompleted($request): bool
    {
        foreach ($request as $item) {
            if (!$item["COMPLETED"]) {
                return false;
            }
        }
        return count($request) > 0;
    }

    /**
     * Проверка на окончание завершения
     * @return bool
     */
    public static function checkLastCompletionDate(): bool
    {
        $lastDate = Option::get(Main::MODULE_ID, "maint_last_date");
        if ($lastDate) {
            $lastDateTime = DateTime::createFromUserTime($lastDate);
            $now = new DateTime();
            $diff = $now->getTimestamp() - $lastDateTime->getTimestamp();

            $days = Option::get(Main::MODULE_ID, "maint_period");

            // Если прошло время (30 дней по умолчанию)
            if ($diff > $days * 24 * 60 * 60) {
                Option::delete(Main::MODULE_ID, ["name" => "maint_last_date"]);
                Option::delete(Main::MODULE_ID, ["name" => "maint_completed_items"]);
                return true; // Данные были сброшены
            }
        }
        return false;
    }

    /**
     * Деактивация
     * @param $id
     * @return mixed
     */
    public static function deactivate($id): mixed
    {
        return MaintenanceTable::update($id, ["ACTIVE" => "N"]);
    }

    /**
     * Активация
     * @param $id
     * @return mixed
     */
    public static function activate($id): mixed
    {
        return MaintenanceTable::update($id, ["ACTIVE" => "Y"]);
    }

    /**
     * Удаление
     * @param $id
     * @return mixed
     */
    public static function delete($id): mixed
    {
        return MaintenanceTable::delete($id);
    }

    /**
     * Добавление
     * @param $request
     * @return mixed
     */
    public static function add($request): mixed
    {
        return MaintenanceTable::add($request);
    }

    /**
     * Обновление
     * @param $id
     * @param $request
     * @return mixed
     */
    public static function update($id, $request): mixed
    {
        return MaintenanceTable::update($id, $request);
    }
}