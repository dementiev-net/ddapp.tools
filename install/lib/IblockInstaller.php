<?php

namespace DD\Tools\Install;

use Bitrix\Main\Loader;

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
            "ID" => "dd_tools_content",
            "SECTIONS" => "Y",
            "ELEMENTS" => "Y",
            "IN_RSS" => "N",
            "SORT" => 500,
            "LANG" => [
                "ru" => ["NAME" => "DD Tools - Контент", "SECTION_NAME" => "Разделы", "ELEMENT_NAME" => "Элементы"],
                "en" => ["NAME" => "DD Tools - Content", "SECTION_NAME" => "Sections", "ELEMENT_NAME" => "Elements"]
            ]
        ];

        $obBlocktype = new \CIBlockType;
        $obBlocktype->Add($arFields);
    }

    /**
     * @return void
     */
    private function createInfoblock()
    {
        $arFields = [
            "ACTIVE" => "Y",
            "NAME" => "DD Tools - Новости и статьи",
            "CODE" => "dd_tools_news",
            "IBLOCK_TYPE_ID" => "dd_tools_content",
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

        if ($iblockId) {
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
            $ibp->Add($field);
        }
    }

    /**
     * @return void
     */
    private function deleteInfoblock()
    {
        $res = \CIBlock::GetList([], ["CODE" => "dd_tools_news", "CHECK_PERMISSIONS" => "N"]);

        if ($ar_res = $res->Fetch()) {

            $iblockId = $ar_res["ID"];
            $rsElements = \CIBlockElement::GetList([], ["IBLOCK_ID" => $iblockId], false, false, ["ID"]);

            while ($arElement = $rsElements->Fetch()) {
                \CIBlockElement::Delete($arElement["ID"]);
            }

            $rsSections = \CIBlockSection::GetList([], ["IBLOCK_ID" => $iblockId], false, ["ID"]);

            while ($arSection = $rsSections->Fetch()) {
                \CIBlockSection::Delete($arSection["ID"]);
            }

            \CIBlock::Delete($iblockId);
        }
    }

    /**
     * @return void
     */
    private function deleteInfoblockType()
    {
        $res = \CIBlock::GetList([], ["TYPE" => "dd_tools_content", "CHECK_PERMISSIONS" => "N"]);

        if (!$res->Fetch()) {
            $obBlockType = new \CIBlockType;
            $obBlockType->Delete("dd_tools_content");
        }
    }
}