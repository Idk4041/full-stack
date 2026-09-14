<?php
$servername = "mysql";
$username = ini_get(option: 'mysqli.default_user'); 
$password = ini_get(option: "mysqli.default_pw"); 

try {
    $conn = new mysqli($servername, $username, $password, "klanten_project");
    if ($conn->connect_error) {
        error_log($conn->connect_error);
        exit("Connection DB failed");
    }
} catch (Exception $e) {
    error_log($e);
    exit("Connection DB failed");
}

return $conn;
