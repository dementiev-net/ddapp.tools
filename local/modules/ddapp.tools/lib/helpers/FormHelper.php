<?php

namespace DDAPP\Tools\Helpers;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;

Loc::loadMessages(__FILE__);

class FormHelper
{
    /**
     * Получает свойства инфоблока
     * @param $iblockId
     * @return array
     */
    public static function getIblockProperties($iblockId): array
    {
        $properties = [];
        $res = PropertyTable::getList([
            "filter" => ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"],
            "select" => ["ID", "CODE", "NAME", "PROPERTY_TYPE", "LIST_TYPE", "MULTIPLE", "IS_REQUIRED", "HINT", "USER_TYPE", "ROW_COUNT", "COL_COUNT", "LINK_IBLOCK_ID"],
            "order" => ["SORT" => "ASC"]
        ]);

        while ($property = $res->fetch()) {
            $property["LIST_VALUES"] = [];

            if ($property["PROPERTY_TYPE"] === "L") {
                $property["LIST_VALUES"] = self::getPropertyListValues($property["ID"]);
            }

            if ($property["PROPERTY_TYPE"] === "E" && !empty($property["LINK_IBLOCK_ID"])) {
                $property["ELEMENT_VALUES"] = self::getElementValues($property["LINK_IBLOCK_ID"]);
            }

            $properties[] = $property;
        }

        return $properties;
    }

    /**
     * Получает список свойства инфоблока
     * @param $propertyId
     * @return array
     */
    public static function getPropertyListValues($propertyId): array
    {
        $values = [];
        $res = \CIBlockPropertyEnum::GetList(
            ["SORT" => "ASC"],
            ["PROPERTY_ID" => $propertyId]
        );
        while ($value = $res->fetch()) {
            $values[] = $value;
        }
        return $values;
    }

    /**
     * Получает элементы связанного инфоблока
     * @param $iblockId
     * @return array
     */
    public static function getElementValues($iblockId): array
    {
        $elements = [];
        $res = \CIBlockElement::GetList(
            ["SORT" => "ASC", "NAME" => "ASC"],
            ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"],
            false,
            false,
            ["ID", "NAME"]
        );
        while ($element = $res->fetch()) {
            $elements[] = $element;
        }
        return $elements;
    }

    /**
     * Генерирование Captcha
     * @return mixed
     */
    public static function generateCaptcha(): mixed
    {
        $cpt = new \CCaptcha();
        $captchaPass = Option::get("main", "captcha_password");

        // Проверка на пустоту, если пусто генерируем новый код капчи
        if (strlen($captchaPass) <= 0) {
            $captchaPass = randString(10);
            Option::set("main", "captcha_password", $captchaPass);
        }

        $cpt->SetCodeCrypt($captchaPass);
        return $cpt->GetCodeCrypt();
    }
}