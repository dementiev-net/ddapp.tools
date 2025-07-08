<?php

namespace DDAPP\Tools;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use DDAPP\Tools\Entity\DataImportTable;

class DataImport
{
    /**
     * Берет по ID
     * @param $id
     * @return mixed
     */
    public static function getById($id): mixed
    {
        $result = DataImportTable::getById($id);

        return $result->fetch();
    }

    /**
     * Берет записи по условию
     * @param $request
     * @return mixed
     */
    public static function getItems($request): mixed
    {
        return DataImportTable::getList($request)->fetchAll();
    }

    /**
     * Удаление
     * @param $id
     * @return mixed
     */
    public static function delete($id): mixed
    {
        return DataImportTable::delete($id);
    }

    /**
     * Добавление
     * @param $request
     * @return mixed
     */
    public static function add($request): mixed
    {
        return DataImportTable::add($request);
    }

    /**
     * Обновление
     * @param $id
     * @param $request
     * @return mixed
     */
    public static function update($id, $request): mixed
    {
        return DataImportTable::update($id, $request);
    }
}