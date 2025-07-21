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
            this.showToast( result && result.message ? result.message : 'Ошибка загрузки формы', 'error');
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

    initFormHandlers: function () {
        var form = document.getElementById(this.componentId + '_form');
        //var input = document.getElementById(this.componentId + '_input');
        var messageDiv = document.getElementById(this.componentId + '_message');

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                //this.handleFormSubmit(input.value, messageDiv);
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
            // Показываем сообщение в модальном окне
            //this.showMessage(messageDiv, response.message, response.status);

            // Дополнительно показываем toast для успешных операций
            //if (response.status === 'success') {
            //    this.showToast(response.message, 'success');
            //}
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