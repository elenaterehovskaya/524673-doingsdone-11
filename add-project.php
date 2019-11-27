<?php
require_once("init.php");

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
    /*
     * SQL-запрос для получения списка проектов у текущего пользователя
     */
    $sql = "SELECT id, name FROM projects WHERE user_id = " . $user_id;
    $result = mysqli_query($link, $sql);
    if ($result === false) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);
    } else {
        // Получаем список проектов у текущего пользователя в виде двумерного массива
        $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    /*
     * SQL-запрос для получения списка из всех задач у текущего пользователя без привязки к проекту
     */
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
    /*
     * ПОЛУЧАЕМ ИЗ ПОЛЕЙ ФОРМЫ необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
     */
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $project = $_POST;
        $errors = [];

        if (empty($project["name"])) {
            $errors["name"] = "Это поле должно быть заполнено";
        }

        $validateLength = validateLength($project["name"], 3, 15);
        if ( $validateLength !== null ) {
            $errors["name"] = $validateLength;
        }

        foreach ($projects as $value) {
            if (isset($project["name"])) {
                if (mb_strtoupper($project["name"]) === mb_strtoupper($value["name"])) {
                    $errors["name"] = "Проект с таким названием уже существует";
                }
            }
        }

        if (count($errors)) {
            $page_content = includeTemplate($tpl_path . "form-project.php", [
                "projects" => $projects,
                "all_tasks" => $all_tasks,
                "errors" => $errors
            ]);
            $layout_content = includeTemplate($tpl_path . "layout.php", [
                "content" => $page_content,
                "user" => $user,
                "title" => "Дела в порядке | Добавление проекта"
            ]);
            print($layout_content);
            exit;
        } else {
            // SQL-запрос на добавление нового проекта
            $sql = "INSERT INTO projects (user_id, name) VALUES ($user_id, ?)";
            $stmt = dbGetPrepareStmt($link, $sql, $project);
            $result = mysqli_stmt_execute($stmt);
            if ($result === false) {
                // Ошибка при выполнении SQL запроса
                $error_string = mysqli_error($link);
            } else {
                header("Location: index.php");
                exit();
            }
        }
    }
}

if ($error_string) {
    showMysqliError($page_content, $tpl_path, $error_string);
}
else {
    $page_content = includeTemplate($tpl_path . "form-project.php", [
        "projects" => $projects,
        "all_tasks" => $all_tasks
    ]);
}

$layout_content = includeTemplate($tpl_path . "layout.php", [
    "content" => $page_content,
    "user" => $user,
    "title" => "Дела в порядке | Добавление проекта"
]);

print($layout_content);
