/**
 * 2Dapp Tools Import Excel Manager
 * @version 1.0.0
 */

BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.ImportExcelManager = function (params) {

    this.params = params || {};
    this.iblockId = params.iblockId || null;
    this.settings = params.settings || {};
    this.properties = params.properties || {};
    this.currentDialog = null;

    this.init();
};

BX.DDAPP.Tools.ImportExcelManager.prototype = {

    init: function () {

        console.log('ImportExcelManager: Params', this.params);

        // Делаем функцию openExcelModal доступной глобально
        var self = this;
        window.openExcelModal = function () {
            self.openModal();
        };
    },

    openModal: function () {
        var self = this;

        this.currentDialog = new BX.CDialog({
            title: this.params.modalMessageTitle,
            content: BX('excel_import_div').innerHTML,
            width: 500,
            height: 250,
            resizable: true,
            draggable: true,
            buttons: [{
                title: this.params.modalMessageBtnClose,
                id: 'excel_import_close',
                name: 'close',
                action: function () {
                    this.parentWindow.Close();
                }
            }]
        });

        this.currentDialog.Show();

        // привязываем обработчик к новому input после открытия диалога
        setTimeout(function () {
            var fileInput = document.querySelector('.bx-core-adm-dialog input[type=file]');
            if (fileInput) {
                fileInput.addEventListener('change', function (e) {

                    // Показываем имя выбранного файла
                    var fileName = e.target.files[0] ? e.target.files[0].name : '';
                    var fileNameSpan = self.currentDialog.PARTS.CONTENT.querySelector('#file-name');

                    if (fileNameSpan) {
                        fileNameSpan.textContent = fileName ? self.params.modalMessageFile + fileName : '';
                    }

                    self.handleExcelFile(e);
                });
            }
        }, 100);
    },

    handleExcelFile: function (e) {
        var self = this;
        BX.showWait();

        var file = e.target.files[0];
        var reader = new FileReader();

        reader.onload = function (e) {
            try {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, {type: 'array'});

                // получаем первый лист
                var worksheet = workbook.Sheets[workbook.SheetNames[0]];

                // получаем настройки профиля
                if (!self.settings || !self.settings.import_fields || !self.settings.import_cells) {
                    throw new Error('Import settings not found');
                }

                var importedCount = 0;
                var errors = [];

                // проходим по всем полям из настроек
                self.settings.import_fields.forEach(function (fieldCode) {
                    var cellAddress = self.settings.import_cells[fieldCode];
                    if (!cellAddress) {
                        errors.push(self.params.messageImportCellError + fieldCode);
                        return;
                    }

                    var cellValue = worksheet[cellAddress] ? worksheet[cellAddress].v : '';

                    // определяем селектор для поля
                    var fieldSelector = self.getFieldSelector(fieldCode);
                    if (fieldSelector) {
                        var fieldElement = document.querySelector(fieldSelector);
                        if (fieldElement) {
                            fieldElement.value = self.formatCellValue(cellValue, fieldCode);
                            importedCount++;
                        } else {
                            errors.push(self.params.messageImportFieldError + fieldCode);
                        }
                    } else {
                        errors.push(self.params.messageImportSelectorError + fieldCode);
                    }
                });

                // закрываем диалог перед показом сообщения
                if (self.currentDialog) {
                    self.currentDialog.Close();
                }

                var message = self.params.messageImport + importedCount;
                if (errors.length > 0) {
                    message += '<br>' + errors.join('<br>');
                }

                self.showAlert(message);

            } catch (error) {
                // закрываем диалог при ошибке
                if (self.currentDialog) {
                    self.currentDialog.Close();
                }

                self.showError(self.params.messageFileImportError);
                console.error('ImportExcelManager File Import Error:', error.message);

            } finally {
                BX.closeWait();
            }
        };

        reader.onerror = function () {
            BX.closeWait();
            self.showError(self.params.messageFileReadtError);
            console.error('ImportExcelManager File Import Error: File Read Error');
        };

        reader.readAsArrayBuffer(file);
    },

    getFieldSelector: function (fieldCode) {
        // Сначала проверяем стандартные поля элемента
        switch (fieldCode) {
            case 'NAME':
                return 'input[name="NAME"]';
            case 'CODE':
                return 'input[name="CODE"]';
            case 'SORT':
                return 'input[name="SORT"]';
            case 'ACTIVE':
                return 'input[name="ACTIVE"]';
            case 'PREVIEW_TEXT':
                return 'textarea[name="PREVIEW_TEXT"]';
            case 'DETAIL_TEXT':
                return 'textarea[name="DETAIL_TEXT"]';
            case 'PREVIEW_PICTURE':
                return 'input[name="PREVIEW_PICTURE"]';
            case 'DETAIL_PICTURE':
                return 'input[name="DETAIL_PICTURE"]';
            default:
                // Если это не стандартное поле, то это свойство
                return this.getPropertySelector(fieldCode);
        }
    },

    getPropertySelector: function (propertyCode) {
        // Поиск элемента с [n0]
        if (this.properties && this.properties[propertyCode]) {
            var propertyId = this.properties[propertyCode];
            return 'input[name="PROP[' + propertyId + '][n0]"], textarea[name="PROP[' + propertyId + '][n0]"], select[name="PROP[' + propertyId + '][n0]"]';
        }

        // Поиск по паттерну имени свойства - извлекаем ID из name атрибута
        var propInputs = document.querySelectorAll('input[name^="PROP["], textarea[name^="PROP["], select[name^="PROP["]');
        for (var i = 0; i < propInputs.length; i++) {
            var input = propInputs[i];
            var nameMatch = input.name.match(/PROP\[(\d+)\]/);
            if (nameMatch) {
                var propertyId = nameMatch[1];
                // Проверяем, есть ли такой ID в this.properties
                for (var propCode in this.properties) {
                    if (this.properties[propCode] === propertyId && 'PROPERTY_' + propCode === propertyCode) {
                        return '[name="' + input.name + '"]';
                    }
                }
            }
        }

        // Поиск элемента с data-property-code
        var propertyElement = document.querySelector('[data-property-code="' + propertyCode + '"]');
        if (propertyElement) {
            var input = propertyElement.querySelector('input, textarea, select');
            if (input && input.name) {
                return '[name="' + input.name + '"]';
            }
        }

        return null;
    },

    formatCellValue: function (value, fieldCode) {
        if (value === null || value === undefined) {
            return '';
        }

        // Преобразуем в строку
        var stringValue = String(value);

        // Заменяем запятую на точку для числовых полей
        if (typeof value === 'number' || /^\d+[,\.]\d+$/.test(stringValue)) {
            stringValue = stringValue.replace(',', '.');
        }

        // Здесь можно добавить другие преобразования в зависимости от fieldCode
        // switch (fieldCode) {
        //     case 'RATING':
        //         // специальная обработка для рейтинга
        //         break;
        // }

        return stringValue;
    },

    showAlert: function (message) {
        BX.UI.Dialogs.MessageBox.alert(message, this.params.messageTitle);
    },

    showError: function (message) {
        BX.UI.Dialogs.MessageBox.alert(message, this.params.messageError);
    }
};