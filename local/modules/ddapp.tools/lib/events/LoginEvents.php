<?php

namespace DDAPP\Tools\Events;

class LoginEvents
{
    /**
     * "OnAfterUserLogin"
     *   После успешного входа пользователя
     * @return void
     */
    public static function OnAfterUserLoginHandler(&$arFields)
    {
    }

    /**
     * "OnBeforeUserLogin"
     *   Перед попыткой входа
     *   Можно добавить дополнительные проверки
     * @return void
     */
    public static function OnBeforeUserLoginHandler(&$arFields)
    {
    }
}
