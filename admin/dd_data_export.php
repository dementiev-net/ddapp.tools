<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use DD\Tools\Helpers\IblockHelper;
use DD\Tools\Helpers\LogHelper;
use DD\Tools\Main;
use DD\Tools\DataExport;

Loc::loadMessages(__FILE__);

// Подключаем модуль
if (!CModule::IncludeModule(Main::MODULE_ID)) {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
    ShowError("Модуль " . Main::MODULE_ID . " не установлен");
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
    die();
}

// Подключаем JS через функцию модуля
Main::includeJS("admin/js/export_manager.js");
Main::includeJS("admin/js/export_profile_manager.js");
Main::includeCSS("admin/css/data_export_form.css");

// Получим права доступа текущего пользователя на модуль
$moduleAccessLevel = $APPLICATION->GetGroupRight(Main::MODULE_ID);

if ($moduleAccessLevel == "D") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$btnDisabled = true;
if ($moduleAccessLevel >= "W") $btnDisabled = false;

// Настройка логирования
LogHelper::configure();

// Подключаем необходимые модули
Loader::includeModule("iblock");
Extension::load("ui.dialogs.messagebox");

$APPLICATION->SetTitle("Экспорт");

// Контекстное меню
$context = new CAdminContextMenu([
    [
        "TEXT" => "Экспортировать" . Loc::getMessage("DD_MAINT_BTN_TO_LIST"),
        "ICON" => "btn_green",
        "LINK" => "#",
        "TITLE" => "К списку записей",
        "LINK_PARAM" => "id='btn_export' data-id='0'"
    ]
]);

$request = Application::getInstance()->getContext()->getRequest();

// Обработка AJAX запросов
if ($request->isPost() && !empty($request->getPost("action"))) {

    switch ($request->getPost("action")) {

        case "get_profiles":
            $profiles = DataExport::getItems([
                "select" => ["ID", "NAME", "IBLOCK_TYPE_ID", "IBLOCK_ID", "EXPORT_TYPE", "SETTINGS"]
            ])->fetchAll();
            echo json_encode(["success" => true, "data" => $profiles]);
            exit;

        case "get_profile":
            if (!empty($request->getPost("profile_id"))) {
                $profile = DataExport::getById($request->getPost("profile_id"));
                echo json_encode(["success" => true, "data" => $profile]);
            }
            exit;

        case "save_profile":
            $fields = [
                "NAME" => $request->getPost("name"),
                "IBLOCK_TYPE_ID" => $request->getPost("iblock_type_id"),
                "IBLOCK_ID" => $request->getPost("iblock_id"),
                "EXPORT_TYPE" => $request->getPost("export_type"),
                "SETTINGS" => json_encode($request->getPost("settings"))
            ];

            if (!empty($request->getPost("profile_id"))) {
                DataExport::update($request->getPost("profile_id"), $fields);
                echo json_encode(["success" => true, "message" => "Профиль обновлен"]);
            } else {
                $result = DataExport::add($fields);
                echo json_encode(["success" => true, "message" => "Профиль создан", "id" => $result->getId()]);
            }
            exit;

        case "delete_profile":
            if (!empty($request->getPost("profile_id"))) {
                DataExport::delete($request->getPost("profile_id"));
                echo json_encode(["success" => true, "message" => "Профиль удален"]);
            }
            exit;

        case "get_iblock_types":
            $types = IblockHelper::getAllBlockType();
            echo json_encode(["success" => true, "data" => $types]);
            exit;

        case "get_iblocks":
            if (!empty($request->getPost("type_id"))) {
                $iblocks = IblockHelper::getBlocks([
                    "select" => ["ID", "NAME"],
                    "filter" => ["IBLOCK_TYPE_ID" => $request->getPost("type_id"), "ACTIVE" => "Y"]
                ]);
                echo json_encode(["success" => true, "data" => $iblocks]);
            }
            exit;

        case "get_iblock_fields":
            if (!empty($request->getPost("iblock_id"))) {
                $iblockId = intval($request->getPost("iblock_id"));
                $fields = [];

                foreach (IblockHelper::getDefaultFieldsNames() as $code => $name) {
                    $fields[] = ["CODE" => $code, "NAME" => $name, "TYPE" => "FIELD", "PROPERTY_TYPE" => "", "MULTIPLE" => "N"];
                }

                $properties = IblockHelper::getProperties([
                    "select" => ["ID", "CODE", "NAME", "PROPERTY_TYPE", "MULTIPLE", "LIST_TYPE", "USER_TYPE"],
                    "filter" => ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"],
                    "order" => ["SORT" => "ASC", "NAME" => "ASC"]
                ]);

                foreach ($properties as $property) {
                    $propertyName = $property["NAME"];

                    // Добавляем информацию о типе свойства
                    switch ($property["PROPERTY_TYPE"]) {
                        case "S":
                            $propertyName .= " (Строка)";
                            break;
                        case "N":
                            $propertyName .= " (Число)";
                            break;
                        case "L":
                            $propertyName .= " (Список)";
                            break;
                        case "F":
                            $propertyName .= " (Файл)";
                            break;
                        case "G":
                            $propertyName .= " (Привязка к разделам)";
                            break;
                        case "E":
                            $propertyName .= " (Привязка к элементам)";
                            break;
                    }

                    if ($property["MULTIPLE"] === "Y") {
                        $propertyName .= " [множественное]";
                    }

                    $fields[] = [
                        "CODE" => "PROPERTY_" . $property["CODE"],
                        "NAME" => $propertyName,
                        "TYPE" => "PROPERTY",
                        "PROPERTY_TYPE" => $property["PROPERTY_TYPE"],
                        "MULTIPLE" => $property["MULTIPLE"]
                    ];
                }
                echo json_encode(["success" => true, "data" => $fields]);
            }
            exit;
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
?>

    <div class="adm-info-message-wrap adm-info-message-gray" id="export_message">
        <div class="adm-info-message">
            <div class="adm-info-message-title">Экспорт</div>
            Успешно экспортировано записей: <span id="export_message_ok">0</span>
            <br>С ошибками: <span id="export_message_error">0</span>
            <p id="export_message_file"></p>
        </div>
    </div>

<?= $context->Show(); ?>

<form id="data-export-form" class="data-export-form">
    <input type="hidden" id="profile-id" name="profile_id">
    <?= bitrix_sessid_post() ?>

    <?php
    $tabControl = new CAdminTabControl("tabControl", [
        [
            "DIV" => "edit1",
            "TAB" => "Настройки" . Loc::getMessage("DD_MAINT_TAB1"),
            "TITLE" => "Настройки экспорта данных инфоблока" . Loc::getMessage("DD_MAINT_TAB1_TITLE")
        ]
    ]);
    $tabControl->Begin();
    ?>

    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%" style="position: relative; top: -4px;">Профиль:</td>
        <td width="60%">
            <select id="profile-select" class="adm-input" <?= $btnDisabled ? "disabled" : "" ?>></select>
            <?php if(!$btnDisabled) { ?>
            <span style="letter-spacing: -5px; position: relative; top: -3px;">
    					<a href="javascript:void(0)" class="adm-table-btn-edit" id="create-profile-btn"></a>
	    				<a href="javascript:void(0);" class="adm-table-btn-delete" id="delete-profile-btn"></a>
                </span>
            <?php } ?>
        </td>
    </tr>

    <!-- Настройки профиля -->
    <tr class="heading profile-settings">
        <td colspan="2">Настройки</td>
    </tr>
    <tr class="profile-settings">
        <td>
            <label for="profile-name">Название профиля:</label>
        </td>
        <td>
            <input type="text" id="profile-name" name="name" class="adm-input" required>
        </td>
    </tr>
    <tr class="profile-settings">
        <td>
            <label for="iblock-type-select">Тип инфоблока:</label>
        </td>
        <td>
            <select id="iblock-type-select" name="iblock_type_id" class="adm-input">
                <option value="">-- Выберите тип инфоблока --</option>
            </select>
        </td>
    </tr>
    <tr class="profile-settings">
        <td>
            <label for="iblock-select">Инфоблок:</label>
        </td>
        <td>
            <select id="iblock-select" name="iblock_id" class="adm-input" disabled>
                <option value="">-- Сначала выберите тип --</option>
            </select>
        </td>
    </tr>
    <tr class="profile-settings">
        <td>
            <label for="export-type-select">Формат экспорта:</label>
        </td>
        <td>
            <select id="export-type-select" name="export_type" class="adm-input">
                <option value="">-- Выберите формат --</option>
                <option value="xls">Excel (XLS)</option>
                <option value="csv">CSV</option>
            </select>
        </td>
    </tr>

    <!-- Выбор полей для экспорта -->
    <tr class="heading fields-selection">
        <td colspan="2">Поля для экспорта</td>
    </tr>
    <tr class="fields-selection">
        <td colspan="2">

            <div class="adm-detail-content-item-block-desc">
                <div style="margin-bottom: 10px;">
                    <input type="button" id="select-all-fields" value="Выбрать все" class="adm-btn">
                    <input type="button" id="deselect-all-fields" value="Снять все" class="adm-btn">
                </div>
            </div>

            <div id="fields-container"
                 style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                <!-- Поля будут загружены динамически -->
            </div>

        </td>
    </tr>

    <!-- Настройки для CSV -->
    <tr class="heading csv-settings">
        <td colspan="2">Настройки CSV</td>
    </tr>
    <tr class="csv-settings">
        <td>
            <label>Разделитель:</label>
        </td>
        <td>
            <select name="settings[delimiter]" class="adm-input">
                <option value=";">Точка с запятой (;)</option>
                <option value=",">Запятая (,)</option>
                <option value="\t">Табуляция</option>
            </select>
        </td>
    </tr>
    <tr class="csv-settings">
        <td>
            <label>Кодировка:</label>
        </td>
        <td>
            <select name="settings[encoding]" class="adm-input">
                <option value="UTF-8">UTF-8</option>
                <option value="Windows-1251">Windows-1251</option>
                <option value="CP866">CP866</option>
            </select>
        </td>
    </tr>
    <tr class="csv-settings">
        <td>
            <label>Включить заголовки:</label>
        </td>
        <td>
            <input type="checkbox" name="settings[include_headers]" value="Y" checked>
        </td>
    </tr>
    <tr class="csv-settings">
        <td>
            <label>Обрамлять кавычками:</label>
        </td>
        <td>
            <input type="checkbox" name="settings[quote_fields]" value="Y">
        </td>
    </tr>

    <!-- Настройки для Excel -->
    <tr class="heading excel-settings">
        <td colspan="2">Настройки Excel</td>
    </tr>
    <tr class="excel-settings">
        <td>
            <label>Название листа:</label>
        </td>
        <td>
            <input type="text" name="settings[sheet_name]" class="adm-input" value="Экспорт">
        </td>
    </tr>
    <tr class="excel-settings">
        <td>
            <label>Включить заголовки:</label>
        </td>
        <td>
            <input type="checkbox" name="settings[include_headers]" value="Y" checked>
        </td>
    </tr>
    <tr class="excel-settings">
        <td>
            <label>Автоширина столбцов:</label>
        </td>
        <td>
            <input type="checkbox" name="settings[auto_width]" value="Y" checked>
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>

    <input type="submit" value="Сохранить" <?= $btnDisabled ? "disabled" : "" ?>>
    <input type="button" id="cancel-btn" value="Отмена" <?= $btnDisabled ? "disabled" : "" ?>>

    <?php $tabControl->End(); ?>

</form>

<script>
    BX.ready(function () {
        // Инициализация
        new BX.DD.Tools.ExportProfileManager({
            ajaxUrl: '<?= Main::getAjaxUrl("admin/dd_data_export.php") ?>'
        });
        new BX.DD.Tools.ExportManager({
            ajaxUrl: '<?= Main::getAjaxUrl("admin/ajax/export_csv.php") ?>',
            beforeUnloadMessage: 'Экспорт в процессе. Покинуть страницу?',
        });
    });
</script>

<?php
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");