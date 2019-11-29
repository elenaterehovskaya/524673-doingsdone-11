<?php
require_once "vendor/autoload.php";
require_once "config.php";
require_once "init.php";

// Проверяем подключение и выполняем запросы
if ($link) {
    // SQL запрос на получение всех невыполненных задач (статус равен нулю), у которых срок равен текущему дню
    $sql = "SELECT user_id FROM tasks WHERE DATE(deadline) = DATE(NOW()) and status = 0 GROUP BY user_id";
    $result = mysqli_query($link, $sql);

    if ($result && mysqli_num_rows($result)) {
        $users_ids = mysqli_fetch_all($result, MYSQLI_ASSOC);
        debug($users_ids);

        foreach ($users_ids as $value) {
            $sql = "SELECT title, deadline FROM tasks WHERE DATE(deadline) = DATE(NOW()) and status = 0 and user_id = " . $value["user_id"];
            $result = mysqli_query($link, $sql);
            $user_tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
            debug($user_tasks);

            $sql = "SELECT email, name FROM users WHERE id = " . $value["user_id"];
            $result = mysqli_query($link, $sql);
            $user = mysqli_fetch_assoc($result);
            debug($user);

            $recipient = [];

            $recipient[$user["email"]] = $user["name"];

            $msg_content = includeTemplate($tpl_path . "email-notify.php", [
                "user_tasks" => $user_tasks,
                "user" => $user
            ]);

            sendMail($swiftMailerConfig, $recipient, $msg_content);

            if ($result) {
                print("Рассылка успешно отправлена");
            }
            else {
                print("Не удалось отправить рассылку");
            }
        }
    }
}

/*
// Create the Transport
$transport = (new Swift_SmtpTransport($yandexMailConfig["domain"], $yandexMailConfig["port"], "ssl"))
    ->setUsername($yandexMailConfig["user_name"])
    ->setPassword($yandexMailConfig["password"]);

// Create the Mailer using your created Transport
$mailer = new Swift_Mailer($transport);

// Create a message
$message = (new Swift_Message("Уведомление от сервиса «Дела в порядке»"))
    ->setFrom(["testemaily@yandex.ru" => "Дела в порядке"])
    ->setTo(["len-sh@yandex.ru" => "Elena"])
    ->setBody("Привет, это тестовое письмо! Уже весело!");

// Send the message
$result = $mailer->send($message);

if ($result) {
    print("Рассылка успешно отправлена");
}
else {
    print("Не удалось отправить рассылку");
}
*/
