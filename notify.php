<?php
require_once "vendor/autoload.php";
require_once "init.php";

// Проверяем подключение и выполняем запросы
if ($link) {
    // SQL запрос на получение всех id пользователей у которых есть невыполненные задачи (статус равен нулю),
    // срок выполнения которых равен текущему дню
    $sql = "SELECT user_id FROM tasks WHERE DATE(deadline) = DATE(NOW()) and status = 0 GROUP BY user_id";
    $result = mysqli_query($link, $sql);

    if ($result && mysqli_num_rows($result)) {
        $users_ids = mysqli_fetch_all($result, MYSQLI_ASSOC);

        foreach ($users_ids as $value) {
            // SQL запрос на получение данных по невыполненным задачам для каждого найденного пользователя
            $sql = "SELECT title, deadline FROM tasks WHERE DATE(deadline) = DATE(NOW()) and status = 0 and user_id = " . $value["user_id"];
            $result = mysqli_query($link, $sql);
            $user_tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);

            // SQL запрос на получение данных о каждом найденном пользователе для отправки e-mail рассылки
            $sql = "SELECT email, name FROM users WHERE id = " . $value["user_id"];
            $result = mysqli_query($link, $sql);
            $user_data = mysqli_fetch_assoc($result);

            $recipient = [];

            $recipient[$user_data["email"]] = $user_data["name"];

            $msg_content = includeTemplate($tpl_path . "email-notify.php", [
                "user_tasks" => $user_tasks,
                "user_data" => $user_data
            ]);

            sendMail($yandexMailConfig, $recipient, $msg_content);

            if ($result) {
                print("Рассылка успешно отправлена");
            } else {
                print("Не удалось отправить рассылку");
            }
        }
    }
}
