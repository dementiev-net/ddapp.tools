<?php

namespace DDAPP\Tools\Install;

use Bitrix\Main\Loader;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Mail\Internal\EventTypeTable;
use Bitrix\Main\Mail\Internal\EventMessageTable;

class EmailTemplateInstaller
{
    private $moduleId;
    private const CRITICAL_EMAIL_TEMPLATE_CODE = "DDAPP_ERROR_CRITICAL";
    private const FREE_SPACE_EMAIL_TEMPLATE_CODE = "DDAPP_MESSAGE_DISK";
    private const FORM_EMAIL_TEMPLATE_CODE = "DDAPP_MESSAGE_FORM";
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
        $emailHeader = "Информационное сообщение сайта #SITE_NAME#\n------------------------------------------\n\n";
        $emailFooter = "\n\nСообщение сгенерировано автоматически.\n";

        $this->createEmailTemplate(
            self::CRITICAL_EMAIL_TEMPLATE_CODE,
            "Критическая ошибка системы",
            "#EMAIL# - Email получателя\n#LOG_TYPE# - Тип лога\n#MESSAGE# - Сообщение об ошибке\n#CONTEXT# - Дополнительный контекст\n#DATE_TIME# - Дата и время\n#SERVER_NAME# - Имя сервера\n#REQUEST_URI# - URL запроса\n#USER_AGENT# - Браузер пользователя\n#USER_ID# - ID пользователя\n#USER_LOGIN# - Логин пользователя\n#MEMORY_USAGE# - Используемая память\n#PEAK_MEMORY# - Пиковое использование памяти",
            "#SITE_NAME#: Критическая ошибка системы! [#LOG_TYPE#]",
            $emailHeader . "Сообщение об ошибке: #MESSAGE#\n\nТип лога :#LOG_TYPE#\nДата и время: #DATE_TIME#\nСервер: #SERVER_NAME#\nURL запроса: #REQUEST_URI#\nПользователь: #USER_LOGIN# (ID: #USER_ID#)\nБраузер: #USER_AGENT#\nПамять (текущая/пиковая): #MEMORY_USAGE# / #PEAK_MEMORY#\n\nКонтекст ошибки: #CONTEXT#" . $emailFooter
        );
        $this->createEmailTemplate(
            self::FREE_SPACE_EMAIL_TEMPLATE_CODE,
            "Мало свободного места",
            "#EMAIL# - Получатели сообщения\n#FREE_SPACE# - Свободное место, Мб\n#TOTAL_SPACE# - Всего места, Мб",
            "#SITE_NAME#: Мало свободного места",
            $emailHeader . "На сайте #SITE_NAME# осталось мало места.\n\nВсего места: #TOTAL_SPACE# Мб.\nОсталось: #FREE_SPACE# Мб.\nЖелаемое количество места: #WANT_SPACE# Мб." . $emailFooter
        );
        $this->createEmailTemplate(
            self::FORM_EMAIL_TEMPLATE_CODE,
            "Задайте Ваш вопрос",
            "#NAME# - Имя\n#PHONE# - Телефон\n#EMAIL# - E-Mail\n#COMMENT# - Ваш вопрос\n#CATEGORIES# - Категория вопроса\n#AGE# - Возраст\n#MEETING# - Дата и время встречи\n#SUBSCRIBE# - Подписка на новости\n#CITY# - Город\n#FILE# - Файл",
            "#SITE_NAME#: Задан вопрос",
            $emailHeader . "Вам было отправлено сообщение через форму обратной связи.\n\nАвтор: #NAME#\nE-mail: #EMAIL#\nТелефон: #PHONE#\nКатегория вопроса: #CATEGORIES#\nВозраст: #AGE#\nДата и время встречи: #MEETING#\nПодписка на новости: #SUBSCRIBE#\nГород: #CITY#\nФайл: #FILE#\n\nТекст сообщения: #COMMENT#" . $emailFooter
        );

        return true;
    }

    /**
     * @return true
     */
    public function uninstall(): bool
    {
        $this->deleteEmailTemplate(self::CRITICAL_EMAIL_TEMPLATE_CODE);
        $this->deleteEmailTemplate(self::FREE_SPACE_EMAIL_TEMPLATE_CODE);
        $this->deleteEmailTemplate(self::FORM_EMAIL_TEMPLATE_CODE);

        return true;
    }

    /**
     * Создает почтовый шаблон
     * @param string $eventName
     * @param string $typeName
     * @param string $typeDesc
     * @param string $tempSubject
     * @param string $tempMessage
     * @return void
     */
    private function createEmailTemplate(string $eventName, string $typeName, string $typeDesc, string $tempSubject, string $tempMessage): void
    {
        if (!Loader::includeModule("main")) {
            return;
        }

        // Проверяем, не существует ли уже такой тип события
        $existingType = EventTypeTable::getList([
                "filter" => ["EVENT_NAME" => $eventName],
                "limit" => 1]
        )->fetch();

        if (!$existingType) {

            $arFields = [
                "LID" => "ru",
                "EVENT_NAME" => $eventName,
                "NAME" => $typeName,
                "DESCRIPTION" => $typeDesc
            ];

            // Создаем тип почтового события
            $typeResult = EventTypeTable::add($arFields);

            if (!$typeResult->isSuccess()) {

                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERRORS" => $typeResult->getErrorMessages(),
                    "FIELDS" => $arFields
                ], "EventTypeTable::add", self::LOG_FILE);

                return;
            }
        }

        // Проверяем, не существует ли уже шаблон сообщения
        $existingMessage = EventMessageTable::getList([
                "filter" => ["EVENT_NAME" => $eventName],
                "limit" => 1]
        )->fetch();

        if (!$existingMessage) {

            $arFields = [
                "ACTIVE" => "Y",
                "EVENT_NAME" => $eventName,
                "LID" => ["s1"],
                "EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
                "EMAIL_TO" => "#EMAIL#",
                "SUBJECT" => $tempSubject,
                "MESSAGE" => $tempMessage,
                "BODY_TYPE" => "text"
            ];

            // Создаем шаблон сообщения
            $em = new \CEventMessage;
            $templateId = $em->Add($arFields);

            if (!$templateId) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERROR" => $em->LAST_ERROR,
                    "FIELDS" => $arFields
                ], "CEventMessage::add", self::LOG_FILE);
            }
        }

    }

    /**
     * Удаляет почтовый шаблон
     * @param string $eventName
     * @return void
     */
    private function deleteEmailTemplate(string $eventName): void
    {
        if (!Loader::includeModule("main")) {
            return;
        }

        // Удаляем шаблоны сообщений
        $messages = EventMessageTable::getList([
            "filter" => ["EVENT_NAME" => $eventName]
        ]);

        while ($message = $messages->fetch()) {

            $result = EventMessageTable::delete($message["ID"]);

            if (!$result->isSuccess()) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERRORS" => $result->getErrorMessages(),
                    "MESSAGE_ID" => $message["ID"],
                    "EVENT_NAME" => $eventName
                ], "EventMessageTable::delete", self::LOG_FILE);
            }
        }

        // Удаляем тип события
        $types = EventTypeTable::getList([
            "filter" => ["EVENT_NAME" => $eventName]
        ]);

        while ($type = $types->fetch()) {

            $result = EventTypeTable::delete($type["ID"]);

            if (!$result->isSuccess()) {
                Debug::writeToFile([
                    "DATE" => date("Y-m-d H:i:s"),
                    "ERRORS" => $result->getErrorMessages(),
                    "TYPE_ID" => $type["ID"],
                    "EVENT_NAME" => $eventName
                ], "EventTypeTable::delete", self::LOG_FILE);
            }
        }
    }
}