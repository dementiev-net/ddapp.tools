<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$componentId = $arResult['COMPONENT_ID'];
?>

<!-- Модальное окно -->
<div class="modal fade" id="<?= $componentId ?>_modal" tabindex="-1" aria-labelledby="<?= $componentId ?>_modalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= $componentId ?>_modalLabel">
                    <?= htmlspecialchars($arResult['MODAL_TITLE']) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="<?= $componentId ?>_form">
                    <div class="mb-3">
                        <label for="<?= $componentId ?>_input" class="form-label visually-hidden">
                            <?= htmlspecialchars($arResult['INPUT_PLACEHOLDER']) ?>
                        </label>
                        <input type="text"
                               class="form-control"
                               id="<?= $componentId ?>_input"
                               placeholder="<?= htmlspecialchars($arResult['INPUT_PLACEHOLDER']) ?>"
                               autocomplete="off"
                               aria-describedby="<?= $componentId ?>_message">
                    </div>
                    <div id="<?= $componentId ?>_message" class="alert d-none" role="alert" aria-live="polite"></div>
                    <button type="submit" class="btn btn-success">Проверить</button>
                </form>
            </div>
        </div>
    </div>
</div>