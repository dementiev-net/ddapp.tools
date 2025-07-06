<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Config\Option;
use DD\Tools\Main;
use DD\Tools\Helpers\UserHelper;
use DD\Tools\Helpers\FileHelper;
use DD\Tools\Helpers\CacheHelper;

Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);

// Подключаем JS
Main::includeJS("admin/js/smtp_test.js");

// Проверка доступа
if (UserHelper::hasModuleAccess($module_id) != "W") {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

Loader::includeModule($module_id);

$cacheSize = FileHelper::formatBytes(CacheHelper::checkCacheSize());

// Настройки модуля для админки, в том числе значения по умолчанию
$aTabs = [
    [
        "DIV" => "TAB1", // Для идентификации (используется для javascript)
        "TAB" => Loc::getMessage("DD_TOOLS_TAB1"),
        "TITLE" => Loc::getMessage("DD_TOOLS_TAB1_TITLE"),
        "OPTIONS" => [
            ["maint_period", Loc::getMessage("DD_TOOLS_MAINT_PERIOD"), 30, ["text", 5, 50]],
            ["cache_period", Loc::getMessage("DD_TOOLS_CACHE_PERIOD"), 0, ["selectbox", Loc::getMessage("DD_TOOLS_CACHE_PERIOD_DEFAULT")]],
            ["", "", Loc::getMessage("DD_TOOLS_CACHE_SIZE") . $cacheSize, ["statichtml"]],
            ["export_step", Loc::getMessage("DD_TOOLS_EXPORT_STEP"), 100, ["text", 5, 50]],
        ]
    ], [
        "DIV" => "TAB2",
        "TAB" => Loc::getMessage("DD_TOOLS_TAB2"),
        "TITLE" => Loc::getMessage("DD_TOOLS_TAB2_TITLE"),
        "OPTIONS" => [
            ["log_enabled", Loc::getMessage("DD_TOOLS_LOG_ENABLED"), "Y", ["checkbox"]],
            ["log_min_level", Loc::getMessage("DD_TOOLS_LOG_MIN_LEVEL"), 1, ["selectbox", Loc::getMessage("DD_TOOLS_LOG_MIN_LEVEL_DEFAULT")]],
            ["log_path", Loc::getMessage("DD_TOOLS_LOG_PATH"), Loc::getMessage("DD_TOOLS_LOG_PATH_DEFAULT"), ["text", 40, 50]],
            ["log_date_format", Loc::getMessage("DD_TOOLS_LOG_DATE_FORMAT"), Loc::getMessage("DD_TOOLS_LOG_DATE_FORMAT_DEFAULT"), ["text", 10, 50]],
            Loc::getMessage("DD_TOOLS_BLOCK2"),
            ["log_max_file_size", Loc::getMessage("DD_TOOLS_LOG_MAX_FILE_SIZE"), Loc::getMessage("DD_TOOLS_LOG_MAX_FILE_SIZE_DEFAULT"), ["text", 10, 50]],
            ["log_max_files", Loc::getMessage("DD_TOOLS_LOG_MAX_FILES"), Loc::getMessage("DD_TOOLS_LOG_MAX_FILES_DEFAULT"), ["text", 5, 50]],
            Loc::getMessage("DD_TOOLS_BLOCK3"),
            ["log_email_enabled", Loc::getMessage("DD_TOOLS_LOG_EMAIL_ENABLED"), "Y", ["checkbox"]],
            ["log_email", Loc::getMessage("DD_TOOLS_LOG_EMAIL"), Loc::getMessage("DD_TOOLS_LOG_EMAIL_DEFAULT"), ["text", 20, 50]],
            ['note' => Loc::getMessage("DD_TOOLS_HELP_TAB2")],
        ]
    ], [
        "DIV" => "TAB3",
        "TAB" => Loc::getMessage("DD_TOOLS_TAB3"),
        "TITLE" => Loc::getMessage("DD_TOOLS_TAB3_TITLE"),
        "OPTIONS" => [
            ["disk_enabled", Loc::getMessage("DD_TOOLS_DISK_ENABLED"), "Y", ["checkbox"]],
            ["disk_delete_cache", Loc::getMessage("DD_TOOLS_DISK_DELETE_CACHE"), "Y", ["checkbox"]],
            Loc::getMessage("DD_TOOLS_BLOCK3"),
            ["disk_email_enabled", Loc::getMessage("DD_TOOLS_DISK_EMAIL_ENABLED"), "Y", ["checkbox"]],
            ["disk_email", Loc::getMessage("DD_TOOLS_DISK_EMAIL"), Loc::getMessage("DD_TOOLS_DISK_EMAIL_DEFAULT"), ["text", 20, 50]],
            Loc::getMessage("DD_TOOLS_BLOCK4"),
            ["disk_type_filesystem", Loc::getMessage("DD_TOOLS_DISK_TYPE_FILESYSTEM"), 1, ["selectbox", Loc::getMessage("DD_TOOLS_DISK_TYPE_FILESYSTEM_DEFAULT")]],
            ["disk_free_space", Loc::getMessage("DD_TOOLS_DISK_FREE_SPACE"), Loc::getMessage("DD_TOOLS_DISK_FREE_SPACE_DEFAULT"), ["text", 5, 50]],
            ["disk_all_space", Loc::getMessage("DD_TOOLS_DISK_ALL_SPACE"), Loc::getMessage("DD_TOOLS_DISK_ALL_SPACE_DEFAULT"), ["text", 5, 50]],
        ]
    ], [
        "DIV" => "TAB4",
        "TAB" => Loc::getMessage("DD_TOOLS_TAB4"),
        "TITLE" => Loc::getMessage("DD_TOOLS_TAB4_TITLE"),
        "OPTIONS" => [
            ["smtp_enabled", Loc::getMessage("DD_TOOLS_SMTP_ENABLED"), "Y", ["checkbox"]],
            ["smtp_host", Loc::getMessage("DD_TOOLS_SMTP_HOST"), "smtp.yandex.ru", ["text", 40, 50]],
            ["smtp_secure", Loc::getMessage("DD_TOOLS_SMTP_SMTP_SECURE"), "0", ["selectbox", Loc::getMessage("DD_TOOLS_SMTP_SMTP_SECURE_DEFAULT")]],
            ["smtp_port", Loc::getMessage("DD_TOOLS_SMTP_PORT"), 465, ["text", 5, 50]],
            Loc::getMessage("DD_TOOLS_BLOCK5"),
            ["smtp_login", Loc::getMessage("DD_TOOLS_SMTP_LOGIN"), Loc::getMessage("DD_TOOLS_SMTP_EMAIL_SENDER_DEFAULT"), ["text", 20, 50]],
            ["smtp_password", Loc::getMessage("DD_TOOLS_SMTP_PASSWORD"), "", ["text", 20, 50]],
            ["smtp_email_sender", Loc::getMessage("DD_TOOLS_SMTP_EMAIL_SENDER"), Loc::getMessage("DD_TOOLS_SMTP_EMAIL_SENDER_DEFAULT"), ["text", 20, 50]],
            ["smtp_name_sender", Loc::getMessage("DD_TOOLS_SMTP_NAME_SENDER"), Loc::getMessage("DD_TOOLS_SMTP_NAME_SENDER_DEFAULT"), ["text", 30, 50]],
            Loc::getMessage("DD_TOOLS_BLOCK6"),
            ["smtp_dkim_enabled", Loc::getMessage("DD_TOOLS_SMTP_DKIM_ENABLED"), "N", ["checkbox"]],
            ["smtp_dkim_domain", Loc::getMessage("DD_TOOLS_SMTP_DKIM_DOMAIN"), Loc::getMessage("DD_TOOLS_SMTP_DKIM_DOMAIN_DEFAULT"), ["text", 40, 50]],
            ["smtp_dkim_selector", Loc::getMessage("DD_TOOLS_SMTP_DKIM_SELECTOR"), Loc::getMessage("DD_TOOLS_SMTP_DKIM_SELECTOR_DEFAULT"), ["text", 40, 50]],
            ["smtp_dkim_passphrase", Loc::getMessage("DD_TOOLS_SMTP_DKIM_PASSPHRASE"), "", ["text", 40, 50]],
            ["smtp_dkim_private_key", Loc::getMessage("DD_TOOLS_SMTP_DKIM_PRIVATE_KEY"), Loc::getMessage("DD_TOOLS_SMTP_DKIM_PRIVATE_KEY_DEFAULT"), ["textarea", 15, 60]],
            ["", "<a href='#' id='smtp_test'>" . Loc::getMessage("DD_TOOLS_SMTP_TEST") . "</a>", "<div id='smtp_test_result'>...</div>", ["statichtml"]],
            ['note' => Loc::getMessage("DD_TOOLS_HELP_TAB4")],
        ]
    ], [
        "DIV" => "TAB5",
        "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"),
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS")
    ]
];

// Проверяем текущий POST запрос и сохраняем выбранные пользователем настройки
if ($request->isPost() && check_bitrix_sessid()) {

    foreach ($aTabs as $aTab) {

        foreach ($aTab["OPTIONS"] as $arOption) {

            if (!is_array($arOption)) {
                continue;
            }

            if ($request["Update"]) {

                $optionValue = $request->getPost($arOption[0]);

                // Метод getPost() не работает с input типа checkbox, для работы сделан этот костыль
                if ($arOption[0] == "smtp_enabled") $optionValue = $optionValue ?: "N";
                if ($arOption[0] == "log_enabled") $optionValue = $optionValue ?: "N";
                if ($arOption[0] == "log_email_enabled") $optionValue = $optionValue ?: "N";
                if ($arOption[0] == "disk_enabled") $optionValue = $optionValue ?: "N";
                if ($arOption[0] == "disk_delete_cache") $optionValue = $optionValue ?: "N";
                if ($arOption[0] == "disk_email_enabled") $optionValue = $optionValue ?: "N";
                if ($arOption[0] == "smtp_enabled") $optionValue = $optionValue ?: "N";
                if ($arOption[0] == "smtp_dkim_enabled") $optionValue = $optionValue ?: "N";
                // Настройка агента очистки кеша
                if ($arOption[0] == "cache_period") {

                    $cachePeriod = (int)$optionValue;
                    $agentName = "\\DD\\Tools\\cacheAgent::run();";

                    // Получим текущий агент
                    $res = \CAgent::GetList([], ["NAME" => $agentName]);

                    if ($agent = $res->Fetch()) {

                        if ($cachePeriod === 0) {
                            // Деактивируем и ставим интервал 0
                            \CAgent::Update($agent["ID"], ["ACTIVE" => "N", "AGENT_INTERVAL" => 0]);

                        } else {
                            $interval = match ($cachePeriod) {
                                1 => 1 * 24 * 3600,       // день
                                2 => 7 * 24 * 3600,       // неделя
                                3 => 30 * 24 * 3600,      // месяц
                                4 => 365 * 24 * 3600,     // год
                                default => 7 * 24 * 3600, // по умолчанию — неделя
                            };

                            CAgent::Update($agent["ID"], ["ACTIVE" => "Y", "AGENT_INTERVAL" => $interval]);
                        }
                    }
                }

                // Устанавливаем выбранные значения параметров и сохраняем в базу данных, хранить можем только текст,
                // значит если приходит массив, то разбиваем его через запятую, если не массив сохраняем как есть
                if ($arOption[0]) {
                    Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
                }
            }

            // Проверяем POST запрос, если инициатором выступила кнопка с name="default" сохраняем дефолтные
            // настройки в базу данных
            if ($request["default"]) {
                if ($arOption[0]) {
                    Option::set($module_id, $arOption[0], $arOption[2]);
                }
            }
        }
    }
}

$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);

$tabControl->Begin();
?>

    <style>
        #bx-admin-prefix .adm-info-message {
            width: calc(100% - 50px) !important;
        }

        #bx-admin-prefix .adm-info-message-wrap {
            text-align: start !important;
        }
    </style>

    <form action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= $module_id ?>&lang=<?= LANG ?>"
          method="post">

        <?php foreach ($aTabs as $aTab) {
            if ($aTab["OPTIONS"]) {
                $tabControl->BeginNextTab();
                __AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
            }
        }

        $tabControl->BeginNextTab();

        require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php";

        $tabControl->Buttons();
        ?>

        <?= bitrix_sessid_post(); ?>

        <input class="adm-btn-save" type="submit" name="Update" value="<?= Loc::getMessage("DD_TOOLS_BTN_APPLY") ?>"/>
        <input type="submit" name="default" value="<?= Loc::getMessage("DD_TOOLS_BTN_DEFAULT") ?>"/>
    </form>

    <script>
        BX.ready(function () {
            // Инициализация
            new BX.DD.Tools.SmtpTest({
                ajaxUrl: '<?= Main::getAjaxUrl("admin/ajax/smtp_test.php") ?>',
                messageLoadingText: '<?= Loc::getMessage("DD_TOOLS_SMTP_TEST_LOADING") ?>',
                messageSuccessText: '<?= Loc::getMessage("DD_TOOLS_SMTP_TEST_SUCCESS") ?>',
                messageErrorText: '<?= Loc::getMessage("DD_TOOLS_SMTP_TEST_ERROR") ?>',
                messageAjaxErrorText: '<?= Loc::getMessage("DD_TOOLS_SMTP_TEST_ERROR_AJAX") ?>',
            });
        });
    </script>

<?php
$tabControl->End();