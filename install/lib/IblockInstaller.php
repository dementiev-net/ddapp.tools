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
            "ID" => "ddapp_tools_content",
            "SECTIONS" => "Y",
            "ELEMENTS" => "Y",
            "IN_RSS" => "N",
            "SORT" => 500,
            "LANG" => [
                "ru" => ["NAME" => "2Dapp Tools - Контент", "SECTION_NAME" => "Разделы", "ELEMENT_NAME" => "Элементы"],
                "en" => ["NAME" => "2Dapp Tools - Content", "SECTION_NAME" => "Sections", "ELEMENT_NAME" => "Elements"]
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
            "NAME" => "2Dapp Tools - Новости и статьи",
            "CODE" => "ddapp_tools_news",
            "IBLOCK_TYPE_ID" => "ddapp_tools_content",
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
            ["NAME" => "Автор", "ACTIVE" => "Y", "SORT" => "100", "CODE" => "AUTHOR", "PROPERTY_TYPE" => "S", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N", "SEARCHABLE" => "Y"],
            ["NAME" => "Источник", "ACTIVE" => "Y", "SORT" => "200", "CODE" => "SOURCE", "PROPERTY_TYPE" => "S", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N", "SEARCHABLE" => "Y"],
            ["NAME" => "Теги", "ACTIVE" => "Y", "SORT" => "300", "CODE" => "TAGS", "PROPERTY_TYPE" => "S", "MULTIPLE" => "Y", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N", "SEARCHABLE" => "Y"],
            ["NAME" => "Рейтинг", "ACTIVE" => "Y", "SORT" => "400", "CODE" => "RATING", "PROPERTY_TYPE" => "N", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N"],
            ["NAME" => "Показывать на главной", "ACTIVE" => "Y", "SORT" => "500", "CODE" => "SHOW_ON_MAIN", "PROPERTY_TYPE" => "L", "LIST_TYPE" => "C", "IBLOCK_ID" => $iblockId, "IS_REQUIRED" => "N",
                "VALUES" => [
                    ["VALUE" => "Да", "DEF" => "N", "SORT" => "10"],
                    ["VALUE" => "Нет", "DEF" => "Y", "SORT" => "20"]
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
        $res = \CIBlock::GetList([], ["CODE" => "ddapp_tools_news", "CHECK_PERMISSIONS" => "N"]);

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
        $res = \CIBlock::GetList([], ["TYPE" => "ddapp_tools_content", "CHECK_PERMISSIONS" => "N"]);

        if (!$res->Fetch()) {
            $obBlockType = new \CIBlockType;
            $result = $obBlockType->Delete("ddapp_tools_content");

            if (!$result) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERROR" => $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : "Неизвестная ошибка при удалении типа инфоблока",
                    "IBLOCK_TYPE" => "ddapp_tools_content"
                ], "CIBlockType::Delete", "/upload/logs/ddapp.tools.install.log");
            }
        }
    }
}