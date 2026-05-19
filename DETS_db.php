<?php

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "expense_tracker";

/* CREATE CONNECTION */

$conn = new mysqli(
    $servername,
    $username,
    $password,
    $dbname
);

/* CHECK CONNECTION */

if($conn->connect_error){

    die(
        "Database Connection Failed : "
        . $conn->connect_error
    );
}

/* UTF-8 SUPPORT */

$conn->set_charset("utf8mb4");

?>