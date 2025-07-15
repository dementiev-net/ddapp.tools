<?php

namespace DDAPP\Tools\Events;

class ContentEvents
{
    /**
     * "OnEndBufferContent"
     *   Финальная обработка HTML
     *   Добавление метрик, сжатие, минификация
     * @return void
     */
    public static function OnEndBufferContentHandler(&$content)
    {
    }
}
