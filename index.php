<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Главная');

// Подключаем необходимые CSS/JS библиотеки
$APPLICATION->SetAdditionalCSS("https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css");
$APPLICATION->AddHeadScript("https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js");

// Подключаем Font Awesome для иконок
$APPLICATION->SetAdditionalCSS("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css");

?>
    <section class="container my-5">
        <h1>ГЛАВНАЯ</h1>
        <p>
            <a href="/test/auth/"
               class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">Авторизация</a>
        </p>
        <p>
            <a href="/test/forms/"
               class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">Формы</a>
        </p>
    </section>

<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');