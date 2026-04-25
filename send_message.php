<?php
// send_message.php
session_start();
require_once 'database/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_id = $_SESSION['user_id'];
    $message = trim($_POST['message'] ?? '');
    $to_id   = isset($_POST['to_user']) ? (int)$_POST['to_user'] : 0;
    $is_group = !empty($_POST['is_group']);
    $edit_id = !empty($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;

    if ($message) {
        if ($edit_id > 0) {
            // Edit
            $stmt = $conn->prepare("UPDATE messages SET message = ? WHERE id = ? AND from_user_id = ?");
            $stmt->bind_param("sii", $message, $edit_id, $from_id);
        } else if ($is_group) {
            // Group message - to_user_id = 0
            $stmt = $conn->prepare("INSERT INTO messages (from_user_id, to_user_id, message) VALUES (?, 0, ?)");
            $stmt->bind_param("is", $from_id, $message);
        } else {
            // Private message
            $stmt = $conn->prepare("INSERT INTO messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $from_id, $to_id, $message);
        }
        $stmt->execute();
    }
}
?>