<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$componentId = $arResult['COMPONENT_ID'];
?>

<div class="ddapp-auth-wrapper">
    <!-- Модальное окно входа -->
    <div class="modal fade"
         id="<?= $componentId ?>_login_modal"
         tabindex="-1"
         aria-labelledby="<?= $componentId ?>_login_modalLabel">
        <div class="modal-dialog <?= $arParams["LOGIN_MODAL_SIZE"] ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="<?= $componentId ?>_login_modalLabel">
                        <i class="fa-solid fa-sign-in-alt me-2"></i>
                        Вход в личный кабинет
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">

                    <!-- Форма входа -->
                    <form id="<?= $componentId ?>_login_form" method="post" novalidate>
                        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">
                        <input type="hidden" name="AUTH_FORM" value="Y">
                        <input type="hidden" name="TYPE" value="AUTH">

                        <div class="mb-3">
                            <label for="login_<?= $componentId ?>" class="form-label">
                                <i class="fa-solid fa-user me-1"></i>
                                Логин или Email <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg"
                                   id="login_<?= $componentId ?>"
                                   name="USER_LOGIN"
                                   placeholder="Введите логин или email"
                                   required
                                   autocomplete="username">
                        </div>

                        <div class="mb-3">
                            <label for="password_<?= $componentId ?>" class="form-label">
                                <i class="fa-solid fa-lock me-1"></i>
                                Пароль <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   class="form-control form-control-lg"
                                   id="password_<?= $componentId ?>"
                                   name="USER_PASSWORD"
                                   placeholder="Введите пароль"
                                   required
                                   autocomplete="current-password">
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

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="remember_<?= $componentId ?>"
                                       name="USER_REMEMBER"
                                       value="Y">
                                <label class="form-check-label" for="remember_<?= $componentId ?>">
                                    Запомнить меня
                                </label>
                            </div>
                        </div>

                        <!-- Блок для вывода сообщений -->
                        <div id="<?= $componentId ?>_login_message" class="alert d-none" role="alert"
                             aria-live="polite"></div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-sign-in-alt me-2"></i>
                                Войти
                            </button>
                        </div>

                        <!-- Дополнительные ссылки -->
                        <div class="text-center">
                            <div class="mb-2">
                                <a href="#" class="forgot-password-link text-decoration-none">
                                    <i class="fa-solid fa-key me-1"></i>
                                    Забыли пароль?
                                </a>
                            </div>
                            <div>
                                <span class="text-muted">Нет аккаунта?</span>
                                <a href="#" class="register-link text-decoration-none">
                                    <i class="fa-solid fa-user-plus me-1"></i>
                                    Зарегистрироваться
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>