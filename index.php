<?php
require_once("data.php");
require_once("functions.php");

// Подключение к MySQL
$link = mysqli_connect("localhost", "root", "", "doings_done");

// Кодировка при работе с MySQL
mysqli_set_charset($link, "utf8");

// Проверка подключения и выполнение запросов
if ($link == false) {
    print("Ошибка подключения к MySQL: " . mysqli_connect_error());
}
else {
    // SQL-запрос для получения данных о текущем пользователе
    $sql = "SELECT id, name FROM users WHERE id = 1";
    $result = mysqli_query($link, $sql);

    if ($result == false) {
        print("Произошла ошибка при выполнении SQL запроса: " . mysqli_error($link));
    }
    else {
        $user = mysqli_fetch_assoc($result);
    }

    // SQL-запрос для получения списка проектов у текущего пользователя
    $sql = "SELECT id, name FROM projects WHERE user_id = 1";
    $result = mysqli_query($link, $sql);

    if ($result == false) {
        print("Произошла ошибка при выполнении SQL запроса: " . mysqli_error($link));
    }
    else {
        $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // SQL-запрос для получения списка из всех задач у текущего пользователя
    $sql = "SELECT tasks.id, tasks.user_id, projects.name AS project,  tasks.title, tasks.deadline, tasks.status FROM tasks "
        . "LEFT JOIN projects ON tasks.project_id = projects.id "
        . "LEFT JOIN users ON tasks.user_id = users.id "
        . "WHERE tasks.user_id = 1";
    $result = mysqli_query($link, $sql);

    if ($result == false) {
        print("Произошла ошибка при выполнении SQL запроса: " . mysqli_error($link));
    }
    else {
        $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// Подключаем шаблон главной страницы и передаём туда необходимые данные: список проектов и список задач у текущего пользователя
$page_content = include_template($path_to_template . "main.php", [
    "show_complete_tasks" => $show_complete_tasks,
    "projects" => $projects,
    "tasks" => $tasks]);

// Подключаем лейаут и передаём туда необходимые данные: HTML-код основного содержимого страницы, имя пользователя и title для страницы
$layout_content = include_template($path_to_template . "layout.php", [
    "content" => $page_content,
    "user" => $user,
    "title" => "Дела в порядке"
    ]);

print($layout_content);
