<?php

namespace DDAPP\Tools\Helpers;

use DDAPP\Tools\Main;

class UserHelper
{
    /**
     * Получает полное имя пользователя
     * @param int $userId
     * @return string
     */
    public static function getFullName(int $userId): string
    {
        $user = \CUser::GetByID($userId)->Fetch();

        if (!$user) {
            return "";
        }

        $parts = array_filter([
            $user["LAST_NAME"],
            $user["NAME"],
            $user["SECOND_NAME"]
        ]);

        return implode(" ", $parts) ?: $user["LOGIN"];
    }

    /**
     * Проверяет права пользователя на модуль
     * @param string $moduleId
     * @return mixed
     */
    public static function hasModuleAccess(string $moduleId)
    {
        global $APPLICATION;

        if (!$moduleId) $moduleId = Main::MODULE_ID;

        return $APPLICATION->GetGroupRight($moduleId);
    }
}