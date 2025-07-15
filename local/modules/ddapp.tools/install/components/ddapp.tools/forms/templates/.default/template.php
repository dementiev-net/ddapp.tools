<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$formId = $arResult['FORM_ID'];
$iblockId = $arResult['IBLOCK_ID'];
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz"
        crossorigin="anonymous"></script>

<div class="ddapp-form-wrapper">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#<?= $formId ?>Modal">
        Открыть форму
    </button>

    <div class="modal fade" id="<?= $formId ?>Modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Форма обратной связи</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Блок для вывода сообщений -->
                    <div id="<?= $formId ?>Messages" class="alert" style="display: none;"></div>

                    <form id="<?= $formId ?>" method="post" novalidate>

                        <?php foreach ($arResult['PROPERTIES'] as $property): ?>
                            <div class="form-group">
                                <label for="property_<?= $property['ID'] ?>">
                                    <?= $property['NAME'] ?>
                                    <?php if ($property['IS_REQUIRED'] === 'Y'): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>

                                <?php
                                // Поля даты
                                if ($property['PROPERTY_TYPE'] === 'S' && $property['USER_TYPE'] === 'DateTime'): ?>
                                    <input type="date" class="form-control"
                                           id="property_<?= $property['ID'] ?>"
                                           name="PROPERTY_<?= $property['ID'] ?>"
                                           <?php if ($property['IS_REQUIRED'] === 'Y'): ?>required<?php endif; ?>>

                                <?php
                                // Строковые поля
                                elseif ($property['PROPERTY_TYPE'] === 'S'):
                                    if ($property['ROW_COUNT'] > 1): ?>
                                        <textarea class="form-control"
                                                  id="property_<?= $property['ID'] ?>"
                                                  name="PROPERTY_<?= $property['ID'] ?>"
                                                  rows="<?= $property['ROW_COUNT'] ?>"
                                                  cols="<?= $property['COLL_COUNT'] ?>"
                                                  <?php if ($property['IS_REQUIRED'] === 'Y'): ?>required<?php endif; ?>></textarea>
                                    <?php else: ?>
                                        <input type="text" class="form-control"
                                               id="property_<?= $property['ID'] ?>"
                                               name="PROPERTY_<?= $property['ID'] ?>"
                                               size="<?= $property['COL_COUNT'] ?>"
                                               <?php if ($property['IS_REQUIRED'] === 'Y'): ?>required<?php endif; ?>>
                                    <?php endif; ?>

                                <?php
                                // Списочные поля
                                elseif ($property['PROPERTY_TYPE'] === 'L'):

                                    if ($property['LIST_TYPE'] === 'L'): // Список
                                        ?>
                                        <select class="form-control"
                                                id="property_<?= $property['ID'] ?>"
                                                name="PROPERTY_<?= $property['ID'] ?><?= $property['MULTIPLE'] === 'Y' ? '[]' : '' ?>"
                                                size="<?= $property['ROW_COUNT'] ?>"
                                            <?= $property['MULTIPLE'] === 'Y' ? 'multiple' : '' ?>
                                                <?php if ($property['IS_REQUIRED'] === 'Y'): ?>required<?php endif; ?>>
                                            <?php if ($property['MULTIPLE'] !== 'Y'): ?>
                                                <option value="">Выберите...</option>
                                            <?php endif; ?>
                                            <?php foreach ($property['LIST_VALUES'] as $value): ?>
                                                <option value="<?= $value['ID'] ?>"><?= $value['VALUE'] ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                    <?php elseif ($property['LIST_TYPE'] === 'C'): // Флажки ?>
                                        <div class="checkbox-group">
                                            <?php foreach ($property['LIST_VALUES'] as $value): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="property_<?= $property['ID'] ?>_<?= $value['ID'] ?>"
                                                           name="PROPERTY_<?= $property['ID'] ?>[]"
                                                           value="<?= $value['ID'] ?>"
                                                           <?php if ($property['IS_REQUIRED'] === 'Y'): ?>required<?php endif; ?>>
                                                    <label class="form-check-label"
                                                           for="property_<?= $property['ID'] ?>_<?= $value['ID'] ?>">
                                                        <?= $value['VALUE'] ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                <?php
                                // Числовые поля
                                elseif ($property['PROPERTY_TYPE'] === 'N'): ?>
                                    <input type="number" class="form-control"
                                           id="property_<?= $property['ID'] ?>"
                                           name="PROPERTY_<?= $property['ID'] ?>"
                                           size="<?= $property['COL_COUNT'] ?>"
                                           step="any"
                                           <?php if ($property['IS_REQUIRED'] === 'Y'): ?>required<?php endif; ?>>

                                <?php
                                // Файловые поля
                                elseif ($property['PROPERTY_TYPE'] === 'F'): ?>
                                    <input type="file" class="form-control"
                                           id="property_<?= $property['ID'] ?>"
                                           name="PROPERTY_<?= $property['ID'] ?><?= $property['MULTIPLE'] === 'Y' ? '[]' : '' ?>"
                                        <?= $property['MULTIPLE'] === 'Y' ? 'multiple' : '' ?>
                                           <?php if ($property['IS_REQUIRED'] === 'Y'): ?>required<?php endif; ?>>

                                <?php
                                // Привязка к элементам
                                elseif ($property['PROPERTY_TYPE'] === 'E'): ?>
                                    <input type="text" class="form-control"
                                           id="property_<?= $property['ID'] ?>"
                                           name="PROPERTY_<?= $property['ID'] ?>"
                                           placeholder="ID элемента"
                                           <?php if ($property['IS_REQUIRED'] === 'Y'): ?>required<?php endif; ?>>

                                <?php endif; ?>

                                <?php if (!empty($property['HINT'])): ?>
                                    <small class="form-text text-muted"><?= $property['HINT'] ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($arParams['USE_BITRIX_CAPTCHA'] === 'Y'): ?>
                            <div class="form-group">
                                <label>Код с картинки <span class="text-danger">*</span></label>
                                <img src="/bitrix/tools/captcha.php?captcha_code=<?= $arResult['CAPTCHA_CODE'] ?>"
                                     alt="Captcha" class="captcha-image">
                                <input type="hidden" name="captcha_code" value="<?= $arResult['CAPTCHA_CODE'] ?>">
                                <input type="text" class="form-control" name="captcha_word">
                            </div>
                        <?php endif; ?>

                        <input type="hidden" name="AJAX_CALL_<?= $iblockId ?>" value="Y">
                        <button type="submit" class="btn btn-primary">Отправить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    BX.ready(function () {
        new BX.DDAPP.Tools.FormManager({
            formId: '<?=$formId?>',
            modalId: '<?=$formId?>Modal',
            messagesId: '<?=$formId?>Messages',
            useGoogleRecaptcha: '<?=$arParams['USE_GOOGLE_RECAPTCHA']?>',
            recaptchaPublicKey: '<?=$arParams['GOOGLE_RECAPTCHA_PUBLIC_KEY']?>'
        });
    });
</script>