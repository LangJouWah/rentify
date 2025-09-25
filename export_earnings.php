<?php
include 'auth.php';
include 'db_connect.php';

$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user || $user['role'] !== 'owner') {
    header('Location: login.html');
    exit;
}

$sql_owner = "SELECT owner_id FROM Owners WHERE user_id = ?";
$stmt_owner = $conn->prepare($sql_owner);
$stmt_owner->bind_param("i", $user['user_id']);
$stmt_owner->execute();
$owner_result = $stmt_owner->get_result();
if ($owner_result->num_rows === 0) {
    echo 'Owner profile not found.';
    exit;
}
$owner_id = $owner_result->fetch_assoc()['owner_id'];
$stmt_owner->close();

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="earnings.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Booking ID', 'Car', 'Customer', 'Start Date', 'End Date', 'Amount', 'Payment Status', 'Booking Status']);

$sql_earnings = "SELECT b.booking_id, c.brand, c.model, u.name, b.start_date, b.end_date, b.total_amount, b.payment_status, b.status FROM Bookings b JOIN Cars c ON b.car_id = c.car_id JOIN Users u ON b.user_id = u.user_id WHERE c.owner_id = ? AND b.status = 'completed' AND b.payment_status = 'completed'";
$stmt_earnings = $conn->prepare($sql_earnings);
$stmt_earnings->bind_param("i", $owner_id);
$stmt_earnings->execute();
$result = $stmt_earnings->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['booking_id'],
        $row['brand'] . ' ' . $row['model'],
        $row['name'],
        $row['start_date'],
        $row['end_date'],
        '₱' . number_format($row['total_amount'], 2),
        $row['payment_status'],
        $row['status'] ?: 'Pending'
    ]);
}

$stmt_earnings->close();
fclose($output);
exit;
?>