<?php
include 'db_connect.php';
include 'auth.php';

$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

$conversation_id = $_GET['conversation_id'] ?? null;

if (!$conversation_id) {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit;
}

// Get conversation details
$stmt = $conn->prepare("SELECT car_id, customer_id, owner_id FROM conversations WHERE conversation_id = ?");
$stmt->bind_param('i', $conversation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false]);
    exit;
}

$conv_data = $result->fetch_assoc();

// Determine if current user is owner or customer and set other_user_id accordingly
if ($user['user_id'] == $conv_data['customer_id']) {
    // Current user is customer, other user is owner
    $response = [
        'success' => true,
        'car_id' => $conv_data['car_id'],
        'other_user_id' => $conv_data['owner_id'],
        'current_user_role' => 'customer'
    ];
} else {
    // Current user is owner, other user is customer
    $response = [
        'success' => true,
        'car_id' => $conv_data['car_id'],
        'other_user_id' => $conv_data['customer_id'],
        'current_user_role' => 'owner'
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>