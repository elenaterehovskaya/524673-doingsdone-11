<?php
require_once("config.php");

$title = "Дела в порядке | Регистрация аккаунта";

// Если сайт находится в неактивном состоянии, выходим на страницу с сообщением о техническом обслуживании
ifSiteDisabled($config, $templatePath, $title);

// Подключение к MySQL
$link = mysqlConnect($mysqlConfig);

// Проверяем наличие ошибок подключения к MySQL и выводим их в шаблоне
ifMysqlConnectError($link, $config, $title, $templatePath, $errorCaption, $errorDefaultMessage);

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
        "password" => function ($value) use ($config) {
            return validateLength($value,
                $config["registerLengthRules"]["password"]["min"],
                $config["registerLengthRules"]["password"]["max"]
            );
        },
        "name" => function ($value) use ($config) {
            return validateLength($value,
                $config["registerLengthRules"]["name"]["min"],
                $config["registerLengthRules"]["name"]["max"]
            );
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
            $email["errorMessage"] = $errorDefaultMessage;
            $pageContent = showTemplateWithError($templatePath, $errorCaption, $email["errorMessage"]);
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
            $user["errorMessage"] = $errorDefaultMessage;
            $pageContent = showTemplateWithError($templatePath, $errorCaption, $user["errorMessage"]);
            $layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
            dumpAndDie($layoutContent);
        }

        header("Location: index.php");
        exit();
    }
}

$pageContent = includeTemplate($templatePath . "form-register.php", [
    "validErrors" => $validErrors
]);

$layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
print($layoutContent);