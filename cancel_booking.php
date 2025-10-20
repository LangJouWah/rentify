<?php
include 'auth.php';
include 'db_connect.php';

header('Content-Type: application/json');

// Retrieve token from cookie
$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user || $user['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? '';
    
    if (empty($booking_id)) {
        echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
        exit;
    }
    
    // Check if booking belongs to user and is cancellable
    $check_sql = "SELECT status FROM bookings WHERE booking_id = ? AND user_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ii", $booking_id, $user['user_id']);
    $stmt_check->execute();
    $booking = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    if ($booking['status'] !== 'confirmed') {
        echo json_encode(['success' => false, 'message' => 'Only confirmed bookings can be cancelled']);
        exit;
    }
    
    // Update booking status to cancelled
    $update_sql = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?";
    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->bind_param("i", $booking_id);
    
    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel booking']);
    }
    
    $stmt_update->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
