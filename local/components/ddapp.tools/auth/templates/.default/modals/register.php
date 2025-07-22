<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$componentId = $arResult['COMPONENT_ID'];

// Определяем поля для отображения
$fieldLabels = [
    'LOGIN' => 'Логин',
    'EMAIL' => 'Email',
    'PASSWORD' => 'Пароль',
    'CONFIRM_PASSWORD' => 'Подтверждение пароля',
    'NAME' => 'Имя',
    'LAST_NAME' => 'Фамилия',
    'SECOND_NAME' => 'Отчество',
    'PERSONAL_PHONE' => 'Телефон',
    'PERSONAL_BIRTHDAY' => 'Дата рождения',
    'PERSONAL_GENDER' => 'Пол',
    'PERSONAL_CITY' => 'Город',
    'WORK_COMPANY' => 'Компания',
    'WORK_POSITION' => 'Должность'
];

$fieldTypes = [
    'EMAIL' => 'email',
    'PASSWORD' => 'password',
    'CONFIRM_PASSWORD' => 'password',
    'PERSONAL_PHONE' => 'tel',
    'PERSONAL_BIRTHDAY' => 'date'
];
?>

<div class="ddapp-auth-wrapper">
    <!-- Модальное окно регистрации -->
    <div class="modal fade"
         id="<?= $componentId ?>_register_modal"
         tabindex="-1"
         aria-labelledby="<?= $componentId ?>_register_modalLabel">
        <div class="modal-dialog <?= $arParams["REGISTER_MODAL_SIZE"] ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="<?= $componentId ?>_register_modalLabel">
                        <i class="fa-solid fa-user-plus me-2"></i>
                        Регистрация
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">

                    <!-- Форма регистрации -->
                    <form id="<?= $componentId ?>_register_form" method="post" novalidate>
                        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">
                        <input type="hidden" name="REGISTER" value="Y">

                        <!-- Основные поля -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="register_login_<?= $componentId ?>" class="form-label">
                                    <i class="fa-solid fa-user me-1"></i>
                                    Логин <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="register_login_<?= $componentId ?>"
                                       name="USER_LOGIN"
                                       placeholder="Введите логин"
                                       required
                                       autocomplete="username">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="register_email_<?= $componentId ?>" class="form-label">
                                    <i class="fa-solid fa-envelope me-1"></i>
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email"
                                       class="form-control"
                                       id="register_email_<?= $componentId ?>"
                                       name="USER_EMAIL"
                                       placeholder="Введите email"
                                       required
                                       autocomplete="email">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="register_password_<?= $componentId ?>" class="form-label">
                                    <i class="fa-solid fa-lock me-1"></i>
                                    Пароль <span class="text-danger">*</span>
                                </label>
                                <input type="password"
                                       class="form-control"
                                       id="register_password_<?= $componentId ?>"
                                       name="USER_PASSWORD"
                                       placeholder="Введите пароль"
                                       required
                                       autocomplete="new-password">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="register_confirm_password_<?= $componentId ?>" class="form-label">
                                    <i class="fa-solid fa-lock me-1"></i>
                                    Подтверждение пароля <span class="text-danger">*</span>
                                </label>
                                <input type="password"
                                       class="form-control"
                                       id="register_confirm_password_<?= $componentId ?>"
                                       name="USER_CONFIRM_PASSWORD"
                                       placeholder="Повторите пароль"
                                       required
                                       autocomplete="new-password">
                            </div>
                        </div>

                        <!-- Дополнительные поля -->
                        <?php if (!empty($arResult["REGISTRATION_FIELDS"])): ?>
                            <div class="additional-fields">
                                <h6 class="mb-3">
                                    <i class="fa-solid fa-info-circle me-1"></i>
                                    Дополнительная информация
                                </h6>

                                <div class="row">
                                    <?php foreach ($arResult["REGISTRATION_FIELDS"] as $field): ?>
                                        <?php if (isset($fieldLabels[$field])): ?>
                                            <div class="col-md-6 mb-3">
                                                <label for="register_<?= strtolower($field) ?>_<?= $componentId ?>" class="form-label">
                                                    <?= $fieldLabels[$field] ?>
                                                    <?php if (in_array($field, $arResult["REQUIRED_FIELDS"])): ?>
                                                        <span class="text-danger">*</span>
                                                    <?php endif; ?>
                                                </label>

                                                <?php if ($field === 'PERSONAL_GENDER'): ?>
                                                    <select class="form-select"
                                                            id="register_<?= strtolower($field) ?>_<?= $componentId ?>"
                                                            name="USER_<?= $field ?>"
                                                        <?= in_array($field, $arResult["REQUIRED_FIELDS"]) ? 'required' : '' ?>>
                                                        <option value="">Выберите пол</option>
                                                        <option value="M">Мужской</option>
                                                        <option value="F">Женский</option>
                                                    </select>
                                                <?php else: ?>
                                                    <input type="<?= $fieldTypes[$field] ?? 'text' ?>"
                                                           class="form-control"
                                                           id="register_<?= strtolower($field) ?>_<?= $componentId ?>"
                                                           name="USER_<?= $field ?>"
                                                           placeholder="Введите <?= strtolower($fieldLabels[$field]) ?>"
                                                        <?= in_array($field, $arResult["REQUIRED_FIELDS"]) ? 'required' : '' ?>
                                                           autocomplete="<?= $field === 'PERSONAL_PHONE' ? 'tel' : 'off' ?>">
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

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

                        <!-- Согласие с правилами -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="agreement_<?= $componentId ?>"
                                       name="agreement"
                                       value="Y"
                                       required>
                                <label class="form-check-label" for="agreement_<?= $componentId ?>">
                                    Я согласен с <a href="/privacy-policy/" target="_blank">политикой конфиденциальности</a>
                                    и <a href="/terms-of-use/" target="_blank">пользовательским соглашением</a>
                                    <span class="text-danger">*</span>
                                </label>
                            </div>
                        </div>

                        <!-- Блок для вывода сообщений -->
                        <div id="<?= $componentId ?>_register_message" class="alert d-none" role="alert"
                             aria-live="polite"></div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-user-plus me-2"></i>
                                Зарегистрироваться
                            </button>
                        </div>

                        <!-- Дополнительные ссылки -->
                        <div class="text-center">
                            <span class="text-muted">Уже есть аккаунт?</span>
                            <a href="#" class="login-link text-decoration-none">
                                <i class="fa-solid fa-sign-in-alt me-1"></i>
                                Войти
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>