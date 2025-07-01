<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use DD\Tools\Helpers\IblockHelper;
use DD\Tools\Helpers\LogHelper;
use DD\Tools\DataExport;

Loc::loadMessages(__FILE__);

$module_id = "dd.tools";

// Получим права доступа текущего пользователя на модуль
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$btnDisabled = true;
if ($moduleAccessLevel >= "W") $btnDisabled = false;

// Настройка логирования
LogHelper::configure();

// Подключаем необходимые модули
Loader::includeModule("iblock");
Extension::load("ui.dialogs.messagebox");

$APPLICATION->SetTitle("Экспорт");

// Контекстное меню
$context = new CAdminContextMenu([
    [
        "TEXT" => "Экспортировать" . Loc::getMessage("DD_MAINT_BTN_TO_LIST"),
        "ICON" => "btn_green",
        "LINK" => "#",
        "TITLE" => "К списку записей"
    ]
]);

$request = Application::getInstance()->getContext()->getRequest();

// Обработка AJAX запросов
if ($request->isPost() && !empty($request->getPost("action"))) {

    switch ($request->getPost("action")) {

        case "get_profiles":
            $profiles = DataExport::getItems([
                "select" => ["ID", "NAME", "IBLOCK_TYPE_ID", "IBLOCK_ID", "EXPORT_TYPE", "SETTINGS"]
            ])->fetchAll();
            echo json_encode(["success" => true, "data" => $profiles]);
            exit;

        case "get_profile":
            if (!empty($request->getPost("profile_id"))) {
                $profile = DataExport::getById($request->getPost("profile_id"));
                echo json_encode(["success" => true, "data" => $profile]);
            }
            exit;

        case "save_profile":
            $fields = [
                "NAME" => $request->getPost("name"),
                "IBLOCK_TYPE_ID" => $request->getPost("iblock_type_id"),
                "IBLOCK_ID" => $request->getPost("iblock_id"),
                "EXPORT_TYPE" => $request->getPost("export_type"),
                "SETTINGS" => json_encode($request->getPost("settings"))
            ];

            if (!empty($request->getPost("profile_id"))) {
                DataExport::update($request->getPost("profile_id"), $fields);
                echo json_encode(["success" => true, "message" => "Профиль обновлен"]);
            } else {
                $result = DataExport::add($fields);
                echo json_encode(["success" => true, "message" => "Профиль создан", "id" => $result->getId()]);
            }
            exit;

        case "delete_profile":
            if (!empty($request->getPost("profile_id"))) {
                DataExport::delete($request->getPost("profile_id"));
                echo json_encode(["success" => true, "message" => "Профиль удален"]);
            }
            exit;

        case "get_iblock_types":
            $types = IblockHelper::getAllBlockType();
            echo json_encode(["success" => true, "data" => $types]);
            exit;

        case "get_iblocks":
            if (!empty($request->getPost("type_id"))) {
                $iblocks = IblockHelper::getBlocks([
                    "select" => ["ID", "NAME"],
                    "filter" => ["IBLOCK_TYPE_ID" => $request->getPost("type_id"), "ACTIVE" => "Y"]
                ]);
                echo json_encode(["success" => true, "data" => $iblocks]);
            }
            exit;

        case "get_iblock_fields":
            if (!empty($request->getPost("iblock_id"))) {
                $iblockId = intval($request->getPost("iblock_id"));
                $fields = [];

                foreach (IblockHelper::getDefaultFieldsNames() as $code => $name) {
                    $fields[] = ["CODE" => $code, "NAME" => $name, "TYPE" => "FIELD", "PROPERTY_TYPE" => "", "MULTIPLE" => "N"];
                }

                $properties = IblockHelper::getProperties([
                    "select" => ["ID", "CODE", "NAME", "PROPERTY_TYPE", "MULTIPLE", "LIST_TYPE", "USER_TYPE"],
                    "filter" => ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"],
                    "order" => ["SORT" => "ASC", "NAME" => "ASC"]
                ]);

                foreach ($properties as $property) {
                    $propertyName = $property["NAME"];

                    // Добавляем информацию о типе свойства
                    switch ($property["PROPERTY_TYPE"]) {
                        case "S":
                            $propertyName .= " (Строка)";
                            break;
                        case "N":
                            $propertyName .= " (Число)";
                            break;
                        case "L":
                            $propertyName .= " (Список)";
                            break;
                        case "F":
                            $propertyName .= " (Файл)";
                            break;
                        case "G":
                            $propertyName .= " (Привязка к разделам)";
                            break;
                        case "E":
                            $propertyName .= " (Привязка к элементам)";
                            break;
                    }

                    if ($property["MULTIPLE"] === "Y") {
                        $propertyName .= " [множественное]";
                    }

                    $fields[] = [
                        "CODE" => "PROPERTY_" . $property["CODE"],
                        "NAME" => $propertyName,
                        "TYPE" => "PROPERTY",
                        "PROPERTY_TYPE" => $property["PROPERTY_TYPE"],
                        "MULTIPLE" => $property["MULTIPLE"]
                    ];
                }
                echo json_encode(["success" => true, "data" => $fields]);
            }
            exit;
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
?>

    <div class="adm-info-message-wrap adm-info-message-gray" id="export_message">
        <div class="adm-info-message">
            <div class="adm-info-message-title">Экспорт завершен</div>
            Успешно экспортировано записей: <span id="export_message_ok">890</span>
            <br>С ошибками: <span id="export_message_error">0</span>
            <p id="export_message_file">Файл: <a href="/upload/232324-234-3.xls">/upload/232324-234-3.xls</a></p>
        </div>
    </div>

<?= $context->Show(); ?>

    <style>
        .data-export-form .profile-settings,
        .data-export-form .fields-selection,
        .data-export-form .csv-settings,
        .data-export-form .excel-settings,
        .adm-info-message-wrap {
            display: none;
        }

        .data-export-form .field-item {
            cursor: move;
            padding: 8px;
            margin: 2px 0;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            display: flex;
            align-items: center;
            transition: background-color 0.2s;
        }

        .data-export-form .field-item:hover {
            background: #e9ecef;
        }

        .data-export-form .field-item.dragging {
            opacity: 0.5;
            background: #fff3cd;
        }

        .data-export-form .field-item .drag-handle {
            margin-right: 8px;
            color: #6c757d;
            cursor: grab;
        }

        .data-export-form .field-item .drag-handle:hover {
            color: #495057;
        }

        .data-export-form .field-item .field-checkbox {
            margin-right: 8px;
        }

        .data-export-form .field-item .field-label {
            flex: 1;
            cursor: pointer;
        }

        .data-export-form .field-group {
            margin-bottom: 20px;
        }

        .data-export-form .field-group h3 {
            margin: 15px 0 10px 0 !important;
            color: #2067b0 !important;
        }

        .data-export-form .sortable-container {
            min-height: 50px;
        }
    </style>

    <form id="data-export-form" class="data-export-form">
        <input type="hidden" id="profile-id" name="profile_id">
        <?= bitrix_sessid_post() ?>

        <?php
        $tabControl = new CAdminTabControl("tabControl", [
            [
                "DIV" => "edit1",
                "TAB" => "Настройки" . Loc::getMessage("DD_MAINT_TAB1"),
                "TITLE" => "Настройки экспорта данных инфоблока" . Loc::getMessage("DD_MAINT_TAB1_TITLE")
            ]
        ]);
        $tabControl->Begin();
        ?>

        <?php $tabControl->BeginNextTab(); ?>

        <tr>
            <td width="40%" style="position: relative; top: -4px;">Профиль:</td>
            <td width="60%">
                <select id="profile-select" class="adm-input"></select>
                <span style="letter-spacing: -5px; position: relative; top: -3px;">
    					<a href="javascript:void(0)" class="adm-table-btn-edit" id="create-profile-btn"></a>
	    				<a href="javascript:void(0);" class="adm-table-btn-delete" id="delete-profile-btn"></a>
                </span>
            </td>
        </tr>

        <!-- Настройки профиля -->
        <tr class="heading profile-settings">
            <td colspan="2">Настройки</td>
        </tr>
        <tr class="profile-settings">
            <td>
                <label for="profile-name">Название профиля:</label>
            </td>
            <td>
                <input type="text" id="profile-name" name="name" class="adm-input" required>
            </td>
        </tr>
        <tr class="profile-settings">
            <td>
                <label for="iblock-type-select">Тип инфоблока:</label>
            </td>
            <td>
                <select id="iblock-type-select" name="iblock_type_id" class="adm-input">
                    <option value="">-- Выберите тип инфоблока --</option>
                </select>
            </td>
        </tr>
        <tr class="profile-settings">
            <td>
                <label for="iblock-select">Инфоблок:</label>
            </td>
            <td>
                <select id="iblock-select" name="iblock_id" class="adm-input" disabled>
                    <option value="">-- Сначала выберите тип --</option>
                </select>
            </td>
        </tr>
        <tr class="profile-settings">
            <td>
                <label for="export-type-select">Формат экспорта:</label>
            </td>
            <td>
                <select id="export-type-select" name="export_type" class="adm-input">
                    <option value="">-- Выберите формат --</option>
                    <option value="xls">Excel (XLS)</option>
                    <option value="csv">CSV</option>
                </select>
            </td>
        </tr>

        <!-- Выбор полей для экспорта -->
        <tr class="heading fields-selection">
            <td colspan="2">Поля для экспорта</td>
        </tr>
        <tr class="fields-selection">
            <td colspan="2">

                <div class="adm-detail-content-item-block-desc">
                    <div style="margin-bottom: 10px;">
                        <input type="button" id="select-all-fields" value="Выбрать все" class="adm-btn">
                        <input type="button" id="deselect-all-fields" value="Снять все" class="adm-btn">
                    </div>
                </div>

                <div id="fields-container"
                     style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                    <!-- Поля будут загружены динамически -->
                </div>

            </td>
        </tr>

        <!-- Настройки для CSV -->
        <tr class="heading csv-settings">
            <td colspan="2">Настройки CSV</td>
        </tr>
        <tr class="csv-settings">
            <td>
                <label>Разделитель:</label>
            </td>
            <td>
                <select name="settings[delimiter]" class="adm-input">
                    <option value=";">Точка с запятой (;)</option>
                    <option value=",">Запятая (,)</option>
                    <option value="\t">Табуляция</option>
                </select>
            </td>
        </tr>
        <tr class="csv-settings">
            <td>
                <label>Кодировка:</label>
            </td>
            <td>
                <select name="settings[encoding]" class="adm-input">
                    <option value="UTF-8">UTF-8</option>
                    <option value="Windows-1251">Windows-1251</option>
                    <option value="CP866">CP866</option>
                </select>
            </td>
        </tr>
        <tr class="csv-settings">
            <td>
                <label>Включить заголовки:</label>
            </td>
            <td>
                <input type="checkbox" name="settings[include_headers]" value="Y" checked>
            </td>
        </tr>
        <tr class="csv-settings">
            <td>
                <label>Обрамлять кавычками:</label>
            </td>
            <td>
                <input type="checkbox" name="settings[quote_fields]" value="Y">
            </td>
        </tr>

        <!-- Настройки для Excel -->
        <tr class="heading excel-settings">
            <td colspan="2">Настройки Excel</td>
        </tr>
        <tr class="excel-settings">
            <td>
                <label>Название листа:</label>
            </td>
            <td>
                <input type="text" name="settings[sheet_name]" class="adm-input" value="Экспорт">
            </td>
        </tr>
        <tr class="excel-settings">
            <td>
                <label>Включить заголовки:</label>
            </td>
            <td>
                <input type="checkbox" name="settings[include_headers]" value="Y" checked>
            </td>
        </tr>
        <tr class="excel-settings">
            <td>
                <label>Автоширина столбцов:</label>
            </td>
            <td>
                <input type="checkbox" name="settings[auto_width]" value="Y" checked>
            </td>
        </tr>

        <?php $tabControl->Buttons(); ?>

        <input type="submit" value="Сохранить" <?= $btnDisabled ? "disabled" : "" ?>>
        <input type="button" id="cancel-btn" value="Отмена" <?= $btnDisabled ? "disabled" : "" ?>>

        <?php $tabControl->End(); ?>

    </form>

    <script>
        class ExportProfileManager {
            constructor() {
                this.currentProfileId = null;
                this.availableFields = [];
                this.init();
            }

            init() {
                this.bindEvents();
                this.loadProfiles();
                this.loadIblockTypes();
            }

            bindEvents() {
                // Выбор профиля
                document.getElementById("profile-select").addEventListener("change", (e) => {
                    if (e.target.value) {
                        this.loadProfile(e.target.value);
                    } else {
                        this.toggleSettings(false);
                        this.toggleFieldsSelection(false);
                        this.hideExportSettings();
                    }
                });

                // Создание нового профиля
                document.getElementById("create-profile-btn").addEventListener("click", () => {
                    this.createNewProfile();
                });

                // Удаление профиля
                document.getElementById("delete-profile-btn").addEventListener("click", () => {
                    this.deleteProfile();
                });

                // Изменение типа инфоблока
                document.getElementById("iblock-type-select").addEventListener("change", (e) => {
                    this.loadIblocks(e.target.value);
                });

                // Изменение инфоблока
                document.getElementById("iblock-select").addEventListener("change", (e) => {
                    if (e.target.value) {
                        this.loadIblockFields(e.target.value);
                    } else {
                        this.toggleFieldsSelection(false);
                    }
                });

                // Изменение типа экспорта
                document.getElementById("export-type-select").addEventListener("change", (e) => {
                    this.showExportSettings(e.target.value);
                });

                // Выбор всех полей
                document.getElementById("select-all-fields").addEventListener("click", () => {
                    this.selectAllFields(true);
                });

                // Снятие всех полей
                document.getElementById("deselect-all-fields").addEventListener("click", () => {
                    this.selectAllFields(false);
                });

                // Сохранение формы
                document.getElementById("data-export-form").addEventListener("submit", (e) => {
                    e.preventDefault();
                    this.saveProfile();
                });

                // Отмена
                document.getElementById("cancel-btn").addEventListener("click", () => {
                    this.toggleSettings(false);
                    this.resetForm();
                });
            }

            createNewProfile() {
                this.currentProfileId = null;
                this.resetForm();
                this.toggleSettings(true);
            }

            async loadProfiles() {
                try {
                    const response = await this.makeRequest("get_profiles");
                    const select = document.getElementById("profile-select");

                    // Очищаем опции кроме первой
                    select.innerHTML = "<option value=''>-- Выберите --</option>";

                    response.data.forEach(profile => {
                        const option = document.createElement("option");
                        option.value = profile.ID;
                        option.textContent = profile.NAME;
                        select.appendChild(option);
                    });
                } catch (error) {
                    console.error("Ошибка загрузки профилей:", error);
                }
            }

            async loadProfile(profileId) {
                try {
                    const response = await this.makeRequest("get_profile", {profile_id: profileId});
                    const profile = response.data;

                    //console.log("Profile settings:", profile.SETTINGS, typeof profile.SETTINGS);

                    this.currentProfileId = profileId;
                    this.fillForm(profile);
                    this.toggleSettings(true);

                    // Загружаем инфоблоки если есть тип
                    if (profile.IBLOCK_TYPE_ID) {
                        await this.loadIblocks(profile.IBLOCK_TYPE_ID);
                        document.getElementById("iblock-select").value = profile.IBLOCK_ID;

                        // Загружаем поля если есть инфоблок
                        if (profile.IBLOCK_ID) {
                            await this.loadIblockFields(profile.IBLOCK_ID);
                        }
                    }

                    // Показываем настройки экспорта
                    if (profile.EXPORT_TYPE) {
                        this.showExportSettings(profile.EXPORT_TYPE);
                        this.fillExportSettings(profile.SETTINGS);
                    }
                } catch (error) {
                    console.error("Ошибка загрузки профиля:", error);
                }
            }

            async deleteProfile() {
                const profileId = document.getElementById("profile-select").value;
                if (!profileId) {
                    BX.UI.Dialogs.MessageBox.alert("Выберите профиль для удаления", "Сообщение");
                    return;
                }

                if (confirm("Вы уверены, что хотите удалить этот профиль?")) {
                    try {
                        await this.makeRequest("delete_profile", {profile_id: profileId});
                        this.loadProfiles();
                        this.toggleSettings(false);
                        this.resetForm();
                        BX.UI.Dialogs.MessageBox.alert("Профиль удален", "Сообщение");
                    } catch (error) {
                        console.error("Ошибка удаления профиля:", error);
                        BX.UI.Dialogs.MessageBox.alert("Ошибка удаления профиля", "Сообщение");
                    }
                }
            }

            async loadIblockTypes() {
                try {
                    const response = await this.makeRequest("get_iblock_types");
                    const select = document.getElementById("iblock-type-select");

                    select.innerHTML = "<option value=''>-- Выберите тип инфоблока --</option>";

                    response.data.forEach(type => {
                        const option = document.createElement("option");
                        option.value = type.ID;
                        option.textContent = type.NAME || type.ID;
                        select.appendChild(option);
                    });
                } catch (error) {
                    console.error("Ошибка загрузки типов инфоблоков:", error);
                }
            }

            async loadIblocks(typeId) {
                const select = document.getElementById("iblock-select");

                if (!typeId) {
                    select.innerHTML = "<option value=''>-- Сначала выберите тип --</option>";
                    select.disabled = true;
                    this.toggleFieldsSelection(false);
                    return;
                }

                try {
                    const response = await this.makeRequest("get_iblocks", {type_id: typeId});

                    select.innerHTML = "<option value=''>-- Выберите инфоблок --</option>";

                    response.data.forEach(iblock => {
                        const option = document.createElement("option");
                        option.value = iblock.ID;
                        option.textContent = iblock.NAME;
                        select.appendChild(option);
                    });

                    select.disabled = false;
                } catch (error) {
                    console.error("Ошибка загрузки инфоблоков:", error);
                }
            }

            async loadIblockFields(iblockId) {
                if (!iblockId) {
                    this.toggleFieldsSelection(false);
                    return;
                }

                try {
                    const response = await this.makeRequest("get_iblock_fields", {iblock_id: iblockId});
                    this.availableFields = response.data;
                    this.renderFieldsSelection();
                    this.toggleFieldsSelection(true);
                } catch (error) {
                    console.error("Ошибка загрузки полей инфоблока:", error);
                }
            }

            async saveProfile() {
                const formData = new FormData(document.getElementById("data-export-form"));
                const data = {};

                // Собираем обычные поля
                for (let [key, value] of formData.entries()) {
                    if (key.startsWith("settings[")) {
                        continue;
                    }
                    data[key] = value;
                }

                // Собираем настройки
                const settings = {};
                const exportFields = [];

                // Получаем поля в порядке их расположения на форме
                const fieldItems = document.querySelectorAll(".field-item");
                fieldItems.forEach(fieldItem => {
                    const checkbox = fieldItem.querySelector("input[type='checkbox']");
                    if (checkbox && checkbox.checked) {
                        exportFields.push(checkbox.value);
                    }
                });

                for (let [key, value] of formData.entries()) {
                    if (key.startsWith("settings[") && key !== "settings[export_fields][]") {
                        const settingKey = key.match(/settings\[(.+)\]/)[1];
                        settings[settingKey] = value;
                    }
                }

                // Добавляем выбранные поля в правильном порядке
                if (exportFields.length > 0) {
                    settings.export_fields = exportFields;
                }

                // Обрабатываем чекбоксы
                document.querySelectorAll("input[type='checkbox'][name^='settings[']").forEach(checkbox => {
                    if (checkbox.name !== "settings[export_fields][]") {
                        const settingKey = checkbox.name.match(/settings\[(.+)\]/)[1];
                        if (!settings[settingKey]) {
                            settings[settingKey] = checkbox.checked ? "Y" : "N";
                        }
                    }
                });

                data.settings = settings;
                data.action = "save_profile";

                try {
                    const response = await this.makeRequest("save_profile", data);

                    BX.UI.Dialogs.MessageBox.alert(response.message, "Сообщение");

                    if (!this.currentProfileId && response.id) {
                        this.currentProfileId = response.id;
                        document.getElementById("profile-id").value = response.id;
                    }

                    this.loadProfiles();
                } catch (error) {
                    console.error("Ошибка сохранения профиля:", error);
                    BX.UI.Dialogs.MessageBox.alert("Ошибка сохранения профиля", "Сообщение");
                }
            }

            async makeRequest(action, data = {}) {
                data.action = action;

                const formData = new FormData();
                Object.keys(data).forEach(key => {
                    if (typeof data[key] === 'object' && data[key] !== null) {
                        formData.append(key, JSON.stringify(data[key]));
                    } else {
                        formData.append(key, data[key]);
                    }
                });

                const response = await fetch(window.location.href, {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || "Ошибка запроса");
                }

                return result;
            }

            renderFieldsSelection() {
                const container = document.getElementById("fields-container");
                container.innerHTML = "";

                // Группируем поля по типам
                const fieldGroups = {
                    "FIELD": "Поля инфоблока",
                    "PROPERTY": "Свойства инфоблока"
                };

                Object.keys(fieldGroups).forEach(groupType => {
                    const groupFields = this.availableFields.filter(field => field.TYPE === groupType);

                    if (groupFields.length === 0) return;

                    // Контейнер группы
                    const groupDiv = document.createElement("div");
                    groupDiv.className = "field-group";

                    // Заголовок группы
                    const groupTitle = document.createElement("h3");
                    groupTitle.textContent = fieldGroups[groupType];
                    groupDiv.appendChild(groupTitle);

                    // Контейнер для сортируемых полей
                    const sortableContainer = document.createElement("div");
                    sortableContainer.className = "sortable-container";
                    sortableContainer.setAttribute("data-group", groupType);

                    // Поля группы
                    groupFields.forEach(field => {
                        const fieldDiv = document.createElement("div");
                        fieldDiv.className = "field-item";
                        fieldDiv.setAttribute("draggable", "true");
                        fieldDiv.setAttribute("data-field-code", field.CODE);

                        // Иконка перетаскивания
                        const dragHandle = document.createElement("span");
                        dragHandle.className = "drag-handle";
                        dragHandle.innerHTML = "⋮⋮"; // или можно использовать &#8942;&#8942;
                        dragHandle.title = "Перетащите для изменения порядка";

                        const checkbox = document.createElement("input");
                        checkbox.type = "checkbox";
                        checkbox.name = "settings[export_fields][]";
                        checkbox.value = field.CODE;
                        checkbox.id = "field_" + field.CODE;
                        checkbox.className = "field-checkbox";

                        const label = document.createElement("label");
                        label.setAttribute("for", "field_" + field.CODE);
                        label.textContent = field.NAME + " [" + field.CODE + "]";
                        label.className = "field-label";

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
            }

            initDragAndDrop() {
                const containers = document.querySelectorAll(".sortable-container");

                containers.forEach(container => {
                    this.makeSortable(container);
                });
            }

            makeSortable(container) {
                let draggedElement = null;
                let placeholder = null;

                container.addEventListener("dragstart", (e) => {
                    if (!e.target.classList.contains("field-item")) return;

                    draggedElement = e.target;
                    draggedElement.classList.add("dragging");

                    // Создаем placeholder
                    placeholder = document.createElement("div");
                    placeholder.className = "field-item";
                    placeholder.style.height = draggedElement.offsetHeight + "px";
                    placeholder.style.background = "#e3f2fd";
                    placeholder.style.border = "2px dashed #2196f3";
                    placeholder.style.opacity = "0.5";
                    placeholder.innerHTML = "<span style='color: #2196f3; text-align: center; display: block;'>Отпустите здесь</span>";

                    e.dataTransfer.effectAllowed = "move";
                    e.dataTransfer.setData("text/html", draggedElement.outerHTML);
                });

                container.addEventListener("dragend", (e) => {
                    if (draggedElement) {
                        draggedElement.classList.remove("dragging");
                        draggedElement = null;
                    }
                    if (placeholder && placeholder.parentNode) {
                        placeholder.parentNode.removeChild(placeholder);
                        placeholder = null;
                    }
                });

                container.addEventListener("dragover", (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = "move";

                    if (!draggedElement || !placeholder) return;

                    const afterElement = this.getDragAfterElement(container, e.clientY);

                    if (afterElement == null) {
                        container.appendChild(placeholder);
                    } else {
                        container.insertBefore(placeholder, afterElement);
                    }
                });

                container.addEventListener("drop", (e) => {
                    e.preventDefault();

                    if (!draggedElement || !placeholder) return;

                    // Заменяем placeholder на перетаскиваемый элемент
                    placeholder.parentNode.replaceChild(draggedElement, placeholder);

                    // Обновляем порядок полей в настройках
                    this.updateFieldsOrder();
                });
            }

            getDragAfterElement(container, y) {
                const draggableElements = [...container.querySelectorAll(".field-item:not(.dragging)")];

                return draggableElements.reduce((closest, child) => {
                    const box = child.getBoundingClientRect();
                    const offset = y - box.top - box.height / 2;

                    if (offset < 0 && offset > closest.offset) {
                        return {offset: offset, element: child};
                    } else {
                        return closest;
                    }
                }, {offset: Number.NEGATIVE_INFINITY}).element;
            }

            updateFieldsOrder() {
                // Получаем все поля в их текущем порядке
                const allFields = document.querySelectorAll(".field-item");
                const orderedFields = [];

                allFields.forEach((fieldElement, index) => {
                    const fieldCode = fieldElement.getAttribute("data-field-code");
                    const checkbox = fieldElement.querySelector("input[type='checkbox']");

                    if (checkbox && fieldCode) {
                        // Обновляем порядок в value checkbox или создаем скрытое поле для порядка
                        checkbox.setAttribute("data-sort-order", index);
                        orderedFields.push({
                            code: fieldCode,
                            order: index,
                            selected: checkbox.checked
                        });
                    }
                });

                // Сохраняем порядок в объекте класса для использования при сохранении
                this.fieldsOrder = orderedFields;
            }

            toggleFieldsSelection(show) {
                document.querySelectorAll(".fields-selection").forEach(el => {
                    el.style.display = show ? "table-row" : "none";
                });
            }

            selectAllFields(select) {
                const checkboxes = document.querySelectorAll("input[name='settings[export_fields][]']");
                checkboxes.forEach(checkbox => {
                    checkbox.checked = select;
                });
            }

            showExportSettings(exportType) {
                this.hideExportSettings();
                const settingsMap = {
                    xls: ".excel-settings",
                    csv: ".csv-settings"
                };
                const selector = settingsMap[exportType];
                if (selector) {
                    document.querySelectorAll(selector).forEach(el => {
                        el.style.display = "table-row";
                    });
                }
            }

            hideExportSettings() {
                [".excel-settings", ".csv-settings"].forEach(selector => {
                    document.querySelectorAll(selector).forEach(el => {
                        el.style.display = "none";
                    });
                });
            }

            fillForm(profile) {
                document.getElementById("profile-id").value = profile.ID;
                document.getElementById("profile-name").value = profile.NAME;
                document.getElementById("iblock-type-select").value = profile.IBLOCK_TYPE_ID || "";
                document.getElementById("export-type-select").value = profile.EXPORT_TYPE || "";
            }

            fillExportSettings(settingsJson) {
                if (!settingsJson) return;

                try {
                    let settings = JSON.parse(settingsJson);

                    // Если результат парсинга - строка, парсим еще раз
                    if (typeof settings === 'string') {
                        settings = JSON.parse(settings);
                    }

                    //console.log("Final parsed settings:", settings);
                    //console.log("Type:", typeof settings);

                    // Теперь должен быть объект
                    if (typeof settings === 'object' && !Array.isArray(settings)) {
                        Object.keys(settings).forEach(key => {
                            if (key === "export_fields" && Array.isArray(settings[key])) {
                                this.restoreFieldsOrder(settings[key]);
                            } else {
                                const input = document.querySelector(`[name="settings[${key}]"]`);

                                //console.log(`Looking for input: settings[${key}]`, input);

                                if (input) {
                                    if (input.type === "checkbox") {
                                        input.checked = settings[key] === "Y";
                                    } else {
                                        input.value = settings[key];
                                    }
                                }
                            }
                        });
                    }
                } catch (error) {
                    console.error("Ошибка парсинга настроек:", error);
                }
            }

            toggleSettings(show) {
                document.querySelectorAll(".profile-settings").forEach(el => {
                    el.style.display = show ? "table-row" : "none";
                });
                if (!show) {
                    document.getElementById("profile-select").value = "";
                }
            }

            resetForm() {
                document.getElementById("data-export-form").reset();
                document.getElementById("profile-id").value = "";
                document.getElementById("iblock-select").innerHTML = "<option value=''>-- Сначала выберите тип --</option>";
                document.getElementById("iblock-select").disabled = true;
                this.toggleSettings(false);
                this.toggleFieldsSelection(false);
                this.hideExportSettings();
                this.currentProfileId = null;
                this.availableFields = [];
            }

            restoreFieldsOrder(savedFields) {
                if (!savedFields || !Array.isArray(savedFields)) return;

                // Отмечаем выбранные поля
                savedFields.forEach(fieldCode => {
                    const checkbox = document.querySelector("input[value='" + fieldCode + "']");
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });

                // Переупорядочиваем элементы согласно сохраненному порядку
                const containers = document.querySelectorAll(".sortable-container");

                containers.forEach(container => {
                    const fieldItems = Array.from(container.querySelectorAll(".field-item"));
                    const sortedItems = [];

                    // Сначала добавляем поля в сохраненном порядке
                    savedFields.forEach(fieldCode => {
                        const fieldItem = fieldItems.find(item =>
                            item.getAttribute("data-field-code") === fieldCode
                        );
                        if (fieldItem) {
                            sortedItems.push(fieldItem);
                        }
                    });

                    // Затем добавляем остальные поля
                    fieldItems.forEach(item => {
                        if (!sortedItems.includes(item)) {
                            sortedItems.push(item);
                        }
                    });

                    // Переставляем элементы в DOM
                    sortedItems.forEach(item => {
                        container.appendChild(item);
                    });
                });
            }
        }

        // Инициализируем
        BX.ready(function () {
            new ExportProfileManager();
        });
    </script>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");