<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use DDAPP\Tools\Main;
use DDAPP\Tools\Maintenance;

// Проверяем, что модуль установлен
if (!CModule::IncludeModule(Main::MODULE_ID)) {
    return;
}

Loc::loadMessages(__FILE__);

$APPLICATION->SetAdditionalCSS("/bitrix/gadgets/ddapp.tools/maintenance/styles.css");

// Проверяем и очищаем данные если прошло время
Maintenance::checkLastCompletionDate();

$items = Maintenance::getAllItems();
$isCompleted = Maintenance::checkIfAllCompleted($items);
$lastCompletionDate = Option::get(Main::MODULE_ID, "maint_last_date");

$class = $isCompleted ? "completed" : "incomplete";
?>

<div class="gadget-content maintenance <?= $class ?>" id="maintenance">
    <div class="header">
        <?php if ($lastCompletionDate) { ?>
            <h4><?= Loc::getMessage("DDAPP_MAINTENANCE_INFO_DONE") ?>: <?= FormatDate("d.m.Y H:i", MakeTimeStamp($lastCompletionDate)) ?></h4>
        <?php } else { ?>
            <?= Loc::getMessage("DDAPP_MAINTENANCE_INFO_NONE") ?>
        <?php } ?>
    </div>
    <div class="items-list">
        <?php foreach ($items as $item): ?>
            <div class="item-row">
                <label class="item-checkbox">
                    <input type="checkbox" value="<?= $item["ID"] ?>"
                        <?= $item["COMPLETED"] ? "checked" : "" ?>>
                </label>
                <div class="item-text">
                    <?php if ($item["LINK"]): ?>
                        <a href="<?= htmlspecialchars($item["LINK"]) ?>"
                           class="item-link" target="_blank">
                            <?= htmlspecialchars($item["NAME"]) ?> (<span style='color: <?= Loc::getMessage("DDAPP_MAINTENANCE_INFO_TYPE_COLOR")[$item["TYPE"]] ?>'><?= Loc::getMessage("DDAPP_MAINTENANCE_INFO_TYPE")[$item["TYPE"]] ?></span>)
                        </a>
                    <?php else: ?>
                        <span class="item-name">
                            <?= htmlspecialchars($item["NAME"]) ?> (<span style='color: <?= Loc::getMessage("DDAPP_MAINTENANCE_INFO_TYPE_COLOR")[$item["TYPE"]] ?>'><?= Loc::getMessage("DDAPP_MAINTENANCE_INFO_TYPE")[$item["TYPE"]] ?></span>)
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    BX.ready(function () {
        var widget = BX('maintenance');

        if (!widget) return;

        var checkboxes = widget.querySelectorAll('.maintenance .items-list .item-row .item-checkbox input');

        checkboxes.forEach(function (checkbox) {

            BX.bind(checkbox, 'change', function (event) {
                var target = event.target;
                var itemId = target.value;
                var isChecked = target.checked;

                BX.ajax({
                    url: '/bitrix/gadgets/ddapp.tools/maintenance/getdata.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'toggle_item',
                        item_id: itemId,
                        checked: isChecked ? 1 : 0,
                        sessid: BX.bitrix_sessid()
                    },
                    onsuccess: function (response) {
                        if (response.success) {
                            var allChecked = true;

                            checkboxes.forEach(function (cb) {
                                if (!cb.checked) {
                                    allChecked = false;
                                }
                            });

                            var header = widget.querySelector('.header');

                            if (allChecked) {
                                BX.removeClass(widget, 'incomplete');
                                BX.addClass(widget, 'completed');

                                if (response.completion_date) {
                                    header.innerHTML = '<h4><?= Loc::getMessage("DDAPP_MAINTENANCE_INFO_DONE") ?>: ' + response.completion_date + '</h4>';
                                }
                            } else {
                                header.innerHTML = '<?= Loc::getMessage("DDAPP_MAINTENANCE_INFO_NONE") ?>';

                                BX.removeClass(widget, 'completed');
                                BX.addClass(widget, 'incomplete');
                            }
                        } else {
                            alert(response.error);
                        }
                    },
                    onfailure: function () {
                        target.checked = !isChecked;
                    }
                });
            });
        });
    });
</script>