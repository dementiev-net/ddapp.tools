<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = array(
    "GROUPS" => array(
        "BUTTONS" => array(
            "NAME" => Loc::getMessage("DDAPP_AUTH_GROUP_BUTTONS"),
        ),
        "SETTINGS" => array(
            "NAME" => Loc::getMessage("DDAPP_AUTH_GROUP_SETTINGS"),
        ),
        "CAPTCHA" => array(
            "NAME" => Loc::getMessage("DDAPP_AUTH_GROUP_CAPTCHA"),
        ),
        "FIELDS" => array(
            "NAME" => Loc::getMessage("DDAPP_AUTH_GROUP_FIELDS"),
        ),
        "REDIRECTS" => array(
            "NAME" => Loc::getMessage("DDAPP_AUTH_GROUP_REDIRECTS"),
        ),
    ),
    "PARAMETERS" => array(
        // Настройки кнопок
        "LOGIN_BUTTON_TEXT" => array(
            "PARENT" => "BUTTONS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_LOGIN_BUTTON_TEXT"),
            "TYPE" => "STRING",
            "DEFAULT" => Loc::getMessage("DDAPP_AUTH_PARAM_LOGIN_BUTTON_DEFAULT"),
        ),
        "LOGIN_BUTTON_CLASS" => array(
            "PARENT" => "BUTTONS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_LOGIN_BUTTON_CLASS"),
            "TYPE" => "STRING",
            "DEFAULT" => "btn btn-primary"
        ),
        "LOGIN_BUTTON_ICON" => array(
            "PARENT" => "BUTTONS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_LOGIN_BUTTON_ICON"),
            "TYPE" => "STRING",
            "DEFAULT" => "fa-solid fa-sign-in-alt"
        ),

        "REGISTER_BUTTON_TEXT" => array(
            "PARENT" => "BUTTONS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_REGISTER_BUTTON_TEXT"),
            "TYPE" => "STRING",
            "DEFAULT" => Loc::getMessage("DDAPP_AUTH_PARAM_REGISTER_BUTTON_DEFAULT"),
        ),
        "REGISTER_BUTTON_CLASS" => array(
            "PARENT" => "BUTTONS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_REGISTER_BUTTON_CLASS"),
            "TYPE" => "STRING",
            "DEFAULT" => "btn btn-outline-primary"
        ),
        "REGISTER_BUTTON_ICON" => array(
            "PARENT" => "BUTTONS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_REGISTER_BUTTON_ICON"),
            "TYPE" => "STRING",
            "DEFAULT" => "fa-solid fa-user-plus"
        ),

        "SHOW_LOGIN_BUTTON" => array(
            "PARENT" => "BUTTONS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_SHOW_LOGIN_BUTTON"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "SHOW_REGISTER_BUTTON" => array(
            "PARENT" => "BUTTONS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_SHOW_REGISTER_BUTTON"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),

        // Размеры модальных окон
        "LOGIN_MODAL_SIZE" => array(
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_LOGIN_MODAL_SIZE"),
            "TYPE" => "LIST",
            "VALUES" => array(
                "modal-sm" => Loc::getMessage("DDAPP_AUTH_MODAL_SIZE_SMALL"),
                "modal-lg" => Loc::getMessage("DDAPP_AUTH_MODAL_SIZE_LARGE"),
                "modal-xl" => Loc::getMessage("DDAPP_AUTH_MODAL_SIZE_EXTRA_LARGE"),
                "" => Loc::getMessage("DDAPP_AUTH_MODAL_SIZE_DEFAULT")
            ),
            "DEFAULT" => ""
        ),
        "REGISTER_MODAL_SIZE" => array(
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_REGISTER_MODAL_SIZE"),
            "TYPE" => "LIST",
            "VALUES" => array(
                "modal-sm" => Loc::getMessage("DDAPP_AUTH_MODAL_SIZE_SMALL"),
                "modal-lg" => Loc::getMessage("DDAPP_AUTH_MODAL_SIZE_LARGE"),
                "modal-xl" => Loc::getMessage("DDAPP_AUTH_MODAL_SIZE_EXTRA_LARGE"),
                "" => Loc::getMessage("DDAPP_AUTH_MODAL_SIZE_DEFAULT")
            ),
            "DEFAULT" => ""
        ),

        // Настройки CAPTCHA
        "USE_CAPTCHA" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_USE_CAPTCHA"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "USE_CAPTCHA_REGISTRATION" => array(
            "PARENT" => "CAPTCHA",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_USE_CAPTCHA_REGISTRATION"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),

        // Дополнительные поля
        "REGISTRATION_FIELDS" => array(
            "PARENT" => "FIELDS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_REGISTRATION_FIELDS"),
            "TYPE" => "STRING",
            "DEFAULT" => "NAME,LAST_NAME,PERSONAL_PHONE",
            "COLS" => 50,
        ),
        "REQUIRED_FIELDS" => array(
            "PARENT" => "FIELDS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_REQUIRED_FIELDS"),
            "TYPE" => "STRING",
            "DEFAULT" => "EMAIL,NAME",
            "COLS" => 50,
        ),

        // Перенаправления
        "SUCCESS_PAGE" => array(
            "PARENT" => "REDIRECTS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_SUCCESS_PAGE"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "REGISTER_SUCCESS_PAGE" => array(
            "PARENT" => "REDIRECTS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_REGISTER_SUCCESS_PAGE"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "AUTH_SUCCESS_PAGE" => array(
            "PARENT" => "REDIRECTS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_AUTH_SUCCESS_PAGE"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),

        // Настройки безопасности
        "USE_CSRF_TOKEN" => array(
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_USE_CSRF_TOKEN"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "STORE_PASSWORD" => array(
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_STORE_PASSWORD"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),

        // Уведомления
        "SEND_REGISTRATION_EMAIL" => array(
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_SEND_REGISTRATION_EMAIL"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "EMAIL_TEMPLATE_REGISTRATION" => array(
            "PARENT" => "SETTINGS",
            "NAME" => Loc::getMessage("DDAPP_AUTH_PARAM_EMAIL_TEMPLATE_REGISTRATION"),
            "TYPE" => "STRING",
            "DEFAULT" => "USER_INFO",
        ),
    ),
);