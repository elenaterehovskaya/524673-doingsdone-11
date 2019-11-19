<?php
session_start();

define("CACHE_DIR", basename(__DIR__ . DIRECTORY_SEPARATOR . "cache"));
define("UPLOAD_PATH", basename(__DIR__ . DIRECTORY_SEPARATOR . "uploads"));

require_once("data.php");
require_once("functions.php");

$mysqlConfig = [
    "host" => "localhost",
    "user" => "root",
    "password" => "",
    "database" => "doings_done"
];

// Подключение к MySQL
$link = mysqli_init();
mysqli_options($link, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
mysqli_real_connect($link, $mysqlConfig["host"], $mysqlConfig["user"], $mysqlConfig["password"], $mysqlConfig["database"]);

// Устанавливаем кодировку при работе с MySQL
mysqli_set_charset($link, "utf8");
