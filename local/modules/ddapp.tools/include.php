<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use DDAPP\Tools\Main;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\CustomMail;

require_once __DIR__ . "/vendor/autoload.php";

Loader::registerAutoLoadClasses(Main::MODULE_ID, [

        // Классы
        "DDAPP\\Tools\\Main" => "lib/Main.php",
        "DDAPP\\Tools\\CustomMail" => "lib/CustomMail.php",
        "DDAPP\\Tools\\Maintenance" => "lib/Maintenance.php",
        "DDAPP\\Tools\\DataExport" => "lib/DataExport.php",
        "DDAPP\\Tools\\DataImages" => "lib/DataImages.php",
        // TODO: ПОМЕНЯТЬ!!!!!
        "DDAPP\\Tools\\Forms\\RateLimiter" => "../../components/ddapp.tools/forms/classes/Forms/RateLimiter.php",
        "DDAPP\\Tools\\Forms\\FileSecurityValidator" => "../../components/ddapp.tools/forms/classes/Forms/FileSecurityValidator.php",

        // Классы событий
        "DDAPP\\Tools\\Events" => "lib/Events.php",
        "DDAPP\\Tools\\Events\\AdminEvents" => "lib/events/AdminEvents.php",
        "DDAPP\\Tools\\Events\\PageEvents" => "lib/events/PageEvents.php",
        "DDAPP\\Tools\\Events\\LoginEvents" => "lib/events/LoginEvents.php",
        "DDAPP\\Tools\\Events\\ContentEvents" => "lib/events/ContentEvents.php",

        // ORM сущности
        "DDAPP\\Tools\\Entity\\MaintenanceTable" => "lib/entity/MaintenanceTable.php",
        "DDAPP\\Tools\\Entity\\DataExportTable" => "lib/entity/DataExportTable.php",
        "DDAPP\\Tools\\Entity\\DataImportTable" => "lib/entity/DataImportTable.php",
        "DDAPP\\Tools\\Entity\\DataImagesTable" => "lib/entity/DataImagesTable.php",

        // Агенты
        "DDAPP\\Tools\\cacheAgent" => "lib/agents/CacheAgent.php",
        "DDAPP\\Tools\\freespaceAgent" => "lib/agents/FreespaceAgent.php",

        // Helper-классы
        "DDAPP\\Tools\\Helpers\\FileHelper" => "lib/helpers/FileHelper.php",
        "DDAPP\\Tools\\Helpers\\UserHelper" => "lib/helpers/UserHelper.php",
        "DDAPP\\Tools\\Helpers\\LogHelper" => "lib/helpers/LogHelper.php",
        "DDAPP\\Tools\\Helpers\\IblockHelper" => "lib/helpers/IblockHelper.php",
        "DDAPP\\Tools\\Helpers\\CacheHelper" => "lib/helpers/CacheHelper.php",
        "DDAPP\\Tools\\Helpers\\UrlHelper" => "lib/helpers/UrlHelper.php"
    ]
);

if (!function_exists('custom_mail') && Option::get(Main::MODULE_ID, "smtp_enabled") == "Y") {
    function custom_mail($to, $subject, $message, $additional_headers = "", $additional_parameters = ""): bool
    {
        // Настройка логирования
        LogHelper::configure();

        $mailer = new CustomMail();
        $result = $mailer->send($to, $subject, $message, $additional_headers, $additional_parameters);

        if ($result["success"]) {
            return true;
        } else {
            LogHelper::error("mail", $result["message"], $result["debug"]);
            return false;
        }
    }
}