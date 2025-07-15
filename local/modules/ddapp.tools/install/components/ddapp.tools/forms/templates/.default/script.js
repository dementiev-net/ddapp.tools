BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.FormManager = function (params) {
    this.params = params || {};
    this.form = null;
    this.modal = null;
    this.messagesBlock = null;
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
        this.messagesBlock = BX(this.params.messagesId);
        this.submitButton = this.form.querySelector('button[type="submit"]');

        if (this.params.useGoogleRecaptcha === 'Y') {
            this.loadRecaptcha();
        }

        this.bindEvents();
    },

    bindEvents: function () {
        BX.bind(this.form, 'submit', BX.proxy(this.onSubmit, this));

        if (this.modal) {
            var self = this;
            this.modal.addEventListener('hidden.bs.modal', function () {
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

        this.hideMessage();

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

        // Сначала очищаем все ошибки
        this.clearValidationErrors();

        for (var i = 0; i < requiredFields.length; i++) {
            var field = requiredFields[i];
            var value = this.getFieldValue(field);

            if (!value) {
                this.addValidationError(field);
                isValid = false;
            }
        }

        return isValid;
    },

    getFieldValue: function(field) {
        if (field.type === 'checkbox' || field.type === 'radio') {
            // Для чекбоксов и радио проверяем группу
            var name = field.name;
            var checkedFields = this.form.querySelectorAll('input[name="' + name + '"]:checked');
            return checkedFields.length > 0;
        } else {
            return field.value.trim();
        }
    },

    addValidationError: function(field) {
        BX.addClass(field, 'is-invalid');

        // Добавляем класс к родительскому контейнеру для лучшей видимости
        var formGroup = field.closest('.form-group');
        if (formGroup) {
            BX.addClass(formGroup, 'has-error');
        }
    },

    clearValidationErrors: function() {
        var invalidFields = this.form.querySelectorAll('.is-invalid');
        for (var i = 0; i < invalidFields.length; i++) {
            BX.removeClass(invalidFields[i], 'is-invalid');
        }

        var errorGroups = this.form.querySelectorAll('.has-error');
        for (var j = 0; j < errorGroups.length; j++) {
            BX.removeClass(errorGroups[j], 'has-error');
        }
    },

    submitWithRecaptcha: function () {
        var self = this;

        if (!this.recaptchaLoaded || typeof grecaptcha === 'undefined') {
            this.showMessage('Ошибка загрузки reCAPTCHA', 'error');
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
                        if (data[element.name]) {
                            // Если уже есть значение, делаем массив
                            if (!Array.isArray(data[element.name])) {
                                data[element.name] = [data[element.name]];
                            }
                            data[element.name].push(element.value);
                        } else {
                            data[element.name] = element.value;
                        }
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
                this.showMessage(result.message, 'success');
                this.resetForm();

                // Закрываем модальное окно через 2 секунды
                setTimeout(BX.proxy(this.hideModal, this), 2000);
            } else {
                this.showMessage(result.message, 'error');
            }
        } catch (e) {
            this.showMessage('Ошибка обработки ответа', 'error');
        }
    },

    onFailure: function () {
        this.isSubmitting = false;
        this.setSubmitButtonState(false);
        this.showMessage('Ошибка отправки формы', 'error');
    },

    setSubmitButtonState: function (loading) {
        if (this.submitButton) {
            this.submitButton.disabled = loading;
            this.submitButton.innerHTML = loading ? 'Отправляется...' : 'Отправить';
        }
    },

    resetForm: function () {
        this.form.reset();
        this.clearValidationErrors();
    },

    hideModal: function () {
        if (this.modal) {
            var modal = bootstrap.Modal.getInstance(this.modal);
            if (modal) {
                modal.hide();
            } else {
                var bsModal = new bootstrap.Modal(this.modal);
                bsModal.hide();
            }
        }
    },

    onModalHidden: function () {
        this.resetForm();
        this.hideMessage();
    },

    showMessage: function (message, type) {
        if (this.messagesBlock) {
            this.messagesBlock.innerHTML = message;
            this.messagesBlock.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
            this.messagesBlock.style.display = 'block';
        }
    },

    hideMessage: function () {
        if (this.messagesBlock) {
            this.messagesBlock.style.display = 'none';
            this.messagesBlock.innerHTML = '';
        }
    }
};