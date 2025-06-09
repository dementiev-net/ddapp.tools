<?php

use Bitrix\Main\Loader;

define("DD_MODULE_NAMESPACE", "dd.tools");

Loader::registerAutoLoadClasses(DD_MODULE_NAMESPACE,
    array(
        // Основной класс модуля
        "DD\\Tools\\Main" => "lib/Main.php",

        // Эвенты
        "DD\\Tools\\Events\\AdminEvents" => "lib/events/AdminEvents.php",
        "DD\\Tools\\Events\\PageEvents" => "lib/events/PageEvents.php",

        // ORM сущности
        "DD\\Tools\\Entity\\DataTable" => "lib/entity/DataTable.php",
        "DD\\Tools\\Entity\\AuthorTable" => "lib/entity/AuthorTable.php",

        // Helper-классы
        "DD\\Tools\\Helpers\\DateHelper" => "lib/helpers/DateHelper.php",
        "DD\\Tools\\Helpers\\FileHelper" => "lib/helpers/FileHelper.php",
        "DD\\Tools\\Helpers\\UserHelper" => "lib/helpers/UserHelper.php",
        "DD\\Tools\\Helpers\\ArrayHelper" => "lib/helpers/ArrayHelper.php",
        "DD\\Tools\\Helpers\\ValidationHelper" => "lib/helpers/ValidationHelper.php",
        "DD\\Tools\\Helpers\\LogHelper" => "lib/helpers/LogHelper.php",
        "DD\\Tools\\Helpers\\CacheHelper" => "lib/helpers/CacheHelper.php",
        "DD\\Tools\\Helpers\\UrlHelper" => "lib/helpers/UrlHelper.php"
    )
);