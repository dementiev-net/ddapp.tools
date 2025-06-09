<?php

namespace DD\Tools\Events;

use Bitrix\Main\Application;
use Bitrix\Main\UI\AdminList;
use Bitrix\Main\Localization\Loc;

class AdminEvents
{
    /**
     * @param $list
     * @param $userMessageText
     * @return void
     */
    public static function OnAdminListDisplayHandler(&$list, &$userMessageText)
    {
        if (Application::getInstance()->getContext()->getRequest()->getRequestedPage() == "/bitrix/admin/agent_list.php") {

            if (strlen($userMessageText)) {
                $message = new \CAdminMessage(["TYPE" => "OK", "MESSAGE" => $userMessageText]);
                echo $message->Show();
            }

            $lAdmin = new \CAdminList($list->table_id, $list->sort);

            foreach ($list->aRows as $id => $v) {
                $arNewActions = [];
                foreach ($v->aActions as $i => $act) {
                    if ($act["ICON"] == "delete") {
                        $arNewActions[] = [
                            "ICON" => "",
                            "TEXT" => Loc::getMessage("DD_ADMIN_EVENT_ACTION_RUN"),
                            "ACTION" => $lAdmin->ActionDoGroup($v->id, "dd_agent_run", "&lang=" . LANGUAGE_ID . "&agent_id=" . $v->id),
                        ];
                    }
                    $arNewActions[] = $act;
                }
                $v->aActions = $arNewActions;
            }
        }
    }

    /**
     * @param $items
     * @return void
     */
    public static function OnAdminContextMenuShowHandler(&$items)
    {
//        global $APPLICATION;
//        \Bitrix\Main\UI\Extension::load("ui.dialogs.messagebox");
//        $request = Context::getCurrent()->getRequest();
//        $iblockCatalogId = Iblock::getByCode(Config::IBLOCK_CATALOG);
//        $iblockOffersId = Iblock::getByCode(Config::IBLOCK_CATALOG_OFFERS);
//        if ($APPLICATION->GetCurPage() == "/bitrix/admin/iblock_element_edit.php" && ($request["IBLOCK_ID"] == $iblockCatalogId || $request["IBLOCK_ID"] == $iblockOffersId)) {
//            $items[] = array(
//                "TEXT" => "Запрос в 1С",
//                "TITLE" => "Выполнить запрос цен и остатков в 1С",
//                "LINK" => "javascript:customScriptFunction();",
//                "ICON" => "btn_edit",
//            );
//            \CJSCore::Init(array("jquery"));
//            echo "<script>
//            function customScriptFunction() {
//                BX.showWait();
//                const id = $("input[name=XML_ID]").val();
//                if (id) {
//                    BX.ajax({
//                        url: "/ajax/1с_update.php?id=" + id,
//                        method: "GET",
//                        dataType: "json",
//                        processData: false,
//                        preparePost: false,
//                        onsuccess: function(data) {
//                            let json = $.parseJSON(data);
//                            BX.UI.Dialogs.MessageBox.alert(
//                                json.message,
//                                json.status ? "Запрос остатков и цен в 1С" : "Ошибка запроса",
//                                (messageBox, button, event) => { window.location.reload(); }
//                            );
//                            BX.closeWait();
//                        },
//                        onfailure: function(data) {
//                            BX.UI.Dialogs.MessageBox.alert("Не получилось запросить остатки и цены в 1С", "Ошибка запроса");
//                            BX.closeWait();
//                        }
//                    });
//                } else {
//                    BX.UI.Dialogs.MessageBox.alert("Не удалось получить внешний код товара на странице", "Ошибка запроса");
//                    BX.closeWait();
//                }
//            }
//        </script>";
    }
}