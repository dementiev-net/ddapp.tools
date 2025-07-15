<?php

namespace DDAPP\Tools;

use DDAPP\Tools\Events\AdminEvents;
use DDAPP\Tools\Events\LoginEvents;
use DDAPP\Tools\Events\PageEvents;
use DDAPP\Tools\Events\ContentEvents;

class Events
{
    public static $userMessageText = "";

    /**
     * @return void
     */
    public static function OnPageStartHandler()
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
     * @return void
     */
    public static function OnPrologHandler()
    {
        PageEvents::OnPrologHandler();
    }

    /**
     * @return void
     */
    public static function OnEpilogHandler()
    {
        PageEvents::OnEpilogHandler();
    }

    /**
     * @param $arFields
     * @return void
     */
    public static function OnAfterUserLoginHandler(&$arFields)
    {
        LoginEvents::OnAfterUserLoginHandler($arFields);
    }

    /**
     * @param $arFields
     * @return void
     */
    public static function OnBeforeUserLoginHandler(&$arFields)
    {
        LoginEvents::OnBeforeUserLoginHandler($arFields);
    }

    /**
     * @param $content
     * @return void
     */
    public static function OnEndBufferContentHandler(&$content)
    {
        ContentEvents::OnEndBufferContentHandler($content);
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