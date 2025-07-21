<?php

namespace DDAPP\Tools\Helpers;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Components\FileSecurityValidator;
use DDAPP\Tools\Components\RateLimiter;

Loc::loadMessages(__FILE__);

// Настройка логирования
LogHelper::configure();

class FormHelper
{
    /**
     * Получает свойства инфоблока
     * @param $iblockId
     * @return array
     */
    public static function getIblockProperties($iblockId): array
    {
        $properties = [];
        $res = PropertyTable::getList([
            "filter" => ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"],
            "select" => ["ID", "CODE", "NAME", "PROPERTY_TYPE", "LIST_TYPE", "MULTIPLE", "IS_REQUIRED", "HINT", "USER_TYPE", "ROW_COUNT", "COL_COUNT", "LINK_IBLOCK_ID"],
            "order" => ["SORT" => "ASC"]
        ]);

        while ($property = $res->fetch()) {
            $property["LIST_VALUES"] = [];

            if ($property["PROPERTY_TYPE"] === "L") {
                $property["LIST_VALUES"] = self::getPropertyListValues($property["ID"]);
            }

            if ($property["PROPERTY_TYPE"] === "E" && !empty($property["LINK_IBLOCK_ID"])) {
                $property["ELEMENT_VALUES"] = self::getElementValues($property["LINK_IBLOCK_ID"]);
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
    public static function getPropertyListValues($propertyId): array
    {
        $values = [];
        $res = \CIBlockPropertyEnum::GetList(
            ["SORT" => "ASC"],
            ["PROPERTY_ID" => $propertyId]
        );
        while ($value = $res->fetch()) {
            $values[] = $value;
        }
        return $values;
    }

    /**
     * Получает элементы связанного инфоблока
     * @param $iblockId
     * @return array
     */
    public static function getElementValues($iblockId): array
    {
        $elements = [];
        $res = \CIBlockElement::GetList(
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
     * Генерирование Captcha
     * @return mixed
     */
    public static function generateCaptcha(): mixed
    {
        $cpt = new \CCaptcha();
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
     * Валидация Captcha
     * @param $request
     * @param $params
     * @return bool
     */
    public static function validateCaptcha($request, $params): bool
    {
        if ($params["USE_BITRIX_CAPTCHA"] === "Y") {
            $captcha = new \CCaptcha();
            $captchaWord = $request->getPost("captcha_word");
            $captchaCode = $request->getPost("captcha_code");

            if (!$captcha->CheckCodeCrypt($captchaWord, $captchaCode)) {
                return false;
            }
        }

        if ($params["USE_GOOGLE_RECAPTCHA"] === "Y") {
            $recaptchaResponse = $request->getPost("g-recaptcha-response");

            if (empty($recaptchaResponse)) {
                return false;
            }

            $data = [
                "secret" => $params["GOOGLE_RECAPTCHA_SECRET_KEY"],
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
     * Проверка Rate Limiting
     * @param $iblockId
     * @param $limits
     * @return array
     */
    public static function validateLimits($iblockId, $limits): array
    {
        $rateLimiter = new RateLimiter($iblockId, $limits);

        return $rateLimiter->checkLimits();
    }

    /**
     * Валидация полей формы
     * @param $request
     * @param $params
     * @param $fileConfig
     * @param $iblockId
     * @return array
     */
    public static function validateForm($request, $params, $fileConfig, $iblockId): array
    {
        $errors = [];
        $properties = self::getIblockProperties($iblockId);

        // Проверка согласия на политику персональных данных
        if ($params["USE_PRIVACY_POLICY"] === "Y") {
            if (empty($formData["privacy_policy_agreement"]) || $formData["privacy_policy_agreement"] !== "Y") {
                $errors[] = "Необходимо дать согласие на обработку персональных данных";
            }
        }

        foreach ($properties as $property) {
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

            if (!empty($errors)) {
                LogHelper::error("form_" . $iblockId, "Form validation failed", ["iblock_id" => $iblockId, "errors" => $errors, "user_ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"]);
            }

            // Улучшенная валидация файлов
            if ($property["PROPERTY_TYPE"] === "F") {
                $fileErrors = self::validateFiles($fileConfig, $property, $iblockId);
                if (!empty($fileErrors)) {
                    $errors = array_merge($errors, $fileErrors);
                }
            }
        }

        return $errors;
    }

    /**
     * Валидация загружаемых файлов
     * @param $fileConfig
     * @param $property
     * @param $iblockId
     * @return array
     */
    private static function validateFiles($fileConfig, $property, $iblockId): array
    {
        $errors = [];
        $fileValidator = new FileSecurityValidator($fileConfig, $iblockId);
        $files = $_FILES["property_" . $property["ID"]] ?? null;

        if (!$files || empty($files["name"])) {
            return $errors;
        }

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

                $fileErrors = $fileValidator->validateFile($fileData, $property["NAME"]);
                $errors = array_merge($errors, $fileErrors);
            }
        } else {
            // Одиночный файл
            if (!empty($files["name"])) {
                $fileErrors = $fileValidator->validateFile($files, $property["NAME"]);
                $errors = array_merge($errors, $fileErrors);
            }
        }

        return $errors;
    }

    /**
     * Сохранение записи в инфоблок
     * @param $request
     * @param $params
     * @param $iblockId
     * @return mixed
     */
    public static function saveElement($request, $params, $iblockId): mixed
    {
        $element = new \CIBlockElement();

        $fields = [
            "IBLOCK_ID" => $iblockId,
            "NAME" => "Заявка от " . date("d.m.Y H:i:s"),
            "ACTIVE" => "Y",
            "PROPERTY_VALUES" => []
        ];

        foreach ($params["PROPERTIES"] as $property) {
            if ($property["PROPERTY_TYPE"] === "F") {
                // Обработка файловых полей
                $fileIds = self::processFileUpload($property, $iblockId);
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
                LogHelper::error("form_" . $iblockId, "Element save failed", ["error" => $error, "fields" => $fields]);
            }
        } else {
            LogHelper::info("form_" . $iblockId, "Element saved successfully", ["element_id" => $elementId, "properties_count" => count($fields["PROPERTY_VALUES"])]);
        }

        return $elementId;
    }

    /**
     * Обработка загрузки файлов
     * @param $property
     * @param $iblockId
     * @return array
     */
    private static function processFileUpload($property, $iblockId): array
    {
        $fileIds = [];
        $files = $_FILES["property_" . $property["ID"]] ?? null;

        if (!$files || empty($files["name"])) {
            return $fileIds;
        }

        // Создание папки для файлов если не существует
        $uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/upload/ddapp_forms/" . $iblockId . "/";
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

                $fileId = self::saveUploadedFile($fileData, $uploadDir, $iblockId);
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
                $fileId = self::saveUploadedFile($files, $uploadDir, $iblockId);
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

        LogHelper::info("form_" . $iblockId, "File upload completed", ["property_id" => $property["ID"], "success_count" => $successCount, "failure_count" => $failureCount, "uploaded_file_ids" => $fileIds]);

        return $fileIds;
    }

    /**
     * Сохранение загруженного файла
     * @param $fileData
     * @param $uploadDir
     * @param $iblockId
     * @return mixed
     */
    private static function saveUploadedFile($fileData, $uploadDir, $iblockId): mixed
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

            $fileId = \CFile::SaveFile($fileArray, "iblock");

            if ($fileId) {
                LogHelper::info("form_" . $iblockId, "File registered successfully", ["file_id" => $fileId, "original_name" => $fileData["name"], "size" => $fileData["size"]]);

                // Удаляем временный файл после регистрации
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            } else {
                LogHelper::error("form_" . $iblockId, "File registration failed", ["original_name" => $fileData["name"], "file_path" => $filePath]);

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
     * Отправка письма
     * @param $elementId
     * @param $request
     * @param $iblockId
     * @param $params
     * @return bool
     */
    public static function sendEmail($elementId, $request, $iblockId, $params): bool
    {
        $fields = [
            "ELEMENT_ID" => $elementId,
            "IBLOCK_ID" => $iblockId,
            "DATE_CREATE" => date("d.m.Y H:i:s")
        ];

        foreach ($params["PROPERTIES"] as $property) {
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
            "EVENT_NAME" => $params["EMAIL_TEMPLATE"],
            "LID" => SITE_ID,
            "MESSAGE_ID" => $params["EMAIL_TEMPLATE"],
            "FIELDS" => $fields
        ]);

        $success = !empty($result->isSuccess()) ? $result->isSuccess() : true;

        LogHelper::info("form_" . $iblockId, "Email sending attempt", ["element_id" => $elementId, "template_id" => $params["EMAIL_TEMPLATE"], "success" => $success]);

        return $success;
    }
}