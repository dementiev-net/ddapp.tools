<?php

namespace DD\Tools;

use DD\Tools\Helpers\LogHelper;

// класс агента
class Agent
{
    // для примера функция пишет в папку модуля время
    static public function superAgent()
    {
        // Настройка логирования
        LogHelper::configure();

        LogHelper::info("cron", "Work!");

        return "\DD\Tools\Agent::superAgent();";
    }
}