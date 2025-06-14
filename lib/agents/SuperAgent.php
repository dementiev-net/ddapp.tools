<?php

namespace DD\Tools;

use DD\Tools\Helpers\LogHelper;

class superAgent
{
    /**
     * @return string
     */
    static public function run()
    {
        // Настройка логирования
        LogHelper::configure();

        LogHelper::info("cron", "SuperAgent work!");

        return "\\DD\\Tools\\superAgent::run();";
    }
}