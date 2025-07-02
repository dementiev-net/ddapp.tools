<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;

// Подключаем модуль инфоблоков
if (!Loader::includeModule("iblock")) {
    echo Json::encode(["status" => "error", "message" => "Модуль iblock не подключен"]);
    exit;
}

// Проверка сессии Bitrix
if (!check_bitrix_sessid()) {
    echo Json::encode(["status" => "error", "message" => "Ошибка сессии"]);
    exit;
}

$request = Application::getInstance()->getContext()->getRequest();

$step = (int)$request->getPost("step");
$totalExported = (int)$request->getPost("totalExported");
$iblockId = (int)$request->getPost("exportId");

// Можно задать размер порции на шаг
$stepSize = 500;

// Путь к папке и имя файла, куда будет сохраняться CSV
$csvFileDir = "/upload/export/";
$csvFileName = "export_iblock_{$iblockId}.csv";
$fileFullPath = $_SERVER["DOCUMENT_ROOT"] . $csvFileDir . $csvFileName;

// При первом шаге удаляем предыдущий файл
if($step === 0 && file_exists($fileFullPath)){
    unlink($fileFullPath);
}

// Проверка и создание директории для экспорта
if (!file_exists($_SERVER["DOCUMENT_ROOT"] . $csvFileDir)) {
    mkdir($_SERVER["DOCUMENT_ROOT"] . $csvFileDir, 0755, true);
}

// Открываем файл для записи или дописывания строк
$file = fopen($fileFullPath, ($step === 0 ? "w" : "a"));

$filter = [
    "IBLOCK_ID" => $iblockId
];

// Получаем общее число элементов инфоблока
$totalItems = CIBlockElement::GetList([], $filter, [], false);

// Теперь выборка элементов частями с оффсетом и лимитом
$navParams = [
    "nTopCount" => false,
    "iNumPage" => $step + 1, // страницы начинаются с 1
    "nPageSize" => $stepSize,
    "checkOutOfRange" => true
];

$res = CIBlockElement::GetList(["ID" => "ASC"], $filter, false, $navParams, [
    "ID", "NAME", "CODE", "DATE_CREATE", "ACTIVE", "IBLOCK_SECTION_ID"
]);

$currentExported = 0;
$errors = 0;

// Записываем шапку файла только на первом шаге
if ($step === 0) {
    $header = ["ID", "Название", "Символьный код", "Дата создания", "Активность", "Раздел"];
    fputcsv($file, $header, ";");
}

// Записываем данные элементов инфоблока в CSV
while ($element = $res->Fetch()) {
    $row = [
        $element["ID"],
        $element["NAME"],
        $element["CODE"],
        $element["DATE_CREATE"],
        $element["ACTIVE"],
        $element["IBLOCK_SECTION_ID"]
    ];

    if (!fputcsv($file, $row, ";")) {
        $errors++;
    } else {
        $currentExported++;
    }
}

fclose($file);

// Суммируем кол-во экспортированных элементов
$totalExported += $currentExported;

// Если экспортировано всё, возвращаем статус завершения
if ($totalExported >= $totalItems) {
    echo Json::encode([
        "status" => "done",
        "exported" => $totalExported,
        "total" => $totalItems,
        "errorsCount" => $errors,
        "fileUrl" => $csvFileDir . $csvFileName
    ]);
} else {
    echo Json::encode([
        "status" => "processing",
        "exported" => $totalExported,
        "total" => $totalItems,
        "errorsCount" => $errors
    ]);
}