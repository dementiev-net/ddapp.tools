<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use DDAPP\Tools\Main;
use DDAPP\Tools\Maintenance;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\UserHelper;
use DDAPP\Tools\Helpers\UrlHelper;

Loc::loadMessages(__FILE__);

$sTableID = "maintenance_table";

// Получим права доступа текущего пользователя на модуль
if (UserHelper::hasModuleAccess("") == "D") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

// Настройка логирования
LogHelper::configure();

$APPLICATION->SetTitle(Loc::getMessage("DDAPP_PAGE_TITLE"));

$oSort = new CAdminSorting($sTableID, "ID", "desc");

$arOrder = (strtoupper($by) === "ID" ? [$by => $order] : [$by => $order, "ID" => "ASC"]);
$lAdmin = new CAdminUiList($sTableID, $oSort);

// Поля для фильтрации
$arFilterFields = [
    "find_name",
    "find_active",
    "find_type",
];

$lAdmin->InitFilter($arFilterFields);

// Подготовка фильтра
$arFilter = [];
if (!empty($find_name)) {
    $arFilter["?NAME"] = $find_name;
}
if (!empty($find_active)) {
    $arFilter["ACTIVE"] = $find_active;
}
if (!empty($find_type)) {
    $arFilter["TYPE"] = $find_type;
}

// Поля фильтра для нового интерфейса
$filterFields = [
    ["id" => "NAME", "name" => Loc::getMessage("DDAPP_MAINTENANCE_NAME_FIELD"), "filterable" => "?", "quickSearch" => "?", "default" => true],
    ["id" => "ACTIVE", "name" => Loc::getMessage("DDAPP_MAINTENANCE_ACTIVE_FIELD"), "filterable" => "", "type" => "list", "items" => array_merge(["" => Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_ALL")], Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_ACTIVE")), "default" => true],
    ["id" => "TYPE", "name" => Loc::getMessage("DDAPP_MAINTENANCE_TYPE_FIELD"), "filterable" => "", "type" => "list", "items" => Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_TYPE"), "default" => true],
    //["id" => "DATE_CREATE", "name" => "Дата создания", "filterable" => "", "type" => "date", "default" => true],
];

$lAdmin->AddFilter($filterFields, $arFilter);

// Обработка действий
if (($arID = $lAdmin->GroupAction()) && UserHelper::hasModuleAccess("") == "W") {

    if (!empty($_REQUEST["action_all_rows_" . $sTableID]) && $_REQUEST["action_all_rows_" . $sTableID] === "Y") {

        $rsData = Maintenance::getItems([
            "order" => [$by => $order],
            "filter" => $arFilter
        ]);
        while ($arRes = $rsData->fetch())
            $arID[] = $arRes["ID"];
    }

    foreach ($arID as $ID) {
        if (strlen($ID) <= 0)
            continue;

        $ID = IntVal($ID);

        switch ($_REQUEST["action"]) {
            case "activate":
                try {
                    Maintenance::activate($ID);
                } catch (Exception $e) {
                    $lAdmin->AddGroupError(Loc::getMessage("DDAPP_MAINTENANCE_MESSAGE_ERROR"), $ID);
                }
                break;

            case "deactivate":
                try {
                    Maintenance::deactivate($ID);
                } catch (Exception $e) {
                    $lAdmin->AddGroupError(Loc::getMessage("DDAPP_MAINTENANCE_MESSAGE_ERROR"), $ID);
                }
                break;

            case "delete":
                try {
                    Maintenance::delete($ID);
                } catch (Exception $e) {
                    $lAdmin->AddGroupError(Loc::getMessage("DDAPP_MAINTENANCE_MESSAGE_ERROR"), $ID);
                }
                break;
        }
    }
}

// Заголовки колонок
$lAdmin->AddHeaders([
    ["id" => "ID", "content" => Loc::getMessage("DDAPP_MAINTENANCE_ID_FIELD"), "sort" => "ID", "default" => true],
    ["id" => "NAME", "content" => Loc::getMessage("DDAPP_MAINTENANCE_NAME_FIELD"), "sort" => "NAME", "default" => true],
    ["id" => "LINK", "content" => Loc::getMessage("DDAPP_MAINTENANCE_LINK_FIELD"), "sort" => "LINK", "default" => true],
    //["id" => "DESCRIPTION", "content" => Loc::getMessage("DDAPP_MAINTENANCE_DESCRIPTION_FIELD"), "sort" => "DESCRIPTION", "default" => true],
    ["id" => "ACTIVE", "content" => Loc::getMessage("DDAPP_MAINTENANCE_ACTIVE_FIELD"), "sort" => "ACTIVE", "default" => true, "align" => "center"],
    ["id" => "PRIORITY", "content" => Loc::getMessage("DDAPP_MAINTENANCE_PRIORITY_FIELD"), "sort" => "PRIORITY", "default" => true, "align" => "center"],
    ["id" => "TYPE", "content" => Loc::getMessage("DDAPP_MAINTENANCE_TYPE_FIELD"), "sort" => "TYPE", "default" => true, "align" => "center"],
    ["id" => "DATE_CREATE", "content" => Loc::getMessage("DDAPP_MAINTENANCE_DATE_CREATE_FIELD"), "sort" => "DATE_CREATE", "default" => false],
    ["id" => "DATE_MODIFY", "content" => Loc::getMessage("DDAPP_MAINTENANCE_DATE_MODIFY_FIELD"), "sort" => "DATE_MODIFY", "default" => false],
]);

// Получение данных
$rsData = Maintenance::getItems([
    "order" => [$by => $order],
    "filter" => $arFilter,
    "select" => ["*"]
]);

$rsData = new CAdminUiResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->SetNavigationParams($rsData);

// Обработка строк
while ($arRes = $rsData->NavNext(true, "f_")) {

    $edit_link = "ddapp_maintenance_edit.php?ID=" . $f_ID . "&lang=" . LANG;

    $row =& $lAdmin->AddRow($f_ID, $arRes, $edit_link, Loc::getMessage("DDAPP_MAINTENANCE_BTN_EDIT"));

    // Форматирование полей
    $row->AddViewField("ID", "<a href='" . $edit_link . "'>" . $f_ID . "</a>");
    $row->AddViewField("NAME", htmlspecialcharsEx($f_NAME));
    $row->AddViewField("LINK", "<a href='" . $f_LINK . "'>" . $f_LINK . "</a>");
    //$row->AddViewField("DESCRIPTION", TruncateText(htmlspecialcharsEx($f_DESCRIPTION), 100));
    $row->AddViewField("ACTIVE", "<span style='color: " . Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_ACTIVE_COLOR")[$f_ACTIVE] . "'>" . Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_ACTIVE")[$f_ACTIVE] . "</span>");
    $row->AddViewField("PRIORITY", $f_PRIORITY . " " . Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_PRIOR"));
    $row->AddViewField("TYPE", "<span style='color: " . Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_TYPE_COLOR")[$f_TYPE] . "'>" . Loc::getMessage("DDAPP_MAINTENANCE_FIELD_VALUE_TYPE")[$f_TYPE] . "</span>");
    $row->AddViewField("DATE_CREATE", $f_DATE_CREATE ? FormatDate("d.m.Y H:i", MakeTimeStamp($f_DATE_CREATE)) : "");
    $row->AddViewField("DATE_MODIFY", $f_DATE_MODIFY ? FormatDate("d.m.Y H:i", MakeTimeStamp($f_DATE_MODIFY)) : "");

    // Действия для строки
    $arActions = [];

    if (UserHelper::hasModuleAccess("") >= "W") {
        $arActions[] = [
            "ICON" => "edit",
            "TEXT" => Loc::getMessage("DDAPP_MAINTENANCE_BTN_EDIT"),
            "ACTION" => $lAdmin->ActionRedirect($edit_link)
        ];

        if ($f_ACTIVE == "Y") {
            $arActions[] = [
                "ICON" => "deactivate",
                "TEXT" => Loc::getMessage("DDAPP_MAINTENANCE_BTN_DEACTIVATE"),
                "ACTION" => $lAdmin->ActionDoGroup($f_ID, "deactivate")
            ];
        } else {
            $arActions[] = [
                "ICON" => "activate",
                "TEXT" => Loc::getMessage("DDAPP_MAINTENANCE_BTN_ACTIVATE"),
                "ACTION" => $lAdmin->ActionDoGroup($f_ID, "activate")
            ];
        }

        $arActions[] = [
            "SEPARATOR" => true
        ];

        $arActions[] = [
            "ICON" => "delete",
            "TEXT" => Loc::getMessage("DDAPP_MAINTENANCE_BTN_DELETE"),
            "ACTION" => "if(confirm('" . Loc::getMessage("DDAPP_MAINTENANCE_BTN_DELETE_INFO") . "')) " . $lAdmin->ActionDoGroup($f_ID, "delete")
        ];
    }

    $row->AddActions($arActions);
}

// Подвал таблицы
$lAdmin->AddFooter([
        [
            "title" => Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"),
            "value" => $rsData->SelectedRowsCount()
        ], [
            "counter" => true,
            "title" => Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"),
            "value" => "0"
        ]
    ]
);

// Контекстное меню и групповые действия
if (UserHelper::hasModuleAccess("") >= "W") {
    $lAdmin->AddAdminContextMenu([
        [
            "TEXT" => Loc::getMessage("DDAPP_MAINTENANCE_BTN_ADD"),
            "LINK" => "ddapp_maintenance_edit.php?lang=" . LANG,
            "TITLE" => "",
            "ICON" => "btn_new",
        ],
    ]);

    $lAdmin->AddGroupActionTable([
        "activate" => Loc::getMessage("DDAPP_MAINTENANCE_BTN_ACTIVATE"),
        "deactivate" => Loc::getMessage("DDAPP_MAINTENANCE_BTN_DEACTIVATE"),
        "delete" => true,
        "for_all" => true,
    ]);
} else {
    $lAdmin->AddAdminContextMenu([]);
}

$lAdmin->CheckListMode();

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

// Показываем сообщения об успехе
$request = Application::getInstance()->getContext()->getRequest();
if ($request->get("save_success") === "Y") {
    CAdminMessage::ShowMessage(["MESSAGE" => Loc::getMessage("DDAPP_MAINTENANCE_MESSAGE_SAVE_OK"), "TYPE" => "OK"]);
    echo UrlHelper::deleteParams("save_success");
}

// Сообщение
$items = Maintenance::getAllItems();
$isCompleted = Maintenance::checkIfAllCompleted($items);
$lastCompletionDate = Option::get(Main::MODULE_ID, "maint_last_date");

$ob = new \CAdminMessage([]);
$planMessage = "";

if ($isCompleted) {
    $planMessage = Loc::getMessage("DDAPP_MAINTENANCE_MESSAGE_OK");
    if ($lastCompletionDate) {
        $planMessage .= " (" . Loc::getMessage("DDAPP_MAINTENANCE_MESSAGE_DATE_COMPLETE") . FormatDate("d.m.Y H:i", MakeTimeStamp($lastCompletionDate)) . ")";
    }
    $ob->ShowNote($planMessage);
} else {
    $ob->ShowMessage(Loc::getMessage("DDAPP_MAINTENANCE_MESSAGE_NO"));
}

// Отображение интерфейса
$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");