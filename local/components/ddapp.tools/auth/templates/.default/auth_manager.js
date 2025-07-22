BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.AuthManager = function (params) {
    this.params = params || {};
    this.componentId = params.componentId;
    this.buttons = params.buttons;
    this.ajaxUrl = params.ajaxUrl;
    this.authParams = {};
    this.modalsLoaded = {
        login: false,
        register: false,
        forgot: false
    };

    this.init();
};

BX.DDAPP.Tools.AuthManager.prototype = {

    init: function () {
        console.log('AuthManager: Params', this.params);

        // Создаем контейнер для toast'ов если его нет
        this.ensureToastContainer();

        this.authParams = JSON.parse(this.buttons.login?.getAttribute('data-auth-params') || '{}');

        if (Object.keys(this.authParams).length === 0) {
            console.error('AuthManager: The authParams is empty', this.authParams);
            this.showToast('Ошибка в параметрах авторизации', 'error');
            return;
        }

        // Привязываем обработчики к кнопкам
        this.bindButtonEvents();
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

    bindButtonEvents: function () {
        // Кнопка входа
        if (this.buttons.login) {
            this.buttons.login.addEventListener('click', this.handleLoginClick.bind(this));
        }

        // Кнопка регистрации
        if (this.buttons.register) {
            this.buttons.register.addEventListener('click', this.handleRegisterClick.bind(this));
        }

        // Кнопка выхода
        if (this.buttons.logout) {
            this.buttons.logout.addEventListener('click', this.handleLogoutClick.bind(this));
        }
    },

    handleLoginClick: function (e) {
        e.preventDefault();
        this.loadModal('login');
    },

    handleRegisterClick: function (e) {
        e.preventDefault();
        this.loadModal('register');
    },

    handleLogoutClick: function (e) {
        e.preventDefault();
        this.logout();
    },

    loadModal: function (type) {
        // Если модальное окно уже загружено, просто показываем его
        if (this.modalsLoaded[type]) {
            this.showModal(type);
            return;
        }

        var postData = {
            'action': 'load_' + type,
            'id': this.componentId,
            'auth-params': this.authParams,
            'sessid': BX.bitrix_sessid()
        };

        console.log('AuthManager: Loading modal', type, postData);

        BX.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            data: postData,
            dataType: 'json',
            onsuccess: this.onModalLoaded.bind(this, type),
            onfailure: this.onLoadError.bind(this)
        });
    },

    onModalLoaded: function (type, result) {
        if (result && result.success) {
            // Добавляем модальное окно в DOM
            document.body.insertAdjacentHTML('beforeend', result.html);
            this.modalsLoaded[type] = true;

            // Инициализируем обработчики для формы
            this.initFormHandlers(type);

            // Показываем модальное окно
            this.showModal(type);

            // Настраиваем удаление при закрытии
            this.setupModalCleanup(type);
        } else {
            console.error('AuthManager: Unexpected response', result);
            this.showToast(result && result.message ? result.message : 'Ошибка загрузки формы', 'error');
        }
    },

    onLoadError: function (error) {
        console.error('AuthManager: Modal load error', error);
        this.showToast('Ошибка загрузки модального окна', 'error');
    },

    showModal: function (type) {
        var modalElement = document.getElementById(this.componentId + '_' + type + '_modal');
        if (modalElement) {
            var modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();

            // Устанавливаем фокус на первое поле ввода после показа модального окна
            modalElement.addEventListener('shown.bs.modal', function () {
                var firstInput = modalElement.querySelector('input[type="text"], input[type="email"]');
                if (firstInput) {
                    firstInput.focus();
                }
            }, {once: true});
        }
    },

    setupModalCleanup: function (type) {
        var modalElement = document.getElementById(this.componentId + '_' + type + '_modal');
        var self = this;

        if (modalElement) {
            // Обрабатываем событие после скрытия модального окна
            modalElement.addEventListener('hidden.bs.modal', function () {
                this.remove();
                self.modalsLoaded[type] = false;
            });
        }
    },

    initFormHandlers: function (type) {
        var form = document.getElementById(this.componentId + '_' + type + '_form');
        var messageDiv = document.getElementById(this.componentId + '_' + type + '_message');

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                this.handleFormSubmit(type, form, messageDiv);
            }.bind(this));

            // Инициализируем переключение между формами
            this.initFormSwitching(type);
        }
    },

    initFormSwitching: function (type) {
        var modal = document.getElementById(this.componentId + '_' + type + '_modal');
        if (!modal) return;

        // Ссылка "Забыли пароль?" в форме входа
        var forgotLink = modal.querySelector('.forgot-password-link');
        if (forgotLink) {
            forgotLink.addEventListener('click', function (e) {
                e.preventDefault();
                bootstrap.Modal.getInstance(modal).hide();
                setTimeout(() => {
                    this.loadModal('forgot');
                }, 300);
            }.bind(this));
        }

        // Ссылка "Регистрация" в форме входа
        var registerLink = modal.querySelector('.register-link');
        if (registerLink) {
            registerLink.addEventListener('click', function (e) {
                e.preventDefault();
                bootstrap.Modal.getInstance(modal).hide();
                setTimeout(() => {
                    this.loadModal('register');
                }, 300);
            }.bind(this));
        }

        // Ссылка "Вход" в форме регистрации
        var loginLink = modal.querySelector('.login-link');
        if (loginLink) {
            loginLink.addEventListener('click', function (e) {
                e.preventDefault();
                bootstrap.Modal.getInstance(modal).hide();
                setTimeout(() => {
                    this.loadModal('login');
                }, 300);
            }.bind(this));
        }

        // Ссылка "Назад к входу" в форме восстановления пароля
        var backToLoginLink = modal.querySelector('.back-to-login-link');
        if (backToLoginLink) {
            backToLoginLink.addEventListener('click', function (e) {
                e.preventDefault();
                bootstrap.Modal.getInstance(modal).hide();
                setTimeout(() => {
                    this.loadModal('login');
                }, 300);
            }.bind(this));
        }
    },

    handleFormSubmit: function (type, form, messageDiv) {
        var formData = new FormData(form);
        var postData = {
            'action': type === 'login' ? 'auth' : type,
            'id': this.componentId,
            'auth-params': this.authParams,
            'sessid': BX.bitrix_sessid()
        };

        // Добавляем данные формы
        for (var pair of formData.entries()) {
            postData[pair[0]] = pair[1];
        }

        console.log('AuthManager: Submitting form', type, postData);

        BX.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            data: postData,
            dataType: 'json',
            onsuccess: function (response) {
                this.onFormResponse(type, response, messageDiv);
            }.bind(this),
            onfailure: function (error) {
                this.onFormError(type, error, messageDiv);
            }.bind(this)
        });
    },

    onFormResponse: function (type, response, messageDiv) {
        console.log('AuthManager: Form response', type, response);

        if (response && response.success) {
            // Показываем success toast
            this.showToast(response.message || 'Операция выполнена успешно!', 'success');

            // Закрываем модальное окно через 2 секунды
            setTimeout(function () {
                var modal = bootstrap.Modal.getInstance(document.getElementById(this.componentId + '_' + type + '_modal'));
                if (modal) {
                    modal.hide();
                }

                // Перенаправляем если указан URL
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else if (type === 'login' || type === 'register') {
                    // Перезагружаем страницу для обновления состояния авторизации
                    window.location.reload();
                }
            }.bind(this), 2000);
        } else {
            this.showMessage(messageDiv, response.message, 'error');
        }
    },

    onFormError: function (type, error, messageDiv) {
        console.error('AuthManager: Form submission error', type, error);
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

    logout: function () {
        var postData = {
            'action': 'logout',
            'id': this.componentId,
            'auth-params': this.authParams,
            'sessid': BX.bitrix_sessid()
        };

        BX.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            data: postData,
            dataType: 'json',
            onsuccess: function (response) {
                if (response && response.success) {
                    this.showToast(response.message || 'Вы успешно вышли из системы', 'success');

                    setTimeout(function () {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.reload();
                        }
                    }, 1000);
                }
            }.bind(this),
            onfailure: function (error) {
                console.error('AuthManager: Logout error', error);
                this.showToast('Ошибка при выходе из системы', 'error');
            }.bind(this)
        });
    },

    destroy: function () {
        // Удаляем обработчики событий
        if (this.buttons.login) {
            this.buttons.login.removeEventListener('click', this.handleLoginClick);
        }
        if (this.buttons.register) {
            this.buttons.register.removeEventListener('click', this.handleRegisterClick);
        }
        if (this.buttons.logout) {
            this.buttons.logout.removeEventListener('click', this.handleLogoutClick);
        }

        // Удаляем модальные окна
        ['login', 'register', 'forgot'].forEach(function (type) {
            var modalElement = document.getElementById(this.componentId + '_' + type + '_modal');
            if (modalElement) {
                modalElement.remove();
            }
        }.bind(this));

        this.modalsLoaded = {
            login: false,
            register: false,
            forgot: false
        };
    }
};