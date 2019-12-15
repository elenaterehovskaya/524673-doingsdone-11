<?php
require_once("config.php");

$title = "Дела в порядке | Гостевая страница";

// Если сайт находится в неактивном состоянии, выходим на страницу с сообщением о техническом обслуживании
ifSiteDisabled($config, $templatePath, $title);

$pageContent = includeTemplate(($config["templatePath"] . "guest.php"), []);

$layoutContent = includeTemplate($templatePath . "layout.php", [
    "pageContent" => $pageContent,
    "config" => $config,
    "title" => $title
]);

print($layoutContent);