<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Главная');
?>

    <section class="container my-5">

        <?
//        $APPLICATION->IncludeComponent(
//            "dd.tools:form.iblock",
//            ".default",
//            array(
//                "IBLOCK_ID" => "48",
//                "FORM_ID" => "contact_form",
//                "EMAIL_TEMPLATE" => "NEW_FEEDBACK",
//                "USE_CAPTCHA" => "N",
//                "USE_RECAPTCHA" => "N",
//                "RECAPTCHA_PUBLIC_KEY" => "your_public_key",
//                "RECAPTCHA_SECRET_KEY" => "your_secret_key",
//                "CACHE_TYPE" => "A",
//                "CACHE_TIME" => "3600"
//            )
//        );
        ?>

        <?php
        $APPLICATION->IncludeComponent(
            "ddapp.tools:forms",
            ".default",
            array(
                "IBLOCK_ID" => 97, // ID инфоблока
                "EMAIL_TEMPLATE" => "DDAPP_MESSAGE_FORM", // ID шаблона письма
                "USE_BITRIX_CAPTCHA" => "N", // Использовать Bitrix Captcha
                "USE_GOOGLE_RECAPTCHA" => "N", // Использовать Google reCAPTCHA v3
                "GOOGLE_RECAPTCHA_PUBLIC_KEY" => "", // Публичный ключ Google reCAPTCHA v3
                "GOOGLE_RECAPTCHA_SECRET_KEY" => "", // Секретный ключ Google reCAPTCHA v3
                "CACHE_TYPE" => "A",
                "CACHE_TIME" => "3600"
            ),
            false
        );
        ?>
    </section>

<?php

// Пример с Google reCAPTCHA
//$APPLICATION->IncludeComponent(
//    "dd.tools:forms",
//    ".default",
//    array(
//        "EMAIL_TEMPLATE" => "CONTACT_FORM",
//        "USE_BITRIX_CAPTCHA" => "N",
//        "USE_GOOGLE_RECAPTCHA" => "Y",
//        "GOOGLE_RECAPTCHA_PUBLIC_KEY" => "6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI",
//        "GOOGLE_RECAPTCHA_SECRET_KEY" => "6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe",
//        "CACHE_TYPE" => "A",
//        "CACHE_TIME" => "3600"
//    ),
//    false
//);
//$APPLICATION->IncludeComponent(
//    "dd.tools:form.pro",
//    ".default",
//    array(
//        "IBLOCK_ID" => "48", // ID инфоблока с полями формы
//        "EMAIL_TEMPLATE" => "FEEDBACK_FORM", // Почтовый шаблон
//        "SUCCESS_REDIRECT" => "/thank-you/", // Страница благодарности
//        "USE_CSRF" => "Y", // Включить CSRF защиту
//        "USE_BITRIX_CAPTCHA" => "Y", // Использовать Bitrix капчу
//        "USE_GOOGLE_RECAPTCHA" => "N", // Использовать Google reCAPTCHA v3
//        "GOOGLE_RECAPTCHA_PUBLIC_KEY" => "6L...", // Публичный ключ
//        "GOOGLE_RECAPTCHA_SECRET_KEY" => "6L...", // Секретный ключ
//        "MAX_FILE_SIZE" => "5", // Макс. размер файла в МБ
//        "ALLOWED_FILE_TYPES" => "jpg,jpeg,png,pdf", // Разрешенные типы файлов
//        "USE_PHONE_MASK" => "Y", // Включить маску телефона
//        "USE_GOOGLE_ANALYTICS" => "Y", // Интеграция с GA
//        "USE_YANDEX_METRIKA" => "Y", // Интеграция с Яндекс.Метрикой
//        "CACHE_TIME" => "3600",
//        "CACHE_GROUPS" => "Y"
//    ),
//    false
//);
?>


<?
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
?>