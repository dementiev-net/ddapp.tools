/**
 * 2Dapp Tools Images Manager
 * @version 1.0.0
 */

BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.ImagesManager = function (params) {

    this.params = params || {};
    this.uploadButton = null;
    this.messageWrap = null;
    this.messageOk = null;
    this.messageProgressA = null;
    this.messageProgressB = null;
    this.messageProgressC = null;
    this.messageError = null;
    this.messageFile = null;

    this.isUploading = false;
    this.progressSize = 500;
    this.uploaded = 0;
    this.total = 0;
    this.errorsCount = 0;
    this.currentStep = 0;

    this.sessid = BX.bitrix_sessid();
    this.init();
};

BX.DDAPP.Tools.ImagesManager.prototype = {

    init: function () {

        console.log('ImagesManager: Params', this.params);

        this.initElements();
        this.bindEvents();
        this.initBeforeUnload();
    },

    initElements: function () {
        this.uploadButton = BX(this.params.buttonId) || document.getElementById(this.params.buttonSelector || 'btn_upload');
        this.messageWrap = BX(this.params.messageWrapId || 'upload_message');
        this.messageOk = BX(this.params.messageOkId || 'upload_message_ok');
        this.messageProgressA = BX(this.params.messageProgressAId || 'progress_percent_a');
        this.messageProgressB = BX(this.params.messageProgressAId || 'progress_percent_b');
        this.messageProgressC = BX(this.params.messageProgressAId || 'progress_percent_c');
        this.messageError = BX(this.params.messageErrorId || 'upload_message_error');
        this.messageFile = BX(this.params.messageFileId || 'upload_message_file');

        if (!this.uploadButton) {
            console.warn('ImagesManager: Upload button not found');
            return false;
        }

        if (!this.messageWrap) {
            console.warn('ImagesManager: Message elements not found');
            return false;
        }

        return true;
    },

    bindEvents: function () {
        if (!this.uploadButton) {
            return;
        }

        BX.bind(this.uploadButton, 'click', BX.proxy(this.onUploadClick, this));
    },

    initBeforeUnload: function () {
        BX.bind(window, 'beforeunload', BX.proxy(this.onBeforeUnload, this));
    },

    onUploadClick: function (e) {
        e.preventDefault();
        var uploadIdInput = document.querySelector('[name="profile_id"]');

        if (!uploadIdInput) {
            return;
        }
        if (!uploadIdInput.value) {
            return;
        }

        this.startUpload();
    },

    onBeforeUnload: function (e) {
        if (this.isUploading) {
            e.preventDefault();
            e.returnValue = this.params.messageBeforeUnload;
            return e.returnValue;
        }
    },

    startUpload: function () {
        this.isUploading = true;
        this.uploaded = 0;
        this.total = 0;
        this.errorsCount = 0;
        this.currentStep = 0;

        this.setButtonState(true);
        this.showProgress();
        this.resetMessages();
        this.uploadStep(0);
    },

    uploadStep: function (step) {
        var self = this;
        var data = this.getAjaxData(step) || {};

        data.sessid = this.sessid;

        console.log('ImagesManager: AJAX', data);

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
            totalUploaded: this.uploaded,
            uploadId: this.getUploadId(),
            sessid: BX.bitrix_sessid(),
            action: this.params.action || 'upload'
        };
    },

    getUploadId: function () {
        if (this.params.uploadId) return this.params.uploadId;
        if (this.uploadButton && this.uploadButton.dataset.uploadId) {
            return this.uploadButton.dataset.uploadId;
        }

        var uploadIdInput = document.querySelector('[name="profile_id"]');

        if (uploadIdInput) {
            return uploadIdInput.value;
        }

        return 0; // fallback
    },

    handleResponse: function (response, currentStep) {
        if (!response) {
            this.finishUpload(this.params.messageWrongServerResponse);
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
                this.finishUpload(response.message || this.params.messageUnknownError);
                break;
            default:
                this.finishUpload(this.params.messageUnknownStatus + ': ' + response.status);
        }
    },

    handleProcessing: function (response, currentStep) {
        this.updateCounters(response);
        this.updateProgress();

        var self = this;
        var delay = this.params.stepDelay || 100;

        setTimeout(function () {
            self.uploadStep(currentStep + 1);
        }, delay);
    },

    handleDone: function (response) {
        this.updateCounters(response);
        this.updateProgress();
        this.showFileLink(response);
        this.finishUpload();
    },

    handleFailure: function (xhr) {

        console.error('ImagesManager: AJAX Error', xhr);

        this.finishUpload(this.params.messageErrorServerConnect + ' (HTTP ' + (xhr.status || 'unknown') + ')');
    },

    updateCounters: function (response) {
        this.uploaded = parseInt(response.uploaded) || 0;
        this.total = parseInt(response.total) || 0;
        this.errorsCount = parseInt(response.errorsCount) || 0;
    },

    updateProgress: function () {
        var progressText = this.uploaded + ' ' + this.params.messageFrom + ' ' + this.total;

        if (this.total > 0) {
            var percent = Math.round((this.uploaded / this.total) * 100);
            this.messageProgressA.style.width = this.calculatePercent(percent) + 'px';
            this.messageProgressB.textContent = percent + '%';
            this.messageProgressC.textContent = percent + '%';
        }

        this.messageOk.textContent = progressText;
        this.messageError.textContent = this.errorsCount.toString();
    },

    showFileLink: function (response) {
        if (!response.fileUrl) return;

        var fileName = response.fileName || response.fileUrl.split('/').pop();
        var linkHtml = this.params.messageFile + ': <a href="' +
            BX.util.htmlspecialchars(response.fileUrl) + '" target="_blank">' +
            BX.util.htmlspecialchars(fileName) + '</a>';

        this.messageFile.innerHTML = linkHtml;
    },

    finishUpload: function (errorMessage) {
        this.isUploading = false;
        this.setButtonState(false);

        if (errorMessage) {
            this.showError(errorMessage);
        }

        this.onUploadComplete();
    },

    setButtonState: function (isLoading) {
        if (!this.uploadButton) return;

        this.uploadButton.disabled = isLoading;

        if (isLoading) {
            this.uploadButton.classList.add('ui-btn-wait');
        } else {
            this.uploadButton.classList.remove('ui-btn-wait');
        }
    },

    showProgress: function () {
        if (this.messageWrap) {
            this.messageWrap.style.display = 'block';
        }
    },

    resetMessages: function () {
        if (this.messageOk) {
            this.messageOk.textContent = '0 ' + this.params.messageFrom + ' 0';
        }
        if (this.messageProgressA) {
            this.messageProgressA.style.width = '0px';
        }
        if (this.messageProgressB) {
            this.messageProgressB.textContent = '0%';
        }
        if (this.messageProgressC) {
            this.messageProgressC.textContent = '0%';
        }
        if (this.messageError) {
            this.messageError.textContent = '0';
            this.messageError.style.color = '';
        }
        if (this.messageFile) {
            this.messageFile.innerHTML = '';
        }
    },

    showError: function (message) {
        if (this.messageError) {
            this.messageError.textContent = message;
            this.messageError.style.fontWeight = 'normal';
            this.messageError.style.color = 'red';
        }
    },

    onUploadComplete: function () {
        console.log('ImagesManager: Upload completed - Uploaded:', this.uploaded, 'Total:', this.total, 'Errors:', this.errorsCount);
    },

    // Публичные методы для управления экспортом
    start: function () {
        if (!this.isUploading) {
            this.startUpload();
        }
    },

    isRunning: function () {
        return this.isUploading;
    },

    getProgress: function () {
        return {
            uploaded: this.uploaded,
            total: this.total,
            errorsCount: this.errorsCount,
            percent: this.total > 0 ? Math.round((this.uploaded / this.total) * 100) : 0
        };
    },

    calculatePercent: function (percent) {
        return Math.round((this.progressSize * percent) / 100);
    }
};