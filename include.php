<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use DD\Tools\Main;
use DD\Tools\Helpers\LogHelper;
use DD\Tools\CustomMail;

require_once __DIR__ . "/vendor/autoload.php";

Loader::registerAutoLoadClasses(Main::MODULE_ID, [

        // Классы
        "DD\\Tools\\Main" => "lib/Main.php",
        "DD\\Tools\\CustomMail" => "lib/CustomMail.php",
        "DD\\Tools\\Maintenance" => "lib/Maintenance.php",
        "DD\\Tools\\DataExport" => "lib/DataExport.php",

        // Классы событий
        "DD\\Tools\\Events" => "lib/Events.php",
        "DD\\Tools\\Events\\AdminEvents" => "lib/events/AdminEvents.php",
        "DD\\Tools\\Events\\PageEvents" => "lib/events/PageEvents.php",
        "DD\\Tools\\Events\\LoginEvents" => "lib/events/LoginEvents.php",
        "DD\\Tools\\Events\\ContentEvents" => "lib/events/ContentEvents.php",

        // ORM сущности
        "DD\\Tools\\Entity\\MaintenanceTable" => "lib/entity/MaintenanceTable.php",
        "DD\\Tools\\Entity\\DataExportTable" => "lib/entity/DataExportTable.php",

        // Агенты
        "DD\\Tools\\cacheAgent" => "lib/agents/CacheAgent.php",
        "DD\\Tools\\freespaceAgent" => "lib/agents/FreespaceAgent.php",

        // Helper-классы
        "DD\\Tools\\Helpers\\FileHelper" => "lib/helpers/FileHelper.php",
        "DD\\Tools\\Helpers\\UserHelper" => "lib/helpers/UserHelper.php",
        "DD\\Tools\\Helpers\\LogHelper" => "lib/helpers/LogHelper.php",
        "DD\\Tools\\Helpers\\IblockHelper" => "lib/helpers/IblockHelper.php",
        "DD\\Tools\\Helpers\\CacheHelper" => "lib/helpers/CacheHelper.php",
        "DD\\Tools\\Helpers\\UrlHelper" => "lib/helpers/UrlHelper.php"
    ]
);

if (!function_exists('custom_mail') && Option::get(Main::MODULE_ID, "smtp_enabled") == "Y") {
    function custom_mail($to, $subject, $message, $additional_headers = "", $additional_parameters = ""): bool
    {
        // Настройка логирования
        LogHelper::configure();

        $mailer = new CustomMail();
        $result = $mailer->mailStyle($to, $subject, $message, $additional_headers, $additional_parameters);

        if ($result["success"]) {
            return true;
        } else {
            LogHelper::error("mail", $result["message"], $result["debug"]);
            return false;
        }
    }
}