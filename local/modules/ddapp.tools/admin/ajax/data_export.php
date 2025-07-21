<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use DDAPP\Tools\Main;
use DDAPP\Tools\DataExport;
use DDAPP\Tools\Helpers\LogHelper;
use DDAPP\Tools\Helpers\UserHelper;

// Подключаем модуль инфоблоков
Loc::loadMessages(__FILE__);
Loader::includeModule("iblock");

header("Content-Type: application/json; charset=utf-8");

// Проверка сессии Bitrix
if (!check_bitrix_sessid()) {
    echo Json::encode(["status" => "error", "message" => Loc::getMessage("ACCESS_DENIED")]);
    exit;
}

// Проверка доступа
if (UserHelper::hasModuleAccess("") != "W") {
    echo Json::encode(["status" => "error", "message" => Loc::getMessage("ACCESS_DENIED")]);
    exit;
}

// Настройка логирования
LogHelper::configure();

$request = Application::getInstance()->getContext()->getRequest();

$step = (int)$request->getPost("step");
$totalExported = (int)$request->getPost("totalExported");
$exportId = (int)$request->getPost("exportId");

// Размер порции
$stepSize = Option::get(Main::MODULE_ID, "export_step");

LogHelper::info("export", "Export step started", ["step" => $step, "export_id" => $exportId, "total_exported" => $totalExported, "step_size" => $stepSize, "user_id" => $GLOBALS["USER"]->GetID()]);

try {
    // Получаем настройки экспорта из таблицы
    $exportSettings = DataExport::getById($exportId);

    if (!$exportSettings) {
        LogHelper::error("export", "Export settings not found", ["export_id" => $exportId]);
        echo Json::encode(["status" => "error", "message" => Loc::getMessage("DDAPP_EXPORT_MESSAGE_ERROR_SETTINGS")]);
        exit;
    }

    $iblockId = $exportSettings["IBLOCK_ID"];
    $exportType = $exportSettings["EXPORT_TYPE"]; // csv или xls
    $settings = Json::decode($exportSettings["SETTINGS"]);

    LogHelper::info("export", "Export settings loaded", ["export_id" => $exportId, "iblock_id" => $iblockId, "export_type" => $exportType, "settings_count" => count($settings)]);

    // Получаем поля для экспорта в нужном порядке
    $exportFields = $settings["export_fields"] ?? ["ID", "NAME"];

    LogHelper::info("export", "Export fields prepared", ["export_id" => $exportId, "fields_count" => count($exportFields), "fields" => $exportFields]);

    // Определяем путь к файлу в зависимости от типа экспорта
    if ($exportType === "csv") {
        $filePath = $_SERVER["DOCUMENT_ROOT"] . $settings["csv_path"];
        $delimiter = $settings["csv_delimiter"] ?? ",";
        $multiDelimiter = $settings["csv_multi_delimiter"] ?? ",";
        $showHeaders = $settings["csv_headers"] === "Y";
    } else {
        $filePath = $_SERVER["DOCUMENT_ROOT"] . $settings["excel_path"];
        $multiDelimiter = $settings["excel_multi_delimiter"] ?? ",";
        $showHeaders = $settings["excel_headers"] === "Y";
    }

    LogHelper::info("export", "File path determined", ["export_id" => $exportId, "file_path" => $filePath, "export_type" => $exportType, "show_headers" => $showHeaders]);

    // При первом шаге удаляем предыдущий файл
    if ($step === 0 && file_exists($filePath)) {
        if (unlink($filePath)) {
            LogHelper::info("export", "Previous export file deleted", ["export_id" => $exportId, "file_path" => $filePath]);
        } else {
            LogHelper::warning("export", "Failed to delete previous export file", ["export_id" => $exportId, "file_path" => $filePath]);
        }
    }

    // Проверка и создание директории для экспорта
    $fileDir = dirname($filePath);
    if (!file_exists($fileDir)) {
        if (mkdir($fileDir, 0755, true)) {
            LogHelper::info("export", "Export directory created", ["export_id" => $exportId, "directory" => $fileDir]);
        } else {
            LogHelper::error("export", "Failed to create export directory", ["export_id" => $exportId, "directory" => $fileDir]);
            throw new Exception("Failed to create export directory");
        }
    }

    $filter = [
        "IBLOCK_ID" => $iblockId
    ];

    // Получаем общее число элементов инфоблока
    $totalItems = CIBlockElement::GetList([], $filter, [], false);

    LogHelper::info("export", "Total items count retrieved", ["export_id" => $exportId, "iblock_id" => $iblockId, "total_items" => $totalItems]);

    // Параметры навигации для порционной выборки
    $navParams = [
        "nTopCount" => false,
        "iNumPage" => $step + 1,
        "nPageSize" => $stepSize,
        "checkOutOfRange" => true
    ];

    // Формируем список полей для SELECT в зависимости от export_fields
    $selectFields = [];
    $propertyFields = []; // Поля свойств для дополнительного получения

    foreach ($exportFields as $field) {
        if (strpos($field, "PROPERTY_") === 0) {
            $selectFields[] = $field;
            $propertyFields[] = $field;
        } else {
            $selectFields[] = $field;
        }
    }

    LogHelper::info("export", "Select fields prepared", ["export_id" => $exportId, "select_fields" => $selectFields, "property_fields_count" => count($propertyFields)]);

    $res = CIBlockElement::GetList(
        ["ID" => "ASC"],
        $filter,
        false,
        $navParams,
        $selectFields
    );

    if (!$res) {
        LogHelper::error("export", "Failed to get elements from iblock", ["export_id" => $exportId, "iblock_id" => $iblockId, "step" => $step]);
        throw new Exception("Failed to get elements from iblock");
    }

    $currentExported = 0;
    $errors = 0;

    if ($exportType === "csv") {
        LogHelper::info("export", "Starting CSV export", ["export_id" => $exportId, "step" => $step, "file_path" => $filePath]);

        // CSV экспорт
        $file = fopen($filePath, ($step === 0 ? "w" : "a"));

        if (!$file) {
            LogHelper::error("export", "Failed to open CSV file for writing", ["export_id" => $exportId, "file_path" => $filePath, "mode" => ($step === 0 ? "w" : "a")]);
            throw new Exception("Failed to open CSV file for writing");
        }

        // Записываем шапку только на первом шаге, если включены заголовки
        if ($step === 0 && $showHeaders) {
            if (fputcsv($file, $exportFields, $delimiter)) {
                LogHelper::info("export", "CSV headers written", ["export_id" => $exportId, "headers" => $exportFields]);
            } else {
                LogHelper::error("export", "Failed to write CSV headers", ["export_id" => $exportId, "headers" => $exportFields]);
            }
        }

        // Записываем данные элементов
        while ($element = $res->Fetch()) {
            $row = [];
            foreach ($exportFields as $field) {
                $value = $element[$field] ?? "";

                // Если это поле свойства и значение является массивом
                if (is_array($value)) {
                    $value = implode($multiDelimiter, $value);
                } // Если это множественное свойство, получаем все значения
                elseif (strpos($field, "PROPERTY_") === 0) {
                    $propertyValues = [];
                    $propRes = CIBlockElement::GetProperty($iblockId, $element["ID"], [], ["CODE" => str_replace("PROPERTY_", "", $field)]);
                    while ($propVal = $propRes->Fetch()) {
                        if (!empty($propVal["VALUE"])) {
                            $propertyValues[] = $propVal["VALUE"];
                        }
                    }
                    if (!empty($propertyValues)) {
                        $value = implode($multiDelimiter, $propertyValues);
                    }
                }

                $row[] = $value;
            }

            if (!fputcsv($file, $row, $delimiter)) {
                $errors++;
                LogHelper::error("export", "Failed to write CSV row", ["export_id" => $exportId, "element_id" => $element["ID"], "step" => $step]);
            } else {
                $currentExported++;
            }
        }

        fclose($file);

        LogHelper::info("export", "CSV export step completed", ["export_id" => $exportId, "step" => $step, "current_exported" => $currentExported, "errors" => $errors]);

    } else {
        LogHelper::info("export", "Starting Excel export", ["export_id" => $exportId, "step" => $step, "file_path" => $filePath]);

        // Excel экспорт
        if ($step === 0) {
            // Создаем новый файл только на первом шаге
            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($filePath);

            LogHelper::info("export", "Excel writer created for first step", ["export_id" => $exportId, "file_path" => $filePath]);

            // Добавляем заголовки если нужно
            if ($showHeaders) {
                $headerCells = [];
                foreach ($exportFields as $field) {
                    $headerCells[] = WriterEntityFactory::createCell($field);
                }
                $headerRow = WriterEntityFactory::createRow($headerCells);
                $writer->addRow($headerRow);

                LogHelper::info("export", "Excel headers added", ["export_id" => $exportId, "headers" => $exportFields]);
            }

            // Записываем данные элементов для первого шага
            while ($element = $res->Fetch()) {
                $cells = [];
                foreach ($exportFields as $field) {
                    $value = $element[$field] ?? "";

                    // Если это поле свойства и значение является массивом
                    if (is_array($value)) {
                        $value = implode($multiDelimiter, $value);
                    } // Если это множественное свойство, получаем все значения
                    elseif (strpos($field, "PROPERTY_") === 0) {
                        $propertyValues = [];
                        $propRes = CIBlockElement::GetProperty($iblockId, $element["ID"], [], ["CODE" => str_replace("PROPERTY_", "", $field)]);
                        while ($propVal = $propRes->Fetch()) {
                            if (!empty($propVal["VALUE"])) {
                                $propertyValues[] = $propVal["VALUE"];
                            }
                        }
                        if (!empty($propertyValues)) {
                            $value = implode($multiDelimiter, $propertyValues);
                        }
                    }

                    $cells[] = WriterEntityFactory::createCell($value);
                }

                $row = WriterEntityFactory::createRow($cells);
                try {
                    $writer->addRow($row);
                    $currentExported++;
                } catch (Exception $e) {
                    $errors++;
                    LogHelper::error("export", "Failed to write Excel row", ["export_id" => $exportId, "element_id" => $element["ID"], "step" => $step, "error" => $e->getMessage()]);
                }
            }

            // Закрываем writer для первого шага
            $writer->close();

            LogHelper::info("export", "Excel export first step completed", ["export_id" => $exportId, "current_exported" => $currentExported, "errors" => $errors]);

        } else {
            // Для последующих шагов читаем существующий файл и дописываем данные
            $tempFilePath = $filePath . ".tmp";

            LogHelper::info("export", "Starting Excel append operation", ["export_id" => $exportId, "step" => $step, "temp_file" => $tempFilePath]);

            // Создаем новый writer для временного файла
            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($tempFilePath);

            // Копируем данные из существующего файла
            if (file_exists($filePath)) {
                $reader = ReaderEntityFactory::createXLSXReader();
                $reader->open($filePath);

                $rowCount = 0;
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $cells = [];
                        foreach ($row->getCells() as $cell) {
                            $cells[] = WriterEntityFactory::createCell($cell->getValue());
                        }
                        $newRow = WriterEntityFactory::createRow($cells);
                        $writer->addRow($newRow);
                        $rowCount++;
                    }
                }

                $reader->close();

                LogHelper::info("export", "Existing Excel data copied", ["export_id" => $exportId, "step" => $step, "rows_copied" => $rowCount]);
            }

            // Добавляем новые данные
            while ($element = $res->Fetch()) {
                $cells = [];
                foreach ($exportFields as $field) {
                    $value = $element[$field] ?? "";

                    // Если это поле свойства и значение является массивом
                    if (is_array($value)) {
                        $value = implode($multiDelimiter, $value);
                    } // Если это множественное свойство, получаем все значения
                    elseif (strpos($field, "PROPERTY_") === 0) {
                        $propertyValues = [];
                        $propRes = CIBlockElement::GetProperty($iblockId, $element["ID"], [], ["CODE" => str_replace("PROPERTY_", "", $field)]);
                        while ($propVal = $propRes->Fetch()) {
                            if (!empty($propVal["VALUE"])) {
                                $propertyValues[] = $propVal["VALUE"];
                            }
                        }
                        if (!empty($propertyValues)) {
                            $value = implode($multiDelimiter, $propertyValues);
                        }
                    }

                    $cells[] = WriterEntityFactory::createCell($value);
                }

                $row = WriterEntityFactory::createRow($cells);
                try {
                    $writer->addRow($row);
                    $currentExported++;
                } catch (Exception $e) {
                    $errors++;
                    LogHelper::error("export", "Failed to write Excel row in append mode", ["export_id" => $exportId, "element_id" => $element["ID"], "step" => $step, "error" => $e->getMessage()]);
                }
            }

            // Закрываем writer
            $writer->close();

            // Заменяем оригинальный файл временным
            if (file_exists($filePath)) {
                if (unlink($filePath)) {
                    LogHelper::info("export", "Original Excel file removed for replacement", ["export_id" => $exportId, "file_path" => $filePath]);
                } else {
                    LogHelper::warning("export", "Failed to remove original Excel file", ["export_id" => $exportId, "file_path" => $filePath]);
                }
            }

            if (rename($tempFilePath, $filePath)) {
                LogHelper::info("export", "Temporary Excel file renamed to original", ["export_id" => $exportId, "temp_file" => $tempFilePath, "final_file" => $filePath]);
            } else {
                LogHelper::error("export", "Failed to rename temporary Excel file", ["export_id" => $exportId, "temp_file" => $tempFilePath, "final_file" => $filePath]);
                throw new Exception("Failed to rename temporary Excel file");
            }

            LogHelper::info("export", "Excel export step completed", ["export_id" => $exportId, "step" => $step, "current_exported" => $currentExported, "errors" => $errors]);
        }
    }

    // Суммируем количество экспортированных элементов
    $totalExported += $currentExported;

    LogHelper::info("export", "Export step summary", ["export_id" => $exportId, "step" => $step, "current_exported" => $currentExported, "total_exported" => $totalExported, "total_items" => $totalItems, "errors" => $errors, "progress_percent" => round(($totalExported / $totalItems) * 100, 2)]);

    // Если экспортировано всё, завершаем процесс
    if ($totalExported >= $totalItems) {
        LogHelper::info("export", "Export completed successfully", ["export_id" => $exportId, "total_exported" => $totalExported, "total_items" => $totalItems, "errors_count" => $errors, "export_type" => $exportType, "file_url" => str_replace($_SERVER["DOCUMENT_ROOT"], "", $filePath), "user_id" => $GLOBALS["USER"]->GetID()]);
        echo Json::encode([
            "status" => "done",
            "exported" => $totalExported,
            "total" => $totalItems,
            "errorsCount" => $errors,
            "fileUrl" => str_replace($_SERVER["DOCUMENT_ROOT"], "", $filePath),
            "exportType" => $exportType
        ]);
    } else {
        LogHelper::info("export", "Export step completed, continuing", ["export_id" => $exportId, "step" => $step, "next_step" => $step + 1, "total_exported" => $totalExported, "total_items" => $totalItems, "errors_count" => $errors, "remaining_items" => $totalItems - $totalExported]);
        echo Json::encode([
            "status" => "processing",
            "exported" => $totalExported,
            "total" => $totalItems,
            "errorsCount" => $errors,
            "step" => $step + 1
        ]);
    }

} catch (Exception $e) {
    LogHelper::error("export", "Export failed with exception", ["export_id" => $exportId, "step" => $step, "error_message" => $e->getMessage(), "error_trace" => $e->getTraceAsString(), "user_id" => $GLOBALS["USER"]->GetID()]);
    echo Json::encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}