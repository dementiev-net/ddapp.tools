/**
 * DD Tools Export Manager
 * @version 1.0.0
 */

BX.namespace('BX.DD.Tools');

BX.DD.Tools.ExportManager = function (params) {

    this.params = params || {};
    this.exportButton = null;
    this.messageWrap = null;
    this.messageOk = null;
    this.messageError = null;
    this.messageFile = null;

    this.isExporting = false;
    this.exported = 0;
    this.total = 0;
    this.errorsCount = 0;
    this.currentStep = 0;

    this.sessid = BX.bitrix_sessid();
    this.init();
};

BX.DD.Tools.ExportManager.prototype = {

    init: function () {
        this.initElements();
        this.bindEvents();
        this.initBeforeUnload();
    },

    initElements: function () {
        this.exportButton = BX(this.params.buttonId) || document.getElementById(this.params.buttonSelector || 'btn_export');
        this.messageWrap = BX(this.params.messageWrapId || 'export_message');
        this.messageOk = BX(this.params.messageOkId || 'export_message_ok');
        this.messageError = BX(this.params.messageErrorId || 'export_message_error');
        this.messageFile = BX(this.params.messageFileId || 'export_message_file');

        if (!this.exportButton) {
            console.warn('ExportManager: Export button not found');
            return false;
        }

        if (!this.messageWrap || !this.messageOk || !this.messageError || !this.messageFile) {
            console.warn('ExportManager: Message elements not found');
            return false;
        }

        return true;
    },

    bindEvents: function () {
        if (!this.exportButton) return;

        BX.bind(this.exportButton, 'click', BX.proxy(this.onExportClick, this));
    },

    initBeforeUnload: function () {
        BX.bind(window, 'beforeunload', BX.proxy(this.onBeforeUnload, this));
    },

    onExportClick: function (e) {
        e.preventDefault();

        if (this.isExporting) {
            return;
        }

        this.startExport();
    },

    onBeforeUnload: function (e) {
        if (this.isExporting) {
            e.preventDefault();
            e.returnValue = this.params.beforeUnloadMessage || 'Экспорт еще не завершен. Вы уверены, что хотите покинуть страницу?';
            return e.returnValue;
        }
    },

    startExport: function () {
        this.isExporting = true;
        this.exported = 0;
        this.total = 0;
        this.errorsCount = 0;
        this.currentStep = 0;

        this.setButtonState(true);
        this.showProgress();
        this.resetMessages();

        this.exportStep(0);
    },

    exportStep: function (step) {
        var self = this;
        var data = this.getAjaxData(step) || {};
        data.sessid = this.sessid;

        BX.ajax({
            url: this.getAjaxUrl(),
            method: 'POST',
            dataType: 'json',
            timeout: this.params.timeout || 60,
            data: data,
            onsuccess: function (response) {
                self.handleResponse(response, step);
            },
            onfailure: function (xhr) {
                self.handleFailure(xhr);
            }
        });
    },

    getAjaxUrl: function () {
        return this.params.ajaxUrl || window.location.href;
    },

    getAjaxData: function (step) {
        return {
            step: step,
            totalExported: this.exported,
            exportId: this.getExportId(),
            sessid: BX.bitrix_sessid(),
            action: this.params.action || 'export_csv'
        };
    },

    getExportId: function () {
        if (this.params.exportId) {
            return this.params.exportId;
        }

        if (this.exportButton && this.exportButton.dataset.exportId) {
            return this.exportButton.dataset.exportId;
        }

        var exportIdInput = document.querySelector('[name="export_id"]');
        if (exportIdInput) {
            return exportIdInput.value;
        }

        return 123; // fallback
    },

    handleResponse: function (response, currentStep) {
        if (!response) {
            this.finishExport('Некорректный ответ сервера');
            return;
        }

        switch (response.status) {
            case 'processing':
                this.handleProcessing(response, currentStep);
                break;
            case 'done':
                this.handleDone(response);
                break;
            case 'error':
                this.finishExport(response.message || 'Неизвестная ошибка');
                break;
            default:
                this.finishExport('Неизвестный статус: ' + response.status);
        }
    },

    handleProcessing: function (response, currentStep) {
        this.updateCounters(response);
        this.updateProgress();

        var self = this;
        var delay = this.params.stepDelay || 100;

        setTimeout(function () {
            self.exportStep(currentStep + 1);
        }, delay);
    },

    handleDone: function (response) {
        this.updateCounters(response);
        this.updateProgress();
        this.showFileLink(response);
        this.finishExport();
    },

    handleFailure: function (xhr) {
        console.error('ExportManager AJAX Error:', xhr);
        this.finishExport('Ошибка запроса к серверу (HTTP ' + (xhr.status || 'unknown') + ')');
    },

    updateCounters: function (response) {
        this.exported = parseInt(response.exported) || 0;
        this.total = parseInt(response.total) || 0;
        this.errorsCount = parseInt(response.errorsCount) || 0;
    },

    updateProgress: function () {
        var progressText = this.exported + " из " + this.total;

        if (this.total > 0) {
            var percent = Math.round((this.exported / this.total) * 100);
            progressText += ' (' + percent + '%)';
        }

        this.messageOk.textContent = progressText;
        this.messageError.textContent = this.errorsCount.toString();
    },

    showFileLink: function (response) {
        if (!response.fileUrl) return;

        var fileName = response.fileName || response.fileUrl.split('/').pop();
        var linkHtml = 'Файл: <a href="' +
            BX.util.htmlspecialchars(response.fileUrl) +
            '" target="_blank">' +
            BX.util.htmlspecialchars(fileName) +
            '</a>';

        this.messageFile.innerHTML = linkHtml;
    },

    finishExport: function (errorMessage) {
        this.isExporting = false;
        this.setButtonState(false);

        if (errorMessage) {
            this.showError(errorMessage);
        }

        this.onExportComplete();
    },

    setButtonState: function (isLoading) {
        if (!this.exportButton) return;

        this.exportButton.disabled = isLoading;

        if (isLoading) {
            this.exportButton.classList.add('ui-btn-wait');
        } else {
            this.exportButton.classList.remove('ui-btn-wait');
        }
    },

    showProgress: function () {
        if (this.messageWrap) {
            this.messageWrap.style.display = 'block';
        }
    },

    resetMessages: function () {
        if (this.messageOk) {
            this.messageOk.textContent = "0 из 0";
        }
        if (this.messageError) {
            this.messageError.textContent = "0";
            this.messageError.style.color = '';
        }
        if (this.messageFile) {
            this.messageFile.innerHTML = "";
        }
    },

    showError: function (message) {
        if (this.messageError) {
            this.messageError.textContent = message;
            this.messageError.style.color = 'red';
        }
    },

    onExportComplete: function () {
        console.log('ExportManager: Export completed. Exported:', this.exported, 'Total:', this.total, 'Errors:', this.errorsCount);
    },

    // Публичные методы для управления экспортом
    start: function () {
        if (!this.isExporting) {
            this.startExport();
        }
    },

    isRunning: function () {
        return this.isExporting;
    },

    getProgress: function () {
        return {
            exported: this.exported,
            total: this.total,
            errorsCount: this.errorsCount,
            percent: this.total > 0 ? Math.round((this.exported / this.total) * 100) : 0
        };
    }
};