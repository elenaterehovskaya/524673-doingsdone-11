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
function addHoursUntilEndTask(array $tasks)
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
 * Создает подготовленное выражение на основе готового SQL запроса и переданных данных
 * @param $link mysqli Ресурс соединения
 * @param $sql string SQL запрос с плейсхолдерами вместо значений
 * @param array $data Данные для вставки на место плейсхолдеров
 * @return mysqli_stmt Подготовленное выражение
 */
function dbGetPrepareStmt($link, $sql, array $data = [])
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
 * Получает данные из MySQL с помощью подготовленного выражения
 * @param $link mysqli Ресурс соединения
 * @param $sql string SQL запрос с плейсхолдерами вместо значений
 * @param array $data Данные для вставки на место плейсхолдеров
 * @return mixed $result Объект результата
 */
function dbSelectData($link, $sql, array $data = [])
{
    // Формируем подготовленное выражение на основе SQL-запроса, ресурс соединения и массива со значениями
    $stmt = dbGetPrepareStmt($link, $sql, $data);
    // Выполняем полученное выражение
    mysqli_stmt_execute($stmt);
    // Получаем объект результата
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}

/**
 * Добавляет новую запись в MySQL
 * @param $link mysqli Ресурс соединения
 * @param $sql string SQL запрос с плейсхолдерами вместо значений
 * @param array $data Данные для вставки на место плейсхолдеров
 * @return bool $result
 */
function dbInsertData($link, $sql, array $data = [])
{
    // Формируем подготовленное выражение на основе SQL-запроса, ресурс соединения и массива со значениями
    $stmt = dbGetPrepareStmt($link, $sql, $data);
    // Выполняем полученное выражение
    $result = mysqli_stmt_execute($stmt);

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
 * Показывает шаблон ошибки с текстом последней ошибки
 * @param string $templatePath Путь к папке с шаблонами
 * @param string $mysqlErrorMessage Ошибка подключения к MySQL или ошибка выполнения SQL запроса
 * @return string HTML контент
 */
function showMysqlError(string $templatePath, string $mysqlErrorMessage)
{
    return includeTemplate($templatePath . "error.php", [
        "mysqlErrorMessage" => $mysqlErrorMessage
    ]);
}

/**
 * Показывает шаблон формы регистрации + ошибки валидации
 * @param string $templatePath Путь к папке с шаблонами
 * @param array $validErrors Двумерный массив с ошибками валидации
 * @return string HTML контент
 */
function showValidErrorRegister(string $templatePath, array $validErrors = [])
{
    return includeTemplate($templatePath . "form-register.php", [
        "validErrors" => $validErrors
    ]);
}

/**
 * Показывает шаблон формы аутентификации + ошибки валидации
 * @param string $templatePath Путь к папке с шаблонами
 * @param array $validErrors Двумерный массив с ошибками валидации
 * @param string $validErrorMessage Итоговое сообщение об ошибки валидации
 * @return string HTML контент
 */
function showValidErrorAuth(string $templatePath, string $validErrorMessage, array $validErrors = [])
{
    return includeTemplate($templatePath . "form-auth.php", [
        "validErrorMessage" => $validErrorMessage,
        "validErrors" => $validErrors
    ]);
}

/**
 * Отправляет подготовленное электронное сообщение (e-mail рассылку)
 * @param array $mailConfig Ассоциативный массив с данными для доступа к SMTP-серверу и параметрами сообщения
 * @param array $recipient Ассоциативный массив с данными получателя в виде [e-mail => имя]
 * @param string $messageContent Сообщение с HTML форматированием
 * @return string $result E-mail рассылка
 */
function sendMail(array $mailConfig, array $recipient, string $messageContent)
{
    // Конфигурация транспорта: отвечает за способ отправки, содержит параметры доступа к SMTP-серверу
    $transport = (new Swift_SmtpTransport($mailConfig["domain"], $mailConfig["port"]))
        ->setUsername($mailConfig["userName"])
        ->setPassword($mailConfig["password"])
        ->setEncryption($mailConfig["encryption"]);

    // Объект библиотеки SwiftMailer, отвечает за отправку сообщений. Передаём туда созданный объект с SMTP-сервером
    $mailer = new Swift_Mailer($transport);

    // Формирование сообщения
    $message = (new Swift_Message($mailConfig["subject"]))
        ->setFrom([$mailConfig["userName"] => $mailConfig["userCaption"]])
        ->setBcc([$recipient])
        ->setBody($messageContent, "text/html");

    // Отправка сообщения
    $result = $mailer->send($message);

    return $result;
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