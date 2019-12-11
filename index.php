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
    $projects = [];
    $tasks =[];

    // SQL-запрос для получения списка проектов у текущего пользователя
    $sql = "SELECT id, name FROM projects WHERE user_id = " . $userId;
    $projectsResult = mysqli_query($link, $sql);

    if (!$projectsResult) {
        $mysqlErrorMessage = mysqli_error($link);
    } else {
        $projects = mysqli_fetch_all($projectsResult, MYSQLI_ASSOC);
    }

    // Проверяем для текущего ID проекта из адресной строки его существование в массиве проектов
    if (isset($_GET["id"])) {
        $projectId = intval($_GET["id"]);

        $findProjectId = false;
        foreach ($projects as $key => $value) {
            if ($projectId === $value["id"]) {
                $findProjectId = true;
                break;
            }
        }

        if ($findProjectId === false) {
            http_response_code(404);
            $mysqlErrorMessage = "Не найдено проекта с таким ID";
        }
    }

    // SQL-запрос для получения списка всех задач у текущего пользователя
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.name AS project, t.title
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id 
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = $userId
SQL;
    $tasksAllResult = mysqli_query($link, $sql);

    if (!$tasksAllResult) {
        $mysqlErrorMessage = mysqli_error($link);
    } else {
        $tasksAll = mysqli_fetch_all($tasksAllResult, MYSQLI_ASSOC);
    }

    // SQL-запрос для получения списка всех задач у текущего пользователя для каждого проекта
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = $userId
SQL;

    if (isset($_GET["id"])) {
        $projectId = intval($_GET["id"]);

        $sql .= " and p.id = $projectId ORDER BY t.id DESC";
    } else {
        $sql .= " ORDER BY t.id DESC";
    }

    $tasksResult = mysqli_query($link, $sql);
    $recordsCount = mysqli_num_rows($tasksResult);

    if (!$tasksResult) {
        $mysqlErrorMessage = mysqli_error($link);
    } else {
        if (isset($_GET["id"]) && $recordsCount == 0) {
            http_response_code(404);
            $mysqlErrorMessage = "Не найдено ни одной задачи для данного проекта";
        } else {
            $tasks = mysqli_fetch_all($tasksResult, MYSQLI_ASSOC);
            $tasks = addHoursUntilEndTask($tasks);
        }
    }

    // Список задач, найденных по поисковому запросу с использование FULLTEXT поиска MySQL
    $search = "";
    $searchTasks = [];

    if (isset($_GET["query"])) {
        $search = trim($_GET["query"]);

        if ($search) {
            $sql = <<<SQL
            SELECT t.id, t.user_id, p.id AS project_id, p.name AS project, t.title
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.user_id = $userId and MATCH(title) AGAINST(?) ORDER BY t.id DESC
SQL;
            $searchTasksResult = dbSelectData($link, $sql, [$search]);

            if (!$searchTasksResult) {
                $mysqlErrorMessage = mysqli_error($link);
            } else {
                $searchTasks = mysqli_fetch_all($searchTasksResult, MYSQLI_ASSOC);
                $countRecords = mysqli_num_rows($searchTasksResult);
            }

            if ($countRecords == 0) {
                http_response_code(404);
                $searchTasksMessage = "Ничего не найдено по вашему запросу";
            }
        }
    }

    // Блок сортировки задач (задачи на сегодня, на завтра, просроченные)
    $url = "";
    $urlLink = "";

    if (isset($_GET["show_completed"])) {
        $showCompleteTasks = intval($_GET["show_completed"]);
        $_GET["show_completed"] = intval(!($showCompleteTasks));
    }

    // Возвращает информацию о path в виде ассоциативного массива
    $scriptName = pathinfo(__FILE__, PATHINFO_BASENAME);
    // Преобразует ассоциативный массив в строку запроса
    $query = http_build_query($_GET);
    $url = "/" . $scriptName . "?" . $query;

    if (mb_strpos($url, "show_completed") === false) {
        $reverseCompleteTasks = intval(!$showCompleteTasks);
        $urlLink = "&show_completed={$reverseCompleteTasks}";
    }

    $filter = $_GET;

    if (isset($filter["tab"])) {
        if ($filter["tab"] === "today") {
            // SQL-запрос для получения списка задач «Повестка дня»
            $sql = <<<SQL
            SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.user_id = u.id
            WHERE DATE(t.deadline) = DATE(NOW()) and t.user_id = $userId ORDER BY t.id DESC
SQL;
            $filterResult = mysqli_query($link, $sql);

            if (!$filterResult) {
                $mysqlErrorMessage = mysqli_error($link);
            }
        }

        if ($filter["tab"] === "tomorrow") {
            // SQL-запрос для получения списка задач на «Завтра»
            $sql = <<<SQL
            SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.user_id = u.id
            WHERE DATE (t.deadline) = DATE(DATE_ADD(NOW(), INTERVAL 24 HOUR)) and t.user_id = $userId ORDER BY t.id DESC
SQL;
            $filterResult = mysqli_query($link, $sql);

            if (!$filterResult) {
                $mysqlErrorMessage = mysqli_error($link);
            }
        }

        if ($filter["tab"] === "past") {
            // SQL-запрос для получения списка «Просроченные»
            $sql = <<<SQL
            SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.user_id = u.id
            WHERE DATE(t.deadline) < DATE(NOW()) and t.user_id = $userId ORDER BY t.id DESC
SQL;
            $filterResult = mysqli_query($link, $sql);

            if (!$filterResult) {
                $mysqlErrorMessage = mysqli_error($link);
            }
        }
        $tasks = mysqli_fetch_all($filterResult, MYSQLI_ASSOC);
        $tasks = addHoursUntilEndTask($tasks);
    }

    // Смена статуса выполнения задачи (выполнена -> не выполнена, не выполнена -> выполнена)
    $taskId = "";
    $taskStatus = [];

    // Для сохранения состояние блоков фильтров, выбранных пользователем
    $filterWhiteList = ["today", "tomorrow", "past"];
    $tabs = "";

    if (isset($_GET["tab"]) && in_array($_GET["tab"], $filterWhiteList)) {
        $tabs .= "&tab=".$_GET["tab"];
    }

    if (isset($_GET["task_id"])) {
        $taskId = intval($_GET["task_id"]);
        if ($taskId) {
            // SQL запрос на получение статуса выбранной задачи
            $sql = "SELECT id, status FROM tasks WHERE id = $taskId and user_id = " . $userId;
            $taskStatusResult = mysqli_query($link, $sql);

            if (!$taskStatusResult) {
                $mysqlErrorMessage = mysqli_error($link);
            } else {
                $taskStatus = mysqli_fetch_assoc($taskStatusResult);
            }

            if (isset($taskStatus["status"])) {
                $status = 0;
                if ($taskStatus["status"] === 0) {
                    $status = 1;
                }
                // SQL запрос на cмену статуса выполнения задачи
                $sql = "UPDATE tasks SET status = $status WHERE id = $taskId and user_id = " . $userId;
                $changeStatusResult = mysqli_query($link, $sql);

                if (!$changeStatusResult) {
                    $mysqlErrorMessage = mysqli_error($link);

                } else {
                    // Для сохранения состояние блоков фильтров и чекбокса — показать выполненные
                    $filterWhiteList = ["today", "tomorrow", "past"];
                    $redirectTab = "";

                    if (isset($_GET["tab"]) && in_array($_GET["tab"], $filterWhiteList)) {
                        $redirectTab .= "?tab=".$_GET["tab"];
                    }

                    $redirectTabPart = "&";
                    if ($redirectTab === "") {
                        $redirectTabPart = "?";
                    }
                    $redirectTab .= "{$redirectTabPart}show_completed={$showCompleteTasks}";

                    $headerLocation = "Location: index.php";
                    if ($redirectTab !== "") {
                        $headerLocation .= $redirectTab;
                    }

                    header($headerLocation);
                    exit();
                }
            }
        }
    }
}

$pageContent = showMysqlError($templatePath, $mysqlErrorMessage);

if (!$mysqlErrorMessage) {
    $showCompleteTasksUrlPart = "&show_completed={$showCompleteTasks}";

    $pageContent = includeTemplate($templatePath . "main.php", [
        "tasks" => $tasks,
        "projects" => $projects,
        "tasksAll" => $tasksAll,
        "searchTasks" => $searchTasks,
        "searchTasksMessage" => $searchTasksMessage,
        "tabs" => $tabs,
        "url" => $url,
        "urlLink" => $urlLink,
        "showCompleteTasks" => $showCompleteTasks,
        "showCompleteTasksUrlPart" => $showCompleteTasksUrlPart
    ]);
}

$layoutContent = includeTemplate($templatePath . "layout.php", [
    "pageContent" => $pageContent,
    "user" => $user,
    "title" => "Дела в порядке"
]);

print($layoutContent);