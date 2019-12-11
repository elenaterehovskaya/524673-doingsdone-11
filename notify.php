<?php
require_once "vendor/autoload.php";
require_once "init.php";

$mysqlErrorMessage = mysqli_connect_error();

// Проверяем наличие ошибок подключения к MySQL и выполняем запросы
if ($mysqlErrorMessage === null) {
    $tasksUser = [];
    $dataUser = [];

    // SQL запрос на получение всех ID пользователей у которых есть невыполненные задачи, срок которых равен текущему дню
    $sql = "SELECT user_id FROM tasks WHERE DATE(deadline) = DATE(NOW()) and status = 0 GROUP BY user_id";
    $usersIdsResult = mysqli_query($link, $sql);

    if (!$usersIdsResult) {
        $mysqlErrorMessage = mysqli_error($link);
        print("Ошибка при выполнении SQL запроса: " . $mysqlErrorMessage);
        exit();
    } else if (!mysqli_num_rows($usersIdsResult)) {
        print("Нет задач для отправки рассылки");
    } else {
        $usersIds = mysqli_fetch_all($usersIdsResult, MYSQLI_ASSOC);

        foreach ($usersIds as $value) {
            // SQL запрос на получение данных по невыполненным задачам для каждого найденного пользователя
            $sql = <<<SQL
            SELECT title, deadline
            FROM tasks
            WHERE DATE(deadline) = DATE(NOW()) and status = 0 and user_id = {$value["user_id"]}
SQL;
            $tasksUserResult = mysqli_query($link, $sql);

            if (!$tasksUserResult) {
                $mysqlErrorMessage = mysqli_error($link);
                print("Ошибка при выполнении SQL запроса: " . $mysqlErrorMessage);
                exit();
            } else {
                $tasksUser = mysqli_fetch_all($tasksUserResult, MYSQLI_ASSOC);
            }

            // SQL запрос на получение данных о каждом найденном пользователе для отправки e-mail рассылки
            $sql = "SELECT email, name FROM users WHERE id = " . $value["user_id"];
            $dataUserResult = mysqli_query($link, $sql);

            if (!$dataUserResult) {
                $mysqlErrorMessage = mysqli_error($link);
                print("Ошибка при выполнении SQL запроса: " . $mysqlErrorMessage);
                exit();
            } else {
                $dataUser = mysqli_fetch_assoc($dataUserResult);

                $recipient = [];
                $recipient[$dataUser["email"]] = $dataUser["name"];

                $messageContent = includeTemplate($templatePath . "email-notify.php", [
                    "dataUser" => $dataUser,
                    "tasksUser" => $tasksUser
                ]);

                $sendMailResult = sendMail($yandexMailConfig, $recipient, $messageContent);
                $sendMailResultMessage = "Рассылка успешно отправлена";

                if (!$sendMailResult) {
                    $sendMailResultMessage = "Не удалось отправить рассылку";
                }
                print $sendMailResultMessage;
            }
        }
    }
} else {
    print("Ошибка подключения к MySQL: " . $mysqlErrorMessage);
}