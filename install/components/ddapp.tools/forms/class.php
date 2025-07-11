<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Mail\Event;

class DDAppFormComponent extends CBitrixComponent
{
    private $iblockId;

    /**
     * @param $params
     * @return mixed
     */
    public function onPrepareComponentParams($params): mixed
    {
        $params["CACHE_TIME"] = isset($params["CACHE_TIME"]) ? $params["CACHE_TIME"] : 3600;
        $this->iblockId = (int)$params["IBLOCK_ID"];

        return $params;
    }

    /**
     * @return void
     */
    public function executeComponent(): void
    {
        if (!Loader::includeModule("iblock")) {
            return;
        }

        $this->arResult["PROPERTIES"] = $this->getIblockProperties();
        $this->arResult["FORM_ID"] = "ddapp_form_" . $this->iblockId;
        $this->arResult["IBLOCK_ID"] = $this->iblockId;
        $this->arResult["CAPTCHA_CODE"] = "";

        $request = Application::getInstance()->getContext()->getRequest();

        // Проверяем AJAX-запрос в самом начале
        if ($request->isPost() && $request->getPost("AJAX_CALL_" . $this->iblockId) === "Y") {
            global $APPLICATION;

            // Очищаем буфер
            $APPLICATION->RestartBuffer();

            $result = $this->processForm();

            header('Content-Type: application/json; charset=utf-8');

            // Выводим JSON и завершаем
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Генерирование Bitrix Captcha
        if ($this->arParams["USE_BITRIX_CAPTCHA"] === "Y") {
            $this->arResult["CAPTCHA_CODE"] = $this->generateCaptcha();
        }

        $this->includeComponentTemplate();
    }

    /**
     * Получает свойства инфоблока
     * @return array
     */
    private function getIblockProperties(): array
    {
        $properties = [];
        $res = PropertyTable::getList([
            "filter" => ["IBLOCK_ID" => $this->iblockId, "ACTIVE" => "Y"],
            "select" => ["ID", "CODE", "NAME", "PROPERTY_TYPE", "LIST_TYPE", "MULTIPLE", "IS_REQUIRED", "HINT", "USER_TYPE"],
            "order" => ["SORT" => "ASC"]
        ]);

        while ($property = $res->fetch()) {
            $property["LIST_VALUES"] = [];
            if ($property["PROPERTY_TYPE"] === "L") {
                $property["LIST_VALUES"] = $this->getPropertyListValues($property["ID"]);
            }
            $properties[] = $property;
        }

        return $properties;
    }

    /**
     * Получает список свойства инфоблока
     * @param $propertyId
     * @return array
     */
    private function getPropertyListValues($propertyId): array
    {
        $values = [];
        $res = CIBlockPropertyEnum::GetList(
            ["SORT" => "ASC"],
            ["PROPERTY_ID" => $propertyId]
        );
        while ($value = $res->fetch()) {
            $values[] = $value;
        }
        return $values;
    }

    /**
     * Генерирование Captcha
     * @return mixed
     */
    private function generateCaptcha(): mixed
    {
        $cpt = new CCaptcha();
        $captchaPass = Option::get("main", "captcha_password");

        // Проверка на пустоту, если пусто генерируем новый код Captcha
        if (strlen($captchaPass) <= 0) {
            $captchaPass = randString(10);
            Option::set("main", "captcha_password", $captchaPass);
        }

        $cpt->SetCodeCrypt($captchaPass);

        return $cpt->GetCodeCrypt();
    }

    /**
     * Обработка формы
     * @return array
     */
    private function processForm(): array
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $result = ["success" => false, "message" => ""];

        if (!$this->validateCaptcha($request)) {
            $result["message"] = "Неверный код капчи";
            return $result;
        }

        // Валидация полей формы
        $errors = $this->validateForm($request);
        if (!empty($errors)) {
            $result["message"] = implode("<br>", $errors);
            return $result;
        }

        // Сохранение элемента инфоблока
        $elementId = $this->saveElement($request);
        if ($elementId) {
            // Отправка письма
            if (!empty($this->arParams["EMAIL_TEMPLATE"])) {
                $this->sendEmail($elementId, $request);
            }

            $result["success"] = true;
            $result["message"] = "Форма успешно отправлена";
        } else {
            $result["message"] = "Ошибка сохранения данных";
        }

        return $result;
    }

    /**
     * Валидация Captcha
     * @param $request
     * @return bool
     */
    private function validateCaptcha($request): bool
    {
        if ($this->arParams['USE_BITRIX_CAPTCHA'] === 'Y') {
            $captcha = new CCaptcha();
            $captchaWord = $request->getPost('captcha_word');
            $captchaCode = $request->getPost('captcha_code');

            if (!$captcha->CheckCodeCrypt($captchaWord, $captchaCode)) {
                return false;
            }
        }

        if ($this->arParams["USE_GOOGLE_RECAPTCHA"] === "Y") {
            $recaptchaResponse = $request->getPost("g-recaptcha-response");

            if (empty($recaptchaResponse)) {
                return false;
            }

            $data = [
                "secret" => $this->arParams["GOOGLE_RECAPTCHA_SECRET_KEY"],
                "response" => $recaptchaResponse,
                "remoteip" => $_SERVER["REMOTE_ADDR"]
            ];

            $options = [
                "http" => [
                    "header" => "Content-type: application/x-www-form-urlencoded\r\n",
                    "method" => "POST",
                    "content" => http_build_query($data)
                ]
            ];

            $context = stream_context_create($options);
            $result = file_get_contents("https://www.google.com/recaptcha/api/siteverify", false, $context);
            $resultJson = json_decode($result);

            if ($resultJson && $resultJson->success && $resultJson->score > 0.5) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Валидация полей формы
     * @param $request
     * @return array
     */
    private function validateForm($request): array
    {
        $errors = [];

        foreach ($this->arResult["PROPERTIES"] as $property) {
            $value = $request->getPost("PROPERTY_" . $property["ID"]);

            if ($property["IS_REQUIRED"] === "Y") {
                if (is_array($value)) {
                    // Для множественных значений (чекбоксы)
                    $filteredValues = array_filter($value, function($v) {
                        return !empty(trim($v));
                    });

                    if (empty($filteredValues)) {
                        $errors[] = "Поле \"" . $property["NAME"] . "\" обязательно для заполнения";
                    }
                } else {
                    // Для одиночных значений
                    if (empty(trim($value))) {
                        $errors[] = "Поле \"" . $property["NAME"] . "\" обязательно для заполнения";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Сохранение записи в инфоблок
     * @param $request
     * @return mixed
     */
    private function saveElement($request): mixed
    {
        $element = new CIBlockElement();

        $fields = [
            "IBLOCK_ID" => $this->iblockId,
            "NAME" => "Заявка от " . date("d.m.Y H:i:s"),
            "ACTIVE" => "Y",
            "PROPERTY_VALUES" => []
        ];

        foreach ($this->arResult["PROPERTIES"] as $property) {
            $value = $request->getPost("PROPERTY_" . $property["ID"]);

            if (!empty($value)) {
                if (is_array($value)) {
                    // Для множественных значений - фильтруем пустые
                    $filteredValues = array_filter($value, function($v) {
                        return !empty(trim($v));
                    });

                    if (!empty($filteredValues)) {
                        $fields["PROPERTY_VALUES"][$property["ID"]] = $filteredValues;
                    }
                } else {
                    // Для одиночных значений
                    if (!empty(trim($value))) {
                        $fields["PROPERTY_VALUES"][$property["ID"]] = $value;
                    }
                }
            }
        }

        $elementId = $element->Add($fields);

        if (!$elementId) {
            // Логируем ошибку
            $error = $element->LAST_ERROR;
            if (!empty($error)) {
                AddMessage2Log("DDApp Form Component Error: " . $error, "ddapp_form");
            }
        }

        return $elementId;
    }

    /**
     * Отправка письма
     * @param $elementId
     * @param $request
     * @return void
     */
    private function sendEmail($elementId, $request): void
    {
        $fields = [
            "ELEMENT_ID" => $elementId,
            "IBLOCK_ID" => $this->iblockId,
            "DATE_CREATE" => date("d.m.Y H:i:s")
        ];

        foreach ($this->arResult["PROPERTIES"] as $property) {
            $value = $request->getPost("PROPERTY_" . $property["ID"]);

            if (!empty($value)) {
                if (is_array($value)) {
                    // Для множественных значений - преобразуем в строку
                    $fields[$property["CODE"]] = implode(", ", $value);
                } else {
                    $fields[$property["CODE"]] = $value;
                }
            } else {
                $fields[$property["CODE"]] = "";
            }
        }

        Event::send([
            "EVENT_NAME" => $this->arParams["EMAIL_TEMPLATE"],
            "LID" => SITE_ID,
            "MESSAGE_ID" => $this->arParams["EMAIL_TEMPLATE"],
            "FIELDS" => $fields
        ]);
    }
}