<?php
require_once("init.php");

if (!isset($_SESSION["user"])) {
    header("location: /guest.php");
    exit();
}

$user = $_SESSION["user"];
$user_id = $_SESSION["user"]["id"];

// Проверяем подключение и выполняем запросы
if ($link === false) {
    $error_string = mysqli_connect_error();
} else {
    // SQL-запрос для получения списка проектов у текущего пользователя
    $sql = "SELECT id, name FROM projects WHERE user_id = " . $user_id;
    $result = mysqli_query($link, $sql);

    if ($result === false) {
        $error_string = mysqli_error($link);
    } else {
        $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // SQL-запрос для получения списка всех задач у текущего пользователя
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.id AS project_id, p.name AS project, t.title, t.deadline, t.status 
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id 
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = $user_id
SQL;
    $result = mysqli_query($link, $sql);

    if ($result === false) {
        $error_string = mysqli_error($link);
    } else {
        $all_tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $required = ["title", "project_id"];
        $errors = [];
        $projects_ids = [];

        foreach ($projects as $key => $value) {
            $projects_ids[] = $value["id"];
        }
        $rules = [
            "title" => function ($value) {
                return validateLength($value, 5, 255);
            },
            "project_id" => function ($value) use ($projects_ids) {
                return validateValue($value, $projects_ids);
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

        // Проверяем ввёл ли пользователь дату выполнения задачи и проверяем её на соответствие формату и текущей дате
        if (isset($_POST["deadline"])) {
            $data = $_POST["deadline"];

            if (isDateValid($data) === false) {
                $errors["deadline"] = "Введите дату в формате ГГГГ-ММ-ДД";
            } else {
                if ($data < date("Y-m-d")) {
                    $errors["deadline"] = "Дата выполнения задачи должна быть больше или равна текущей";
                } else {
                    // Добавляем дату выполнения задачи в наш массив $task
                    $task["deadline"] = $data;
                }
            }
        }

        // Проверяем загрузил ли пользователь файл, получаем имя файла и его размер
        if (isset($_FILES["file"]) && $_FILES["file"]["name"] !== "") {
            $white_list_files = [
                "image/jpeg",
                "image/png",
                "image/gif",
                "application/pdf",
                "application/msword",
                "text/plain"
            ];

            $file_type = mime_content_type($_FILES["file"]["tmp_name"]);
            $file_name = $_FILES["file"]["name"];
            $file_size = $_FILES["file"]["size"];
            $tmp_name = $_FILES["file"]["tmp_name"];

            if (!in_array($file_type, $white_list_files)) {
                $errors["file"] = "Загрузите файл в формате .jpg, .png, .gif, .pdf, .doc или .txt";
            } else {
                if ($file_size > 500000) {
                    $errors["file"] = "Максимальный размер файла: 500Кб";
                } else {
                    // Сохраняем его в папке «uploads» и формируем ссылку на скачивание
                    $file_path = __DIR__ . "/uploads/";
                    $file_url = "/uploads/" . $file_name;

                    // Перемещает загруженный файл по новому адресу
                    move_uploaded_file($tmp_name, $file_path . $file_name);

                    // Добавляем название файла в наш массив $task
                    $task["file"] = $file_url;
                }
            }
        }

        // Проверяем длину массива с ошибками. Если он не пустой, показываем ошибки пользователю вместе с формой
        if (count($errors)) {
            $page_content = includeTemplate($tpl_path . "form-task.php", [
                "projects" => $projects,
                "all_tasks" => $all_tasks,
                "errors" => $errors
            ]);
            $layout_content = includeTemplate($tpl_path . "layout.php", [
                "content" => $page_content,
                "user" => $user,
                "title" => "Дела в порядке | Добавление задачи"
            ]);
            print($layout_content);
            exit();
        } else {
            // SQL-запрос на добавление новой задачи (на месте значений — знаки вопроса — плейсхолдеры)
            $sql = "INSERT INTO tasks (user_id, title, project_id, deadline, file) VALUES ($user_id, ?, ?, ?, ?)";
            $result = dbInsertData($link, $sql, $task);

            if ($result === false) {
                $error_string = mysqli_error($link);
            } else {
                header("Location: index.php");
                exit();
            }
        }
    }
}

if ($error_string) {
    showMysqliError($page_content, $tpl_path, $error_string);
} else {
    $page_content = includeTemplate($tpl_path . "form-task.php", [
        "projects" => $projects,
        "all_tasks" => $all_tasks
    ]);
}

$layout_content = includeTemplate($tpl_path . "layout.php", [
    "content" => $page_content,
    "user" => $user,
    "title" => "Дела в порядке | Добавление задачи"
]);

print($layout_content);
