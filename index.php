<?php
require_once("init.php");

// Если пользователь не вошёл в систему (т.е. нет о нем информации в сессии), переходим на гостевую страницу и выходим
if (!isset($_SESSION["user"])) {
    header("location: /guest.php");
    exit;
}

$user = $_SESSION["user"];
$user_id = $_SESSION["user"]["id"];

// Проверяем подключение и выполняем запросы
if ($link === false) {
    // Ошибка подключения к MySQL
    $error_string = mysqli_connect_error();
}
else {
    // SQL-запрос для получения списка проектов у текущего пользователя
    $sql = "SELECT id, name FROM projects WHERE user_id = " . $user_id ;
    $result = mysqli_query($link, $sql);

    if ($result === false) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);
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
            $error_string = "Не найдено проекта с таким ID!";
        }
    }

    // SQL-запрос для получения списка из всех задач у текущего пользователя без привязки к проекту
    $sql = <<<SQL
     SELECT t.id, t.user_id, p.id AS project_id, p.name AS project, t.title, t.deadline, t.status 
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id 
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = $user_id
SQL;
    $result = mysqli_query($link, $sql);

    if ($result === false) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);
    } else {
        // Получаем список из всех задач у текущего пользователя без привязки к проекту в виде двумерного массива
        $all_tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // SQL-запрос для получения списка всех задач у текущего пользователя
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = $user_id
SQL;

    if (isset($_GET["id"])) {
        $project_id = intval($_GET["id"]);
        $sql .= " and p.id = $project_id ORDER BY t.id DESC";
    }
    else {
        $sql .= " ORDER BY t.id DESC";
    }
    $result = mysqli_query($link, $sql);
    $records_count = mysqli_num_rows($result);

    if ($result === false || $records_count == 0) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);

        if ($records_count == 0) {
            http_response_code(404);
            $error_string = "Не найдено ни одной задачи для данного проекта!";
        }
    } else {
        // Получаем список из всех задач у текущего пользователя в виде двумерного массива
        $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
        // Добавляем в массив кол-во часов, оставшихся до даты окончания выполнения задач
        $tasks = addHoursUntilEnd2Tasks($tasks);
    }

    // Список задач, найденных по поисковому запросу с использование FULLTEXT поиска MySQL
    $search = "";
    $task_serch = [];

    if (isset($_GET["q"])) {
        $search = htmlspecialchars($_GET["q"]);
    }

    if ($search) {
        $sql = <<<SQL
        SELECT t.id, t.user_id, p.id AS project_id, p.name AS project, t.title
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.user_id = $user_id and MATCH(title) AGAINST(?); 
SQL;
        $stmt = dbGetPrepareStmt($link, $sql, [$search]);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result === false) {
            // Ошибка при выполнении SQL запроса
            $error_string = mysqli_error($link);
        }
        else {
            $task_search = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $records_count = mysqli_num_rows($result);
        }

        if ($records_count == 0) {
            $search_message  = "Ничего не найдено по вашему запросу";
        }
    }
}

if ($error_string) {
    showMysqliError($page_content, $tpl_path, $error_string);
}
else {
    $page_content = includeTemplate($tpl_path . "main.php", [
        "show_complete_tasks" => $show_complete_tasks,
        "projects" => $projects,
        "all_tasks" => $all_tasks,
        "tasks" => $tasks,
        "task_search" => $task_search,
        "search_message" => $search_message
    ]);
}

$layout_content = includeTemplate($tpl_path . "layout.php", [
    "content" => $page_content,
    "user" => $user,
    "title" => "Дела в порядке"
]);

print($layout_content);
