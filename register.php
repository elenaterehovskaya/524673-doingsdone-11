<?php
require_once("init.php");

$mysqlErrorMessage = mysqli_connect_error();

// Проверяем наличие ошибок подключения к MySQL и выполняем запросы
if ($mysqlErrorMessage === null) {
    $mysqlErrorMessage = "";

    // ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // ВАЛИДАЦИЯ формы
        $user = $_POST;

        $requiredFields = ["email", "password", "name"];
        $validErrors = [];

        $validRules = [
            "email" => function ($value) {
                return validateEmail($value);
            },
            "password" => function($value) {
            return validateLength($value, 8, 32);
            },
            "name" => function ($value) {
                return validateLength($value, 4, 20);
            }
        ];

        foreach ($user as $key => $value) {
            if (isset($validRules[$key])) {
                $rule = $validRules[$key];
                $validErrors[$key] = $rule($value);
            }

            if (in_array($key, $requiredFields) && empty($value)) {
                $validErrors[$key] = "Это поле должно быть заполнено";
            }
        }

        if (isset($user["email"]) && !$validErrors["email"]) {
            // Проверяем существование в базе данных таблице users пользователя с e-mail из формы
            $email = mysqli_real_escape_string($link, $user["email"]);
            $sql = "SELECT id FROM users WHERE email = '$email'";
            $userResult = mysqli_query($link, $sql);

            if (!$userResult) {
                $mysqlErrorMessage = mysqli_error($link);
            } else {
                if (mysqli_num_rows($userResult) > 0) {
                    $validErrors["email"] = "Указанный e-mail уже используется другим пользователем";
                }
            }
        }
        // Массив отфильтровываем, чтобы удалить пустые значения и оставить только сообщения об ошибках
        $validErrors = array_filter($validErrors);

        // Конец ВАЛИДАЦИИ формы

        if (empty($validErrors)) {
            // Добавим нового пользователя в БД. Чтобы не хранить пароль в открытом виде преобразуем его в хеш
            $password = password_hash($user["password"], PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (email, name, password) VALUES (?, ?, ?)";
            $userNewResult = dbInsertData($link, $sql, [$user["email"], $user["name"], $password]);

            if (!$userNewResult) {
                $mysqlErrorMessage = mysqli_error($link);
            } else {
                header("Location: index.php");
                exit();
            }
        }
    }
}

$pageContent = showMysqlError($templatePath, $mysqlErrorMessage);

if (!$mysqlErrorMessage) {
    $pageContent = showValidErrorRegister($templatePath, $validErrors);
}

$layoutContent = includeTemplate($templatePath . "layout.php", [
    "pageContent" => $pageContent,
    "config" => $config,
    "user" => [],
    "title" => "Дела в порядке | Регистрация аккаунта"
]);

print($layoutContent);