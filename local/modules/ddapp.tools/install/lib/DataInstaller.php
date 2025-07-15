<?php

namespace DDAPP\Tools\Install;

use Bitrix\Main\Loader;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use DDAPP\Tools\Entity\MaintenanceTable;

class DataInstaller
{
    private $moduleId;
    private const LOG_FILE = "/upload/ddapp.tools.install.log";

    public function __construct($moduleId)
    {
        $this->moduleId = $moduleId;
    }

    /**
     * @return true
     */
    public function install(): bool
    {
        Loader::includeModule($this->moduleId);
        Loader::includeModule("iblock");

        $this->addDefaultMaintenance();
        $this->addIblockElements();

        return true;
    }

    /**
     * @return void
     */
    private function addDefaultMaintenance(): void
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
                ], "MaintenanceTable::add", self::LOG_FILE);
            }
        }
    }

    /**
     * @return void
     */
    private function addIblockElements(): void
    {
        $iblockId = $this->getIblockId("ddapp_forms_city");

        if (!$iblockId) {
            return;
        }

        $defaultElements = [
            ["NAME" => "Москва", "CODE" => "moscow", "PREVIEW_TEXT" => "", "DETAIL_TEXT" => ""],
            ["NAME" => "Санкт Петербург", "CODE" => "piter", "PREVIEW_TEXT" => "", "DETAIL_TEXT" => ""],
            ["NAME" => "Казань", "CODE" => "kazan", "PREVIEW_TEXT" => "", "DETAIL_TEXT" => ""],
        ];

        foreach ($defaultElements as $elementData) {
            $this->addIblockElement($iblockId, $elementData);
        }
    }

    /**
     * @param $code
     * @return mixed
     */
    private function getIblockId($code): mixed
    {
        $res = \CIBlock::GetList([], ["CODE" => $code, "CHECK_PERMISSIONS" => "N"]);

        if ($ar_res = $res->Fetch()) {
            return $ar_res["ID"];
        }

        Debug::writeToFile([
            "DATE" => date("Y-m-d H:i:s"),
            "MESSAGE" => "Инфоблок с таким CODE не найден",
            "CODE" => $code,
        ], "CIBlock::GetList", self::LOG_FILE);

        return false;
    }

    /**
     * @param $iblockId
     * @param $elementData
     * @return mixed
     */
    private function addIblockElement($iblockId, $elementData): mixed
    {
        $el = new \CIBlockElement;

        $arFields = ["IBLOCK_ID" => $iblockId, "NAME" => $elementData["NAME"], "CODE" => $elementData["CODE"], "ACTIVE" => "Y", "PREVIEW_TEXT" => $elementData["PREVIEW_TEXT"], "DETAIL_TEXT" => $elementData["DETAIL_TEXT"], "PROPERTY_VALUES" => $elementData["PROPERTIES"]];

        $elementId = $el->Add($arFields);

        if (!$elementId) {
            Debug::writeToFile([
                "DATE" => date("Y-m-d H:i:s"),
                "ERROR" => $el->LAST_ERROR,
                "FIELDS" => $arFields,
            ], "CIBlockElement::add", self::LOG_FILE);
        }

        return $elementId;
    }
}