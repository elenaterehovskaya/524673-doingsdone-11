<?php
/**
 * Подключает шаблон, передает туда данные и возвращает итоговый HTML контент
 * @param string $name Путь к файлу шаблона относительно папки с шаблонами
 * @param array $data Ассоциативный массив с данными для шаблона
 * @return string Итоговый HTML
 */
function includeTemplate(string $name, array $data = [])
{
    $result = "";

    if (!is_readable($name)) {
        return $result;
    }

    ob_start();
    extract($data);
    require $name;

    $result = ob_get_clean();

    return $result;
}

/**
 * Выполняет подключение к MySQL
 * @param array $mysqlConfig Ассоциативный массив с параметрами для подключения к БД
 * @return array $result Ассоциативный массив с информацией по ресурсу соединения
 */
function mysqlConnect(array $mysqlConfig): array
{
    try {
        // Установка перехвата ошибок: MYSQLI_REPORT_ERROR — Заносит в протокол ошибки вызовов функций mysqli
        // MYSQLI_REPORT_STRICT — Вместо сообщений об ошибках выбрасывает исключение mysqli_sql_exception
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $link = mysqli_init();
        // Устанавливает преобразование целочисленных значений и  чисел с плавающей запятой из столбцов таблицы в PHP числа
        mysqli_options($link, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        mysqli_real_connect($link, $mysqlConfig["host"], $mysqlConfig["user"], $mysqlConfig["password"],
            $mysqlConfig["database"]);

        // Кодировка при работе с MySQL
        mysqli_set_charset($link, "utf8");
        $result = [
            "success" => 1,
            "link" => $link
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка подключения к MySQL",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * Показывает страницу с сообщением о техническом обслуживании, если сайт находится в неактивном состоянии
 * @param array $config Двумерный массив с параметрами сайта
 * @param string $templatePath Путь к папке с шаблонами
 * @param string $title Название страницы сайта
 */
function ifSiteDisabled(array $config, string $templatePath, string $title)
{
    if (isset($config["enable"]) && $config["enable"] === false) {
        $_SESSION = [];
        $pageContent = includeTemplate(($config["templatePath"] . "off.php"), []);

        $layoutContent = includeTemplate($templatePath . "layout.php", [
            "pageContent" => $pageContent,
            "config" => $config,
            "title" => $title
        ]);
        dumpAndDie($layoutContent);
    }
}

/**
 * Показывает страницу с сообщением об ошибке подключения к MySQL
 * @param array $link mysqli Ассоциативный массив с информацией по ресурсу соединения
 * @param array $config Двумерный массив с параметрами сайта
 * @param string $title Название страницы сайта
 * @param string $templatePath Путь к папке с шаблонами
 */
function ifMysqlConnectError(array $link, array $config, string $title, string $templatePath)
{
    if ($link["success"] === 0) {
        $pageContent = showTemplateWithError($templatePath, $link["errorCaption"], $link["errorMessage"]);
        $layoutContent = showTemplateLayoutGuest($templatePath, $pageContent, $config, $title);
        dumpAndDie($layoutContent);
    }
}

/**
 * Создает подготовленное выражение на основе готового SQL-запроса и переданных данных
 * @param $link mysqli Ресурс соединения
 * @param string $sql SQL-запрос с плейсхолдерами вместо значений
 * @param array $data Массив с данными для вставки на место плейсхолдеров
 * @return false|mysqli_stmt Подготовленное выражение
 */
function dbGetPrepareStmt($link, string $sql, array $data = [])
{
    $stmt = mysqli_prepare($link, $sql);

    if ($stmt === false) {
        $errorMsg = "Не удалось инициализировать подготовленное выражение: " . mysqli_error($link);
        die($errorMsg);
    }
    if ($data) {
        $types = "";
        $stmt_data = [];

        foreach ($data as $value) {
            $type = "s";

            if (is_int($value)) {
                $type = "i";
            } else {
                if (is_string($value)) {
                    $type = "s";
                } else {
                    if (is_double($value)) {
                        $type = "d";
                    }
                }
            }

            if ($type) {
                $types .= $type;
                $stmt_data[] = $value;
            }
        }
        $values = array_merge([$stmt, $types], $stmt_data);

        $func = "mysqli_stmt_bind_param";
        $func(...$values);

        if (mysqli_errno($link) > 0) {
            $errorMsg = "Не удалось связать подготовленное выражение с параметрами: " . mysqli_error($link);
            die($errorMsg);
        }
    }

    return $stmt;
}

/**
 * SQL-запрос при регистрации пользователя для поиска в базе данных уже используемого e-mail
 * @param $link mysqli Ресурс соединения
 * @param string $email E-mail переданный при аутентификации
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetEmail($link, string $email): array
{
    $sql = "SELECT id FROM users WHERE email = '$email'";
    try {
        $emailResult = mysqli_query($link, $sql);
        $result = [
            "success" => 1,
            "count" => mysqli_num_rows($emailResult)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на добавление нового пользователя в базу данных
 * @param $link mysqli Ресурс соединения
 * @param array $data Массив с данными для вставки на место плейсхолдеров
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbInsertUser($link, array $data = []): array
{
    $sql = "INSERT INTO users (email, name, password) VALUES (?, ?, ?)";
    try {
        // Формируем подготовленное выражение на основе SQL-запроса, ресурс соединения и массива со значениями
        $stmt = dbGetPrepareStmt($link, $sql, $data);
        // Выполняем полученное выражение
        mysqli_stmt_execute($stmt);
        $result = ["success" => 1];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос при аутентификации пользователя для поиска в базе данных пользователя с переданным e-mail
 * @param $link mysqli Ресурс соединения
 * @param string $email E-mail переданный при аутентификации
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetUser($link, string $email): array
{
    $sql = "SELECT * FROM users WHERE email = '$email'";
    try {
        $userResult = mysqli_query($link, $sql);
        $user = mysqli_fetch_array($userResult, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $user
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения списка проектов у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $userId Id текущего пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetProjects($link, int $userId): array
{
    $sql = "SELECT id, name FROM projects WHERE user_id = " . $userId;
    try {
        $projectsResult = mysqli_query($link, $sql);
        $projects = mysqli_fetch_all($projectsResult, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $projects,
            "count" => mysqli_num_rows($projectsResult)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на добавление нового проекта у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $userId Id текущего пользователя
 * @param array $data Массив с данными для вставки на место плейсхолдеров
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbInsertProject($link, int $userId, array $data = []): array
{
    $sql = "INSERT INTO projects (user_id, name) VALUES ($userId, ?)";
    try {
        // Формируем подготовленное выражение на основе SQL-запроса, ресурс соединения и массива со значениями
        $stmt = dbGetPrepareStmt($link, $sql, $data);
        // Выполняем полученное выражение
        mysqli_stmt_execute($stmt);
        $result = ["success" => 1];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения списка всех задач у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $userId Id текущего пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetTasks($link, int $userId): array
{
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = $userId ORDER BY t.id DESC
SQL;
    try {
        $tasksResult = mysqli_query($link, $sql);
        $tasks = mysqli_fetch_all($tasksResult, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $tasks,
            "count" => mysqli_num_rows($tasksResult)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на добавление новой задачи у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $userId Id текущего пользователя
 * @param array $data Массив с данными для вставки на место плейсхолдеров
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbInsertTask($link, int $userId, array $data = []): array
{
    $sql = "INSERT INTO tasks (user_id, title, project_id, deadline, file) VALUES ($userId, ?, ?, ?, ?)";
    try {
        // Формируем подготовленное выражение на основе SQL-запроса, ресурс соединения и массива со значениями
        $stmt = dbGetPrepareStmt($link, $sql, $data);
        // Выполняем полученное выражение
        mysqli_stmt_execute($stmt);
        $result = ["success" => 1];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения списка всех задач у текущего пользователя для каждого проекта
 * @param $link mysqli Ресурс соединения
 * @param int $projectId Id выбранного проекта текущего пользователя
 * @param int $userId Id текущего пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetTasksProject($link, int $projectId, int $userId): array
{
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = $userId and p.id = $projectId ORDER BY t.id DESC;
SQL;
    try {
        $tasksResult = mysqli_query($link, $sql);
        $tasks = mysqli_fetch_all($tasksResult, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $tasks,
            "count" => mysqli_num_rows($tasksResult)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения списка задач, найденных по поисковому запросу с использование FULLTEXT поиска MySQL
 * @param $link mysqli Ресурс соединения
 * @param int $userId Id текущего пользователя
 * @param array $data Массив с данными для вставки на место плейсхолдеров
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetSearchTasks($link, int $userId, array $data = []): array
{
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.id AS project_id, p.name AS project, t.title
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = $userId and MATCH(title) AGAINST(?) ORDER BY t.id DESC
SQL;
    try {
        $stmt = dbGetPrepareStmt($link, $sql, $data);
        mysqli_stmt_execute($stmt);
        $searchTasksResult = mysqli_stmt_get_result($stmt);
        $searchTasks = mysqli_fetch_all($searchTasksResult, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $searchTasks,
            "count" => mysqli_num_rows($searchTasksResult)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения данных для блока сортировки задач у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $userId Id текущего пользователя
 * @param array $filter Ассоциативный массив с фильтрами (задачи на сегодня, на завтра, просроченные)
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetFilterTasks($link, int $userId, array $filter = []): array
{
    switch ($filter["tab"]) {
        case "today":
            // SQL-запрос для получения списка задач «Повестка дня»
            $sql = <<<SQL
            SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.user_id = u.id
            WHERE DATE(t.deadline) = DATE(NOW()) and t.user_id = $userId ORDER BY t.id DESC
SQL;
            break;
        case "tomorrow":
            // SQL-запрос для получения списка задач на «Завтра»
            $sql = <<<SQL
            SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.user_id = u.id
            WHERE DATE (t.deadline) = DATE(DATE_ADD(NOW(), INTERVAL 24 HOUR)) and t.user_id = $userId ORDER BY t.id DESC
SQL;
            break;
        case "past":
            // SQL-запрос для получения списка «Просроченные»
            $sql = <<<SQL
            SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.user_id = u.id
            WHERE DATE(t.deadline) < DATE(NOW()) and t.user_id = $userId ORDER BY t.id DESC
SQL;
            break;
    }
    try {
        $filterResult = mysqli_query($link, $sql);
        $filterTasks = mysqli_fetch_all($filterResult, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $filterTasks
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения статуса выбранной задачи у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $taskId Id выбранной задачи текущего пользователя
 * @param int $userId Id текущего пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetStatusTask($link, int $taskId, int $userId): array
{
    $sql = "SELECT id, status FROM tasks WHERE id = $taskId and user_id = " . $userId;
    try {
        $statusTaskResult = mysqli_query($link, $sql);
        $statusTask = mysqli_fetch_assoc($statusTaskResult);
        $result = [
            "success" => 1,
            "data" => $statusTask
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на cмену статуса выполнения задачи у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $status Статус выбранной задачи
 * @param int $taskId Id выбранной задачи текущего пользователя
 * @param int $userId Id текущего пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbChangeStatusTask($link, int $status, int $taskId, int $userId): array
{
    $sql = "UPDATE tasks SET status = $status WHERE id = $taskId and user_id = " . $userId;
    try {
        mysqli_query($link, $sql);
        $result = ["success" => 1];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на получение всех ID пользователей, у которых есть невыполненные задачи, срок которых равен текущему дню
 * @param $link mysqli Ресурс соединения
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetUsersIds($link): array
{
    $sql = "SELECT user_id FROM tasks WHERE DATE(deadline) = DATE(NOW()) and status = 0 GROUP BY user_id";
    try {
        $usersIdsResult = mysqli_query($link, $sql);
        $usersIds = mysqli_fetch_all($usersIdsResult, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $usersIds,
            "count" => mysqli_num_rows($usersIdsResult)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на получение данных по невыполненным задачам для каждого найденного пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $value Значением ID найденного пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetTasksUser($link, int $value): array
{
    $sql = "SELECT title, deadline FROM tasks WHERE DATE(deadline) = DATE(NOW()) and status = 0 and user_id = $value";
    try {
        $tasksUserResult = mysqli_query($link, $sql);
        $tasksUser = mysqli_fetch_all($tasksUserResult, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $tasksUser
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на получение данных о каждом найденном пользователе для отправки e-mail рассылки
 * @param $link mysqli Ресурс соединения
 * @param int $value Значением ID найденного пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetDataUser($link, int $value): array
{
    $sql = "SELECT email, name FROM users WHERE id = $value";
    try {
        $dataUserResult = mysqli_query($link, $sql);
        $dataUser = mysqli_fetch_assoc($dataUserResult);
        $result = [
            "success" => 1,
            "data" => $dataUser
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Ошибка при выполнении SQL-запроса",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * Показывает шаблон с информацией об ошибке выполнения SQL-запроса
 * @param string $templatePath Путь к папке с шаблонами
 * @param string $errorCaption Заголовок ошибки
 * @param string $errorMessage Текст ошибки
 * @return string HTML контент
 */
function showTemplateWithError(string $templatePath, string $errorCaption, string $errorMessage)
{
    return includeTemplate($templatePath . "inform.php", [
        "messageCaption" => $errorCaption,
        "message" => $errorMessage
    ]);
}

/**
 * Показывает шаблон с информацией о результате выполненного действия (поиска в БД или отправки сообщения)
 * @param string $templatePath Путь к папке с шаблонами
 * @param string $messageCaption Заголовок сообщения
 * @param string $message Текст сообщения
 * @return string HTML контент
 */
function showTemplateWithMessage(string $templatePath, string $messageCaption, string $message)
{
    return includeTemplate($templatePath . "inform.php", [
        "messageCaption" => $messageCaption,
        "message" => $message
    ]);
}

/**
 * Показывает шаблон лейаута для зарегистрированного пользователя
 * @param string $templatePath Путь к папке с шаблонами
 * @param string $pageContent Содержание контентной части
 * @param string $title Название страницы
 * @param array $user Данные текущего пользователя
 * @return string HTML контент
 */
function showTemplateLayout(string $templatePath, string $pageContent, string $title, array $user = [])
{
    return $layoutContent = includeTemplate($templatePath . "layout.php", [
        "pageContent" => $pageContent,
        "title" => $title,
        "user" => $user
    ]);
}

/**
 * Показывает шаблон лейаута для НЕзарегистрированного пользователя
 * @param string $templatePath Путь к папке с шаблонами
 * @param string $pageContent Содержание контентной части
 * @param array $config Массив с параметрами сайта
 * @param string $title Название страницы сайта
 * @return string HTML контент
 */
function showTemplateLayoutGuest(string $templatePath, string $pageContent, array $config, string $title)
{
    return $layoutContent = includeTemplate($templatePath . "layout.php", [
        "pageContent" => $pageContent,
        "config" => $config,
        "title" => $title,
        "user" => []
    ]);
}

/**
 * Показывает страницу с информацией о результате поиска в БД заданных параметров
 * @param string $templatePath Путь к папке с шаблонами
 * @param string $messageCaption Заголовок сообщения
 * @param string $message Текст сообщения
 * @param string $title Название страницы сайта
 * @param array $user Данные текущего пользователя
 */
function ifErrorResultSearch(string $templatePath, string $messageCaption, string $message, string $title, array $user)
{
    $pageContent = showTemplateWithMessage($templatePath, $messageCaption, $message);
    $layoutContent = showTemplateLayout($templatePath, $pageContent, $title, $user);
    dumpAndDie($layoutContent);
}

/**
 * Отправляет подготовленное электронное сообщение (e-mail рассылку)
 * @param array $mailConfig Ассоциативный массив с данными для доступа к SMTP-серверу и параметрами сообщения
 * @param array $recipient Ассоциативный массив с данными получателя в виде [e-mail => имя]
 * @param string $messageContent Сообщение с HTML форматированием
 * @return array $result E-mail рассылка
 */
function mailSendMessage(array $mailConfig, array $recipient, string $messageContent): array
{
    try {
        // Конфигурация транспорта, отвечает за способ отправки. Содержит параметры доступа к SMTP-серверу
        $transport = (new Swift_SmtpTransport($mailConfig["domain"], $mailConfig["port"]))
            ->setUsername($mailConfig["userName"])
            ->setPassword($mailConfig["password"])
            ->setEncryption($mailConfig["encryption"]);

        // Объект библиотеки SwiftMailer, отвечает за отправку сообщений. Передаём туда созданный объект с SMTP-сервером
        $mailer = new Swift_Mailer($transport);

        // Формирование сообщения. Содержит параметры сообщения: текст, тему, отправителя и получателя
        $message = (new Swift_Message($mailConfig["subject"]))
            ->setFrom([$mailConfig["userName"] => $mailConfig["userCaption"]])
            ->setBcc($recipient)
            ->setBody($messageContent, "text/html");

        // Отправка сообщения
        $result = [
            "success" => 1,
            "message" => $mailer->send($message)
        ];
    }
    catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorCaption" => "Возникла ошибка при отправке рассылки",
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * Получает значение параметра запроса без обращения к $_POST
 * INPUT_POST — константа для поиска в POST-параметрах
 * @param mixed $name Название параметра, значение которого получаем
 * @return mixed
 */
function getPostVal($name)
{
    return filter_input(INPUT_POST, $name, FILTER_SANITIZE_SPECIAL_CHARS);
}

/**
 * Получает значение параметра запроса без обращения к $_GET
 * INPUT_GET — константа для поиска в GET-параметрах
 * @param mixed $name Название параметра, значение которого получаем
 * @return mixed
 */
function getGetVal($name)
{
    return filter_input(INPUT_GET, $name, FILTER_SANITIZE_SPECIAL_CHARS);
}

/**
 * Проверяет e-mail на корректность
 * @param string $value Значение поля ввода
 * @return string|null
 */
function validateEmail(string $value)
{
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return "E-mail введён некорректно";
    }

    return null;
}

/**
 * Проверяет, присутствует ли в массиве значение
 * @param mixed $value Искомое значение
 * @param array $valuesList Массив значений
 * @return string|null
 */
function validateValue($value, array $valuesList)
{
    if (!in_array($value, $valuesList)) {
        return "Выберите проект из раскрывающегося списка";
    }

    return null;
}

/**
 * Проверяет длину поля
 * @param string $value Значение поля ввода
 * @param int $min Минимальное значение символов
 * @param int $max Максимальное значение символов
 * @return string|null
 */
function validateLength(string $value, int $min, int $max)
{
    if ($value) {
        $length = mb_strlen($value);
        if ($length < $min or $length > $max) {
            return "Поле должно содержать от $min до $max символов";
        }
    }

    return null;
}

/**
 * Проверяет переданную дату на соответствие формату "ГГГГ-ММ-ДД"
 * Примеры использования:
 * is_date_valid("2019-01-01"); // true
 * is_date_valid("2016-02-29"); // true
 * is_date_valid("2019-04-31"); // false
 * is_date_valid("10.10.2010"); // false
 * is_date_valid("10/10/2010"); // false
 * @param string $date Дата в виде строки
 * @return bool true при совпадении с форматом "ГГГГ-ММ-ДД", иначе false
 */
function isDateValid(string $date): bool
{
    $formatToCheck = "Y-m-d";
    $dateTimeObj = date_create_from_format($formatToCheck, $date);

    return $dateTimeObj !== false && array_sum(date_get_last_errors()) === 0;
}

/**
 * Подсчитывает количество задач внутри каждого проекта
 * @param array $tasks Двумерный массив с данными для задач проекта
 * @param array $item Двумерный массив с названиями проектов
 * @return int $count Количество задач внутри проекта
 */
function getCountTasksProject(array $tasks, array $item)
{
    $count = 0;

    foreach ($tasks as $task) {
        if (isset($task["project"]) && isset($item["name"]) && $task["project"] == $item["name"]) {
            $count++;
        }
    }

    return $count;
}

/**
 * Рассчитывает оставшееся время (в часах) до даты окончания выполнения задачи
 * с помощью метки времени unixtime
 * @param array $tasks Двумерный массив с данными для задач проекта
 * @return array Итоговый двумерный массив
 */
function addHoursUntilEndTask(array $tasks): array
{
    foreach ($tasks as $task_key => $task) {
        if (isset($task["deadline"])) {
            $tsEnd = strtotime($task["deadline"]);
            $tsNow = time();
            $tsDiff = $tsEnd - $tsNow;
            $hoursUntilEnd = floor($tsDiff / 3600);
            $tasks[$task_key]["hours_until_end"] = $hoursUntilEnd;
        }
    }

    return $tasks;
}

/**
 * Выводит информацию в удобочитаемом виде (предназначение — отладка кода)
 * @param mixed $value Ассоциативный или двумерный массив с данными
 */
function debug($value)
{
    print("<pre>");
    print_r($value);
    print("</pre>");
}

/**
 * Выводит значение и завершает работу
 * @param mixed $value
 */
function dumpAndDie($value)
{
    die($value);
}