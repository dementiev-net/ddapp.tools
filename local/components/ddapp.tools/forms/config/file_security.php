<?php
/**
 * Конфигурация безопасности для загрузки файлов
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

return [
    // Основные настройки
    "max_file_size" => 10 * 1024 * 1024, // 10MB в байтах
    "max_files_count" => 10, // Максимальное количество файлов за раз

    // Разрешенные расширения (в нижнем регистре)
    "allowed_extensions" => [
        // Изображения
        "jpg", "jpeg", "png", "gif", "webp", "svg",
        // Документы
        "pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx",
        "odt", "ods", "odp", "rtf",
        // Текстовые файлы
        "txt", "csv",
        // Архивы
        "zip", "rar", "7z", "tar", "gz",
        // Аудио/Видео (если необходимо)
        "mp3", "wav", "mp4", "avi", "mkv",
    ],

    // Запрещенные расширения (критически опасные)
    "forbidden_extensions" => [
        // Исполняемые файлы
        "exe", "bat", "cmd", "com", "scr", "pif", "vbs", "vbe",
        "js", "jse", "ws", "wsf", "wsc", "wsh", "ps1", "ps1xml",
        "ps2", "ps2xml", "psc1", "psc2", "msh", "msh1", "msh2",
        "mshxml", "msh1xml", "msh2xml",
        // Веб-скрипты
        "php", "php3", "php4", "php5", "phtml", "asp", "aspx",
        "jsp", "pl", "py", "rb", "cgi",
        // Системные файлы
        "dll", "sys", "drv", "ocx",
        // Макросы
        "xlsm", "xlsb", "xltm", "xla", "xlam", "docm", "dotm",
        "pptm", "potm", "ppam", "ppsm", "sldm",
    ],

    // MIME-типы для дополнительной проверки
    "mime_types" => [
        "jpg" => ["image/jpeg"],
        "jpeg" => ["image/jpeg"],
        "png" => ["image/png"],
        "gif" => ["image/gif"],
        "webp" => ["image/webp"],
        "svg" => ["image/svg+xml"],
        "pdf" => ["application/pdf"],
        "doc" => ["application/msword"],
        "docx" => ["application/vnd.openxmlformats-officedocument.wordprocessingml.document"],
        "xls" => ["application/vnd.ms-excel"],
        "xlsx" => ["application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"],
        "ppt" => ["application/vnd.ms-powerpoint"],
        "pptx" => ["application/vnd.openxmlformats-officedocument.presentationml.presentation"],
        "txt" => ["text/plain"],
        "csv" => ["text/csv", "application/csv"],
        "zip" => ["application/zip"],
        "rar" => ["application/x-rar-compressed"],
        "7z" => ["application/x-7z-compressed"],
        "mp3" => ["audio/mpeg"],
        "wav" => ["audio/wav"],
        "mp4" => ["video/mp4"],
        "avi" => ["video/x-msvideo"],
    ],

    // Паттерны для поиска вредоносного кода в файлах
    "malware_patterns" => [
        // PHP теги
        "/<\?php/i",
        "/<\?=/i",
        "/<script.*php/i",

        // JavaScript/HTML инъекции
        "/<script[^>]*>/i",
        "/<iframe[^>]*>/i",
        "/javascript:/i",
        "/data:text\/html/i",
        "/vbscript:/i",

        // Подозрительные функции
        "/eval\s*\(/i",
        "/exec\s*\(/i",
        "/system\s*\(/i",
        "/shell_exec\s*\(/i",
        "/base64_decode\s*\(/i",

        // SQL инъекции
        "/union\s+select/i",
        "/drop\s+table/i",
        "/insert\s+into/i",
        "/delete\s+from/i",
    ],

    // Настройки проверки содержимого файлов
    "content_checks" => [
        "enabled" => true,
        "max_check_size" => 1024 * 1024, // 1MB - максимальный размер для проверки содержимого
        "check_first_bytes" => 2048, // Количество первых байт для проверки
    ],

    // Настройки для изображений
    "image_checks" => [
        "enabled" => true,
        "max_width" => 4000,
        "max_height" => 4000,
        "check_exif" => true, // Проверка EXIF данных
        "strip_exif" => true, // Удаление EXIF данных
    ],

    // Настройки карантина подозрительных файлов
    "quarantine" => [
        "enabled" => true,
        "directory" => "/upload/quarantine/",
        "retention_days" => 30, // Сколько дней хранить файлы в карантине
    ],

    // Настройки логирования
    "logging" => [
        "enabled" => true,
        "log_uploads" => true,
        "log_rejections" => true,
        "log_quarantine" => true,
    ],

    // Антивирусная проверка (если доступна)
    "antivirus" => [
        "enabled" => false, // Включить при наличии ClamAV или другого сканера
        "command" => "/usr/bin/clamscan", // Путь к антивирусному сканеру
        "timeout" => 30, // Таймаут сканирования в секундах
    ],

    // Настройки для конкретных типов файлов
    "file_type_settings" => [
        "images" => [
            "convert_to_jpg" => false, // Принудительное конвертирование в JPG
            "resize_large" => true, // Автоматическое изменение размера больших изображений
            "max_size_for_resize" => 2 * 1024 * 1024, // 2MB
        ],
        "documents" => [
            "scan_for_macros" => true, // Сканирование документов на наличие макросов
            "remove_metadata" => true, // Удаление метаданных
        ],
        "archives" => [
            "scan_contents" => true, // Сканирование содержимого архивов
            "max_extraction_size" => 50 * 1024 * 1024, // 50MB
            "max_files_in_archive" => 100,
        ],
    ],

    // Настройки для разных уровней безопасности
    "security_levels" => [
        "low" => [
            "content_checks" => false,
            "antivirus" => false,
            "quarantine" => false,
        ],
        "medium" => [
            "content_checks" => true,
            "antivirus" => false,
            "quarantine" => true,
        ],
        "high" => [
            "content_checks" => true,
            "antivirus" => true,
            "quarantine" => true,
            "strict_mime_check" => true,
        ],
    ],

    // Текущий уровень безопасности
    "current_security_level" => "medium",
];