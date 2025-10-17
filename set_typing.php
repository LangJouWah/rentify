<?php
include 'db_connect.php';

$car_id = $_GET['car_id'] ?? null;
$user_id = $_GET['user_id'] ?? null;
$is_typing = $_GET['is_typing'] ?? 0;

if (!$car_id || !$user_id) {
    http_response_code(400);
    echo 'Missing parameters';
    exit;
}

$stmt = $conn->prepare("INSERT INTO Typing (car_id, user_id, is_typing, last_updated) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE is_typing = ?, last_updated = NOW()");
$stmt->bind_param("iiii", $car_id, $user_id, $is_typing, $is_typing);
if ($stmt->execute()) {
    echo 'Typing status updated';
} else {
    http_response_code(500);
    echo 'Error: ' . $stmt->error;
}
$stmt->close();
?>