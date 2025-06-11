<?php

namespace DD\Tools\Entity;

use Bitrix\Main\Entity;

class AuthorTable extends Entity\DataManager
{

    /**
     * @return string
     */
    public static function getTableName()
    {
        return "pop_up_authors";
    }

    /**
     * @return array
     */
    public static function getMap()
    {
        return [
            new Entity\IntegerField(
                "ID",
                [
                    "primary" => true,
                    "autocomplete" => true,
                ]
            ),
            new Entity\StringField(
                "NAME",
                [
                    "required" => true,
                ]
            ),
            new Entity\StringField("LAST_NAME")
        ];
    }
}