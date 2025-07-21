<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Page\Asset;

Asset::getInstance()->addJs($templateFolder . "/form_manager.js");
Asset::getInstance()->addJs($templateFolder . "/inputmask.min.js");

$componentId = $arResult["COMPONENT_ID"];
?>

<!-- Кнопка для загрузки и открытия модального окна -->
<button type="button"
        class="<?= htmlspecialcharsEx($arResult["BUTTON_CLASS"]) ?>"
        data-id="<?= $componentId ?>"
        data-ajax-url="<?= $arResult["AJAX_URL"] ?>"
        data-template="<?= htmlspecialchars($templateFolder) ?>"
        data-form-params="<?= htmlspecialchars(json_encode($arResult["FORM_PARAMS"], JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8") ?>">
    <?php if (!empty($arResult["BUTTON_ICON"])): ?>
        <i class="<?= htmlspecialcharsEx($arResult["BUTTON_ICON"]) ?> me-2"></i>
    <?php endif; ?>
    <?= htmlspecialchars($arResult["BUTTON_TEXT"]) ?>
</button>

<script>
    BX.ready(function () {
        // Инициализация конкретного экземпляра
        var componentId = '<?= $componentId ?>';
        var button = document.querySelector('[data-id="' + componentId + '"]');

        if (button) {
            var modalForm = new BX.DDAPP.Tools.FormManager({
                componentId: componentId,
                button: button,
                ajaxUrl: button.getAttribute('data-ajax-url')
            });

            // Сохраняем ссылку на экземпляр для возможного использования
            button.ddappModalForm = modalForm;
        }
    });
</script>