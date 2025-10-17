<?php
include '../db_connect.php';

$conversation_id = $_GET['conversation_id'];
$user_id = $_GET['user_id'];
$is_typing = $_GET['is_typing'];

$stmt = $conn->prepare("INSERT INTO TypingIndicators (user_id, conversation_id, is_typing) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_typing = ?, last_updated = CURRENT_TIMESTAMP");
$stmt->bind_param('iiii', $user_id, $conversation_id, $is_typing, $is_typing);
$stmt->execute();
$stmt->close();
?>