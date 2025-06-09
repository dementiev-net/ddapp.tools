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
    public static function OnPageStart()
    {
//         echo "<pre>";
//         echo "Обработчик события";
//         echo "</pre>";
    }

    /**
     * @param $userMessageText
     * @return void
     */
    public static function OnBeforePrologHandler(&$userMessageText)
    {
        $request = Context::getCurrent()->getRequest();

        $action = null;
        if (isset($request["action_button"]) && !isset($request["action"])) {
            $action = $request["action_button"];
        } elseif (isset($request["action"])) {
            $action = $request["action"];
        }

        if (!$action) {
            return;
        }

        $connection = Application::getConnection();
        $currentUser = CurrentUser::get();
        $application = Application::getInstance();

        if ($action == "dd_agent_run" &&
            $currentUser->canDoOperation("view_other_settings") &&
            $application->getContext()->getRequest()->getRequestedPage() == "/bitrix/admin/agent_list.php" &&
            check_bitrix_sessid() &&
            $request["mode"] == "list" &&
            is_numeric($request["agent_id"])
        ) {
            $agentId = (int)$request["agent_id"];

            $helper = $connection->getSqlHelper();
            $sql = "SELECT ID, NAME, AGENT_INTERVAL, IS_PERIOD, MODULE_ID FROM b_agent WHERE ID = " . $helper->forSql($agentId) . " ORDER BY SORT DESC";
            $result = $connection->query($sql);

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
