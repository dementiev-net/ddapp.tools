<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<?
// проверка на активность 
if ($arResult['ACTIVE'] == 'Y' && $arResult['SETTINGS']['hmarketing_checkbox'] == 'Y') {

    // получаем id сайта
    $context = \Bitrix\Main\Application::getInstance()->getContext();
    $siteId = $context->getSite();

    // получаем URL страницы
    $request = \Bitrix\Main\Context::getCurrent()->getRequest();
    $urlDir = $request->getRequestedPageDirectory();

    // флаг
    $flag = true;

    // если массив $arResult['EXCEPTIONS'] не пустой 
    if (!empty($arResult['EXCEPTIONS'])) {
        // ищем в массиве URL страницы, если URL присутствует, $flag становится false
        $flag = !in_array($urlDir, $arResult['EXCEPTIONS']);
    }
?>

    <? if (in_array($siteId, $arResult['SITE']) && $flag) : ?>

        <div class="ms-popup <?= preg_replace('#,#', ' ', $arResult['SETTINGS']['hmarketing_multiselectbox']) ?> ">
            <a href="<?= $arResult['LINK']; ?>" target="_<?= $arResult['TARGET'] ?>">
                <div class="ms-popup-phone"><?= $arResult['SETTINGS']['hmarketing_text'] ?></div>
            </a>
            <button class="ms-popup-close" type="button" title="Закрыть"></button>
            <a href="<?= $arResult['LINK']; ?>" target="_<?= $arResult['TARGET'] ?>">
                <img src="<?= $arResult['LINK_PICTURE']; ?>" width="<?= $arResult['SETTINGS']['hmarketing_selectbox'] ?>" alt="<?= $arResult['ALT_PICTURE'] ?>">
            </a>
        </div>

    <? endif ?>

<? } ?>