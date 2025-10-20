<?php
include 'db_connect.php';
include 'auth.php';

$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$car_id = $_POST['car_id'] ?? null;
$sender_id = $_POST['sender_id'] ?? null;
$receiver_id = $_POST['receiver_id'] ?? null;

if (!$car_id || !$sender_id || !$receiver_id || !isset($_FILES['file'])) {
    http_response_code(400);
    echo 'Missing parameters or file';
    exit;
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$max_size = 5 * 1024 * 1024; // 5MB
$file_type = $_FILES['file']['type'];
$file_size = $_FILES['file']['size'];
$file_tmp = $_FILES['file']['tmp_name'];
$file_name = uniqid('chat_') . '_' . $_FILES['file']['name'];

if (!in_array($file_type, $allowed_types) || $file_size > $max_size) {
    http_response_code(400);
    echo 'Invalid file type or size';
    exit;
}

$upload_dir = 'Uploads/chats/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
$destination = $upload_dir . $file_name;

if (move_uploaded_file($file_tmp, $destination)) {
    echo $destination;
} else {
    http_response_code(500);
    echo 'Error uploading file';
}
?>
