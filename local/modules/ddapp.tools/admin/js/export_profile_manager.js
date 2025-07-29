/**
 * 2Dapp Tools Export Profile Manager
 * @version 1.0.0
 */

BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.ExportProfileManager = function (params) {

    this.params = params || {};
    this.currentProfileId = null;
    this.availableFields = [];
    this.ajaxUrl = params && params.ajaxUrl ? params.ajaxUrl : window.location.href;
    this.sessid = BX.bitrix_sessid();

    this.init();
};

BX.DDAPP.Tools.ExportProfileManager.prototype = {

    init: function () {

        console.log('ExportProfileManager: Params', this.params);

        this.toggleButtons(false);
        this.bindEvents();
        this.loadProfiles('');
        this.loadIblockTypes();
    },

    bindEvents: function () {
        var self = this;

        // Выбор профиля
        var profileSelect = document.getElementById('profile_select');
        if (profileSelect) {
            BX.bind(profileSelect, 'change', function () {
                if (this.value) {
                    self.loadProfile(this.value);
                } else {
                    self.resetForm();
                    self.toggleSettings(false);
                    self.toggleFieldsSelection(false);
                    self.hideExportSettings();
                }
            });
        } else {
            console.warn('ExportProfileManager: Select profiles not found');
        }

        // Создание нового профиля
        var createBtn = document.getElementById('create_profile_btn');
        if (createBtn) {
            BX.bind(createBtn, 'click', function () {
                self.createNewProfile();
            });
        }

        // Удаление профиля
        var deleteBtn = document.getElementById('delete_profile_btn');
        if (deleteBtn) {
            BX.bind(deleteBtn, 'click', function () {
                self.deleteProfile();
            });
        }

        // Изменение типа инфоблока
        var iblockTypeSelect = document.getElementById('iblock_type_select');
        if (iblockTypeSelect) {
            BX.bind(iblockTypeSelect, 'change', function () {
                self.loadIblocks(this.value);
            });
        }

        // Изменение инфоблока
        var iblockSelect = document.getElementById('iblock_select');
        if (iblockSelect) {
            BX.bind(iblockSelect, 'change', function () {
                if (this.value) {
                    self.loadIblockFields(this.value);
                } else {
                    self.toggleFieldsSelection(false);
                }
            });
        }

        // Изменение типа экспорта
        var exportTypeSelect = document.getElementById('export_type_select');
        if (exportTypeSelect) {
            BX.bind(exportTypeSelect, 'change', function () {
                self.showExportSettings(this.value);
            });
        }

        // Выбор всех полей
        var selectAllBtn = document.getElementById('select_all_fields');
        if (selectAllBtn) {
            BX.bind(selectAllBtn, 'click', function () {
                self.selectAllFields(true);
            });
        }

        // Снятие всех полей
        var deselectAllBtn = document.getElementById('deselect_all_fields');
        if (deselectAllBtn) {
            BX.bind(deselectAllBtn, 'click', function () {
                self.selectAllFields(false);
            });
        }

        // Сохранение формы
        var form = document.getElementById('data_export_form');
        if (form) {
            BX.bind(form, 'submit', function (e) {
                BX.PreventDefault(e);
                self.saveProfile();
            });
        }

        // Отмена
        var cancelBtn = document.getElementById('cancel_btn');
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
            profileSelect.innerHTML = '<option value="">' + BX.message('DDAPP_EXPORT_SETTINGS_PROFILE_SELECT') + '</option>';

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
            self.showError(BX.message('DDAPP_EXPORT_MESSAGE_PROFILE_ERROR'));
            console.error('ExportProfileManager: Load Profile Error', error);
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
                            // Показываем настройки экспорта
                            if (profile.EXPORT_TYPE) {
                                self.showExportSettings(profile.EXPORT_TYPE);
                                self.fillExportSettings(profile.SETTINGS);
                            }
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
            self.showError(BX.message('DDAPP_EXPORT_MESSAGE_PROFILE_ERROR'));
            console.error('ExportProfileManager: Load Profile Error', error);
        });
    },

    deleteProfile: function () {
        var self = this;
        var profileSelect = document.getElementById('profile_select');
        if (!profileSelect) return;

        var profileId = profileSelect.value;

        if (!profileId) {
            this.showAlert(BX.message('DDAPP_EXPORT_MESSAGE_PROFILE_SELECT_ERROR'));
            return;
        }

        const messageBox = BX.UI.Dialogs.MessageBox.create({
                message: BX.message('DDAPP_EXPORT_MESSAGE_BEFORE_DELETE'),
                title: BX.message('DDAPP_EXPORT_MESSAGE_TITLE'),
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
            self.loadProfiles('');
            self.toggleSettings(false);
            self.resetForm();
            self.showAlert(BX.message('DDAPP_EXPORT_MESSAGE_PROFILE_DELETE'));
        }, function (error) {
            BX.closeWait();
            self.showError(BX.message('DDAPP_EXPORT_MESSAGE_PROFILE_DELETE_ERROR'));
            console.error('ExportProfileManager: Delete Profile Error', error);
        });
    },

    loadIblockTypes: function () {
        var self = this;

        this.makeRequest('get_iblock_types', {}, function (response) {
            var select = document.getElementById('iblock_type_select');
            if (!select) return;

            select.innerHTML = '<option value="">' + BX.message('DDAPP_EXPORT_SETTINGS_IBLOCK_TYPE_SELECT') + '</option>';

            if (response.data && response.data.length) {
                response.data.forEach(function (type) {
                    var option = document.createElement('option');
                    option.value = type.ID;
                    option.textContent = type.NAME || type.ID;
                    select.appendChild(option);
                });
            }
        }, function (error) {
            self.showError(BX.message('DDAPP_EXPORT_MESSAGE_IBLOCK_TYPE_ERROR'));
            console.error('ExportProfileManager: Load Profile Type Error', error);
        });
    },

    loadIblocks: function (typeId, callback) {
        var self = this;
        var select = document.getElementById('iblock_select');
        if (!select) return;

        if (!typeId) {
            select.innerHTML = '<option value="">' + BX.message('DDAPP_EXPORT_SETTINGS_IBLOCK_SELECT_FIRST') + '</option>';
            select.disabled = true;
            this.toggleFieldsSelection(false);
            return;
        }

        this.makeRequest('get_iblocks', {type_id: typeId}, function (response) {
            select.innerHTML = '<option value="">' + BX.message('DDAPP_EXPORT_SETTINGS_IBLOCK_SELECT') + '</option>';

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
            console.error('ExportProfileManager: Load Profile Error', error);
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
            console.error('ExportProfileManager: Load Iblock Fields Error', error);
        });
    },

    saveProfile: function () {
        var self = this;
        var form = document.getElementById('data_export_form');
        if (!form) return;

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
        var exportFields = [];

        // Получаем поля в порядке их расположения на форме
        var fieldsContainer = document.getElementById('fields_container');
        if (fieldsContainer) {
            var fieldItems = fieldsContainer.querySelectorAll('.field-item');

            fieldItems.forEach(function (fieldItem) {
                var checkbox = fieldItem.querySelector('input[type="checkbox"]');
                if (checkbox && checkbox.checked) {
                    exportFields.push(checkbox.value);
                }
            });
        }

        // Собираем остальные настройки
        for (var pair of formData.entries()) {
            var key = pair[0];
            var value = pair[1];

            if (key.indexOf('settings[') === 0 && key !== 'settings[export_fields][]') {
                var settingKey = key.match(/settings\[(.+)\]/)[1];
                settings[settingKey] = value;
            }
        }

        // Добавляем выбранные поля в правильном порядке
        if (exportFields.length > 0) {
            settings.export_fields = exportFields;
        }

        // Обрабатываем чекбоксы
        var checkboxes = form.querySelectorAll('input[type="checkbox"][name^="settings["]');
        checkboxes.forEach(function (checkbox) {
            if (checkbox.name !== 'settings[export_fields][]') {
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
            self.showError(BX.message('DDAPP_EXPORT_MESSAGE_PROFILE_SAVE_ERROR'));
            console.error('ExportProfileManager: Save Profile Error', error);
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
                        console.error('ExportProfileManager: Connect Error', result);
                        errorCallback(result && result.message ? result.message : BX.message('DDAPP_EXPORT_MESSAGE_ERROR_SERVER_CONNECT'));
                    }
                }
            },
            onfailure: function (error) {
                if (errorCallback) {
                    console.error('ExportProfileManager: Connect Error', error);
                    errorCallback(BX.message('DDAPP_EXPORT_MESSAGE_ERROR_SERVER_CONNECT'));
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
            'FIELD': BX.message('DDAPP_EXPORT_SETTINGS_IBLOCK_FIELD'),
            'PROPERTY': BX.message('DDAPP_EXPORT_SETTINGS_IBLOCK_PROPERTY')
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
                fieldDiv.setAttribute('draggable', 'true');
                fieldDiv.setAttribute('data-field-code', field.CODE);

                // Иконка перетаскивания
                var dragHandle = document.createElement('span');
                dragHandle.className = 'drag-handle';
                dragHandle.innerHTML = '⋮⋮';

                var checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'settings[export_fields][]';
                checkbox.value = field.CODE;
                checkbox.id = 'field_' + field.CODE;
                checkbox.className = 'field-checkbox';

                var label = document.createElement('label');
                label.setAttribute('for', 'field_' + field.CODE);
                label.textContent = field.NAME + ' [' + field.CODE + ']';
                label.className = 'field-label';

                fieldDiv.appendChild(dragHandle);
                fieldDiv.appendChild(checkbox);
                fieldDiv.appendChild(label);

                sortableContainer.appendChild(fieldDiv);
            });

            groupDiv.appendChild(sortableContainer);
            container.appendChild(groupDiv);
        });

        // Инициализируем drag & drop для всех групп
        this.initDragAndDrop();
    },

    initDragAndDrop: function () {
        var containers = document.querySelectorAll('.sortable-container');
        var self = this;

        containers.forEach(function (container) {
            self.makeSortable(container);
        });
    },

    makeSortable: function (container) {
        var self = this;
        var draggedElement = null;
        var placeholder = null;

        container.addEventListener('dragstart', function (e) {
            var target = e.target.closest('.field-item');
            if (!target) return;

            draggedElement = target;
            target.classList.add('dragging');

            // Создаем placeholder
            placeholder = document.createElement('div');
            placeholder.className = 'field-item';
            placeholder.style.height = target.offsetHeight + 'px';
            placeholder.style.background = '#e3f2fd';
            placeholder.style.border = '1px dashed #2196f3';
            placeholder.style.opacity = '0.5';
            placeholder.innerHTML = '<span style="color: #2196f3; text-align: center; display: block;"></span>';

            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', target.outerHTML);
            }
        });

        container.addEventListener('dragend', function (e) {
            if (draggedElement) {
                draggedElement.classList.remove('dragging');
                draggedElement = null;
            }
            if (placeholder && placeholder.parentNode) {
                placeholder.parentNode.removeChild(placeholder);
                placeholder = null;
            }
        });

        container.addEventListener('dragover', function (e) {
            e.preventDefault();

            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = 'move';
            }

            if (!draggedElement || !placeholder) return;

            var afterElement = self.getDragAfterElement(container, e.clientY);

            if (afterElement == null) {
                container.appendChild(placeholder);
            } else {
                container.insertBefore(placeholder, afterElement);
            }
        });

        container.addEventListener('drop', function (e) {
            e.preventDefault();

            if (!draggedElement || !placeholder) return;

            // Заменяем placeholder на перетаскиваемый элемент
            placeholder.parentNode.replaceChild(draggedElement, placeholder);

            // Обновляем порядок полей в настройках
            self.updateFieldsOrder();
        });
    },

    getDragAfterElement: function (container, y) {
        var draggableElements = Array.from(container.querySelectorAll('.field-item:not(.dragging)'));

        return draggableElements.reduce(function (closest, child) {
            var box = child.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return {offset: offset, element: child};
            } else {
                return closest;
            }
        }, {offset: Number.NEGATIVE_INFINITY}).element;
    },

    updateFieldsOrder: function () {
        var fieldsContainer = document.getElementById('fields_container');
        if (!fieldsContainer) return;

        var allFields = fieldsContainer.querySelectorAll('.field-item');
        var orderedFields = [];

        allFields.forEach(function (fieldElement, index) {
            var fieldCode = fieldElement.getAttribute('data-field-code');
            var checkbox = fieldElement.querySelector('input[type="checkbox"]');

            if (checkbox && fieldCode) {
                checkbox.setAttribute('data-sort-order', index);
                orderedFields.push({
                    code: fieldCode,
                    order: index,
                    selected: checkbox.checked
                });
            }
        });

        this.fieldsOrder = orderedFields;
    },

    toggleFieldsSelection: function (show) {
        var elements = document.querySelectorAll('.fields-selection');
        elements.forEach(function (el) {
            el.style.display = show ? 'table-row' : 'none';
        });
    },

    selectAllFields: function (select) {
        var checkboxes = document.querySelectorAll('input[name="settings[export_fields][]"]');
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = select;
        });
    },

    showExportSettings: function (exportType) {
        this.hideExportSettings();

        var settingsMap = {
            xls: '.excel-settings',
            csv: '.csv-settings'
        };

        var selector = settingsMap[exportType];
        if (selector) {
            var elements = document.querySelectorAll(selector);
            elements.forEach(function (el) {
                el.style.display = 'table-row';
            });
        }
    },

    hideExportSettings: function () {
        ['.excel-settings', '.csv-settings'].forEach(function (selector) {
            var elements = document.querySelectorAll(selector);
            elements.forEach(function (el) {
                el.style.display = 'none';
            });
        });
    },

    fillForm: function (profile) {
        var profileIdInput = document.getElementById('profile_id');
        if (profileIdInput) profileIdInput.value = profile.ID;

        var profileNameInput = document.getElementById('profile_name');
        if (profileNameInput) profileNameInput.value = profile.NAME;

        var iblockTypeSelect = document.getElementById('iblock_type_select');
        if (iblockTypeSelect) iblockTypeSelect.value = profile.IBLOCK_TYPE_ID || '';

        var exportTypeSelect = document.getElementById('export_type_select');
        if (exportTypeSelect) exportTypeSelect.value = profile.EXPORT_TYPE || '';
    },

    fillExportSettings: function (settingsJson) {
        if (!settingsJson) return;

        try {
            var settings = JSON.parse(settingsJson);

            console.log('ExportProfileManager: Profile settings', settings);

            // Если результат парсинга - строка, парсим еще раз
            if (typeof settings === 'string') {
                settings = JSON.parse(settings);
            }

            var self = this;

            // Теперь должен быть объект
            if (typeof settings === 'object' && !Array.isArray(settings)) {
                Object.keys(settings).forEach(function (key) {
                    if (key === 'export_fields' && Array.isArray(settings[key])) {
                        self.restoreFieldsOrder(settings[key]);
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
            console.error('ExportProfileManager: Settings Parser Error', error);
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
        var form = document.getElementById('data_export_form');
        if (form && form.reset) {
            form.reset();
        }

        var profileIdInput = document.getElementById('profile_id');
        if (profileIdInput) {
            profileIdInput.value = '';
        }

        var iblockSelect = document.getElementById('iblock_select');
        if (iblockSelect) {
            iblockSelect.innerHTML = '<option value="">' + BX.message('DDAPP_EXPORT_SETTINGS_IBLOCK_SELECT_FIRST') + '</option>';
            iblockSelect.disabled = true;
        }

        this.toggleButtons(false);
        this.toggleSettings(false);
        this.toggleFieldsSelection(false);
        this.hideExportSettings();
        this.currentProfileId = null;
        this.availableFields = [];
    },

    restoreFieldsOrder: function (savedFields) {
        if (!savedFields || !Array.isArray(savedFields)) return;

        var self = this;

        // Отмечаем выбранные поля
        savedFields.forEach(function (fieldCode) {
            var checkbox = document.querySelector('input[value="' + fieldCode + '"]');
            if (checkbox) {
                checkbox.checked = true;
            }
        });

        // Переупорядочиваем элементы согласно сохраненному порядку
        var containers = document.querySelectorAll('.sortable-container');

        containers.forEach(function (container) {
            var fieldItems = Array.from(container.querySelectorAll('.field-item'));
            var sortedItems = [];

            // Сначала добавляем поля в сохраненном порядке
            savedFields.forEach(function (fieldCode) {
                var fieldItem = fieldItems.find(function (item) {
                    return item.getAttribute('data-field-code') === fieldCode;
                });
                if (fieldItem) {
                    sortedItems.push(fieldItem);
                }
            });

            // Затем добавляем остальные поля
            fieldItems.forEach(function (item) {
                if (sortedItems.indexOf(item) === -1) {
                    sortedItems.push(item);
                }
            });

            // Переставляем элементы в DOM
            sortedItems.forEach(function (item) {
                container.appendChild(item);
            });
        });
    },

    showAlert: function (message) {
        BX.UI.Dialogs.MessageBox.alert(message, BX.message('DDAPP_EXPORT_MESSAGE_TITLE'));
    },

    showError: function (message) {
        BX.UI.Dialogs.MessageBox.alert(message, BX.message('DDAPP_EXPORT_MESSAGE_ERROR'));
    }
};