<?php

namespace DD\Tools\Helpers;

class ValidationHelper
{
    /**
     * Проверяет email
     * @param string $email
     * @return bool
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Проверяет телефон (простая проверка)
     * @param string $phone
     * @return bool
     */
    public static function isValidPhone(string $phone): bool
    {
        $phone = preg_replace("/[^\d]/", "", $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 15;
    }
}