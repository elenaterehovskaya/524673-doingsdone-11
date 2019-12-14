<?php
require_once("config.php");
require_once("data.php");
require_once("functions.php");

$pageContent = includeTemplate(($config["templatePath"] . "off.php"), []);
if (isset($config["enable"]) && $config["enable"]) {
    $pageContent = includeTemplate(($config["templatePath"] . "guest.php"), []);
}

$layoutContent = includeTemplate($templatePath . "layout.php", [
    "pageContent" => $pageContent,
    "config" => $config,
    "title" => "Дела в порядке | Гостевая страница"
]);

print($layoutContent);