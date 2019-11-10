<?php
/**
 * Подключает шаблон, передает туда данные и возвращает итоговый HTML контент
 * @param string $name Путь к файлу шаблона относительно папки с шаблонами
 * @param array $data Ассоциативный массив с данными для шаблона
 * @return string Итоговый HTML
 */
function include_template(string $name, array $data = []) {
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
 * @param array $item Название проекта
 * @return int $count Количество задач внутри проекта
 */
function get_tasks_count_by_project(array $tasks, array $item) {
    $count = 0;

    foreach ($tasks as $task) {
        if (isset($task["project"]) && isset($item["name"]) && $task["project"] == $item["name"]) {
            $count ++;
        }
    }
    return $count;
}

/**
 * Расчёт оставшегося времени до определенной даты (даты окончания выполнения задачи)
 * с помощью метки времени unixtime
 */
foreach ($tasks as $task_key => $task) {
    if (isset($task["deadline"])) {
        $ts_end = strtotime($task["deadline"]);
        $ts_now = time();
        $ts_diff = $ts_end - $ts_now;
        $hours_until_end = floor($ts_diff / 3600);
        $tasks[$task_key]["hours_until_end"] = $hours_until_end;
    }
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
