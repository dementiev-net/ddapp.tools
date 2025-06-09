<?php

namespace DD\Tools;

use DD\Tools\Helpers\LogHelper;

// класс агента
class Agent
{
    // для примера функция пишет в папку модуля время
    static public function superAgent()
    {
        LogHelper::write("cron", "Сработал в: " . date("Y-m-d H:i:s"));

        return "\DD\Tools\Agent::superAgent();";
    }
}