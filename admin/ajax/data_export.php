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
use DD\Tools\Main;
use DD\Tools\DataExport;
use DD\Tools\Helpers\LogHelper;
use DD\Tools\Helpers\UserHelper;

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
$totalExported = (int)$request->getPost("totalExported");
$exportId = (int)$request->getPost("exportId");

// Размер порции
$stepSize = Option::get(Main::MODULE_ID, "export_step");

try {
    // Получаем настройки экспорта из таблицы
    $exportSettings = DataExport::getById($exportId);

    if (!$exportSettings) {
        echo Json::encode(["status" => "error", "message" => Loc::getMessage("DD_EXPORT_MESSAGE_ERROR_SETTINGS")]);
        exit;
    }

    $iblockId = $exportSettings["IBLOCK_ID"];
    $exportType = $exportSettings["EXPORT_TYPE"]; // csv или xls
    $settings = Json::decode($exportSettings["SETTINGS"]);

    // Получаем поля для экспорта в нужном порядке
    $exportFields = $settings["export_fields"] ?? ["ID", "NAME"];

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

    // При первом шаге удаляем предыдущий файл
    if ($step === 0 && file_exists($filePath)) {
        unlink($filePath);
    }

    // Проверка и создание директории для экспорта
    $fileDir = dirname($filePath);
    if (!file_exists($fileDir)) {
        mkdir($fileDir, 0755, true);
    }

    $filter = [
        "IBLOCK_ID" => $iblockId
    ];

    // Получаем общее число элементов инфоблока
    $totalItems = CIBlockElement::GetList([], $filter, [], false);

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

    $res = CIBlockElement::GetList(
        ["ID" => "ASC"],
        $filter,
        false,
        $navParams,
        $selectFields
    );

    $currentExported = 0;
    $errors = 0;

    if ($exportType === "csv") {
        // CSV экспорт
        $file = fopen($filePath, ($step === 0 ? "w" : "a"));

        // Записываем шапку только на первом шаге, если включены заголовки
        if ($step === 0 && $showHeaders) {
            fputcsv($file, $exportFields, $delimiter);
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
            } else {
                $currentExported++;
            }
        }

        fclose($file);

    } else {
        // Excel экспорт
        if ($step === 0) {
            // Создаем новый файл только на первом шаге
            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($filePath);

            // Добавляем заголовки если нужно
            if ($showHeaders) {
                $headerCells = [];
                foreach ($exportFields as $field) {
                    $headerCells[] = WriterEntityFactory::createCell($field);
                }
                $headerRow = WriterEntityFactory::createRow($headerCells);
                $writer->addRow($headerRow);
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
                }
            }

            // Закрываем writer для первого шага
            $writer->close();
        } else {
            // Для последующих шагов читаем существующий файл и дописываем данные
            $tempFilePath = $filePath . ".tmp";

            // Создаем новый writer для временного файла
            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($tempFilePath);

            // Копируем данные из существующего файла
            if (file_exists($filePath)) {
                $reader = ReaderEntityFactory::createXLSXReader();
                $reader->open($filePath);

                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $cells = [];
                        foreach ($row->getCells() as $cell) {
                            $cells[] = WriterEntityFactory::createCell($cell->getValue());
                        }
                        $newRow = WriterEntityFactory::createRow($cells);
                        $writer->addRow($newRow);
                    }
                }

                $reader->close();
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
                }
            }

            // Закрываем writer
            $writer->close();

            // Заменяем оригинальный файл временным
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            rename($tempFilePath, $filePath);
        }
    }

    // Суммируем количество экспортированных элементов
    $totalExported += $currentExported;

    // Если экспортировано всё, завершаем процесс
    if ($totalExported >= $totalItems) {
        echo Json::encode([
            "status" => "done",
            "exported" => $totalExported,
            "total" => $totalItems,
            "errorsCount" => $errors,
            "fileUrl" => str_replace($_SERVER["DOCUMENT_ROOT"], "", $filePath),
            "exportType" => $exportType
        ]);
    } else {
        echo Json::encode([
            "status" => "processing",
            "exported" => $totalExported,
            "total" => $totalItems,
            "errorsCount" => $errors,
            "step" => $step + 1
        ]);
    }

} catch (Exception $e) {
    echo Json::encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}