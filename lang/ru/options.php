<?php
$MESS["DD_TOOLS_TAB1"] = "Настройки";
$MESS["DD_TOOLS_TAB1_TITLE"] = "Настройка параметров модуля";
$MESS["DD_TOOLS_TAB2"] = "Логирование";
$MESS["DD_TOOLS_TAB2_TITLE"] = "Настройка логирования модуля";
$MESS["DD_TOOLS_TAB3"] = "Свободное место";
$MESS["DD_TOOLS_TAB3_TITLE"] = "Настройка мониторинга остатка места на диске";
$MESS["DD_TOOLS_TAB4"] = "SMTP почта";
$MESS["DD_TOOLS_TAB4_TITLE"] = "Настройка отправки писем через SMTP";

$MESS["DD_TOOLS_BLOCK1"] = "Основные настройки";
$MESS["DD_TOOLS_BLOCK2"] = "Настройки ротации";
$MESS["DD_TOOLS_BLOCK3"] = "Настройки email уведомлений при критических ошибках";
$MESS['DD_TOOLS_BLOCK4'] = "Настройки ограничения места";
$MESS['DD_TOOLS_BLOCK5'] = "Авторизация";
$MESS['DD_TOOLS_BLOCK6'] = "Настройка DKIM подписи";

$MESS["DD_TOOLS_MAINT_PERIOD"] = "Периодичность плана обслуживания, дни";
$MESS["DD_TOOLS_CACHE_PERIOD"] = "Периодичность очистки кеша";
$MESS["DD_TOOLS_CACHE_PERIOD_DEFAULT"] = [
    "0" => "Никогда",
    "1" => "День",
    "2" => "Неделя",
    "3" => "Месяц",
    "4" => "Год"
];
$MESS["DD_TOOLS_CACHE_SIZE"] = "размер: ";
$MESS["DD_TOOLS_EXPORT_STEP"] = "Шаг экспорта за раз, шт.: ";

$MESS["DD_TOOLS_LOG_ENABLED"] = "Активность";
$MESS["DD_TOOLS_LOG_MIN_LEVEL"] = "Минимальный уровень логирования";
$MESS["DD_TOOLS_LOG_MIN_LEVEL_DEFAULT"] = [
    "1" => "DEBUG",
    "2" => "INFO",
    "3" => "WARNING",
    "4" => "ERROR",
    "5" => "CRITICAL",
];
$MESS["DD_TOOLS_LOG_PATH"] = "Путь до папки с логами";
$MESS["DD_TOOLS_LOG_PATH_DEFAULT"] = "/upload/logs/";
$MESS["DD_TOOLS_LOG_DATE_FORMAT"] = "Формат даты в логах";
$MESS["DD_TOOLS_LOG_DATE_FORMAT_DEFAULT"] = "d.m.Y H:i:s";
$MESS["DD_TOOLS_LOG_MAX_FILE_SIZE"] = "Максимальный размер файла";
$MESS["DD_TOOLS_LOG_MAX_FILE_SIZE_DEFAULT"] = "5242880";
$MESS["DD_TOOLS_LOG_MAX_FILES"] = "Количество файлов";
$MESS["DD_TOOLS_LOG_MAX_FILES_DEFAULT"] = "20";
$MESS["DD_TOOLS_LOG_EMAIL_ENABLED"] = "Отправлять письмо";
$MESS["DD_TOOLS_LOG_EMAIL"] = "E-Mail";
$MESS["DD_TOOLS_LOG_EMAIL_DEFAULT"] = "admin@yoursite.ru";

$MESS["DD_TOOLS_DISK_ENABLED"] = "Активность";
$MESS["DD_TOOLS_DISK_DELETE_CACHE"] = "Удалять кэш при малом месте";
$MESS["DD_TOOLS_DISK_EMAIL_ENABLED"] = "Отправлять письмо";
$MESS["DD_TOOLS_DISK_EMAIL"] = "E-Mail";
$MESS["DD_TOOLS_DISK_EMAIL_DEFAULT"] = "admin@yoursite.ru";
$MESS['DD_TOOLS_DISK_TYPE_FILESYSTEM'] = "Тип определения свободного места";
$MESS['DD_TOOLS_DISK_TYPE_FILESYSTEM_DEFAULT'] = [
    "1" => "Функции PHP",
    "2" => "Функция обхода папок"
];;
$MESS["DD_TOOLS_DISK_FREE_SPACE"] = "Необходимый запас свободного места, Мб";
$MESS["DD_TOOLS_DISK_FREE_SPACE_DEFAULT"] = "3000";
$MESS['DD_TOOLS_DISK_ALL_SPACE'] = "Всего выделено места, Мб";
$MESS['DD_TOOLS_DISK_ALL_SPACE_DEFAULT'] = "0";
$MESS['TYPE_FILESYSTEM_1'] = "Функции PHP";
$MESS['TYPE_FILESYSTEM_2'] = "Функция обхода папок";

$MESS["DD_TOOLS_SMTP_ENABLED"] = "Отправлять письма через SMTP";
$MESS["DD_TOOLS_SMTP_HOST"] = "SMTP сервер";
$MESS["DD_TOOLS_SMTP_SMTP_SECURE"] = "Тип защищенного соединения";
$MESS["DD_TOOLS_SMTP_SMTP_SECURE_DEFAULT"] = [
    "0" => "Без авторизации",
    "1" => "SSL",
    "2" => "TLS",
];
$MESS["DD_TOOLS_SMTP_PORT"] = "Порт";
$MESS["DD_TOOLS_SMTP_LOGIN"] = "Логин от почты";
$MESS["DD_TOOLS_SMTP_PASSWORD"] = "Пароль от почты";
$MESS["DD_TOOLS_SMTP_EMAIL_SENDER"] = "E-mail адрес отправителя";
$MESS["DD_TOOLS_SMTP_EMAIL_SENDER_DEFAULT"] = "admin@yoursite.ru";
$MESS["DD_TOOLS_SMTP_NAME_SENDER"] = "Имя отправителя";
$MESS["DD_TOOLS_SMTP_NAME_SENDER_DEFAULT"] = "yoursite.ru";
$MESS["DD_TOOLS_SMTP_DKIM_ENABLED"] = "Использовать цифровую подпись";
$MESS["DD_TOOLS_SMTP_DKIM_DOMAIN"] = "Домен";
$MESS["DD_TOOLS_SMTP_DKIM_DOMAIN_DEFAULT"] = "yoursite.ru";
$MESS["DD_TOOLS_SMTP_DKIM_SELECTOR"] = "Селектор";
$MESS["DD_TOOLS_SMTP_DKIM_SELECTOR_DEFAULT"] = "mail";
$MESS["DD_TOOLS_SMTP_DKIM_PASSPHRASE"] = "Ключевая фраза";
$MESS["DD_TOOLS_SMTP_DKIM_PRIVATE_KEY"] = "Приватный ключ";
$MESS["DD_TOOLS_SMTP_DKIM_PRIVATE_KEY_DEFAULT"] = "-----BEGIN RSA PRIVATE KEY-----";
$MESS["DD_TOOLS_SMTP_TEST"] = "Тестировать соединение";
$MESS["DD_TOOLS_SMTP_TEST_LOADING"] = "Тестирование SMTP...";
$MESS["DD_TOOLS_SMTP_TEST_SUCCESS"] = "SMTP тест пройден успешно";
$MESS["DD_TOOLS_SMTP_TEST_ERROR"] = "Ошибка: ";
$MESS["DD_TOOLS_SMTP_TEST_ERROR_AJAX"] = "Ошибка AJAX-запроса";

$MESS["DD_TOOLS_BTN_APPLY"] = "Применить";
$MESS["DD_TOOLS_BTN_DEFAULT"] = "По умолчанию";

$MESS["DD_TOOLS_HELP_TAB1"] = "";
$MESS["DD_TOOLS_HELP_TAB2"] = "
<h4>Подключение:</h4>
<pre>
use DD\Tools\Helpers\LogHelper;

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
$MESS["DD_TOOLS_HELP_TAB3"] = "";
$MESS["DD_TOOLS_HELP_TAB4"] = "
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