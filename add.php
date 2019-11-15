<?php
require_once("data.php");
require_once("functions.php");

// Подключение к MySQL
// Включаем преобразование целочисленных значений и чисел с плавающей запятой из столбцов таблицы в PHP числа
$link = mysqli_init();
mysqli_options($link, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
mysqli_real_connect($link, $mysqlConfig["host"], $mysqlConfig["user"], $mysqlConfig["password"], $mysqlConfig["database"]);

// Устанавливаем кодировку при работе с MySQL
mysqli_set_charset($link, "utf8");

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
        "title" => "Дела в порядке"
    ]);
    print($layout_content);
    exit;
}
else {
    /*
     * SQL-запрос для получения данных о текущем пользователе
     */
    $user_id = 1;

    $sql = "SELECT id, name FROM users WHERE id = " . $user_id;
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
            "title" => "Дела в порядке"
        ]);
        print($layout_content);
        exit;
    } else {
        // Получаем данные о пользователе в виде ассоциативного массива
        $user = mysqli_fetch_assoc($result);
    }

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
            "title" => "Дела в порядке"
        ]);
        print($layout_content);
        exit;
    } else {
        // Получаем список проектов у текущего пользователя в виде двумерного массива
        $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    /*
     * SQL-запрос для получения списка из всех задач у текущего пользователя без привязки к проекту
     */
    $sql = <<<SQL
    SELECT tasks.id, tasks.user_id, projects.id AS project_id, projects.name AS project, tasks.title, tasks.deadline, tasks.status 
    FROM tasks
    LEFT JOIN projects ON tasks.project_id = projects.id 
    LEFT JOIN users ON tasks.user_id = users.id
    WHERE tasks.user_id = $user_id
SQL;
    $result = mysqli_query($link, $sql);

    if ($result === false || mysqli_num_rows($result) == 0) {
        // Ошибка при выполнении SQL запроса или SQL запрос не вернул ни одной записи
        http_response_code(404);
        $error_string = mysqli_error($link);
        $error_content = includeTemplate($path_to_template . "error.php", [
            "error" => $error_string
        ]);
        $layout_content = includeTemplate($path_to_template . "layout.php", [
            "content" => $error_content,
            "user" => $user,
            "title" => "Дела в порядке"
        ]);
        print($layout_content);
        exit;
    } else {
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
        $required = ["title", "project_id", "deadline"];
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
            "project_id" => FILTER_DEFAULT
        ];

        // В массиве $task будут все значения полей из перечисленных в массиве $fields, если в форме не нашлось необходимого поля, то оно добавится со значением NULL
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

        // Проверяем ввёл ли пользователь дату выполнения задачи и проверяем её на соответствие формату 'ГГГГ-ММ-ДД'
        if (isset($_POST["deadline"])) {
            $data = ($_POST["deadline"]);
            if (isDateValid($data) === false) {
                $errors["deadline"] = "Введите дату в формате ГГГГ-ММ-ДД";
            }
            else {
                // Добавляем дату выполнения задачи в наш массив $task
                $task["deadline"] = $data;
            }
        }

        // Проверяем загрузил ли пользователь файл, получаем имя файла и его размер
        if (isset($_FILES["user_file"]) && $_FILES['user_file']['name'] !== "") {

            $current_mime_type = mime_content_type($_FILES["user_file"]["tmp_name"]);
            $white_list_files = ["image/jpeg", "image/png", "text/plain", "application/pdf", "application/msword"];

            $file_name = $_FILES["user_file"]["name"];
            $file_size = $_FILES["user_file"]["size"];
            $tmp_name = $_FILES["user_file"]["tmp_name"];


            if (!in_array($current_mime_type, $white_list_files)) {
                $errors["user_file"] = "Загрузите файл в формате jpeg, png, txt, pdf или doc";
            }else if ($file_size > 200000) {
                $errors["user_file"] = "Максимальный размер файла: 200Кб";
            }
            else {
                // Сохраняем его в папке «uploads» и формируем ссылку на скачивание
                $file_path = __DIR__ . "/uploads/";
                $file_url = "/uploads/" . $file_name;

                // Функция move_uploaded_file($current_path, $new_path) проверяет, что файл действительно загружен через форму и перемещает загруженный файл по новому адресу
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
                "title" => "Дела в порядке"
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
                    "title" => "Дела в порядке"
                ]);
                print($layout_content);
                exit;
            } else {
                // Если запрос выполнен успешно, переадресовываем пользователя на главную страницу
                header("Location: index.php");
            }
        }
    }
}

// Подключаем шаблон страницы «Добавления задачи» и передаём туда необходимые данные: список проектов, полный список задач у текущего пользователя
$page_content = includeTemplate($path_to_template . "form-task.php", [
    "projects" => $projects,
    "all_tasks" => $all_tasks
]);

// Подключаем «Лейаут» и передаём туда необходимые данные: HTML-код основного содержимого страницы, имя пользователя и title для страницы
$layout_content = includeTemplate($path_to_template . "layout.php", [
    "content" => $page_content,
    "user" => $user,
    "title" => "Дела в порядке"
]);

print($layout_content);
