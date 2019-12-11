<?php
// Основные параметры сайта: название, путь к шаблонам и статус работы сайта
$config = [
    "siteName" => "Дела в порядке",
    "templatePath" => "templates/",
    // true — сайт доступен и работает; false — вместо страниц сайта будет показана заглушка (off.php)
    "enable" => true
];

// Параметры для подключения к БД
$mysqlConfig = [
    "host" => "localhost",
    "user" => "root",
    "password" => "",
    "database" => "doings_done"
];

// Параметры для отправки электронного сообщения (e-mail рассылки)
$yandexMailConfig = [
    "userName" => "testemaily@yandex.ru",
    "password" => "WEB_web_WEB",
    "domain" => "smtp.yandex.ru",
    "port" => "587",
    "encryption" => "tls",

    "userCaption" => "Дела в порядке",
    "subject" => "Уведомление от сервиса «Дела в порядке»"
];