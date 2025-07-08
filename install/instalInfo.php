<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!check_bitrix_sessid()) {
    return;
}

if ($errorException = $APPLICATION->getException()) {
    // Вывод сообщения об ошибке при установке модуля
    CAdminMessage::showMessage(
        Loc::getMessage("DDAPP_TOOLS_INSTALL_FAILED") . ": " . $errorException->GetString()
    );
} else {
    // Вывод уведомления при успешной установке модуля
    CAdminMessage::showNote(
        Loc::getMessage("DDAPP_TOOLS_INSTALL_SUCCESS")
    );
}
?>

<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="submit" name="" value="<?= Loc::getMessage("MOD_BACK") ?>">
</form>