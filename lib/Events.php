<?php

namespace DD\Tools;

use Bitrix\Main\Entity\Event;
use DD\Tools\Events\AdminEvents;
use DD\Tools\Events\PageEvents;

class Events
{
    public static $userMessageText = "";

    /**
     * @return void
     */
    public static function OnPageStart()
    {
        PageEvents::OnPageStartHandler();
    }

    /**
     * @return void
     */
    public static function OnBeforePrologHandler()
    {
        PageEvents::OnBeforePrologHandler(self::$userMessageText);
    }

    /**
     * @param $list
     * @return void
     */
    public static function OnAdminListDisplayHandler(&$list)
    {
        AdminEvents::OnAdminListDisplayHandler($list, self::$userMessageText);
    }

    /**
     * @param $items
     * @return void
     */
    public static function OnAdminContextMenuShowHandler(&$items)
    {
        AdminEvents::OnAdminContextMenuShowHandler($items);
    }
}