<?php
$MESS["DD_MODULE_OPTIONS_HELP_edit1"] = "";
$MESS["DD_MODULE_OPTIONS_HELP_edit2"] = "
<h4>Подключение:</h4>
<pre>
use DD\Tools\Helpers\LogHelper;

LogHelper::configure();
        
или
        
LogHelper::configure(array(
    'log_enabled' => 'Y',
    'log_min_level' => LogHelper::LEVEL_INFO,
    'log_path' => '/local/logs/custom/',
    'log_date_format' => 'd.m.Y H:i:s',
    'log_max_file_size' => 5242880,
    'log_max_files' => 10,
    'log_critical_email' => array(
        'enabled' => 'Y',
        'template_code' => 'DD_TOOLS_CRITICAL_ERROR', // Автоматически создается при установке
        'email' => 'admin@yoursite.com', // ОБЯЗАТЕЛЬНО укажите email администратора
    )
));
</pre>
<h4>Обычное логирование:</h4>
<pre>
LogHelper::info('user_actions', 'User logged in', array('user_id' => 123));
LogHelper::error('payment', 'Payment processing failed', array('order_id' => 456));
LogHelper::warning('payment', 'Invalid password', array('login' => 'admin'));
LogHelper::debug('payment', 'Invalid password', array('login' => 'admin'));
</pre>
<h4>Критическая ошибка - автоматически отправит email:</h4>
<pre>
LogHelper::critical('system', 'Database connection lost', array(
    'host' => 'localhost',
    'error' => 'Connection timeout'
));
</pre>
<h4>Логирование исключений:</h4>
<pre>
try {
    // некий код
} catch (Exception \$e) {
    LogHelper::exception('api', \$e, array('method' => 'getUserData'));
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