<?php

namespace DD\Tools\Helpers;

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
     * @param int $userId
     * @param string $moduleId
     * @return bool
     */
    public static function hasModuleAccess(int $userId, string $moduleId): bool
    {
        global $APPLICATION;

        $currentUserId = $APPLICATION->GetGroupRight($moduleId, [], $userId);
        return $currentUserId !== "D";
    }
}