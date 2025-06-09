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
        return array(
            new Entity\IntegerField(
                "ID",
                array(
                    "primary" => true,
                    "autocomplete" => true,
                )
            ),
            new Entity\StringField(
                "NAME",
                array(
                    "required" => true,
                )
            ),
            new Entity\StringField("LAST_NAME")
        );
    }
}