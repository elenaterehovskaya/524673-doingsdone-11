<?php
require_once 'vendor/autoload.php';
require_once 'init.php';

// Конфигурация траспорта: сообщения электронной почты отправляются по протоколу SMTP.
// Поэтому нам понадобятся данные для доступа к SMTP-серверу. Указываем его адрес и логин с паролем
$transport = new Swift_SmtpTransport("phpdemo.ru", 25);
$transport->setUsername("keks@phpdemo.ru");
$transport->setPassword("htmlacademy");

// Отправка сообщения: создадим главный объект библиотеки SwiftMailer, ответственный за отправку сообщений.
// Передадим туда созданный объект с SMTP-сервером
$mailer = new Swift_Mailer($transport);

// Проверяем подключение и выполняем запросы
if ($link === false) {
    // Ошибка подключения к MySQL
    // $error_string = mysqli_connect_error();
}
else {
    // SQL запрос на получение всех невыполненных задач (статус равен нулю), у которых срок равен текущему дню
    $sql = "SELECT user_id FROM tasks WHERE DATE(deadline) = DATE(NOW()) and status = 0 GROUP BY user_id";
    $result = mysqli_query($link, $sql);

    if ($result === false) {
        // Ошибка при выполнении SQL запроса
        // $error_string = mysqli_error($link);
    }
    else {
        $users_ids = mysqli_fetch_all($result, MYSQLI_ASSOC);

        foreach ($users_ids as $key => $value) {
            $sql = "SELECT title, deadline FROM tasks WHERE DATE(deadline) = DATE(NOW()) and status = 0 and user_id = " . $value;
            $result = mysqli_query($link, $sql);
            $user_tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $sql = "SELECT email, name FROM users WHERE id = " . $value;
            $result = mysqli_query($link, $sql);
            $user = mysqli_fetch_all($result, MYSQLI_ASSOC);

            // Формирование сообщения: установим параметры сообщения: тема, отправитель и получатель в формате вида "email -> имя"
            $message = new Swift_Message();
            $message->setSubject("Уведомление от сервиса «Дела в порядке»");
            $message->setFrom(["keks@phpdemo.ru" => "Дела в порядке"]);
            $message->setTo([$user["email"]] = $user["name"]);

            // Передаем список задач в шаблон, используемый для сообщения
            $msg_content = includeTemplate($tpl_path . "email-notify.php", [
                "user_tasks" => $user_tasks,
                "user" => $user
            ]);
            $message->setBody($msg_content, "text/html");

            $result = $mailer->send($message);

            if ($result) {
                print("Рассылка успешно отправлена");
            }
            else {
                print("Не удалось отправить рассылку");
            }
        }
    }
}

