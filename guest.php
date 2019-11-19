<?php
require_once('config.php');
require_once("data.php");
require_once("functions.php");

// Подключаем шаблон «Гостевой страницы»
if (isset($config["enable"])) {
    if ($config["enable"]) {
        $page_content = includeTemplate(($config["tpl_path"] . "guest.php"), []);
    }
    else {
        $page_content = includeTemplate(($config["tpl_path"] . "off.php"), []);
    }
}

// Подключаем «Лейаут» и передаём: HTML-код основного содержимого страницы и title для страницы
$layout_content = includeTemplate($path_to_template . "layout.php", [
    "content" => $page_content,
    "title" => "Дела в порядке | Гостевая страница",
]);

print($layout_content);
