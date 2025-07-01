<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\TypeTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\UI\Extension;
use DD\Tools\Helpers\LogHelper;
use DD\Tools\Entity\DataExportTable;

Loc::loadMessages(__FILE__);

$module_id = "dd.tools";

// Получим права доступа текущего пользователя на модуль
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$btnDisabled = true;
if ($moduleAccessLevel >= "W") $btnDisabled = false;

// Настройка логирования
LogHelper::configure();

// Подключаем необходимые модули
Loader::includeModule("iblock");
Extension::load("ui.dialogs.messagebox");

$APPLICATION->SetTitle("ТЕСТ");

// Контекстное меню
$context = new CAdminContextMenu([
    [
        "TEXT" => "Выгрузить" . Loc::getMessage("DD_MAINT_BTN_TO_LIST"),
        "ICON" => "btn_green",
        "LINK" => "#",
        "TITLE" => "К списку записей",
        "LINK_PARAM" => "class='btn_green'"
    ]
]);


require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
?>

    <div class="adm-info-message-wrap adm-info-message-gray" id="export_message">
        <div class="adm-info-message">
            <div class="adm-info-message-title">Экспорт завершен</div>
            Успешно экспортировано записей: <span id="export_message_ok">890</span>
            <br>С ошибками: <span id="export_message_error">0</span>
            <p id="export_message_file">Файл: <a href="/upload/232324-234-3.xls">/upload/232324-234-3.xls</a></p>
        </div>
    </div>

<?= $context->Show(); ?>

    <style>
        .adm-info-message-wrap {
            display: none;
        }
    </style>

    <script>
        BX.ready(function () {
            const exportButton = document.querySelector('.btn_green');
            const messageWrap = document.getElementById('export_message');
            const messageOk = document.getElementById('export_message_ok');
            const messageError = document.getElementById('export_message_error');
            const messageFile = document.getElementById('export_message_file');

            exportButton.addEventListener('click', function (e) {
                e.preventDefault();

                messageWrap.style.display = 'block';
                messageOk.textContent = "0 из 0";
                messageError.textContent = "0";
                messageFile.innerHTML = "";

                let exported = 0;
                let total = 0;

                function exportStep(nextStep = 0) {
                    BX.ajax({
                        url: '/local/ajax/export_csv.php',
                        data: {
                            step: nextStep,
                            totalExported: exported,
                            exportId: 123 // передайте нужный ID объекта/настройки экспорта
                        },
                        method: 'POST',
                        dataType: 'json',
                        onsuccess: function (response) {
                            if (response.status === 'processing') {
                                exported = response.exported;
                                total = response.total;

                                messageOk.textContent = exported + " из " + total;
                                messageError.textContent = response.errorsCount;

                                // Далее запускаем следующий шаг
                                exportStep(nextStep + 1);
                            } else if (response.status === 'done') {
                                exported = response.exported;
                                total = response.total;

                                messageOk.textContent = exported + " из " + total;
                                messageError.textContent = response.errorsCount;

                                messageFile.innerHTML = 'Файл: <a href="' + response.fileUrl + '">' + response.fileUrl + '</a>';
                            } else if (response.status === 'error') {
                                messageError.textContent = response.message;
                            }
                        },
                        onfailure: function() {
                            messageError.textContent = 'Ошибка запроса к серверу.';
                        }
                    });
                }

                exportStep(0);
            });
        });
    </script>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");