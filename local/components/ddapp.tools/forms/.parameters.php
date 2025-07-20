<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = array(
    "PARAMETERS" => array(
        "BUTTON_TEXT" => array(
            "PARENT" => "BASE",
            "NAME" => "Текст кнопки",
            "TYPE" => "STRING",
            "DEFAULT" => "Открыть форму"
        ),
        "MODAL_TITLE" => array(
            "PARENT" => "BASE",
            "NAME" => "Заголовок модального окна",
            "TYPE" => "STRING",
            "DEFAULT" => "Проверка данных"
        ),
        "INPUT_PLACEHOLDER" => array(
            "PARENT" => "BASE",
            "NAME" => "Placeholder для поля ввода",
            "TYPE" => "STRING",
            "DEFAULT" => "Введите значение"
        )
    )
);
?>