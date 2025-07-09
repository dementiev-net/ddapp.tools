<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\UserHelper;
use DDAPP\Tools\Main;

Loc::loadMessages(__FILE__);

die("ДЕЛАЕМ LANG!!!");


// Подключаем JS и CSS
Main::includeJS("admin/js/log_viewer.js");
Main::includeCSS("admin/css/log_viewer.css");

// Получим права доступа текущего пользователя на модуль
if (UserHelper::hasModuleAccess("") == "D") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$btnDisabled = UserHelper::hasModuleAccess("") >= "W" ? false : true;

// Настройка логирования
LogHelper::configure();

Extension::load("ui.dialogs.messagebox");

$APPLICATION->SetTitle("Просмотр логов");

$request = Application::getInstance()->getContext()->getRequest();

// Получаем путь к логам
$logPath = Bitrix\Main\Config\Option::get(Main::MODULE_ID, "log_path", "/upload/logs");
$fullLogPath = $_SERVER["DOCUMENT_ROOT"] . $logPath;

// Обработка AJAX запросов
if ($request->isPost() && check_bitrix_sessid()) {
    $action = $request->getPost("action");

    switch ($action) {
        case "get_log_files":
            echo json_encode(LogHelper::getLogFiles());
            exit;

        case "get_log_data":
            $filename = $request->getPost("filename");
            $filtersJson = $request->getPost("filters");
            $filters = $filtersJson ? json_decode($filtersJson, true) : [];
            $page = (int)$request->getPost("page") ?: 1;

            if ($filename) {
                // Получаем все записи сначала для пользователей
                $allEntries = LogHelper::parseLogFile($filename, []);
                $users = LogHelper::getUsers($allEntries);

                // Затем применяем фильтры
                $filteredEntries = LogHelper::parseLogFile($filename, $filters);
                $stats = LogHelper::getStats($filteredEntries);
                $paginatedData = LogHelper::paginate($filteredEntries, $page);

                echo json_encode([
                    'entries' => $paginatedData['entries'],
                    'stats' => $stats,
                    'users' => $users,
                    'pagination' => [
                        'total' => $paginatedData['total'],
                        'pages' => $paginatedData['pages'],
                        'current_page' => $paginatedData['current_page']
                    ]
                ]);
            }
            exit;

        case "clear_log":
            $filename = $request->getPost("filename");
            if ($filename && !$btnDisabled) {
                $filepath = $fullLogPath . '/' . $filename;
                if (file_exists($filepath)) {
                    //file_put_contents($filepath, '');
                    echo json_encode(['success' => true, 'message' => 'Лог очищен']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Файл не найден']);
                }
            }
            exit;
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
?>

    <div class="log-viewer">
        <div class="log-controls">
            <div class="control-group">
                <label>Файл лога:</label>
                <select id="log-file-select" class="adm-input">
                    <option value="">Выберите файл лога</option>
                </select>
                <input id="refresh-files" type="button" value="Обновить список">
                <input id="clear-log" type="button" value="Очистить лог" <?= $btnDisabled ? "disabled" : "" ?>>
            </div>

            <div class="filters">
                <div class="filter-group">
                    <label>Уровень:</label>
                    <select id="level-filter" class="adm-input">
                        <option value="">Все уровни</option>
                        <option value="ERROR">ERROR</option>
                        <option value="WARNING">WARNING</option>
                        <option value="INFO">INFO</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Пользователь:</label>
                    <select id="user-filter" class="adm-input">
                        <option value="">Все пользователи</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Дата:</label>
                    <input type="date" id="date-filter" class="adm-input">
                </div>

                <div class="filter-group">
                    <label>Поиск:</label>
                    <input type="text" id="search-filter" class="adm-input" placeholder="Поиск по тексту">
                </div>

                <input id="clear-filters" type="button" value="Очистить фильтры">
            </div>
        </div>

        <div class="log-stats">
            <div class="stats-item">
                <span class="label">Всего записей:</span>
                <span id="total-count" class="value">0</span>
            </div>
            <div class="stats-item error">
                <span class="label">Ошибок:</span>
                <span id="error-count" class="value">0</span>
            </div>
            <div class="stats-item warning">
                <span class="label">Предупреждений:</span>
                <span id="warning-count" class="value">0</span>
            </div>
            <div class="stats-item info">
                <span class="label">Информация:</span>
                <span id="info-count" class="value">0</span>
            </div>
        </div>

        <div class="log-content">
            <table id="log-table" class="adm-list-table">
                <thead>
                <tr class="adm-list-table-header">
                    <th>Дата/Время</th>
                    <th>Уровень</th>
                    <th>Пользователь</th>
                    <th>URL</th>
                    <th>Память</th>
                </tr>
                </thead>
                <tbody id="log-entries">
                <tr>
                    <td colspan="6" class="no-data">Выберите файл лога</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination" id="pagination" style="display: none;">
            <div class="pagination-info">
                <span id="pagination-info"></span>
            </div>
            <div class="pagination-controls">
                <span class="page-text">Страницы:</span>
                <span id="page-numbers"></span>
                <div class="page-button">
                    <a href="javascript:void(0)" id="prev-page" class="page-button arrow prev">Предыдущая</a>
                    <a href="javascript:void(0)" id="next-page" class="page-button arrow next">Следующая</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Инициализация при загрузке страницы
        BX.ready(function () {
            new BX.DDAPP.Tools.LogViewer({
                ajaxUrl: '<?= Main::getAjaxUrl("admin/ddapp_log.php") ?>',
            });
        });
    </script>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");