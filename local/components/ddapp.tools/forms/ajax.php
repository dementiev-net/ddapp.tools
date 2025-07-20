<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Page\Asset;

//if ($arParams['USE_SCRIPT_1'] === 'Y') {
//    Asset::getInstance()->addJs($templateFolder . '/_script.js');
//}

// Проверяем, что это AJAX запрос
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die('Access denied');
}

$request = Application::getInstance()->getContext()->getRequest();

// Логируем входящие данные для отладки
error_log('AJAX Request received');
error_log('POST data: ' . print_r($_POST, true));
error_log('Is AJAX: ' . ($request->isAjaxRequest() ? 'yes' : 'no'));

if (!$request->isAjaxRequest()) {
    http_response_code(400);
    echo json_encode(['error' => 'Only AJAX requests allowed']);
    die();
}

$action = $request->getPost('component_action');
$componentId = $request->getPost('component_id');

error_log('Action: ' . $action);
error_log('Component ID: ' . $componentId);

if (!$action || !$componentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    die();
}

if ($action === 'check_value') {
    $value = trim($request->getPost('value'));

    $response = array(
        'status' => 'error',
        'message' => ''
    );

    if (empty($value)) {
        $response['message'] = 'Поле не может быть пустым!';
    } else {
        $response['status'] = 'success';
        $response['message'] = 'Проверка прошла успешно!';
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    die();
}

if ($action === 'load_modal') {
    $modalTitle = $request->getPost('modal_title') ?: 'Проверка данных';
    $inputPlaceholder = $request->getPost('input_placeholder') ?: 'Введите значение';
    $templateName = $request->getPost('template') ?: '.default';

    error_log('Modal title: ' . $modalTitle);
    error_log('Input placeholder: ' . $inputPlaceholder);
    error_log('Template: ' . $templateName);

    // Устанавливаем переменные для шаблона
    $arResult = array(
        'COMPONENT_ID' => $componentId,
        'MODAL_TITLE' => $modalTitle,
        'INPUT_PLACEHOLDER' => $inputPlaceholder
    );

    // Определяем путь к шаблону модального окна
    $modalTemplatePath = __DIR__ . '/templates/' . $templateName . '/modal.php';

    // Проверяем существование файла шаблона
    if (!file_exists($modalTemplatePath)) {
        // Fallback на дефолтный шаблон
        $modalTemplatePath = __DIR__ . '/templates/.default/modal.php';
        error_log('Template not found, using default: ' . $modalTemplatePath);
    }

    if (!file_exists($modalTemplatePath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Modal template not found']);
        die();
    }

    // Получаем HTML из шаблона
    ob_start();
    include($modalTemplatePath);
    $modalHtml = ob_get_clean();

    if (empty($modalHtml)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to render modal template']);
        die();
    }

    $response = array(
        'status' => 'success',
        'html' => $modalHtml
    );

    error_log('Sending response: ' . json_encode($response));

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    die();
}

// Если действие не распознано
http_response_code(400);
echo json_encode(['error' => 'Unknown action: ' . $action]);
die();