<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$componentId = $arResult['COMPONENT_ID'];
?>

<div class="ddapp-form-wrapper">

    <!-- Модальное окно -->
    <div class="modal fade"
         id="<?= $componentId ?>_modal"
         tabindex="-1"
         aria-labelledby="<?= $componentId ?>_modalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="<?= $componentId ?>_modalLabel">
                        <?= $arResult["NAME"] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">

                    <!-- Описание -->
                    <?php if (!empty($arResult["DESCRIPTION"])): ?>
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <?= $arResult["DESCRIPTION"] ?>
                        </div>
                    <?php endif; ?>

                    <!-- Форма -->
                    <form id="<?= $componentId ?>_form" method="post" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

                        <div class="mb-3">
                            <?php foreach ($arResult["PROPERTIES"] as $index => $property): ?>
                                <div class="form-group mb-3">
                                    <label for="property_<?= $property["ID"] ?>" class="form-label">
                                        <?= $property["NAME"] ?>
                                        <?php if ($property["IS_REQUIRED"] === "Y"): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>

                                    <?php
                                    // Поля даты "S:DateTime"
                                    if ($property["PROPERTY_TYPE"] === "S" && $property["USER_TYPE"] === "DateTime"): ?>
                                        <input type="date"
                                               class="form-control"
                                               id="property_<?= $property["ID"] ?>"
                                               name="property_<?= $property["ID"] ?>"
                                            <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                               aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">

                                    <?php
                                    // Строковые поля "S"
                                    elseif ($property["PROPERTY_TYPE"] === "S"):
                                        if ($property["ROW_COUNT"] > 1): ?>
                                            <textarea class="form-control"
                                                      id="property_<?= $property["ID"] ?>"
                                                      name="property_<?= $property["ID"] ?>"
                                                      rows="<?= $property["ROW_COUNT"] ?>"
                                                      cols="<?= $property["COL_COUNT"] ?>"
                                                      placeholder="Введите <?= strtolower($property["NAME"]) ?>"
                                                  <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                                  aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>"></textarea>
                                        <?php else: ?>
                                            <input type="text"
                                                   class="form-control"
                                                   id="property_<?= $property["ID"] ?>"
                                                   name="property_<?= $property["ID"] ?>"
                                                   placeholder="Введите <?= strtolower($property["NAME"]) ?>"
                                                <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                                   aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">
                                            <?php if (!empty($property["HINT"]) && strtoupper(trim($property["HINT"])) === "EMAIL"): ?>
                                                <div class="email-hint">
                                                    <i class="fas fa-lightbulb me-1"></i>
                                                    Нажмите <kbd>Tab</kbd> для автодополнения домена
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                    <?php
                                    // Списочные поля "L"
                                    elseif ($property["PROPERTY_TYPE"] === "L"):

                                        if ($property["LIST_TYPE"] === "L"): // Список
                                            ?>
                                            <select class="form-select"
                                                    id="property_<?= $property["ID"] ?>"
                                                    name="property_<?= $property["ID"] ?><?= $property["MULTIPLE"] === "Y" ? "[]" : "" ?>"
                                                <?= $property["MULTIPLE"] === "Y" ? " multiple" : "" ?>
                                                <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                                    aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">
                                                <?php if ($property["MULTIPLE"] !== "Y"): ?>
                                                    <option value="">Выберите вариант...</option>
                                                <?php endif; ?>
                                                <?php foreach ($property["LIST_VALUES"] as $value): ?>
                                                    <option value="<?= $value["ID"] ?>"><?= $value["VALUE"] ?></option>
                                                <?php endforeach; ?>
                                            </select>

                                        <?php elseif ($property["LIST_TYPE"] === "C"): // Флажки ?>
                                            <div class="checkbox-group" role="group"
                                                 aria-labelledby="property_<?= $property["ID"] ?>_label">
                                                <?php foreach ($property["LIST_VALUES"] as $valueIndex => $value): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input"
                                                               type="checkbox"
                                                               id="property_<?= $property["ID"] ?>_<?= $value["ID"] ?>"
                                                               name="property_<?= $property["ID"] ?>[]"
                                                               value="<?= $value["ID"] ?>"
                                                            <?= $property["IS_REQUIRED"] === "Y" && $valueIndex === 0 ? " required" : "" ?>>
                                                        <label class="form-check-label"
                                                               for="property_<?= $property["ID"] ?>_<?= $value["ID"] ?>">
                                                            <?= $value["VALUE"] ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                    <?php
                                    // Числовые поля "N"
                                    elseif ($property["PROPERTY_TYPE"] === "N"): ?>
                                        <input type="number"
                                               class="form-control"
                                               id="property_<?= $property["ID"] ?>"
                                               name="property_<?= $property["ID"] ?>"
                                               placeholder="Введите число"
                                               step="any"
                                            <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                               aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">

                                    <?php
                                    // Файловые поля "F" - будут обработаны JavaScript
                                    elseif ($property["PROPERTY_TYPE"] === "F"): ?>
                                        <input type="file"
                                               class="form-control d-none"
                                               id="property_<?= $property["ID"] ?>"
                                               name="property_<?= $property["ID"] ?><?= $property["MULTIPLE"] === "Y" ? "[]" : "" ?>"
                                               accept=".<?= implode(',.', $fileConfig['allowed_extensions']) ?>"
                                            <?= $property["MULTIPLE"] === "Y" ? " multiple" : "" ?>
                                            <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                               aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">

                                    <?php
                                    // Привязка к элементам "E"
                                    elseif ($property["PROPERTY_TYPE"] === "E" && !empty($property["ELEMENT_VALUES"])): ?>
                                        <select class="form-select"
                                                id="property_<?= $property["ID"] ?>"
                                                name="property_<?= $property["ID"] ?><?= $property["MULTIPLE"] === "Y" ? "[]" : "" ?>"
                                            <?= $property["MULTIPLE"] === "Y" ? "multiple" : "" ?>
                                            <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                                aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">
                                            <?php if ($property["MULTIPLE"] !== "Y"): ?>
                                                <option value="">Выберите элемент...</option>
                                            <?php endif; ?>
                                            <?php foreach ($property["ELEMENT_VALUES"] as $element): ?>
                                                <option value="<?= $element["ID"] ?>"><?= $element["NAME"] ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                    <?php endif; ?>

                                    <?php if (!empty($property["HINT"])): ?>
                                        <div id="hint_<?= $property["ID"] ?>" class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <?= $property["HINT"] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($arParams["USE_BITRIX_CAPTCHA"] === "Y"): ?>
                                <div class="form-group mb-3">
                                    <label class="form-label">
                                        Код с картинки <span class="text-danger">*</span>
                                    </label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <img src="/bitrix/tools/captcha.php?captcha_code=<?= $arResult["CAPTCHA_CODE"] ?>"
                                                 alt="Captcha" class="captcha-image img-fluid border rounded mb-2">
                                            <input type="hidden" name="captcha_code"
                                                   value="<?= $arResult["CAPTCHA_CODE"] ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text"
                                                   class="form-control"
                                                   name="captcha_word"
                                                   placeholder="Введите код с картинки"
                                                   required
                                                   autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Обязательные поля -->
                            <?php
                            $hasRequiredFields = false;
                            foreach ($arResult["PROPERTIES"] as $property) {
                                if ($property["IS_REQUIRED"] === "Y") {
                                    $hasRequiredFields = true;
                                    break;
                                }
                            }
                            if ($hasRequiredFields || $arParams["USE_BITRIX_CAPTCHA"] === "Y"): ?>
                                <div class="alert alert-light">
                                    <small class="text-muted">
                                        <i class="fas fa-asterisk text-danger me-1"></i>
                                        Поля, отмеченные звездочкой (*), обязательны для заполнения
                                    </small>
                                </div>
                            <?php endif; ?>

                            <?php if ($arParams["USE_PRIVACY_POLICY"] === "Y"): ?>
                                <div class="form-group mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="privacy_policy_agreement"
                                               name="privacy_policy_agreement"
                                               value="Y"
                                               required
                                               aria-describedby="privacy_policy_help">
                                        <label class="form-check-label" for="privacy_policy_agreement">
                                            <?= htmlspecialchars($arParams["PRIVACY_POLICY_TEXT"]) ?>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div id="privacy_policy_help" class="form-text">
                                            <i class="fas fa-shield-alt me-1"></i>
                                            Обязательное поле для отправки формы
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Блок для вывода сообщений -->
                        <div id="<?= $componentId ?>_message" class="alert d-none" role="alert"
                             aria-live="polite"></div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                <?= $arResult["BUTTON_TEXT"] ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>