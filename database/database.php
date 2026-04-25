<?php
// database/database.php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'riceguard';     // ← New database name

$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die('MySQL Connection Error: ' . mysqli_error($conn));
}

mysqli_select_db($conn, $db);
mysqli_query($conn, "SET NAMES utf8mb4");
?>