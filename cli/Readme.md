# DDAPP.Tools - CLI инструменты для Bitrix

Модуль ddapp.tools предоставляет набор CLI-инструментов для управления проектом на Bitrix.

## Управление кешем (cache.php)

```bash
cd /home/bitrix/www/local/modules/ddapp.tools/cli/
php cache.php help
```

## Система миграций (migrate.php)

```bash
cd /home/bitrix/www/local/modules/ddapp.tools/cli/
php migrate.php help
```

### Структура файла миграции

При создании миграции автоматически генерируется файл с такой структурой:

```php
<?php
/**
 * Миграция: AddUserTable
 * Создана: 2024-12-07 12:34:56
 */

use Bitrix\Main\Application;

class MigrationAddUserTable
{
    private $connection;
    
    public function __construct()
    {
        $this->connection = Application::getConnection();
    }
    
    /**
     * Выполнить миграцию
     */
    public function up()
    {
        // Код миграции
        // Например:
        // $this->connection->query("CREATE TABLE ...");
        // $this->connection->query("INSERT INTO ...");
        
        echo "Миграция AddUserTable выполнена\n";
    }
    
    /**
     * Откатить миграцию
     */
    public function down()
    {
        // Код отката миграции
        // Например:
        // $this->connection->query("DROP TABLE ...");
        // $this->connection->query("DELETE FROM ...");
        
        echo "Миграция AddUserTable откачена\n";
    }
}
```

## Рекомендации по использованию

### Кеш
- Используйте `php cache.php info` для мониторинга размера кеша
- Очищайте кеш после обновлений через `php cache.php clear`
- Для точечной очистки используйте `clear-type`

### Миграции
- Всегда тестируйте миграции на тестовом окружении
- Создавайте осмысленные имена миграций
- Обязательно реализуйте метод `down()` для возможности отката
- Используйте транзакции для сложных операций
- Проверяйте статус миграций перед деплоем

### Именование миграций
- Используйте snake_case: `addapp_user_table`, `update_iblock_properties`
- Начинайте с глагола: `create`, `add`, `update`, `remove`, `fix`
- Будьте описательными: `fix_user_permissions_for_catalog`

## Безопасность

- CLI-скрипты должны запускаться только из командной строки
- Миграции выполняются с правами пользователя, запускающего скрипт
- Всегда делайте резервные копии перед выполнением миграций
- Используйте `migrate.php reset` только в процессе разработки