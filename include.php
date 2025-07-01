<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

require_once __DIR__ . "/vendor/autoload.php";

Loader::registerAutoLoadClasses("dd.tools", [

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
        "DD\\Tools\\Helpers\\DateHelper" => "lib/helpers/DateHelper.php",
        "DD\\Tools\\Helpers\\FileHelper" => "lib/helpers/FileHelper.php",
        "DD\\Tools\\Helpers\\UserHelper" => "lib/helpers/UserHelper.php",
        "DD\\Tools\\Helpers\\ArrayHelper" => "lib/helpers/ArrayHelper.php",
        "DD\\Tools\\Helpers\\ValidationHelper" => "lib/helpers/ValidationHelper.php",
        "DD\\Tools\\Helpers\\LogHelper" => "lib/helpers/LogHelper.php",
        "DD\\Tools\\Helpers\\IblockHelper" => "lib/helpers/IblockHelper.php",
        "DD\\Tools\\Helpers\\CacheHelper" => "lib/helpers/CacheHelper.php",
        "DD\\Tools\\Helpers\\UrlHelper" => "lib/helpers/UrlHelper.php"
    ]
);

if (!function_exists('custom_mail') && Option::get("dd.tools", "smtp_enabled") == "Y") {
    function custom_mail($to, $subject, $message, $additional_headers = "", $additional_parameters = "")
    {
        //$smtp = new CWebprostorSmtp(false, $additional_headers);
        //$result = $smtp->SendMail($to, $subject, $message, $additional_headers, $additional_parameters);

//        $mailer = new \DD\Tools\CustomMail();
//        $result = $mailer->send([
//            'to' => ['info@dementiev.net'],
//            //'cc' => 'copy@example.com',
//            'subject' => 'Письмо с вложением',
//            'html_body' => '<h1>HTML письмо</h1><p>Содержание</p>',
//            'text_body' => 'Альтернативный текст',
////    'attachments' => [
////        '/path/to/file.pdf',
////        [
////            'path' => '/path/to/image.jpg',
////            'name' => 'photo.jpg',
////            'type' => 'image/jpeg'
////        ]
////    ],
//            'headers' => [
//                'X-Custom-Header' => 'Custom Value'
//            ]
//        ]);

//        echo "<pre>11";
//        print_r($result);
//        die;
        //if ($result) {
        //    return true;
        //} else {
        //    return false;
        //}
    }
}