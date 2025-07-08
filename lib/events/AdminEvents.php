<?php

namespace DDAPP\Tools\Events;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\AdminList;
use Bitrix\Main\UI\Extension;
use DDAPP\Tools\Main;
use DDAPP\Tools\DataImport;

Loc::loadMessages(__FILE__);

class AdminEvents
{
    /**
     * Событие OnAdminListDisplay вызывается в функции CAdminList::Display() при выводе
     * в административном разделе списка элементов
     * Событие позволяет модифицировать объект списка, в частности, добавить произвольные
     * групповые действия над элементами списка, добавить команды в меню действий элемента списка и т.п.
     * @param $list
     * @param $userMessageText
     * @return void
     */
    public static function OnAdminListDisplayHandler(&$list, &$userMessageText)
    {
        // Добавление кнопки "Запустить" для агентов
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
                            "TEXT" => Loc::getMessage("DDAPP_EVENT_ACTION_RUN"),
                            "ACTION" => $lAdmin->ActionDoGroup($v->id, "ddapp_agent_run", "&lang=" . LANG . "&agent_id=" . $v->id),
                        ];
                    }
                    $arNewActions[] = $act;
                }
                $v->aActions = $arNewActions;
            }
        }
    }

    /**
     * Событие OnAdminContextMenuShow вызывается в функции CAdminContextMenu::Show() при выводе
     * в административном разделе панели кнопок
     * Событие позволяет модифицировать или добавить собственные кнопки на панель.
     * @param $items
     * @return void
     */
    public static function OnAdminContextMenuShowHandler(&$items)
    {
        global $APPLICATION;

        $page = $APPLICATION->GetCurPage();
        $iblockId = $_REQUEST["IBLOCK_ID"];

        if ($page == "/bitrix/admin/iblock_element_edit.php" && $iblockId) {

            // Проверяем, есть ли профиль для данного инфоблока
            $profile = DataImport::getItems([
                "filter" => ["IBLOCK_ID" => $iblockId],
                "limit" => 1
            ])[0];

            if ($profile) {
                Extension::load("ui.dialogs.messagebox");
                \CJSCore::Init(["jquery"]);
                Main::includeJS("admin/js/xlsx.full.min.js");
                Main::includeJS("admin/js/import-excel_manager.js");
                Main::includeCSS("admin/css/data_import_form.css");

                $items[] = [
                    "TEXT" => Loc::getMessage("DDAPP_EVENT_MODAL_EXCEL_BTN"),
                    "TITLE" => Loc::getMessage("DDAPP_EVENT_MODAL_EXCEL_BTN_TITLE"),
                    "LINK" => "javascript:openExcelModal();",
                    "ICON" => "btn_new",
                ];

                // Получаем информацию о свойствах инфоблока
                $properties = [];
                $rsProps = \CIBlockProperty::GetList([], ["IBLOCK_ID" => $iblockId]);
                while ($prop = $rsProps->Fetch()) {
                    $properties[$prop["CODE"]] = $prop["ID"];
                }

                // Передаем настройки и свойства в JavaScript
                $settings = json_decode($profile["SETTINGS"], true);
                ?>
                <script>
                    BX.ready(function () {
                        new BX.DDAPP.Tools.ImportExcelManager({
                            iblockId: <?= $iblockId ?>,
                            settings: <?= json_encode($settings) ?>,
                            properties: <?= json_encode($properties) ?>,
                            modalMessageTitle: '<?= Loc::getMessage("DDAPP_EVENT_MODAL_TITLE")?>',
                            modalMessageFile: '<?= Loc::getMessage("DDAPP_EVENT_MODAL_FILE")?>',
                            modalMessageBtnClose: '<?= Loc::getMessage("DDAPP_EVENT_MODAL_BTN_CLOSE")?>',
                            messageTitle: '<?= Loc::getMessage("DDAPP_EVENT_MESSAGE_TITLE")?>',
                            messageError: '<?= Loc::getMessage("DDAPP_EVENT_MESSAGE_ERROR")?>',


                            messageFileImportError: '<?= Loc::getMessage("DDAPP_EVENT_MESSAGE_FILE_IMPORT_ERROR")?>',
                            messageFileReadtError: '<?= Loc::getMessage("DDAPP_EVENT_MESSAGE_FILE_READ_ERROR")?>',
                            messageImport: '<?= Loc::getMessage("DDAPP_EVENT_MESSAGE_IMPORT_MESSAGE")?>',
                            messageImportCellError: '<?= Loc::getMessage("DDAPP_EVENT_MESSAGE_CELL_IS_NULL_ERROR")?>',
                            messageImportFieldError: '<?= Loc::getMessage("DDAPP_EVENT_MESSAGE_FIELD_NOT_FOUND_ERROR")?>',
                            messageImportSelectorError: '<?= Loc::getMessage("DDAPP_EVENT_MESSAGE_SELECTOR_NOT_TRUE_ERROR")?>',


                        });
                    });
                </script>
                <div id="excel_import_div" style="display:none;">
                    <div style="margin: 20px;">
                        <p><?= Loc::getMessage("DDAPP_EVENT_MODAL_EXCEL_FIELDS") ?></p>
                        <ul style="margin-left: 20px; margin-bottom: 10px;">
                            <?php foreach ($settings["import_fields"] as $key => $value) { ?>
                                <li>
                                    <?= Loc::getMessage("DDAPP_EVENT_MODAL_EXCEL_FIELDS_CODE") ?> '<?= $value ?>'
                                    - <?= Loc::getMessage("DDAPP_EVENT_MODAL_EXCEL_FIELDS_CELL") ?> <?= $settings["import_cells"][$value] ?>
                                </li>
                            <? } ?>
                        </ul>
                        <div class="adm-file-input-container">
                            <label class="adm-file-btn adm-file-btn-success">
                                <span class="adm-file-btn-text"><?= Loc::getMessage("DDAPP_EVENT_MODAL_EXCEL_FILE_SELECT") ?></span>
                                <input type="file"
                                       id="excel-file"
                                       class="adm-file-btn-input"
                                       accept=".xlsx,.xls">
                            </label>
                            <span class="adm-file-input-filename" id="file-name"><?= Loc::getMessage("DDAPP_EVENT_MODAL_EXCEL_FILE_NOT_SELECTED") ?></span>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
    }
}