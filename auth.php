<?php
require_once("init.php");

$mysqlErrorMessage = mysqli_connect_error();

// Проверяем наличие ошибок подключения к MySQL и выполняем запросы
if ($mysqlErrorMessage === null) {
    $mysqlErrorMessage = "";

    // ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // ВАЛИДАЦИЯ формы
        $userGuest = $_POST;

        $requiredFields = ["email", "password"];
        $validErrors = [];
        $validErrorMessage = "";

        if (!filter_var($userGuest["email"], FILTER_VALIDATE_EMAIL)) {
            $validErrors["email"] = "E-mail введён некорректно";
        }

        foreach ($requiredFields as $field) {
            if (empty($userGuest[$field])) {
                $validErrors[$field] = "Это поле должно быть заполнено";
            }
        }

        $validErrorMessage = "Пожалуйста, исправьте ошибки в форме";

        if (!count($validErrors)) {
            $validErrorMessage = "Вы ввели неверный email/пароль";
        }

        // Находим в базе данных в таблице users пользователя с переданным e-mail
        $email = mysqli_real_escape_string($link, $userGuest["email"]);
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $userGuestResult = mysqli_query($link, $sql);

        if (!$userGuestResult) {
            $mysqlErrorMessage = mysqli_error($link);
        } else {
            // Пользователь с переданным e-mail
            $user = mysqli_fetch_array($userGuestResult, MYSQLI_ASSOC);
        }
        // Конец ВАЛИДАЦИИ формы

        if (empty($validErrors) and $user) {
            // Проверяем, соответствует ли переданный пароль хешу
            if (password_verify($userGuest["password"], $user["password"])) {
                $_SESSION["user"] = $user;

                header("Location: index.php");
                exit();
            }
        }
    }
}

$pageContent = showMysqlError($templatePath, $mysqlErrorMessage);

if (!$mysqlErrorMessage) {
    $pageContent = showValidErrorAuth($templatePath, $validErrorMessage, $validErrors);
}

$layoutContent = includeTemplate($templatePath . "layout.php", [
    "pageContent" => $pageContent,
    "config" => $config,
    "user" => [],
    "title" => "Дела в порядке | Авторизация на сайте"
]);

print($layoutContent);