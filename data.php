<?php
date_default_timezone_set("Europe/Moscow");

$title = "";
$content = "";
$show_complete_tasks = rand(0, 1);

// Название папки с шаблонами
$path_to_template = "templates/";

// Массив с названиями проектов
$projects = ["Входящие", "Учёба", "Работа", "Домашние дела", "Авто"];

// Двумерный массив с данными для задач проекта
$tasks = [
    [
        "title" => "Собеседование в IT компании",
        "deadline" => "01.12.2019",
        "project" => "Работа",
        "completed" => false
    ],
    [
        "title" => "Выполнить тестовое задание",
        "deadline" => "03.11.2019",
        "project" => "Работа",
        "completed" => false
    ],
    [
        "title" => "Сделать задание первого раздела",
        "deadline" => "21.12.2019",
        "project" => "Учёба",
        "completed" => true
    ],
    [
        "title" => "Встреча с другом",
        "deadline" => "22.12.2019",
        "project" => "Входящие",
        "completed" => false
    ],
    [
        "title" => "Купить корм для кота",
        "deadline" => null,
        "project" => "Домашние дела",
        "completed" => false
    ],
    [
        "title" => "Заказать пиццу",
        "deadline" => null,
        "project" => "Домашние дела",
        "completed" => false
    ]
];
