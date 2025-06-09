<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Cобираем зарегистрированные через RegisterModuleDependences и AddEventHandler
// обработчики события OnSomeEvent
$rsHandlers = GetModuleEvents(DD_MODULE_NAMESPACE, "OnSomeEvent");

while ($arHandler = $rsHandlers->Fetch()) {
    // выполняем каждое зарегистрированное событие по одному
    ExecuteModuleEventEx($arHandler, array(/* параметры которые нужно передать в модуль */));
}