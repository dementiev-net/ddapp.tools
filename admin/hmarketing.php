<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php";

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Type\DateTime;
use DD\Tools\Entity\DataTable;
use DD\Tools\Helpers\LogHelper;

$op = Bitrix\Main\Config\Option::getForModule("dd.tools");
echo "<pre>";
print_r($op);
die;


Loc::loadMessages(__FILE__);

// Получим права доступа текущего пользователя на модуль
$POST_RIGHT = $APPLICATION->GetGroupRight("dd.tools");
if ($POST_RIGHT == "D") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$actionDisabled = ($POST_RIGHT == "W") ? "" : "disabled";

// Настройка логирования
LogHelper::configure();

// Подключение файлов
Asset::getInstance()->addJs("/bitrix/js/" . "dd.tools" . "/script.min.js");

\CJSCore::Init(["ajax"]);

$APPLICATION->SetTitle("Настройка Pop Up");

IncludeModuleLangFile(__FILE__);

$aTabs = [
    [
        "TAB" => "Параметры",
        "TITLE" => "Параметры вывода pop-up"
    ]
];

$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);

Loader::includeModule("dd.tools");

/**
 * Обработка AJAX-запроса
 */
if ($REQUEST_METHOD === "POST" && $_POST["ajax"] === "Y" && check_bitrix_sessid()) {

    $percent = (int)$_POST["percent"];
    $barId = $_POST["bar_id"]; // ID прогресс-бара

    // Генерируем случайную ошибку с вероятностью 20%
    if (rand(1, 100) <= 20) {
        $errorMessages = [
            "Недостаточно места на диске",
            "Ошибка подключения к серверу",
            "Превышено время ожидания операции",
            "Нет доступа к файлу конфигурации",
            "Ошибка авторизации в облачном хранилище"
        ];

        $randomError = $errorMessages[array_rand($errorMessages)];

        header("Content-Type: application/json");
        echo json_encode([
            "success" => false,
            "error" => $randomError,
            "bar_id" => $barId
        ]);
        LogHelper::error("admin", "AJAX request: " . $randomError);
        die();
    }

    $next = min($percent + rand(5, 15), 100);

    header("Content-Type: application/json");
    echo json_encode([
        "success" => true,
        "percent" => $next,
        "bar_id" => $barId
    ]);
    die();
}

/**
 * Обработка формы
 */
if ($REQUEST_METHOD == "POST" && $save != "" && $POST_RIGHT == "W" && check_bitrix_sessid()) {

    $dataTable = new DataTable;

    $arFields = [
        "ACTIVE" => ($ACTIVE == "") ? "N" : "Y",
        "SITE" => json_encode($SITE),
        "LINK" => htmlspecialchars($LINK),
        "LINK_PICTURE" => htmlspecialchars($LINK_PICTURE),
        "ALT_PICTURE" => htmlspecialchars($ALT_PICTURE),
        "EXCEPTIONS" => $EXCEPTIONS == "" ? "" : trim(htmlspecialchars($EXCEPTIONS)),
        "DATE" => new DateTime(date("d.m.Y H:i:s")),
        "TARGET" => htmlspecialchars($TARGET),
    ];

    $res = $dataTable->Update(1, $arFields);

    // Прошло успешно
    if ($res->isSuccess()) {
        if ($save != "") {
            LocalRedirect($APPLICATION->GetCurPage() . "?mess=ok&lang=" . LANG);
        }
    }

    // Прошло с ошибкой
    if (!$res->isSuccess()) {
        if ($e = $APPLICATION->GetException()) {
            $message = new CAdminMessage($e);
            LogHelper::error("admin", "Form processing: " . $message);

        } else {
            $message = new CAdminMessage(implode("<br>", $res->getErrorMessages()));
            LogHelper::error("admin", "Form processing", $res->getErrorMessages());
        }
    }
}

// Подготовка данных для формы
$result = DataTable::GetByID(1);
if ($result->getSelectedRowsCount()) {
    $dataTable = $result->fetch();
    $str_ACTIVE = $dataTable["ACTIVE"];
    $str_SITE = json_decode($dataTable["SITE"]);
    $str_LINK = $dataTable["LINK"];
    $str_LINK_PICTURE = $dataTable["LINK_PICTURE"];
    $str_ALT_PICTURE = $dataTable["ALT_PICTURE"];
    $str_EXCEPTIONS = $dataTable["EXCEPTIONS"];
    $str_TARGET = $dataTable["TARGET"];
}

require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";

// Сообщения об успешном сохранении
if ($_REQUEST["mess"] == "ok") {
    CAdminMessage::ShowMessage(["MESSAGE" => "Сохранено успешно", "TYPE" => "OK"]);
}

// Сообщение об ошибке
if ($message) {
    echo $message->Show();
}

// Сообщения об ошибке от ORM
if ($dataTable->LAST_ERROR != "") {
    CAdminMessage::ShowMessage($dataTable->LAST_ERROR);
}

// Конфигурация прогресс-баров
$progressBars = [
    [
        "id" => "bitrix-cloud",
        "title" => "Облачное хранилище \"1С-Битрикс\"",
        "total_space" => "2 ГБ",
        "max_gb" => 2
    ], [
        "id" => "backup-system",
        "title" => "Система резервного копирования",
        "total_space" => "5 ГБ",
        "max_gb" => 5
    ], [
        "id" => "media-storage",
        "title" => "Медиа-хранилище",
        "total_space" => "10 ГБ",
        "max_gb" => 10
    ]
];
?>

<form method="POST" action="<?= $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>"
      ENCTYPE="multipart/form-data"
      name="post_form">

<?= bitrix_sessid_post(); ?>

<?php
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
    <tr>
        <td width="40%">Активность:</td>
        <td width="60%"><input type="checkbox" name="ACTIVE" value="Y" <? if ($str_ACTIVE == "Y") echo " checked" ?>>
        </td>
    </tr>
    <tr>
        <td>
            <label for="SITE">Сайты:</label>
        </td>
        <td>
            <select name="SITE[]" multiple>
                <option value="s1" <?= in_array("s1", $str_SITE) ? "selected" : "" ?>>Для России</option>
                <option value="kz" <?= in_array("kz", $str_SITE) ? "selected" : "" ?>>Для Казахстана</option>
            </select>
        </td>
    </tr>
    <tr>
        <td width="40%">Ссылка для перехода:</td>
        <td width="60%"><input type="text" name="LINK" size="70" value="<?= $str_LINK ?>"/></td>
    </tr>
    <tr>
        <td width="40%">Ссылка на картинку:</td>
        <td width="60%"><input type="text" name="LINK_PICTURE" size="70" value="<?= $str_LINK_PICTURE ?>"/></td>
    </tr>
    <tr>
        <td width="40%">Alt картинки:</td>
        <td width="60%"><input type="text" name="ALT_PICTURE" size="70" value="<?= $str_ALT_PICTURE ?>"/></td>
    </tr>
    <tr>
        <td width="40%">Исключения:</td>
        <td width="60%"><textarea cols="50" rows="15" name="EXCEPTIONS"><?= $str_EXCEPTIONS ?></textarea></td>
    </tr>
    <tr>
        <td width="40%">Значение TARGET (self/blank):</td>
        <td width="60%"><input type="text" name="TARGET" value="<?= $str_TARGET ?>"/></td>
    </tr>

<?php foreach ($progressBars as $bar) { ?>

    <!-- Прогресс-бары -->
    <tr>
        <td class="adm-detail-valign-top adm-detail-content-cell-l" width="40%">
            <?= $bar["title"] ?><span class="required"><sup>2</sup></span>:
        </td>
        <td width="60%" class="adm-detail-content-cell-r">

            <!-- Контейнер для сообщений об ошибках -->
            <div id="error-message-<?= $bar["id"] ?>" style="display: none; margin-bottom: 10px;"></div>

            <!-- Контейнер для информации и прогресс-бара (скрыт по умолчанию) -->
            <div id="progress-info-<?= $bar["id"] ?>" style="display: none;">
                Использовано места: <span id="used-space-<?= $bar["id"] ?>">0 Б</span> из <?= $bar["total_space"] ?>
                <div class="adm-progress-bar-outer" style="width: 500px;">
                    <div class="adm-progress-bar-inner" style="width: 0px;" id="progress-bar-<?= $bar["id"] ?>">
                        <div class="adm-progress-bar-inner-text" style="width: 500px;"
                             id="progress-text-<?= $bar["id"] ?>">0%
                        </div>
                    </div>
                    <span id="percent-text-<?= $bar["id"] ?>">0%</span>
                </div>
            </div>
            <div class="adm-info-message-buttons">
                <input type="button" value="Запустить" class="adm-btn-save progress-start-btn"
                       data-bar-id="<?= $bar["id"] ?>" data-max-gb="<?= $bar["max_gb"] ?>" <?= $actionDisabled ?>>
                <input type="button" value="Остановить" class="adm-btn progress-stop-btn"
                       data-bar-id="<?= $bar["id"] ?>" style="display: none;" <?= $actionDisabled ?>>
                <input type="button" value="Сбросить" class="adm-btn progress-reset-btn"
                       data-bar-id="<?= $bar["id"] ?>" style="display: none;" <?= $actionDisabled ?>>
            </div>
        </td>
    </tr>

    <?php
}

// Стандартные кнопки отправки формы
$tabControl->Buttons();
?>

    <input class="adm-btn-save" type="submit" name="save" value="Сохранить настройки" <?= $actionDisabled ?>/>

<?php
$tabControl->End();
?>

    <br>
    <div class="adm-info-message-wrap">
        <div class="adm-info-message">
            <div><span class="required"><sup>1</sup></span> Компания "1С-Битрикс" бесплатно предоставляет место в облаке
                для хранения трех резервных копий на каждую активную лицензию. Объём пространства в облаке зависит от
                лицензии. Доступ к резервным копиям осуществляется по лицензионному ключу и паролю. Без знания пароля
                никто, включая сотрудников "1С-Битрикс", не сможет получить доступ к вашим данным.
            </div>
            <div><span class="required"><sup>2</sup></span> Если выбрано несколько сайтов для помещения в архив, в корне
                архива будет лежать первый по списку сайт, а публичные части остальных сайтов будут помещены в папку <b>/bitrix/backup/sites</b>.
                При восстановлении нужно будет вручную скопировать их в нужные папки и создать символьные ссылки.
            </div>
            <div><span class="required"><sup>3</sup></span> Для маски исключения действуют следующие правила:
                <p>
                </p>
                <li>шаблон маски может содержать символы "*", которые соответствуют любому количеству любых символов в
                    имени файла или папки;
                </li>
                <li>если в начале стоит косая черта ("/" или "\"), путь считается от корня сайта;</li>
                <li>в противном случае шаблон применяется к каждому файлу или папке;</li>
                <p>Примеры шаблонов:</p>
                <li>/content/photo - исключить целиком папку /content/photo;</li>
                <li>*.zip - исключить файлы с расширением "zip";</li>
                <li>.access.php - исключить все файлы ".access.php";</li>
                <li>/files/download/*.zip - исключить файлы с расширением "zip" в директории /files/download;</li>
                <li>/files/d*/*.ht* - исключить файлы из директорий, начинающихся на "/files/d" с расширениями,
                    начинающимися на "ht".
                </li>
            </div>
            <div><span class="required"><sup>4</sup></span> При размещении резервной копии в облачном хранилище
                "1С-Битрикс" отключить шифрование нельзя.
            </div>
            <div><span class="required"><sup>5</sup></span> Системные ограничения php не позволяют делать размер одной
                части архива более 2 Гб. Не устанавливайте это значение больше 200 Мб т.к. это существенно увеличивает
                время архивации и распаковки, оптимальное значение: 100 Мб.
            </div>
        </div>
    </div>

<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";