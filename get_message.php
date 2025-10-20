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
$current_user_id = $_GET['current_user_id'] ?? null;
$other_user_id = $_GET['other_user_id'] ?? null;
$is_owner = $_GET['is_owner'] ?? false;

if (!$car_id || !$current_user_id || !$other_user_id) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

// Get conversation ID
$conv_stmt = $conn->prepare("SELECT conversation_id FROM conversations 
                           WHERE car_id = ? AND customer_id = ? AND owner_id = ?");
if ($is_owner === 'true') {
    $conv_stmt->bind_param('iii', $car_id, $other_user_id, $current_user_id);
} else {
    $conv_stmt->bind_param('iii', $car_id, $current_user_id, $other_user_id);
}
$conv_stmt->execute();
$conv_result = $conv_stmt->get_result();

if ($conv_result->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$conversation_id = $conv_result->fetch_assoc()['conversation_id'];

// Get messages with sender names
$stmt = $conn->prepare("SELECT m.message_id, m.sender_id, m.receiver_id, m.message, m.file_path, m.type, m.timestamp, u.name as sender_name
                       FROM messages m 
                       JOIN users u ON m.sender_id = u.user_id
                       WHERE m.conversation_id = ? 
                       ORDER BY timestamp ASC");
$stmt->bind_param('i', $conversation_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message_id' => $row['message_id'],
        'sender_id' => $row['sender_id'],
        'receiver_id' => $row['receiver_id'],
        'message' => $row['message'],
        'file_path' => $row['file_path'],
        'type' => $row['type'],
        'timestamp' => $row['timestamp'],
        'sender_name' => $row['sender_name'] // Add sender name
    ];
}

header('Content-Type: application/json');
echo json_encode($messages);
?>
