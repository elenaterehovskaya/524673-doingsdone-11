<?php
require_once("config.php");
require_once("data.php");
require_once("functions.php");

// Подключаем шаблон «Гостевой страницы»
if (isset($config["enable"])) {
    if ($config["enable"]) {
        $pageContent = includeTemplate(($config["templatePath"] . "guest.php"), []);
    } else {
        $pageContent = includeTemplate(($config["templatePath"] . "off.php"), []);
    }
}

$layoutContent = includeTemplate($templatePath . "layout.php", [
    "pageContent" => $pageContent,
    "config" => $config,
    "title" => "Дела в порядке | Гостевая страница"
]);

print($layoutContent);