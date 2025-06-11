<?php

namespace DD\Tools;

use DD\Tools\Helpers\LogHelper;

class freespaceAgent
{
    static public function run()
    {
        // Настройка логирования
        LogHelper::configure();

        LogHelper::info("cron", "FreespaceAgent work!");

        return "\\DD\\Tools\\freespaceAgent::run();";
    }
}