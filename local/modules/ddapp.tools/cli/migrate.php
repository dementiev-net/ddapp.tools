<?php
/**
 * CLI скрипт для управления миграциями Bitrix
 * Использование: php migrate.php [action] [options]
 */

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/../../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;

class MigrationManager
{
    private $connection;
    private $migrationTable = 'ddapp_migrations';
    private $migrationDir;

    public function __construct()
    {
        $this->connection = Application::getConnection();
        $this->migrationDir = dirname(__FILE__) . '/../migrations';

        // Создаем директорию для миграций если её нет
        if (!is_dir($this->migrationDir)) {
            mkdir($this->migrationDir, 0755, true);
        }

        $this->createMigrationTable();
    }

    /**
     * Создать таблицу миграций
     */
    private function createMigrationTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->migrationTable}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `migration` (`migration`)
        )";

        $this->connection->query($sql);
    }

    /**
     * Создать новую миграцию
     */
    public function create($name)
    {
        $className = $this->toCamelCase($name);
        $timestamp = date('YmdHis');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $this->migrationDir . '/' . $filename;

        $template = $this->getMigrationTemplate($className);

        file_put_contents($filepath, $template);

        echo "Миграция создана: $filename\n";
        echo "Путь: $filepath\n";
        echo "Класс: Migration{$className}\n";
    }

    /**
     * Выполнить миграции
     */
    public function migrate($target = null)
    {
        $migrations = $this->getPendingMigrations();

        if (empty($migrations)) {
            echo "Нет миграций для выполнения.\n";
            return;
        }

        echo "Найдено миграций: " . count($migrations) . "\n";

        foreach ($migrations as $migration) {
            if ($target && $migration['name'] !== $target) {
                continue;
            }

            echo "Выполнение миграции: {$migration['name']}\n";

            try {
                $this->executeMigration($migration);
                $this->markAsExecuted($migration['name']);
                echo "✓ Миграция {$migration['name']} выполнена успешно\n";
            } catch (Exception $e) {
                echo "✗ Ошибка при выполнении миграции {$migration['name']}: " . $e->getMessage() . "\n";
                break;
            }

            if ($target) {
                break;
            }
        }

        echo "Миграции завершены.\n";
    }

    /**
     * Откатить миграцию
     */
    public function rollback($steps = 1)
    {
        $executedMigrations = $this->getExecutedMigrations($steps);

        if (empty($executedMigrations)) {
            echo "Нет миграций для отката.\n";
            return;
        }

        echo "Откат " . count($executedMigrations) . " миграций:\n";

        foreach (array_reverse($executedMigrations) as $migration) {
            echo "Откат миграции: {$migration['name']}\n";

            try {
                $this->rollbackMigration($migration);
                $this->markAsRolledBack($migration['name']);
                echo "✓ Миграция {$migration['name']} откачена успешно\n";
            } catch (Exception $e) {
                echo "✗ Ошибка при откате миграции {$migration['name']}: " . $e->getMessage() . "\n";
                break;
            }
        }

        echo "Откат завершен.\n";
    }

    /**
     * Показать статус миграций
     */
    public function status()
    {
        $allMigrations = $this->getAllMigrations();
        $executedMigrations = $this->getExecutedMigrations();

        $executedNames = array_column($executedMigrations, 'name');

        echo "=== Статус миграций ===\n";
        echo "Всего файлов миграций: " . count($allMigrations) . "\n";
        echo "Выполнено: " . count($executedMigrations) . "\n";
        echo "Ожидают выполнения: " . (count($allMigrations) - count($executedMigrations)) . "\n\n";

        foreach ($allMigrations as $migration) {
            $status = in_array($migration['name'], $executedNames) ? '✓' : '✗';
            echo "{$status} {$migration['name']}\n";
        }
    }

    /**
     * Получить все миграции из файловой системы
     */
    private function getAllMigrations()
    {
        $migrations = [];
        $dir = new Directory($this->migrationDir);

        if ($dir->isExists()) {
            $files = $dir->getChildren();
            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $name = $file->getName();
                    $migrations[] = [
                        'name' => str_replace('.php', '', $name),
                        'path' => $file->getPath()
                    ];
                }
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Получить еще не выполненные миграции
     */
    private function getPendingMigrations()
    {
        $allMigrations = $this->getAllMigrations();
        $executedMigrations = $this->getExecutedMigrations();

        $executedNames = array_column($executedMigrations, 'name');

        return array_filter($allMigrations, function($migration) use ($executedNames) {
            return !in_array($migration['name'], $executedNames);
        });
    }

    /**
     * Получить выполненные миграции
     */
    private function getExecutedMigrations($limit = null)
    {
        $sql = "SELECT migration as name FROM {$this->migrationTable} ORDER BY id DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }

        $result = $this->connection->query($sql);
        $migrations = [];

        while ($row = $result->fetch()) {
            $migrations[] = ['name' => $row['name']];
        }

        return $migrations;
    }

    /**
     * Выполнить миграцию
     */
    private function executeMigration($migration)
    {
        require_once $migration['path'];

        $className = $this->getClassNameFromFile($migration['name']);

        if (!class_exists($className)) {
            throw new Exception("Класс {$className} не найден в файле миграции");
        }

        $migrationInstance = new $className();

        if (!method_exists($migrationInstance, 'up')) {
            throw new Exception("Метод up() не найден в классе {$className}");
        }

        $migrationInstance->up();
    }

    /**
     * Откатить миграцию
     */
    private function rollbackMigration($migration)
    {
        $allMigrations = $this->getAllMigrations();
        $migrationFile = null;

        foreach ($allMigrations as $m) {
            if ($m['name'] === $migration['name']) {
                $migrationFile = $m;
                break;
            }
        }

        if (!$migrationFile) {
            throw new Exception("Файл миграции {$migration['name']} не найден");
        }

        require_once $migrationFile['path'];

        $className = $this->getClassNameFromFile($migration['name']);
        $migrationInstance = new $className();

        if (!method_exists($migrationInstance, 'down')) {
            throw new Exception("Метод down() не найден в классе {$className}");
        }

        $migrationInstance->down();
    }

    /**
     * Отметить миграцию как выполненную
     */
    private function markAsExecuted($migrationName)
    {
        $sql = "INSERT INTO {$this->migrationTable} (migration) VALUES (?)";
        $this->connection->query($sql, [$migrationName]);
    }

    /**
     * Отметить миграцию как откаченную
     */
    private function markAsRolledBack($migrationName)
    {
        $sql = "DELETE FROM {$this->migrationTable} WHERE migration = ?";
        $this->connection->query($sql, [$migrationName]);
    }

    /**
     * Получить имя класса из имени файла
     */
    private function getClassNameFromFile($filename)
    {
        // Убираем timestamp из начала имени файла
        $name = preg_replace('/^\d{14}_/', '', $filename);
        return 'Migration' . $this->toCamelCase($name);
    }

    /**
     * Конвертировать в CamelCase
     */
    private function toCamelCase($string)
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string)));
    }

    /**
     * Получить шаблон миграции
     */
    private function getMigrationTemplate($className)
    {
        return <<<PHP
<?php
/**
 * Миграция: {$className}
 * Создана: " . date('Y-m-d H:i:s') . "
 */

use Bitrix\Main\Application;

class Migration{$className}
{
    private \$connection;
    
    public function __construct()
    {
        \$this->connection = Application::getConnection();
    }
    
    /**
     * Выполнить миграцию
     */
    public function up()
    {
        // Код миграции
        // Например:
        // \$this->connection->query("CREATE TABLE ...");
        // \$this->connection->query("INSERT INTO ...");
        
        echo "Миграция {$className} выполнена\\n";
    }
    
    /**
     * Откатить миграцию
     */
    public function down()
    {
        // Код отката миграции
        // Например:
        // \$this->connection->query("DROP TABLE ...");
        // \$this->connection->query("DELETE FROM ...");
        
        echo "Миграция {$className} откачена\\n";
    }
}
PHP;
    }

    /**
     * Очистить все миграции (только для разработки)
     */
    public function reset()
    {
        echo "ВНИМАНИЕ: Эта операция удалит все записи о выполненных миграциях!\n";
        echo "Вы уверены? (y/N): ";

        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);

        if (strtolower($confirmation) !== 'y') {
            echo "Операция отменена.\n";
            return;
        }

        $sql = "TRUNCATE TABLE {$this->migrationTable}";
        $this->connection->query($sql);

        echo "Таблица миграций очищена.\n";
    }
}

// Справка
function showHelp()
{
    echo "Система миграций для Bitrix\n";
    echo "Использование: php migrate.php [команда] [опции]\n\n";
    echo "Команды:\n";
    echo "  create [name]      - создать новую миграцию\n";
    echo "  migrate [target]   - выполнить миграции (или конкретную)\n";
    echo "  rollback [steps]   - откатить миграции (по умолчанию 1 шаг)\n";
    echo "  status             - показать статус миграций\n";
    echo "  reset              - очистить таблицу миграций (только для разработки)\n";
    echo "  help               - показать эту справку\n\n";
    echo "Примеры:\n";
    echo "  php migrate.php create addapp_user_table\n";
    echo "  php migrate.php migrate\n";
    echo "  php migrate.php rollback 3\n";
    echo "  php migrate.php status\n";
}

// Основная логика
$migrationManager = new MigrationManager();

$action = $argv[1] ?? 'help';

switch ($action) {
    case 'create':
        $name = $argv[2] ?? '';
        if (empty($name)) {
            echo "Ошибка: не указано имя миграции\n";
            showHelp();
            exit(1);
        }
        $migrationManager->create($name);
        break;

    case 'migrate':
        $target = $argv[2] ?? null;
        $migrationManager->migrate($target);
        break;

    case 'rollback':
        $steps = isset($argv[2]) ? intval($argv[2]) : 1;
        $migrationManager->rollback($steps);
        break;

    case 'status':
        $migrationManager->status();
        break;

    case 'reset':
        $migrationManager->reset();
        break;

    case 'help':
    case '--help':
    case '-h':
        showHelp();
        break;

    default:
        echo "Неизвестная команда: $action\n";
        showHelp();
        exit(1);
}