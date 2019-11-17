<?php
require_once("data.php");
require_once("functions.php");

// Подключение к MySQL
// Включаем преобразование целочисленных значений и чисел с плавающей запятой из столбцов таблицы в PHP числа
$link = mysqli_init();
mysqli_options($link, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
mysqli_real_connect($link, $mysqlConfig["host"], $mysqlConfig["user"], $mysqlConfig["password"], $mysqlConfig["database"]);

// Устанавливаем кодировку при работе с MySQL
mysqli_set_charset($link, "utf8");

// Проверяем подключение и выполняем запросы
if ($link === false) {
    // Ошибка подключения к MySQL
    $error_string = mysqli_connect_error();
    $error_content = includeTemplate($path_to_template . "error.php", [
        "error" => $error_string
    ]);
    $layout_content = includeTemplate($path_to_template . "layout.php", [
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
    $sql = "SELECT id, name FROM users WHERE id = " . $user_id;
    $result = mysqli_query($link, $sql);

    if ($result === false) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);
        $error_content = includeTemplate($path_to_template . "error.php", [
            "error" => $error_string
        ]);
        $layout_content = includeTemplate($path_to_template . "layout.php", [
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
        $error_content = includeTemplate($path_to_template . "error.php", [
            "error" => $error_string
        ]);
        $layout_content = includeTemplate($path_to_template . "layout.php", [
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

    // Проверяем для нашего текущего ID проекта из адресной строки его существование в наших проектах
    if (isset($_GET["id"])) {
        $project_id = intval($_GET["id"]);
        $weFindProject = false;

        foreach ($projects as $key => $value) {
            if ($project_id === $value["id"]) {
                $weFindProject = true;
                break;
            }
        }
        if ($weFindProject === false) {
            // Ошибка: значения параметра запроса не существует
            http_response_code(404);
            $error_string = 'Не найдено проекта с таким ID!';
            $error_content = includeTemplate($path_to_template . "error.php", [
                "error" => $error_string
            ]);
            $layout_content = includeTemplate($path_to_template . "layout.php", [
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
    SELECT tasks.id, tasks.user_id, projects.id AS project_id, projects.name AS project, tasks.title, tasks.deadline, tasks.status 
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
        $error_content = includeTemplate($path_to_template . "error.php", [
            "error" => $error_string
        ]);
        $layout_content = includeTemplate($path_to_template . "layout.php", [
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
     * SQL-запрос для получения списка всех задач у текущего пользователя
     */
    $sql = <<<SQL
    SELECT tasks.id, tasks.user_id, projects.name AS project, tasks.title, tasks.file, tasks.deadline, tasks.status 
    FROM tasks
    LEFT JOIN projects ON tasks.project_id = projects.id 
    LEFT JOIN users ON tasks.user_id = users.id
    WHERE tasks.user_id = $user_id
SQL;
    if (isset($_GET["id"])) {
        $project_id = intval($_GET["id"]);
        $sql .= " and projects.id = $project_id ORDER BY tasks.id DESC";
    }
    else {
        $sql .= " ORDER BY tasks.id DESC";
    }
    $result = mysqli_query($link, $sql);
    $records_count = mysqli_num_rows($result);

    if ($result === false || $records_count == 0) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);
        if ($records_count == 0) {
            http_response_code(404);
            $error_string = 'Не найдено ни одной задачи для данного проекта!';
        }
        $error_content = includeTemplate($path_to_template . "error.php", [
            "error" => $error_string
        ]);
        $layout_content = includeTemplate($path_to_template . "layout.php", [
            "content" => $error_content,
            "user" => $user,
            "title" => "Дела в порядке"
        ]);
        print($layout_content);
        exit;
    } else {
        // Получаем список из всех задач у текущего пользователя в виде двумерного массива
        $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
        // Добавляем в массив кол-во часов, оставшихся до даты окончания выполнения задач
        $tasks = addHoursUntilEnd2Tasks($tasks);
    }
}

// Подключаем шаблон «Главной страницы» и передаём туда необходимые данные: список проектов, полный список задач и список задач у текущего пользователя
$page_content = includeTemplate($path_to_template . "main.php", [
    "show_complete_tasks" => $show_complete_tasks,
    "projects" => $projects,
    "all_tasks" => $all_tasks,
    "tasks" => $tasks
]);

// Подключаем «Лейаут» и передаём туда необходимые данные: HTML-код основного содержимого страницы, имя пользователя и title для страницы
$layout_content = includeTemplate($path_to_template . "layout.php", [
    "content" => $page_content,
    "user" => $user,
    "title" => "Дела в порядке"
]);

print($layout_content);
