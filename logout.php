<?php
session_start();

// Чтобы очистить сессию можно просто присвоить ей пустой массив
$_SESSION = [];
header("Location: /guest.php");
