<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!check_bitrix_sessid()) {
    return;
}

if ($errorException = $APPLICATION->getException()) {
    // Вывод сообщения об ошибке при удалении модуля
    CAdminMessage::showMessage(
        Loc::getMessage("DDAPP_TOOLS_DEINSTALL_FAILED") . ": " . $errorException->GetString()
    );
} else {
    // Вывод уведомления при успешном удалении модуля
    CAdminMessage::showNote(
        Loc::getMessage("DDAPP_TOOLS_DEINSTALL_SUCCESS")
    );
}
?>

<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="submit" name="" value="<?= Loc::getMessage("MOD_BACK") ?>">
</form>