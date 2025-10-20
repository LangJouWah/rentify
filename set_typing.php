<?php
include 'db_connect.php';
include 'auth.php';

$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user) {
    http_response_code(401);
    exit;
}

$car_id = $_GET['car_id'] ?? null;
$user_id = $_GET['user_id'] ?? null;
$is_typing = $_GET['is_typing'] ?? 0;

if (!$car_id || !$user_id) {
    http_response_code(400);
    exit;
}

// For simplicity, we'll use a sessions table or create a simple typing table
// Since your database doesn't have the expected typing structure, we'll create a simple solution

// Create temporary typing storage (you might want to create a proper table for this)
$stmt = $conn->prepare("INSERT INTO typingindicators (user_id, conversation_id, is_typing) 
                       VALUES (?, ?, ?) 
                       ON DUPLICATE KEY UPDATE is_typing = ?, last_updated = CURRENT_TIMESTAMP");

// We need to find the conversation_id
$conv_stmt = $conn->prepare("SELECT conversation_id FROM conversations 
                           WHERE car_id = ? AND (customer_id = ? OR owner_id = ?) 
                           LIMIT 1");
$conv_stmt->bind_param('iii', $car_id, $user_id, $user_id);
$conv_stmt->execute();
$conv_result = $conv_stmt->get_result();

if ($conv_result->num_rows > 0) {
    $conversation_id = $conv_result->fetch_assoc()['conversation_id'];
    $stmt->bind_param('iiii', $user_id, $conversation_id, $is_typing, $is_typing);
    $stmt->execute();
}

echo "OK";
?>
