<?php

namespace DDAPP\Tools\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\UserTable;
use Bitrix\Main\Mail\Event;

class AuthHelper
{
    /**
     * Валидация формы авторизации
     */
    public static function validateLoginForm($request, $params)
    {
        $errors = [];

        $login = trim($request->getPost("USER_LOGIN"));
        $password = $request->getPost("USER_PASSWORD");

        if (empty($login)) {
            $errors[] = "Не указан логин или email";
        }

        if (empty($password)) {
            $errors[] = "Не указан пароль";
        }

        // Проверка CAPTCHA
        if ($params["USE_CAPTCHA"] === "Y") {
            if (!self::validateCaptcha($request)) {
                $errors[] = "Неверный код капчи";
            }
        }

        return $errors;
    }

    /**
     * Валидация формы регистрации
     */
    public static function validateRegistrationForm($request, $params)
    {
        $errors = [];

        $login = trim($request->getPost("USER_LOGIN"));
        $email = trim($request->getPost("USER_EMAIL"));
        $password = $request->getPost("USER_PASSWORD");
        $confirmPassword = $request->getPost("USER_CONFIRM_PASSWORD");

        // Обязательные поля
        if (empty($login)) {
            $errors[] = "Не указан логин";
        } elseif (strlen($login) < 3) {
            $errors[] = "Логин должен содержать не менее 3 символов";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
            $errors[] = "Логин может содержать только латинские буквы, цифры и знак подчеркивания";
        }

        if (empty($email)) {
            $errors[] = "Не указан email";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Некорректный формат email";
        }

        if (empty($password)) {
            $errors[] = "Не указан пароль";
        } elseif (strlen($password) < 6) {
            $errors[] = "Пароль должен содержать не менее 6 символов";
        }

        if ($password !== $confirmPassword) {
            $errors[] = "Пароли не совпадают";
        }

        // Проверка уникальности логина и email
        if (!empty($login)) {
            $existUser = \CUser::GetByLogin($login)->Fetch();
            if ($existUser) {
                $errors[] = "Пользователь с таким логином уже существует";
            }
        }

        if (!empty($email)) {
            $existUser = \CUser::GetList(($by="id"), ($order="desc"), ["EMAIL" => $email])->Fetch();
            if ($existUser) {
                $errors[] = "Пользователь с таким email уже существует";
            }
        }

        // Проверка дополнительных обязательных полей
        if (!empty($params["REQUIRED_FIELDS"])) {
            foreach ($params["REQUIRED_FIELDS"] as $field) {
                $value = trim($request->getPost("USER_" . $field));
                if (empty($value)) {
                    $fieldName = self::getFieldName($field);
                    $errors[] = "Не заполнено поле \"$fieldName\"";
                }
            }
        }

        // Проверка CAPTCHA
        if ($params["USE_CAPTCHA_REGISTRATION"] === "Y") {
            if (!self::validateCaptcha($request)) {
                $errors[] = "Неверный код капчи";
            }
        }

        // Проверка согласия с правилами
        if ($request->getPost("agreement") !== "Y") {
            $errors[] = "Необходимо согласие с политикой конфиденциальности";
        }

        return $errors;
    }

    /**
     * Валидация формы восстановления пароля
     */
    public static function validateForgotForm($request, $params)
    {
        $errors = [];

        $login = trim($request->getPost("USER_LOGIN"));

        if (empty($login)) {
            $errors[] = "Не указан логин или email";
        }

        // Проверка CAPTCHA
        if ($params["USE_CAPTCHA"] === "Y") {
            if (!self::validateCaptcha($request)) {
                $errors[] = "Неверный код капчи";
            }
        }

        return $errors;
    }

    /**
     * Проверка CAPTCHA
     */
    public static function validateCaptcha($request)
    {
        $captchaWord = $request->getPost("captcha_word");
        $captchaCode = $request->getPost("captcha_code");

        if (empty($captchaWord) || empty($captchaCode)) {
            return false;
        }

        return \CaptchaCheckCode($captchaWord, $captchaCode);
    }

    /**
     * Авторизация пользователя
     */
    public static function authenticateUser($login, $password, $remember = false)
    {
        global $USER;

        if (!is_object($USER)) {
            $USER = new \CUser;
        }

        $authResult = $USER->Login($login, $password, $remember ? "Y" : "N");

        if ($authResult === true) {
            return [
                'success' => true,
                'user_id' => $USER->GetID()
            ];
        } else {
            return [
                'success' => false,
                'error' => $authResult['MESSAGE'] ?? 'Неверный логин или пароль'
            ];
        }
    }

    /**
     * Регистрация пользователя
     */
    public static function registerUser($userData, $params)
    {
        $user = new \CUser;

        // Подготавливаем данные для регистрации
        $fields = [
            "LOGIN" => $userData["USER_LOGIN"],
            "EMAIL" => $userData["USER_EMAIL"],
            "PASSWORD" => $userData["USER_PASSWORD"],
            "CONFIRM_PASSWORD" => $userData["USER_CONFIRM_PASSWORD"],
            "ACTIVE" => "Y",
            "GROUP_ID" => [2], // Группа "Пользователи"
        ];

        // Добавляем дополнительные поля
        foreach ($userData as $key => $value) {
            if (strpos($key, "USER_") === 0 && !in_array($key, ["USER_LOGIN", "USER_EMAIL", "USER_PASSWORD", "USER_CONFIRM_PASSWORD"])) {
                $fieldName = substr($key, 5); // Убираем префикс USER_
                $fields[$fieldName] = $value;
            }
        }

        $userId = $user->Add($fields);

        if ($userId) {
            // Отправляем уведомление о регистрации
            if ($params["SEND_REGISTRATION_EMAIL"] === "Y") {
                self::sendRegistrationEmail($userId, $params);
            }

            // Автоматическая авторизация после регистрации
            global $USER;
            $USER->Authorize($userId);

            return [
                'success' => true,
                'user_id' => $userId
            ];
        } else {
            return [
                'success' => false,
                'error' => $user->LAST_ERROR
            ];
        }
    }

    /**
     * Восстановление пароля
     */
    public static function forgotPassword($login)
    {
        // Ищем пользователя по логину или email
        $filter = [];
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $filter["EMAIL"] = $login;
        } else {
            $filter["LOGIN"] = $login;
        }

        $user = \CUser::GetList(($by="id"), ($order="desc"), $filter)->Fetch();

        if (!$user) {
            return [
                'success' => false,
                'error' => 'Пользователь не найден'
            ];
        }

        // Генерируем новый пароль
        $newPassword = randString(8);

        $userObj = new \CUser;
        $result = $userObj->Update($user["ID"], [
            "PASSWORD" => $newPassword,
            "CONFIRM_PASSWORD" => $newPassword
        ]);

        if ($result) {
            // Отправляем новый пароль на email
            Event::send([
                "EVENT_NAME" => "NEW_USER_PASSWORD",
                "LID" => SITE_ID,
                "C_FIELDS" => [
                    "EMAIL" => $user["EMAIL"],
                    "LOGIN" => $user["LOGIN"],
                    "NAME" => $user["NAME"],
                    "LAST_NAME" => $user["LAST_NAME"],
                    "PASSWORD" => $newPassword
                ],
            ]);

            return [
                'success' => true,
                'message' => 'Новый пароль отправлен на ваш email'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Ошибка при обновлении пароля'
            ];
        }
    }

    /**
     * Отправка email при регистрации
     */
    private static function sendRegistrationEmail($userId, $params)
    {
        $user = \CUser::GetByID($userId)->Fetch();
        if (!$user) return;

        Event::send([
            "EVENT_NAME" => $params["EMAIL_TEMPLATE_REGISTRATION"],
            "LID" => SITE_ID,
            "C_FIELDS" => [
                "EMAIL" => $user["EMAIL"],
                "LOGIN" => $user["LOGIN"],
                "NAME" => $user["NAME"],
                "LAST_NAME" => $user["LAST_NAME"],
                "USER_ID" => $userId,
            ],
        ]);
    }

    /**
     * Получить название поля
     */
    private static function getFieldName($field)
    {
        $fieldNames = [
            'NAME' => 'Имя',
            'LAST_NAME' => 'Фамилия',
            'SECOND_NAME' => 'Отчество',
            'EMAIL' => 'Email',
            'PERSONAL_PHONE' => 'Телефон',
            'PERSONAL_BIRTHDAY' => 'Дата рождения',
            'PERSONAL_GENDER' => 'Пол',
            'PERSONAL_CITY' => 'Город',
            'WORK_COMPANY' => 'Компания',
            'WORK_POSITION' => 'Должность'
        ];

        return $fieldNames[$field] ?? $field;
    }

    /**
     * Проверка лимитов (защита от спама)
     */
    public static function validateLimits($action, $params = [])
    {
        $session = Application::getInstance()->getSession();
        $sessionKey = "auth_limits_" . $action;
        $currentTime = time();

        $limits = $session->get($sessionKey) ?? [];

        // Очищаем старые записи (старше часа)
        $limits = array_filter($limits, function($time) use ($currentTime) {
            return ($currentTime - $time) < 3600;
        });

        // Проверяем лимиты
        $limitsPerMinute = 5; // 5 попыток в минуту
        $limitsPerHour = 30;  // 30 попыток в час

        $lastMinuteAttempts = array_filter($limits, function($time) use ($currentTime) {
            return ($currentTime - $time) < 60;
        });

        if (count($lastMinuteAttempts) >= $limitsPerMinute) {
            return [
                'allowed' => false,
                'message' => 'Слишком много попыток. Попробуйте через минуту.',
                'retry_after' => 60
            ];
        }

        if (count($limits) >= $limitsPerHour) {
            return [
                'allowed' => false,
                'message' => 'Слишком много попыток. Попробуйте через час.',
                'retry_after' => 3600
            ];
        }

        // Добавляем текущую попытку
        $limits[] = $currentTime;
        $session->set($sessionKey, $limits);

        return [
            'allowed' => true
        ];
    }

    /**
     * Генерация CAPTCHA
     */
    public static function generateCaptcha()
    {
        if (function_exists('GetCaptchaCode')) {
            return GetCaptchaCode();
        }

        // Альтернативная генерация CAPTCHA
        $session = Application::getInstance()->getSession();
        $captchaCode = randString(8);
        $session->set("CAPTCHA_CODE", $captchaCode);
        return $captchaCode;
    }

    /**
     * Логирование действий пользователей
     */
    public static function logUserAction($action, $userId = null, $details = [])
    {
        global $USER;

        if (!$userId && is_object($USER)) {
            $userId = $USER->GetID();
        }

        $logData = [
            'action' => $action,
            'user_id' => $userId,
            'ip' => $_SERVER["REMOTE_ADDR"] ?? 'unknown',
            'user_agent' => $_SERVER["HTTP_USER_AGENT"] ?? 'unknown',
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        LogHelper::info("auth_" . $action, "User action", $logData);
    }
}