<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$formId = $arResult["FORM_ID"];
$iblockId = $arResult["IBLOCK_ID"];
$fileConfig = $arResult["FILE_CONFIG"];

// Добавляем скрипты аналитики в head
global $APPLICATION;

// Google Analytics
if (!empty($arParams["GA_MEASUREMENT_ID"])) {
    $APPLICATION->AddHeadString("
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src=\"https://www.googletagmanager.com/gtag/js?id={$arParams["GA_MEASUREMENT_ID"]}\"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{$arParams["GA_MEASUREMENT_ID"]}');
        </script>
    ");
}

// Яндекс.Метрика
if (!empty($arParams["YANDEX_METRIKA_ID"])) {
    $APPLICATION->AddHeadString("
        <!-- Yandex.Metrika counter -->
        <script type=\"text/javascript\">
            (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
            (window, document, \"script\", \"https://mc.yandex.ru/metrika/tag.js\", \"ym\");

            ym({$arParams["YANDEX_METRIKA_ID"]}, \"init\", {
                clickmap:true,
                trackLinks:true,
                accurateTrackBounce:true,
                webvisor:true
            });
            
            window.yaCounter = {$arParams["YANDEX_METRIKA_ID"]};
        </script>
        <noscript><div><img src=\"https://mc.yandex.ru/watch/{$arParams["YANDEX_METRIKA_ID"]}\" style=\"position:absolute; left:-9999px;\" alt=\"\" /></div></noscript>
    ");
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
      integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q"
        crossorigin="anonymous"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.8/inputmask.min.js"></script>

<div class="ddapp-form-wrapper">

    <!-- Кнопка -->
    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#<?= $formId ?>Modal">
        <i class="fa-solid fa-up-right-from-square"></i>
        <?= !empty($arParams["BUTTON_TEXT"]) ? $arParams["BUTTON_TEXT"] : "Открыть форму" ?>
    </button>

    <div class="modal fade" id="<?= $formId ?>Modal" tabindex="-1" role="dialog"
         aria-labelledby="<?= $formId ?>ModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="<?= $formId ?>ModalLabel"><?= $arResult["NAME"] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($arResult["DESCRIPTION"])): ?>
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <?= $arResult["DESCRIPTION"] ?>
                        </div>
                    <?php endif; ?>

                    <!-- Блок для вывода сообщений -->
                    <div id="<?= $formId ?>Messages" class="alert" style="display: none;" role="alert"></div>

                    <!-- Форма -->
                    <form id="<?= $formId ?>" method="post" enctype="multipart/form-data" novalidate>

                        <?php foreach ($arResult["PROPERTIES"] as $index => $property): ?>
                            <div class="form-group mb-3">
                                <label for="property_<?= $property["ID"] ?>" class="form-label">
                                    <?= $property["NAME"] ?>
                                    <?php if ($property["IS_REQUIRED"] === "Y"): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>

                                <?php
                                // Поля даты "S:DateTime"
                                if ($property["PROPERTY_TYPE"] === "S" && $property["USER_TYPE"] === "DateTime"): ?>
                                    <input type="date"
                                           class="form-control"
                                           id="property_<?= $property["ID"] ?>"
                                           name="property_<?= $property["ID"] ?>"
                                        <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                           aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">

                                <?php
                                // Строковые поля "S"
                                elseif ($property["PROPERTY_TYPE"] === "S"):
                                    if ($property["ROW_COUNT"] > 1): ?>
                                        <textarea class="form-control"
                                                  id="property_<?= $property["ID"] ?>"
                                                  name="property_<?= $property["ID"] ?>"
                                                  rows="<?= $property["ROW_COUNT"] ?>"
                                                  cols="<?= $property["COL_COUNT"] ?>"
                                                  placeholder="Введите <?= strtolower($property["NAME"]) ?>"
                                                  <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                                  aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>"></textarea>
                                    <?php else: ?>
                                        <input type="text"
                                               class="form-control"
                                               id="property_<?= $property["ID"] ?>"
                                               name="property_<?= $property["ID"] ?>"
                                               placeholder="Введите <?= strtolower($property["NAME"]) ?>"
                                            <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                               aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">
                                        <?php if (!empty($property["HINT"]) && strtoupper(trim($property["HINT"])) === "EMAIL"): ?>
                                            <div class="email-hint">
                                                <i class="fas fa-lightbulb me-1"></i>
                                                Нажмите <kbd>Tab</kbd> для автодополнения домена
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                <?php
                                // Списочные поля "L"
                                elseif ($property["PROPERTY_TYPE"] === "L"):

                                    if ($property["LIST_TYPE"] === "L"): // Список
                                        ?>
                                        <select class="form-select"
                                                id="property_<?= $property["ID"] ?>"
                                                name="property_<?= $property["ID"] ?><?= $property["MULTIPLE"] === "Y" ? "[]" : "" ?>"
                                            <?= $property["MULTIPLE"] === "Y" ? " multiple" : "" ?>
                                            <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                                aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">
                                            <?php if ($property["MULTIPLE"] !== "Y"): ?>
                                                <option value="">Выберите вариант...</option>
                                            <?php endif; ?>
                                            <?php foreach ($property["LIST_VALUES"] as $value): ?>
                                                <option value="<?= $value["ID"] ?>"><?= $value["VALUE"] ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                    <?php elseif ($property["LIST_TYPE"] === "C"): // Флажки ?>
                                        <div class="checkbox-group" role="group"
                                             aria-labelledby="property_<?= $property["ID"] ?>_label">
                                            <?php foreach ($property["LIST_VALUES"] as $valueIndex => $value): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           id="property_<?= $property["ID"] ?>_<?= $value["ID"] ?>"
                                                           name="property_<?= $property["ID"] ?>[]"
                                                           value="<?= $value["ID"] ?>"
                                                        <?= $property["IS_REQUIRED"] === "Y" && $valueIndex === 0 ? " required" : "" ?>>
                                                    <label class="form-check-label"
                                                           for="property_<?= $property["ID"] ?>_<?= $value["ID"] ?>">
                                                        <?= $value["VALUE"] ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                <?php
                                // Числовые поля "N"
                                elseif ($property["PROPERTY_TYPE"] === "N"): ?>
                                    <input type="number"
                                           class="form-control"
                                           id="property_<?= $property["ID"] ?>"
                                           name="property_<?= $property["ID"] ?>"
                                           placeholder="Введите число"
                                           step="any"
                                        <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                           aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">

                                <?php
                                // Файловые поля "F" - будут обработаны JavaScript
                                elseif ($property["PROPERTY_TYPE"] === "F"): ?>
                                    <input type="file"
                                           class="form-control d-none"
                                           id="property_<?= $property["ID"] ?>"
                                           name="property_<?= $property["ID"] ?><?= $property["MULTIPLE"] === "Y" ? "[]" : "" ?>"
                                           accept=".<?= implode(',.', $fileConfig['allowed_extensions']) ?>"
                                        <?= $property["MULTIPLE"] === "Y" ? " multiple" : "" ?>
                                        <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                           aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">

                                <?php
                                // Привязка к элементам "E"
                                elseif ($property["PROPERTY_TYPE"] === "E" && !empty($property["ELEMENT_VALUES"])): ?>
                                    <select class="form-select"
                                            id="property_<?= $property["ID"] ?>"
                                            name="property_<?= $property["ID"] ?><?= $property["MULTIPLE"] === "Y" ? "[]" : "" ?>"
                                        <?= $property["MULTIPLE"] === "Y" ? "multiple" : "" ?>
                                        <?= $property["IS_REQUIRED"] === "Y" ? " required" : "" ?>
                                            aria-describedby="<?= !empty($property["HINT"]) ? "hint_" . $property["ID"] : "" ?>">
                                        <?php if ($property["MULTIPLE"] !== "Y"): ?>
                                            <option value="">Выберите элемент...</option>
                                        <?php endif; ?>
                                        <?php foreach ($property["ELEMENT_VALUES"] as $element): ?>
                                            <option value="<?= $element["ID"] ?>"><?= $element["NAME"] ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                <?php endif; ?>

                                <?php if (!empty($property["HINT"])): ?>
                                    <div id="hint_<?= $property["ID"] ?>" class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <?= $property["HINT"] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($arParams["USE_BITRIX_CAPTCHA"] === "Y"): ?>
                            <div class="form-group mb-3">
                                <label class="form-label">
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

                        <!-- Обязательные поля -->
                        <?php
                        $hasRequiredFields = false;
                        foreach ($arResult["PROPERTIES"] as $property) {
                            if ($property["IS_REQUIRED"] === "Y") {
                                $hasRequiredFields = true;
                                break;
                            }
                        }
                        if ($hasRequiredFields || $arParams["USE_BITRIX_CAPTCHA"] === "Y"): ?>
                            <div class="alert alert-light">
                                <small class="text-muted">
                                    <i class="fas fa-asterisk text-danger me-1"></i>
                                    Поля, отмеченные звездочкой (*), обязательны для заполнения
                                </small>
                            </div>
                        <?php endif; ?>

                        <?php if ($arParams["USE_PRIVACY_POLICY"] === "Y"): ?>
                            <div class="form-group mb-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="privacy_policy_agreement"
                                           name="privacy_policy_agreement"
                                           value="Y"
                                           required
                                           aria-describedby="privacy_policy_help">
                                    <label class="form-check-label" for="privacy_policy_agreement">
                                        <?php if (!empty($arParams["PRIVACY_POLICY_LINK"])): ?>
                                            <?= str_replace(
                                                "политикой обработки персональных данных",
                                                "<a href=\"" . htmlspecialchars($arParams["PRIVACY_POLICY_LINK"]) . "\" target=\"_blank\" rel=\"noopener\">политикой обработки персональных данных</a>",
                                                htmlspecialchars($arParams["PRIVACY_POLICY_TEXT"])
                                            ) ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($arParams["PRIVACY_POLICY_TEXT"]) ?>
                                        <?php endif; ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div id="privacy_policy_help" class="form-text">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Обязательное поле для отправки формы
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <input type="hidden" name="ajax_<?= $iblockId ?>" value="Y">
                        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                Отправить форму
                            </button>
                        </div>
                    </form>
                </div>

                <div class="modal-footer bg-light">
                    <small class="text-muted w-100 text-center">
                        <i class="fas fa-shield-alt me-1"></i>
                        Ваши данные защищены и не передаются третьим лицам
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Подключение Font Awesome для иконок -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
    BX.ready(function () {
        // Инициализация формы с расширенными параметрами
        window.formManager_<?= $iblockId ?> = new BX.DDAPP.Tools.FormManager({
            formId: '<?= $formId ?>',
            modalId: '<?= $formId ?>Modal',
            messagesId: '<?= $formId ?>Messages',
            useGoogleRecaptcha: '<?= $arParams["USE_GOOGLE_RECAPTCHA"] ?>',
            recaptchaPublicKey: '<?= $arParams["GOOGLE_RECAPTCHA_PUBLIC_KEY"] ?>',
            fileConfig: <?= json_encode($fileConfig) ?>,
            iblockId: <?= $iblockId ?>,
            analytics: {
                ga_measurement_id: '<?= $arParams["GA_MEASUREMENT_ID"] ?? "" ?>',
                yandex_metrika_id: '<?= $arParams["YANDEX_METRIKA_ID"] ?? "" ?>'
            }
        });

        // Обработка событий аналитики из очереди
        if (window.ddappAnalytics && window.ddappAnalytics.length > 0) {
            window.ddappAnalytics.forEach(function (event) {
                if (typeof gtag !== 'undefined') {
                    gtag('event', event.event_name, event.parameters);
                }
            });
            window.ddappAnalytics = [];
        }

        // Отслеживание времени заполнения формы
        var formStartTime = Date.now();
        var modal = document.getElementById('<?= $formId ?>Modal');

        modal.addEventListener('shown.bs.modal', function () {
            formStartTime = Date.now();

            // Google Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'form_opened', {
                    'form_id': '<?= $formId ?>',
                    'form_name': '<?= addslashes($arResult["NAME"]) ?>'
                });
            }

            // Яндекс.Метрика
            if (typeof ym !== 'undefined' && window.yaCounter) {
                ym(window.yaCounter, 'reachGoal', 'form_view', {
                    form_id: '<?= $formId ?>'
                });
            }
        });

        // Отслеживание успешной отправки
        var originalOnSuccess = window.formManager_<?= $iblockId ?>.onSuccess;
        window.formManager_<?= $iblockId ?>.onSuccess = function (response) {
            originalOnSuccess.call(this, response);

            if (response.success) {
                var fillTime = Math.round((Date.now() - formStartTime) / 1000);

                // Google Analytics Enhanced Ecommerce
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_submit', {
                        'form_id': '<?= $formId ?>',
                        'form_name': '<?= addslashes($arResult["NAME"]) ?>',
                        'element_id': response.element_id,
                        'fill_time_seconds': fillTime,
                        'value': 1,
                        'currency': 'RUB'
                    });

                    // Конверсия
                    gtag('event', 'conversion', {
                        'send_to': '<?= $arParams["GA_MEASUREMENT_ID"] ?? "" ?>/form_conversion',
                        'value': 1,
                        'currency': 'RUB'
                    });
                }

                // Яндекс.Метрика цель и параметры
                if (typeof ym !== 'undefined' && window.yaCounter) {
                    ym(window.yaCounter, 'reachGoal', 'form_submit', {
                        form_id: '<?= $formId ?>',
                        element_id: response.element_id,
                        fill_time: fillTime
                    });

                    // Отправка параметров визита
                    ym(window.yaCounter, 'params', {
                        form_submitted: true,
                        form_id: '<?= $formId ?>',
                        last_form_submit: new Date().toISOString()
                    });
                }

                // VK Pixel (если настроен)
                if (typeof VK !== 'undefined' && VK.Retargeting) {
                    VK.Retargeting.Event('lead');
                }
            }
        };

        // Отслеживание заполнения полей
        var formFields = document.querySelectorAll('#<?= $formId ?> input, #<?= $formId ?> textarea, #<?= $formId ?> select');
        var fieldsInteracted = new Set();

        formFields.forEach(function (field) {
            field.addEventListener('blur', function () {
                if (this.value.trim() && !fieldsInteracted.has(this.name)) {
                    fieldsInteracted.add(this.name);

                    // Отслеживание прогресса заполнения
                    var progress = (fieldsInteracted.size / formFields.length * 100);

                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'form_progress', {
                            'form_id': '<?= $formId ?>',
                            'field_name': this.name,
                            'progress_percent': Math.round(progress)
                        });
                    }

                    // Milestone события (25%, 50%, 75%)
                    if (progress >= 25 && progress < 50 && !window.milestone25_<?= $iblockId ?>) {
                        window.milestone25_<?= $iblockId ?> = true;
                        if (typeof gtag !== 'undefined') {
                            gtag('event', 'form_milestone', {
                                'form_id': '<?= $formId ?>',
                                'milestone': '25_percent'
                            });
                        }
                    } else if (progress >= 50 && progress < 75 && !window.milestone50_<?= $iblockId ?>) {
                        window.milestone50_<?= $iblockId ?> = true;
                        if (typeof gtag !== 'undefined') {
                            gtag('event', 'form_milestone', {
                                'form_id': '<?= $formId ?>',
                                'milestone': '50_percent'
                            });
                        }
                    } else if (progress >= 75 && !window.milestone75_<?= $iblockId ?>) {
                        window.milestone75_<?= $iblockId ?> = true;
                        if (typeof gtag !== 'undefined') {
                            gtag('event', 'form_milestone', {
                                'form_id': '<?= $formId ?>',
                                'milestone': '75_percent'
                            });
                        }
                    }
                }
            });
        });

        // Отслеживание ошибок валидации
        var originalShowMessage = window.formManager_<?= $iblockId ?>.showMessage;
        window.formManager_<?= $iblockId ?>.showMessage = function (message, type) {
            originalShowMessage.call(this, message, type);

            if (type === 'error') {
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_error', {
                        'form_id': '<?= $formId ?>',
                        'error_message': message.substring(0, 100) // Ограничиваем длину
                    });
                }

                if (typeof ym !== 'undefined' && window.yaCounter) {
                    ym(window.yaCounter, 'reachGoal', 'form_error');
                }
            }
        };

        // Отслеживание времени на странице при закрытии формы
        modal.addEventListener('hidden.bs.modal', function () {
            var timeOnForm = Math.round((Date.now() - formStartTime) / 1000);

            if (typeof gtag !== 'undefined') {
                gtag('event', 'form_closed', {
                    'form_id': '<?= $formId ?>',
                    'time_on_form_seconds': timeOnForm,
                    'fields_filled': fieldsInteracted.size
                });
            }
        });

        // Отслеживание скролла в модальном окне
        var modalBody = modal.querySelector('.modal-body');
        var scrollTracked = false;

        modalBody.addEventListener('scroll', function () {
            if (!scrollTracked && this.scrollTop > 100) {
                scrollTracked = true;

                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_scroll', {
                        'form_id': '<?= $formId ?>'
                    });
                }
            }
        });

        // Отслеживание загрузки файлов
        var fileInputs = document.querySelectorAll('#<?= $formId ?> input[type="file"]');
        fileInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                if (this.files.length > 0) {
                    var totalSize = 0;
                    var fileTypes = [];

                    for (var i = 0; i < this.files.length; i++) {
                        totalSize += this.files[i].size;
                        var ext = this.files[i].name.split('.').pop().toLowerCase();
                        if (fileTypes.indexOf(ext) === -1) {
                            fileTypes.push(ext);
                        }
                    }

                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'file_upload', {
                            'form_id': '<?= $formId ?>',
                            'files_count': this.files.length,
                            'total_size_mb': Math.round(totalSize / 1024 / 1024 * 100) / 100,
                            'file_types': fileTypes.join(',')
                        });
                    }
                }
            });
        });
    });

    // Глобальная функция для внешнего доступа к форме
    window.getDDAppForm_<?= $iblockId ?> = function () {
        return window.formManager_<?= $iblockId ?>;
    };

    // Отслеживание выхода со страницы без отправки формы
    window.addEventListener('beforeunload', function () {
        var modal = document.getElementById('<?= $formId ?>Modal');
        if (modal.classList.contains('show')) {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'form_abandoned', {
                    'form_id': '<?= $formId ?>',
                    'fields_filled': window.formManager_<?= $iblockId ?> ?
                        document.querySelectorAll('#<?= $formId ?> input:not([value=""]), #<?= $formId ?> textarea:not([value=""]), #<?= $formId ?> select:not([value=""])').length : 0
                });
            }
        }
    });
</script>