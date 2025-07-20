<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Page\Asset;

Asset::getInstance()->addJs($templateFolder . '/form_manager.js');


//if ($arParams['USE_SCRIPT_1'] === 'Y') {
//Asset::getInstance()->addJs($templateFolder . '/_script.js');
//}

$componentId = $arResult['COMPONENT_ID'];
?>

<!-- Кнопка для загрузки и открытия модального окна -->
<button type="button"
        class="btn btn-primary forms-load-modal-btn"
        data-component-id="<?= $componentId ?>"
        data-modal-title="<?= htmlspecialchars($arResult['MODAL_TITLE']) ?>"
        data-input-placeholder="<?= htmlspecialchars($arResult['INPUT_PLACEHOLDER']) ?>"
        data-ajax-url="<?= $arResult['AJAX_URL'] ?>"
        data-template="<?= htmlspecialchars($arResult['TEMPLATE_NAME']) ?>">
    <?= htmlspecialchars($arResult['BUTTON_TEXT']) ?>
</button>

<script>
    BX.ready(function () {
        // Инициализация конкретного экземпляра
        var componentId = '<?=$componentId?>';
        var button = document.querySelector('[data-component-id="' + componentId + '"]');

        if (button) {
            var modalForm = new BX.DDAPP.Tools.FormManager({
                componentId: componentId,
                button: button,
                ajaxUrl: button.getAttribute('data-ajax-url'),
                modalTitle: button.getAttribute('data-modal-title'),
                inputPlaceholder: button.getAttribute('data-input-placeholder')
            });

            // Сохраняем ссылку на экземпляр для возможного использования
            button.ddappModalForm = modalForm;
        }
    });
</script>