<?php
header('Content-Type: application/json');
include '../db_connect.php';

$conversation_id = $_GET['conversation_id'];
$user_id = $_GET['user_id'];

// Clean old typing (if >5s old)
$conn->query("UPDATE TypingIndicators SET is_typing = 0 WHERE last_updated < NOW() - INTERVAL 5 SECOND");

$stmt = $conn->prepare("SELECT is_typing FROM TypingIndicators WHERE user_id = ? AND conversation_id = ?");
$stmt->bind_param('ii', $user_id, $conversation_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
echo json_encode(['is_typing' => $row['is_typing'] ?? 0]);
?>