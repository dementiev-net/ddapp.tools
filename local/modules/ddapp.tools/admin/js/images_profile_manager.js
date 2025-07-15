/**
 * 2Dapp Tools Images Profile Manager
 * @version 1.0.0
 */

BX.namespace('BX.DDAPP.Tools');

BX.DDAPP.Tools.ImagesProfileManager = function (params) {

    this.params = params || {};
    this.currentProfileId = null;
    this.availableFields = [];
    this.ajaxUrl = params && params.ajaxUrl ? params.ajaxUrl : window.location.href;
    this.sessid = BX.bitrix_sessid();

    this.init();
};

BX.DDAPP.Tools.ImagesProfileManager.prototype = {

    init: function () {

        console.log('ImagesProfileManager: Params', this.params);

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
            console.warn('ImagesProfileManager: Select profiles not found');
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

        // Сохранение формы
        var form = document.getElementById("data_images_form");
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
            console.error('ImagesProfileManager Load Profile Error:', error);
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
                            self.fillImagesSettings(profile.SETTINGS);
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
            console.error('ImagesProfileManager Load Profile Error:', error);
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
            console.error('ImagesProfileManager Delete Profile Error:', error);
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
            console.error('ImagesProfileManager Load Profile Type Error:', error);
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
            console.error('ImagesProfileManager Load Profile Error:', error);
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
            console.error('ImagesProfileManager Load Iblock Fields Error:', error);
        });
    },

    saveProfile: function () {
        var self = this;
        var form = document.getElementById('data_images_form');
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

        for (var pair of formData.entries()) {
            var key = pair[0];
            var value = pair[1];

            if (key.indexOf('settings[') === 0) {
                var settingKey = key.match(/settings\[(.+)\]/)[1];
                settings[settingKey] = value;
            }
        }

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
            console.error('ImagesProfileManager Save Profile Error:', error);
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
                        console.error('ImagesProfileManager Connect Error', result);
                        errorCallback(result && result.message ? result.message : this.params.messageErrorServerConnect);
                    }
                }
            },
            onfailure: function (error) {
                if (errorCallback) {
                    console.error('ImagesProfileManager Connect Error:', error);
                    errorCallback(this.params.messageErrorServerConnect);
                }
            }
        });
    },

    renderFieldsSelection: function () {
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

            var select = document.getElementById('images_field');
            if (!select) return;

            groupFields.forEach(function (field) {
                var option = document.createElement('option');
                option.value = field.CODE;
                option.textContent = field.NAME + ' [' + field.CODE + ']';
                select.appendChild(option);
            });
        });
    },

    toggleFieldsSelection: function (show) {
        var elements = document.querySelectorAll('.fields-selection');
        elements.forEach(function (el) {
            el.style.display = show ? 'table-row' : 'none';
        });
    },

    fillForm: function (profile) {
        var profileIdInput = document.getElementById('profile_id');
        if (profileIdInput) profileIdInput.value = profile.ID;

        var profileNameInput = document.getElementById('profile_name');
        if (profileNameInput) profileNameInput.value = profile.NAME;

        var iblockTypeSelect = document.getElementById('iblock_type_select');
        if (iblockTypeSelect) iblockTypeSelect.value = profile.IBLOCK_TYPE_ID || '';

        var zipFileInput = document.getElementById('zip_file');
        if (zipFileInput) zipFileInput.value = profile.ZIP_FILE || '';
    },

    fillImagesSettings: function (settingsJson) {
        if (!settingsJson) return;

        try {
            var settings = JSON.parse(settingsJson);

            console.log("ImagesProfileManager: Profile settings:", settings);

            // Если результат парсинга - строка, парсим еще раз
            if (typeof settings === 'string') {
                settings = JSON.parse(settings);
            }

            var self = this;

            // Теперь должен быть объект
            if (typeof settings === 'object' && !Array.isArray(settings)) {
                Object.keys(settings).forEach(function (key) {
                    var input = document.querySelector('[name="settings[' + key + ']"]');
                    if (input) {
                        input.value = settings[key];
                    }
                });
            }
        } catch (error) {
            console.error('ImagesProfileManager Settings Parser Error:', error);
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
        var form = document.getElementById('data_images_form');
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

    showAlert: function (message) {
        BX.UI.Dialogs.MessageBox.alert(message, this.params.messageTitle);
    },

    showError: function (message) {
        BX.UI.Dialogs.MessageBox.alert(message, this.params.messageError);
    }
};