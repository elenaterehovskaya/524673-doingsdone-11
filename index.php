<?php
require_once("data.php");
require_once("functions.php");

$page_content = include_template($path_to_template . "main.php", [
    "show_complete_tasks" => $show_complete_tasks,
    "projects" => $projects,
    "tasks" => $tasks]);

$layout_content = include_template($path_to_template . "layout.php", [
    "content" => $page_content,
    "title" => "Дела в порядке"]);

print($layout_content);
