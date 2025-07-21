<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;
use DDAPP\Tools\Main;
use DDAPP\Tools\DataExport;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\UserHelper;
use DDAPP\Tools\Helpers\IblockHelper;

Loc::loadMessages(__FILE__);

// Подключаем JS и CSS
Main::includeJS("admin/js/export_manager.js");
Main::includeJS("admin/js/export_profile_manager.js");
Main::includeCSS("admin/css/data_export_form.css");

// Получим права доступа текущего пользователя на модуль
if (UserHelper::hasModuleAccess("") == "D") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$btnDisabled = UserHelper::hasModuleAccess("") >= "W" ? false : true;

// Настройка логирования
LogHelper::configure();

// Подключаем необходимые модули
Loader::includeModule("iblock");
Extension::load("ui.dialogs.messagebox");

$APPLICATION->SetTitle(Loc::getMessage("DDAPP_PAGE_TITLE"));

$request = Application::getInstance()->getContext()->getRequest();

// Обработка AJAX запросов
if ($request->isPost() && check_bitrix_sessid() && UserHelper::hasModuleAccess("") >= "W" && !empty($request->getPost("action"))) {

    header("Content-Type: application/json; charset=utf-8");

    switch ($request->getPost("action")) {

        case "get_profiles":
            $profiles = DataExport::getItems([
                "select" => ["ID", "NAME", "IBLOCK_TYPE_ID", "IBLOCK_ID", "EXPORT_TYPE", "SETTINGS"]
            ]);
            echo Json::encode(["success" => true, "data" => $profiles]);
            exit;

        case "get_profile":
            if (!empty($request->getPost("profile_id"))) {
                $profile = DataExport::getById($request->getPost("profile_id"));
                echo Json::encode(["success" => true, "data" => $profile]);
            }
            exit;

        case "save_profile":
            $fields = [
                "NAME" => $request->getPost("name"),
                "IBLOCK_TYPE_ID" => $request->getPost("iblock_type_id"),
                "IBLOCK_ID" => $request->getPost("iblock_id"),
                "EXPORT_TYPE" => $request->getPost("export_type"),
                "SETTINGS" => Json::encode($request->getPost("settings"))
            ];

            if (empty($request->getPost("name"))) {
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_EXPORT_MESSAGE_PROFILE_NAME_ERROR")]);
                exit;
            }
            if (empty($request->getPost("iblock_id"))) {
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_EXPORT_MESSAGE_PROFILE_IBLOCK_ERROR")]);
                exit;
            }
            if (empty($request->getPost("export_type"))) {
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_EXPORT_MESSAGE_PROFILE_FORMAT_ERROR")]);
                exit;
            }
            if (!empty($request->getPost("profile_id"))) {
                DataExport::update($request->getPost("profile_id"), $fields);
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_EXPORT_MESSAGE_PROFILE_ADD")]);
            } else {
                $result = DataExport::add($fields);
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_EXPORT_MESSAGE_PROFILE_EDIT"), "id" => $result->getId()]);
            }
            exit;

        case "delete_profile":
            if (!empty($request->getPost("profile_id"))) {
                DataExport::delete($request->getPost("profile_id"));
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_EXPORT_MESSAGE_PROFILE_DELETE")]);
            }
            exit;

        case "get_iblock_types":
            $types = IblockHelper::getAllBlockType();
            echo Json::encode(["success" => true, "data" => $types]);
            exit;

        case "get_iblocks":
            if (!empty($request->getPost("type_id"))) {
                $iblocks = IblockHelper::getBlocks([
                    "select" => ["ID", "NAME"],
                    "filter" => ["IBLOCK_TYPE_ID" => $request->getPost("type_id"), "ACTIVE" => "Y"]
                ]);
                echo Json::encode(["success" => true, "data" => $iblocks]);
            }
            exit;

        case "get_iblock_fields":
            if (!empty($request->getPost("iblock_id"))) {
                $iblockId = intval($request->getPost("iblock_id"));
                $fields = [];

                foreach (IblockHelper::getDefaultFieldsNames("") as $code => $name) {
                    $fields[] = ["CODE" => $code, "NAME" => $name, "TYPE" => "FIELD", "PROPERTY_TYPE" => "", "MULTIPLE" => "N"];
                }

                $properties = IblockHelper::getAllProperties([
                    "select" => ["ID", "CODE", "NAME", "PROPERTY_TYPE", "MULTIPLE", "LIST_TYPE", "USER_TYPE"],
                    "filter" => ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"],
                    "order" => ["SORT" => "ASC", "NAME" => "ASC"]
                ]);

                foreach ($properties as $property) {
                    $propertyName = $property["NAME"];

                    // Добавляем информацию о типе свойства
                    $message = Loc::getMessage("DDAPP_EXPORT_PROPERTY_TYPE_" . $property["PROPERTY_TYPE"]);
                    if ($message !== null) {
                        $propertyName .= $message;
                    }

                    if ($property["MULTIPLE"] === "Y") {
                        $propertyName .= Loc::getMessage("DDAPP_EXPORT_PROPERTY_TYPE_M");
                    }

                    $fields[] = [
                        "CODE" => "PROPERTY_" . $property["CODE"],
                        "NAME" => $propertyName,
                        "TYPE" => "PROPERTY",
                        "PROPERTY_TYPE" => $property["PROPERTY_TYPE"],
                        "MULTIPLE" => $property["MULTIPLE"]
                    ];
                }
                echo Json::encode(["success" => true, "data" => $fields]);
            }
            exit;
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

// Контекстное меню
$oMenu = new CAdminContextMenu([
    [
        "TEXT" => Loc::getMessage("DDAPP_EXPORT_BTN_EXPORT"),
        "ICON" => "btn_green",
        "LINK" => "/bitrix/admin/ddapp_maintenance_list.php?lang=" . LANG,
        "TITLE" => Loc::getMessage("DDAPP_MAINTENANCE_BTN_TO_LIST"),
        "LINK_PARAM" => "id='btn_export'"
    ]
]);
?>

    <div class="adm-info-message-wrap adm-info-message-gray" id="export_message">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?= Loc::getMessage("DDAPP_EXPORT_PROCESS_TITLE") ?></div>
            <?= Loc::getMessage("DDAPP_EXPORT_PROCESS_ITEMS_OK") ?><strong><span id="export_message_ok">0</span></strong>
            <br><?= Loc::getMessage("DDAPP_EXPORT_PROCESS_ITEMS_ERROR") ?><strong><span id="export_message_error">0</span></strong>
            <p id="export_message_file"></p>
            <div class="adm-progress-bar-outer" style="width: 500px;">
                <div class="adm-progress-bar-inner" id="progress_percent_a" style="width: 0;">
                    <div class="adm-progress-bar-inner-text" id="progress_percent_b" style="width: 500px;">0%</div>
                </div>
                <span id="progress_percent_c">0%</span>
            </div>
        </div>
    </div>

<?= $oMenu->Show(); ?>

    <form id="data_export_form" class="data-export-form">
        <input type="hidden" id="profile_id" name="profile_id">
        <?= bitrix_sessid_post() ?>

        <?php
        $tabControl = new CAdminTabControl("tabControl", [
            [
                "DIV" => "edit1",
                "TAB" => Loc::getMessage("DDAPP_EXPORT_TAB1"),
                "TITLE" => Loc::getMessage("DDAPP_EXPORT_TAB1_TITLE")
            ]
        ]);
        $tabControl->Begin();
        ?>

        <?php $tabControl->BeginNextTab(); ?>

        <tr>
            <td width="40%" style="position: relative; top: -4px;"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_PROFILE") ?>
                :
            </td>
            <td width="60%">
                <select id="profile_select" class="adm-input" <?= $btnDisabled ? "disabled" : "" ?>></select>
                <?php if (!$btnDisabled) { ?>
                    <span style="letter-spacing: -5px; position: relative; top: -3px;">
    					<a href="javascript:void(0)" class="adm-table-btn-edit" id="create_profile_btn"></a>
	    				<a href="javascript:void(0);" class="adm-table-btn-delete" id="delete_profile_btn"></a>
                </span>
                <?php } ?>
            </td>
        </tr>

        <!-- Настройки профиля -->
        <tr class="heading profile-settings">
            <td colspan="2"><?= Loc::getMessage("DDAPP_EXPORT_BLOCK1") ?></td>
        </tr>
        <tr class="profile-settings">
            <td>
                <label for="profile_name"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_PROFILE_NAME") ?>:</label>
            </td>
            <td>
                <input type="text" id="profile_name" name="name" class="adm-input">
            </td>
        </tr>
        <tr class="profile-settings">
            <td>
                <label for="iblock_type_select"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_IBLOCK_TYPE") ?>:</label>
            </td>
            <td>
                <select id="iblock_type_select" name="iblock_type_id" class="adm-input">
                    <option value=""><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_IBLOCK_TYPE_SELECT") ?></option>
                </select>
            </td>
        </tr>
        <tr class="profile-settings">
            <td>
                <label for="iblock_select"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_IBLOCK") ?>:</label>
            </td>
            <td>
                <select id="iblock_select" name="iblock_id" class="adm-input" disabled>
                    <option value=""><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_IBLOCK_SELECT") ?></option>
                </select>
            </td>
        </tr>
        <tr class="profile-settings">
            <td>
                <label for="export_type_select"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_FORMAT") ?>:</label>
            </td>
            <td>
                <select id="export_type_select" name="export_type" class="adm-input">
                    <option value=""><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_FORMAT_SELECT") ?></option>
                    <option value="xls"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_FORMAT_XLS") ?></option>
                    <option value="csv"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_FORMAT_CSV") ?></option>
                </select>
            </td>
        </tr>

        <!-- Выбор полей для экспорта -->
        <tr class="heading fields-selection">
            <td colspan="2"><?= Loc::getMessage("DDAPP_EXPORT_BLOCK2") ?></td>
        </tr>
        <tr class="fields-selection">
            <td colspan="2">

                <div class="adm-detail-content-item-block-desc">
                    <div style="margin-bottom: 10px;">
                        <input type="button" id="select_all_fields"
                               value="<?= Loc::getMessage("DDAPP_EXPORT_BTN_SELECT_ALL") ?>" class="adm-btn">
                        <input type="button" id="deselect_all_fields"
                               value="<?= Loc::getMessage("DDAPP_EXPORT_BTN_DESELECT_ALL") ?>" class="adm-btn">
                    </div>
                </div>
                <div id="fields_container"
                     style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                    <!-- Поля будут загружены динамически -->
                </div>

            </td>
        </tr>

        <!-- Настройки для CSV -->
        <tr class="heading csv-settings">
            <td colspan="2"><?= Loc::getMessage("DDAPP_EXPORT_BLOCK3") ?></td>
        </tr>
        <tr class="csv-settings">
            <td>
                <label><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_PATH") ?>:</label>
            </td>
            <td>
                <input type="text" name="settings[csv_path]" size="40" class="adm-input"
                       value="/upload/export_<?= date("Y-m-d"); ?>.csv">
            </td>
        </tr>
        <tr class="csv-settings">
            <td>
                <label><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER") ?>:</label>
            </td>
            <td>
                <select name="settings[csv_delimiter]" class="adm-input">
                    <option value=","><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_1") ?></option>
                    <option value=";"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_2") ?></option>
                    <option value="\t"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_3") ?></option>
                    <option value="|"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_4") ?></option>
                </select>
            </td>
        </tr>
        <tr class="csv-settings">
            <td>
                <label><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_MULTI_DELIMITER") ?>:</label>
            </td>
            <td>
                <select name="settings[csv_multi_delimiter]" class="adm-input">
                    <option value=","><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_1") ?></option>
                    <option value=";"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_2") ?></option>
                    <option value="\t"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_3") ?></option>
                    <option value="|"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_4") ?></option>
                </select>
            </td>
        </tr>
        <tr class="csv-settings">
            <td>
                <label><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_NEED_CSV_HEADER") ?>:</label>
            </td>
            <td>
                <input type="checkbox" name="settings[csv_headers]" value="Y">
            </td>
        </tr>

        <!-- Настройки для Excel -->
        <tr class="heading excel-settings">
            <td colspan="2"><?= Loc::getMessage("DDAPP_EXPORT_BLOCK4") ?></td>
        </tr>
        <tr class="excel-settings">
            <td>
                <label><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_PATH") ?>:</label>
            </td>
            <td>
                <input type="text" name="settings[excel_path]" size="40" class="adm-input"
                       value="/upload/export_<?= date("Y-m-d"); ?>.xlsx">
            </td>
        </tr>
        <tr class="excel-settings">
            <td>
                <label><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_MULTI_DELIMITER") ?>:</label>
            </td>
            <td>
                <select name="settings[excel_multi_delimiter]" class="adm-input">
                    <option value=","><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_1") ?></option>
                    <option value=";"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_2") ?></option>
                    <option value="\t"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_3") ?></option>
                    <option value="|"><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_DELIMITER_4") ?></option>
                </select>
            </td>
        </tr>
        <tr class="excel-settings">
            <td>
                <label><?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_NEED_EXEL_HEADER") ?>:</label>
            </td>
            <td>
                <input type="checkbox" name="settings[excel_headers]" value="Y">
            </td>
        </tr>

        <?php $tabControl->Buttons(); ?>

        <input type="submit" id="submit_btn"
               value="<?= Loc::getMessage("DDAPP_EXPORT_BTN_SAVE") ?>" <?= $btnDisabled ? "disabled" : "" ?>>
        <input type="button" id="cancel_btn"
               value="<?= Loc::getMessage("DDAPP_EXPORT_BTN_CANCEL") ?>" <?= $btnDisabled ? "disabled" : "" ?>>

        <?php $tabControl->End(); ?>

    </form>

    <script>
        BX.ready(function () {
            // Инициализация
            new BX.DDAPP.Tools.ExportProfileManager({
                ajaxUrl: '<?= Main::getAjaxUrl("admin/ddapp_data_export.php") ?>',
                messageTitle: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_TITLE")?>',
                messageError: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_ERROR")?>',
                messageErrorServerConnect: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_ERROR_SERVER_CONNECT")?>',
                messageBeforeDelete: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_BEFORE_DELETE")?>',
                messageProfileSelect: '<?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_PROFILE_SELECT")?>',
                messageIblockSelect: '<?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_IBLOCK_SELECT")?>',
                messageIblockField: '<?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_IBLOCK_FIELD")?>',
                messageIblockProperty: '<?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_IBLOCK_PROPERTY")?>',
                messageIblockTypeSelect: '<?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_IBLOCK_TYPE_SELECT")?>',
                messageIblockTypeSelectFirst: '<?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_IBLOCK_SELECT_FIRST")?>',
                messageProfileLoadError: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_PROFILE_ERROR")?>',
                messageProfileSaveError: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_PROFILE_SAVE_ERROR")?>',
                messageProfileSelectError: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_PROFILE_SELECT_ERROR")?>',
                messageProfileDeleteError: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_PROFILE_DELETE_ERROR")?>',
                messageProfileDeleteOk: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_PROFILE_DELETE")?>',
                messageIblockSelectError: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_IBLOCK_TYPE_ERROR")?>',
            });
            new BX.DDAPP.Tools.ExportManager({
                ajaxUrl: '<?= Main::getAjaxUrl("admin/ajax/data_export.php") ?>',
                messageBeforeUnload: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_BEFORE_UNLOAD")?>',
                messageWrongServerResponse: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_WRONG_SERVER_RESPONSE")?>',
                messageUnknownError: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_UNKNOWN_ERROR")?>',
                messageUnknownStatus: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_UNKNOWN_STATUS")?>',
                messageErrorServerConnect: '<?= Loc::getMessage("DDAPP_EXPORT_MESSAGE_ERROR_SERVER_CONNECT")?>',
                messageFrom: '<?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_FROM")?>',
                messageFile: '<?= Loc::getMessage("DDAPP_EXPORT_SETTINGS_FILE")?>',
            });
        });
    </script>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");