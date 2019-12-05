<?php
require_once("init.php");

// Проверяем подключение и выполняем запросы
if ($link === false) {
    $error_string = mysqli_connect_error();
} else {
    // ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user_guest = $_POST;

        $required = ["email", "password"];
        $errors = [];
        $error_message = "";

        if (!filter_var($user_guest["email"], FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "E-mail введён некорректно";
        }

        foreach ($required as $field) {
            if (empty($user_guest[$field])) {
                $errors[$field] = "Это поле должно быть заполнено";
            }
        }

        if (count($errors)) {
            $error_message = "Пожалуйста, исправьте ошибки в форме";
        } else {
            $error_message = "Вы ввели неверный email/пароль";
        }

        // Находим в базе данных в таблице users пользователя с переданным e-mail
        $email = mysqli_real_escape_string($link, $user_guest["email"]);
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($link, $sql);

        if ($result === false) {
            $error_string = mysqli_error($link);
        } else {
            $user = mysqli_fetch_array($result, MYSQLI_ASSOC);
        }

        if (empty($errors) and $user) {
            if (password_verify($user_guest["password"], $user["password"])) {
                $_SESSION["user"] = $user;
                header("Location: /index.php");
                exit();
            }
        }
    }
}

if ($error_string) {
    showMysqliError($page_content, $tpl_path, $error_string);
} else {
    showValidErrorAuth($page_content, $tpl_path, $error_message, $errors);
}

$layout_content = includeTemplate($tpl_path . "layout.php", [
    "content" => $page_content,
    "user" => [],
    "title" => "Дела в порядке | Авторизация на сайте",
    "config" => $config
]);

print($layout_content);
