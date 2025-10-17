<?php
include 'db_connect.php';

$car_id = $_GET['car_id'] ?? null;
$current_user_id = $_GET['current_user_id'] ?? null;
$other_user_id = $_GET['other_user_id'] ?? null;
$is_owner = $_GET['is_owner'] === 'true';

if (!$car_id || !$current_user_id || !$other_user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// Construct the WHERE clause based on user role
$where_clause = $is_owner ?
    "WHERE m.car_id = ? AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))" :
    "WHERE m.car_id = ? AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))";
$sql_conversation = "
    SELECT m.*, u.name AS sender_name, c.brand, c.model, IFNULL(m.file_path, '') AS file_path
    FROM Messages m
    JOIN Users u ON m.sender_id = u.user_id
    JOIN Cars c ON m.car_id = c.car_id
    $where_clause
    ORDER BY m.sent_at ASC";

$stmt = $conn->prepare($sql_conversation);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("iiiii", $car_id, $current_user_id, $other_user_id, $other_user_id, $current_user_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'message_id' => $row['message_id'],
            'sender_id' => $row['sender_id'],
            'receiver_id' => $row['receiver_id'],
            'message' => $row['message'],  // Use 'message' to match database column
            'type' => $row['file_path'] ? 'file' : 'text',
            'file_path' => $row['file_path'],
            'sent_at' => $row['sent_at'],
            'sender_name' => $row['sender_name']
        ];
    }
    // Mark messages as read for the current user
    $update_stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE car_id = ? AND receiver_id = ? AND sender_id = ?");
    $update_stmt->bind_param("iii", $car_id, $current_user_id, $other_user_id);
    $update_stmt->execute();
    $update_stmt->close();

    echo json_encode($messages);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
}

$stmt->close();
echo json_encode($messages);
?>