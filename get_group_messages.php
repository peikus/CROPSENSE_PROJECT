<?php
// get_group_messages.php
session_start();
require_once 'database/database.php';

$stmt = $conn->prepare("SELECT m.id, m.from_user_id, m.message, m.created_at, u.full_name as sender_name 
                        FROM messages m 
                        LEFT JOIN users u ON m.from_user_id = u.id 
                        WHERE m.to_user_id = 0 
                        ORDER BY m.created_at ASC");
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($messages);
?>