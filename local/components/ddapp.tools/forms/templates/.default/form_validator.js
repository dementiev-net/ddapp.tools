/**
 * 2Dapp Form Validator
 * @version 1.0.0
 */

BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.FormValidator = function(formId, params) {
    this.formId = formId;
    this.params = params || {};
    this.form = document.getElementById(formId);

    if (!this.form) {
        console.error('FormValidator: Form not found', formId);
        return;
    }

    this.init();
};

BX.DDAPP.Tools.FormValidator.prototype = {

    init: function() {
        this.addFormClass();
        this.bindEvents();
    },

    addFormClass: function() {
        // Добавляем класс needs-validation для активации Bootstrap валидации
        this.form.classList.add('needs-validation');
    },

    bindEvents: function() {
        // Валидация в реальном времени для всех полей
        var inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            this.bindFieldEvents(input);
        }.bind(this));
    },

    bindFieldEvents: function(field) {
        // Только очистка валидации при изменении
        this.bindClearValidationEvents(field);

        // Фронтенд валидация отключена - валидируем только на сервере
    },

    bindClearValidationEvents: function(field) {
        // Для обычных полей ввода
        if (field.type === 'text' || field.type === 'email' || field.type === 'tel' ||
            field.type === 'number' || field.type === 'url' || field.type === 'password' ||
            field.tagName === 'TEXTAREA') {

            field.addEventListener('input', function() {
                this.clearFieldValidation(field);
                // Добавить очистку общего сообщения
                this.clearGeneralMessage();
            }.bind(this));
        }

        // Для селектов, дат, чекбоксов, радио, файлов
        if (field.tagName === 'SELECT' || field.type === 'date' ||
            field.type === 'datetime-local' || field.type === 'checkbox' ||
            field.type === 'radio' || field.type === 'file') {

            field.addEventListener('change', function() {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    this.clearGroupValidation(field);
                } else {
                    this.clearFieldValidation(field);
                }
                // Добавить очистку общего сообщения
                this.clearGeneralMessage();
            }.bind(this));
        }
    },

    // Добавить новый метод для очистки общего сообщения
    clearGeneralMessage: function() {
        var messageDiv = this.form.querySelector('.alert[role="alert"]');
        if (messageDiv && !messageDiv.classList.contains('d-none')) {
            messageDiv.classList.add('d-none');
            messageDiv.textContent = '';
        }
    },

    // Валидация на фронтенде отключена - используем только серверную
    validateField: function(field) {
        // Просто очищаем предыдущие ошибки
        this.clearFieldValidation(field);
        return true;
    },

    validateForm: function() {
        // Фронтенд валидация отключена - всегда разрешаем отправку
        // Валидация будет на сервере
        return true;
    },

    // Удаляем методы фронтенд валидации - используем только серверную
    // validateCheckboxGroups, validateRadioGroups, validateFiles, isValidEmail, isValidPhone, isValidUrl удалены

    showFieldErrors: function(fieldErrors) {
        Object.keys(fieldErrors).forEach(function(fieldName) {
            var field = this.form.querySelector('[name="' + fieldName + '"]');
            if (field) {
                // Добавляем класс ошибки
                field.classList.add('is-invalid');
                field.classList.remove('is-valid');

                // Показываем сообщение об ошибке
                this.showFieldError(field, fieldErrors[fieldName]);

                // Для чекбоксов и радио подсвечиваем всю группу
                if (field.type === 'checkbox' || field.type === 'radio') {
                    var groupFields = this.form.querySelectorAll('[name="' + fieldName + '"]');
                    groupFields.forEach(function(groupField) {
                        groupField.classList.add('is-invalid');
                        groupField.classList.remove('is-valid');
                    });
                }

                // Для файлов добавляем класс к контейнеру
                if (field.type === 'file') {
                    var uploadArea = field.closest('.file-upload-area');
                    if (uploadArea) {
                        uploadArea.classList.add('is-invalid');
                        uploadArea.classList.remove('is-valid');
                    }
                }
            }
        }.bind(this));
    },

    clearAllValidation: function() {
        // Удаляем все классы валидации
        var fields = this.form.querySelectorAll('.is-invalid, .is-valid');
        fields.forEach(function(field) {
            field.classList.remove('is-invalid', 'is-valid');
        });

        // Удаляем классы с файловых контейнеров
        var uploadAreas = this.form.querySelectorAll('.file-upload-area.is-invalid, .file-upload-area.is-valid');
        uploadAreas.forEach(function(area) {
            area.classList.remove('is-invalid', 'is-valid');
        });

        // Удаляем все сообщения об ошибках
        var errorMessages = this.form.querySelectorAll('.invalid-feedback');
        errorMessages.forEach(function(error) {
            error.textContent = '';
            error.style.display = 'none';
        });
    },

    clearFieldValidation: function(field) {
        if (!field) return;

        // Убираем классы валидации
        field.classList.remove('is-invalid', 'is-valid');

        // Убираем ошибку из file-upload-area если это файл
        if (field.type === 'file') {
            var uploadArea = field.closest('.file-upload-area');
            if (uploadArea) {
                uploadArea.classList.remove('is-invalid', 'is-valid');
            }
        }

        // Скрываем сообщение об ошибке
        this.clearFieldError(field);
    },

    clearGroupValidation: function(field) {
        if (!field) return;

        // Очищаем валидацию для всех полей с таким же именем (группа чекбоксов/радио)
        var groupFields = this.form.querySelectorAll('[name="' + field.name + '"]');
        groupFields.forEach(function(groupField) {
            this.clearFieldValidation(groupField);
        }.bind(this));
    },

    showFieldError: function(field, message) {
        var container = field.closest('.form-group, .mb-3, .form-check');
        if (!container) container = field.parentNode;

        var errorDiv = container.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            container.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    },

    clearFieldError: function(field) {
        var container = field.closest('.form-group, .mb-3, .form-check');
        if (!container) container = field.parentNode;

        var errorDiv = container.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.style.display = 'none';
        }
    },

    scrollToFirstError: function() {
        var firstError = this.form.querySelector('.is-invalid');
        if (firstError) {
            firstError.focus();
            firstError.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    },

    // Методы для отображения серверных ошибок
};