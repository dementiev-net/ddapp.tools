<?php

namespace DD\Tools\Helpers;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;

class IblockHelper
{
    /**
     * Берет все типы инфоблоков
     * @return array
     */
    public static function getAllBlockType(): array
    {
        $types = [];

        $res = \CIBlockType::GetList();
        while ($type = $res->Fetch()) {
            $lang = \CIBlockType::GetByIDLang($type["ID"], LANG);
            if ($lang) {
                $types[] = [
                    "ID" => $type["ID"],
                    "NAME" => $lang["NAME"]
                ];
            }
        }

        return $types;
    }

    /**
     * Берет инфоблоки по условию
     * @param $request
     * @return mixed
     */
    public static function getBlocks($request): mixed
    {
        return IblockTable::getList($request)->fetchAll();
    }

    /**
     * Берет свойства инфоблока
     * @param $request
     * @return mixed
     */
    public static function getProperties($request): mixed
    {
        return PropertyTable::getList($request)->fetchAll();
    }

    /**
     * Стандартные имена полей инфоблока
     * @return string[]
     */
    public static function getDefaultFieldsNames(): array
    {
        return [
            "ID" => "ID",
            "NAME" => "Название",
            "CODE" => "Символьный код",
            "ACTIVE" => "Активность",
            "SORT" => "Сортировка",
            "PREVIEW_TEXT" => "Описание для анонса",
            "PREVIEW_PICTURE" => "Картинка для анонса",
            "DETAIL_TEXT" => "Детальное описание",
            "DETAIL_PICTURE" => "Детальная картинка",
            "DATE_CREATE" => "Дата создания",
            "CREATED_BY" => "Кем создан",
            "TIMESTAMP_X" => "Дата изменения",
            "MODIFIED_BY" => "Кем изменен",
            "ACTIVE_FROM" => "Начало активности",
            "ACTIVE_TO" => "Окончание активности",
            "TAGS" => "Теги",
            "XML_ID" => "Внешний код"
        ];
    }
}