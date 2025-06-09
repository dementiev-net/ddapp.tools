<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);

$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);

if ($POST_RIGHT < "S") {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

Loader::includeModule($module_id);

// Настройки модуля для админки, в том числе значения по умолчанию
$aTabs = array(
    array(
        "DIV" => "edit1", // Значение будет вставленно во все элементы вкладки для идентификации (используется для javascript)
        "TAB" => "Название вкладки в табах",
        "TITLE" => "Главное название в админке",
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
                if ($arOption[0] == "hmarketing_checkbox") {
                    if ($optionValue == "") {
                        $optionValue = "N";
                    }
                }

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

// // пример получения значения из настроек модуля конкретного поля
// $op = \Bitrix\Main\Config\Option::get(
//     // ID модуля, обязательный параметр
//     DD_MODULE_NAMESPACE,
//     // имя параметра, обязательный параметр
//     "hmarketing_multiselectbox",
//     // возвращается значение по умолчанию, если значение не задано
//     "",
//     // ID сайта, если значение параметра различно для разных сайтов
//     false
// );

// // пример получения значения из настроек модуля всех полей
// $op = \Bitrix\Main\Config\Option::getForModule(DD_MODULE_NAMESPACE);

// остальные команды https://dev.1c-bitrix.ru/api_d7/bitrix/main/config/option/index.php