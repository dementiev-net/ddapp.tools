<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use DDAPP\Tools\Maintenance;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\UserHelper;

Loc::loadMessages(__FILE__);

// Получим права доступа текущего пользователя на модуль
if (UserHelper::hasModuleAccess("") == "D") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$btnDisabled = UserHelper::hasModuleAccess("") >= "W" ? false : true;

// Настройка логирования
LogHelper::configure();

$request = Application::getInstance()->getContext()->getRequest();
$ID = intval($request->get("ID"));
$isEdit = $ID > 0;

$APPLICATION->SetTitle($isEdit ? Loc::getMessage("DDAPP_PAGE_TITLE_EDIT") : Loc::getMessage("DDAPP_PAGE_TITLE_ADD"));

// Контекстное меню
$context = new CAdminContextMenu([
    [
        "TEXT" => Loc::getMessage("DDAPP_MAINTENANCE_BTN_TO_LIST"),
        "ICON" => "btn_list",
        "LINK" => "/bitrix/admin/ddapp_maintenance_list.php?lang=" . LANG,
        "TITLE" => Loc::getMessage("DDAPP_MAINTENANCE_BTN_TO_LIST"),
    ]
]);

// Инициализация переменных
$arFields = [
    "NAME" => "",
    "LINK" => "",
    "DESCRIPTION" => "",
    "ACTIVE" => "Y",
    "PRIORITY" => 1,
    "TYPE" => "SCHEDULED"
];

$arErrors = [];

// Сообщение
$ob = new \CAdminMessage([]);

// Получаем данные записи при редактировании
if ($isEdit) {
    if ($existingData = Maintenance::getById($ID)) {
        $arFields = array_merge($arFields, $existingData);
    } else {
        $ob->ShowMessage("План обслуживания не завершен");
        ShowError("Запись не найдена");
        return;
    }
}

// Обработка формы
if ($request->isPost() && check_bitrix_sessid() && UserHelper::hasModuleAccess("") >= "W") {

    $arFields["NAME"] = trim($request->getPost("NAME"));
    $arFields["LINK"] = trim($request->getPost("LINK"));
    $arFields["DESCRIPTION"] = trim($request->getPost("DESCRIPTION"));
    $arFields["ACTIVE"] = $request->getPost("ACTIVE") === "Y" ? "Y" : "N";
    $arFields["PRIORITY"] = intval($request->getPost("PRIORITY"));
    $arFields["TYPE"] = trim($request->getPost("TYPE"));

    // Валидация
    if (empty($arFields["NAME"])) $arErrors[] = Loc::getMessage("DDAPP_MAINTENANCE_MESSAGE_ERROR_NAME_EMPTY");
    if (strlen($arFields["NAME"]) > 255) $arErrors[] = Loc::getMessage("DDAPP_MAINTENANCE_MESSAGE_ERROR_NAME_TOO_LONG");
    if ($arFields["PRIORITY"] < 1) $arFields["PRIORITY"] = 1;

    // Сохранение
    if (empty($arErrors)) {
        $saveFields = $arFields;

        // Добавляем служебные поля
        if (!$isEdit) {
            $saveFields["DATE_CREATE"] = new DateTime();
        }
        $saveFields["DATE_MODIFY"] = new DateTime();

        try {
            if ($isEdit) {
                $result = Maintenance::update($ID, $saveFields);
            } else {
                $result = Maintenance::add($saveFields);
                $ID = $result->getId();
            }

            if ($result->isSuccess()) {
                // Обработка действий кнопок
                if ($request->getPost("save")) LocalRedirect("/bitrix/admin/ddapp_maintenance_list.php?ID=" . $ID . "&lang=" . LANG . "&" . GetFilterParams("F_") . "&save_success=Y");
                if ($request->getPost("apply")) LocalRedirect("/bitrix/admin/ddapp_maintenance_edit.php?ID=" . $ID . "&lang=" . LANG . "&" . GetFilterParams("F_") . "&apply_success=Y");
                LocalRedirect("/bitrix/admin/ddapp_maintenance_list.php?lang=" . LANG . "&" . GetFilterParams("F_"));

            } else {
                $arErrors = $result->getErrorMessages();
            }
        } catch (Exception $e) {
            $arErrors[] = "Ошибка сохранения: " . $e->getMessage();
        }
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

// Показываем сообщения об успехе
if ($request->get("apply_success") === "Y") {
    CAdminMessage::ShowMessage(["MESSAGE" => Loc::getMessage("DDAPP_MAINTENANCE_MESSAGE_EDIT_OK"), "TYPE" => "OK"]);
}

// Показываем ошибки
if (!empty($arErrors)) {
    $message = new CAdminMessage([
        "TYPE" => "ERROR",
        "MESSAGE" => Loc::getMessage("DDAPP_MAINTENANCE_MESSAGE_ERROR"),
        "DETAILS" => implode("<br>", $arErrors),
        "HTML" => true
    ]);
    echo $message->Show();
}

// Контекстное меню
echo $context->Show();
?>

    <form method="POST"
          action="<?= $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?><?php if ($ID > 0) echo "&ID=" . $ID ?>"
          enctype="multipart/form-data">
        <?= bitrix_sessid_post() ?>

        <?php
        $tabControl = new CAdminTabControl("tabControl", [
            [
                "DIV" => "edit1",
                "TAB" => Loc::getMessage("DDAPP_MAINTENANCE_TAB1"),
                "TITLE" => Loc::getMessage("DDAPP_MAINTENANCE_TAB1_TITLE")
            ], [
                "DIV" => "edit2",
                "TAB" => Loc::getMessage("DDAPP_MAINTENANCE_TAB2"),
                "TITLE" => Loc::getMessage("DDAPP_MAINTENANCE_TAB2_TITLE")
            ]
        ]);
        $tabControl->Begin();
        ?>

        <?php $tabControl->BeginNextTab(); ?>

        <?php if ($isEdit): ?>
            <tr>
                <td><?= Loc::getMessage("DDAPP_MAINTENANCE_ID_FIELD") ?>:</td>
                <td><?= $ID ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td width="40%"><?= Loc::getMessage("DDAPP_MAINTENANCE_ACTIVE_FIELD") ?>:</td>
            <td width="60%">
                <input type="checkbox" name="ACTIVE" value="Y" <?php if ($arFields["ACTIVE"] === "Y") echo "checked" ?>
                       id="ACTIVE">
                <label for="ACTIVE"></label>
            </td>
        </tr>
        <tr>
            <td><span><?= Loc::getMessage("DDAPP_MAINTENANCE_NAME_FIELD") ?>:</span></td>
            <td>
                <input type="text" name="NAME" value="<?= htmlspecialcharsEx($arFields["NAME"]) ?>" size="50"
                       maxlength="255">
            </td>
        </tr>
        <tr>
            <td><span><?= Loc::getMessage("DDAPP_MAINTENANCE_LINK_FIELD") ?>:</span></td>
            <td>
                <input type="text" name="LINK" value="<?= htmlspecialcharsEx($arFields["LINK"]) ?>" size="70"
                       maxlength="255">
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage("DDAPP_MAINTENANCE_DESCRIPTION_FIELD") ?>:</td>
            <td>
                <textarea name="DESCRIPTION" rows="10"
                          cols="65"><?= htmlspecialcharsEx($arFields["DESCRIPTION"]) ?></textarea>
            </td>
        </tr>

        <?php $tabControl->BeginNextTab(); ?>

        <tr>
            <td width="40%"><?= Loc::getMessage("DDAPP_MAINTENANCE_PRIORITY_FIELD") ?>:</td>
            <td width="60%">
                <input type="number" name="PRIORITY" value="<?= intval($arFields["PRIORITY"]) ?>" min="1" max="100">
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage("DDAPP_MAINTENANCE_TYPE_FIELD") ?>:</td>
            <td>
                <select name="TYPE">
                    <option value=""><?= Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_NO") ?></option>
                    <option value="SCHEDULED" <?php if ($arFields["TYPE"] === "SCHEDULED") echo "selected" ?>><?= Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_TYPE")["SCHEDULED"] ?></option>
                    <option value="EMERGENCY" <?php if ($arFields["TYPE"] === "EMERGENCY") echo "selected" ?>><?= Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_TYPE")["EMERGENCY"] ?></option>
                    <option value="PREVENTIVE" <?php if ($arFields["TYPE"] === "PREVENTIVE") echo "selected" ?>><?= Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_TYPE")["PREVENTIVE"] ?></option>
                </select>
            </td>
        </tr>

        <?php $tabControl->Buttons([
            "disabled" => $btnDisabled,
            "back_url" => "/bitrix/admin/ddapp_maintenance_list.php?lang=" . LANG . "&" . GetFilterParams("F_")
        ]); ?>

        <?php $tabControl->End(); ?>
    </form>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");