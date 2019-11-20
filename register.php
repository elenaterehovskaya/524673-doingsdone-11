<?php
require_once("config.php");
require_once("init.php");

// Проверяем подключение и выполняем запросы
if ($link === false) {
    // Ошибка подключения к MySQL
    $error_string = mysqli_connect_error();
}
else {
    // Страница запрошена методом POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // В массиве $user будут все значения полей из массива POST
        $user = $_POST;

        // ВАЛИДАЦИЯ ФОРМЫ
        // Список обязательных к заполнению полей
        $required = ["email", "password", "name"];
        $errors = [];

        $rules = [
            "email" => function($value) {
                return validateEmail($value);
            },
            "name" => function($value) {
                return validateLength($value, 5, 20);
            }
        ];

        // Применяем функции валидации ко всем полям формы. Результат работы функций записывается в массив ошибок
        foreach ($user as $key => $value) {
            if (isset($rules[$key])) {
                $rule = $rules[$key];
                $errors[$key] = $rule($value);
            }
            // В этом же цикле проверяем заполнены ли обязательные поля. Результат записывается в массив ошибок
            if (in_array($key, $required) && empty($value)) {
                $errors[$key] = "Это поле должно быть заполнено";
            }
        }

        // Данный массив в итоге отфильтровываем, чтобы удалить пустые значения и оставить только сообщения об ошибках
        $errors = array_filter($errors);

        if (empty($errors)) {
            // Проверяем существование пользователя с email из формы в таблице пользователей в базе данных
            $email = mysqli_real_escape_string($link, $user["email"]); // Экранирует специальные символы в строке
            $sql = "SELECT id FROM users WHERE email = '$email'";
            $result = mysqli_query($link, $sql);
            if ($result === false) {
                // Ошибка при выполнении SQL запроса
                $error_string = mysqli_error($link);
            }
            else {
                if (mysqli_num_rows($result) > 0) {
                    $errors["email"] = "Указанный email уже используется другим пользователем";
                }
                else {
                    // Добавим нового пользователя в БД. Чтобы не хранить пароль в открытом виде преобразуем его в хеш
                    $password = password_hash($user["password"], PASSWORD_DEFAULT);

                    $sql = "INSERT INTO users (email, name, password) VALUES (?, ?, ?)";
                    $stmt = dbGetPrepareStmt($link, $sql, [$user["email"], $user["name"], $password]);
                    $result = mysqli_stmt_execute($stmt);
                    if ($result === false) {
                        // Ошибка при выполнении SQL запроса
                        $error_string = mysqli_error($link);
                    }
                    else {
                        // Если запрос выполнен успешно, переадресовываем пользователя на главную страницу
                        header("Location: auth.php");
                        exit();
                    }
                }
            }
        }
    }
}

// Подключаем шаблон страницы «Регистрация аккаунта» и передаём: список ошибок при выполнении SQL запроса или ошибок валидации
if ($error_string) {
    $page_content = includeTemplate($path_to_template . "error.php", [
        "error" => $error_string
    ]);
}
else {
    $page_content = includeTemplate($path_to_template . "form-register.php", [
        "errors" => $errors
    ]);
}

// Подключаем «Лейаут» и передаём: HTML-код основного содержимого страницы и title для страницы
$layout_content = includeTemplate($path_to_template . "layout.php", [
    "content" => $page_content,
    "user" => [],
    "title" => "Дела в порядке | Регистрация аккаунта",
    "config" => $config
]);

print($layout_content);



/*
require_once("config.php");
require_once("data.php");
require_once("functions.php");
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
        "title" => "Дела в порядке | Регистрация аккаунта"
    ]);
    print($layout_content);
    exit;
}
else {
    $tpl_data = [];

    // Страница запрошена методом POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // В массиве $user будут все значения полей из массива POST
        $user = $_POST;

        // ВАЛИДАЦИЯ ФОРМЫ
        // Список обязательных к заполнению полей
        $required = ["email", "password", "name"];
        $errors = [];

        $rules = [
            "email" => function($value) {
                return validateEmail($value);
            },
            "name" => function($value) {
                return validateLength($value, 5, 20);
            }
        ];

        // Применяем функции валидации ко всем полям формы. Результат работы функций записывается в массив ошибок
        foreach ($user as $key => $value) {
            if (isset($rules[$key])) {
                $rule = $rules[$key];
                $errors[$key] = $rule($value);
            }
            // В этом же цикле проверяем заполнены ли обязательные поля. Результат записывается в массив ошибок
            if (in_array($key, $required) && empty($value)) {
                $errors[$key] = "Это поле должно быть заполнено";
            }
        }

        // Данный массив в итоге отфильтровываем, чтобы удалить пустые значения и оставить только сообщения об ошибках
        $errors = array_filter($errors);

        // Передаём в шаблон список ошибок и данные из формы
        $tpl_data ["errors"] = $errors;

        if (empty($errors)) {
            // Проверяем существование пользователя с email из формы в таблице пользователей в базе данных
            $email = mysqli_real_escape_string($link, $user["email"]); // Экранирует специальные символы в строке
            $sql = "SELECT id FROM users WHERE email = '$email'";
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
            }
            else {
                if (mysqli_num_rows($result) > 0) {
                    $errors["email"] = "Указанный email уже используется другим пользователем";
                }
                else {
                    // Добавим нового пользователя в БД. Чтобы не хранить пароль в открытом виде преобразуем его в хеш
                    $password = password_hash($user["password"], PASSWORD_DEFAULT);

                    $sql = "INSERT INTO users (email, name, password) VALUES (?, ?, ?)";
                    $stmt = dbGetPrepareStmt($link, $sql, [$user["email"], $user["name"], $password]);
                    $result = mysqli_stmt_execute($stmt);
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
                    }
                    else {
                        // Если запрос выполнен успешно, переадресовываем пользователя на главную страницу
                        header("Location: auth.php");
                        exit();
                    }
                }
            }
        }
    }
}

// Подключаем шаблон страницы «Регистрация аккаунта» и передаём: список ошибок
$page_content = includeTemplate($path_to_template . "form-register.php", $tpl_data);

// Подключаем «Лейаут» и передаём: HTML-код основного содержимого страницы и title для страницы
$layout_content = includeTemplate($path_to_template . "layout.php", [
    "content" => $page_content,
    "user" => [],
    "title" => "Дела в порядке | Регистрация аккаунта",
    "config" => $config // проброс переменной $config
]);

print($layout_content);
*/
