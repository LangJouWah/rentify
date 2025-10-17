<?php
header('Content-Type: application/json');
include '../db_connect.php';

$conversation_id = $_GET['conversation_id'];
$user_id = $_GET['user_id'];  // Current user

// Mark as read
$stmt = $conn->prepare("UPDATE Messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ?");
$stmt->bind_param('ii', $conversation_id, $user_id);
$stmt->execute();
$stmt->close();

// Fetch messages
$stmt = $conn->prepare("SELECT * FROM Messages WHERE conversation_id = ? ORDER BY timestamp ASC");
$stmt->bind_param('i', $conversation_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();
echo json_encode($messages);
?>