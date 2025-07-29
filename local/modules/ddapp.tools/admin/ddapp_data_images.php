<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;
use DDAPP\Tools\Main;
use DDAPP\Tools\DataImages;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\UserHelper;
use DDAPP\Tools\Helpers\IblockHelper;

Loc::loadMessages(__FILE__);

// Подключаем JS и CSS
Main::includeJS("admin/js/images_manager.js");
Main::includeJS("admin/js/images_profile_manager.js");
Main::includeCSS("admin/css/data_images_form.css");

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
            $profiles = DataImages::getItems([
                "select" => ["ID", "NAME", "IBLOCK_TYPE_ID", "IBLOCK_ID", "ZIP_FILE", "SETTINGS"]
            ]);
            echo Json::encode(["success" => true, "data" => $profiles]);
            exit;

        case "get_profile":
            if (!empty($request->getPost("profile_id"))) {
                $profile = DataImages::getById($request->getPost("profile_id"));
                echo Json::encode(["success" => true, "data" => $profile]);
            }
            exit;

        case "save_profile":
            $fields = [
                "NAME" => $request->getPost("name"),
                "IBLOCK_TYPE_ID" => $request->getPost("iblock_type_id"),
                "IBLOCK_ID" => $request->getPost("iblock_id"),
                "ZIP_FILE" => $request->getPost("zip_file"),
                "SETTINGS" => Json::encode($request->getPost("settings"))
            ];

            if (empty($request->getPost("name"))) {
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_NAME_ERROR")]);
                exit;
            }
            if (empty($request->getPost("iblock_id"))) {
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_IBLOCK_ERROR")]);
                exit;
            }
            if (empty($request->getPost("zip_file"))) {
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_FILE_ERROR")]);
                exit;
            }
            if (empty($request->getPost("settings")["images_field"])) {
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_FIELD_ERROR")]);
                exit;
            }
            if (empty($request->getPost("settings")["images_code"])) {
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_FIELD_CODE")]);
                exit;
            }
            if (!empty($request->getPost("profile_id"))) {
                DataImages::update($request->getPost("profile_id"), $fields);
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_ADD")]);
            } else {
                $result = DataImages::add($fields);
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_EDIT"), "id" => $result->getId()]);
            }
            exit;

        case "delete_profile":
            if (!empty($request->getPost("profile_id"))) {
                DataImages::delete($request->getPost("profile_id"));
                echo Json::encode(["success" => true, "message" => Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_DELETE")]);
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

                foreach (IblockHelper::getDefaultFieldsNames("F") as $code => $name) {
                    $fields[] = ["CODE" => $code, "NAME" => $name, "TYPE" => "FIELD", "PROPERTY_TYPE" => "", "MULTIPLE" => "N"];
                }

                $properties = IblockHelper::getAllProperties([
                    "select" => ["ID", "CODE", "NAME", "PROPERTY_TYPE", "MULTIPLE", "LIST_TYPE", "USER_TYPE"],
                    "filter" => ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y", "PROPERTY_TYPE" => "F"],
                    "order" => ["SORT" => "ASC", "NAME" => "ASC"]
                ]);

                foreach ($properties as $property) {
                    $propertyName = $property["NAME"];

                    // Добавляем информацию о типе свойства
                    if ($property["MULTIPLE"] === "Y") {
                        $propertyName .= Loc::getMessage("DDAPP_IMAGES_PROPERTY_TYPE_M");
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
        "TEXT" => Loc::getMessage("DDAPP_IMAGES_BTN_IMPORT"),
        "ICON" => "btn_green",
        "LINK" => "/bitrix/admin/ddapp_maintenance_list.php?lang=" . LANG,
        "TITLE" => Loc::getMessage("DDAPP_MAINTENANCE_BTN_TO_LIST"),
        "LINK_PARAM" => "id='btn_upload'"
    ]
]);
?>

    <div class="adm-info-message-wrap adm-info-message-gray" id="upload_message">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?= Loc::getMessage("DDAPP_IMAGES_PROCESS_TITLE") ?></div>
            <?= Loc::getMessage("DDAPP_IMAGES_PROCESS_ITEMS_OK") ?><strong><span
                        id="upload_message_ok">0</span></strong>
            <br><?= Loc::getMessage("DDAPP_IMAGES_PROCESS_ITEMS_ERROR") ?><strong><span
                        id="upload_message_error">0</span></strong>
            <p id="upload_message_file"></p>
            <div class="adm-progress-bar-outer" style="width: 500px;">
                <div class="adm-progress-bar-inner" id="progress_percent_a" style="width: 0;">
                    <div class="adm-progress-bar-inner-text" id="progress_percent_b" style="width: 500px;">0%</div>
                </div>
                <span id="progress_percent_c">0%</span>
            </div>
        </div>
    </div>

<?= $oMenu->Show(); ?>

    <form id="data_images_form" name="data_images_form" class="data-images-form">
        <input type="hidden" id="profile_id" name="profile_id">
        <?= bitrix_sessid_post() ?>

        <?php
        $tabControl = new CAdminTabControl("tabControl", [
            [
                "DIV" => "edit1",
                "TAB" => Loc::getMessage("DDAPP_IMAGES_TAB1"),
                "TITLE" => Loc::getMessage("DDAPP_IMAGES_TAB1_TITLE")
            ]
        ]);
        $tabControl->Begin();
        ?>

        <?php $tabControl->BeginNextTab(); ?>

        <tr>
            <td width="40%"
                style="position: relative; top: -4px;"><?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_PROFILE") ?>
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
            <td colspan="2"><?= Loc::getMessage("DDAPP_IMAGES_BLOCK1") ?></td>
        </tr>
        <tr class="profile-settings">
            <td>
                <label for="profile_name"><?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_PROFILE_NAME") ?>:</label>
            </td>
            <td>
                <input type="text" id="profile_name" name="name" class="adm-input">
            </td>
        </tr>
        <tr class="profile-settings">
            <td>
                <label for="iblock_type_select"><?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_IBLOCK_TYPE") ?>:</label>
            </td>
            <td>
                <select id="iblock_type_select" name="iblock_type_id" class="adm-input">
                    <option value=""><?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_IBLOCK_TYPE_SELECT") ?></option>
                </select>
            </td>
        </tr>
        <tr class="profile-settings">
            <td>
                <label for="iblock_select"><?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_IBLOCK") ?>:</label>
            </td>
            <td>
                <select id="iblock_select" name="iblock_id" class="adm-input" disabled>
                    <option value=""><?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_IBLOCK_SELECT") ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_FILE") ?></td>
            <td>
                <input type="text" id="zip_file" name="zip_file"
                       value="<? echo htmlspecialcharsbx($URL_DATA_FILE); ?>" size="30">
                <input type="button" value="<?= Loc::getMessage("DDAPP_IMAGES_BTN_FILE") ?>" OnClick="BtnClick()">
                <?php
                CAdminFileDialog::ShowScript([
                    "event" => "BtnClick",
                    "arResultDest" => [
                        "FORM_NAME" => "data_images_form",
                        "FORM_ELEMENT_NAME" => "zip_file",
                    ],
                    "arPath" => [
                        "SITE" => SITE_ID,
                        "PATH" => "/upload",
                    ],
                    "select" => 'F', // F - file only, D - folder only
                    "operation" => 'O', // O - open, S - save
                    "showUploadTab" => true,
                    "showAddToMenuTab" => false,
                    "fileFilter" => 'zip',
                    "allowAllFiles" => true,
                    "SaveConfig" => true,
                ]);
                ?>
            </td>
        </tr>

        <!-- Настройки импорта -->
        <tr class="heading fields-selection">
            <td colspan="2"><?= Loc::getMessage("DDAPP_IMAGES_BLOCK2") ?></td>
        </tr>
        <tr class="fields-selection">
            <td>
                <label for="images_field"><?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_FIELD") ?>:</label>
            </td>
            <td>
                <select id="images_field" name="settings[images_field]" class="adm-input">
                    <option value=""><?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_FIELD_SELECT") ?></option>
                </select>
            </td>
        </tr>
        <tr class="fields-selection">
            <td>
                <label for="images_code"><?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_CODE") ?>:</label>
            </td>
            <td>
                <input type="text" id="images_code" name="settings[images_code]" size="20" placeholder="CML2_ARTICLE">
            </td>
        </tr>

        <?php $tabControl->Buttons(); ?>

        <input type="submit" id="submit_btn"
               value="<?= Loc::getMessage("DDAPP_IMAGES_BTN_SAVE") ?>" <?= $btnDisabled ? "disabled" : "" ?>>
        <input type="button" id="cancel_btn"
               value="<?= Loc::getMessage("DDAPP_IMAGES_BTN_CANCEL") ?>" <?= $btnDisabled ? "disabled" : "" ?>>

        <?php $tabControl->End(); ?>

    </form>

    <script>
        BX.ready(function () {
            BX.message({
                DDAPP_IMAGES_MESSAGE_TITLE: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_TITLE") ?>',
                DDAPP_IMAGES_MESSAGE_ERROR: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_ERROR") ?>',
                DDAPP_IMAGES_MESSAGE_ERROR_SERVER_CONNECT: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_ERROR_SERVER_CONNECT") ?>',
                DDAPP_IMAGES_MESSAGE_BEFORE_DELETE: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_BEFORE_DELETE") ?>',
                DDAPP_IMAGES_SETTINGS_PROFILE_SELECT: '<?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_PROFILE_SELECT") ?>',
                DDAPP_IMAGES_SETTINGS_IBLOCK_SELECT: '<?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_IBLOCK_SELECT") ?>',
                DDAPP_IMAGES_SETTINGS_IBLOCK_FIELD: '<?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_IBLOCK_FIELD") ?>',
                DDAPP_IMAGES_SETTINGS_IBLOCK_PROPERTY: '<?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_IBLOCK_PROPERTY") ?>',
                DDAPP_IMAGES_SETTINGS_IBLOCK_TYPE_SELECT: '<?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_IBLOCK_TYPE_SELECT") ?>',
                DDAPP_IMAGES_SETTINGS_IBLOCK_SELECT_FIRST: '<?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_IBLOCK_SELECT_FIRST") ?>',
                DDAPP_IMAGES_MESSAGE_PROFILE_ERROR: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_ERROR") ?>',
                DDAPP_IMAGES_MESSAGE_PROFILE_SAVE_ERROR: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_SAVE_ERROR") ?>',
                DDAPP_IMAGES_MESSAGE_PROFILE_SELECT_ERROR: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_SELECT_ERROR") ?>',
                DDAPP_IMAGES_MESSAGE_PROFILE_DELETE_ERROR: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_DELETE_ERROR") ?>',
                DDAPP_IMAGES_MESSAGE_PROFILE_DELETE: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_PROFILE_DELETE") ?>',
                DDAPP_IMAGES_MESSAGE_IBLOCK_TYPE_ERROR: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_IBLOCK_TYPE_ERROR") ?>',
                DDAPP_IMAGES_MESSAGE_BEFORE_UNLOAD: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_BEFORE_UNLOAD") ?>',
                DDAPP_IMAGES_MESSAGE_WRONG_SERVER_RESPONSE: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_WRONG_SERVER_RESPONSE") ?>',
                DDAPP_IMAGES_MESSAGE_UNKNOWN_ERROR: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_UNKNOWN_ERROR") ?>',
                DDAPP_IMAGES_MESSAGE_UNKNOWN_STATUS: '<?= Loc::getMessage("DDAPP_IMAGES_MESSAGE_UNKNOWN_STATUS") ?>',
                DDAPP_IMAGES_SETTINGS_FROM: '<?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_FROM") ?>',
                DDAPP_IMAGES_SETTINGS_FILE: '<?= Loc::getMessage("DDAPP_IMAGES_SETTINGS_FILE") ?>',
            });
            // Инициализация
            new BX.DDAPP.Tools.ImagesProfileManager({
                ajaxUrl: '<?= Main::getAjaxUrl("admin/ddapp_data_images.php") ?>',
            });
            new BX.DDAPP.Tools.ImagesManager({
                ajaxUrl: '<?= Main::getAjaxUrl("admin/ajax/data_images.php") ?>',
            });
        });
    </script>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");