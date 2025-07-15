<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

if (!check_bitrix_sessid()) {
    return;
}

$btnDisabled = "";
if (!CheckVersion(ModuleManager::getVersion('main'), '20.0.0')) {
    $btnDisabled = "disabled";
    CAdminMessage::showMessage(Loc::getMessage("DDAPP_TOOLS_INSTALL_ERROR_VERSION"));
}
?>

<form action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="hidden" name="id" value="ddapp.tools">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="step" value="2">
    <p><?= Loc::getMessage("DDAPP_TOOLS_ADDAPP_DATA") ?></p>
    <p><input type="checkbox" name="addapp_data" id="addapp_data" value="Y" checked><label
                for="addapp_data"><?= Loc::getMessage("DDAPP_TOOLS_ADDAPP_DATA_BUTTON") ?></label></p>
    <input type="submit" name="" value="<?= Loc::getMessage("MOD_INSTALL") ?>" <?= $btnDisabled ?>>
</form>