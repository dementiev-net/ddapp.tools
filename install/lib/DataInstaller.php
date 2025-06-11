<?php

namespace DD\Tools\Install;

use Bitrix\Main\Loader;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use DD\Tools\Entity\DataTable;
use DD\Tools\Entity\AuthorTable;

class DataInstaller
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
        Loader::includeModule($this->moduleId);
        Loader::includeModule("iblock");

        $this->addDefaultData();
        $this->addDefaultAuthors();
        $this->addIblockElements();

        return true;
    }

    /**
     * @return void
     */
    private function addDefaultData()
    {
        $testElements = [
            ["ACTIVE" => "N", "SITE" => "[\"s1\"]", "LINK" => " ", "LINK_PICTURE" => "/bitrix/components/dd.tools/popup.baner/templates/.default/img/banner.jpg", "ALT_PICTURE" => " ", "EXCEPTIONS" => " ", "DATE" => new DateTime(date("d.m.Y H:i:s")), "TARGET" => "self", "AUTHOR_ID" => "1"],
            ["ACTIVE" => "N", "SITE" => "[\"s2\"]", "LINK" => " ", "LINK_PICTURE" => "/bitrix/components/dd.tools/popup.baner/templates/.default/img/banner2.jpg", "ALT_PICTURE" => " ", "EXCEPTIONS" => " ", "DATE" => new DateTime(date("d.m.Y H:i:s")), "TARGET" => "self", "AUTHOR_ID" => "1"]
        ];

        foreach ($testElements as $elementData) {

            $result = DataTable::add($elementData);

            if (!$result->isSuccess()) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERRORS" => $result->getErrorMessages(),
                    "FIELDS" => $elementData,
                ], "DataTable::add", "/upload/logs/dd.tools.install.log");
            }
        }
    }

    /**
     * @return void
     */
    private function addDefaultAuthors()
    {
        $testElements = [
            ["NAME" => "Иван", "LAST_NAME" => "Иванов"],
            ["NAME" => "Иван2", "LAST_NAME" => "Иванов2"]
        ];

        foreach ($testElements as $elementData) {

            $result = AuthorTable::add($elementData);

            if (!$result->isSuccess()) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERRORS" => $result->getErrorMessages(),
                    "FIELDS" => $elementData,
                ], "AuthorTable::add", "/upload/logs/dd.tools.install.log");
            }
        }
    }

    /**
     * @return false|void
     */
    private function addIblockElements()
    {
        $iblockId = $this->getIblockId("dd_tools_news");

        if (!$iblockId) {
            return false;
        }

        $testElements = [
            [
                "NAME" => "Первая новость DD Tools", "CODE" => "first_news_dd_tools", "PREVIEW_TEXT" => "Краткое описание первой новости для тестирования модуля DD Tools", "DETAIL_TEXT" => "Подробное описание первой новости. Здесь может быть много текста с различными подробностями о функционале модуля DD Tools.",
                "PROPERTIES" => ["AUTHOR" => "Иван Петров", "SOURCE" => "Официальный сайт", "TAGS" => ["новости", "dd.tools", "модуль"], "RATING" => 5, "SHOW_ON_MAIN" => "Да"]
            ], [
                "NAME" => "Обновление функционала", "CODE" => "functionality_update", "PREVIEW_TEXT" => "Информация о новых возможностях модуля DD Tools", "DETAIL_TEXT" => "В новой версии модуля DD Tools добавлены дополнительные функции для работы с контентом и улучшена производительность.",
                "PROPERTIES" => ["AUTHOR" => "Анна Сидорова", "SOURCE" => "Блог разработчиков", "TAGS" => ["обновление", "функционал", "производительность"], "RATING" => 4, "SHOW_ON_MAIN" => "Да"]
            ], [
                "NAME" => "Руководство по установке", "CODE" => "installation_guide", "PREVIEW_TEXT" => "Пошаговая инструкция по установке и настройке модуля", "DETAIL_TEXT" => "Данное руководство поможет вам правильно установить и настроить модуль DD Tools на вашем сайте. Следуйте инструкциям для корректной работы.",
                "PROPERTIES" => ["AUTHOR" => "Техническая поддержка", "SOURCE" => "Документация", "TAGS" => ["установка", "настройка", "инструкция"], "RATING" => 3, "SHOW_ON_MAIN" => "Нет"]
            ]
        ];

        foreach ($testElements as $elementData) {
            $this->addIblockElement($iblockId, $elementData);
        }
    }

    /**
     * @param $code
     * @return false
     */
    private function getIblockId($code)
    {
        $res = \CIBlock::GetList([], ["CODE" => $code, "CHECK_PERMISSIONS" => "N"]);

        if ($ar_res = $res->Fetch()) {
            return $ar_res["ID"];
        }

        Debug::writeToFile([
            "DATE" => date("Y-m-d H:i:s"),
            "MESSAGE" => "Инфоблок с таким CODE не найден",
            "CODE" => $code,
        ], "CIBlock::GetList", "/upload/logs/dd.tools.install.log");

        return false;
    }

    /**
     * @param $iblockId
     * @param $elementData
     * @return mixed
     */
    private function addIblockElement($iblockId, $elementData)
    {
        $el = new \CIBlockElement;

        $arFields = ["IBLOCK_ID" => $iblockId, "NAME" => $elementData["NAME"], "CODE" => $elementData["CODE"], "ACTIVE" => "Y", "PREVIEW_TEXT" => $elementData["PREVIEW_TEXT"], "DETAIL_TEXT" => $elementData["DETAIL_TEXT"], "PROPERTY_VALUES" => $elementData["PROPERTIES"]];

        $elementId = $el->Add($arFields);

        if (!$elementId) {
            Debug::writeToFile([
                "DATE" => date("Y-m-d H:i:s"),
                "ERROR" => $el->LAST_ERROR,
                "FIELDS" => $arFields,
            ], "CIBlockElement::add", "/upload/logs/dd.tools.install.log");
        }

        return $elementId;
    }
}