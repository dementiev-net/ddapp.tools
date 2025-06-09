<?php

namespace DD\Tools;

use DD\Tools\Entity\DataTable;
use Bitrix\Main\Entity\Event;

class Main
{
    /**
     * Метод для получения строки из таблицы базы данных
     * @return mixed
     */
    public static function get()
    {
        $result = DataTable::getList(
            array(
                "select" => array("*")
            )
        );

        $row = $result->fetch();

        print "<pre>";
        print_r($row);
        print "</pre>";

        return $row;
    }
}