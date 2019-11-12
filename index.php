<?php
require_once("data.php");
require_once("functions.php");

// Подключение к MySQL
$link = mysqli_connect("localhost", "root", "", "doings_done");

// Кодировка при работе с MySQL
mysqli_set_charset($link, "utf8");

// Проверка подключения и выполнение запросов
if ($link === false) {
    // Ошибка подключения к MySQL
    $error_string = mysqli_connect_error();
    $error_content = include_template($path_to_template . "error.php", [
        "error" => $error_string]);
    $layout_content = include_template($path_to_template . "layout.php", [
        "content" => $error_content,
        "user" => $user,
        "title" => "Дела в порядке"
    ]);
    print($layout_content);
    exit;
}
else {
    /*
     * SQL-запрос для получения данных о текущем пользователе
     */
    $user_id = 1;

    $sql = "SELECT id, name FROM users WHERE id = " . $user_id;
    $result = mysqli_query($link, $sql);

    if ($result === false) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);
        $error_content = include_template($path_to_template . "error.php", [
            "error" => $error_string]);
        $layout_content = include_template($path_to_template . "layout.php", [
            "content" => $error_content,
            "user" => $user,
            "title" => "Дела в порядке"
        ]);
        print($layout_content);
        exit;
    } else {
        // Получаем данные о пользователе в виде ассоциативного массива
        $user = mysqli_fetch_assoc($result);
    }

    /*
     * SQL-запрос для получения списка проектов у текущего пользователя
     */
    $sql = "SELECT id, name FROM projects WHERE user_id = " . $user_id;
    $result = mysqli_query($link, $sql);

    if ($result === false) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);
        $error_content = include_template($path_to_template . "error.php", [
            "error" => $error_string]);
        $layout_content = include_template($path_to_template . "layout.php", [
            "content" => $error_content,
            "user" => $user,
            "title" => "Дела в порядке"
        ]);
        print($layout_content);
        exit;
    } else {
        // Получаем список проектов у текущего пользователя в виде двумерного массива
        $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // Проверим для нашего текущего ID проекта из адресной строки его существование в наших проектах
    if (isset($_GET['id'])) {
        $project_id = intval($_GET['id']);
        $weFindProject = false;
        foreach($projects as $key => $value){
            if ($project_id === intval($value['id'])){
                $weFindProject = true;
                break;
            }
        }
        if ($weFindProject === false){
            http_response_code(404);
            $error_string = 'Не найдено проекта с таким ID!';
            $error_content = include_template($path_to_template . "error.php", [
                "error" => $error_string]);
            $layout_content = include_template($path_to_template . "layout.php", [
                "content" => $error_content,
                "user" => $user,
                "title" => "Дела в порядке"
            ]);
            print($layout_content);
            exit;
        }
    }

    /*
     * SQL-запрос для получения списка из всех задач у текущего пользователя без привязки к проекту
     */
    $sql = <<<SQL
    SELECT tasks.id, tasks.user_id, projects.id as 'project_id', projects.name AS project, tasks.title, tasks.deadline, tasks.status 
    FROM tasks
    LEFT JOIN projects ON tasks.project_id = projects.id 
    LEFT JOIN users ON tasks.user_id = users.id
    WHERE tasks.user_id = $user_id
SQL;
    $result = mysqli_query($link, $sql);
    if ($result === false || mysqli_num_rows($result) == 0) {
        http_response_code(404);
        $error_string = mysqli_error($link);
        $error_content = include_template($path_to_template . "error.php", [
            "error" => $error_string]);
        $layout_content = include_template($path_to_template . "layout.php", [
            "content" => $error_content,
            "user" => $user,
            "title" => "Дела в порядке"
        ]);
        print($layout_content);
        exit;
    } else {
        // Получаем список из всех задач у текущего пользователя в виде двумерного массива
        $all_tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    /*
     * SQL-запрос для получения списка всех задач у текущего пользователя
     */
    $sql = <<<SQL
    SELECT tasks.id, tasks.user_id, projects.name AS project, tasks.title, tasks.deadline, tasks.status 
    FROM tasks
    LEFT JOIN projects ON tasks.project_id = projects.id 
    LEFT JOIN users ON tasks.user_id = users.id
    WHERE tasks.user_id = $user_id
SQL;
    if (isset($_GET['id'])) {
        $project_id = intval($_GET['id']);
        $sql = $sql . " and projects.id = " . $project_id;
    }
    $result = mysqli_query($link, $sql);
    if ($result === false || mysqli_num_rows($result) == 0) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);
        if (mysqli_num_rows($result) == 0) {
            $error_string = 'Не найдено ни одной задачи для данного проекта!';
        }
        $error_content = include_template($path_to_template . "error.php", [
            "error" => $error_string]);
        $layout_content = include_template($path_to_template . "layout.php", [
            "content" => $error_content,
            "user" => $user,
            "title" => "Дела в порядке"
        ]);
        print($layout_content);
        exit;
    } else {
        // Получаем список из всех задач у текущего пользователя в виде двумерного массива
        $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $tasks = addHoursUntilEnd2Tasks($tasks);
    }
}

// Подключаем шаблон главной страницы и передаём туда необходимые данные: список проектов и список задач у текущего пользователя
$page_content = include_template($path_to_template . "main.php", [
    "show_complete_tasks" => $show_complete_tasks,
    "projects" => $projects,
    "all_tasks" => $all_tasks,
    "tasks" => $tasks]);

// Подключаем лейаут и передаём туда необходимые данные: HTML-код основного содержимого страницы, имя пользователя и title для страницы
$layout_content = include_template($path_to_template . "layout.php", [
    "content" => $page_content,
    "user" => $user,
    "title" => "Дела в порядке"
    ]);

print($layout_content);
