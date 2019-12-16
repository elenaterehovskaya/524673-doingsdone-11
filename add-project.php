<?php
require_once("config.php");

if (!isset($_SESSION["user"])) {
    header("location: /guest.php");
    exit();
}

$title = "Дела в порядке | Добавление проекта";
$user = $_SESSION["user"];
$userId = intval($_SESSION["user"]["id"]);

// Если сайт находится в неактивном состоянии, выходим на страницу с сообщением о техническом обслуживании
ifSiteDisabled($config, $templatePath, $title);

// Подключение к MySQL
$link = mysqlConnect($mysqlConfig);

// Проверяем наличие ошибок подключения к MySQL и выводим их в шаблоне
ifMysqlConnectError($link, $config, $title, $templatePath);

$link = $link["link"];

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

// ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ВАЛИДАЦИЯ формы
    $project = $_POST;
    $validErrors = [];

    if (empty($project["name"])) {
        $validErrors["name"] = "Это поле должно быть заполнено";
    }

    $validateLength = validateLength($project["name"],
        $config["addLengthRules"]["project"]["min"],
        $config["addLengthRules"]["project"]["max"]
    );

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

        $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
        dumpAndDie($layoutContent);
    }

    // Добавление нового проекта
    $project = dbInsertProject($link, $userId, $project);
    if ($project["success"] === 0) {
        $pageContent = showTemplateWithError($templatePath, $project["errorCaption"], $project["errorMessage"]);
        $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
        dumpAndDie($layoutContent);
    }

    header("Location: index.php");
    exit();
}

$pageContent = includeTemplate($templatePath . "form-project.php", [
    "projects" => $projects,
    "tasksAll" => $tasksAll
]);

$layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
print($layoutContent);