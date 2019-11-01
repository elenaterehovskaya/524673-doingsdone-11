<?php
function include_template($name, array $data = []) {
    $name = "templates/" . $name;
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

function get_tasks_count_by_project(array $tasks, string $item) {
    $count = 0;

    foreach ($tasks as $task) {
        if (isset ($task["project"]) && $task["project"] == $item) {
            $count ++;
        }
    }
    return $count;
}
