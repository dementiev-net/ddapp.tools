<?php

namespace DDAPP\Tools;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use DDAPP\Tools\Entity\DataImagesTable;

class DataImages
{
    /**
     * Берет по ID
     * @param $id
     * @return mixed
     */
    public static function getById($id): mixed
    {
        $result = DataImagesTable::getById($id);

        return $result->fetch();
    }

    /**
     * Берет записи по условию
     * @param $request
     * @return mixed
     */
    public static function getItems($request): mixed
    {
        return DataImagesTable::getList($request)->fetchAll();
    }

    /**
     * Удаление
     * @param $id
     * @return mixed
     */
    public static function delete($id): mixed
    {
        return DataImagesTable::delete($id);
    }

    /**
     * Добавление
     * @param $request
     * @return mixed
     */
    public static function add($request): mixed
    {
        return DataImagesTable::add($request);
    }

    /**
     * Обновление
     * @param $id
     * @param $request
     * @return mixed
     */
    public static function update($id, $request): mixed
    {
        return DataImagesTable::update($id, $request);
    }
}