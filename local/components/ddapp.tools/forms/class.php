<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Directory;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Forms\FileSecurityValidator;
use DDAPP\Tools\Forms\RateLimiter;

// Настройка логирования
LogHelper::configure();

class DDAppFormComponent extends CBitrixComponent
{
    private $iblockId;
    private $fileConfig;
    private $rateLimiter;
    private $hookManager;
    private $fileValidator;

    /**
     * @param $params
     * @return mixed
     */
    public function onPrepareComponentParams($params): mixed
    {
        $params["CACHE_TIME"] = isset($params["CACHE_TIME"]) ? $params["CACHE_TIME"] : 3600;
        $this->iblockId = (int)$params["IBLOCK_ID"];

        // Загрузка конфигурации безопасности файлов
        $this->fileConfig = $this->loadFileConfig($params);

        // Инициализация компонентов
        $this->rateLimiter = new RateLimiter($this->iblockId, $params["RATE_LIMITS"] ?? []);
        $this->fileValidator = new FileSecurityValidator($this->fileConfig, $this->iblockId);

        return $params;
    }

    /**
     * Загрузка конфигурации файлов
     */
    private function loadFileConfig($params)
    {
        $configPath = __DIR__ . "/config/file_security.php";
        $defaultConfig = file_exists($configPath) ? include($configPath) : [];

        // Переопределяем настройки из параметров компонента
        if (isset($params["MAX_FILE_SIZE"]) && (int)$params["MAX_FILE_SIZE"] > 0) {
            $defaultConfig["max_file_size"] = (int)$params["MAX_FILE_SIZE"] * 1024 * 1024;
        }

        if (!empty($params["ALLOWED_FILE_EXTENSIONS"])) {
            $defaultConfig["allowed_extensions"] = array_map("trim", explode(",", strtolower($params["ALLOWED_FILE_EXTENSIONS"])));
        }

        return $defaultConfig;
    }

    /**
     * @return void
     */
    public function executeComponent(): void
    {
        if (!Loader::includeModule("iblock")) {
            return;
        }

        $res = CIBlock::GetByID($this->iblockId);
        $arIblock = $res->GetNext();
        if (!$arIblock["ID"]) {
            return;
        }

        $this->arResult["NAME"] = $arIblock["NAME"];
        $this->arResult["DESCRIPTION"] = $arIblock["DESCRIPTION"];
        $this->arResult["PROPERTIES"] = $this->getIblockProperties();
        $this->arResult["FORM_ID"] = "ddapp_form_" . $this->iblockId;
        $this->arResult["IBLOCK_ID"] = $this->iblockId;
        $this->arResult["CAPTCHA_CODE"] = "";
        $this->arResult["FILE_CONFIG"] = $this->fileConfig;

        $request = Application::getInstance()->getContext()->getRequest();

        // Проверяем AJAX-запрос в самом начале
        if ($request->isPost() && $request->getPost("ajax_" . $this->iblockId) === "Y") {
            global $APPLICATION;

            // CSRF защита
            if (!check_bitrix_sessid()) {
                $APPLICATION->RestartBuffer();
                header("Content-Type: application/json; charset=utf-8");
                echo json_encode([
                    "success" => false,
                    "message" => "Ошибка безопасности. Обновите страницу и попробуйте снова."
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            // Проверка rate limiting
            $rateLimitResult = $this->rateLimiter->checkLimits();
            if (!$rateLimitResult["allowed"]) {
                $APPLICATION->RestartBuffer();
                header("Content-Type: application/json; charset=utf-8");
                echo json_encode([
                    "success" => false,
                    "message" => $rateLimitResult["message"],
                    "retry_after" => $rateLimitResult["retry_after"]
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            // Очищаем буфер
            $APPLICATION->RestartBuffer();

            $result = $this->processForm();

            // Устанавливаем правильный заголовок
            header("Content-Type: application/json; charset=utf-8");

            // Выводим JSON и завершаем
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Генерирование Bitrix Captcha
        if ($this->arParams["USE_BITRIX_CAPTCHA"] === "Y") {
            $this->arResult["CAPTCHA_CODE"] = $this->generateCaptcha();
        }

        $this->includeComponentTemplate();
    }

    /**
     * Получает свойства инфоблока
     * @return array
     */
    private function getIblockProperties(): array
    {
        $properties = [];
        $res = PropertyTable::getList([
            "filter" => ["IBLOCK_ID" => $this->iblockId, "ACTIVE" => "Y"],
            "select" => ["ID", "CODE", "NAME", "PROPERTY_TYPE", "LIST_TYPE", "MULTIPLE", "IS_REQUIRED", "HINT", "USER_TYPE", "ROW_COUNT", "COL_COUNT", "LINK_IBLOCK_ID"],
            "order" => ["SORT" => "ASC"]
        ]);

        while ($property = $res->fetch()) {
            $property["LIST_VALUES"] = [];

            if ($property["PROPERTY_TYPE"] === "L") {
                $property["LIST_VALUES"] = $this->getPropertyListValues($property["ID"]);
            }

            if ($property["PROPERTY_TYPE"] === "E" && !empty($property["LINK_IBLOCK_ID"])) {
                $property["ELEMENT_VALUES"] = $this->getElementValues($property["LINK_IBLOCK_ID"]);
            }

            $properties[] = $property;
        }

        return $properties;
    }

    /**
     * Получает список свойства инфоблока
     * @param $propertyId
     * @return array
     */
    private function getPropertyListValues($propertyId): array
    {
        $values = [];
        $res = CIBlockPropertyEnum::GetList(
            ["SORT" => "ASC"],
            ["PROPERTY_ID" => $propertyId]
        );
        while ($value = $res->fetch()) {
            $values[] = $value;
        }
        return $values;
    }

    /**
     * Генерирование Captcha
     * @return mixed
     */
    private function generateCaptcha(): mixed
    {
        $cpt = new CCaptcha();
        $captchaPass = Option::get("main", "captcha_password");

        // Проверка на пустоту, если пусто генерируем новый код капчи
        if (strlen($captchaPass) <= 0) {
            $captchaPass = randString(10);
            Option::set("main", "captcha_password", $captchaPass);
        }

        $cpt->SetCodeCrypt($captchaPass);
        return $cpt->GetCodeCrypt();
    }

    /**
     * Обработка формы
     * @return array
     */
    private function processForm(): array
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $result = ["success" => false, "message" => ""];

        LogHelper::info("form_" . $this->iblockId, "Form processing started", [
            "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown",
            "user_agent" => substr($_SERVER["HTTP_USER_AGENT"] ?? "", 0, 100)
        ]);

        // Хук перед валидацией
        $formData = $request->getPostList()->toArray();
        $formData = $this->hookManager->executeHooks("beforeValidation", $formData, $this->iblockId);

        if (!$this->validateCaptcha($request)) {
            $result["message"] = "Неверный код капчи";
            return $result;
        }

        // Валидация полей формы
        $errors = $this->validateForm($request);

        // Хук после валидации
        $errors = $this->hookManager->executeHooks("afterValidation", $errors, $formData, $this->iblockId);

        if (!empty($errors)) {
            $result["message"] = implode("<br>", $errors);
            LogHelper::warning("form_" . $this->iblockId, "Form validation failed", [
                "errors" => $errors,
                "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"
            ]);
            return $result;
        }

        // Хук перед сохранением
        $formData = $this->hookManager->executeHooks("beforeSave", $formData, $this->iblockId);

        // Сохранение элемента инфоблока
        $elementId = $this->saveElement($request);

        if ($elementId) {
            // Хук после сохранения
            $elementId = $this->hookManager->executeHooks("afterSave", $elementId, $formData, $this->iblockId);

            // Отправка письма
            if (!empty($this->arParams["EMAIL_TEMPLATE"])) {
                $emailResult = $this->sendEmail($elementId, $request);
                $this->hookManager->executeHooks("afterEmailSend", $emailResult, [], $this->arParams["EMAIL_TEMPLATE"], $this->iblockId);
            }

            LogHelper::info("form_" . $this->iblockId, "Form submitted successfully", [
                "element_id" => $elementId,
                "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"
            ]);

            $result["success"] = true;
            $result["message"] = "Форма успешно отправлена";
            $result["element_id"] = $elementId;
        } else {
            $result["message"] = "Ошибка сохранения данных";
            LogHelper::error("form_" . $this->iblockId, "Form save failed", [
                "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"
            ]);
        }

        return $result;
    }

    /**
     * Валидация Captcha
     * @param $request
     * @return bool
     */
    private function validateCaptcha($request): bool
    {
        if ($this->arParams["USE_BITRIX_CAPTCHA"] === "Y") {
            $captcha = new CCaptcha();
            $captchaWord = $request->getPost("captcha_word");
            $captchaCode = $request->getPost("captcha_code");

            if (!$captcha->CheckCodeCrypt($captchaWord, $captchaCode)) {
                return false;
            }
        }

        if ($this->arParams["USE_GOOGLE_RECAPTCHA"] === "Y") {
            $recaptchaResponse = $request->getPost("g-recaptcha-response");

            if (empty($recaptchaResponse)) {
                return false;
            }

            $data = [
                "secret" => $this->arParams["GOOGLE_RECAPTCHA_SECRET_KEY"],
                "response" => $recaptchaResponse,
                "remoteip" => $_SERVER["REMOTE_ADDR"]
            ];

            $options = [
                "http" => [
                    "header" => "Content-type: application/x-www-form-urlencoded\r\n",
                    "method" => "POST",
                    "content" => http_build_query($data)
                ]
            ];

            $context = stream_context_create($options);
            $result = file_get_contents("https://www.google.com/recaptcha/api/siteverify", false, $context);
            $resultJson = json_decode($result);

            if ($resultJson && $resultJson->success && $resultJson->score > 0.5) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Валидация полей формы с улучшенной обработкой файлов
     * @param $request
     * @return array
     */
    private function validateForm($request): array
    {
        $errors = [];

        foreach ($this->arResult["PROPERTIES"] as $property) {
            $value = $request->getPost("property_" . $property["ID"]);

            if ($property["IS_REQUIRED"] === "Y") {
                $isEmpty = false;

                if ($property["PROPERTY_TYPE"] === "F") {
                    // Для файловых полей проверяем $_FILES
                    $files = $_FILES["property_" . $property["ID"]] ?? null;
                    if (!$files || (is_array($files["name"]) && empty(array_filter($files["name"]))) || (!is_array($files["name"]) && empty($files["name"]))) {
                        $isEmpty = true;
                    }
                } elseif (is_array($value)) {
                    // Для множественных значений
                    $filteredValues = array_filter($value, function ($v) {
                        return !empty(trim($v));
                    });
                    $isEmpty = empty($filteredValues);
                } else {
                    // Для одиночных значений
                    $isEmpty = empty(trim($value));
                }

                if ($isEmpty) {
                    $errors[] = "Поле '" . $property["NAME"] . "' обязательно для заполнения";
                }
            }

            // Дополнительная валидация по типам
            if (!empty($value) && !is_array($value)) {
                switch ($property["PROPERTY_TYPE"]) {
                    case "N": // Число
                        if (!is_numeric($value)) {
                            $errors[] = "Поле '" . $property["NAME"] . "' должно содержать числовое значение";
                        }
                        break;

                    case "S": // Дата
                        if ($property["USER_TYPE"] === "DateTime") {
                            $date = DateTime::createFromFormat("Y-m-d", $value);
                            if (!$date || $date->format("Y-m-d") !== $value) {
                                $errors[] = "Поле '" . $property["NAME"] . "' должно содержать корректную дату";
                            }
                        }
                        break;
                }
            }

            // Улучшенная валидация файлов
            if ($property["PROPERTY_TYPE"] === "F") {
                $fileErrors = $this->validateFiles($property);
                if (!empty($fileErrors)) {
                    $errors = array_merge($errors, $fileErrors);
                }
            }
        }

        return $errors;
    }

    /**
     * Улучшенная валидация загружаемых файлов
     * @param array $property
     * @return array
     */
    private function validateFiles(array $property): array
    {
        $errors = [];
        $files = $_FILES["property_" . $property["ID"]] ?? null;

        if (!$files || empty($files["name"])) {
            return $errors;
        }

        LogHelper::info("form_" . $this->iblockId, "Starting enhanced file validation", [
            "property_id" => $property["ID"],
            "property_name" => $property["NAME"],
            "files_count" => is_array($files["name"]) ? count($files["name"]) : 1
        ]);

        // Обработка множественных файлов
        if (is_array($files["name"])) {
            for ($i = 0; $i < count($files["name"]); $i++) {
                if (empty($files["name"][$i])) continue;

                $fileData = [
                    "name" => $files["name"][$i],
                    "type" => $files["type"][$i],
                    "tmp_name" => $files["tmp_name"][$i],
                    "error" => $files["error"][$i],
                    "size" => $files["size"][$i]
                ];

                $fileErrors = $this->fileValidator->validateFile($fileData, $property["NAME"]);
                $errors = array_merge($errors, $fileErrors);
            }
        } else {
            // Одиночный файл
            if (!empty($files["name"])) {
                $fileErrors = $this->fileValidator->validateFile($files, $property["NAME"]);
                $errors = array_merge($errors, $fileErrors);
            }
        }

        return $errors;
    }

    /**
     * Сохранение записи в инфоблок
     * @param $request
     * @return mixed
     */
    private function saveElement($request): mixed
    {
        $element = new CIBlockElement();

        $fields = [
            "IBLOCK_ID" => $this->iblockId,
            "NAME" => "Заявка от " . date("d.m.Y H:i:s"),
            "ACTIVE" => "Y",
            "PROPERTY_VALUES" => []
        ];

        foreach ($this->arResult["PROPERTIES"] as $property) {
            if ($property["PROPERTY_TYPE"] === "F") {
                // Обработка файловых полей
                $fileIds = $this->processFileUpload($property);
                if (!empty($fileIds)) {
                    $fields["PROPERTY_VALUES"][$property["ID"]] = $fileIds;
                }
            } else {
                $value = $request->getPost("property_" . $property["ID"]);

                if (!empty($value) || $value === "0") {
                    if (is_array($value)) {
                        // Для множественных значений - фильтруем пустые
                        $filteredValues = array_filter($value, function ($v) {
                            return !empty(trim($v)) || $v === "0";
                        });

                        if (!empty($filteredValues)) {
                            $fields["PROPERTY_VALUES"][$property["ID"]] = $filteredValues;
                        }
                    } else {
                        // Для одиночных значений
                        $processedValue = trim($value);

                        switch ($property["PROPERTY_TYPE"]) {
                            case "N": // Число
                                $processedValue = is_numeric($value) ? $value : "";
                                break;
                            case "S": // Строка/Дата
                                if ($property["USER_TYPE"] === "DateTime") {
                                    // Дата уже валидирована
                                }
                                break;
                            default:
                                $processedValue = trim($value);
                        }

                        if (!empty($processedValue) || $processedValue === "0") {
                            $fields["PROPERTY_VALUES"][$property["ID"]] = $processedValue;
                        }
                    }
                }
            }
        }

        $elementId = $element->Add($fields);

        if (!$elementId) {
            $error = $element->LAST_ERROR;
            if (!empty($error)) {
                LogHelper::error("form_" . $this->iblockId, "Element save failed", [
                    "error" => $error,
                    "fields" => $fields
                ]);
            }
        } else {
            LogHelper::info("form_" . $this->iblockId, "Element saved successfully", [
                "element_id" => $elementId,
                "properties_count" => count($fields["PROPERTY_VALUES"])
            ]);
        }

        return $elementId;
    }

    /**
     * Обработка загрузки файлов
     * @param array $property
     * @return array
     */
    private function processFileUpload(array $property): array
    {
        $fileIds = [];
        $files = $_FILES["property_" . $property["ID"]] ?? null;

        if (!$files || empty($files["name"])) {
            return $fileIds;
        }

        LogHelper::info("form_" . $this->iblockId, "Starting file upload process", [
            "property_id" => $property["ID"],
            "property_name" => $property["NAME"],
            "files_count" => is_array($files["name"]) ? count($files["name"]) : 1
        ]);

        // Создание папки для файлов если не существует
        $uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/upload/ddapp_forms/" . $this->iblockId . "/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $successCount = 0;
        $failureCount = 0;

        // Обработка множественных файлов
        if (is_array($files["name"])) {
            for ($i = 0; $i < count($files["name"]); $i++) {
                if (empty($files["name"][$i]) || $files["error"][$i] !== UPLOAD_ERR_OK) {
                    $failureCount++;
                    continue;
                }

                $fileData = [
                    "name" => $files["name"][$i],
                    "type" => $files["type"][$i],
                    "tmp_name" => $files["tmp_name"][$i],
                    "error" => $files["error"][$i],
                    "size" => $files["size"][$i]
                ];

                $fileId = $this->saveUploadedFile($fileData, $uploadDir);
                if ($fileId) {
                    $fileIds[] = $fileId;
                    $successCount++;
                } else {
                    $failureCount++;
                }
            }
        } else {
            // Одиночный файл
            if (!empty($files["name"]) && $files["error"] === UPLOAD_ERR_OK) {
                $fileId = $this->saveUploadedFile($files, $uploadDir);
                if ($fileId) {
                    $fileIds[] = $fileId;
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } else {
                $failureCount++;
            }
        }

        LogHelper::info("form_" . $this->iblockId, "File upload completed", [
            "property_id" => $property["ID"],
            "success_count" => $successCount,
            "failure_count" => $failureCount,
            "uploaded_file_ids" => $fileIds
        ]);

        return $fileIds;
    }

    /**
     * Сохранение загруженного файла
     * @param array $fileData
     * @param string $uploadDir
     * @return int|false
     */
    private function saveUploadedFile(array $fileData, string $uploadDir)
    {
        // Генерация безопасного имени файла
        $pathInfo = pathinfo($fileData["name"]);
        $extension = strtolower($pathInfo["extension"] ?? "");
        $baseName = preg_replace("/[^a-zA-Z0-9_-]/", "", $pathInfo["filename"]);
        $fileName = $baseName . "_" . time() . "_" . rand(1000, 9999) . "." . $extension;
        $filePath = $uploadDir . $fileName;

        // Перемещение файла
        if (move_uploaded_file($fileData["tmp_name"], $filePath)) {
            // Регистрация файла в Bitrix
            $fileArray = [
                "name" => $fileData["name"],
                "size" => $fileData["size"],
                "tmp_name" => $filePath,
                "type" => $fileData["type"],
                "old_file" => "",
                "del" => "",
                "MODULE_ID" => "iblock"
            ];

            $fileId = CFile::SaveFile($fileArray, "iblock");

            if ($fileId) {
                LogHelper::info("form_" . $this->iblockId, "File registered successfully", [
                    "file_id" => $fileId,
                    "original_name" => $fileData["name"],
                    "size" => $fileData["size"]
                ]);

                // Удаляем временный файл после регистрации
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            } else {
                LogHelper::error("form_" . $this->iblockId, "File registration failed", [
                    "original_name" => $fileData["name"],
                    "file_path" => $filePath
                ]);

                // Удаляем файл при ошибке регистрации
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            return $fileId;
        }

        return false;
    }

    /**
     * Получает элементы связанного инфоблока
     * @param $iblockId
     * @return array
     */
    private function getElementValues($iblockId): array
    {
        $elements = [];
        $res = CIBlockElement::GetList(
            ["SORT" => "ASC", "NAME" => "ASC"],
            ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"],
            false,
            false,
            ["ID", "NAME"]
        );
        while ($element = $res->fetch()) {
            $elements[] = $element;
        }
        return $elements;
    }

    /**
     * Отправка письма
     * @param $elementId
     * @param $request
     * @return bool
     */
    private function sendEmail($elementId, $request): bool
    {
        $fields = [
            "ELEMENT_ID" => $elementId,
            "IBLOCK_ID" => $this->iblockId,
            "DATE_CREATE" => date("d.m.Y H:i:s")
        ];

        // Хук перед отправкой письма
        $fields = $this->hookManager->executeHooks("beforeEmailSend", $fields, $this->arParams["EMAIL_TEMPLATE"], $this->iblockId);

        foreach ($this->arResult["PROPERTIES"] as $property) {
            if ($property["PROPERTY_TYPE"] === "F") {
                // Для файловых полей получаем информацию о загруженных файлах
                $files = $_FILES["property_" . $property["ID"]] ?? null;
                if ($files && !empty($files["name"])) {
                    if (is_array($files["name"])) {
                        $fileNames = array_filter($files["name"]);
                        $fields[$property["CODE"]] = implode(", ", $fileNames);
                    } else {
                        $fields[$property["CODE"]] = $files["name"];
                    }
                } else {
                    $fields[$property["CODE"]] = "";
                }
            } else {
                $value = $request->getPost("property_" . $property["ID"]);

                if (!empty($value)) {
                    if (is_array($value)) {
                        $fields[$property["CODE"]] = implode(", ", $value);
                    } else {
                        $fields[$property["CODE"]] = $value;
                    }
                } else {
                    $fields[$property["CODE"]] = "";
                }
            }
        }

        $result = Event::send([
            "EVENT_NAME" => $this->arParams["EMAIL_TEMPLATE"],
            "LID" => SITE_ID,
            "MESSAGE_ID" => $this->arParams["EMAIL_TEMPLATE"],
            "FIELDS" => $fields
        ]);

        $success = !empty($result->isSuccess()) ? $result->isSuccess() : true;

        LogHelper::info("form_" . $this->iblockId, "Email sending attempt", [
            "element_id" => $elementId,
            "template_id" => $this->arParams["EMAIL_TEMPLATE"],
            "success" => $success
        ]);

        return $success;
    }

    /**
     * Получение менеджера хуков для внешнего использования
     */
    public function getHookManager(): HookManager
    {
        return $this->hookManager;
    }
}