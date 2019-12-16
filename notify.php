<?php
require_once "vendor/autoload.php";
require_once "config.php";

$title = "Дела в порядке | Отправка e-mail рассылки";

// Подключение к MySQL
$link = mysqlConnect($mysqlConfig);

// Проверяем наличие ошибок подключения к MySQL и выводим их в шаблоне
ifMysqlConnectError($link, $config, $title, $templatePath, $errorCaption, $errorDefaultMessage);

$link = $link["link"];

// Список ID пользователей, у которых есть невыполненные задачи, срок выполнения которых равен текущему дню
$usersIds = dbGetUsersIds($link);
if ($usersIds["success"] === 0) {
    $pageContent = showTemplateWithError($templatePath, $errorCaption, $usersIds["errorMessage"]);
    $layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
    dumpAndDie($layoutContent);
}

if ($usersIds["count"] === 0) {
    $message = "Нет задач для отправки рассылки";
    $pageContent = showTemplateWithMessage($templatePath, $messageCaption, $message);
    $layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
    dumpAndDie($layoutContent);
}

$usersIds = $usersIds["data"];

foreach ($usersIds as $value) {
    // Список невыполненных задач для каждого найденного пользователя
    $tasksUser = dbGetTasksUser($link, $value["user_id"]);
    if ($tasksUser["success"] === 0) {
        $pageContent = showTemplateWithError($templatePath, $errorCaption, $tasksUser["errorMessage"]);
        $layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
        dumpAndDie($layoutContent);
    }

    $tasksUser = $tasksUser["data"];

    // Список данных о каждом найденном пользователе для отправки e-mail рассылки
    $dataUser = dbGetDataUser($link, $value["user_id"]);
    if ($dataUser["success"] === 0) {
        $pageContent = showTemplateWithError($templatePath, $dataUser["errorCaption"], $dataUser["errorMessage"]);
        $layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
        dumpAndDie($layoutContent);
    }

    $dataUser = $dataUser["data"];

    $recipient = [];
    $recipient[$dataUser["email"]] = $dataUser["name"];

    $messageContent = includeTemplate($templatePath . "email-notify.php", [
        "dataUser" => $dataUser,
        "tasksUser" => $tasksUser
    ]);

    $mailSendResult = mailSendMessage($yandexMailConfig, $recipient, $messageContent);
    if ($mailSendResult["success"] === 0) {
        $pageContent = showTemplateWithError($templatePath, $errorCaption, $mailSendResult["errorMessage"]);
        $layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
        dumpAndDie($layoutContent);
    }

    $message = "Рассылка успешно отправлена!";
}

$pageContent = showTemplateWithMessage($templatePath, $messageCaption, $message);
$layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
print($layoutContent);