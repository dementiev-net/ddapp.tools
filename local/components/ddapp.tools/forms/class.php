<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Iblock\IblockTable;
use DDAPP\Tools\Helpers\LogHelper;

// Настройка логирования
LogHelper::configure();

class DDAppFormButtonComponent extends CBitrixComponent
{
    private $iblockId;

    /**
     * @param $params
     * @return mixed
     */
    public function onPrepareComponentParams($params): mixed
    {
        $this->iblockId = (int)$params["IBLOCK_ID"];

        return $params;
    }

    /**
     * @return void
     */
    public function executeComponent(): void
    {
        if (!Loader::includeModule("iblock")) {
            LogHelper::error("form_" . $this->iblockId, "The iblock module is not connected");
            return;
        }

        // Проверяем существование инфоблока
        $res = CIBlock::GetByID($this->iblockId);
        $arIblock = $res->GetNext();
        if (!$arIblock["ID"]) {
            LogHelper::error("form_" . $this->iblockId, "The iblock was not found", ["iblock_id" => $this->iblockId]);
            return;
        }

        // Передаем данные в результат
        $this->arResult["COMPONENT_ID"] = "form_modal_" . $this->iblockId;
        $this->arResult["BUTTON_TEXT"] = $this->arParams["BUTTON_TEXT"] ?? "Связаться с нами";
        $this->arResult["BUTTON_CLASS"] = $this->arParams["BUTTON_CLASS"] ?? "btn btn-primary btn-lg";
        $this->arResult["BUTTON_ICON"] = $this->arParams["BUTTON_ICON"] ?? "fa-solid fa-envelope";
        $this->arResult["MODAL_SIZE"] = $this->arParams["MODAL_SIZE"] ?? "modal-lg";

        // Подготавливаем параметры для передачи в основной компонент формы
        $this->arResult["FORM_PARAMS"] = $this->prepareFormParams();

        // Генерируем AJAX URL для загрузки формы
        $this->arResult["AJAX_URL"] = $this->getAjaxUrl();

        $this->includeComponentTemplate();
    }

    /**
     * Подготовка параметров для основного компонента формы
     * @return array
     */
    private function prepareFormParams(): array
    {
        return [
            "IBLOCK_ID" => $this->iblockId,
            "ALLOWED_FILE_EXTENSIONS" => $this->arParams["ALLOWED_FILE_EXTENSIONS"] ?? "jpg,jpeg,png,gif,pdf,doc,docx",
            "CHECK_FILE_CONTENT" => $this->arParams["CHECK_FILE_CONTENT"] ?? "Y",
            "EMAIL_TEMPLATE" => $this->arParams["EMAIL_TEMPLATE"] ?? "DDAPP_MESSAGE_FORM",
            "ENABLE_ANALYTICS" => $this->arParams["ENABLE_ANALYTICS"] ?? "N",
            "FILE_UPLOAD_DIR" => $this->arParams["FILE_UPLOAD_DIR"] ?? "/upload/forms/",
            "GA_MEASUREMENT_ID" => $this->arParams["GA_MEASUREMENT_ID"] ?? "",
            "GOOGLE_RECAPTCHA_PUBLIC_KEY" => $this->arParams["GOOGLE_RECAPTCHA_PUBLIC_KEY"] ?? "",
            "GOOGLE_RECAPTCHA_SECRET_KEY" => $this->arParams["GOOGLE_RECAPTCHA_SECRET_KEY"] ?? "",
            "MAX_FILE_SIZE" => $this->arParams["MAX_FILE_SIZE"] ?? "10",
            "PRIVACY_POLICY_TEXT" => $this->arParams["PRIVACY_POLICY_TEXT"] ?? "",
            "RATE_LIMIT_ENABLED" => $this->arParams["RATE_LIMIT_ENABLED"] ?? "Y",
            "RATE_LIMIT_PER_HOUR" => $this->arParams["RATE_LIMIT_PER_HOUR"] ?? "30",
            "RATE_LIMIT_PER_MINUTE" => $this->arParams["RATE_LIMIT_PER_MINUTE"] ?? "5",
            "USE_BITRIX_CAPTCHA" => $this->arParams["USE_BITRIX_CAPTCHA"] ?? "N",
            "USE_GOOGLE_RECAPTCHA" => $this->arParams["USE_GOOGLE_RECAPTCHA"] ?? "N",
            "USE_PRIVACY_POLICY" => $this->arParams["USE_PRIVACY_POLICY"] ?? "N",
            "VK_PIXEL_ID" => $this->arParams["VK_PIXEL_ID"] ?? "",
            "YANDEX_METRIKA_ID" => $this->arParams["YANDEX_METRIKA_ID"] ?? ""
        ];
    }

    /**
     * Генерация URL для AJAX загрузки формы
     * @return string
     */
    private function getAjaxUrl(): string
    {
        return $this->getPath() . "/ajax.php";
    }
}