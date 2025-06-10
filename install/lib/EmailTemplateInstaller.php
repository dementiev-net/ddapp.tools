<?php

namespace DD\Tools\Install;

use Bitrix\Main\Loader;
use Bitrix\Main\Mail\Internal\EventTypeTable;
use Bitrix\Main\Mail\Internal\EventMessageTable;

class EmailTemplateInstaller
{
    private $moduleId;
    private const CRITICAL_EMAIL_TEMPLATE_CODE = "DD_TOOLS_CRITICAL_ERROR";

    public function __construct($moduleId)
    {
        $this->moduleId = $moduleId;
    }

    /**
     * @return true
     */
    public function install()
    {
        $this->createEmailTemplate();

        return true;
    }

    /**
     * @return true
     */
    public function uninstall()
    {
        $this->deleteEmailTemplate();

        return true;
    }

    /**
     * Создает почтовый шаблон для критических ошибок
     * @return bool
     */
    private function createEmailTemplate(): bool
    {
        if (!Loader::includeModule("main")) {
            return false;
        }

        // Проверяем, не существует ли уже такой тип события
        $existingType = EventTypeTable::getList(array(
            "filter" => array("EVENT_NAME" => self::CRITICAL_EMAIL_TEMPLATE_CODE),
            "limit" => 1
        ))->fetch();

        if (!$existingType) {
            // Создаем тип почтового события
            $typeResult = EventTypeTable::add(array(
                "LID" => "ru",
                "EVENT_NAME" => self::CRITICAL_EMAIL_TEMPLATE_CODE,
                "NAME" => "Критическая ошибка системы",
                "DESCRIPTION" => "#EMAIL# - Email получателя\n#LOG_TYPE# - Тип лога\n#MESSAGE# - Сообщение об ошибке\n#CONTEXT# - Дополнительный контекст\n#DATE_TIME# - Дата и время\n#SERVER_NAME# - Имя сервера\n#REQUEST_URI# - URL запроса\n#USER_AGENT# - Браузер пользователя\n#USER_ID# - ID пользователя\n#USER_LOGIN# - Логин пользователя\n#MEMORY_USAGE# - Используемая память\n#PEAK_MEMORY# - Пиковое использование памяти"
            ));

            if (!$typeResult->isSuccess()) {
                return false;
            }
        }

        // Проверяем, не существует ли уже шаблон сообщения
        $existingMessage = EventMessageTable::getList(array(
            "filter" => array("EVENT_NAME" => self::CRITICAL_EMAIL_TEMPLATE_CODE),
            "limit" => 1
        ))->fetch();

        if (!$existingMessage) {
            // Создаем шаблон сообщения
            $em = new \CEventMessage;
            $templateId = $em->Add(array(
                "ACTIVE" => "Y",
                "EVENT_NAME" => self::CRITICAL_EMAIL_TEMPLATE_CODE,
                "LID" => array("s1"),
                "EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
                "EMAIL_TO" => "#EMAIL#",
                "SUBJECT" => "[КРИТИЧЕСКАЯ ОШИБКА] #SERVER_NAME# - #LOG_TYPE#",
                "MESSAGE" => "Сообщение об ошибке: #MESSAGE#\n\nТип лога :#LOG_TYPE#\nДата и время: #DATE_TIME#\nСервер: #SERVER_NAME#\nURL запроса: #REQUEST_URI#\nПользователь: #USER_LOGIN# (ID: #USER_ID#)\nБраузер: #USER_AGENT#\nПамять (текущая/пиковая): #MEMORY_USAGE# / #PEAK_MEMORY#\n\nКонтекст ошибки: #CONTEXT#",
                "BODY_TYPE" => "text"
            ));
        }

        return true;
    }

    /**
     * Удаляет почтовый шаблон
     * @return bool
     */
    private function deleteEmailTemplate(): bool
    {
        if (!Loader::includeModule("main")) {
            return false;
        }

        // Удаляем шаблоны сообщений
        $messages = EventMessageTable::getList(array(
            "filter" => array("EVENT_NAME" => self::CRITICAL_EMAIL_TEMPLATE_CODE)
        ));

        while ($message = $messages->fetch()) {
            EventMessageTable::delete($message["ID"]);
        }

        // Удаляем тип события
        $types = EventTypeTable::getList(array(
            "filter" => array("EVENT_NAME" => self::CRITICAL_EMAIL_TEMPLATE_CODE)
        ));

        while ($type = $types->fetch()) {
            EventTypeTable::delete($type["ID"]);
        }

        return true;
    }
}