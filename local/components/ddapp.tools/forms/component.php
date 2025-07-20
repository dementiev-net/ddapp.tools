<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Генерируем уникальный ID для компонента
$arResult['COMPONENT_ID'] = 'forms_' . randString(8);
$arResult['BUTTON_TEXT'] = $arParams['BUTTON_TEXT'] ?: 'Открыть форму';
$arResult['MODAL_TITLE'] = $arParams['MODAL_TITLE'] ?: 'Проверка данных';
$arResult['INPUT_PLACEHOLDER'] = $arParams['INPUT_PLACEHOLDER'] ?: 'Введите значение';
$arResult['AJAX_URL'] = '/local/components/ddapp.tools/forms/ajax.php';
$arResult['TEMPLATE_NAME'] = $this->getTemplateName(); // Получаем имя текущего шаблона

// Подключаем основной шаблон
$this->IncludeComponentTemplate();
?>