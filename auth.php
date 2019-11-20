<?php
require_once("config.php");
require_once("init.php");

// Проверяем подключение и выполняем запросы
if ($link === false) {
    // Ошибка подключения к MySQL
    $error_string = mysqli_connect_error();
    $error_content = includeTemplate($path_to_template . "error.php", [
        "error" => $error_string
    ]);
    $layout_content = includeTemplate($path_to_template . "layout.php", [
        "content" => $error_content,
        "title" => "Дела в порядке | Авторизация на сайте"
    ]);
    print($layout_content);
    exit;
}
else {
    // Страница запрошена методом POST
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
        }
        else {
            $error_message = "Вы ввели неверный email/пароль";
        }

        // Находим в таблице users в базе данных пользователя с переданным email
        $email = mysqli_real_escape_string($link, $user_guest["email"]); // Экранирует специальные символы в строке
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($link, $sql);
        if ($result === false) {
            // Ошибка при выполнении SQL запроса
            $error_string = mysqli_error($link);
            $error_content = includeTemplate($path_to_template . "error.php", [
                "error" => $error_string
            ]);
            $layout_content = includeTemplate($path_to_template . "layout.php", [
                "content" => $error_content,
                "title" => "Дела в порядке | Регистрация аккаунта"
            ]);
            print($layout_content);
            exit;
        } else {
            $user = $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
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

// Подключаем шаблон страницы «Авторизация на сайте» и передаём список ошибок
$page_content = includeTemplate($path_to_template . "form-auth.php", [
    "errors" => $errors,
    "error_message" => $error_message
]);

// Подключаем «Лейаут» и передаём: HTML-код основного содержимого страницы и title для страницы
$layout_content = includeTemplate($path_to_template . "layout.php", [
    "content" => $page_content,
    "user" => [],
    "title" => "Дела в порядке | Авторизация на сайте",
    "config" => $config // проброс переменной $config
]);

print($layout_content);
