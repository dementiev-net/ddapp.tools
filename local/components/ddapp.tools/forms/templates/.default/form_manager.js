/**
 * 2Dapp Tools Form Manager
 * @version 1.0.0
 */

BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.FormManager = function (params) {

    this.params = params || {};
    this.componentId = params.componentId;
    this.button = params.button;
    this.ajaxUrl = params.ajaxUrl;
    this.modalTitle = params.modalTitle;
    this.inputPlaceholder = params.inputPlaceholder;
    this.ajaxUrl = params && params.ajaxUrl ? params.ajaxUrl : window.location.href;
    this.modalLoaded = false;

    this.init();
};

BX.DDAPP.Tools.FormManager.prototype = {

    init: function() {

        console.log('FormManager: Params', this.params);

        if (this.button) {
            this.button.addEventListener('click', this.handleButtonClick.bind(this));
        }
    },

    handleButtonClick: function(e) {
        e.preventDefault();

        // Если модальное окно уже загружено, просто показываем его
        if (this.modalLoaded) {
            this.showModal();
            return;
        }

        this.loadModal();
    },

    loadModal: function() {
        var postData = {
            'component_action': 'load_modal',
            'component_id': this.componentId,
            'modal_title': this.modalTitle,
            'input_placeholder': this.inputPlaceholder,
            'template': this.button.getAttribute('data-template'),
            'sessid': BX.bitrix_sessid()
        };

        console.log('Loading modal for component:', this.componentId);
        console.log('Post data:', postData);

        BX.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            data: postData,
            dataType: 'json',
            onsuccess: this.onModalLoaded.bind(this),
            onfailure: this.onLoadError.bind(this)
        });
    },

    onModalLoaded: function(response) {
        console.log('Modal loaded:', response);

        if (response && response.status === 'success') {
            // Добавляем модальное окно в DOM
            document.body.insertAdjacentHTML('beforeend', response.html);
            this.modalLoaded = true;

            // Инициализируем обработчики для формы
            this.initFormHandlers();

            // Показываем модальное окно
            this.showModal();

            // Настраиваем удаление при закрытии
            this.setupModalCleanup();
        } else {
            console.error('Unexpected response:', response);
            this.showError('Ошибка загрузки формы');
        }
    },

    onLoadError: function(error) {
        console.error('Modal load error:', error);
        this.showError('Ошибка загрузки модального окна');
    },

    showModal: function() {
        var modalElement = document.getElementById(this.componentId + '_modal');
        if (modalElement) {
            var modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();

            // Устанавливаем фокус на первое поле ввода после показа модального окна
            modalElement.addEventListener('shown.bs.modal', function() {
                var firstInput = modalElement.querySelector('input[type="text"]');
                if (firstInput) {
                    firstInput.focus();
                }
            }, { once: true }); // Выполняется только один раз
        }
    },

    setupModalCleanup: function() {
        var modalElement = document.getElementById(this.componentId + '_modal');
        var self = this; // Сохраняем ссылку на экземпляр класса

        if (modalElement) {
            // Обрабатываем событие перед скрытием модального окна
            modalElement.addEventListener('hide.bs.modal', function() {
                // Убираем фокус с любых элементов внутри модального окна
                var focusedElement = modalElement.querySelector(':focus');
                if (focusedElement) {
                    focusedElement.blur();
                }

                // Возвращаем фокус на кнопку, которая открыла модальное окно
                if (self.button) {
                    setTimeout(function() {
                        self.button.focus();
                    }, 100);
                }
            });

            // Обрабатываем событие после скрытия модального окна
            modalElement.addEventListener('hidden.bs.modal', function() {
                // this здесь ссылается на modalElement
                this.remove(); // Удаляем модальное окно из DOM
                self.modalLoaded = false; // Обновляем состояние в нашем классе
            });
        }
    },

    initFormHandlers: function() {
        var form = document.getElementById(this.componentId + '_form');
        var input = document.getElementById(this.componentId + '_input');
        var messageDiv = document.getElementById(this.componentId + '_message');

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                this.handleFormSubmit(input.value, messageDiv);
            }.bind(this));
        }
    },

    handleFormSubmit: function(value, messageDiv) {
        var postData = {
            'component_action': 'check_value',
            'component_id': this.componentId,
            'value': value,
            'sessid': BX.bitrix_sessid()
        };

        console.log('Submitting form for component:', this.componentId);
        console.log('Value:', value);
        console.log('Post data:', postData);

        BX.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            data: postData,
            dataType: 'json',
            onsuccess: function(response) {
                this.onFormResponse(response, messageDiv);
            }.bind(this),
            onfailure: function(error) {
                this.onFormError(error, messageDiv);
            }.bind(this)
        });
    },

    onFormResponse: function(response, messageDiv) {
        console.log('Form response:', response);

        if (response && response.status) {
            this.showMessage(messageDiv, response.message, response.status);
        } else {
            console.error('Invalid response format:', response);
            this.showMessage(messageDiv, 'Ошибка формата ответа', 'error');
        }
    },

    onFormError: function(error, messageDiv) {
        console.error('Form submission error:', error);
        this.showMessage(messageDiv, 'Ошибка отправки запроса', 'error');
    },

    showMessage: function(messageDiv, message, status) {
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

    showError: function(message) {
        console.error('DDAppModalForm Error:', message);
        // Можно добавить показ глобального уведомления
        alert('Ошибка: ' + message);
    },

    destroy: function() {
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