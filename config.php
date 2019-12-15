<?php
session_start();

date_default_timezone_set("Europe/Moscow");

require_once("data.php");
require_once("functions.php");

$config = [
    "siteName" => "Дела в порядке",
    "templatePath" => "templates/",
    "filePath" => __DIR__ . "/uploads/",
    // true — сайт доступен и работает; false — вместо страниц сайта будет показана заглушка (off.php)
    "enable" => true,

    "registerLengthRules" => [
        "password" => [
            "min" => 8,
            "max" => 32
        ],
        "name" => [
            "min" => 4,
            "max" => 20
        ]
    ],

    "addLengthRules" => [
        "project" => [
            "min" => 3,
            "max" => 15
        ],
        "title" => [
            "min" => 5,
            "max" => 255
        ]
    ]
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