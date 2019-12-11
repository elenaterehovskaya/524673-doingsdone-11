<?php
require_once("init.php");

if (!isset($_SESSION["user"])) {
    header("location: /guest.php");
    exit();
}

$user = $_SESSION["user"];
$userId = intval($_SESSION["user"]["id"]);

$mysqlErrorMessage = mysqli_connect_error();

// Проверяем наличие ошибок подключения к MySQL и выполняем запросы
if ($mysqlErrorMessage === null) {
    $mysqlErrorMessage = "";

    // SQL-запрос для получения списка проектов у текущего пользователя
    $sql = "SELECT id, name FROM projects WHERE user_id = " . $userId;
    $projectsResult = mysqli_query($link, $sql);

    if (!$projectsResult) {
        $mysqlErrorMessage = mysqli_error($link);
    } else {
        // Cписок проектов у текущего пользователя в виде двумерного массива
        $projects = mysqli_fetch_all($projectsResult, MYSQLI_ASSOC);
    }

    // SQL-запрос для получения списка всех задач у текущего пользователя
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.id AS project_id, p.name AS project, t.title, t.deadline, t.status 
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id 
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = $userId
SQL;
    $tasksAllResult = mysqli_query($link, $sql);

    if (!$tasksAllResult) {
        $mysqlErrorMessage = mysqli_error($link);
    } else {
        // Cписок всех задач у текущего пользователя в виде двумерного массива
        $tasksAll = mysqli_fetch_all($tasksAllResult, MYSQLI_ASSOC);
    }

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
            "title" => function ($value) {
                return validateLength($value, 5, 255);
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
                    $filePath = __DIR__ . "/uploads/";
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

            $layoutContent = includeTemplate($templatePath . "layout.php", [
                "pageContent" => $pageContent,
                "user" => $user,
                "title" => "Дела в порядке | Добавление задачи"
            ]);

            print($layoutContent);
            exit();
        } else {
            // SQL-запрос на добавление новой задачи
            $sql = "INSERT INTO tasks (user_id, title, project_id, deadline, file) VALUES ($userId, ?, ?, ?, ?)";
            $taskNewResult = dbInsertData($link, $sql, $task);

            if (!$taskNewResult) {
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
    $pageContent = includeTemplate($templatePath . "form-task.php", [
        "projects" => $projects,
        "tasksAll" => $tasksAll
    ]);
}

$layoutContent = includeTemplate($templatePath . "layout.php", [
    "pageContent" => $pageContent,
    "user" => $user,
    "title" => "Дела в порядке | Добавление задачи"
]);

print($layoutContent);