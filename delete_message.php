<?php
session_start();
require_once 'database/database.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND from_user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
}
?>