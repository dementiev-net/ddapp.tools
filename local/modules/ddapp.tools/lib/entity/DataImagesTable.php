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

class DataImagesTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName()
    {
        return "ddapp_data_images";
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
                "required" => true
            ]),
            "IBLOCK_TYPE_ID" => new StringField("IBLOCK_TYPE_ID", [
            ]),
            "IBLOCK_ID" => new IntegerField("IBLOCK_ID", [
            ]),
            "ZIP_FILE" => new StringField("ZIP_FILE", [
            ]),
            "SETTINGS" => new TextField("SETTINGS", [
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