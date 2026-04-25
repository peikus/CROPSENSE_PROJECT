<?php
session_start();
require_once 'database/database.php';

$from_id = $_SESSION['user_id'];
$to_id = (int)($_GET['to_user'] ?? 0);

if ($to_id > 0) {
    $stmt = $conn->prepare("SELECT m.id, m.from_user_id, m.message, m.created_at, u.full_name as sender_name 
                            FROM messages m 
                            LEFT JOIN users u ON m.from_user_id = u.id 
                            WHERE (m.from_user_id = ? AND m.to_user_id = ?) 
                               OR (m.from_user_id = ? AND m.to_user_id = ?) 
                            ORDER BY m.created_at ASC");
    $stmt->bind_param("iiii", $from_id, $to_id, $to_id, $from_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($messages);
} else {
    echo json_encode([]);
}
?>