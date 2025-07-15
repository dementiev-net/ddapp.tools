<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use DD\Tools\Main;

// Cобираем зарегистрированные обработчики события OnWebHook
$rsHandlers = GetModuleEvents(Main::MODULE_ID, "OnWebHook");

while ($arHandler = $rsHandlers->Fetch()) {
    // выполняем каждое зарегистрированное событие по одному
    ExecuteModuleEventEx($arHandler, [/* параметры которые нужно передать в модуль */]);
}