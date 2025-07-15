/**
 * 2Dapp Tools SMTP Test Module
 * @version 1.0.0
 */

BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.SmtpTest = function (params) {

    this.params = params || {};
    this.testButton = null;
    this.messageWrap = null;
    this.messageOk = null;
    this.messageError = null;
    this.messageLoading = null;

    this.isTesting = false;
    this.sessid = BX.bitrix_sessid();

    this.init();
};

BX.DDAPP.Tools.SmtpTest.prototype = {

    init: function () {
        this.initElements();
        this.bindEvents();
    },

    initElements: function () {
        this.testButton = BX(this.params.buttonId) || BX('smtp_test');
        this.messageWrap = BX(this.params.messageWrapId || 'smtp_test_result');
        this.messageOk = BX(this.params.messageOkId);
        this.messageError = BX(this.params.messageErrorId);
        this.messageLoading = BX(this.params.messageLoadingId);

        if (!this.testButton) {
            console.warn('SmtpTest: Test button not found');
            return false;
        }

        if (!this.messageWrap) {
            console.warn('SmtpTest: Message wrap element not found');
            return false;
        }

        return true;
    },

    bindEvents: function () {
        if (!this.testButton) return;

        BX.bind(this.testButton, 'click', BX.proxy(this.onTestClick, this));
    },

    onTestClick: function (event) {
        event.preventDefault();

        if (this.isTesting) {
            return;
        }

        this.startTest();
    },

    startTest: function () {
        this.setTestingState(true);
        this.showMessage('loading');

        BX.ajax({
            url: this.params.ajaxUrl || '',
            method: 'POST',
            dataType: 'json',
            timeout: this.params.timeout || 30,
            data: {
                sessid: this.sessid,
                action: 'smtp_test'
            },
            onsuccess: BX.proxy(this.onTestSuccess, this),
            onfailure: BX.proxy(this.onTestFailure, this),
            onloadstart: BX.proxy(this.onLoadStart, this),
            onloadend: BX.proxy(this.onLoadEnd, this)
        });
    },

    onTestSuccess: function (result) {
        if (result && result.success) {
            this.showMessage('success', result.message);
        } else {
            this.showMessage('error', result && result.message ? result.message : null);
        }

        // Выводим отладочную информацию
        if (result && result.debug) {
            console.log('SMTP Test Debug:', result.debug);
        }
    },

    onTestFailure: function (xhr) {
        this.showMessage('ajax_error');
        console.error('SMTP Test AJAX Error:', xhr);
    },

    onLoadStart: function () {
        if (this.messageWrap) {
            this.messageWrap.classList.add('loading');
        }
    },

    onLoadEnd: function () {
        this.setTestingState(false);

        if (this.messageWrap) {
            this.messageWrap.classList.remove('loading');
        }
    },

    setTestingState: function (testing) {
        this.isTesting = testing;

        if (this.testButton) {
            this.testButton.disabled = testing;

            if (testing) {
                this.testButton.classList.add('ui-btn-wait');
            } else {
                this.testButton.classList.remove('ui-btn-wait');
            }
        }
    },

    showMessage: function (type, message) {
        if (!this.messageWrap) return;

        this.hideAllMessages();

        let html = '';
        let targetElement = null;

        switch (type) {
            case 'loading':
                html = this.params.messageLoadingText;
                targetElement = this.messageLoading;
                break;

            case 'success':
                html = this.params.messageSuccessText;
                if (message) {
                    html += BX.util.htmlspecialchars(message);
                }
                targetElement = this.messageOk;
                break;

            case 'error':
                html = this.params.messageErrorText;
                if (message) {
                    html += BX.util.htmlspecialchars(message);
                }
                targetElement = this.messageError;
                break;

            case 'ajax_error':
                html = this.params.messageAjaxErrorText;
                targetElement = this.messageError;
                break;
        }

        // Если есть специальный элемент для этого типа сообщения
        if (targetElement) {
            targetElement.innerHTML = html;
            BX.show(targetElement);
        } else {
            // Иначе выводим в общий контейнер
            let className = 'smtp-' + type;
            let color = type === 'success' ? 'green' : 'red';

            if (type === 'loading') {
                className = 'smtp-loading';
                color = '#666';
            }

            this.messageWrap.innerHTML = '<span class="' + className + '" style="color: ' + color + ';">' + html + '</span>';
        }
    },

    hideAllMessages: function () {
        if (this.messageOk) BX.hide(this.messageOk);
        if (this.messageError) BX.hide(this.messageError);
        if (this.messageLoading) BX.hide(this.messageLoading);
    },

    destroy: function () {
        if (this.testButton) {
            BX.unbind(this.testButton, 'click', BX.proxy(this.onTestClick, this));
        }

        this.testButton = null;
        this.messageWrap = null;
        this.messageOk = null;
        this.messageError = null;
        this.messageLoading = null;
        this.isTesting = false;
    },

    // Публичные методы
    test: function () {
        if (!this.isTesting) {
            this.startTest();
        }
    },

    isInProgress: function () {
        return this.isTesting;
    }
};