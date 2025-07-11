<?php

namespace DDAPP\Tools\Install;

use Bitrix\Main\Loader;
use Bitrix\Main\Diag\Debug;

class IblockInstaller
{
    private $moduleId;

    public function __construct($moduleId)
    {
        $this->moduleId = $moduleId;
    }

    /**
     * @return true
     */
    public function install()
    {
        $this->createInfoblockType();
        $this->createInfoblock();

        return true;
    }

    /**
     * @return true
     */
    public function uninstall()
    {
        $this->deleteInfoblock();
        $this->deleteInfoblockType();

        return true;
    }

    /**
     * @return void
     */
    private function createInfoblockType()
    {
        $arFields = [
            "ID" => "ddapp_forms",
            "SECTIONS" => "N",
            "ELEMENTS" => "Y",
            "IN_RSS" => "N",
            "SORT" => 500,
            "LANG" => [
                "ru" => ["NAME" => "Формы", "SECTION_NAME" => "Разделы", "ELEMENT_NAME" => "Элементы"],
                "en" => ["NAME" => "Forms", "SECTION_NAME" => "Sections", "ELEMENT_NAME" => "Elements"]
            ]
        ];

        $obBlocktype = new \CIBlockType;
        $typeId = $obBlocktype->Add($arFields);

        if (!$typeId) {
            Debug::writeToFile([
                "DATE" => date("Y-m-d H:i:s"),
                "ERROR" => $obBlocktype->LAST_ERROR,
                "FIELDS" => $arFields
            ], "CIBlockType::add", "/upload/logs/ddapp.tools.install.log");
        }
    }

    /**
     * @return void
     */
    private function createInfoblock()
    {
        $arFields = [
            "ACTIVE" => "Y",
            "NAME" => "Задайте Ваш вопрос",
            "CODE" => "question",
            "IBLOCK_TYPE_ID" => "ddapp_forms",
            "SITE_ID" => ["s1"],
            "SORT" => 500,
            "GROUP_ID" => ["2" => "R"],
            "VERSION" => 2,
            "WORKFLOW" => "N",
            "BIZPROC" => "N",
            "INDEX_ELEMENT" => "Y",
            "INDEX_SECTION" => "Y"
        ];

        $ib = new \CIBlock;
        $iblockId = $ib->Add($arFields);

        if (!$iblockId) {
            Debug::writeToFile([
                "DATE" => date("Y-m-d H:i:s"),
                "ERROR" => $ib->LAST_ERROR,
                "FIELDS" => $arFields
            ], "CIBlock::add", "/upload/logs/ddapp.tools.install.log");
        } else {
            $this->createIblockProperties($iblockId);
        }
    }

    /**
     * @param $iblockId
     * @return void
     */
    private function createIblockProperties($iblockId)
    {
        $arPropFields = [
            ["NAME" => "Имя", "ACTIVE" => "Y", "SORT" => "100", "CODE" => "NAME", "PROPERTY_TYPE" => "S", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "Y", "SEARCHABLE" => "N"],
            ["NAME" => "Телефон", "ACTIVE" => "Y", "SORT" => "200", "CODE" => "PHONE", "PROPERTY_TYPE" => "S", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "Y", "SEARCHABLE" => "N"],
            ["NAME" => "E-Mail", "ACTIVE" => "Y", "SORT" => "300", "CODE" => "EMAIL", "PROPERTY_TYPE" => "S", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N", "SEARCHABLE" => "N"],
            ["NAME" => "Ваш вопрос", "ACTIVE" => "Y", "SORT" => "400", "CODE" => "COMMENT", "PROPERTY_TYPE" => "S", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N", "SEARCHABLE" => "N", "ROW_COUNT" => 5, "COL_COUNT" => 30],
            ["NAME" => "Категория вопроса", "ACTIVE" => "Y", "SORT" => "400", "CODE" => "CATEGORIES", "PROPERTY_TYPE" => "L", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N",
                "VALUES" => [
                    ["VALUE" => "Общие вопросы", "SORT" => "10", "DEF" => "Y"],
                    ["VALUE" => "Технический вопрос", "SORT" => "20", "DEF" => "N"],
                    ["VALUE" => "Бухгалтерия", "SORT" => "30", "DEF" => "N"],
                ]
            ]
        ];
        $ibp = new \CIBlockProperty;
        foreach ($arPropFields as $field) {

            $result = $ibp->Add($field);

            if (!$result) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERROR" => $ibp->LAST_ERROR,
                    "FIELDS" => $field,
                ], "CIBlockProperty::add", "/upload/logs/ddapp.tools.install.log");
            }
        }
    }

    /**
     * @return void
     */
    private function deleteInfoblock()
    {
        $res = \CIBlock::GetList([], ["CODE" => "question", "CHECK_PERMISSIONS" => "N"]);

        if ($ar_res = $res->Fetch()) {

            $iblockId = $ar_res["ID"];

            // Удаляем элементы инфоблока
            $rsElements = \CIBlockElement::GetList([], ["IBLOCK_ID" => $iblockId], false, false, ["ID"]);

            while ($arElement = $rsElements->Fetch()) {

                $result = \CIBlockElement::Delete($arElement["ID"]);

                if (!$result) {
                    Debug::writeToFile([
                        "DATE" => date("Y-m-d H:i:s"),
                        "ERROR" => $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : "Неизвестная ошибка при удалении элемента",
                        "ELEMENT_ID" => $arElement["ID"],
                        "IBLOCK_ID" => $iblockId,
                    ], "CIBlockElement::Delete", "/upload/logs/ddapp.tools.install.log");
                }
            }

            // Удаляем разделы инфоблока
            $rsSections = \CIBlockSection::GetList([], ["IBLOCK_ID" => $iblockId], false, ["ID"]);

            while ($arSection = $rsSections->Fetch()) {

                $result = \CIBlockSection::Delete($arSection["ID"]);

                if (!$result) {
                    Debug::writeToFile([
                        "DATE" => date("Y-m-d H:i:s"),
                        "ERROR" => $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : "Неизвестная ошибка при удалении раздела",
                        "SECTION_ID" => $arSection["ID"],
                        "IBLOCK_ID" => $iblockId,
                    ], "CIBlockSection::Delete", "/upload/logs/ddapp.tools.install.log");
                }
            }

            // Удаляем сам инфоблок
            $result = \CIBlock::Delete($iblockId);

            if (!$result) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERROR" => $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : "Неизвестная ошибка при удалении инфоблока",
                    "IBLOCK_ID" => $iblockId,
                ], "CIBlock::Delete", "/upload/logs/ddapp.tools.install.log");
            }
        }
    }

    /**
     * @return void
     */
    private function deleteInfoblockType()
    {
        $res = \CIBlock::GetList([], ["TYPE" => "ddapp_forms", "CHECK_PERMISSIONS" => "N"]);

        if (!$res->Fetch()) {
            $obBlockType = new \CIBlockType;
            $result = $obBlockType->Delete("ddapp_forms");

            if (!$result) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERROR" => $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : "Неизвестная ошибка при удалении типа инфоблока",
                    "IBLOCK_TYPE" => "ddapp_forms"
                ], "CIBlockType::Delete", "/upload/logs/ddapp.tools.install.log");
            }
        }
    }
}