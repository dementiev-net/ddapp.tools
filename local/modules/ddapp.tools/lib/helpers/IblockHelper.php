<?php

namespace DDAPP\Tools\Helpers;

use Bitrix\Main\Localization\Loc;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;

Loc::loadMessages(__FILE__);

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
    public static function getAllProperties($request): mixed
    {
        return PropertyTable::getList($request)->fetchAll();
    }

    /**
     * Стандартные имена полей инфоблока
     * @param $type
     * @return array
     */
    public static function getDefaultFieldsNames($type): array
    {
        if ($type == "F") {
            return [
                "PREVIEW_PICTURE" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_PREVIEW_PICTURE"),
                "DETAIL_PICTURE" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_DETAIL_PICTURE"),
            ];
        }

        return [
            "ID" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_ID"),
            "NAME" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_NAME"),
            "CODE" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_CODE"),
            "ACTIVE" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_ACTIVE"),
            "SORT" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_SORT"),
            "PREVIEW_TEXT" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_PREVIEW_TEXT"),
            "PREVIEW_PICTURE" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_PREVIEW_PICTURE"),
            "DETAIL_TEXT" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_DETAIL_TEXT"),
            "DETAIL_PICTURE" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_DETAIL_PICTURE"),
            "DATE_CREATE" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_DATE_CREATE"),
            "CREATED_BY" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_CREATED_BY"),
            "TIMESTAMP_X" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_TIMESTAMP_X"),
            "MODIFIED_BY" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_MODIFIED_BY"),
            "ACTIVE_FROM" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_ACTIVE_FROM"),
            "ACTIVE_TO" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_ACTIVE_TO"),
            "TAGS" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_TAGS"),
            "XML_ID" => Loc::getMessage("DDAPP_HELPER_DEFAULT_FIELD_XML_ID"),
        ];
    }
}