<?php

namespace DD\Tools\Install;

use Bitrix\Main\Loader;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use DD\Tools\Entity\DataTable;
use DD\Tools\Entity\MaintenanceTable;

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
        $this->addDefaultMaintenance();
        $this->addIblockElements();

        return true;
    }

    /**
     * @return void
     */
    private function addDefaultData()
    {
        $defaultElements = [
            ["ACTIVE" => "N", "SITE" => "[\"s1\"]", "LINK" => " ", "LINK_PICTURE" => "/bitrix/components/dd.tools/popup.baner/templates/.default/img/banner.jpg", "ALT_PICTURE" => " ", "EXCEPTIONS" => " ", "DATE" => new DateTime(date("d.m.Y H:i:s")), "TARGET" => "self"],
            ["ACTIVE" => "N", "SITE" => "[\"s2\"]", "LINK" => " ", "LINK_PICTURE" => "/bitrix/components/dd.tools/popup.baner/templates/.default/img/banner2.jpg", "ALT_PICTURE" => " ", "EXCEPTIONS" => " ", "DATE" => new DateTime(date("d.m.Y H:i:s")), "TARGET" => "self"]
        ];

        foreach ($defaultElements as $elementData) {

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
    private function addDefaultMaintenance()
    {
        $defaultElements = [
            ["NAME" => "Оптимизация таблиц", "LINK" => "/bitrix/admin/repair_db.php?optimize_tables=Y", "DESCRIPTION" => "Настройки - Инструменты - Диагностика - Оптимизация БД (кнопка Оптимизировать)", "TYPE" => "SCHEDULED", "ACTIVE" => "Y", "DATE_CREATE" => new DateTime(date("d.m.Y H:i:s")), "DATE_MODIFY" => new DateTime(date("d.m.Y H:i:s"))],
            ["NAME" => "Проверка системы", "LINK" => "/bitrix/admin/site_checker.php", "DESCRIPTION" => "Инструменты - Проверка системы (провести Тестирование системы, кнопка Начать тестирование) - если обнаружены не соответствия отметить", "TYPE" => "SCHEDULED", "ACTIVE" => "Y", "DATE_CREATE" => new DateTime(date("d.m.Y H:i:s")), "DATE_MODIFY" => new DateTime(date("d.m.Y H:i:s"))],
            ["NAME" => "Анализ производительности", "LINK" => "/bitrix/admin/perfmon_panel.php", "DESCRIPTION" => "Настройки - Производительность - Панель производительности (кнопка Тестировать конфигурацию) проверить закладки Конфигурация, Битрикс, Разработка - если обнаружены не соответствия отметить", "TYPE" => "SCHEDULED", "ACTIVE" => "Y", "DATE_CREATE" => new DateTime(date("d.m.Y H:i:s")), "DATE_MODIFY" => new DateTime(date("d.m.Y H:i:s"))],
            ["NAME" => "Поиск длительных запросов", "LINK" => "/bitrix/admin/settings.php?lang=ru&mid=perfmon&mid_menu=1", "DESCRIPTION" => "Настройки - Настройки продукта - Настройки модулей - Монитор производительности\n- очистить статистику\n- отметить галочку Записывать только медленные SQL запросы\n- запустить монитор производительности на 1 час\nДалее посмотреть Настройки - Производительность - Запросы SQL - если обнаружены не соответствия отметить", "TYPE" => "SCHEDULED", "ACTIVE" => "Y", "DATE_CREATE" => new DateTime(date("d.m.Y H:i:s")), "DATE_MODIFY" => new DateTime(date("d.m.Y H:i:s"))],
            ["NAME" => "Очистка кеша", "LINK" => "/bitrix/admin/cache.php", "DESCRIPTION" => "Проверить, что не переполняется и кэш чиститься", "TYPE" => "SCHEDULED", "ACTIVE" => "Y", "DATE_CREATE" => new DateTime(date("d.m.Y H:i:s")), "DATE_MODIFY" => new DateTime(date("d.m.Y H:i:s"))],
            ["NAME" => "Резервные копии", "LINK" => "/bitrix/admin/dump_list.php", "DESCRIPTION" => "Проверить, что настроено ежедневное в 22:00 локальное автоматическое резервное копирование базы данных сайта с хранением 3х копий за последние 3 дня", "TYPE" => "SCHEDULED", "ACTIVE" => "Y", "DATE_CREATE" => new DateTime(date("d.m.Y H:i:s")), "DATE_MODIFY" => new DateTime(date("d.m.Y H:i:s"))],
            ["NAME" => "Проверка логов ошибок", "LINK" => "/bitrix/admin/event_log.php", "DESCRIPTION" => "Проверить на наличие ошибок", "TYPE" => "SCHEDULED", "ACTIVE" => "Y", "DATE_CREATE" => new DateTime(date("d.m.Y H:i:s")), "DATE_MODIFY" => new DateTime(date("d.m.Y H:i:s"))],
            ["NAME" => "Проверка модулей", "LINK" => "/bitrix/admin/module_admin.php", "DESCRIPTION" => "Отключить не используемые (с созданием резервной копии!)", "TYPE" => "SCHEDULED", "ACTIVE" => "Y", "DATE_CREATE" => new DateTime(date("d.m.Y H:i:s")), "DATE_MODIFY" => new DateTime(date("d.m.Y H:i:s"))],
            ["NAME" => "Обновление системы", "LINK" => "/bitrix/admin/update_system.php", "DESCRIPTION" => "Выполнить обновление системы и сторонних решений (с созданием резервной копии!)", "TYPE" => "SCHEDULED", "ACTIVE" => "Y", "DATE_CREATE" => new DateTime(date("d.m.Y H:i:s")), "DATE_MODIFY" => new DateTime(date("d.m.Y H:i:s"))],
        ];

        foreach ($defaultElements as $elementData) {

            $result = MaintenanceTable::add($elementData);

            if (!$result->isSuccess()) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERRORS" => $result->getErrorMessages(),
                    "FIELDS" => $elementData,
                ], "MaintenanceTable::add", "/upload/logs/dd.tools.install.log");
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

        $defaultElements = [
            ["NAME" => "Первая новость DD Tools", "CODE" => "first_news_dd_tools", "PREVIEW_TEXT" => "Краткое описание первой новости для тестирования модуля DD Tools", "DETAIL_TEXT" => "Подробное описание первой новости. Здесь может быть много текста с различными подробностями о функционале модуля DD Tools.",
                "PROPERTIES" => ["AUTHOR" => "Иван Петров", "SOURCE" => "Официальный сайт", "TAGS" => ["новости", "dd.tools", "модуль"], "RATING" => 5, "SHOW_ON_MAIN" => "Да"]],
            ["NAME" => "Обновление функционала", "CODE" => "functionality_update", "PREVIEW_TEXT" => "Информация о новых возможностях модуля DD Tools", "DETAIL_TEXT" => "В новой версии модуля DD Tools добавлены дополнительные функции для работы с контентом и улучшена производительность.",
                "PROPERTIES" => ["AUTHOR" => "Анна Сидорова", "SOURCE" => "Блог разработчиков", "TAGS" => ["обновление", "функционал", "производительность"], "RATING" => 4, "SHOW_ON_MAIN" => "Да"]],
            ["NAME" => "Руководство по установке", "CODE" => "installation_guide", "PREVIEW_TEXT" => "Пошаговая инструкция по установке и настройке модуля", "DETAIL_TEXT" => "Данное руководство поможет вам правильно установить и настроить модуль DD Tools на вашем сайте. Следуйте инструкциям для корректной работы.",
                "PROPERTIES" => ["AUTHOR" => "Техническая поддержка", "SOURCE" => "Документация", "TAGS" => ["установка", "настройка", "инструкция"], "RATING" => 3, "SHOW_ON_MAIN" => "Нет"]]
        ];

        foreach ($defaultElements as $elementData) {
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