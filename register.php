<?php
require_once("init.php");

$title = "Дела в порядке | Регистрация аккаунта";

// Подключение к MySQL
$link = mysqlConnect($mysqlConfig);

// Проверяем наличие ошибок подключения к MySQL и выводим их в шаблоне
if ($link["success"] === 0) {
    $pageContent = showTemplateWithError($templatePath, $link["errorCaption"], $link["errorMessage"]);
    $layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
    dumpAndDie($layoutContent);
}

$link = $link["link"];

// ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        $email = mysqli_real_escape_string($link, $user["email"]);

        // Поиск в базе данных в таблице users уже используемого e-mail
        $email = dbGetEmail($link, $email);
        if ($email["success"] === 0) {
            $pageContent = showTemplateWithError($templatePath, $email["errorCaption"], $email["errorMessage"]);
            $layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
            dumpAndDie($layoutContent);
        }

        if ($email["count"] > 0) {
            $validErrors["email"] = "Указанный e-mail уже используется другим пользователем";
        }
    }
    // Массив отфильтровываем, чтобы удалить пустые значения и оставить только сообщения об ошибках
    $validErrors = array_filter($validErrors);

    if (empty($validErrors)) {
        // Добавим нового пользователя в БД. Чтобы не хранить пароль в открытом виде преобразуем его в хеш
        $password = password_hash($user["password"], PASSWORD_DEFAULT);

        $user = dbInsertUser($link, [$user["email"], $user["name"], $password]);
        if ($user["success"] === 0) {
            $pageContent = showTemplateWithError($templatePath, $user["errorCaption"], $user["errorMessage"]);
            $layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
            dumpAndDie($layoutContent);
        } else {
            header("Location: index.php");
            exit();
        }
    }
}

$pageContent = showTemplateWithError($templatePath, $errorCaption, $errorMessage);

if (!$errorMessage) {
    $pageContent = includeTemplate($templatePath . "form-register.php", [
        "validErrors" => $validErrors
    ]);
}

$layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
print($layoutContent);