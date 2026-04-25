<?php
session_start();
require_once 'database/database.php';

$stmt = $conn->prepare("SELECT id, full_name, role FROM users WHERE status = 'approved' AND id != ? AND role != 'admin'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($users);
?>