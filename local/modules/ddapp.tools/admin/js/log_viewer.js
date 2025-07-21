/**
 * DDAPP Tools Log Viewer
 * @version 1.0.0
 */

BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.LogViewer = function (params) {
    this.params = params || {};
    this.ajaxUrl = params && params.ajaxUrl ? params.ajaxUrl : window.location.href;
    this.currentPage = 1;
    this.currentFilename = '';
    this.debounceTimer = null;
    this.refreshFiles = null;
    this.logFileSelect = null;
    this.clearFilters = null;
    this.clearLog = null;
    this.prevPage = null;
    this.nextPage = null;
    this.levelFilter = null;
    this.userFilter = null;
    this.dateFilter = null;
    this.searchFilter = null;
    this.totalCount = null;
    this.debugCount = null;
    this.infoCount = null;
    this.warningCount = null;
    this.errorCount = null;
    this.criticalCount = null;
    this.logEntries = null;
    this.pagination = null;
    this.paginationInfo = null;
    this.pageNumbers = null;

    this.init();
};

BX.DDAPP.Tools.LogViewer.prototype = {

    init: function () {

        console.log('LogViewer: Params', this.params);

        this.initElements();
        this.bindEvents();
        this.loadLogFiles();
    },

    initElements: function () {
        this.refreshFiles = BX('refresh-files');
        this.logFileSelect = BX('log-file-select');
        this.clearFilters = BX('clear-filters');
        this.clearLog = BX('clear-log');
        this.prevPage = BX('prev-page');
        this.nextPage = BX('next-page');
        this.levelFilter = BX('level-filter');
        this.userFilter = BX('user-filter');
        this.dateFilter = BX('date-filter');
        this.searchFilter = BX('search-filter');
        this.totalCount = BX('total-count');
        this.debugCount = BX('debug-count');
        this.infoCount = BX('info-count');
        this.warningCount = BX('warning-count');
        this.errorCount = BX('error-count');
        this.criticalCount = BX('critical-count');
        this.logEntries = BX('log-entries');
        this.pagination = BX('pagination');
        this.paginationInfo = BX('pagination-info');
        this.pageNumbers = BX('page-numbers');
    },

    bindEvents: function () {
        var self = this;

        BX.bind(this.refreshFiles, 'click', function () {
            self.loadLogFiles(this.value);
        });

        BX.bind(this.logFileSelect, 'change', function () {
            self.onSelectLogFile(this.value);
        });

        BX.bind(this.clearFilters, 'click', function () {
            self.onClearFilters();
        });

        BX.bind(this.clearLog, 'click', function () {
            self.onClearLog();
        });

        BX.bind(this.prevPage, 'click', function () {
            self.onPrevPage();
        });

        BX.bind(this.nextPage, 'click', function () {
            self.onNextPage();
        });

        // Автоматическое применение фильтров при изменении
        BX.bind(this.levelFilter, 'change', function () {
            self.applyFilters();
        });

        BX.bind(this.userFilter, 'change', function () {
            self.applyFilters();
        });

        BX.bind(this.dateFilter, 'change', function () {
            self.applyFilters();
        });

        BX.bind(this.searchFilter, 'input', function () {
            self.debounceApplyFilters();
        });
    },

    debounceApplyFilters: function () {
        var self = this;
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
        this.debounceTimer = setTimeout(function () {
            self.applyFilters();
        }, 500);
    },

    loadLogFiles: function () {
        var self = this;

        BX.showWait();

        BX.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'get_log_files',
                sessid: BX.bitrix_sessid()
            },
            onsuccess: function (response) {
                BX.closeWait();
                self.updateLogFilesSelect(response);
            },
            onfailure: function () {
                BX.closeWait();
                console.error('LogViewer: Load Files Error');
                self.showError(self.params.messageErrorLoadFile);
            }
        });
    },

    updateLogFilesSelect: function (files) {
        var select = this.logFileSelect;
        select.innerHTML = '<option value="">' + this.params.messageSelectLogFile + '</option>';

        for (var i = 0; i < files.length; i++) {
            var option = BX.create('option', {
                props: {
                    value: files[i],
                    textContent: files[i]
                }
            });
            select.appendChild(option);
        }
    },

    onSelectLogFile: function (filename) {
        this.currentFilename = filename;
        this.currentPage = 1;
        if (filename) {
            this.loadLogData();
        } else {
            this.clearTable();
        }
    },

    loadLogData: function () {
        if (!this.currentFilename) return;

        BX.showWait();

        var self = this;
        var filters = this.getFilters();

        BX.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'get_log_data',
                filename: this.currentFilename,
                filters: JSON.stringify(filters),
                page: this.currentPage,
                sessid: BX.bitrix_sessid()
            },
            onsuccess: function (response) {
                BX.closeWait();
                self.updateStats(response.stats);
                self.updateUserFilter(response.users);
                self.updateTable(response.entries);
                self.updatePagination(response.pagination);
            },
            onfailure: function () {
                BX.closeWait();
                console.error('LogViewer: Load Data Error');
                self.showError(self.params.messageErrorLoadData);
            }
        });
    },

    getFilters: function () {
        return {
            level: this.levelFilter.value,
            user: this.userFilter.value,
            date: this.dateFilter.value,
            search: this.searchFilter.value
        };
    },

    updateStats: function (stats) {
        this.totalCount.textContent = stats.total;
        this.debugCount.textContent = stats.debug;
        this.infoCount.textContent = stats.info;
        this.warningCount.textContent = stats.warning;
        this.errorCount.textContent = stats.error;
        this.criticalCount.textContent = stats.critical;
    },

    updateUserFilter: function (users) {
        var select = this.userFilter;
        var currentValue = select.value;

        select.innerHTML = '<option value="">' + this.params.messageAllUsers + '</option>';

        for (var i = 0; i < users.length; i++) {
            var option = BX.create('option', {
                props: {
                    value: users[i],
                    textContent: users[i]
                }
            });
            if (users[i] === currentValue) {
                option.selected = true;
            }
            select.appendChild(option);
        }
    },

    updateTable: function (entries) {
        var tbody = this.logEntries;

        if (entries.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="no-data">' + this.params.messageDataNotFound + '</td></tr>';
            return;
        }

        var html = '';
        for (var i = 0; i < entries.length; i++) {
            var entry = entries[i];
            var message = BX.util.htmlspecialchars(entry.message);

            message = message.replace(/\\\//g, '/');
            message = message.replace(/ \| /g, '<br>');

            html += '<tr class="log-entry" style="border-bottom: none;">' +
                '<td class="log-datetime" rowspan="2">' + BX.util.htmlspecialchars(entry.datetime) + '</td>' +
                '<td><span class="log-level level-' + entry.level + '">' + entry.level + '</span></td>' +
                '<td class="log-user">' + BX.util.htmlspecialchars(entry.user) + '</td>' +
                '<td class="log-url">' + BX.util.htmlspecialchars(entry.url) + '</td>' +
                '<td class="log-memory">' + BX.util.htmlspecialchars(entry.memory) + ' / ' + BX.util.htmlspecialchars(entry.peak) + '</td>' +
                '</tr>' +
                '<tr><td colspan="4" class="log-message">' + message + '</td></tr>';
        }
        tbody.innerHTML = html;
    },

    updatePagination: function (pagination) {
        var paginationDiv = this.pagination;

        if (pagination.pages <= 1) {
            paginationDiv.style.display = 'none';
            return;
        }

        paginationDiv.style.display = 'flex';

        this.paginationInfo.textContent =
            'Показано ' + (((pagination.current_page - 1) * 5) + 1) + '-' +
            Math.min(pagination.current_page * 5, pagination.total) + ' ' + this.params.messagePageFrom + ' ' + pagination.total;

        this.prevPage.disabled = pagination.current_page === 1;
        this.nextPage.disabled = pagination.current_page === pagination.pages;

        this.generatePageNumbers(pagination);
    },

    generatePageNumbers: function (pagination) {
        var pageNumbers = this.pageNumbers;
        var self = this;

        BX.cleanNode(pageNumbers);

        var maxPages = 5;
        var start = Math.max(1, pagination.current_page - Math.floor(maxPages / 2));
        var end = Math.min(pagination.pages, start + maxPages - 1);

        if (end - start + 1 < maxPages) {
            start = Math.max(1, end - maxPages + 1);
        }

        for (var i = start; i <= end; i++) {
            var pageBtn = BX.create('a', {
                props: {
                    href: '#',
                    className: 'page-number' + (i === pagination.current_page ? ' active' : ''),
                    textContent: i
                },
                attrs: {
                    'data-page': i
                }
            });

            BX.bind(pageBtn, 'click', function (e) {
                e.preventDefault();
                var page = parseInt(this.getAttribute('data-page'));
                self.goToPage(page);
            });

            pageNumbers.appendChild(pageBtn);
        }
    },

    clearTable: function () {
        this.logEntries.innerHTML = '<tr><td colspan="5" class="no-data">' + this.params.messageDataNotFound + '</td></tr>';
        this.pagination.style.display = 'none';
        this.updateStats({total: 0, debug: 0, info: 0, warning: 0, error: 0, critical: 0});
    },

    applyFilters: function () {
        this.currentPage = 1;
        this.loadLogData();
    },

    onClearFilters: function () {
        this.levelFilter.value = '';
        this.userFilter.value = '';
        this.dateFilter.value = '';
        this.searchFilter.value = '';
        this.applyFilters();
    },

    onClearLog: function () {
        if (!this.currentFilename) return;

        var self = this;

        const messageBox = BX.UI.Dialogs.MessageBox.create({
                message: self.params.messageBeforeDelete,
                title: self.params.messageTitle,
                buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
                onOk: function (messageBox) {
                    self.performClearLog();
                    messageBox.close();
                },
            }
        );
        messageBox.show();
    },

    performClearLog: function () {
        var self = this;

        BX.showWait();

        BX.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'clear_log',
                filename: this.currentFilename,
                sessid: BX.bitrix_sessid()
            },
            onsuccess: function (response) {
                BX.closeWait();
                if (response.success) {
                    self.loadLogData();
                    self.showAlert(self.params.messageDeleteOk);
                } else {
                    console.error('LogViewer: Error', response.message);
                    self.showError(response.message);
                }
            },
            onfailure: function () {
                BX.closeWait();
                console.error('LogViewer: Clear Log Error');
                self.showError(self.params.messageErrorLogClear);
            }
        });
    },

    onPrevPage: function () {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.loadLogData();
        }
    },

    onNextPage: function () {
        this.currentPage++;
        this.loadLogData();
    },

    goToPage: function (page) {
        this.currentPage = page;
        this.loadLogData();
    },

    showAlert: function (message) {
        BX.UI.Dialogs.MessageBox.alert(message, this.params.messageTitle);
    },

    showError: function (message) {
        tbody.innerHTML = '<tr><td colspan="5" class="no-data">' + this.params.messageDataNotFound + '</td></tr>';
        BX.UI.Dialogs.MessageBox.alert(message, this.params.messageError);
    }
};