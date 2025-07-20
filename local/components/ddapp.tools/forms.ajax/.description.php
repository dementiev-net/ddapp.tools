<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
    "NAME" => "Кнопка формы обратной связи",
    "DESCRIPTION" => "Легкий компонент-обертка для динамической загрузки формы через AJAX",
    "ICON" => "/images/form_button.gif",
    "CACHE_PATH" => "Y",
    "PATH" => array(
        "ID" => "ddapp",
        "NAME" => "DDApp Tools",
        "CHILD" => array(
            "ID" => "forms",
            "NAME" => "Формы",
            "CHILD" => array(
                "ID" => "button",
                "NAME" => "Кнопки форм"
            )
        )
    ),
);