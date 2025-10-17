<?php
include 'db_connect.php';

$car_id = $_GET['car_id'] ?? null;
$user_id = $_GET['user_id'] ?? null;

if (!$car_id || !$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$stmt = $conn->prepare("SELECT is_typing FROM Typing WHERE car_id = ? AND user_id = ? AND last_updated >= DATE_SUB(NOW(), INTERVAL 5 SECOND)");
$stmt->bind_param("ii", $car_id, $user_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $is_typing = $result->num_rows > 0 ? $result->fetch_assoc()['is_typing'] : false;
    echo json_encode(['is_typing' => $is_typing]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $stmt->error]);
}
?>