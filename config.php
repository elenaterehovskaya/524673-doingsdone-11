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
    "port" => "587",
    "encryption" => "tls",

    "user_caption" => "Дела в порядке",
    "subject" => "Уведомление от сервиса «Дела в порядке»"
];
