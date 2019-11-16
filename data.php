<?php
date_default_timezone_set("Europe/Moscow");

$mysqlConfig = [
    "host" => "localhost",
    "user" => "root",
    "password" => "",
    "database" => "doings_done"
];

// Название папки с шаблонами
$path_to_template = "templates/";

// HTML-код основного содержимого страницы
$content = "";

// title для страницы
$title = "";

// Текущий пользователь
$user = [];
$user_id = 2;

// Cписок проектов у текущего пользователя
$projects = [];

// Cписок задач у текущего пользователя
$tasks = [];

// Cписок задач у текущего пользователя без привязки к проекту
$all_tasks = [];

// Текст ошибки при выполнении SQL запросов
$error = "";

// Массив с ошибками при отправке формы
$errors = [];

$show_complete_tasks = rand(0, 1);
