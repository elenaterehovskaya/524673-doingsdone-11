<?php
date_default_timezone_set("Europe/Moscow");

// Название папки с шаблонами
$path_to_template = "templates/";

// title для страницы
$title = "";

// Текущий пользователь
$user = [];

// Cписок проектов у текущего пользователя
$projects = [];

// Cписок задач у текущего пользователя без привязки к проекту
$all_tasks = [];

// Cписок задач у текущего пользователя
$tasks = [];

// HTML-код основного содержимого страницы
$content = "";

// Текст ошибки
$error = "";

$show_complete_tasks = rand(0, 1);
