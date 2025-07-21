<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\UserHelper;
use DDAPP\Tools\Main;

Loc::loadMessages(__FILE__);

// Подключаем JS и CSS
Main::includeJS("admin/js/log_viewer.js");
Main::includeCSS("admin/css/log_viewer.css");

// Получим права доступа текущего пользователя на модуль
if (UserHelper::hasModuleAccess("") == "D") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$btnDisabled = UserHelper::hasModuleAccess("") >= "W" ? false : true;

// Настройка логирования
LogHelper::configure();

Extension::load("ui.dialogs.messagebox");

$APPLICATION->SetTitle(Loc::getMessage("DDAPP_PAGE_TITLE"));

$request = Application::getInstance()->getContext()->getRequest();

// Получаем путь к логам
$logPath = Bitrix\Main\Config\Option::get(Main::MODULE_ID, "log_path");
$fullLogPath = $_SERVER["DOCUMENT_ROOT"] . $logPath;

// Обработка AJAX запросов
if ($request->isPost() && check_bitrix_sessid()) {
    $action = $request->getPost("action");

    header("Content-Type: application/json; charset=utf-8");

    switch ($action) {
        case "get_log_files":
            echo Json::encode(LogHelper::getLogFiles());
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

                echo Json::encode([
                    "entries" => $paginatedData["entries"],
                    "stats" => $stats,
                    "users" => $users,
                    "pagination" => [
                        "total" => $paginatedData["total"],
                        "pages" => $paginatedData["pages"],
                        "current_page" => $paginatedData["current_page"]
                    ]
                ]);
            }
            exit;

        case "clear_log":
            $filename = $request->getPost("filename");
            if ($filename && !$btnDisabled) {
                $filepath = $fullLogPath . "/" . $filename;
                if (file_exists($filepath)) {
                    //file_put_contents($filepath, "");
                    echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_LOGFILE_MESSAGE_LOG_CLEAR")]);
                } else {
                    echo Json::encode(["success" => false, "message" => Loc::getMessage("DDAPP_LOGFILE_MESSAGE_ERROR_FILE_NOT_FOUND")]);
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
                <label for="log-file-select"><?= Loc::getMessage("DDAPP_LOGFILE_CONTROL_FILE") ?>:</label>
                <select id="log-file-select" class="adm-input">
                    <option value=""><?= Loc::getMessage("DDAPP_LOGFILE_CONTROL_FILE_SELECT") ?></option>
                </select>
                <input id="refresh-files" type="button" value="<?= Loc::getMessage("DDAPP_LOGFILE_BTN_REFRESH") ?>">
                <input id="clear-log" type="button"
                       value="<?= Loc::getMessage("DDAPP_LOGFILE_BTN_CLEAR") ?>" <?= $btnDisabled ? "disabled" : "" ?>>
            </div>

            <div class="filters">
                <div class="filter-group">
                    <label for="level-filter"><?= Loc::getMessage("DDAPP_LOGFILE_FILTER_LEVEL") ?>:</label>
                    <select id="level-filter" class="adm-input">
                        <option value=""><?= Loc::getMessage("DDAPP_LOGFILE_FILTER_LEVEL_ALL") ?></option>
                        <option value="<?= LogHelper::LEVEL_DEBUG ?>"><?= Loc::getMessage("DDAPP_LOGFILE_LEVEL_DEBUG") ?></option>
                        <option value="<?= LogHelper::LEVEL_INFO ?>"><?= Loc::getMessage("DDAPP_LOGFILE_LEVEL_INFO") ?></option>
                        <option value="<?= LogHelper::LEVEL_WARNING ?>"><?= Loc::getMessage("DDAPP_LOGFILE_LEVEL_WARNING") ?></option>
                        <option value="<?= LogHelper::LEVEL_ERROR ?>"><?= Loc::getMessage("DDAPP_LOGFILE_LEVEL_ERROR") ?></option>
                        <option value="<?= LogHelper::LEVEL_CRITICAL ?>"><?= Loc::getMessage("DDAPP_LOGFILE_LEVEL_CRITICAL") ?></option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="user-filter"><?= Loc::getMessage("DDAPP_LOGFILE_FILTER_USER") ?>:</label>
                    <select id="user-filter" class="adm-input">
                        <option value=""><?= Loc::getMessage("DDAPP_LOGFILE_FILTER_USER_ALL") ?></option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="date-filter"><?= Loc::getMessage("DDAPP_LOGFILE_FILTER_DATE") ?>:</label>
                    <input type="date" id="date-filter" class="adm-input">
                </div>

                <div class="filter-group">
                    <label for="search-filter"><?= Loc::getMessage("DDAPP_LOGFILE_FILTER_SEARCH") ?>:</label>
                    <input type="text" id="search-filter" class="adm-input"
                           placeholder="<?= Loc::getMessage("DDAPP_LOGFILE_FILTER_SEARCH_PLACEHOLDER") ?>">
                </div>

                <input id="clear-filters" type="button"
                       value="<?= Loc::getMessage("DDAPP_LOGFILE_BTN_FILTER_CLEAR") ?>">
            </div>
        </div>

        <div class="log-stats">
            <div class="stats-item">
                <span class="label"><?= Loc::getMessage("DDAPP_LOGFILE_LEVEL_ALL") ?>:</span>
                <span id="total-count" class="value">0</span>
            </div>
            <div class="stats-item debug">
                <span class="label"><?= Loc::getMessage("DDAPP_LOGFILE_LEVEL_DEBUG") ?>:</span>
                <span id="debug-count" class="value">0</span>
            </div>
            <div class="stats-item info">
                <span class="label"><?= Loc::getMessage("DDAPP_LOGFILE_LEVEL_INFO") ?>:</span>
                <span id="info-count" class="value">0</span>
            </div>
            <div class="stats-item warning">
                <span class="label"><?= Loc::getMessage("DDAPP_LOGFILE_LEVEL_WARNING") ?>:</span>
                <span id="warning-count" class="value">0</span>
            </div>
            <div class="stats-item error">
                <span class="label"><?= Loc::getMessage("DDAPP_LOGFILE_LEVEL_ERROR") ?>:</span>
                <span id="error-count" class="value">0</span>
            </div>
            <div class="stats-item critical">
                <span class="label"><?= Loc::getMessage("DDAPP_LOGFILE_LEVEL_CRITICAL") ?>:</span>
                <span id="critical-count" class="value">0</span>
            </div>
        </div>

        <div class="log-content">
            <table id="log-table" class="adm-list-table">
                <thead>
                <tr class="adm-list-table-header">
                    <th><?= Loc::getMessage("DDAPP_LOGFILE_TABLE_DATE") ?></th>
                    <th><?= Loc::getMessage("DDAPP_LOGFILE_TABLE_LEVEL") ?></th>
                    <th><?= Loc::getMessage("DDAPP_LOGFILE_TABLE_USER") ?></th>
                    <th><?= Loc::getMessage("DDAPP_LOGFILE_TABLE_URL") ?></th>
                    <th><?= Loc::getMessage("DDAPP_LOGFILE_TABLE_MEMORY") ?></th>
                </tr>
                </thead>
                <tbody id="log-entries">
                <tr>
                    <td colspan="6" class="no-data"><?= Loc::getMessage("DDAPP_LOGFILE_MESSAGE_DATA_NOT_FOUND") ?></td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination" id="pagination" style="display: none;">
            <div class="pagination-info">
                <span id="pagination-info"></span>
            </div>
            <div class="pagination-controls">
                <span class="page-text"><?= Loc::getMessage("DDAPP_LOGFILE_TABLE_PAGES") ?>:</span>
                <span id="page-numbers"></span>
                <div class="page-button">
                    <a href="javascript:void(0)" id="prev-page"
                       class="page-button arrow prev"><?= Loc::getMessage("DDAPP_LOGFILE_TABLE_PAGES_PREV") ?></a>
                    <a href="javascript:void(0)" id="next-page"
                       class="page-button arrow next"><?= Loc::getMessage("DDAPP_LOGFILE_TABLE_PAGES_NEXT") ?></a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Инициализация
        BX.ready(function () {
            new BX.DDAPP.Tools.LogViewer({
                ajaxUrl: "<?= Main::getAjaxUrl("admin/ddapp_log.php") ?>",
                messageTitle: '<?= Loc::getMessage("DDAPP_LOGFILE_MESSAGE_TITLE")?>',
                messageBeforeDelete: '<?= Loc::getMessage("DDAPP_LOGFILE_MESSAGE_BEFORE_DELETE")?>',
                messageDeleteOk: '<?= Loc::getMessage("DDAPP_LOGFILE_MESSAGE_LOG_CLEAR")?>',
                messageError: '<?= Loc::getMessage("DDAPP_LOGFILE_MESSAGE_ERROR")?>',
                messageErrorLoadFile: '<?= Loc::getMessage("DDAPP_LOGFILE_MESSAGE_ERROR_LOAD_FILE")?>',
                messageErrorLoadData: '<?= Loc::getMessage("DDAPP_LOGFILE_MESSAGE_ERROR_LOAD_DATA")?>',
                messageErrorLogClear: '<?= Loc::getMessage("DDAPP_LOGFILE_MESSAGE_ERROR_LOG_CLEAR")?>',
                messageAllUsers: '<?= Loc::getMessage("DDAPP_LOGFILE_FILTER_USER_ALL")?>',
                messageSelectLogFile: '<?= Loc::getMessage("DDAPP_LOGFILE_CONTROL_FILE_SELECT")?>',
                messageDataNotFound: '<?= Loc::getMessage("DDAPP_LOGFILE_MESSAGE_DATA_NOT_FOUND")?>',
                messagePageFrom: '<?= Loc::getMessage("DDAPP_LOGFILE_TABLE_PAGES_FROM")?>',
            });
        });
    </script>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");