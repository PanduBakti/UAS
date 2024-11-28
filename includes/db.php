<?php
$host = "127.0.0.1";
$username = "root"; 
$password = ""; 
$database = "peminjaman_auditorium";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);