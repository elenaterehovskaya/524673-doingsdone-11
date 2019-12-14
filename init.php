<?php
session_start();

define("CACHE_DIR", basename(__DIR__ . DIRECTORY_SEPARATOR . "cache"));
define("UPLOAD_PATH", basename(__DIR__ . DIRECTORY_SEPARATOR . "uploads"));

date_default_timezone_set("Europe/Moscow");

require_once("data.php");
require_once("functions.php");
require_once("config.php");