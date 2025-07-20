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
    this.fileConfig = this.params.fileConfig || {};
    this.selectedFiles = new Map();
    this.dragCounter = 0;
    this.isMobile = this.detectMobile();
    this.analytics = new BX.DDAPP.Tools.Analytics();
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
        this.initFileHandling();
        this.initInputMasks();
        this.initMobileOptimizations();
        this.trackAnalytics('form_loaded', {form_id: this.params.formId});
    },

    detectMobile: function () {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
            window.innerWidth <= 768;
    },

    bindEvents: function () {
        BX.bind(this.form, 'submit', BX.proxy(this.onSubmit, this));

        if (this.modal) {
            var self = this;
            this.modal.addEventListener('hidden.bs.modal', function () {
                self.onModalHidden();
                self.trackAnalytics('form_closed');
            });

            this.modal.addEventListener('shown.bs.modal', function () {
                self.trackAnalytics('form_opened');
            });
        }

        // Отслеживание взаимодействий с полями
        var formFields = this.form.querySelectorAll('input, textarea, select');
        for (var i = 0; i < formFields.length; i++) {
            BX.bind(formFields[i], 'focus', BX.proxy(function (e) {
                this.trackAnalytics('field_focused', {
                    field_type: e.target.type,
                    field_name: e.target.name
                });
            }, this));
        }
    },

    initFileHandling: function () {
        var fileInputs = this.form.querySelectorAll('input[type="file"]');
        var self = this;

        for (var i = 0; i < fileInputs.length; i++) {
            this.setupFileInput(fileInputs[i]);
        }
    },

    setupFileInput: function (input) {
        var self = this;
        var wrapper = this.createFileWrapper(input);

        // Заменяем стандартный input на wrapper
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        input.style.display = 'none';

        // Настройка drag & drop
        this.initDragAndDrop(wrapper, input);

        // Обработка выбора файлов
        BX.bind(input, 'change', function (e) {
            self.handleFileSelection(e.target, wrapper);
        });

        // Кнопка выбора файлов
        var selectBtn = wrapper.querySelector('.file-select-btn');
        BX.bind(selectBtn, 'click', function () {
            input.click();
        });
    },

    createFileWrapper: function (input) {
        var isMultiple = input.hasAttribute('multiple');
        var fieldName = input.closest('.form-group').querySelector('label').textContent.trim();

        var wrapper = document.createElement('div');
        wrapper.className = 'file-upload-wrapper';
        wrapper.innerHTML =
            '<div class="file-drop-zone">' +
            '<div class="drop-content">' +
            '<div class="drop-icon">📁</div>' +
            '<div class="drop-text">Перетащите файл' + (isMultiple ? 'ы' : '') + ' сюда</div>' +
            '<div class="drop-hint">или</div>' +
            '<button type="button" class="btn btn-outline-primary file-select-btn">Выберите файл' + (isMultiple ? 'ы' : '') + '</button>' +
            '</div>' +
            '</div>' +
            '<div class="file-info">' +
            '<small><strong>Ограничения:</strong><br>' +
            '• Максимальный размер: ' + Math.round(this.fileConfig.max_size / 1024 / 1024 * 10) / 10 + ' MB<br>' +
            '• Разрешенные типы: ' + (this.fileConfig.allowed_extensions || []).join(', ').toUpperCase() + '</small>' +
            '</div>' +
            '<div class="selected-files-preview"></div>';

        return wrapper;
    },

    initDragAndDrop: function (wrapper, input) {
        var self = this;
        var dropZone = wrapper.querySelector('.file-drop-zone');

        // Предотвращение стандартного поведения браузера
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (eventName) {
            BX.bind(dropZone, eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        // Визуальная обратная связь
        BX.bind(dropZone, 'dragenter', function (e) {
            self.dragCounter++;
            BX.addClass(dropZone, 'dragover');
            self.trackAnalytics('file_drag_enter');
        });

        BX.bind(dropZone, 'dragleave', function (e) {
            self.dragCounter--;
            if (self.dragCounter === 0) {
                BX.removeClass(dropZone, 'dragover');
            }
        });

        BX.bind(dropZone, 'drop', function (e) {
            self.dragCounter = 0;
            BX.removeClass(dropZone, 'dragover');

            var files = e.dataTransfer.files;
            if (files.length > 0) {
                self.handleDroppedFiles(files, input, wrapper);
                self.trackAnalytics('files_dropped', {count: files.length});
            }
        });
    },

    handleDroppedFiles: function (files, input, wrapper) {
        // Создаем FileList для input
        var dataTransfer = new DataTransfer();

        // Добавляем существующие файлы если multiple
        if (input.hasAttribute('multiple') && input.files) {
            for (var i = 0; i < input.files.length; i++) {
                dataTransfer.items.add(input.files[i]);
            }
        }

        // Добавляем новые файлы
        for (var i = 0; i < files.length; i++) {
            if (!input.hasAttribute('multiple') && i > 0) break;
            dataTransfer.items.add(files[i]);
        }

        input.files = dataTransfer.files;
        this.handleFileSelection(input, wrapper);
    },

    handleFileSelection: function (input, wrapper) {
        var files = input.files;
        var propertyId = input.name.replace('property_', '').replace('[]', '');

        this.clearFileValidationError(wrapper);

        if (files.length === 0) {
            this.updateFilePreview(wrapper, []);
            this.selectedFiles.delete(propertyId);
            return;
        }

        var validFiles = [];
        var errors = [];

        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var fileErrors = this.validateSingleFile(file);

            if (fileErrors.length === 0) {
                validFiles.push(file);
            } else {
                errors = errors.concat(fileErrors);
            }
        }

        if (errors.length > 0) {
            this.showFileValidationError(wrapper, errors);
            input.value = '';
            this.trackAnalytics('file_validation_failed', {
                errors_count: errors.length,
                files_count: files.length
            });
        } else {
            this.selectedFiles.set(propertyId, validFiles);
            this.updateFilePreview(wrapper, validFiles);
            this.trackAnalytics('files_selected', {
                count: validFiles.length,
                total_size: Array.from(validFiles).reduce((sum, f) => sum + f.size, 0)
            });
        }
    },

    validateSingleFile: function (file) {
        var errors = [];
        var maxSize = this.fileConfig.max_size || 10485760;
        var allowedExtensions = this.fileConfig.allowed_extensions || [];
        var forbiddenExtensions = this.fileConfig.forbidden_extensions || [];

        var fileName = file.name.toLowerCase();
        var fileExtension = fileName.split('.').pop();

        // Проверка размера
        if (file.size > maxSize) {
            var maxSizeMB = Math.round(maxSize / 1024 / 1024 * 10) / 10;
            errors.push('Файл "' + file.name + '" превышает максимальный размер (' + maxSizeMB + ' MB)');
        }

        // Проверка запрещенных расширений
        if (forbiddenExtensions.indexOf(fileExtension) !== -1) {
            errors.push('Тип файла "' + fileExtension + '" запрещен для загрузки');
            return errors;
        }

        // Проверка разрешенных расширений
        if (allowedExtensions.length > 0 && allowedExtensions.indexOf(fileExtension) === -1) {
            errors.push('Тип файла "' + fileExtension + '" не разрешен');
        }

        // Проверка имени файла
        if (this.isFileNameSuspicious(file.name)) {
            errors.push('Имя файла "' + file.name + '" содержит недопустимые символы');
        }

        return errors;
    },

    updateFilePreview: function (wrapper, files) {
        var preview = wrapper.querySelector('.selected-files-preview');
        preview.innerHTML = '';

        if (files.length === 0) {
            return;
        }

        var container = document.createElement('div');
        container.className = 'selected-files';

        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var fileItem = this.createFilePreviewItem(file, i, wrapper);
            container.appendChild(fileItem);
        }

        preview.appendChild(container);
    },

    createFilePreviewItem: function (file, index, wrapper) {
        var self = this;
        var item = document.createElement('div');
        item.className = 'file-item';

        var fileSize = this.formatFileSize(file.size);
        var isImage = this.isImageFile(file);

        item.innerHTML =
            '<div class="file-info-block">' +
            (isImage ? '<div class="file-thumbnail"></div>' : '<div class="file-icon">📄</div>') +
            '<div class="file-details">' +
            '<div class="file-name">' + file.name + '</div>' +
            '<div class="file-size">' + fileSize + '</div>' +
            '</div>' +
            '</div>' +
            '<button type="button" class="file-remove" title="Удалить файл">×</button>';

        // Создание миниатюры для изображений
        if (isImage) {
            this.createImageThumbnail(file, item.querySelector('.file-thumbnail'));
        }

        // Обработка удаления файла
        var removeBtn = item.querySelector('.file-remove');
        BX.bind(removeBtn, 'click', function () {
            self.removeFileFromSelection(wrapper, index);
            self.trackAnalytics('file_removed', {file_name: file.name});
        });

        return item;
    },

    createImageThumbnail: function (file, container) {
        if (!file.type.startsWith('image/')) return;

        var reader = new FileReader();
        reader.onload = function (e) {
            var img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'thumbnail-image';
            img.style.maxWidth = '50px';
            img.style.maxHeight = '50px';
            img.style.objectFit = 'cover';
            container.appendChild(img);
        };
        reader.readAsDataURL(file);
    },

    removeFileFromSelection: function (wrapper, index) {
        var input = wrapper.querySelector('input[type="file"]');
        var propertyId = input.name.replace('property_', '').replace('[]', '');
        var files = Array.from(this.selectedFiles.get(propertyId) || []);

        files.splice(index, 1);

        if (files.length === 0) {
            this.selectedFiles.delete(propertyId);
            input.value = '';
        } else {
            this.selectedFiles.set(propertyId, files);
            // Обновляем FileList в input
            var dataTransfer = new DataTransfer();
            files.forEach(function (file) {
                dataTransfer.items.add(file);
            });
            input.files = dataTransfer.files;
        }

        this.updateFilePreview(wrapper, files);
    },

    isImageFile: function (file) {
        return file.type.startsWith('image/');
    },

    formatFileSize: function (bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    },

    initMobileOptimizations: function () {
        if (!this.isMobile) return;

        // Оптимизация для мобильных устройств
        BX.addClass(this.form, 'mobile-optimized');

        // Увеличиваем размер touch targets
        var inputs = this.form.querySelectorAll('input, button, select, textarea');
        for (var i = 0; i < inputs.length; i++) {
            BX.addClass(inputs[i], 'mobile-input');
        }

        // Автоскролл к ошибкам валидации
        this.enableErrorScrolling();

        // Виртуальная клавиатура
        this.handleVirtualKeyboard();
    },

    enableErrorScrolling: function () {
        var self = this;
        this.originalShowMessage = this.showMessage;

        this.showMessage = function (message, type) {
            self.originalShowMessage.call(self, message, type);

            if (type === 'error' && self.messagesBlock) {
                setTimeout(function () {
                    self.messagesBlock.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }, 100);
            }
        };
    },

    handleVirtualKeyboard: function () {
        var initialHeight = window.innerHeight;

        window.addEventListener('resize', function () {
            var currentHeight = window.innerHeight;
            var diff = initialHeight - currentHeight;

            if (diff > 150) { // Клавиатура открыта
                document.body.style.height = currentHeight + 'px';
            } else {
                document.body.style.height = '';
            }
        });
    },

    isFileNameSuspicious: function (fileName) {
        return /[<>:"|?*]/.test(fileName) ||
            fileName.substring(0, fileName.lastIndexOf('.')).indexOf('.') !== -1;
    },

    showFileValidationError: function (wrapper, errors) {
        var errorDiv = document.createElement('div');
        errorDiv.className = 'file-validation-error text-danger mt-2';
        errorDiv.innerHTML = errors.join('<br>');

        wrapper.appendChild(errorDiv);
        BX.addClass(wrapper, 'has-error');

        // Анимация встряхивания для привлечения внимания
        BX.addClass(wrapper, 'shake');
        setTimeout(function () {
            BX.removeClass(wrapper, 'shake');
        }, 500);
    },

    clearFileValidationError: function (wrapper) {
        var errorDiv = wrapper.querySelector('.file-validation-error');
        if (errorDiv) {
            errorDiv.remove();
        }
        BX.removeClass(wrapper, 'has-error');
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
        this.trackAnalytics('form_submit_attempt');

        if (!this.validateForm()) {
            this.trackAnalytics('form_validation_failed');
            return false;
        }

        if (!this.validateAllFiles()) {
            this.showMessage('Пожалуйста, исправьте ошибки в загружаемых файлах', 'error');
            this.trackAnalytics('file_validation_failed_on_submit');
            return false;
        }

        if (this.params.useGoogleRecaptcha === 'Y') {
            this.submitWithRecaptcha();
        } else {
            this.submitForm();
        }
    },

    validateForm: function () {
        var isValid = true;
        var errors = [];

        this.clearValidationErrors();

        // Проверка согласия на политику персональных данных
        var privacyCheckbox = this.form.querySelector('#privacy_policy_agreement');
        if (privacyCheckbox && !privacyCheckbox.checked) {
            isValid = false;
            errors.push('Необходимо дать согласие на обработку персональных данных');
            this.addValidationError(privacyCheckbox.closest('.form-group'), 'Согласие обязательно');

            // Трекинг аналитики
            this.trackAnalytics('form_validation_error', {
                'error_type': 'privacy_policy_missing'
            });
        }

        var requiredFields = this.form.querySelectorAll('[required]');

        for (var i = 0; i < requiredFields.length; i++) {
            var field = requiredFields[i];
            var value = field.value.trim();

            if (!value || (field.type === 'checkbox' && !field.checked)) {
                isValid = false;
                var fieldGroup = field.closest('.form-group') || field.closest('.form-check');
                if (fieldGroup) {
                    this.addValidationError(fieldGroup, 'Это поле обязательно для заполнения');
                }
            }
        }

        if (!isValid) {
            this.showMessage(errors.join('. '), 'error');
            this.trackAnalytics('form_validation_failed', {
                'errors_count': errors.length
            });
        }

        return isValid;
    },

    // Добавляем метод для визуального выделения ошибок
    addValidationError: function (fieldGroup, message) {
        if (!fieldGroup) return;

        fieldGroup.classList.add('has-error');

        // Удаляем предыдущие сообщения об ошибках
        var existingError = fieldGroup.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        // Добавляем новое сообщение об ошибке
        var errorDiv = document.createElement('div');
        errorDiv.className = 'error-message text-danger small mt-1';
        errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>' + message;
        fieldGroup.appendChild(errorDiv);
    },

    clearValidationErrors: function () {
        var errorGroups = this.form.querySelectorAll('.has-error');
        for (var i = 0; i < errorGroups.length; i++) {
            errorGroups[i].classList.remove('has-error');
        }

        var errorMessages = this.form.querySelectorAll('.error-message');
        for (var j = 0; j < errorMessages.length; j++) {
            errorMessages[j].remove();
        }
    },

    validateAllFiles: function () {
        var fileInputs = this.form.querySelectorAll('input[type="file"]');
        var isValid = true;

        for (var i = 0; i < fileInputs.length; i++) {
            if (fileInputs[i].files && fileInputs[i].files.length > 0) {
                for (var j = 0; j < fileInputs[i].files.length; j++) {
                    var errors = this.validateSingleFile(fileInputs[i].files[j]);
                    if (errors.length > 0) {
                        isValid = false;
                        break;
                    }
                }
            }
        }

        return isValid;
    },

    getFieldValue: function (field) {
        if (field.type === 'checkbox' || field.type === 'radio') {
            var name = field.name;
            var checkedFields = this.form.querySelectorAll('input[name="' + name + '"]:checked');
            return checkedFields.length > 0;
        } else if (field.type === 'file') {
            return field.files && field.files.length > 0;
        } else {
            return field.value.trim();
        }
    },

    addValidationError: function (field) {
        BX.addClass(field, 'is-invalid');

        var formGroup = field.closest('.form-group');
        if (formGroup) {
            BX.addClass(formGroup, 'has-error');
        }
    },

    clearValidationErrors: function () {
        var invalidFields = this.form.querySelectorAll('.is-invalid');
        for (var i = 0; i < invalidFields.length; i++) {
            BX.removeClass(invalidFields[i], 'is-invalid');
        }

        var errorGroups = this.form.querySelectorAll('.has-error');
        for (var j = 0; j < errorGroups.length; j++) {
            BX.removeClass(errorGroups[j], 'has-error');
        }

        var fileErrors = this.form.querySelectorAll('.file-validation-error');
        for (var k = 0; k < fileErrors.length; k++) {
            fileErrors[k].remove();
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

    initInputMasks: function () {
        // Проверяем доступность Inputmask
        if (typeof Inputmask === 'undefined') {
            console.warn('Inputmask library not loaded');
            return;
        }

        var maskedInputs = this.form.querySelectorAll('.masked-input');
        var self = this;

        for (var i = 0; i < maskedInputs.length; i++) {
            this.setupInputMask(maskedInputs[i]);
        }

        // Логирование если доступно
        if (typeof console !== 'undefined') {
            console.log('Input masks initialized:', maskedInputs.length);
        }
    },

    setupInputMask: function (input) {
        var maskType = input.getAttribute('data-mask-type');
        var self = this;

        if (maskType === 'phone') {
            this.setupPhoneMask(input);
        } else if (maskType === 'email') {
            this.setupEmailMask(input);
        }
    },

    setupPhoneMask: function (input) {
        var self = this;

        // Создаем маску для телефонов
        var im = new Inputmask({
            mask: [
                '+7 (999) 999-99-99',
                '+9{1,4} 999 999 999',
                '+9{1,4} 999 999 9999',
                '+9{1,4} 999 999 99999'
            ],
            showMaskOnHover: false,
            showMaskOnFocus: true,
            placeholder: '_',
            clearIncomplete: true,
            autoUnmask: false,
            removeMaskOnSubmit: false,
            onBeforePaste: function (pastedValue, opts) {
                var cleaned = pastedValue.replace(/\D/g, '');

                if (cleaned.length === 11 && cleaned[0] === '8') {
                    cleaned = '7' + cleaned.substring(1);
                }

                if (cleaned.length === 11 && cleaned[0] === '7') {
                    cleaned = '+' + cleaned;
                }

                return cleaned;
            },
            onincomplete: function () {
                BX.addClass(input, 'is-invalid');
                BX.removeClass(input, 'is-valid');
            },
            oncomplete: function () {
                BX.removeClass(input, 'is-invalid');
                BX.addClass(input, 'is-valid');

                self.trackAnalytics('phone_complete', {
                    'field_name': input.name,
                    'phone_length': input.value.length
                });
            },
            onKeyValidation: function (key, result) {
                if (!result && input) {
                    input.style.borderColor = '#dc3545';
                    setTimeout(function () {
                        input.style.borderColor = '';
                    }, 300);
                }
            }
        });

        // Применяем маску
        im.mask(input);

        // Сохраняем ссылку на маску
        input._inputmask = im;

        // Дополнительные обработчики
        BX.bind(input, 'focus', function () {
            if (this.value === '' || this.value === '+7 (___) ___-__-__') {
                this.value = '+7 ';
                setTimeout(function () {
                    if (input.setSelectionRange) {
                        input.setSelectionRange(3, 3);
                    }
                }, 10);
            }
        });

        BX.bind(input, 'blur', function () {
            if (im.unmaskedvalue && im.unmaskedvalue().length === 0) {
                this.value = '';
                BX.removeClass(this, 'is-valid');
                BX.removeClass(this, 'is-invalid');
            }
        });

        BX.bind(input, 'input', function () {
            if (im.unmaskedvalue) {
                var unmaskedValue = im.unmaskedvalue();

                if (unmaskedValue.length >= 5 && unmaskedValue.length <= 15) {
                    self.trackAnalytics('phone_progress', {
                        'field_name': input.name,
                        'digits_entered': unmaskedValue.length
                    });
                }
            }
        });
    },

    setupEmailMask: function (input) {
        var self = this;

        // Для email не используем inputmask, делаем собственную валидацию
        BX.bind(input, 'input', function () {
            var value = this.value.toLowerCase();
            this.value = value;

            var isValid = self.validateEmail(value);

            if (value.length > 0) {
                if (isValid) {
                    BX.removeClass(this, 'is-invalid');
                    BX.addClass(this, 'is-valid');
                } else {
                    BX.removeClass(this, 'is-valid');
                    BX.addClass(this, 'is-invalid');
                }
            } else {
                BX.removeClass(this, 'is-valid');
                BX.removeClass(this, 'is-invalid');
            }

            self.handleEmailAutocomplete(this, value);
        });

        BX.bind(input, 'keydown', function (e) {
            // Tab для принятия автодополнения
            if (e.keyCode === 9 && this.getAttribute('data-suggestion')) {
                e.preventDefault();
                this.value = this.getAttribute('data-suggestion');
                this.removeAttribute('data-suggestion');
                this.placeholder = 'example@domain.com';

                var event;
                if (typeof Event === 'function') {
                    event = new Event('input', {bubbles: true});
                } else {
                    event = document.createEvent('Event');
                    event.initEvent('input', true, true);
                }
                this.dispatchEvent(event);
            }
        });
    },

    handleEmailAutocomplete: function (input, value) {
        if (!value.includes('@') || value.includes('.')) {
            input.removeAttribute('data-suggestion');
            input.placeholder = 'example@domain.com';
            return;
        }

        var parts = value.split('@');
        if (parts.length === 2 && parts[1].length > 0) {
            var domain = parts[1];
            var suggestions = [
                'gmail.com',
                'yandex.ru',
                'mail.ru',
                'outlook.com',
                'hotmail.com',
                'yahoo.com',
                'rambler.ru',
                'inbox.ru'
            ];

            for (var i = 0; i < suggestions.length; i++) {
                if (suggestions[i].indexOf(domain) === 0 && suggestions[i] !== domain) {
                    var suggestion = parts[0] + '@' + suggestions[i];
                    input.setAttribute('data-suggestion', suggestion);
                    input.placeholder = suggestion;
                    return;
                }
            }
        }

        input.removeAttribute('data-suggestion');
        input.placeholder = 'example@domain.com';
    },


    setupEmailValidation: function (input) {
        var self = this;

        BX.bind(input, 'input', function (e) {
            var value = e.target.value.toLowerCase();
            e.target.value = value;

            // Простая валидация email в реальном времени
            var isValid = self.validateEmail(value);

            if (value.length > 0) {
                if (isValid) {
                    BX.removeClass(e.target, 'is-invalid');
                    BX.addClass(e.target, 'is-valid');
                } else {
                    BX.removeClass(e.target, 'is-valid');
                    BX.addClass(e.target, 'is-invalid');
                }
            } else {
                BX.removeClass(e.target, 'is-valid');
                BX.removeClass(e.target, 'is-invalid');
            }

            // Аналитика
            if (isValid && value.length > 5) {
                self.trackAnalytics('email_valid', {
                    field_name: e.target.name,
                    domain: value.split('@')[1] || ''
                });
            }
        });

        // Автодополнение популярных доменов
        BX.bind(input, 'keyup', function (e) {
            if (e.keyCode === 9 || e.keyCode === 13) return; // Tab или Enter

            var value = e.target.value;
            if (value.includes('@') && !value.includes('.')) {
                var parts = value.split('@');
                if (parts.length === 2 && parts[1].length > 0) {
                    var domain = parts[1];
                    var suggestions = ['gmail.com', 'yandex.ru', 'mail.ru', 'outlook.com', 'yahoo.com'];

                    for (var i = 0; i < suggestions.length; i++) {
                        if (suggestions[i].indexOf(domain) === 0) {
                            var suggestion = parts[0] + '@' + suggestions[i];
                            self.showEmailSuggestion(e.target, suggestion, parts[0].length + 1 + domain.length);
                            break;
                        }
                    }
                }
            }
        });
    },

    validateEmail: function (email) {
        var re = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;
        return re.test(email.toLowerCase());
    },

    showEmailSuggestion: function (input, suggestion, cursorPos) {
        // Простое автодополнение через placeholder
        if (input.value.length < suggestion.length) {
            input.setAttribute('data-suggestion', suggestion);

            // Добавляем подсказку в placeholder, если поле пустое
            if (input.value === '') {
                input.placeholder = suggestion;
            }
        }
    },

    validateMaskedFields: function () {
        var maskedInputs = this.form.querySelectorAll('.masked-input');
        var errors = [];

        for (var i = 0; i < maskedInputs.length; i++) {
            var input = maskedInputs[i];
            var maskType = input.getAttribute('data-mask-type');
            var value = input.value.trim();

            if (input.hasAttribute('required') && value === '') {
                continue;
            }

            if (value !== '') {
                if (maskType === 'email' && !this.validateEmail(value)) {
                    errors.push('Поле "' + this.getFieldLabel(input) + '" должно содержать корректный email адрес');
                } else if (maskType === 'phone' && !this.validatePhone(value)) {
                    errors.push('Поле "' + this.getFieldLabel(input) + '" должно содержать корректный номер телефона');
                }
            }
        }

        return errors;
    },

    validatePhone: function (phone) {
        var cleaned = phone.replace(/\D/g, '');

        if (cleaned.length === 11 && cleaned[0] === '7') {
            return true;
        }

        if (cleaned.length >= 7 && cleaned.length <= 15) {
            return true;
        }

        return false;
    },

    getFieldLabel: function (input) {
        var label = this.form.querySelector('label[for="' + input.id + '"]');
        return label ? label.textContent.replace('*', '').trim() : input.name;
    },

    // Метод для получения очищенного значения телефона
    getPhoneValue: function (input) {
        if (input._inputmask && input._inputmask.unmaskedvalue) {
            return input._inputmask.unmaskedvalue();
        }
        return input.value.replace(/\D/g, '');
    },

// Метод для программной установки значения телефона
    setPhoneValue: function (input, phoneNumber) {
        if (input._inputmask && input._inputmask.setValue) {
            input._inputmask.setValue(phoneNumber);
        } else {
            input.value = phoneNumber;
        }
    },

    submitForm: function () {
        this.isSubmitting = true;
        this.setSubmitButtonState(true);
        this.trackAnalytics('form_submit_started');

        var formData = new FormData(this.form);

        // Получаем очищенные значения телефонов для отправки
        var phoneInputs = this.form.querySelectorAll('input[data-mask-type="phone"]');
        for (var i = 0; i < phoneInputs.length; i++) {
            var input = phoneInputs[i];
            var cleanedPhone = this.getPhoneValue(input);

            if (cleanedPhone && cleanedPhone.length > 0) {
                // Отправляем с + если это не международный номер
                var phoneToSend = cleanedPhone;
                if (cleanedPhone.length === 11 && cleanedPhone[0] === '7') {
                    phoneToSend = '+' + cleanedPhone;
                } else if (cleanedPhone[0] !== '+') {
                    phoneToSend = '+' + cleanedPhone;
                }
                formData.set(input.name, phoneToSend);
            }
        }

        formData.append('sessid', this.sessid);

        var self = this;

        BX.ajax({
            method: 'POST',
            dataType: 'json',
            url: window.location.href,
            data: formData,
            processData: false,
            start: function () {
                self.trackAnalytics('ajax_request_started');
            },
            onsuccess: function (response) {
                self.onSuccess(response);
            },
            onfailure: function () {
                self.onFailure();
            }
        });
    },

    onSuccess: function (response) {
        this.isSubmitting = false;
        this.setSubmitButtonState(false);

        if (response.success) {
            this.showMessage(response.message, 'success');
            this.resetForm();
            this.trackAnalytics('form_submit_success', {
                element_id: response.element_id
            });

            // Закрываем модальное окно через 2 секунды
            setTimeout(BX.proxy(this.hideModal, this), 2000);
        } else {
            this.showMessage(response.message, 'error');
            this.trackAnalytics('form_submit_error', {
                error_message: response.message
            });
        }
    },

    onFailure: function () {
        this.isSubmitting = false;
        this.setSubmitButtonState(false);
        this.showMessage('Ошибка отправки формы', 'error');
        this.trackAnalytics('form_submit_failure');
    },

    setSubmitButtonState: function (loading) {
        if (this.submitButton) {
            this.submitButton.disabled = loading;
            this.submitButton.innerHTML = loading ?
                '<span class="spinner-border spinner-border-sm me-2"></span>Отправляется...' :
                'Отправить';
        }
    },

    resetForm: function () {
        this.form.reset();
        this.clearValidationErrors();
        this.selectedFiles.clear();

        // Очищаем превью файлов
        var previews = this.form.querySelectorAll('.selected-files-preview');
        for (var i = 0; i < previews.length; i++) {
            previews[i].innerHTML = '';
        }
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

            // Автоскрытие success сообщений на мобильных
            if (type === 'success' && this.isMobile) {
                setTimeout(BX.proxy(this.hideMessage, this), 3000);
            }
        }
    },

    hideMessage: function () {
        if (this.messagesBlock) {
            this.messagesBlock.style.display = 'none';
            this.messagesBlock.innerHTML = '';
        }
    },

    trackAnalytics: function (eventName, parameters) {
        this.analytics.track(eventName, Object.assign({
            form_id: this.params.formId,
            timestamp: Date.now()
        }, parameters || {}));
    },

    // Публичные методы для расширения функциональности
    addCustomValidator: function (validator) {
        // Возможность добавления кастомных валидаторов
    },

    getFormData: function () {
        return new FormData(this.form);
    },

    destroy: function () {
        // Очистка event listeners при уничтожении компонента
        this.selectedFiles.clear();
        // Дополнительная очистка...
    }
};

/**
 * Класс для аналитики
 */
BX.DDAPP.Tools.Analytics = function () {
    this.queue = [];
    this.init();
};

BX.DDAPP.Tools.Analytics.prototype = {
    init: function () {
        // Обработка очереди аналитики если GA загружен
        if (typeof gtag !== 'undefined') {
            this.processQueue();
        }

        // Обработка очереди из головы страницы
        if (window.ddappAnalytics) {
            this.queue = this.queue.concat(window.ddappAnalytics);
            this.processQueue();
        }
    },

    track: function (eventName, parameters) {
        var event = {
            event_name: eventName,
            parameters: parameters || {}
        };

        this.queue.push(event);
        this.processQueue();
    },

    processQueue: function () {
        if (typeof gtag === 'undefined') {
            return;
        }

        while (this.queue.length > 0) {
            var event = this.queue.shift();

            // Google Analytics
            gtag('event', event.event_name, event.parameters);

            // Яндекс.Метрика цели
            if (typeof ym !== 'undefined' && window.yaCounter) {
                var goalName = this.mapEventToYandexGoal(event.event_name);
                if (goalName) {
                    ym(window.yaCounter, 'reachGoal', goalName, event.parameters);
                }
            }
        }
    },

    mapEventToYandexGoal: function (eventName) {
        var mapping = {
            'form_submit_success': 'form_submit',
            'form_opened': 'form_view',
            'files_selected': 'file_upload'
        };

        return mapping[eventName] || null;
    }
};