<?php
include '../db_connect.php';

$car_id = $_POST['car_id'] ?? null;
$sender_id = $_POST['sender_id'] ?? null;
$receiver_id = $_POST['receiver_id'] ?? null;
$message = $_POST['message'] ?? null;
$type = $_POST['type'] ?? 'text';
$file_path = $type === 'file' ? $message : '';

if (!$car_id || !$sender_id || !$receiver_id || !$message) {
    http_response_code(400);
    echo 'Missing parameters';
    exit;
}

$stmt = $conn->prepare("INSERT INTO messages (car_id, sender_id, receiver_id, message, file_path, sent_at, is_read) VALUES (?, ?, ?, ?, ?, NOW(), FALSE)");
$stmt->bind_param("iiiss", $car_id, $sender_id, $receiver_id, $message, $file_path);
if ($stmt->execute()) {
    echo 'Message sent';
} else {
    http_response_code(500);
    echo 'Error: ' . $stmt->error;
}
$stmt->close();
?>