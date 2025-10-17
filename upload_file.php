<?php
include 'db_connect.php';

$conversation_id = $_POST['conversation_id'];
$sender_id = $_POST['sender_id'];
$receiver_id = $_POST['receiver_id'];

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (in_array($file['type'], $allowed_types) && $file['size'] < 5 * 1024 * 1024) {  // 5MB limit
        $upload_dir = '../uploads/';
        $file_name = time() . '_' . basename($file['name']);
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            echo $file_name;  // Return filename for send_message
        } else {
            echo '';
        }
    } else {
        echo '';
    }
}
?>