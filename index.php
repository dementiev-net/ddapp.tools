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
	[
		"IBLOCK_ID" => "97",
		"EMAIL_TEMPLATE" => "DDAPP_MESSAGE_FORM",
		"USE_BITRIX_CAPTCHA" => "N",
		"USE_GOOGLE_RECAPTCHA" => "N",
		"GOOGLE_RECAPTCHA_PUBLIC_KEY" => "",
		"GOOGLE_RECAPTCHA_SECRET_KEY" => "",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
		"COMPONENT_TEMPLATE" => ".default",
		"BUTTON_TEXT" => "Открыть форму",
		"ENABLE_ANALYTICS" => "Y",
		"USE_PRIVACY_POLICY" => "Y",
		"PRIVACY_POLICY_TEXT" => "Я согласен с политикой обработки персональных данных",
		"PRIVACY_POLICY_LINK" => "/privacy-policy/",
		"RATE_LIMIT_ENABLED" => "Y",
		"RATE_LIMIT_PER_MINUTE" => "5",
		"RATE_LIMIT_PER_HOUR" => "30",
		"MAX_FILE_SIZE" => "10",
		"ALLOWED_FILE_EXTENSIONS" => "jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip",
		"FILE_UPLOAD_DIR" => "/upload/ddapp_forms/",
		"CHECK_FILE_CONTENT" => "Y",
		"SECURITY_LEVEL" => "medium",
		"GA_MEASUREMENT_ID" => "",
		"YANDEX_METRIKA_ID" => "",
		"VK_PIXEL_ID" => ""
	],
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