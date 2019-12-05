<?php
require_once("init.php");

// Проверяем подключение и выполняем запросы
if ($link === false) {
    $error_string = mysqli_connect_error();
} else {
    // ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user = $_POST;

        $required = ["email", "password", "name"];
        $errors = [];

        $rules = [
            "email" => function ($value) {
                return validateEmail($value);
            },
            "name" => function ($value) {
                return validateLength($value, 4, 20);
            }
        ];

        foreach ($user as $key => $value) {
            if (isset($rules[$key])) {
                $rule = $rules[$key];
                $errors[$key] = $rule($value);
            }

            if (in_array($key, $required) && empty($value)) {
                $errors[$key] = "Это поле должно быть заполнено";
            }
        }
        // Массив отфильтровываем, чтобы удалить пустые значения и оставить только сообщения об ошибках
        $errors = array_filter($errors);

        if (empty($errors)) {
            // Проверяем существование в базе данных таблице users пользователя с e-mail из формы
            $email = mysqli_real_escape_string($link, $user["email"]);
            $sql = "SELECT id FROM users WHERE email = '$email'";
            $result = mysqli_query($link, $sql);

            if ($result === false) {
                $error_string = mysqli_error($link);
            } else {
                if (mysqli_num_rows($result) > 0) {
                    $errors["email"] = "Указанный e-mail уже используется другим пользователем";
                } else {
                    // Добавим нового пользователя в БД. Чтобы не хранить пароль в открытом виде преобразуем его в хеш
                    $password = password_hash($user["password"], PASSWORD_DEFAULT);

                    $sql = "INSERT INTO users (email, name, password) VALUES (?, ?, ?)";
                    $result = dbInsertData($link, $sql, [$user["email"], $user["name"], $password]);

                    if ($result === false) {
                        $error_string = mysqli_error($link);
                    } else {
                        header("Location: auth.php");
                        exit();
                    }
                }
            }
        }
    }
}

if ($error_string) {
    showMysqliError($page_content, $tpl_path, $error_string);
} else {
    showValidErrorRegister($page_content, $tpl_path, $errors);
}

$layout_content = includeTemplate($tpl_path . "layout.php", [
    "content" => $page_content,
    "user" => [],
    "title" => "Дела в порядке | Регистрация аккаунта",
    "config" => $config
]);

print($layout_content);
