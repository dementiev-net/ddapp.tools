<?php
namespace DDAPP\Tools\Components;

use DDAPP\Tools\Helpers\LogHelper;

class FileSecurityValidator
{
    private $config;
    private $formId;

    public function __construct($config, $formId)
    {
        $this->config = $config;
        $this->formId = $formId;
    }

    /**
     * Валидация файла с расширенными проверками
     */
    public function validateFile($fileData, $fieldName = "")
    {
        $errors = [];

        LogHelper::info("file_validator", "Starting enhanced file validation", [
            "file_name" => $fileData["name"],
            "file_size" => $fileData["size"],
            "form_id" => $this->formId,
            "field_name" => $fieldName
        ]);

        // Базовые проверки
        $errors = array_merge($errors, $this->validateBasic($fileData, $fieldName));

        // Проверка magic bytes
        if (empty($errors)) {
            $errors = array_merge($errors, $this->validateMagicBytes($fileData, $fieldName));
        }

        // Проверка содержимого
        if (empty($errors) && $this->config["content_checks"]["enabled"]) {
            $errors = array_merge($errors, $this->validateContent($fileData, $fieldName));
        }

        // Проверка метаданных для изображений
        if (empty($errors) && $this->isImage($fileData["name"])) {
            $errors = array_merge($errors, $this->validateImageMetadata($fileData, $fieldName));
        }

        if (!empty($errors)) {
            LogHelper::warning("file_validator", "File validation failed", [
                "file_name" => $fileData["name"],
                "errors" => $errors,
                "form_id" => $this->formId
            ]);
        } else {
            LogHelper::info("file_validator", "File validation passed", [
                "file_name" => $fileData["name"],
                "form_id" => $this->formId
            ]);
        }

        return $errors;
    }

    /**
     * Проверка magic bytes файла
     */
    private function validateMagicBytes($fileData, $fieldName)
    {
        $errors = [];
        $extension = strtolower(pathinfo($fileData["name"], PATHINFO_EXTENSION));

        if (!isset($this->config["mime_types"][$extension])) {
            return $errors;
        }

        // Читаем первые байты файла для определения типа
        $handle = fopen($fileData["tmp_name"], "rb");
        if (!$handle) {
            $errors[] = "Не удалось прочитать файл '{$fileData["name"]}'";
            return $errors;
        }

        $magicBytes = fread($handle, 16);
        fclose($handle);

        $detectedType = $this->detectFileTypeByMagicBytes($magicBytes);
        $allowedTypes = $this->getMagicBytesForExtension($extension);

        if (!in_array($detectedType, $allowedTypes)) {
            $errors[] = "Файл '{$fileData["name"]}' не соответствует заявленному типу";

            LogHelper::warning("file_validator", "Magic bytes mismatch", [
                "file_name" => $fileData["name"],
                "extension" => $extension,
                "detected_type" => $detectedType,
                "allowed_types" => $allowedTypes,
                "form_id" => $this->formId
            ]);
        }

        return $errors;
    }

    /**
     * Определение типа файла по magic bytes
     */
    private function detectFileTypeByMagicBytes($bytes)
    {
        $signatures = [
            "ffd8ff" => "jpeg",
            "89504e" => "png",
            "474946" => "gif",
            "255044" => "pdf",
            "504b03" => "zip",
            "526172" => "rar",
            "d0cf11" => "doc/xls",
            "504b07" => "docx/xlsx"
        ];

        $hex = bin2hex(substr($bytes, 0, 3));

        foreach ($signatures as $signature => $type) {
            if (strpos($hex, $signature) === 0) {
                return $type;
            }
        }

        return "unknown";
    }

    /**
     * Получение разрешенных magic bytes для расширения
     */
    private function getMagicBytesForExtension($extension)
    {
        $mapping = [
            "jpg" => ["jpeg"],
            "jpeg" => ["jpeg"],
            "png" => ["png"],
            "gif" => ["gif"],
            "pdf" => ["pdf"],
            "zip" => ["zip"],
            "doc" => ["doc/xls"],
            "docx" => ["docx/xlsx"],
            "xls" => ["doc/xls"],
            "xlsx" => ["docx/xlsx"]
        ];

        return $mapping[$extension] ?? [];
    }

    /**
     * Расширенная проверка содержимого
     */
    private function validateContent($fileData, $fieldName)
    {
        $errors = [];

        if ($fileData["size"] > $this->config["content_checks"]["max_check_size"]) {
            return $errors; // Файл слишком большой для проверки содержимого
        }

        $content = file_get_contents($fileData["tmp_name"], false, null, 0, $this->config["content_checks"]["check_first_bytes"]);

        // Проверка на вредоносные паттерны
        foreach ($this->config["malware_patterns"] as $pattern) {
            if (preg_match($pattern, $content)) {
                $errors[] = "Файл '{$fileData["name"]}' содержит потенциально опасный код";

                LogHelper::error("file_validator", "Malware pattern detected", [
                    "file_name" => $fileData["name"],
                    "pattern" => $pattern,
                    "form_id" => $this->formId,
                    "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"
                ]);

                $this->quarantineFile($fileData);
                break;
            }
        }

        return $errors;
    }

    /**
     * Проверка метаданных изображений
     */
    private function validateImageMetadata($fileData, $fieldName)
    {
        $errors = [];

        if (!$this->config["image_checks"]["enabled"]) {
            return $errors;
        }

        $imageInfo = getimagesize($fileData["tmp_name"]);

        if ($imageInfo === false) {
            $errors[] = "Файл '{$fileData["name"]}' не является корректным изображением";
            return $errors;
        }

        // Проверка размеров
        if ($imageInfo[0] > $this->config["image_checks"]["max_width"] ||
            $imageInfo[1] > $this->config["image_checks"]["max_height"]) {
            $errors[] = "Размер изображения '{$fileData["name"]}' превышает допустимый";
        }

        // Удаление EXIF данных если настроено
        if ($this->config["image_checks"]["strip_exif"]) {
            $this->stripExifData($fileData["tmp_name"]);
        }

        return $errors;
    }

    /**
     * Помещение файла в карантин
     */
    private function quarantineFile($fileData)
    {
        if (!$this->config["quarantine"]["enabled"]) {
            return;
        }

        $quarantineDir = $_SERVER["DOCUMENT_ROOT"] . $this->config["quarantine"]["directory"];

        if (!is_dir($quarantineDir)) {
            mkdir($quarantineDir, 0755, true);
        }

        $quarantineFile = $quarantineDir . date("Y-m-d_H-i-s") . "_" . $fileData["name"];

        if (copy($fileData["tmp_name"], $quarantineFile)) {
            LogHelper::warning("file_validator", "File quarantined", [
                "original_file" => $fileData["name"],
                "quarantine_path" => $quarantineFile,
                "form_id" => $this->formId,
                "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"
            ]);
        }
    }

    /**
     * Удаление EXIF данных из изображения
     */
    private function stripExifData($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ["jpg", "jpeg"])) {
            $image = imagecreatefromjpeg($filePath);
            if ($image !== false) {
                imagejpeg($image, $filePath, 90);
                imagedestroy($image);
            }
        }
    }

    /**
     * Проверка является ли файл изображением
     */
    private function isImage($fileName)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, ["jpg", "jpeg", "png", "gif", "webp"]);
    }

    /**
     * Базовые проверки файла
     */
    private function validateBasic($fileData, $fieldName)
    {
        $errors = [];

        // Проверка на ошибки загрузки
        if ($fileData["error"] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($fileData["error"], $fieldName);
            return $errors;
        }

        // Проверка размера
        if ($fileData["size"] > $this->config["max_file_size"]) {
            $maxSizeMB = round($this->config["max_file_size"] / 1024 / 1024, 1);
            $errors[] = "Размер файла '{$fileData["name"]}' превышает максимально допустимый ({$maxSizeMB} MB)";
        }

        // Проверка расширения
        $extension = strtolower(pathinfo($fileData["name"], PATHINFO_EXTENSION));

        if (in_array($extension, $this->config["forbidden_extensions"])) {
            $errors[] = "Тип файла '{$extension}' запрещен для загрузки";
        }

        if (!in_array($extension, $this->config["allowed_extensions"])) {
            $allowedList = implode(", ", $this->config["allowed_extensions"]);
            $errors[] = "Тип файла '{$extension}' не разрешен. Разрешенные типы: {$allowedList}";
        }

        return $errors;
    }

    private function getUploadErrorMessage($errorCode, $fieldName)
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => "Файл в поле '{$fieldName}' слишком большой",
            UPLOAD_ERR_FORM_SIZE => "Файл в поле '{$fieldName}' слишком большой",
            UPLOAD_ERR_PARTIAL => "Файл в поле '{$fieldName}' загружен частично",
            UPLOAD_ERR_NO_TMP_DIR => "Отсутствует временная папка для загрузки файла",
            UPLOAD_ERR_CANT_WRITE => "Ошибка записи файла на диск"
        ];

        return $messages[$errorCode] ?? "Ошибка загрузки файла в поле '{$fieldName}'";
    }
}