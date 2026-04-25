<?php
session_start();
require_once 'database/database.php';

$user_id = $_SESSION['user_id'];
$res = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id AND is_read = 0 ORDER BY created_at DESC");
$notifications = [];

while($row = $res->fetch_assoc()){
    $notifications[] = $row;
    $conn->query("UPDATE notifications SET is_read = 1 WHERE id = ".$row['id']);
}

header('Content-Type: application/json');
echo json_encode($notifications);