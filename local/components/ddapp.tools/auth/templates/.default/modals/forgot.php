<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$componentId = $arResult['COMPONENT_ID'];
?>

<div class="ddapp-auth-wrapper">
    <!-- Модальное окно восстановления пароля -->
    <div class="modal fade"
         id="<?= $componentId ?>_forgot_modal"
         tabindex="-1"
         aria-labelledby="<?= $componentId ?>_forgot_modalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="<?= $componentId ?>_forgot_modalLabel">
                        <i class="fa-solid fa-key me-2"></i>
                        Восстановление пароля
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">

                    <!-- Информационное сообщение -->
                    <div class="alert alert-info mb-3">
                        <i class="fa-solid fa-info-circle me-2"></i>
                        Введите ваш email или логин. Мы отправим вам инструкции по восстановлению пароля.
                    </div>

                    <!-- Форма восстановления пароля -->
                    <form id="<?= $componentId ?>_forgot_form" method="post" novalidate>
                        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">
                        <input type="hidden" name="FORGOT_PASSWORD" value="Y">

                        <div class="mb-3">
                            <label for="forgot_login_<?= $componentId ?>" class="form-label">
                                <i class="fa-solid fa-envelope me-1"></i>
                                Email или логин <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg"
                                   id="forgot_login_<?= $componentId ?>"
                                   name="USER_LOGIN"
                                   placeholder="Введите email или логин"
                                   required
                                   autocomplete="username">
                            <div class="form-text">
                                <i class="fa-solid fa-lightbulb me-1"></i>
                                Укажите email или логин, который вы использовали при регистрации
                            </div>
                        </div>

                        <?php if ($arResult["USE_CAPTCHA"]): ?>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fa-solid fa-shield-alt me-1"></i>
                                    Код с картинки <span class="text-danger">*</span>
                                </label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <img src="/bitrix/tools/captcha.php?captcha_code=<?= $arResult["CAPTCHA_CODE"] ?>"
                                             alt="Captcha" class="captcha-image img-fluid border rounded mb-2">
                                        <input type="hidden" name="captcha_code"
                                               value="<?= $arResult["CAPTCHA_CODE"] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text"
                                               class="form-control"
                                               name="captcha_word"
                                               placeholder="Введите код с картинки"
                                               required
                                               autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Блок для вывода сообщений -->
                        <div id="<?= $componentId ?>_forgot_message" class="alert d-none" role="alert"
                             aria-live="polite"></div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-paper-plane me-2"></i>
                                Отправить инструкции
                            </button>
                        </div>

                        <!-- Дополнительные ссылки -->
                        <div class="text-center">
                            <a href="#" class="back-to-login-link text-decoration-none">
                                <i class="fa-solid fa-arrow-left me-1"></i>
                                Назад к входу
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>