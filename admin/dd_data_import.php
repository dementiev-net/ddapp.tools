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
use DD\Tools\Main;
use DD\Tools\Helpers\LogHelper;

Loc::loadMessages(__FILE__);

$module_id = "dd.tools";

// Подключаем модуль
if (!CModule::IncludeModule($module_id)) {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
    ShowError("Модуль " . $module_id . " не установлен");
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
    die();
}

// Подключаем JS через функцию модуля
Main::includeJS("admin/js/dd_data_import.js");

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
            <div class="adm-info-message-title">Экспорт</div>
            Успешно экспортировано записей: <span id="export_message_ok">0</span>
            <br>С ошибками: <span id="export_message_error">0</span>
            <p id="export_message_file"></p>
        </div>
    </div>

<?= $context->Show(); ?>

    <style>
        .adm-info-message-wrap {
            display: none;
        }
    </style>

    <script>
        BX.ready(function() {
            // Инициализация
            new BX.DD.Tools.ExportManager({
                ajaxUrl: '<?= Main::getAjaxUrl("admin/ajax/export_csv.php") ?>',
                beforeUnloadMessage: 'Экспорт в процессе. Покинуть страницу?',
            });
        });
    </script>

    <script>
        // BX.ready(function() {
        //     const exportButton = BX('export_button') || document.querySelector('.btn_green');
        //     const messageWrap = BX('export_message');
        //     const messageOk = BX('export_message_ok');
        //     const messageError = BX('export_message_error');
        //     const messageFile = BX('export_message_file');
        //
        //     // Проверяем наличие всех необходимых элементов
        //     if (!exportButton || !messageWrap || !messageOk || !messageError || !messageFile) {
        //         console.warn('Export elements not found');
        //         return;
        //     }
        //
        //     let isExporting = false;
        //     let exported = 0;
        //     let total = 0;
        //     let errorsCount = 0;
        //
        //     BX.bind(exportButton, 'click', function(e) {
        //         e.preventDefault();
        //
        //         // Предотвращаем повторный запуск
        //         if (isExporting) {
        //             return;
        //         }
        //
        //         startExport();
        //     });
        //
        //     function startExport() {
        //         isExporting = true;
        //         exported = 0;
        //         total = 0;
        //         errorsCount = 0;
        //
        //         // Блокируем кнопку и добавляем индикацию загрузки
        //         exportButton.disabled = true;
        //         exportButton.classList.add('ui-btn-wait');
        //
        //         // Показываем блок с сообщениями
        //         messageWrap.style.display = 'block';
        //         messageOk.textContent = "0 из 0";
        //         messageError.textContent = "0";
        //         messageFile.innerHTML = "";
        //
        //         exportStep(0);
        //     }
        //
        //     function exportStep(nextStep = 0) {
        //         BX.ajax({
        //             url: '/local/ajax/export_csv.php',
        //             method: 'POST',
        //             dataType: 'json',
        //             timeout: 60, // Увеличиваем таймаут для больших объемов данных
        //             data: {
        //                 step: nextStep,
        //                 totalExported: exported,
        //                 exportId: getExportId(), // Получаем ID динамически
        //                 sessid: BX.bitrix_sessid() // Добавляем защиту от CSRF
        //             },
        //             onsuccess: function(response) {
        //                 if (!response) {
        //                     finishExport('Некорректный ответ сервера');
        //                     return;
        //                 }
        //
        //                 switch (response.status) {
        //                     case 'processing':
        //                         handleProcessing(response, nextStep);
        //                         break;
        //                     case 'done':
        //                         handleDone(response);
        //                         break;
        //                     case 'error':
        //                         finishExport(response.message || 'Неизвестная ошибка');
        //                         break;
        //                     default:
        //                         finishExport('Неизвестный статус: ' + response.status);
        //                 }
        //             },
        //             onfailure: function(xhr) {
        //                 console.error('Export AJAX Error:', xhr);
        //                 finishExport('Ошибка запроса к серверу (HTTP ' + (xhr.status || 'unknown') + ')');
        //             }
        //         });
        //     }
        //
        //     function handleProcessing(response, currentStep) {
        //         exported = parseInt(response.exported) || 0;
        //         total = parseInt(response.total) || 0;
        //         errorsCount = parseInt(response.errorsCount) || 0;
        //
        //         updateProgress();
        //
        //         // Добавляем небольшую задержку для предотвращения перегрузки сервера
        //         setTimeout(function() {
        //             exportStep(currentStep + 1);
        //         }, 100);
        //     }
        //
        //     function handleDone(response) {
        //         exported = parseInt(response.exported) || 0;
        //         total = parseInt(response.total) || 0;
        //         errorsCount = parseInt(response.errorsCount) || 0;
        //
        //         updateProgress();
        //
        //         if (response.fileUrl) {
        //             const fileName = response.fileName || response.fileUrl.split('/').pop();
        //             messageFile.innerHTML = 'Файл: <a href="' +
        //                 BX.util.htmlspecialchars(response.fileUrl) +
        //                 '" target="_blank">' +
        //                 BX.util.htmlspecialchars(fileName) +
        //                 '</a>';
        //         }
        //
        //         finishExport();
        //     }
        //
        //     function updateProgress() {
        //         messageOk.textContent = exported + " из " + total;
        //         messageError.textContent = errorsCount.toString();
        //
        //         // Добавляем процентный индикатор
        //         if (total > 0) {
        //             const percent = Math.round((exported / total) * 100);
        //             messageOk.textContent += ' (' + percent + '%)';
        //         }
        //     }
        //
        //     function finishExport(errorMessage = null) {
        //         isExporting = false;
        //
        //         // Разблокируем кнопку
        //         exportButton.disabled = false;
        //         exportButton.classList.remove('ui-btn-wait');
        //
        //         if (errorMessage) {
        //             messageError.textContent = errorMessage;
        //             messageError.style.color = 'red';
        //         }
        //
        //         console.log('Export finished. Exported:', exported, 'Total:', total, 'Errors:', errorsCount);
        //     }
        //
        //     function getExportId() {
        //         // Получаем ID из атрибута кнопки, формы или другого источника
        //         return exportButton.dataset.exportId ||
        //             document.querySelector('[name="export_id"]')?.value ||
        //             123; // fallback значение
        //     }
        //
        //     // Обработка закрытия страницы во время экспорта
        //     window.addEventListener('beforeunload', function(e) {
        //         if (isExporting) {
        //             e.preventDefault();
        //             e.returnValue = 'Экспорт еще не завершен. Вы уверены, что хотите покинуть страницу?';
        //             return e.returnValue;
        //         }
        //     });
        // });
        /////////////////////////////////////////////////
        // BX.ready(function () {
        //     const exportButton = document.querySelector('.btn_green');
        //     const messageWrap = document.getElementById('export_message');
        //     const messageOk = document.getElementById('export_message_ok');
        //     const messageError = document.getElementById('export_message_error');
        //     const messageFile = document.getElementById('export_message_file');
        //
        //     exportButton.addEventListener('click', function (e) {
        //         e.preventDefault();
        //
        //         messageWrap.style.display = 'block';
        //         messageOk.textContent = "0 из 0";
        //         messageError.textContent = "0";
        //         messageFile.innerHTML = "";
        //
        //         let exported = 0;
        //         let total = 0;
        //
        //         function exportStep(nextStep = 0) {
        //             BX.ajax({
        //                 url: '/local/ajax/export_csv.php',
        //                 data: {
        //                     step: nextStep,
        //                     totalExported: exported,
        //                     exportId: 123 // передайте нужный ID объекта/настройки экспорта
        //                 },
        //                 method: 'POST',
        //                 dataType: 'json',
        //                 onsuccess: function (response) {
        //                     if (response.status === 'processing') {
        //                         exported = response.exported;
        //                         total = response.total;
        //
        //                         messageOk.textContent = exported + " из " + total;
        //                         messageError.textContent = response.errorsCount;
        //
        //                         // Далее запускаем следующий шаг
        //                         exportStep(nextStep + 1);
        //                     } else if (response.status === 'done') {
        //                         exported = response.exported;
        //                         total = response.total;
        //
        //                         messageOk.textContent = exported + " из " + total;
        //                         messageError.textContent = response.errorsCount;
        //
        //                         messageFile.innerHTML = 'Файл: <a href="' + response.fileUrl + '">' + response.fileUrl + '</a>';
        //                     } else if (response.status === 'error') {
        //                         messageError.textContent = response.message;
        //                     }
        //                 },
        //                 onfailure: function() {
        //                     messageError.textContent = 'Ошибка запроса к серверу.';
        //                 }
        //             });
        //         }
        //
        //         exportStep(0);
        //     });
        // });
    </script>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");