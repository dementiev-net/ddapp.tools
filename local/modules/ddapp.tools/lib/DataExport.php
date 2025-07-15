<?php

namespace DDAPP\Tools;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use DDAPP\Tools\Entity\DataExportTable;

class DataExport
{
    /**
     * Берет по ID
     * @param $id
     * @return mixed
     */
    public static function getById($id): mixed
    {
        $result = DataExportTable::getById($id);

        return $result->fetch();
    }

    /**
     * Берет записи по условию
     * @param $request
     * @return mixed
     */
    public static function getItems($request): mixed
    {
        return DataExportTable::getList($request)->fetchAll();
    }

    /**
     * Удаление
     * @param $id
     * @return mixed
     */
    public static function delete($id): mixed
    {
        return DataExportTable::delete($id);
    }

    /**
     * Добавление
     * @param $request
     * @return mixed
     */
    public static function add($request): mixed
    {
        return DataExportTable::add($request);
    }

    /**
     * Обновление
     * @param $id
     * @param $request
     * @return mixed
     */
    public static function update($id, $request): mixed
    {
        return DataExportTable::update($id, $request);
    }
}