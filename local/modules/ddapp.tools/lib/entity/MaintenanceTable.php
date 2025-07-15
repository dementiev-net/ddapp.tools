<?php

namespace DDAPP\Tools\Entity;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class MaintenanceTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName()
    {
        return "ddapp_maintenance_plans";
    }

    /**
     * @return array
     */
    public static function getMap()
    {
        return [
            "ID" => new IntegerField("ID", [
                "primary" => true,
                "autocomplete" => true
            ]),
            "NAME" => new StringField("NAME", [
                "required" => true,
                "validation" => [__CLASS__, "validateName"]
            ]),
            "LINK" => new StringField("LINK", [
                "size" => 500,
            ]),
            "DESCRIPTION" => new TextField("DESCRIPTION", [
            ]),
            "ACTIVE" => new StringField("ACTIVE", [
                "validation" => [__CLASS__, "validateActive"],
                "default_value" => "Y"
            ]),
            "PRIORITY" => new IntegerField("PRIORITY", [
                "validation" => [__CLASS__, "validatePriority"],
                "default_value" => 1
            ]),
            "TYPE" => new StringField("TYPE", [
                "size" => 50,
                "validation" => [__CLASS__, "validateType"]
            ]),
            "DATE_CREATE" => new DatetimeField("DATE_CREATE", [
                "required" => true,
                "default_value" => function () {
                    return new DateTime();
                }
            ]),
            "DATE_MODIFY" => new DatetimeField("DATE_MODIFY", [
                "required" => true,
                "default_value" => function () {
                    return new DateTime();
                }
            ]),
        ];
    }

    /**
     * Валидация поля NAME
     * @param $value
     * @param $primary
     * @param $row
     * @param $id
     * @return array
     */
    public static function validateName($value = null, $primary = null, $row = null, $id = null)
    {
        $result = [];

        if ($value === null) {
            return $result;
        }

        if (strlen($value) === 0) {
            $result[] = Loc::getMessage("DDAPP_ENTITY_NAME_REQUIRED");
        }

        if (strlen($value) > 255) {
            $result[] = Loc::getMessage("DDAPP_ENTITY_NAME_TOO_LONG");
        }

        return $result;
    }

    /**
     * Валидация поля ACTIVE
     * @param $value
     * @param $primary
     * @param $row
     * @param $id
     * @return array
     */
    public static function validateActive($value = null, $primary = null, $row = null, $id = null)
    {
        $result = [];

        if ($value === null) {
            return $result;
        }

        if (!in_array($value, ["Y", "N"])) {
            $result[] = Loc::getMessage("DDAPP_ENTITY_ACTIVE_INVALID");
        }

        return $result;
    }

    /**
     * Валидация поля PRIORITY
     * @param $value
     * @param $primary
     * @param $row
     * @param $id
     * @return array
     */
    public static function validatePriority($value = null, $primary = null, $row = null, $id = null)
    {
        $result = [];

        if ($value === null) {
            return $result;
        }

        if ($value < 1 || $value > 100) {
            $result[] = Loc::getMessage("DDAPP_ENTITY_PRIORITY_INVALID");
        }

        return $result;
    }

    /**
     * Валидация поля TYPE
     * @param $value
     * @param $primary
     * @param $row
     * @param $id
     * @return array
     */
    public static function validateType($value = null, $primary = null, $row = null, $id = null)
    {
        $result = [];
        $allowedTypes = ["SCHEDULED", "EMERGENCY", "PREVENTIVE"];

        if ($value === null) {
            return $result;
        }

        if (!empty($value) && !in_array($value, $allowedTypes)) {
            $result[] = Loc::getMessage("DDAPP_ENTITY_TYPE_INVALID");
        }

        return $result;
    }

    /**
     * Обработчик события перед добавлением записи
     * @param \Bitrix\Main\Entity\Event $event
     * @return \Bitrix\Main\Entity\EventResult
     */
    public static function onBeforeAdd(\Bitrix\Main\Entity\Event $event)
    {
        $result = new \Bitrix\Main\Entity\EventResult;
        $fields = $event->getParameter("fields");

        // Автоматически устанавливаем дату создания и изменения
        $now = new DateTime();
        if (!isset($fields["DATE_CREATE"])) {
            $result->modifyFields(["DATE_CREATE" => $now]);
        }
        if (!isset($fields["DATE_MODIFY"])) {
            $result->modifyFields(["DATE_MODIFY" => $now]);
        }

        return $result;
    }

    /**
     * Обработчик события перед обновлением записи
     * @param \Bitrix\Main\Entity\Event $event
     * @return \Bitrix\Main\Entity\EventResult
     */
    public static function onBeforeUpdate(\Bitrix\Main\Entity\Event $event)
    {
        $result = new \Bitrix\Main\Entity\EventResult;

        // Автоматически обновляем дату изменения
        $result->modifyFields(["DATE_MODIFY" => new DateTime()]);

        return $result;
    }
}