<?php
require_once("config.php");

if (!isset($_SESSION["user"])) {
    header("location: /guest.php");
    exit();
}

$title = "Дела в порядке";
$user = $_SESSION["user"];
$userId = intval($_SESSION["user"]["id"]);

// Если сайт находится в неактивном состоянии, выходим на страницу с сообщением о техническом обслуживании
ifSiteDisabled($config, $templatePath, $title);

// Подключение к MySQL
$link = mysqlConnect($mysqlConfig);

// Проверяем наличие ошибок подключения к MySQL и выводим их в шаблоне
ifMysqlConnectError($link, $config, $title, $templatePath);

$link = $link["link"];
$projects = [];
$tasks = [];

// Список проектов у текущего пользователя
$projects = dbGetProjects($link, $userId);
if ($projects["success"] === 0) {
    $pageContent = showTemplateWithError($templatePath, $projects["errorCaption"], $projects["errorMessage"]);
    $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
    dumpAndDie($layoutContent);
}

$projects = $projects["data"];

// Список всех задач у текущего пользователя
$tasksAll = dbGetTasks($link, $userId);
if ($tasksAll["success"] === 0) {
    $pageContent = showTemplateWithError($templatePath, $tasksAll["errorCaption"], $tasksAll["errorMessage"]);
    $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
    dumpAndDie($layoutContent);
}

$tasksAll = $tasksAll["data"];

// Список всех задач у текущего пользователя для каждого проекта
$tasks = dbGetTasks($link, $userId);
if ($tasks["success"] === 0) {
    $pageContent = showTemplateWithError($templatePath, $tasks["errorCaption"], $tasks["errorMessage"]);
    $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
    dumpAndDie($layoutContent);
}

$tasks = $tasks["data"];
$tasks = addHoursUntilEndTask($tasks);

if (isset($_GET["project_id"])) {
    $projectId = intval($_GET["project_id"]);

    $tasks = dbGetTasksProject($link, $projectId, $userId);
    if ($tasks["success"] === 0) {
        $pageContent = showTemplateWithError($templatePath, $tasks["errorCaption"], $tasks["errorMessage"]);
        $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
        dumpAndDie($layoutContent);
    }

    // Проверяем для текущего ID проекта из адресной строки его существование в массиве проектов
    $currentProjectId = false;
    foreach ($projects as $key => $value) {
        if ($projectId === $value["id"]) {
            $currentProjectId = true;
            break;
        }
    }

    if ($currentProjectId === false) {
        http_response_code(404);
        $message = "Не найдено проекта с таким ID";
        ifErrorResultSearch($templatePath, $messageCaption, $message, $title, $user);
    }

    if ($tasks["count"] == 0) {
        http_response_code(404);
        $message = "Не найдено ни одной задачи для данного проекта";
        ifErrorResultSearch($templatePath, $messageCaption, $message, $title, $user);
    }

    $tasks = $tasks["data"];
    $tasks = addHoursUntilEndTask($tasks);
}

// Список задач, найденных по поисковому запросу с использование FULLTEXT поиска MySQL
$search = "";
$searchTasks = [];

if (isset($_GET["query"])) {
    $search = trim($_GET["query"]);

    if ($search) {
        $searchTasks = dbGetSearchTasks($link, $userId, [$search]);
        if ($searchTasks["success"] === 0) {
            $pageContent = showTemplateWithError($templatePath, $searchTasks["errorCaption"],
                $searchTasks["errorMessage"]);
            $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
            dumpAndDie($layoutContent);
        }

        if ($searchTasks["count"] == 0) {
            http_response_code(404);
            $searchTasksMessage = "Ничего не найдено по вашему запросу";
        }

        $searchTasks = $searchTasks["data"];
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
    $urlLink = "&show_completed=$reverseCompleteTasks";
}

$filter = $_GET;
$filterWhiteList = ["today", "tomorrow", "past"];

if (isset($filter["tab"]) && in_array($filter["tab"], $filterWhiteList)) {
    $tasks = dbGetFilterTasks($link, $userId, $filter);
    if ($tasks["success"] === 0) {
        $pageContent = showTemplateWithError($templatePath, $tasks["errorCaption"], $tasks["errorMessage"]);
        $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
        dumpAndDie($layoutContent);
    }

    $tasks = $tasks["data"];
    $tasks = addHoursUntilEndTask($tasks);
}

// Смена статуса выполнения задачи (выполнена -> не выполнена, не выполнена -> выполнена)
$taskId = "";
$taskStatus = [];
$tabs = "";

// Для сохранения состояния блоков фильтров, выбранных пользователем
if (isset($_GET["tab"]) && in_array($_GET["tab"], $filterWhiteList)) {
    $tabs .= "&tab=" . $_GET["tab"];
}

if (isset($_GET["task_id"])) {
    $taskId = intval($_GET["task_id"]);

    $statusTask = dbGetStatusTask($link, $taskId, $userId);
    if ($statusTask ["success"] === 0) {
        $pageContent = showTemplateWithError($templatePath, $statusTask["errorCaption"],
            $statusTask["errorMessage"]);
        $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
        dumpAndDie($layoutContent);
    }

    $statusTask = $statusTask["data"];

    if (isset($statusTask["status"])) {
        $status = 0;
        if ($statusTask["status"] === 0) {
            $status = 1;
        }

        $changeStatusTask = dbChangeStatusTask($link, $status, $taskId, $userId);
        if ($changeStatusTask["success"] === 0) {
            $pageContent = showTemplateWithError($templatePath, $changeStatusTask["errorCaption"],
                $changeStatusTask["errorMessage"]);
            $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
            dumpAndDie($layoutContent);
        }

        $redirectTab = "";

        if (isset($_GET["tab"]) && in_array($_GET["tab"], $filterWhiteList)) {
            $redirectTab .= "?tab=" . $_GET["tab"];
        }

        $redirectTabPart = "&";
        if ($redirectTab === "") {
            $redirectTabPart = "?";
        }

        $redirectTab .= "{$redirectTabPart}show_completed=$showCompleteTasks";

        $headerLocation = "Location: index.php";
        if ($redirectTab !== "") {
            $headerLocation .= $redirectTab;
        }

        header($headerLocation);
        exit();
    }
}

$showCompleteTasksUrl = "&show_completed=$showCompleteTasks";

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
    "showCompleteTasksUrl" => $showCompleteTasksUrl
]);

$layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
print($layoutContent);