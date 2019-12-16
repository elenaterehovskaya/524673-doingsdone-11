<?php
require_once("config.php");

if (!isset($_SESSION["user"])) {
    header("location: /guest.php");
    exit();
}

$title = "Дела в порядке | Добавление задачи";
$user = $_SESSION["user"];
$userId = intval($_SESSION["user"]["id"]);

// Если сайт находится в неактивном состоянии, выходим на страницу с сообщением о техническом обслуживании
ifSiteDisabled($config, $templatePath, $title);

// Подключение к MySQL
$link = mysqlConnect($mysqlConfig);

// Проверяем наличие ошибок подключения к MySQL и выводим их в шаблоне
ifMysqlConnectError($link, $config, $title, $templatePath, $errorCaption, $errorDefaultMessage);

$link = $link["link"];

// Список проектов у текущего пользователя
$projects = dbGetProjects($link, $userId);
if ($projects["success"] === 0) {
    $projects["errorMessage"] = $errorDefaultMessage;
    $pageContent = showTemplateWithError($templatePath, $errorCaption, $projects["errorMessage"]);
    $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
    dumpAndDie($layoutContent);
}

$projects = $projects["data"];

// Список всех задач у текущего пользователя
$tasksAll = dbGetTasks($link, $userId);
if ($tasksAll["success"] === 0) {
    $tasksAll["errorMessage"] = $errorDefaultMessage;
    $pageContent = showTemplateWithError($templatePath, $errorCaption, $tasksAll["errorMessage"]);
    $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
    dumpAndDie($layoutContent);
}

$tasksAll = $tasksAll["data"];

// ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ВАЛИДАЦИЯ формы
    $requiredFields = ["title", "project_id"];
    $validErrors = [];

    // Создаём массив с ID проектов, и заносим его для проверки в функцию validateValue
    $projectsIds = [];

    foreach ($projects as $key => $value) {
        $projectsIds[] = $value["id"];
    }

    $validRules = [
        "title" => function ($value) use ($config) {
            return validateLength($value,
                $config["addLengthRules"]["title"]["min"],
                $config["addLengthRules"]["title"]["max"]
            );
        },
        "project_id" => function ($value) use ($projectsIds) {
            return validateValue($value, $projectsIds);
        }
    ];

    $fields = [
        "title" => FILTER_DEFAULT,
        "project_id" => FILTER_DEFAULT,
        "deadline" => FILTER_DEFAULT,
        "file" => FILTER_DEFAULT
    ];

    // В массиве $task будут все значения полей из перечисленных в массиве $fields, если в форме не нашлось
    // необходимого поля, то оно добавится со значением NULL
    $task = filter_input_array(INPUT_POST, $fields, true);

    // Применяем функции валидации ко всем полям формы. Результат работы функций записывается в массив ошибок
    foreach ($task as $key => $value) {
        if (isset($validRules[$key])) {
            $rule = $validRules[$key];
            $validErrors[$key] = $rule($value);
        }

        if (in_array($key, $requiredFields) && empty($value)) {
            $validErrors[$key] = "Это поле должно быть заполнено";
        }
    }

    // Массив отфильтровываем, чтобы удалить пустые значения и оставить только сообщения об ошибках
    $validErrors = array_filter($validErrors);

    // Проверяем ввёл ли пользователь дату выполнения задачи и проверяем её на соответствие формату и текущей дате
    if (isset($_POST["deadline"])) {
        $data = $_POST["deadline"];

        if (isDateValid($data) === false) {
            $validErrors["deadline"] = "Введите дату в формате ГГГГ-ММ-ДД";
        } else {
            if ($data < date("Y-m-d")) {
                $validErrors["deadline"] = "Дата выполнения задачи должна быть больше или равна текущей";
            } else {
                // Добавляем дату выполнения задачи в наш массив $task
                $task["deadline"] = $data;
            }
        }
    }

    // Проверяем загрузил ли пользователь файл, получаем имя файла и его размер
    if (isset($_FILES["file"]) && $_FILES["file"]["name"] !== "") {
        $fileWhiteList = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "application/pdf",
            "application/msword",
            "text/plain"
        ];

        $fileType = mime_content_type($_FILES["file"]["tmp_name"]);
        $fileName = $_FILES["file"]["name"];
        $fileSize = $_FILES["file"]["size"];
        $tmpName = $_FILES["file"]["tmp_name"];

        if (!in_array($fileType, $fileWhiteList)) {
            $validErrors["file"] = "Загрузите файл в формате .jpg, .png, .gif, .pdf, .doc или .txt";
        } else {
            if ($fileSize > 300000) {
                $validErrors["file"] = "Максимальный размер файла: 300Кб";
            } else {
                // Сохраняем его в папке «uploads» и формируем ссылку на скачивание
                $filePath = $config["filePath"];
                $fileUrl = "/uploads/" . $fileName;

                // Перемещает загруженный файл по новому адресу
                move_uploaded_file($tmpName, $filePath . $fileName);

                // Добавляем название файла в наш массив $task
                $task["file"] = $fileUrl;
            }
        }
    }
    // Конец ВАЛИДАЦИИ формы

    // Подсчитываем количество элементов массива с ошибками. Если он не пустой, показываем ошибки вместе с формой
    if (count($validErrors)) {
        $pageContent = includeTemplate($templatePath . "form-task.php", [
            "projects" => $projects,
            "tasksAll" => $tasksAll,
            "validErrors" => $validErrors
        ]);

        $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
        dumpAndDie($layoutContent);
    }

    // Добавление новой задачи
    $task = dbInsertTask($link, $userId, $task);
    if ($task["success"] === 0) {
        $task["errorMessage"] = $errorDefaultMessage;
        $pageContent = showTemplateWithError($templatePath, $errorCaption, $task["errorMessage"]);
        $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
        dumpAndDie($layoutContent);
    }

    header("Location: index.php");
    exit();
}

$pageContent = includeTemplate($templatePath . "form-task.php", [
    "projects" => $projects,
    "tasksAll" => $tasksAll
]);

$layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
print($layoutContent);