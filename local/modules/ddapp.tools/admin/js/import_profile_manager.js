/**
 * 2Dapp Tools Import Profile Manager
 * @version 1.0.0
 */

BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.ImportProfileManager = function (params) {

    this.params = params || {};
    this.currentProfileId = null;
    this.availableFields = [];
    this.ajaxUrl = params && params.ajaxUrl ? params.ajaxUrl : window.location.href;
    this.sessid = BX.bitrix_sessid();

    this.init();
};

BX.DDAPP.Tools.ImportProfileManager.prototype = {

    init: function () {

        console.log('ImportProfileManager: Params', this.params);

        this.toggleButtons(false);
        this.bindEvents();
        this.loadProfiles("");
        this.loadIblockTypes();
    },

    bindEvents: function () {
        var self = this;

        // Выбор профиля
        var profileSelect = document.getElementById("profile_select");
        if (profileSelect) {
            BX.bind(profileSelect, 'change', function () {
                if (this.value) {
                    self.loadProfile(this.value);
                } else {
                    self.resetForm();
                    self.toggleSettings(false);
                    self.toggleFieldsSelection(false);
                }
            });
        } else {
            console.warn('ImportProfileManager: Select profiles not found');
        }

        // Создание нового профиля
        var createBtn = document.getElementById("create_profile_btn");
        if (createBtn) {
            BX.bind(createBtn, 'click', function () {
                self.createNewProfile();
            });
        }

        // Удаление профиля
        var deleteBtn = document.getElementById("delete_profile_btn");
        if (deleteBtn) {
            BX.bind(deleteBtn, 'click', function () {
                self.deleteProfile();
            });
        }

        // Изменение типа инфоблока
        var iblockTypeSelect = document.getElementById("iblock_type_select");
        if (iblockTypeSelect) {
            BX.bind(iblockTypeSelect, 'change', function () {
                self.loadIblocks(this.value);
            });
        }

        // Изменение инфоблока
        var iblockSelect = document.getElementById("iblock_select");
        if (iblockSelect) {
            BX.bind(iblockSelect, 'change', function () {
                if (this.value) {
                    self.loadIblockFields(this.value);
                } else {
                    self.toggleFieldsSelection(false);
                }
            });
        }

        // Выбор всех полей
        var selectAllBtn = document.getElementById("select_all_fields");
        if (selectAllBtn) {
            BX.bind(selectAllBtn, 'click', function () {
                self.selectAllFields(true);
            });
        }

        // Снятие всех полей
        var deselectAllBtn = document.getElementById("deselect_all_fields");
        if (deselectAllBtn) {
            BX.bind(deselectAllBtn, 'click', function () {
                self.selectAllFields(false);
            });
        }

        // Сохранение формы
        var form = document.getElementById("data_import_form");
        if (form) {
            BX.bind(form, 'submit', function (e) {
                BX.PreventDefault(e);
                self.saveProfile();
            });
        }

        // Отмена
        var cancelBtn = document.getElementById("cancel_btn");
        if (cancelBtn) {
            BX.bind(cancelBtn, 'click', function () {
                self.toggleSettings(false);
                self.resetForm();
            });
        }
    },

    createNewProfile: function () {
        this.currentProfileId = null;
        this.resetForm();
        this.toggleButtons(true);
        this.toggleSettings(true);
    },

    loadProfiles: function (selectId) {
        var self = this;

        BX.showWait();

        this.makeRequest('get_profiles', {}, function (response) {
            BX.closeWait();

            var profileSelect = document.getElementById('profile_select');
            if (!profileSelect) return;

            // Очищаем опции кроме первой
            profileSelect.innerHTML = '<option value="">' + self.params.messageProfileSelect + '</option>';

            if (response.data && response.data.length) {
                response.data.forEach(function (profile) {
                    var option = document.createElement('option');
                    option.value = profile.ID;
                    option.textContent = profile.NAME;
                    profileSelect.appendChild(option);
                });
            }
            // Выбираем нужный
            if (selectId) {
                profileSelect.value = selectId;
            }

        }, function (error) {
            BX.closeWait();
            self.showError(self.params.messageProfileLoadError);
            console.error('ImportProfileManager Load Profile Error:', error);
        });
    },

    loadProfile: function (profileId) {
        var self = this;

        BX.showWait();

        this.makeRequest('get_profile', {profile_id: profileId}, function (response) {
            var profile = response.data;

            self.toggleButtons(true);
            self.currentProfileId = profileId;
            self.fillForm(profile);
            self.toggleSettings(true);

            // Загружаем инфоблоки если есть тип
            if (profile.IBLOCK_TYPE_ID) {
                self.loadIblocks(profile.IBLOCK_TYPE_ID, function () {
                    var iblockSelect = document.getElementById('iblock_select');
                    if (iblockSelect) {
                        iblockSelect.value = profile.IBLOCK_ID;
                    }

                    // Загружаем поля если есть инфоблок
                    if (profile.IBLOCK_ID) {
                        self.loadIblockFields(profile.IBLOCK_ID, function () {
                            // Показываем настройки импорта
                            self.fillImportSettings(profile.SETTINGS);
                            BX.closeWait();
                        });
                    } else {
                        BX.closeWait();
                    }
                });
            } else {
                BX.closeWait();
            }
        }, function (error) {
            BX.closeWait();
            self.showError(self.params.messageProfileLoadError);
            console.error('ImportProfileManager Load Profile Error:', error);
        });
    },

    deleteProfile: function () {
        var self = this;
        var profileSelect = document.getElementById('profile_select');
        if (!profileSelect) return;

        var profileId = profileSelect.value;

        if (!profileId) {
            this.showAlert(self.params.messageProfileSelectError);
            return;
        }

        const messageBox = BX.UI.Dialogs.MessageBox.create({
                message: self.params.messageBeforeDelete,
                title: self.params.messageTitle,
                buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
                onOk: function (messageBox) {
                    self.doDeleteProfile(profileId);
                    messageBox.close();
                },
            }
        );
        messageBox.show();
    },

    doDeleteProfile: function (profileId) {
        var self = this;

        BX.showWait();

        this.makeRequest('delete_profile', {profile_id: profileId}, function () {
            BX.closeWait();
            self.loadProfiles("");
            self.toggleSettings(false);
            self.resetForm();
            self.showAlert(self.params.messageProfileDeleteOk);
        }, function (error) {
            BX.closeWait();
            self.showError(self.params.messageProfileDeleteError);
            console.error('ImportProfileManager Delete Profile Error:', error);
        });
    },

    loadIblockTypes: function () {
        var self = this;

        this.makeRequest('get_iblock_types', {}, function (response) {
            var select = document.getElementById('iblock_type_select');
            if (!select) return;

            select.innerHTML = '<option value="">' + self.params.messageIblockTypeSelect + '</option>';

            if (response.data && response.data.length) {
                response.data.forEach(function (type) {
                    var option = document.createElement('option');
                    option.value = type.ID;
                    option.textContent = type.NAME || type.ID;
                    select.appendChild(option);
                });
            }
        }, function (error) {
            self.showError(self.params.messageIblockSelectError);
            console.error('ImportProfileManager Load Profile Type Error:', error);
        });
    },

    loadIblocks: function (typeId, callback) {
        var self = this;
        var select = document.getElementById('iblock_select');
        if (!select) return;

        if (!typeId) {
            select.innerHTML = '<option value="">' + self.params.messageIblockTypeSelectFirst + '</option>';
            select.disabled = true;
            this.toggleFieldsSelection(false);
            return;
        }

        this.makeRequest('get_iblocks', {type_id: typeId}, function (response) {
            select.innerHTML = '<option value="">' + self.params.messageIblockSelect + '</option>';

            if (response.data && response.data.length) {
                response.data.forEach(function (iblock) {
                    var option = document.createElement('option');
                    option.value = iblock.ID;
                    option.textContent = iblock.NAME;
                    select.appendChild(option);
                });
            }

            select.disabled = false;

            if (callback) {
                callback();
            }
        }, function (error) {
            console.error('ImportProfileManager Load Profile Error:', error);
        });
    },

    loadIblockFields: function (iblockId, callback) {
        var self = this;

        if (!iblockId) {
            this.toggleFieldsSelection(false);
            return;
        }

        this.makeRequest('get_iblock_fields', {iblock_id: iblockId}, function (response) {
            self.availableFields = response.data;
            self.renderFieldsSelection();
            self.toggleFieldsSelection(true);

            if (callback) {
                callback();
            }
        }, function (error) {
            console.error('ImportProfileManager Load Iblock Fields Error:', error);
        });
    },

    saveProfile: function () {
        var self = this;
        var form = document.getElementById('data_import_form');
        if (!form) return;

        // Проверяем, что для всех отмеченных полей указаны ячейки
        var validationError = this.validateFieldsAndCells();
        if (validationError) {
            this.showError(validationError);
            return;
        }

        var formData = new FormData(form);
        var data = {};

        // Собираем обычные поля
        for (var pair of formData.entries()) {
            var key = pair[0];
            var value = pair[1];

            if (key.indexOf('settings[') !== 0) {
                data[key] = value;
            }
        }

        // Собираем настройки
        var settings = {};
        var importFields = [];
        var importCells = {};

        // Получаем поля в порядке их расположения на форме
        var fieldsContainer = document.getElementById('fields_container');
        if (fieldsContainer) {
            var fieldItems = fieldsContainer.querySelectorAll('.field-item');

            fieldItems.forEach(function (fieldItem) {
                var checkbox = fieldItem.querySelector('input[type="checkbox"]');
                var cellInput = fieldItem.querySelector('input[type="text"]');

                if (checkbox && checkbox.checked) {
                    importFields.push(checkbox.value);
                    if (cellInput && cellInput.value) {
                        importCells[checkbox.value] = cellInput.value;
                    }
                }
            });
        }

        // Собираем остальные настройки
        for (var pair of formData.entries()) {
            var key = pair[0];
            var value = pair[1];

            if (key.indexOf('settings[') === 0 && key !== 'settings[import_fields][]') {
                var settingKey = key.match(/settings\[(.+)\]/)[1];
                settings[settingKey] = value;
            }
        }

        // Добавляем выбранные поля в правильном порядке
        if (importFields.length > 0) {
            settings.import_fields = importFields;
            settings.import_cells = importCells;
        }

        // Обрабатываем чекбоксы
        var checkboxes = form.querySelectorAll('input[type="checkbox"][name^="settings["]');
        checkboxes.forEach(function (checkbox) {
            if (checkbox.name !== 'settings[import_fields][]') {
                var settingKey = checkbox.name.match(/settings\[(.+)\]/)[1];
                if (typeof settings[settingKey] === 'undefined') {
                    settings[settingKey] = checkbox.checked ? 'Y' : 'N';
                }
            }
        });

        data.settings = settings;
        data.action = 'save_profile';

        BX.showWait();

        this.makeRequest('save_profile', data, function (response) {
            BX.closeWait();

            self.showAlert(response.message);

            if (!self.currentProfileId && response.id) {
                self.currentProfileId = response.id;
                var profileIdInput = document.getElementById('profile_id');
                if (profileIdInput) {
                    profileIdInput.value = response.id;
                }
            }

            self.loadProfiles(self.currentProfileId);

        }, function (error) {
            BX.closeWait();
            self.showError(self.params.messageProfileSaveError);
            console.error('ImportProfileManager Save Profile Error:', error);
        });
    },

    makeRequest: function (action, data, successCallback, errorCallback) {
        data = data || {};
        data.action = action;
        data.sessid = this.sessid;

        BX.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            data: data,
            dataType: 'json',
            onsuccess: function (result) {
                if (result && result.success) {
                    if (successCallback) {
                        successCallback(result);
                    }
                } else {
                    if (errorCallback) {
                        console.error('ImportProfileManager Connect Error', result);
                        errorCallback(result && result.message ? result.message : this.params.messageErrorServerConnect);
                    }
                }
            },
            onfailure: function (error) {
                if (errorCallback) {
                    console.error('ImportProfileManager Connect Error:', error);
                    errorCallback(this.params.messageErrorServerConnect);
                }
            }
        });
    },

    renderFieldsSelection: function () {
        var container = document.getElementById('fields_container');
        if (!container) return;

        container.innerHTML = '';

        // Группируем поля по типам
        var fieldGroups = {
            'FIELD': this.params.messageIblockField,
            'PROPERTY': this.params.messageIblockProperty
        };

        var self = this;

        Object.keys(fieldGroups).forEach(function (groupType) {
            var groupFields = self.availableFields.filter(function (field) {
                return field.TYPE === groupType;
            });

            if (groupFields.length === 0) return;

            // Контейнер группы
            var groupDiv = document.createElement('div');
            groupDiv.className = 'field-group';

            // Заголовок группы
            var groupTitle = document.createElement('h3');
            groupTitle.textContent = fieldGroups[groupType];
            groupDiv.appendChild(groupTitle);

            // Контейнер для сортируемых полей
            var sortableContainer = document.createElement('div');
            sortableContainer.className = 'sortable-container';
            sortableContainer.setAttribute('data-group', groupType);

            // Поля группы
            groupFields.forEach(function (field) {
                var fieldDiv = document.createElement('div');
                fieldDiv.className = 'field-item';
                fieldDiv.setAttribute('data-field-code', field.CODE);

                var checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'settings[import_fields][]';
                checkbox.value = field.CODE;
                checkbox.id = 'field_' + field.CODE;
                checkbox.className = 'field-checkbox';

                var input = document.createElement('input');
                input.type = 'text';
                input.value = '';
                input.id = 'cell_' + field.CODE;
                input.className = 'field-input';
                input.placeholder = 'A1';

                var labelCheckbox = document.createElement('label');
                labelCheckbox.setAttribute('for', 'field_' + field.CODE);
                labelCheckbox.textContent = field.NAME + ' [' + field.CODE + ']';
                labelCheckbox.className = 'field-label';
                labelCheckbox.style.flex = '1';

                var labelInput = document.createElement('label');
                labelInput.setAttribute('for', 'cell_' + field.CODE);
                labelInput.textContent = 'ячейка';
                labelInput.className = 'field-label';

                fieldDiv.appendChild(checkbox);
                fieldDiv.appendChild(labelCheckbox);
                fieldDiv.appendChild(input);
                fieldDiv.appendChild(labelInput);

                sortableContainer.appendChild(fieldDiv);
            });

            groupDiv.appendChild(sortableContainer);
            container.appendChild(groupDiv);
        });
    },

    toggleFieldsSelection: function (show) {
        var elements = document.querySelectorAll('.fields-selection');
        elements.forEach(function (el) {
            el.style.display = show ? 'table-row' : 'none';
        });
    },

    selectAllFields: function (select) {
        var checkboxes = document.querySelectorAll('input[name="settings[import_fields][]"]');
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = select;
        });
    },

    fillForm: function (profile) {
        var profileIdInput = document.getElementById('profile_id');
        if (profileIdInput) profileIdInput.value = profile.ID;

        var profileNameInput = document.getElementById('profile_name');
        if (profileNameInput) profileNameInput.value = profile.NAME;

        var iblockTypeSelect = document.getElementById('iblock_type_select');
        if (iblockTypeSelect) iblockTypeSelect.value = profile.IBLOCK_TYPE_ID || '';
    },

    fillImportSettings: function (settingsJson) {
        if (!settingsJson) return;

        try {
            var settings = JSON.parse(settingsJson);

            console.log("ImportProfileManager: Profile settings:", settings);

            // Если результат парсинга - строка, парсим еще раз
            if (typeof settings === 'string') {
                settings = JSON.parse(settings);
            }

            var self = this;

            // Теперь должен быть объект
            if (typeof settings === 'object' && !Array.isArray(settings)) {
                Object.keys(settings).forEach(function (key) {
                    if (key === 'import_fields' && Array.isArray(settings[key])) {
                        self.restoreFields(settings[key], settings.import_cells);
                    } else if (key === 'import_cells') {
                        // Обрабатывается в restoreFields
                    } else {
                        var input = document.querySelector('[name="settings[' + key + ']"]');

                        if (input) {
                            if (input.type === 'checkbox') {
                                input.checked = settings[key] === 'Y';
                            } else {
                                input.value = settings[key];
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('ImportProfileManager Settings Parser Error:', error);
        }
    },

    toggleSettings: function (show) {
        var elements = document.querySelectorAll('.profile-settings');
        elements.forEach(function (el) {
            el.style.display = show ? 'table-row' : 'none';
        });

        if (!show) {
            var profileSelect = document.getElementById('profile_select');
            if (profileSelect) {
                profileSelect.value = '';
            }
        }
    },

    toggleButtons: function (show) {
        const buttonIds = ['submit_btn', 'cancel_btn'];
        buttonIds.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.disabled = !show;
        });
    },

    resetForm: function () {
        var form = document.getElementById('data_import_form');
        if (form && form.reset) {
            form.reset();
        }

        var profileIdInput = document.getElementById('profile_id');
        if (profileIdInput) {
            profileIdInput.value = '';
        }

        var iblockSelect = document.getElementById('iblock_select');
        if (iblockSelect) {
            iblockSelect.innerHTML = '<option value="">' + this.params.messageIblockTypeSelectFirst + '</option>';
            iblockSelect.disabled = true;
        }

        this.toggleButtons(false);
        this.toggleSettings(false);
        this.toggleFieldsSelection(false);
        this.currentProfileId = null;
        this.availableFields = [];
    },

    restoreFields: function (savedFields, savedCells) {
        if (!savedFields || !Array.isArray(savedFields)) return;

        var self = this;

        // Отмечаем выбранные поля
        savedFields.forEach(function (fieldCode) {
            var checkbox = document.querySelector('input[value="' + fieldCode + '"]');
            if (checkbox) {
                checkbox.checked = true;
            }

            // Восстанавливаем значения ячеек
            if (savedCells && savedCells[fieldCode]) {
                var cellInput = document.querySelector('input[id="cell_' + fieldCode + '"]');
                if (cellInput) {
                    cellInput.value = savedCells[fieldCode];
                }
            }
        });
    },

    validateFieldsAndCells: function () {
        var fieldsContainer = document.getElementById('fields_container');
        if (!fieldsContainer) return null;

        var fieldItems = fieldsContainer.querySelectorAll('.field-item');
        var emptyFields = [];

        // Сначала убираем все предыдущие ошибки
        fieldItems.forEach(function (fieldItem) {
            var cellInput = fieldItem.querySelector('input[type="text"]');
            if (cellInput) {
                cellInput.style.border = '';
            }
        });

        fieldItems.forEach(function (fieldItem) {
            var checkbox = fieldItem.querySelector('input[type="checkbox"]');
            var cellInput = fieldItem.querySelector('input[type="text"]');

            if (checkbox && checkbox.checked) {
                if (!cellInput || !cellInput.value.trim()) {
                    var label = fieldItem.querySelector('.field-label');
                    var fieldName = label ? label.textContent : checkbox.value;
                    emptyFields.push(fieldName);

                    // Подсвечиваем поле с ошибкой
                    if (cellInput) {
                        cellInput.style.border = '1px solid red';
                    }
                }
            }
        });

        if (emptyFields.length > 0) {
            return this.params.messageIblockFieldValidationError + emptyFields.join(', ');
        }

        return null;
    },

    showAlert: function (message) {
        BX.UI.Dialogs.MessageBox.alert(message, this.params.messageTitle);
    },

    showError: function (message) {
        BX.UI.Dialogs.MessageBox.alert(message, this.params.messageError);
    }
};