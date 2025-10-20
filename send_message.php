<?php
include 'db_connect.php';
include 'auth.php';

$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user) {
    http_response_code(401);
    exit;
}

$car_id = $_POST['car_id'] ?? null;
$sender_id = $_POST['sender_id'] ?? null;
$receiver_id = $_POST['receiver_id'] ?? null;
$message = $_POST['message'] ?? '';
$type = $_POST['type'] ?? 'text';

if (!$car_id || !$sender_id || !$receiver_id) {
    http_response_code(400);
    echo "Missing required parameters";
    exit;
}

// Get or create conversation
$conv_stmt = $conn->prepare("SELECT conversation_id FROM conversations 
                           WHERE car_id = ? AND customer_id = ? AND owner_id = ?");
if ($user['role'] === 'owner') {
    $conv_stmt->bind_param('iii', $car_id, $receiver_id, $sender_id);
} else {
    $conv_stmt->bind_param('iii', $car_id, $sender_id, $receiver_id);
}
$conv_stmt->execute();
$conv_result = $conv_stmt->get_result();

if ($conv_result->num_rows > 0) {
    $conversation_id = $conv_result->fetch_assoc()['conversation_id'];
} else {
    // Create new conversation
    $customer_id = ($user['role'] === 'owner') ? $receiver_id : $sender_id;
    $owner_id = ($user['role'] === 'owner') ? $sender_id : $receiver_id;
    
    $insert_conv = $conn->prepare("INSERT INTO conversations (customer_id, owner_id, car_id) 
                                  VALUES (?, ?, ?)");
    $insert_conv->bind_param('iii', $customer_id, $owner_id, $car_id);
    $insert_conv->execute();
    $conversation_id = $conn->insert_id;
}

// Insert message
$file_path = ($type === 'file') ? $message : null;
$message_content = ($type === 'text') ? $message : null;

$stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, receiver_id, message, file_path, type, car_id) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('iiisssi', $conversation_id, $sender_id, $receiver_id, $message_content, $file_path, $type, $car_id);

if ($stmt->execute()) {
    echo "Message sent";
} else {
    http_response_code(500);
    echo "Error sending message";
}
?>
