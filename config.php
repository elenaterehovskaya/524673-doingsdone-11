<?php
// Файл конфигурации. Здесь задаются основные параметры сайта: название и путь к шаблонам
// Также здесь присутствует важная опция — статус работы сайта. За него отвечает ключ "enable"
// Если он равен false, то вместо страниц сайта будет показана заглушка
$config = [
    "sitename" => "Дела в порядке",
    "tpl_path" => "templates/",
    // сайт доступен и работает
    "enable" => true
];

$yandexMailConfig = [
    "user_name" => "testemaily@yandex.ru",
    "password" => "WEB_web_WEB",
    "domain" => "smtp.yandex.ru",
    "port" => "465"
];

$swiftMailerConfig = [
    "user_name" => "9f02a757379c29",
    "password" => "24872c91be709a",
    "address" => "smtp.mailtrap.io",
    "domain" => "smtp.mailtrap.io",
    "port" => "2525",
    "authentication" => "cram_md5"
];

/*
$swiftMailerConfig = [
    "domain" => "phpdemo.ru",
    "port" => "25",
    "user_name" => "keks@phpdemo.ru",
    "password" => "htmlacademy"
];
*/
