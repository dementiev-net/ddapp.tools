<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use DDAPP\Tools\Helpers\LogHelper;

// Настройка логирования
LogHelper::configure();

class DDAppAuthComponent extends CBitrixComponent
{
    /**
     * @param $params
     * @return mixed
     */
    public function onPrepareComponentParams($params): mixed
    {
        // Устанавливаем значения по умолчанию
        $params["SHOW_LOGIN_BUTTON"] = $params["SHOW_LOGIN_BUTTON"] ?? "Y";
        $params["SHOW_REGISTER_BUTTON"] = $params["SHOW_REGISTER_BUTTON"] ?? "Y";
        $params["LOGIN_BUTTON_TEXT"] = $params["LOGIN_BUTTON_TEXT"] ?? "Войти";
        $params["REGISTER_BUTTON_TEXT"] = $params["REGISTER_BUTTON_TEXT"] ?? "Регистрация";
        $params["LOGIN_BUTTON_CLASS"] = $params["LOGIN_BUTTON_CLASS"] ?? "btn btn-primary";
        $params["REGISTER_BUTTON_CLASS"] = $params["REGISTER_BUTTON_CLASS"] ?? "btn btn-outline-primary";
        $params["LOGIN_BUTTON_ICON"] = $params["LOGIN_BUTTON_ICON"] ?? "fa-solid fa-sign-in-alt";
        $params["REGISTER_BUTTON_ICON"] = $params["REGISTER_BUTTON_ICON"] ?? "fa-solid fa-user-plus";
        $params["LOGIN_MODAL_SIZE"] = $params["LOGIN_MODAL_SIZE"] ?? "";
        $params["REGISTER_MODAL_SIZE"] = $params["REGISTER_MODAL_SIZE"] ?? "";
        $params["USE_CAPTCHA"] = $params["USE_CAPTCHA"] ?? "N";
        $params["USE_CAPTCHA_REGISTRATION"] = $params["USE_CAPTCHA_REGISTRATION"] ?? "Y";
        $params["USE_CSRF_TOKEN"] = $params["USE_CSRF_TOKEN"] ?? "Y";
        $params["STORE_PASSWORD"] = $params["STORE_PASSWORD"] ?? "Y";
        $params["SEND_REGISTRATION_EMAIL"] = $params["SEND_REGISTRATION_EMAIL"] ?? "Y";
        $params["EMAIL_TEMPLATE_REGISTRATION"] = $params["EMAIL_TEMPLATE_REGISTRATION"] ?? "USER_INFO";

        return $params;
    }

    /**
     * @return void
     */
    public function executeComponent(): void
    {
        global $USER;

        // Генерируем уникальный ID компонента
        $this->arResult["COMPONENT_ID"] = "auth_modal_" . substr(md5(uniqid()), 0, 8);

        // Проверяем авторизацию пользователя
        $this->arResult["IS_AUTHORIZED"] = $USER->IsAuthorized();

        if ($this->arResult["IS_AUTHORIZED"]) {
            // Если пользователь авторизован, показываем информацию о нем
            $this->arResult["USER_NAME"] = $USER->GetFullName() ?: $USER->GetLogin();
            $this->arResult["USER_EMAIL"] = $USER->GetEmail();
        } else {
            // Подготавливаем данные для форм авторизации
            $this->prepareAuthForms();
        }

        // Настройки кнопок
        $this->arResult["SHOW_LOGIN_BUTTON"] = $this->arParams["SHOW_LOGIN_BUTTON"] === "Y";
        $this->arResult["SHOW_REGISTER_BUTTON"] = $this->arParams["SHOW_REGISTER_BUTTON"] === "Y";
        $this->arResult["LOGIN_BUTTON_TEXT"] = $this->arParams["LOGIN_BUTTON_TEXT"];
        $this->arResult["REGISTER_BUTTON_TEXT"] = $this->arParams["REGISTER_BUTTON_TEXT"];
        $this->arResult["LOGIN_BUTTON_CLASS"] = $this->arParams["LOGIN_BUTTON_CLASS"];
        $this->arResult["REGISTER_BUTTON_CLASS"] = $this->arParams["REGISTER_BUTTON_CLASS"];
        $this->arResult["LOGIN_BUTTON_ICON"] = $this->arParams["LOGIN_BUTTON_ICON"];
        $this->arResult["REGISTER_BUTTON_ICON"] = $this->arParams["REGISTER_BUTTON_ICON"];

        // Подготавливаем параметры для передачи в AJAX
        $this->arResult["AUTH_PARAMS"] = $this->prepareAuthParams();

        // Генерируем AJAX URL
        $this->arResult["AJAX_URL"] = $this->getAjaxUrl();

        $this->includeComponentTemplate();
    }

    /**
     * Подготовка форм авторизации
     */
    private function prepareAuthForms(): void
    {
        // Подготавливаем дополнительные поля регистрации
        $registrationFields = [];
        if (!empty($this->arParams["REGISTRATION_FIELDS"])) {
            $fields = explode(",", $this->arParams["REGISTRATION_FIELDS"]);
            foreach ($fields as $field) {
                $field = trim($field);
                if (!empty($field)) {
                    $registrationFields[] = $field;
                }
            }
        }

        // Подготавливаем обязательные поля
        $requiredFields = [];
        if (!empty($this->arParams["REQUIRED_FIELDS"])) {
            $fields = explode(",", $this->arParams["REQUIRED_FIELDS"]);
            foreach ($fields as $field) {
                $field = trim($field);
                if (!empty($field)) {
                    $requiredFields[] = $field;
                }
            }
        }

        $this->arResult["REGISTRATION_FIELDS"] = $registrationFields;
        $this->arResult["REQUIRED_FIELDS"] = $requiredFields;
    }

    /**
     * Подготовка параметров для передачи в AJAX
     * @return array
     */
    private function prepareAuthParams(): array
    {
        return [
            "LOGIN_MODAL_SIZE" => $this->arParams["LOGIN_MODAL_SIZE"],
            "REGISTER_MODAL_SIZE" => $this->arParams["REGISTER_MODAL_SIZE"],
            "USE_CAPTCHA" => $this->arParams["USE_CAPTCHA"],
            "USE_CAPTCHA_REGISTRATION" => $this->arParams["USE_CAPTCHA_REGISTRATION"],
            "USE_CSRF_TOKEN" => $this->arParams["USE_CSRF_TOKEN"],
            "STORE_PASSWORD" => $this->arParams["STORE_PASSWORD"],
            "SEND_REGISTRATION_EMAIL" => $this->arParams["SEND_REGISTRATION_EMAIL"],
            "EMAIL_TEMPLATE_REGISTRATION" => $this->arParams["EMAIL_TEMPLATE_REGISTRATION"],
            "REGISTRATION_FIELDS" => $this->arResult["REGISTRATION_FIELDS"],
            "REQUIRED_FIELDS" => $this->arResult["REQUIRED_FIELDS"],
            "SUCCESS_PAGE" => $this->arParams["SUCCESS_PAGE"],
            "REGISTER_SUCCESS_PAGE" => $this->arParams["REGISTER_SUCCESS_PAGE"],
            "AUTH_SUCCESS_PAGE" => $this->arParams["AUTH_SUCCESS_PAGE"],
        ];
    }

    /**
     * Генерация URL для AJAX запросов
     * @return string
     */
    private function getAjaxUrl(): string
    {
        return $this->getPath() . "/ajax.php";
    }
}