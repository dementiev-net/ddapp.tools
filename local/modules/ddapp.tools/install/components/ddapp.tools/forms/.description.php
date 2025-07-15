<?php
// .description.php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
    "NAME" => "Форма обратной связи",
    "DESCRIPTION" => "Компонент для создания формы из свойств инфоблока",
    "ICON" => "/images/form.gif",
    "CACHE_PATH" => "Y",
    "PATH" => array(
        "ID" => "ddapp",
        "NAME" => "DDApp Tools",
        "CHILD" => array(
            "ID" => "forms",
            "NAME" => "Формы"
        )
    ),
);