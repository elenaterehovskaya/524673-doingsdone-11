<?php
require_once("init.php");

$title = "Дела в порядке | Авторизация на сайте";

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
    $userGuest = $_POST;

    $requiredFields = ["email", "password"];
    $validErrors = [];
    $validErrorMessage = "";

    if (isset($userGuest["email"]) && !filter_var($userGuest["email"], FILTER_VALIDATE_EMAIL)) {
        $validErrors["email"] = "E-mail введён некорректно";
    }

    foreach ($requiredFields as $field) {
        if (empty($userGuest[$field])) {
            $validErrors[$field] = "Это поле должно быть заполнено";
        }
    }

    if (count($validErrors)) {
        $validErrorMessage = "Пожалуйста, исправьте ошибки в форме";
    } else if (isset($userGuest["email"])) {
        $email = mysqli_real_escape_string($link, $userGuest["email"]);

        // Поиск в базе данных в таблице users пользователя с переданным e-mail
        $user = dbGetUser($link, $email);
        if ($user["success"] === 0) {
            $pageContent = showTemplateWithError($templatePath, $user["errorCaption"], $user["errorMessage"]);
            $layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
            dumpAndDie($layoutContent);
        }

        $user = $user["data"];

        if (!$user) {
            $validErrorMessage = "Вы ввели неверный email/пароль";
        } else {
            // Проверяем, соответствует ли переданный пароль хешу
            if (password_verify($userGuest["password"], $user["password"])) {
                $_SESSION["user"] = $user;

                header("Location: index.php");
                exit();
            }
        }
    }
}

$pageContent = showTemplateWithError($templatePath, $errorCaption, $errorMessage);

if (!$errorMessage) {
    $pageContent = includeTemplate($templatePath . "form-auth.php", [
        "validErrorMessage" => $validErrorMessage,
        "validErrors" => $validErrors
    ]);
}

$layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
print($layoutContent);