BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.FormManager = function (params) {

    this.params = params || {};
    this.componentId = params.componentId;
    this.button = params.button;
    this.ajaxUrl = params.ajaxUrl;
    this.formParams = {};
    this.modalLoaded = false;

    this.init();
};

BX.DDAPP.Tools.FormManager.prototype = {

    init: function () {

        console.log('FormManager: Params', this.params);

        // Создаем контейнер для toast'ов если его нет
        this.ensureToastContainer();

        this.formParams = JSON.parse(this.button.getAttribute('data-form-params') || '{}');

        if (Object.keys(this.formParams).length === 0) {
            console.error('FormManager: The formParams is empty', this.formParams);
            this.showToast('Ошибка в параметрах формы', 'error');
            return;
        }

        if (this.button) {
            this.button.addEventListener('click', this.handleButtonClick.bind(this));
        }
    },

    ensureToastContainer: function () {
        var containerId = 'ddapp-toast-container';
        var container = document.getElementById(containerId);

        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        this.toastContainer = container;
    },

    showToast: function (message, type, delay) {
        type = type || 'info';
        delay = delay || 5000;

        var toastId = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        var typeConfig = {
            'success': {
                bgClass: 'bg-success'
            },
            'error': {
                bgClass: 'bg-danger'
            },
            'warning': {
                bgClass: 'bg-warning text-dark'
            },
            'info': {
                bgClass: 'bg-info'
            }
        };

        var config = typeConfig[type] || typeConfig['info'];

        var toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${config.bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Закрыть"></button>
                </div>
            </div>`;

        this.toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        var toastElement = document.getElementById(toastId);
        var toast = new bootstrap.Toast(toastElement, {
            delay: delay,
            autohide: true
        });

        // Удаляем элемент из DOM после скрытия
        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });

        toast.show();

        return toast;
    },

    handleButtonClick: function (e) {
        e.preventDefault();

        // Если модальное окно уже загружено, просто показываем его
        if (this.modalLoaded) {
            this.showModal();
            return;
        }

        this.loadModal();
    },

    loadModal: function () {
        var postData = {
            'action': 'load',
            'id': this.componentId,
            'form-params': this.formParams,
            'template': this.button.getAttribute('data-template'),
            'sessid': BX.bitrix_sessid()
        };

        console.log('FormManager: Loading modal', postData);

        BX.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            data: postData,
            dataType: 'json',
            onsuccess: this.onModalLoaded.bind(this),
            onfailure: this.onLoadError.bind(this)
        });
    },

    onModalLoaded: function (result) {
        if (result && result.success) {
            // Добавляем модальное окно в DOM
            document.body.insertAdjacentHTML('beforeend', result.html);
            this.modalLoaded = true;

            // Инициализируем обработчики для формы
            this.initFormHandlers();

            // Показываем модальное окно
            this.showModal();

            // Настраиваем удаление при закрытии
            this.setupModalCleanup();
        } else {
            console.error('FormManager: Unexpected response', result);
            this.showToast(result && result.message ? result.message : 'Ошибка загрузки формы', 'error');
        }
    },

    onLoadError: function (error) {
        console.error('FormManager: Modal load error', error);
        this.showToast('Ошибка загрузки модального окна', 'error');
    },

    showModal: function () {
        var modalElement = document.getElementById(this.componentId + '_modal');
        if (modalElement) {
            var modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();

            // Устанавливаем фокус на первое поле ввода после показа модального окна
            modalElement.addEventListener('shown.bs.modal', function () {
                var firstInput = modalElement.querySelector('input[type="text"]');
                if (firstInput) {
                    firstInput.focus();
                }
            }, {once: true}); // Выполняется только один раз
        }
    },

    setupModalCleanup: function () {
        var modalElement = document.getElementById(this.componentId + '_modal');
        var self = this; // Сохраняем ссылку на экземпляр класса

        if (modalElement) {
            // Обрабатываем событие перед скрытием модального окна
            modalElement.addEventListener('hide.bs.modal', function () {
                // Убираем фокус с любых элементов внутри модального окна
                var focusedElement = modalElement.querySelector(':focus');
                if (focusedElement) {
                    focusedElement.blur();
                }

                // Возвращаем фокус на кнопку, которая открыла модальное окно
                if (self.button) {
                    setTimeout(function () {
                        self.button.focus();
                    }, 100);
                }
            });

            // Обрабатываем событие после скрытия модального окна
            modalElement.addEventListener('hidden.bs.modal', function () {
                // this здесь ссылается на modalElement
                this.remove(); // Удаляем модальное окно из DOM
                self.modalLoaded = false; // Обновляем состояние в нашем классе
            });
        }
    },

    initFileUpload: function () {
        var fileAreas = document.querySelectorAll('.file-upload-area');

        fileAreas.forEach(function (area) {
            var input = area.querySelector('input[type="file"]');
            var dropZone = area.querySelector('.file-drop-zone');
            var previewList = area.querySelector('.file-preview-list');
            var selectButton = dropZone.querySelector('button');

            // Клик по кнопке выбора
            selectButton.addEventListener('click', function (e) {
                e.preventDefault();
                input.click();
            });

            // Drag & Drop
            dropZone.addEventListener('dragover', function (e) {
                e.preventDefault();
                dropZone.classList.add('border-primary', 'bg-light');
            });

            dropZone.addEventListener('dragleave', function (e) {
                e.preventDefault();
                dropZone.classList.remove('border-primary', 'bg-light');
            });

            dropZone.addEventListener('drop', function (e) {
                e.preventDefault();
                dropZone.classList.remove('border-primary', 'bg-light');

                var files = e.dataTransfer.files;
                input.files = files;
                this.showFilePreview(files, previewList);
            }.bind(this));

            // Изменение через input
            input.addEventListener('change', function (e) {
                this.showFilePreview(e.target.files, previewList);
            }.bind(this));
        }.bind(this));
    },

    showFilePreview: function (files, container) {
        container.innerHTML = '';

        Array.from(files).forEach(function (file, index) {
            var fileItem = document.createElement('div');
            fileItem.className = 'file-preview-item d-flex align-items-center justify-content-between p-2 border rounded mb-2';

            var fileInfo = document.createElement('div');
            fileInfo.innerHTML = '<i class="fas fa-file me-2"></i>' + file.name + ' <small class="text-muted">(' + this.formatFileSize(file.size) + ')</small>';

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.addEventListener('click', function () {
                // Логика удаления файла
            });

            fileItem.appendChild(fileInfo);
            fileItem.appendChild(removeBtn);
            container.appendChild(fileItem);
        }.bind(this));
    },

    formatFileSize: function (bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    initEmailAutocomplete: function () {
        var emailInputs = document.querySelectorAll('input[type="text"]');
        var commonDomains = ['gmail.com', 'yandex.ru', 'mail.ru', 'outlook.com', 'yahoo.com'];

        emailInputs.forEach(function (input) {
            var hint = input.parentElement.querySelector('.email-hint');
            if (!hint) return;

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Tab' && input.value.includes('@') && !input.value.includes('.')) {
                    e.preventDefault();
                    var atIndex = input.value.indexOf('@');
                    var domain = input.value.substring(atIndex + 1);

                    var match = commonDomains.find(d => d.startsWith(domain));
                    if (match) {
                        input.value = input.value.substring(0, atIndex + 1) + match;
                        input.setSelectionRange(atIndex + 1 + domain.length, input.value.length);
                    }
                }
            });
        });
    },

    initFormHandlers: function () {
        var form = document.getElementById(this.componentId + '_form');
        var messageDiv = document.getElementById(this.componentId + '_message');

        if (form) {
            this.initFileUpload();
            this.initEmailAutocomplete();

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                this.handleFormSubmit("", messageDiv);
            }.bind(this));
        }
    },

    handleFormSubmit: function (value, messageDiv) {
        var postData = {
            'action': 'save',
            'id': this.componentId,
            'form-params': this.formParams,
            'template': this.button.getAttribute('data-template'),
            'sessid': BX.bitrix_sessid()
        };

        console.log('FormManager: Submitting form', postData);

        BX.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            data: postData,
            dataType: 'json',
            onsuccess: function (response) {
                this.onFormResponse(response, messageDiv);
            }.bind(this),
            onfailure: function (error) {
                this.onFormError(error, messageDiv);
            }.bind(this)
        });
    },

    onFormResponse: function (response, messageDiv) {
        console.log('FormManager: Form response', response);

        if (response && response.success) {
            // Показываем success toast
            this.showToast(response.message || 'Форма успешно отправлена!', 'success');

            // Закрываем модальное окно через 2 секунды
            setTimeout(function () {
                var modal = bootstrap.Modal.getInstance(document.getElementById(this.componentId + '_modal'));
                if (modal) {
                    modal.hide();
                }
            }.bind(this), 2000);

            // Очищаем форму
            var form = document.getElementById(this.componentId + '_form');
            if (form) {
                form.reset();
                // Очищаем превью файлов
                var previews = form.querySelectorAll('.file-preview-list');
                previews.forEach(function (preview) {
                    preview.innerHTML = '';
                });
            }
        } else {
            this.showMessage(messageDiv, response.message, 'error');
        }
    },

    onFormError: function (error, messageDiv) {
        console.error('FormManager: Form submission error', error);
        this.showToast('Ошибка отправки запроса', 'error');
    },

    showMessage: function (messageDiv, message, status) {
        if (!messageDiv) return;

        messageDiv.className = 'alert';
        messageDiv.classList.remove('d-none', 'alert-success', 'alert-danger');

        if (status === 'success') {
            messageDiv.classList.add('alert-success');
        } else {
            messageDiv.classList.add('alert-danger');
        }

        messageDiv.textContent = message;
    },

    destroy: function () {
        if (this.button) {
            this.button.removeEventListener('click', this.handleButtonClick);
        }

        var modalElement = document.getElementById(this.componentId + '_modal');
        if (modalElement) {
            modalElement.remove();
        }

        this.modalLoaded = false;
    }
};