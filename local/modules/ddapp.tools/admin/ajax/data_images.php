<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use DDAPP\Tools\Main;
use DDAPP\Tools\DataImages;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\UserHelper;

// Подключаем модуль инфоблоков
Loc::loadMessages(__FILE__);
Loader::includeModule("iblock");

// Проверка сессии Bitrix
if (!check_bitrix_sessid()) {
    echo json_encode(["status" => "error", "message" => Loc::getMessage("ACCESS_DENIED")]);
    exit;
}

// Проверка доступа
if (UserHelper::hasModuleAccess("") != "W") {
    echo json_encode(["status" => "error", "message" => Loc::getMessage("ACCESS_DENIED")]);
    exit;
}

// Настройка логирования
LogHelper::configure();

$request = Application::getInstance()->getContext()->getRequest();

$step = (int)$request->getPost("step");
$totalUploaded = (int)$request->getPost("totalUploaded");
$uploadId = (int)$request->getPost("uploadId");

// Размер порции
$stepSize = Option::get(Main::MODULE_ID, "upload_step");

try {
    // Получаем настройки загрузки из таблицы
    $uploadSettings = DataImages::getById($uploadId);

    if (!$uploadSettings) {
        LogHelper::error("images", "Upload settings not found", ["uploadId" => $uploadId]);
        echo Json::encode(["status" => "error", "message" => Loc::getMessage("DDAPP_IMAGES_MESSAGE_ERROR_SETTINGS")]);
        exit;
    }

    LogHelper::info("images", "Starting image upload process", ["uploadId" => $uploadId, "step" => $step, "iblockId" => $uploadSettings["IBLOCK_ID"], "zipFile" => $uploadSettings["ZIP_FILE"]]);

    $iblockId = $uploadSettings["IBLOCK_ID"];
    $iblockTypeId = $uploadSettings["IBLOCK_TYPE_ID"];
    $zipFilePath = $_SERVER["DOCUMENT_ROOT"] . $uploadSettings["ZIP_FILE"];
    $settings = Json::decode($uploadSettings["SETTINGS"]);

    $imagesField = $settings["images_field"] ?? "DETAIL_PICTURE";
    $imagesCode = $settings["images_code"] ?? "";

    // Проверяем существование ZIP файла
    if (!file_exists($zipFilePath)) {
        LogHelper::error("images", "ZIP file not found", ["zipFilePath" => $zipFilePath]);
        echo Json::encode(["status" => "error", "message" => "ZIP файл не найден: " . $zipFilePath]);
        exit;
    }

    // Создаем временную папку для распаковки
    $tempDir = $_SERVER["DOCUMENT_ROOT"] . "/upload/temp/" . uniqid("images_");
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    // Распаковка ZIP архива
    $zip = new ZipArchive;
    if ($zip->open($zipFilePath) !== TRUE) {
        LogHelper::error("images", "Failed to open ZIP archive", ["zipFilePath" => $zipFilePath]);
        echo Json::encode(["status" => "error", "message" => "Не удалось открыть ZIP архив"]);
        exit;
    }

    $zip->extractTo($tempDir);
    $zip->close();

    LogHelper::info("images", "ZIP archive extracted", ["zipFilePath" => $zipFilePath, "tempDir" => $tempDir]);

    // Получаем список файлов из архива
    $files = scandir($tempDir);
    $imageFiles = [];

    foreach ($files as $file) {
        if ($file == "." || $file == "..") continue;

        $pathInfo = pathinfo($file);
        $extension = strtolower($pathInfo["extension"]);

        // Проверяем, что это изображение
        if (in_array($extension, ["jpg", "jpeg", "png", "gif", "bmp", "webp"])) {
            $imageFiles[] = $file;
        }
    }

    // Группируем файлы по кодам
    $groupedImages = [];
    foreach ($imageFiles as $file) {
        $pathInfo = pathinfo($file);
        $filename = $pathInfo["filename"];

        // Проверяем, начинается ли файл с нашего кода
        if (strpos($filename, $imagesCode) === 0) {
            $key = $imagesCode;

            // Проверяем, есть ли суффикс _1, _2, _3 и т.д.
            $pattern = "/^" . preg_quote($imagesCode, "/") . "(_(\d+))?$/";
            if (preg_match($pattern, $filename, $matches)) {
                $suffix = isset($matches[2]) ? (int)$matches[2] : 0;

                if (!isset($groupedImages[$key])) {
                    $groupedImages[$key] = [];
                }

                $groupedImages[$key][$suffix] = $file;
            }
        }
    }

    // Сортируем изображения по индексу для каждого кода
    foreach ($groupedImages as $code => &$images) {
        ksort($images);
    }

    // Проверяем, есть ли изображения в архиве
    if (empty($groupedImages)) {
        LogHelper::error("images", "No images found in archive", ["imagesCode" => $imagesCode, "totalFiles" => count($imageFiles)]);

        // Очищаем временную папку
        $tempFiles = scandir($tempDir);
        foreach ($tempFiles as $file) {
            if ($file != "." && $file != "..") {
                unlink($tempDir . "/" . $file);
            }
        }
        rmdir($tempDir);

        echo Json::encode([
            "status" => "error",
            "message" => "В архиве не найдено изображений с кодом: " . $imagesCode
        ]);
        exit;
    }

    LogHelper::info("images", "Images grouped by code", ["imagesCode" => $imagesCode, "groupsCount" => count($groupedImages), "totalImages" => array_sum(array_map("count", $groupedImages))]);

    // Получаем информацию о поле (множественное или нет)
    $isMultipleField = false;
    $rsProperty = CIBlockProperty::GetByID($imagesField, $iblockId);
    if ($arProperty = $rsProperty->Fetch()) {
        $isMultipleField = $arProperty["MULTIPLE"] === "Y";
    } else {
        // Если это не свойство, проверяем стандартные поля
        $standardFields = ["DETAIL_PICTURE", "PREVIEW_PICTURE"];
        if (in_array($imagesField, $standardFields)) {
            $isMultipleField = false; // Стандартные поля картинок не множественные
        }
    }

    // Получаем элементы инфоблока с нужным кодом
    $filter = [
        "IBLOCK_ID" => $iblockId,
        "CODE" => $imagesCode
    ];

    $currentUploaded = 0;
    $errors = 0;

    // Навигация для порционной обработки
    $navParams = [
        "nTopCount" => false,
        "iNumPage" => $step + 1,
        "nPageSize" => $stepSize,
        "checkOutOfRange" => true
    ];

    $res = CIBlockElement::GetList(
        ["ID" => "ASC"],
        $filter,
        false,
        $navParams,
        ["ID", "CODE", "NAME"]
    );

    $totalItems = CIBlockElement::GetList([], $filter, [], false);

    while ($element = $res->Fetch()) {
        $elementId = $element["ID"];
        $elementCode = $element["CODE"];

        // Проверяем, есть ли изображения для этого элемента
        if (!isset($groupedImages[$elementCode])) {
            continue;
        }

        $images = $groupedImages[$elementCode];
        $uploadedImages = [];

        // Загружаем изображения
        foreach ($images as $index => $imageFile) {
            $imagePath = $tempDir . "/" . $imageFile;

            if (!file_exists($imagePath)) {
                continue;
            }

            // Загружаем файл в Bitrix
            $fileArray = CFile::MakeFileArray($imagePath);
            $fileArray["MODULE_ID"] = "iblock";

            if ($fileId = CFile::SaveFile($fileArray, "iblock")) {
                $uploadedImages[] = $fileId;
            }
        }

        if (empty($uploadedImages)) {
            continue;
        }

        // Подготавливаем данные для обновления
        $arFields = [];

        if (in_array($imagesField, ["DETAIL_PICTURE", "PREVIEW_PICTURE"])) {
            // Стандартные поля картинок
            $arFields[$imagesField] = $uploadedImages[0]; // Берем только первую картинку
        } else {
            // Свойство инфоблока
            if ($isMultipleField) {
                $arFields["PROPERTY_VALUES"][$imagesField] = $uploadedImages;
            } else {
                $arFields["PROPERTY_VALUES"][$imagesField] = $uploadedImages[0];
            }
        }

        // Обновляем элемент
        $el = new CIBlockElement;
        if ($el->Update($elementId, $arFields)) {
            $currentUploaded++;
            LogHelper::info("images", "Element updated successfully", ["elementId" => $elementId, "elementCode" => $elementCode, "imagesCount" => count($uploadedImages), "field" => $imagesField]);
        } else {
            $errors++;
            LogHelper::error("images", "Error updating element", ["elementId" => $elementId, "elementCode" => $elementCode, "error" => $el->LAST_ERROR]);
        }
    }

    // Суммируем количество обработанных элементов
    $totalUploaded += $currentUploaded;

    // Очищаем временную папку для текущего шага
    $tempFiles = scandir($tempDir);
    foreach ($tempFiles as $file) {
        if ($file != "." && $file != "..") {
            unlink($tempDir . "/" . $file);
        }
    }

    // Если обработано всё, завершаем процесс
    if ($totalUploaded >= $totalItems) {
        // Удаляем временную папку
        if (file_exists($tempDir)) {
            rmdir($tempDir);
        }

        // Удаляем ZIP файл
        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        LogHelper::info("images", "Image upload process completed", ["uploadId" => $uploadId, "totalUploaded" => $totalUploaded, "totalItems" => $totalItems, "errors" => $errors]);

        echo Json::encode([
            "status" => "done",
            "uploaded" => $totalUploaded,
            "total" => $totalItems,
            "errorsCount" => $errors,
            "message" => "Загрузка изображений завершена"
        ]);
    } else {
        LogHelper::info("images", "Processing step completed", ["uploadId" => $uploadId, "step" => $step, "currentUploaded" => $currentUploaded, "totalUploaded" => $totalUploaded, "totalItems" => $totalItems, "errors" => $errors]);

        echo Json::encode([
            "status" => "processing",
            "uploaded" => $totalUploaded,
            "total" => $totalItems,
            "errorsCount" => $errors,
            "step" => $step + 1
        ]);
    }

} catch (Exception $e) {
    LogHelper::error("images", "Exception occurred during image upload", ["uploadId" => $uploadId, "step" => $step, "error" => $e->getMessage(), "file" => $e->getFile(), "line" => $e->getLine()]);
    echo Json::encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

    // Очищаем временные файлы в случае ошибки
    if (isset($tempDir) && file_exists($tempDir)) {
        $tempFiles = scandir($tempDir);
        foreach ($tempFiles as $file) {
            if ($file != "." && $file != "..") {
                unlink($tempDir . "/" . $file);
            }
        }
        rmdir($tempDir);
    }
}