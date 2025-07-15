<?php

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid()) {
    return;
}
?>

<form action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="hidden" name="id" value="ddapp.tools">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">
    <?= CAdminMessage::ShowMessage(Loc::getMessage("MOD_UNINST_WARN")) ?>
    <p><?= Loc::getMessage("MOD_UNINST_SAVE") ?></p>
    <p><input type="checkbox" name="save_data" id="save_data" value="Y" checked><label
                for="save_data"><?= Loc::getMessage("DDAPP_TOOLS_MOD_UNINST_DATA") ?></label></p>
    <input type="submit" name="" value="<?= Loc::getMessage("DDAPP_TOOLS_MOD_UNINST_DATA_BUTTON") ?>">
</form>