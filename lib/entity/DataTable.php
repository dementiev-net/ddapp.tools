<?php

namespace DD\Tools\Entity;

use Bitrix\Main\Entity;
use Bitrix\Main\Application;

class DataTable extends Entity\DataManager
{

    /**
     * @return string
     */
    public static function getTableName()
    {
        return "pop_up_table";
    }

    /**
     * @return string
     */
    public static function getConnectionName()
    {
        return "default";
    }

    /**
     * @return array
     */
    public static function getMap()
    {
        /*
         * Типы полей: 
         * DatetimeField - дата и время
         * DateField - дата
         * BooleanField - логическое поле да/нет
         * IntegerField - числовой формат
         * FloatField - числовой дробный формат
         * EnumField - список, можно передавать только заданные значения
         * TextField - text
         * StringField - varchar
         */

        return [

            new Entity\IntegerField(
                "ID",
                [
                    "primary" => true,
                    "autocomplete" => true,
                ]
            ),
            new Entity\BooleanField(
                "ACTIVE",
                [
                    "values" => ["N", "Y"]
                ]
            ),
            new Entity\StringField(
                "SITE",
                [
                    "required" => true,
                ]
            ),
            new Entity\StringField(
                "LINK",
                [
                    "required" => true,
                ]
            ),
            new Entity\StringField(
                "LINK_PICTURE",
                [
                    "column_name" => "LINK_PICTURE_CODE",
                    "validation" => function () {
                        return [
                            new Entity\Validator\Unique,
                            function ($value, $primary, $row, $field) {
                                if (strlen($value) <= 100)
                                    return true;
                                else
                                    return "Код LINK_PICTURE должен содержать не более 100 символов";
                            }
                        ];
                    }
                ]
            ),
            new Entity\StringField(
                "ALT_PICTURE",
                [
                    "required" => true,
                ]
            ),
            new Entity\TextField(
                "EXCEPTIONS"
            ),
            new Entity\DatetimeField(
                "DATE",
                [
                    "required" => true,
                ]
            ),
            new Entity\EnumField(
                "TARGET",
                [
                    "values" => ["self", "blank"],
                    "required" => true,
                ]
            ),
            new Entity\IntegerField(
                "AUTHOR_ID"
            ),
            new Entity\ReferenceField(
                "AUTHOR",
                "DD\Tools\Entity\AuthorTable",
                ["=this.AUTHOR_ID" => "ref.ID"]
            ),
        ];
    }

    /**
     * Очистка тегированного кеша при добавлении
     * @param Entity\Event $event
     * @return void
     */
    public static function onAfterAdd(Entity\Event $event)
    {
        DataTable::clearCache();
    }

    /**
     * Очистка тегированного кеша при изменении
     * @param Entity\Event $event
     * @return void
     */
    public static function onAfterUpdate(Entity\Event $event)
    {
        DataTable::clearCache();
    }

    /**
     * Очистка тегированного кеша при удалении
     * @param Entity\Event $event
     * @return void
     */
    public static function onAfterDelete(Entity\Event $event)
    {
        DataTable::clearCache();
    }

    /**
     * Основной метод очистки кеша по тегу
     * @return void
     */
    public static function clearCache()
    {
        // служба пометки кеша тегами
        $taggedCache = Application::getInstance()->getTaggedCache();
        $taggedCache->clearByTag("popup");
    }

    /**
     * События (для примера запретим изменять поле LINK_PICTURE)
     * @param Entity\Event $event
     * @return Entity\EventResult
     */
    public static function onBeforeUpdate(Entity\Event $event)
    {
        $result = new Entity\EventResult;
        $data = $event->getParameter("fields");
        if (isset($data["LINK_PICTURE"])) {
            $result->addError(
                new Entity\FieldError(
                    $event->getEntity()->getField("LINK_PICTURE"),
                    "Запрещено менять LINK_PICTURE код у баннера"
                )
            );
        }
        return $result;
    }
}