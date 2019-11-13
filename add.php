<?php
require_once("data.php");
require_once("functions.php");

// Подключение к MySQL
// Включаем преобразование целочисленных значений и чисел с плавающей запятой из столбцов таблицы в PHP числа
$link = mysqli_init();
mysqli_options($link, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
mysqli_real_connect($link, "localhost", "root", "", "doings_done");

// Устанавливаем кодировку при работе с MySQL
mysqli_set_charset($link, "utf8");

// Проверяем подключение и выполняем запросы
if ($link === false) {
    // Ошибка подключения к MySQL
    $error_string = mysqli_connect_error();
    $error_content = include_template($path_to_template . "error.php", [
        "error" => $error_string
    ]);
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
    $user_id = 2;

    $sql = "SELECT id, name FROM users WHERE id = " . $user_id;
    $result = mysqli_query($link, $sql);

    if ($result === false) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);
        $error_content = include_template($path_to_template . "error.php", [
            "error" => $error_string
        ]);
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
            "error" => $error_string
        ]);
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

    /*
     * SQL-запрос для получения списка из всех задач у текущего пользователя без привязки к проекту
     */
    $sql = <<<SQL
    SELECT tasks.id, tasks.user_id, projects.id AS 'project_id', projects.name AS project, tasks.title, tasks.deadline, tasks.status 
    FROM tasks
    LEFT JOIN projects ON tasks.project_id = projects.id 
    LEFT JOIN users ON tasks.user_id = users.id
    WHERE tasks.user_id = $user_id
SQL;
    $result = mysqli_query($link, $sql);

    if ($result === false || mysqli_num_rows($result) == 0) {
        // Ошибка при выполнении SQL запроса или SQL запрос не вернул ни одной записи
        http_response_code(404);
        $error_string = mysqli_error($link);
        $error_content = include_template($path_to_template . "error.php", [
            "error" => $error_string
        ]);
        $layout_content = include_template($path_to_template . "layout.php", [
            "content" => $error_content,
            "user" => $user,
            "title" => "Дела в порядке"
        ]);
        print($layout_content);
        exit;
    } else {
        // Получаем список из всех задач у текущего пользователя без привязки к проекту в виде двумерного массива
        $all_tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    /*
     * Получаем из полей формы необходимые данные от пользователя и сохраняем их в БД
     */
    // Страница запрошена методом POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Скопируем POST массив в новую переменную $_POST["name"], $_POST["project"], $_POST["date"]
        $task = $_POST;
        debug($task);

        // SQL-запрос на добавление новой задачи (на месте значений — знаки вопроса — плейсхолдеры)
        $sql = "INSERT INTO tasks (user_id, title, project_id, deadline) VALUES (2, ?, ?, ?)";
        // С помощью функции-помощника формируем подготовленное выражение, на основе SQL-запроса и значений для него
        $stmt = db_get_prepare_stmt($link, $sql, $task);
        // Выполняем полученное выражение
        $result = mysqli_stmt_execute($stmt);

        if ($result === false) {
            // Ошибка при выполнении SQL запроса
            $error_string = mysqli_error($link);
            $error_content = include_template($path_to_template . "error.php", [
                "error" => $error_string
            ]);
            $layout_content = include_template($path_to_template . "layout.php", [
                "content" => $error_content,
                "user" => $user,
                "title" => "Дела в порядке"
            ]);
            print($layout_content);
            exit;
        } else {
            // Если запрос выполнен успешно, переадресовываем пользователя на главную страницу
            $page_content = include_template($path_to_template . "main.php", [
                "show_complete_tasks" => $show_complete_tasks,
                "projects" => $projects,
                "all_tasks" => $all_tasks,
                "tasks" => $tasks
            ]);
            $layout_content = include_template($path_to_template . "layout.php", [
                "content" => $page_content,
                "user" => $user,
                "title" => "Дела в порядке"
            ]);
            print($layout_content);
            exit;
        }
    }
}

// Подключаем шаблон страницы Добавления задачи и передаём туда необходимые данные: список проектов, полный список задач у текущего пользователя
$page_content = include_template($path_to_template . "form-task.php", [
    "projects" => $projects,
    "all_tasks" => $all_tasks
]);

// Подключаем Лейаут и передаём туда необходимые данные: HTML-код основного содержимого страницы, имя пользователя и title для страницы
$layout_content = include_template($path_to_template . "layout.php", [
    "content" => $page_content,
    "user" => $user,
    "title" => "Дела в порядке"
]);

print($layout_content);
