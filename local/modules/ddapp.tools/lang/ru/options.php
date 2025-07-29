<?php
// Вкладки
$MESS["DDAPP_TOOLS_TAB1"] = "Настройки";
$MESS["DDAPP_TOOLS_TAB1_TITLE"] = "Настройка параметров модуля";
$MESS["DDAPP_TOOLS_TAB2"] = "Логирование";
$MESS["DDAPP_TOOLS_TAB2_TITLE"] = "Настройка логирования модуля";
$MESS["DDAPP_TOOLS_TAB3"] = "Свободное место";
$MESS["DDAPP_TOOLS_TAB3_TITLE"] = "Настройка мониторинга остатка места на диске";
$MESS["DDAPP_TOOLS_TAB4"] = "SMTP почта";
$MESS["DDAPP_TOOLS_TAB4_TITLE"] = "Настройка отправки писем через SMTP";
$MESS["DDAPP_TOOLS_TAB5"] = "Аналитика";
$MESS["DDAPP_TOOLS_TAB5_TITLE"] = "Настройка отправки аналитики";

// Блоки
$MESS["DDAPP_TOOLS_BLOCK1"] = "Основные настройки";
$MESS["DDAPP_TOOLS_BLOCK2"] = "Настройки ротации";
$MESS["DDAPP_TOOLS_BLOCK3"] = "Настройки email уведомлений при критических ошибках";
$MESS['DDAPP_TOOLS_BLOCK4'] = "Настройки ограничения места";
$MESS['DDAPP_TOOLS_BLOCK5'] = "Авторизация";
$MESS['DDAPP_TOOLS_BLOCK6'] = "Настройка DKIM подписи";
$MESS['DDAPP_TOOLS_BLOCK7'] = "Google reCAPTCHA v3";
$MESS['DDAPP_TOOLS_BLOCK8'] = "Yandex SmartCaptcha";

// Настройки
$MESS["DDAPP_TOOLS_MAINT_PERIOD"] = "Периодичность плана обслуживания, дни:";
$MESS["DDAPP_TOOLS_CACHE_PERIOD"] = "Периодичность очистки кеша:";
$MESS["DDAPP_TOOLS_CACHE_PERIOD_DEFAULT"] = [
    "0" => "Никогда",
    "1" => "День",
    "2" => "Неделя",
    "3" => "Месяц",
    "4" => "Год"
];
$MESS["DDAPP_TOOLS_CACHE_SIZE"] = "размер: ";
$MESS["DDAPP_TOOLS_EXPORT_STEP"] = "Шаг экспорта за раз, шт.:";
$MESS["DDAPP_TOOLS_USE_CAPTCHA"] = "Использовать защиту:";
$MESS["DDAPP_TOOLS_USE_CAPTCHA_DEFAULT"] = [
    "0" => "Ничего",
    "1" => "Bitrix Captcha",
    "2" => "Google reCAPTCHA v3",
    "3" => "Yandex SmartCaptcha",
];
$MESS["DDAPP_TOOLS_GOOGLE_RECAPTCHA_PUBLIC_KEY"] = "Публичный ключ:";
$MESS["DDAPP_TOOLS_GOOGLE_RECAPTCHA_SECRET_KEY"] = "Секретный ключ:";
$MESS["DDAPP_TOOLS_YANDEX_CAPTCHA_CLIENT_KEY"] = "Ключ клиента:";
$MESS["DDAPP_TOOLS_YANDEX_CAPTCHA_SERVER_KEY"] = "Ключ сервера:";
$MESS["DDAPP_TOOLS_YANDEX_CAPTCHA_INVISIBLE"] = "Невидимая капча:";
$MESS["DDAPP_TOOLS_YANDEX_CAPTCHA_SHIELD_CUSTOM"] = "Скрыть шильдик (использовать кастомный):";
$MESS["DDAPP_TOOLS_YANDEX_CAPTCHA_SHIELD_POSITION"] = "Расположение шильдика на странице:";
$MESS["DDAPP_TOOLS_YANDEX_CAPTCHA_SHIELD_POSITION_DEFAULT"] = [
    "1" => "Верхний левый угол",
    "2" => "По центру слева",
    "3" => "Нижний левый угол",
    "4" => "Верхний правый угол",
    "5" => "По центру справа",
    "6" => "Нижний правый угол",
];

// Логирование
$MESS["DDAPP_TOOLS_LOG_ENABLED"] = "Активность:";
$MESS["DDAPP_TOOLS_LOG_MIN_LEVEL"] = "Минимальный уровень логирования:";
$MESS["DDAPP_TOOLS_LOG_MIN_LEVEL_DEFAULT"] = [
    "1" => "DEBUG",
    "2" => "INFO",
    "3" => "WARNING",
    "4" => "ERROR",
    "5" => "CRITICAL",
];
$MESS["DDAPP_TOOLS_LOG_PATH"] = "Путь до папки с логами:";
$MESS["DDAPP_TOOLS_LOG_PATH_DEFAULT"] = "/upload/logs/";
$MESS["DDAPP_TOOLS_LOG_DATE_FORMAT"] = "Формат даты в логах:";
$MESS["DDAPP_TOOLS_LOG_DATE_FORMAT_DEFAULT"] = "d.m.Y H:i:s";
$MESS["DDAPP_TOOLS_LOG_MAX_FILE_SIZE"] = "Максимальный размер файла:";
$MESS["DDAPP_TOOLS_LOG_MAX_FILE_SIZE_DEFAULT"] = "5242880";
$MESS["DDAPP_TOOLS_LOG_MAX_FILES"] = "Количество файлов:";
$MESS["DDAPP_TOOLS_LOG_MAX_FILES_DEFAULT"] = "20";
$MESS["DDAPP_TOOLS_LOG_EMAIL_ENABLED"] = "Отправлять письмо:";
$MESS["DDAPP_TOOLS_LOG_EMAIL"] = "E-Mail:";
$MESS["DDAPP_TOOLS_LOG_EMAIL_DEFAULT"] = "admin@yoursite.ru";

// Свободное место
$MESS["DDAPP_TOOLS_DISK_ENABLED"] = "Активность:";
$MESS["DDAPP_TOOLS_DISK_DELETE_CACHE"] = "Удалять кэш при малом месте:";
$MESS["DDAPP_TOOLS_DISK_EMAIL_ENABLED"] = "Отправлять письмо:";
$MESS["DDAPP_TOOLS_DISK_EMAIL"] = "E-Mail:";
$MESS["DDAPP_TOOLS_DISK_EMAIL_DEFAULT"] = "admin@yoursite.ru";
$MESS['DDAPP_TOOLS_DISK_TYPE_FILESYSTEM'] = "Тип определения свободного места:";
$MESS['DDAPP_TOOLS_DISK_TYPE_FILESYSTEM_DEFAULT'] = [
    "1" => "Функции PHP",
    "2" => "Функция обхода папок"
];;
$MESS["DDAPP_TOOLS_DISK_FREE_SPACE"] = "Необходимый запас свободного места, Мб:";
$MESS["DDAPP_TOOLS_DISK_FREE_SPACE_DEFAULT"] = "3000";
$MESS['DDAPP_TOOLS_DISK_ALL_SPACE'] = "Всего выделено места, Мб:";
$MESS['DDAPP_TOOLS_DISK_ALL_SPACE_DEFAULT'] = "0";
$MESS['TYPE_FILESYSTEM_1'] = "Функции PHP";
$MESS['TYPE_FILESYSTEM_2'] = "Функция обхода папок";

// SMTP почта
$MESS["DDAPP_TOOLS_SMTP_ENABLED"] = "Отправлять письма через SMTP:";
$MESS["DDAPP_TOOLS_SMTP_HOST"] = "SMTP сервер:";
$MESS["DDAPP_TOOLS_SMTP_SMTP_SECURE"] = "Тип защищенного соединения:";
$MESS["DDAPP_TOOLS_SMTP_SMTP_SECURE_DEFAULT"] = [
    "0" => "Без авторизации",
    "1" => "SSL",
    "2" => "TLS",
];
$MESS["DDAPP_TOOLS_SMTP_PORT"] = "Порт:";
$MESS["DDAPP_TOOLS_SMTP_LOGIN"] = "Логин от почты:";
$MESS["DDAPP_TOOLS_SMTP_PASSWORD"] = "Пароль от почты:";
$MESS["DDAPP_TOOLS_SMTP_EMAIL_SENDER"] = "E-mail адрес отправителя:";
$MESS["DDAPP_TOOLS_SMTP_EMAIL_SENDER_DEFAULT"] = "admin@yoursite.ru";
$MESS["DDAPP_TOOLS_SMTP_NAME_SENDER"] = "Имя отправителя:";
$MESS["DDAPP_TOOLS_SMTP_NAME_SENDER_DEFAULT"] = "yoursite.ru";
$MESS["DDAPP_TOOLS_SMTP_DKIM_ENABLED"] = "Использовать цифровую подпись:";
$MESS["DDAPP_TOOLS_SMTP_DKIM_DOMAIN"] = "Домен:";
$MESS["DDAPP_TOOLS_SMTP_DKIM_DOMAIN_DEFAULT"] = "yoursite.ru";
$MESS["DDAPP_TOOLS_SMTP_DKIM_SELECTOR"] = "Селектор:";
$MESS["DDAPP_TOOLS_SMTP_DKIM_SELECTOR_DEFAULT"] = "mail";
$MESS["DDAPP_TOOLS_SMTP_DKIM_PASSPHRASE"] = "Ключевая фраза:";
$MESS["DDAPP_TOOLS_SMTP_DKIM_PRIVATE_KEY"] = "Приватный ключ:";
$MESS["DDAPP_TOOLS_SMTP_DKIM_PRIVATE_KEY_DEFAULT"] = "-----BEGIN RSA PRIVATE KEY-----";
$MESS["DDAPP_TOOLS_SMTP_TEST"] = "Тестировать соединение";
$MESS["DDAPP_TOOLS_SMTP_TEST_LOADING"] = "Тестирование SMTP...";
$MESS["DDAPP_TOOLS_SMTP_TEST_SUCCESS"] = "SMTP тест пройден успешно";
$MESS["DDAPP_TOOLS_SMTP_TEST_ERROR"] = "Ошибка: ";
$MESS["DDAPP_TOOLS_SMTP_TEST_ERROR_AJAX"] = "Ошибка AJAX-запроса";

// Аналитика
$MESS["DDAPP_TOOLS_ANALYTICS_ENABLED"] = "Включить аналитику:";
$MESS["DDAPP_TOOLS_ANALYTICS_GA_MEASUREMENT_ID"] = "ID Google Analytics:";
$MESS["DDAPP_TOOLS_ANALYTICS_YANDEX_METRIKA_ID"] = "ID Яндекс.Метрики:";
$MESS["DDAPP_TOOLS_ANALYTICS_VK_PIXEL_ID"] = "ID VK Pixel:";

// Кнопки
$MESS["DDAPP_TOOLS_BTN_APPLY"] = "Применить";
$MESS["DDAPP_TOOLS_BTN_DEFAULT"] = "По умолчанию";

// Подсказки
$MESS["DDAPP_TOOLS_HELP_TAB1"] = "";
$MESS["DDAPP_TOOLS_HELP_TAB2"] = "
<h4>Подключение:</h4>
<pre>
use DDAPP\Tools\Helpers\LogHelper;

LogHelper::configure();
        
или
        
LogHelper::configure([
    'log_enabled' => 'Y',
    'log_min_level' => LogHelper::LEVEL_INFO,
    'log_path' => '/local/logs/custom/',
    'log_date_format' => 'd.m.Y H:i:s',
    'log_max_file_size' => 5242880,
    'log_max_files' => 10,
    'log_critical_email' => [
        'enabled' => 'Y',
        'email' => 'admin@yoursite.ru',
    ]
]);
</pre>
<h4>Обычное логирование:</h4>
<pre>
LogHelper::info('user_actions', 'User logged in', ['user_id' => 123]);
LogHelper::error('payment', 'Payment processing failed', ['order_id' => 456]);
LogHelper::warning('payment', 'Invalid password', ['login' => 'admin']);
LogHelper::debug('payment', 'Invalid password', ['login' => 'admin']);
</pre>
<h4>Критическая ошибка - автоматически отправит email:</h4>
<pre>
LogHelper::critical('system', 'Database connection lost', [
    'host' => 'localhost',
    'error' => 'Connection timeout'
]);
</pre>
<h4>Логирование исключений:</h4>
<pre>
try {
    // некий код
} catch (Exception \$e) {
    LogHelper::exception('api', \$e, ['method' => 'getUserData']);
}
</pre>
<h4>Измерение производительности:</h4>
<pre>
\$result = LogHelper::timeExecution('performance', 'heavy_database_query', function() {
    // тяжелый запрос к БД
    return \$someResult;
});
</pre>
<h4>Очистка старых логов (можно добавить в cron):</h4>
<pre>
LogHelper::cleanOldLogs('system', 30); // удалить логи старше 30 дней
</pre>";
$MESS["DDAPP_TOOLS_HELP_TAB3"] = "";
$MESS["DDAPP_TOOLS_HELP_TAB4"] = "
<h4>Генерация приватного ключа:</h4>
<pre>
openssl genrsa -out dkim_private.key 2048
</pre>
<h4>Генерация публичного ключа:</h4>
<pre>
openssl rsa -in dkim_private.key -pubout -out dkim_public.key
</pre>
<h4>Создание DNS записи (пример для селектора \"default\"):</h4>
<pre>
# Тип: TXT
# Имя: default._domainkey.yoursite.ru
# Значение: v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...
</pre>";
$MESS["DDAPP_TOOLS_HELP_TAB5"] = "";