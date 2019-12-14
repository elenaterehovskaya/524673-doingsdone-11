<?php
require_once "vendor/autoload.php";
require_once "init.php";

// Подключение к MySQL
$link = mysqlConnect($mysqlConfig);

// Проверяем наличие ошибок подключения к MySQL и выводим их на экран
if ($link["success"] === 0) {
    print($link["errorCaption"] . " | " . $link["errorMessage"]);
    exit();
}

print($link["message"]);
$link = $link["link"];

// Список уникальных значений ID пользователей, у которых есть невыполненные задачи, срок которых равен текущему дню
$usersIds = dbGetUsersIds($link);
if ($usersIds["success"] === 0) {
    print($usersIds["errorCaption"] . " | " . $usersIds["errorMessage"]);
    exit();
}

$usersIds = $usersIds["data"];

/*if ($usersIds["count"] === 0) {
    print("Нет задач для отправки рассылки");
}*/

foreach ($usersIds as $value) {
    $value = $value["user_id"];
    // Список невыполненным задачам для каждого найденного пользователя
    $tasksUser = dbGetTasksUser($link, $value);
    if ($tasksUser["success"] === 0) {
        print($tasksUser["errorCaption"] . " | " . $tasksUser["errorMessage"]);
        exit();
    }

    $tasksUser = $tasksUser["data"];

    // Список данных о каждом найденном пользователе для отправки e-mail рассылки
    $dataUser = dbGetDataUser($link, $value);
    if ($dataUser["success"] === 0) {
        print($dataUser["errorCaption"] . " | " . $dataUser["errorMessage"]);
        exit();
    }

    $dataUser = $dataUser["data"];

    $recipient = [];
    $recipient[$dataUser["email"]] = $dataUser["name"];

    $messageContent = includeTemplate($templatePath . "email-notify.php", [
        "dataUser" => $dataUser,
        "tasksUser" => $tasksUser
    ]);

    $sendMailResult = sendMail($yandexMailConfig, $recipient, $messageContent);
    $sendMailResultMessage = "Рассылка успешно отправлена! ";

    if (!$sendMailResult) {
        $sendMailResultMessage = "Не удалось отправить рассылку! ";
    }
    print $sendMailResultMessage;
}