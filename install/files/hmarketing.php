<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Cобираем зарегистрированные через RegisterModuleDependences и AddEventHandler
// обработчики события OnSomeEvent
$rsHandlers = GetModuleEvents(Main::MODULE_ID, "OnSomeEvent");

while ($arHandler = $rsHandlers->Fetch()) {
    // выполняем каждое зарегистрированное событие по одному
    ExecuteModuleEventEx($arHandler, [/* параметры которые нужно передать в модуль */]);
}