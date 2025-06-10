<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);

$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($POST_RIGHT != "W") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

Loader::includeModule($module_id);

// Настройки модуля для админки, в том числе значения по умолчанию
$aTabs = array(
    array(
        "DIV" => "edit1", // Для идентификации (используется для javascript)
        "TAB" => "Настройки",
        "TITLE" => "Настройка параметров модуля",
        "OPTIONS" => array(
            "Название секции checkbox",
            array(
                "hmarketing_checkbox", // Имя элемента формы, для хранения в бд
                "Поясняющий текс элемента checkbox",
                "Y",
                array("checkbox"),
            ),
            "Название секции text",
            array(
                "hmarketing_text",
                "Поясняющий текс элемента text",
                "Жми!",
                array("text", 10, 50)
            ),
            "Название секции selectbox",
            array(
                "hmarketing_selectbox",
                "Поясняющий текс элемента selectbox",
                "460",
                array("selectbox", array(
                    "460" => "460Х306",
                    "360" => "360Х242",
                ))
            ),
            "Название секции multiselectbox",
            array(
                "hmarketing_multiselectbox",
                "Поясняющий текс элемента multiselectbox",
                "left, bottom",
                array("multiselectbox", array(
                    "left" => "Лево",
                    "right" => "Право",
                    "top" => "Верх",
                    "bottom" => "Низ",
                ))
            )
        )
    ),
    array(
        "DIV" => "edit2",
        "TAB" => "Логирование",
        "TITLE" => "Настройка логирования модуля",
        "OPTIONS" => array(
            "Основные настройки",
            array(
                "log_enabled", // Имя элемента формы, для хранения в бд
                "Включить логирование",
                "Y",
                array("checkbox"),
            ),
            array(
                "log_min_level",
                "Минимальный уровень логирования",
                "460",
                array("selectbox", array(
                    "1" => "DEBUG",
                    "2" => "INFO",
                    "3" => "WARNING",
                    "4" => "ERROR",
                    "5" => "CRITICAL",
                ))
            ),
            array(
                "log_path",
                "Путь до папки с логами",
                "/upload/logs/",
                array("text", 40, 50)
            ),
            array(
                "log_date_format",
                "Формат даты в логах",
                "d.m.Y H:i:s",
                array("text", 10, 50)
            ),
            "Настройки ротации",
            array(
                "log_max_file_size",
                "Максимальный размер файла",
                "5242880",
                array("text", 10, 50)
            ),
            array(
                "log_max_files",
                "Количество файлов",
                "10",
                array("text", 5, 50)
            ),
            "Настройки email уведомлений при критических ошибках",
            array(
                "log_email_enabled",
                "Отправлять письмо",
                "Y",
                array("checkbox"),
            ),
            array(
                "log_email_temp",
                "Код шаблона письма",
                "DD_TOOLS_CRITICAL_ERROR",
                array("text", 30, 50)
            ),
            array(
                "log_email",
                "E-Mail",
                "admin@yoursite.com",
                array("text", 20, 50),
            ),
            array(
                "log_note",
                "",
                "",
                array(),
                "note" =>
                    "<p><strong>Подключение:</strong></p>
                    <p>use DD\Tools\Helpers\LogHelper;</p>
                    <p>
                            LogHelper::configure();<br>
                            <br>или<br><br>
                            LogHelper::configure(array(<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;'log_enabled' => 'Y',<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;'log_min_level' => LogHelper::LEVEL_INFO,<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;'log_path' => '/local/logs/custom/',<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;'log_date_format' => 'd.m.Y H:i:s',<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;'log_max_file_size' => 5242880,<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;'log_max_files' => 10,<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;'log_critical_email' => array(<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'enabled' => 'Y',<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'template_code' => 'DD_TOOLS_CRITICAL_ERROR', // Автоматически создается при установке<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'email' => 'admin@yoursite.com', // ОБЯЗАТЕЛЬНО укажите email администратора<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;)<br>
                            ));
                    </p>
                    <p><strong>Обычное логирование:</strong></p>
                    <p>
                           LogHelper::info('user_actions', 'User logged in', array('user_id' => 123));<br>
                           LogHelper::error('payment', 'Payment processing failed', array('order_id' => 456));<br>
                           LogHelper::warning('payment', 'Invalid password', array('login' => 'admin'));<br>
                           LogHelper::debug('payment', 'Invalid password', array('login' => 'admin'));
                    </p>
                    <p><strong>Критическая ошибка - автоматически отправит email:</strong></p>
                    <p>
                           LogHelper::critical('system', 'Database connection lost', array(<br>
                           &nbsp;&nbsp;&nbsp;&nbsp;'host' => 'localhost',<br>
                           &nbsp;&nbsp;&nbsp;&nbsp;'error' => 'Connection timeout'<br>
                           ));
                    </p>
                    <p><strong>Логирование исключений:</strong></p>
                    <p>
                           try {<br>
                           &nbsp;&nbsp;&nbsp;&nbsp;// некий код<br>
                           } catch (Exception \$e) {<br>
                           &nbsp;&nbsp;&nbsp;&nbsp;LogHelper::exception('api', \$e, array('method' => 'getUserData'));<br>
                           }
                    </p>
                    <p><strong>Измерение производительности:</strong></p>
                    <p>
                           \$result = LogHelper::timeExecution('performance', 'heavy_database_query', function() {<br>
                           &nbsp;&nbsp;&nbsp;&nbsp;// тяжелый запрос к БД<br>
                           &nbsp;&nbsp;&nbsp;&nbsp;return \$someResult;<br>
                           });<br>
                    </p>
                    <p><strong>Очистка старых логов (можно добавить в cron):</strong></p>
                    <p>
                           LogHelper::cleanOldLogs('system', 30); // удалить логи старше 30 дней
                    </p>",
            ),
        )
    ),
    array(
        "DIV" => "edit3",
        "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"),
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS")
    )
);

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
                if ($arOption[0] == "log_enabled") $optionValue = $optionValue ?: "N";
                if ($arOption[0] == "log_email_enabled") $optionValue = $optionValue ?: "N";

                // Устанавливаем выбранные значения параметров и сохраняем в базу данных, хранить можем только текст, значит если приходит массив, то разбиваем его через запятую, если не массив сохраняем как есть
                Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
            }

            // Проверяем POST запрос, если инициатором выступила кнопка с name="default" сохраняем дефолтные настройки в базу данных
            if ($request["default"]) {
                Option::set($module_id, $arOption[0], $arOption[2]);
            }
        }
    }
}

// Отрисовываем форму, для этого создаем новый экземпляр класса CAdminTabControl, куда и передаём массив с настройками
$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);

// Отображаем заголовки закладок
$tabControl->Begin();
?>

    <style>
        #bx-admin-prefix .adm-info-message {
            width: 90% !important;
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

        echo(bitrix_sessid_post());
        ?>

        <input class="adm-btn-save" type="submit" name="Update" value="Применить"/>
        <input type="submit" name="default" value="По умолчанию"/>
    </form>

<?php
// Обозначаем конец отрисовки формы
$tabControl->End();