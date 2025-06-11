<?php
$MESS["DD_TOOLS_TAB2"] = "Логирование";
$MESS["DD_TOOLS_TAB2_TITLE"] = "Настройка логирования модуля";
$MESS["DD_TOOLS_TAB3"] = "Свободное место";
$MESS["DD_TOOLS_TAB3_TITLE"] = "Настройка мониторинга остатка места на диске";

$MESS["DD_TOOLS_BLOCK1"] = "Основные настройки";
$MESS["DD_TOOLS_BLOCK2"] = "Настройки ротации";
$MESS["DD_TOOLS_BLOCK3"] = "Настройки email уведомлений при критических ошибках";
$MESS['DD_TOOLS_BLOCK4'] = "Настройки ограничения места";

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
$MESS["DD_TOOLS_DISK_AGENT_TIME"] = "Выполнять проверку каждые, сек";
$MESS["DD_TOOLS_DISK_AGENT_TIME_DEFAULT"] = "3600";
$MESS['DD_TOOLS_DISK_TYPE_FILESYSTEM'] = "Тип определения свободного места";
$MESS['DD_TOOLS_DISK_TYPE_FILESYSTEM_DEFAULT'] = [
    "1" => "Функции disk_free_space(), disk_total_space()",
    "2" => "Функция обхода всех папок и файлов"
];;
$MESS["DD_TOOLS_DISK_FREE_SPACE"] = "Необходимый запас свободного места, Мб";
$MESS["DD_TOOLS_DISK_FREE_SPACE_DEFAULT"] = "300";
$MESS['DD_TOOLS_DISK_ALL_SPACE'] = "Всего выделено места, Мб";
$MESS['DD_TOOLS_DISK_ALL_SPACE_DEFAULT'] = "0";
$MESS['TYPE_FILESYSTEM_1'] = "Функции disk_free_space(), disk_total_space()";
$MESS['TYPE_FILESYSTEM_2'] = "Функция обхода всех папок и файлов";

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
        'email' => 'admin@yoursite.com',
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