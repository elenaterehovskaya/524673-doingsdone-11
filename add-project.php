<?php
require_once("init.php");

if (!isset($_SESSION["user"])) {
    header("location: /guest.php");
    exit();
}

$user = $_SESSION["user"];
$userId = intval($_SESSION["user"]["id"]);

$mysqlErrorMessage = mysqli_connect_error();

// Проверяем наличие ошибок подключения к MySQL и выполняем запросы
if ($mysqlErrorMessage === null) {
    $mysqlErrorMessage = "";

    // SQL-запрос для получения списка проектов у текущего пользователя
    $sql = "SELECT id, name FROM projects WHERE user_id = " . $userId;
    $projectsResult = mysqli_query($link, $sql);

    if (!$projectsResult) {
        $mysqlErrorMessage = mysqli_error($link);
    } else {
        // Cписок проектов у текущего пользователя в виде двумерного массива
        $projects = mysqli_fetch_all($projectsResult, MYSQLI_ASSOC);
    }

    // SQL-запрос для получения списка всех задач у текущего пользователя
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.id AS project_id, p.name AS project, t.title, t.deadline, t.status 
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id 
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = $userId
SQL;
    $tasksAllResult = mysqli_query($link, $sql);

    if (!$tasksAllResult) {
        $mysqlErrorMessage = mysqli_error($link);
    } else {
        // Cписок всех задач у текущего пользователя в виде двумерного массива
        $tasksAll = mysqli_fetch_all($tasksAllResult, MYSQLI_ASSOC);
    }

    // ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // ВАЛИДАЦИЯ формы
        $project = $_POST;
        $validErrors = [];

        if (empty($project["name"])) {
            $validErrors["name"] = "Это поле должно быть заполнено";
        }

        $validateLength = validateLength($project["name"], 3, 15);
        if ($validateLength !== null) {
            $validErrors["name"] = $validateLength;
        }

        foreach ($projects as $value) {
            if (isset($project["name"])) {
                if (mb_strtoupper($project["name"]) === mb_strtoupper($value["name"])) {
                    $validErrors["name"] = "Проект с таким названием уже существует";
                }
            }
        }
        // Конец ВАЛИДАЦИИ формы

        // Подсчитываем количество элементов массива с ошибками. Если он не пустой, показываем ошибки вместе с формой
        if (count($validErrors)) {
            $pageContent = includeTemplate($templatePath . "form-project.php", [
                "projects" => $projects,
                "tasksAll" => $tasksAll,
                "validErrors" => $validErrors
            ]);

            $layoutContent = includeTemplate($templatePath . "layout.php", [
                "pageContent" => $pageContent,
                "user" => $user,
                "title" => "Дела в порядке | Добавление проекта"
            ]);

            print($layoutContent);
            exit();
        } else {
            // SQL-запрос на добавление нового проекта
            $sql = "INSERT INTO projects (user_id, name) VALUES ($userId, ?)";
            $projectNewResult = dbInsertData($link, $sql, $project);

            if (!$projectNewResult) {
                $mysqlErrorMessage = mysqli_error($link);
            } else {
                header("Location: index.php");
                exit();
            }
        }
    }
}

$pageContent = showMysqlError($templatePath, $mysqlErrorMessage);

if (!$mysqlErrorMessage) {
    $pageContent = includeTemplate($templatePath . "form-project.php", [
        "projects" => $projects,
        "tasksAll" => $tasksAll
    ]);
}

$layoutContent = includeTemplate($templatePath . "layout.php", [
    "pageContent" => $pageContent,
    "user" => $user,
    "title" => "Дела в порядке | Добавление проекта"
]);

print($layoutContent);