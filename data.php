<?php
date_default_timezone_set("Europe/Moscow");

// Название папки с шаблонами
$path_to_template = "templates/";

// HTML-код основного содержимого страницы
$content = "";

// title для страницы
$title = "";

// Cписок проектов у текущего пользователя
$projects = [];

// Cписок задач у текущего пользователя
$tasks = [];

// Cписок задач у текущего пользователя без привязки к проекту
$all_tasks = [];

// Текст ошибок при валидации формы и при выполнении SQL запросов
$error = "";
$error_message = "";
$error_string = "";

// Массив с ошибками при отправке формы
$errors = [];

$show_complete_tasks = rand(0, 1);
