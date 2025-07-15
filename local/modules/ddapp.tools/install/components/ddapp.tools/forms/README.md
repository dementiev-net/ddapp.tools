Пример вызова

```php
        <?php
        $APPLICATION->IncludeComponent(
            "ddapp.tools:forms",
            ".default",
            array(
                "IBLOCK_ID" => 49, // ID инфоблока
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
```