<?php
require_once("functions.php");
require_once("data.php");

$page_content = include_template("main.php", [
    "show_complete_tasks" => $show_complete_tasks,
    "projects" => $projects,
    "tasks" => $tasks]);

$layout_content = include_template("layout.php", [
    "content" => $page_content,
    "title" => "Дела в порядке"]);

print($layout_content);
