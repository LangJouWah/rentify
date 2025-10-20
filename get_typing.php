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

if (!$car_id || !$user_id) {
    http_response_code(400);
    echo json_encode(['is_typing' => false]);
    exit;
}

// Find conversation and check typing status
$stmt = $conn->prepare("SELECT t.is_typing 
                       FROM typingindicators t 
                       JOIN conversations c ON t.conversation_id = c.conversation_id 
                       WHERE c.car_id = ? AND t.user_id = ? AND t.last_updated > DATE_SUB(NOW(), INTERVAL 3 SECOND)");
$stmt->bind_param('ii', $car_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$is_typing = $result->num_rows > 0 ? $result->fetch_assoc()['is_typing'] : 0;

header('Content-Type: application/json');
echo json_encode(['is_typing' => (bool)$is_typing]);
?>
