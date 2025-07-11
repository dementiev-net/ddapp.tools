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
                    <form id="<?= $formId ?>" method="post">
                        <?php foreach ($arResult['PROPERTIES'] as $property): ?>
                            <div class="form-group">
                                <label for="property_<?= $property['ID'] ?>">
                                    <?= $property['NAME'] ?>
                                    <?php if ($property['IS_REQUIRED'] === 'Y'): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>

                                <?php if ($property['PROPERTY_TYPE'] === 'S' && $property['USER_TYPE'] !== 'HTML'): ?>
                                    <input type="text" class="form-control"
                                           id="property_<?= $property['ID'] ?>"
                                           name="PROPERTY_<?= $property['ID'] ?>"
                                        <?= $property['IS_REQUIRED'] === 'Y' ? 'required' : '' ?>>

                                <?php elseif ($property['PROPERTY_TYPE'] === 'S' && $property['USER_TYPE'] === 'HTML'): ?>
                                    <textarea class="form-control"
                                              id="property_<?= $property['ID'] ?>"
                                              name="PROPERTY_<?= $property['ID'] ?>"
                                              rows="5"
                                              <?= $property['IS_REQUIRED'] === 'Y' ? 'required' : '' ?>></textarea>

                                <?php elseif ($property['PROPERTY_TYPE'] === 'F'): ?>
                                    <input type="file" class="form-control-file"
                                           id="property_<?= $property['ID'] ?>"
                                           name="PROPERTY_<?= $property['ID'] ?>"
                                        <?= $property['IS_REQUIRED'] === 'Y' ? 'required' : '' ?>>

                                <?php elseif ($property['PROPERTY_TYPE'] === 'L' && $property['LIST_TYPE'] === 'L'): ?>
                                    <select class="form-control"
                                            id="property_<?= $property['ID'] ?>"
                                            name="PROPERTY_<?= $property['ID'] ?>"
                                        <?= $property['IS_REQUIRED'] === 'Y' ? 'required' : '' ?>>
                                        <option value="">Выберите...</option>
                                        <?php foreach ($property['LIST_VALUES'] as $value): ?>
                                            <option value="<?= $value['ID'] ?>"><?= $value['VALUE'] ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                <?php elseif ($property['PROPERTY_TYPE'] === 'L' && $property['LIST_TYPE'] === 'C'): ?>
                                    <?php foreach ($property['LIST_VALUES'] as $value): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   id="property_<?= $property['ID'] ?>_<?= $value['ID'] ?>"
                                                   name="PROPERTY_<?= $property['ID'] ?>[]"
                                                   value="<?= $value['ID'] ?>">
                                            <label class="form-check-label"
                                                   for="property_<?= $property['ID'] ?>_<?= $value['ID'] ?>">
                                                <?= $value['VALUE'] ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
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
                                <input type="text" class="form-control" name="captcha_word" required>
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
    /**
     * DDApp Tools Form Manager
     * @version 1.1.0
     */

    BX.namespace('BX.DDAPP.Tools');

    BX.DDAPP.Tools.FormManager = function (params) {
        this.params = params || {};
        this.form = null;
        this.modal = null;
        this.submitButton = null;
        this.isSubmitting = false;
        this.recaptchaLoaded = false;
        this.sessid = BX.bitrix_sessid();
        this.init();
    };

    BX.DDAPP.Tools.FormManager.prototype = {

        init: function () {
            this.form = BX(this.params.formId);
            this.modal = BX(this.params.modalId);
            this.submitButton = this.form.querySelector('button[type="submit"]');

            if (this.params.useGoogleRecaptcha === 'Y') {
                this.loadRecaptcha();
            }

            this.bindEvents();
        },

        bindEvents: function () {
            BX.bind(this.form, 'submit', BX.proxy(this.onSubmit, this));

            if (this.modal) {
                // Используем Bootstrap 5 API
                var self = this;
                this.modal.addEventListener('hidden.bs.modal', function() {
                    self.onModalHidden();
                });
            }
        },

        loadRecaptcha: function () {
            if (!this.recaptchaLoaded && this.params.recaptchaPublicKey) {
                var script = document.createElement('script');
                script.src = 'https://www.google.com/recaptcha/api.js?render=' + this.params.recaptchaPublicKey;
                script.onload = BX.proxy(function () {
                    this.recaptchaLoaded = true;
                }, this);
                document.head.appendChild(script);
            }
        },

        onSubmit: function (e) {
            e.preventDefault();

            if (this.isSubmitting) {
                return false;
            }

            if (!this.validateForm()) {
                return false;
            }

            if (this.params.useGoogleRecaptcha === 'Y') {
                this.submitWithRecaptcha();
            } else {
                this.submitForm();
            }
        },

        validateForm: function () {
            var requiredFields = this.form.querySelectorAll('[required]');
            var isValid = true;

            for (var i = 0; i < requiredFields.length; i++) {
                var field = requiredFields[i];
                var value = field.value.trim();

                if (!value) {
                    BX.addClass(field, 'is-invalid');
                    isValid = false;
                } else {
                    BX.removeClass(field, 'is-invalid');
                }
            }

            return isValid;
        },

        submitWithRecaptcha: function () {
            var self = this;

            if (!this.recaptchaLoaded || typeof grecaptcha === 'undefined') {
                this.showError('Ошибка загрузки reCAPTCHA');
                return;
            }

            grecaptcha.ready(function () {
                grecaptcha.execute(self.params.recaptchaPublicKey, {action: 'submit'}).then(function (token) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'g-recaptcha-response';
                    input.value = token;
                    self.form.appendChild(input);
                    self.submitForm();
                });
            });
        },

        submitForm: function () {
            this.isSubmitting = true;
            this.setSubmitButtonState(true);

            var data = {};
            Array.from(this.form.elements).forEach(function (element) {
                if (element.name && element.type !== 'button' && element.type !== 'submit') {
                    if (element.type === 'checkbox' || element.type === 'radio') {
                        if (element.checked) {
                            data[element.name] = element.value;
                        }
                    } else {
                        data[element.name] = element.value || '';
                    }
                }
            });

            BX.ajax({
                method: 'POST',
                url: window.location.href,
                data: data,
                processData: false,
                contentType: false,
                onsuccess: BX.proxy(this.onSuccess, this),
                onfailure: BX.proxy(this.onFailure, this)
            });
        },

        onSuccess: function (response) {
            this.isSubmitting = false;
            this.setSubmitButtonState(false);

            try {
                var result = JSON.parse(response);
                if (result.success) {
                    this.showAlert(result.message);
                    this.resetForm();
                    this.hideModal();
                } else {
                    this.showError(result.message);
                }
            } catch (e) {
                this.showError('Ошибка обработки ответа');
            }
        },

        onFailure: function () {
            this.isSubmitting = false;
            this.setSubmitButtonState(false);
            this.showError('Ошибка отправки формы');
        },

        setSubmitButtonState: function (loading) {
            if (this.submitButton) {
                this.submitButton.disabled = loading;
                this.submitButton.innerHTML = loading ? 'Отправляется...' : 'Отправить';
            }
        },

        resetForm: function () {
            this.form.reset();
            var invalidFields = this.form.querySelectorAll('.is-invalid');
            for (var i = 0; i < invalidFields.length; i++) {
                BX.removeClass(invalidFields[i], 'is-invalid');
            }
        },

        hideModal: function () {
            if (this.modal) {
                // Используем Bootstrap 5 API
                var modal = bootstrap.Modal.getInstance(this.modal);
                if (modal) {
                    modal.hide();
                } else {
                    // Если экземпляр не найден, создаем новый
                    var bsModal = new bootstrap.Modal(this.modal);
                    bsModal.hide();
                }
            }
        },

        onModalHidden: function () {
            this.resetForm();
        },

        showAlert: function (message) {
            alert(message);
        },

        showError: function (message) {
            alert(message);
        }
    };

    BX.ready(function () {
        new BX.DDAPP.Tools.FormManager({
            formId: '<?=$formId?>',
            modalId: '<?=$formId?>Modal',
            useGoogleRecaptcha: '<?=$arParams['USE_GOOGLE_RECAPTCHA']?>',
            recaptchaPublicKey: '<?=$arParams['GOOGLE_RECAPTCHA_PUBLIC_KEY']?>'
        });
    });
</script>