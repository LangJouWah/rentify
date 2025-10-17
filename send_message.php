<?php
include 'db_connect.php';
include 'auth.php';  // Validate token if needed

$conversation_id = $_POST['conversation_id'];
$sender_id = $_POST['sender_id'];
$receiver_id = $_POST['receiver_id'];
$message = htmlspecialchars($_POST['message']);
$type = $_POST['type'];

$stmt = $conn->prepare("INSERT INTO Messages (conversation_id, sender_id, receiver_id, message, type) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('iiiss', $conversation_id, $sender_id, $receiver_id, $message, $type);
$stmt->execute();
$stmt->close();
echo 'success';

// In send_message.php
if ($type == 'file') {
    $file_path = 'uploads/' . $message;  // $message is filename from upload
    $stmt = $conn->prepare("INSERT INTO Messages (conversation_id, sender_id, receiver_id, file_path, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('iiiss', $conversation_id, $sender_id, $receiver_id, $file_path, $type);
} else {
    $stmt = $conn->prepare("INSERT INTO Messages (conversation_id, sender_id, receiver_id, message, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('iiiss', $conversation_id, $sender_id, $receiver_id, $message, $type);
}
$stmt->execute();
?>