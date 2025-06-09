<?php

namespace DD\Tools\Events;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\AdminList;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\Security\Sign\Signer;

class PageEvents
{
    /**
     * Событие "OnPageStart" вызывается в начале выполняемой части пролога сайта,
     * после подключения всех библиотек и отработки Агентов
     * @return void
     */
    public static function OnPageStartHandler()
    {
//         echo "<pre>";
//         echo "Обработчик события";
//         echo "</pre>";
    }

    /**
     * Событие "OnBeforeProlog" вызывается в выполняемой части пролога сайта,
     * после события OnPageStart
     * @param $userMessageText
     * @return void
     */
    public static function OnBeforePrologHandler(&$userMessageText)
    {
        $request = Context::getCurrent()->getRequest();

        // Запуск агентов
        $action = $request["action_button"] ?? $request["action"] ?? null;

        if ($action == "dd_agent_run" &&
            Application::getInstance()->getContext()->getRequest()->getRequestedPage() == "/bitrix/admin/agent_list.php" &&
            CurrentUser::get()->canDoOperation("view_other_settings") &&
            check_bitrix_sessid() &&
            $request["mode"] == "list" &&
            is_numeric($request["agent_id"])
        ) {
            $agentId = (int)$request["agent_id"];
            $result = \CAgent::GetByID($agentId);

            if ($arAgent = $result->fetch()) {

                @set_time_limit(0);

                if (strlen($arAgent["MODULE_ID"]) > 0 && $arAgent["MODULE_ID"] != "main") {
                    if (!Loader::includeModule($arAgent["MODULE_ID"])) {
                        return;
                    }
                }

                \CTimeZone::Disable();
                $eval_result = "";
                eval("\$eval_result=" . $arAgent["NAME"]);
                \CTimeZone::Enable();

                if (strlen($eval_result)) {
                    $userMessageText = Loc::getMessage("DD_ADMIN_EVENT_ACTION_RUN_OK");
                }

                unset($_REQUEST["action"]);
            }
        }
    }
}
