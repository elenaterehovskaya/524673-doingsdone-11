<?php
/**
 * Подключает шаблон, передает туда данные и возвращает итоговый HTML контент
 * @param string $name Путь к файлу шаблона относительно папки с шаблонами
 * @param array $data Ассоциативный массив с данными для шаблона
 * @return string Итоговый HTML
 */
function includeTemplate(string $name, array $data = []) {
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
function getCountTasksProject(array $tasks, array $item) {
    $count = 0;

    foreach ($tasks as $task) {
        if (isset($task["project"]) && isset($item["name"]) && $task["project"] == $item["name"]) {
            $count ++;
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
function addHoursUntilEnd2Tasks(array $tasks) {
    foreach ($tasks as $task_key => $task) {
        if (isset($task["deadline"])) {
            $ts_end = strtotime($task["deadline"]);
            $ts_now = time();
            $ts_diff = $ts_end - $ts_now;
            $hours_until_end = floor($ts_diff / 3600);
            $tasks[$task_key]["hours_until_end"] = $hours_until_end;
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
function dbGetPrepareStmt($link, $sql, $data = []) {
    $stmt = mysqli_prepare($link, $sql);

    if ($stmt === false) {
        $errorMsg = 'Не удалось инициализировать подготовленное выражение: ' . mysqli_error($link);
        die($errorMsg);
    }
    if ($data) {
        $types = "";
        $stmt_data = [];

        foreach ($data as $value) {
            $type = "s";

            if (is_int($value)) {
                $type = "i";
            }
            else if (is_string($value)) {
                $type = "s";
            }
            else if (is_double($value)) {
                $type = "d";
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
            $errorMsg = 'Не удалось связать подготовленное выражение с параметрами: ' . mysqli_error($link);
            die($errorMsg);
        }
    }
    return $stmt;
}

/**
 * Получает данные из MySQL
 * @param $link mysqli Ресурс соединения
 * @param $sql string SQL запрос с плейсхолдерами вместо значений
 * @param array $data Данные для вставки на место плейсхолдеров
 * @return array Двумерный массив с данными
 */
function dbSelectData($link, $sql, $data = []) {
    $result = [];
    $stmt = dbGetPrepareStmt($link, $sql, $data);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $result = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    return $result;
}

/**
 * Добавляет новую запись в MySQL
 * @param $link mysqli Ресурс соединения
 * @param $sql string SQL запрос с плейсхолдерами вместо значений
 * @param array $data Данные для вставки на место плейсхолдеров
 * @return bool|int|string Возвращает автоматически генерируемый ID
 */
function dbInsertData($link, $sql, $data = []) {
    $stmt = dbGetPrepareStmt($link, $sql, $data);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        $result = mysqli_insert_id($link);
    }
    return $result;
}

/**
 * Получает значение поля после отправки формы
 * @param mixed $name Название параметра, значение которого получаем
 * @return mixed
 */
function getPostVal($name) {
    if (isset ($_POST[$name])) {
        $name = $_POST[$name];
    }
    else {
        $name = "";
    }
    return $name;
    // return $_POST[$name] ?? ""; — краткая форма записи тела фцнкции
}

/**
 * Получает значение поля после отправки формы
 * функция filter_input получает значение параметра запроса без обращения к $_POST и проверки ключей
 * INPUT_POST — константа для поиска в POST-параметрах
 * @param mixed $name Название параметра, значение которого получаем
 * @return mixed
 */
function getInputPostVal($name) {
    return filter_input(INPUT_POST, $name);
}

/**
 * Проверяет, присутствует ли в массиве значение
 * @param mixed $value Искомое значение
 * @param array $values_list Массив значений
 * @return string|null
 */
function validateValue($value, array $values_list) {
    if (!in_array($value, $values_list)) {
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
function validateLength($value, $min, $max) {
    if ($value) {
        $length = strlen($value);
        if ($length < $min or $length > $max) {
            return "Название задачи должно быть от $min до $max символов";
        }
    }
    return null;
}

/**
 * Проверяет переданную дату на соответствие формату 'ГГГГ-ММ-ДД'
 * Примеры использования:
 * is_date_valid('2019-01-01'); // true
 * is_date_valid('2016-02-29'); // true
 * is_date_valid('2019-04-31'); // false
 * is_date_valid('10.10.2010'); // false
 * is_date_valid('10/10/2010'); // false
 * @param string $date Дата в виде строки
 * @return bool true при совпадении с форматом 'ГГГГ-ММ-ДД', иначе false
 */
function isDateValid(string $date) : bool {
    $format_to_check = "Y-m-d";
    $dateTimeObj = date_create_from_format($format_to_check, $date);

    return $dateTimeObj !== false && array_sum(date_get_last_errors()) === 0;
}

/**
 * Выводит информацию в удобочитаемом виде (предназначение — отладка кода)
 * @param mixed $value Ассоциативный или двумерный массив с данными
 */
function debug($value) {
    print("<pre>");
    print_r($value);
    print("</pre>");
}
