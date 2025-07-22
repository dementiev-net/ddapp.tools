<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Page\Asset;

Asset::getInstance()->addJs("/bitrix/js/main/core/core.js");
Asset::getInstance()->addJs("/bitrix/js/main/core/core_ajax.js");
Asset::getInstance()->addJs($templateFolder . "/auth_manager.js");

$componentId = $arResult["COMPONENT_ID"];
?>

<div class="ddapp-auth-wrapper">

    <?php if (!$arResult["IS_AUTHORIZED"]): ?>
        <!-- Кнопки для неавторизованных пользователей -->
        <div class="auth-buttons d-flex gap-2">

            <?php if ($arResult["SHOW_LOGIN_BUTTON"]): ?>
                <button type="button"
                        class="<?= htmlspecialcharsEx($arResult["LOGIN_BUTTON_CLASS"]) ?>"
                        data-id="<?= $componentId ?>"
                        data-type="login"
                        data-ajax-url="<?= $arResult["AJAX_URL"] ?>"
                        data-auth-params="<?= htmlspecialchars(json_encode($arResult["AUTH_PARAMS"], JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8") ?>">
                    <?php if (!empty($arResult["LOGIN_BUTTON_ICON"])): ?>
                        <i class="<?= htmlspecialcharsEx($arResult["LOGIN_BUTTON_ICON"]) ?> me-2"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($arResult["LOGIN_BUTTON_TEXT"]) ?>
                </button>
            <?php endif; ?>

            <?php if ($arResult["SHOW_REGISTER_BUTTON"]): ?>
                <button type="button"
                        class="<?= htmlspecialcharsEx($arResult["REGISTER_BUTTON_CLASS"]) ?>"
                        data-id="<?= $componentId ?>"
                        data-type="register"
                        data-ajax-url="<?= $arResult["AJAX_URL"] ?>"
                        data-auth-params="<?= htmlspecialchars(json_encode($arResult["AUTH_PARAMS"], JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8") ?>">
                    <?php if (!empty($arResult["REGISTER_BUTTON_ICON"])): ?>
                        <i class="<?= htmlspecialcharsEx($arResult["REGISTER_BUTTON_ICON"]) ?> me-2"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($arResult["REGISTER_BUTTON_TEXT"]) ?>
                </button>
            <?php endif; ?>

        </div>

    <?php else: ?>
        <!-- Информация для авторизованных пользователей -->
        <div class="user-info d-flex align-items-center">
            <div class="user-details me-3">
                <span class="user-name fw-bold"><?= htmlspecialchars($arResult["USER_NAME"]) ?></span>
                <?php if (!empty($arResult["USER_EMAIL"])): ?>
                    <small class="user-email text-muted d-block"><?= htmlspecialchars($arResult["USER_EMAIL"]) ?></small>
                <?php endif; ?>
            </div>

            <button type="button"
                    class="btn btn-outline-secondary btn-sm"
                    data-id="<?= $componentId ?>"
                    data-type="logout"
                    data-ajax-url="<?= $arResult["AJAX_URL"] ?>"
                    data-auth-params="<?= htmlspecialchars(json_encode($arResult["AUTH_PARAMS"], JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8") ?>">
                <i class="fa-solid fa-sign-out-alt me-1"></i>
                Выйти
            </button>
        </div>

    <?php endif; ?>

</div>

<script>
    BX.ready(function () {
        // Инициализация конкретного экземпляра
        var componentId = '<?= $componentId ?>';
        var buttons = {
            login: document.querySelector('[data-id="' + componentId + '"][data-type="login"]'),
            register: document.querySelector('[data-id="' + componentId + '"][data-type="register"]'),
            logout: document.querySelector('[data-id="' + componentId + '"][data-type="logout"]')
        };

        // Проверяем что хотя бы одна кнопка существует
        if (buttons.login || buttons.register || buttons.logout) {
            var firstButton = buttons.login || buttons.register || buttons.logout;

            var authManager = new BX.DDAPP.Tools.AuthManager({
                componentId: componentId,
                buttons: buttons,
                ajaxUrl: firstButton.getAttribute('data-ajax-url')
            });

            // Сохраняем ссылку на экземпляр для возможного использования
            if (buttons.login) buttons.login.ddappAuthManager = authManager;
            if (buttons.register) buttons.register.ddappAuthManager = authManager;
            if (buttons.logout) buttons.logout.ddappAuthManager = authManager;
        }
    });
</script>