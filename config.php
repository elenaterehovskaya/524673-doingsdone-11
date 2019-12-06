<?php
// Основные параметры сайта: название, путь к шаблонам и статус работы сайта
$config = [
    "sitename" => "Дела в порядке",
    "tpl_path" => "templates/",
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

// Параметры для отправки email сообщения (рассылки)
$yandexMailConfig = [
    "user_name" => "testemaily@yandex.ru",
    "password" => "WEB_web_WEB",
    "domain" => "smtp.yandex.ru",
    "port" => "587",
    "encryption" => "tls",

    "user_caption" => "Дела в порядке",
    "subject" => "Уведомление от сервиса «Дела в порядке»"
];
