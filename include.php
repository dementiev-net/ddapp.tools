<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses("dd.tools",
    array(
        // Основной класс модуля
        "DD\\Tools\\Main" => "lib/Main.php",

        // Классы событий
        "DD\\Tools\\Events" => "lib/Events.php",
        "DD\\Tools\\Events\\AdminEvents" => "lib/events/AdminEvents.php",
        "DD\\Tools\\Events\\PageEvents" => "lib/events/PageEvents.php",
        "DD\\Tools\\Events\\LoginEvents" => "lib/events/LoginEvents.php",
        "DD\\Tools\\Events\\ContentEvents" => "lib/events/ContentEvents.php",

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