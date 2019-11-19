<?php
require_once("data.php");
require_once("functions.php");
require_once("init.php");

// Если пользователь не вошёл в систему (т.е. нет о нем информации в сессии), подключаем тут же (!) страницу для гостя и выходим
if (!isset($_SESSION["user"])) {
    header("location: /guest.php");
    exit;
}

$user = $_SESSION["user"];
$user_id = $_SESSION["user"]["id"];

// Проверяем подключение и выполняем запросы
if ($link === false) {
    // Ошибка подключения к MySQL
    $error_string = mysqli_connect_error();
    $error_content = includeTemplate($path_to_template . "error.php", [
        "error" => $error_string
    ]);
    $layout_content = includeTemplate($path_to_template . "layout.php", [
        "content" => $error_content,
        "user" => $user,
        "title" => "Дела в порядке | Добавление задачи"
    ]);
    print($layout_content);
    exit;
}
else {
    /*
     * SQL-запрос для получения списка проектов у текущего пользователя
     */
    $sql =  "SELECT id, name FROM projects WHERE user_id = " . $user_id;
    $result = mysqli_query($link, $sql);
    if ($result === false) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);
        $error_content = includeTemplate($path_to_template . "error.php", [
            "error" => $error_string
        ]);
        $layout_content = includeTemplate($path_to_template . "layout.php", [
            "content" => $error_content,
            "user" => $user,
            "title" => "Дела в порядке | Добавление задачи"
        ]);
        print($layout_content);
        exit;
    }
    else {
        // Получаем список проектов у текущего пользователя в виде двумерного массива
        $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    /*
     * SQL-запрос для получения списка из всех задач у текущего пользователя без привязки к проекту
     */
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.id AS project_id, p.name AS project, t.title, t.deadline, t.status 
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id 
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = $user_id
SQL;
    $result = mysqli_query($link, $sql);
    if ($result === false) {
        // Ошибка при выполнении SQL запроса
        $error_string = mysqli_error($link);
        $error_content = includeTemplate($path_to_template . "error.php", [
            "error" => $error_string
        ]);
        $layout_content = includeTemplate($path_to_template . "layout.php", [
            "content" => $error_content,
            "user" => $user,
            "title" => "Дела в порядке | Добавление задачи"
        ]);
        print($layout_content);
        exit;
    }
    else {
        // Получаем список из всех задач у текущего пользователя без привязки к проекту в виде двумерного массива
        $all_tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    /*
     * ПОЛУЧАЕМ ИЗ ПОЛЕЙ ФОРМЫ необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
     */
    // Страница запрошена методом POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // ВАЛИДАЦИЯ ФОРМЫ
        // Список обязательных к заполнению полей
        $required = ["title", "project_id"];
        $errors = [];

        // Создаём массив с ID-шниками проектов, и заносим его для проверки в функцию validateValue
        $projects_ids = [];

        foreach($projects as $key => $value) {
            $projects_ids[] = $value["id"];
        }
        $rules = [
            "title" => function($value) {
                return validateLength($value, 5, 255);
            },
            "project_id" => function($value) use ($projects_ids) {
                return validateValue($value, $projects_ids);
            }
        ];

        // Одновременное получение и валидация полей: перечисляем поля, которые хотим получить из массива POST
        $fields = [
            "title" => FILTER_DEFAULT,
            "project_id" => FILTER_DEFAULT,
            "deadline" => FILTER_DEFAULT,
            "file" => FILTER_DEFAULT
        ];

        // В массиве $task будут все значения полей из перечисленных в массиве $fields, если в форме не нашлось необходимого поля,
        //то оно добавится со значением NULL
        $task = filter_input_array(INPUT_POST, $fields, true);

        // Применяем функции валидации ко всем полям формы. Результат работы функций записывается в массив ошибок
        foreach ($task as $key => $value) {
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

        // Проверяем ввёл ли пользователь дату выполнения задачи и проверяем её на соответствие формату и текущей дате
        if (isset($_POST["deadline"])) {
            $data = ($_POST["deadline"]);

            if (isDateValid($data) === false) {
                $errors["deadline"] = "Введите дату в формате ГГГГ-ММ-ДД";
            }
            else if ($data < date("Y-m-d")) {
                $errors["deadline"] = "Дата выполнения задачи должна быть больше или равна текущей";
            }
            else {
                // Добавляем дату выполнения задачи в наш массив $task
                $task["deadline"] = $data;
            }
        }

        // Проверяем загрузил ли пользователь файл, получаем имя файла и его размер
        if (isset($_FILES["file"]) && $_FILES["file"]["name"] !== "") {
            $white_list_files = ["image/jpeg", "image/png", "image/gif", "application/pdf", "application/msword", "text/plain"];

            $file_type = mime_content_type($_FILES["file"]["tmp_name"]);
            $file_name = $_FILES["file"]["name"];
            $file_size = $_FILES["file"]["size"];
            $tmp_name = $_FILES["file"]["tmp_name"];

            if (!in_array($file_type, $white_list_files)) {
                $errors["file"] = "Загрузите файл в формате .jpg, .png, .gif, .pdf, .doc или .txt";
            }
            else if ($file_size > 500000) {
                $errors["file"] = "Максимальный размер файла: 500Кб";
            }
            else {
                // Сохраняем его в папке «uploads» и формируем ссылку на скачивание
                $file_path = __DIR__ . "/uploads/";
                $file_url = "/uploads/" . $file_name;
                // Функция move_uploaded_file($current_path, $new_path) проверяет, что файл действительно загружен через форму
                //и перемещает загруженный файл по новому адресу
                move_uploaded_file($tmp_name, $file_path . $file_name);
                // Добавляем название файла в наш массив $task
                $task["file"] = $file_url;
            }
        }
        // Конец ВАЛИДАЦИИ ФОРМЫ

        // Проверяем длину массива с ошибками. Если он не пустой, значит были ошибки. Показываем ошибки пользователю вместе с формой
        // Для этого подключаем шаблон формы и передаем туда массив, где будут заполненные поля, а также список ошибок
        if (count($errors)) {
            $page_content = includeTemplate($path_to_template . "form-task.php", [
                "projects" => $projects,
                "all_tasks" => $all_tasks,
                "errors" => $errors
            ]);
            $layout_content = includeTemplate($path_to_template . "layout.php", [
                "content" => $page_content,
                "user" => $user,
                "title" => "Дела в порядке | Добавление задачи"
            ]);
            print($layout_content);
            exit;
        }
        else {
            // SQL-запрос на добавление новой задачи (на месте значений — знаки вопроса — плейсхолдеры)
            $sql = "INSERT INTO tasks (user_id, title, project_id, deadline, file) VALUES ($user_id, ?, ?, ?, ?)";
            // С помощью функции-помощника формируем подготовленное выражение, на основе SQL-запроса и значений для него
            $stmt = dbGetPrepareStmt($link, $sql, $task);
            // Выполняем полученное выражение
            $result = mysqli_stmt_execute($stmt);
            if ($result === false) {
                // Ошибка при выполнении SQL запроса
                $error_string = mysqli_error($link);
                $error_content = includeTemplate($path_to_template . "error.php", [
                    "error" => $error_string
                ]);
                $layout_content = includeTemplate($path_to_template . "layout.php", [
                    "content" => $error_content,
                    "user" => $user,
                    "title" => "Дела в порядке | Добавление задачи"
                ]);
                print($layout_content);
                exit;
            }
            else {
                // Если запрос выполнен успешно, переадресовываем пользователя на главную страницу
                header("Location: index.php");
                exit();
            }
        }
    }
}

// Подключаем шаблон страницы «Добавления задачи» и передаём: список проектов, полный список задач у текущего пользователя
$page_content = includeTemplate($path_to_template . "form-task.php", [
    "projects" => $projects,
    "all_tasks" => $all_tasks
]);

// Подключаем «Лейаут» и передаём: HTML-код основного содержимого страницы, имя пользователя и title для страницы
$layout_content = includeTemplate($path_to_template . "layout.php", [
    "content" => $page_content,
    "user" => $user,
    "title" => "Дела в порядке | Добавление задачи"
]);

print($layout_content);
