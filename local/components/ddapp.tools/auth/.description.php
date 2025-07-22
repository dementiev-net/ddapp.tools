<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
    "NAME" => "Формы авторизации",
    "DESCRIPTION" => "Компонент для создания модальных форм авторизации, регистрации и восстановления пароля",
    "ICON" => "/images/auth.gif",
    "CACHE_PATH" => "Y",
    "PATH" => array(
        "ID" => "ddapp",
        "NAME" => "DDApp Tools",
        "CHILD" => array(
            "ID" => "auth",
            "NAME" => "Авторизация"
        )
    ),
);