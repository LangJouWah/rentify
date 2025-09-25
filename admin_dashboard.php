<?php
// admin_dashboard.php - Admin features
include 'auth.php';

$token = $_GET['token'];
$user = get_user_from_token($token);
if (!$user || $user['role'] !== 'admin') die("Unauthorized");

// Get admin_id
$sql_admin = "SELECT admin_id FROM Admins WHERE user_id = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $user['user_id']);
$stmt_admin->execute();
$admin_id = $stmt_admin->get_result()->fetch_assoc()['admin_id'];

// Manage users, cars, bookings, payments (similar to above, with CRUD operations)

// Generate report example: financial report
if (isset($_GET['generate_report'])) {
    $report_type = 'financial report';
    // Query total income
    $sql_income = "SELECT SUM(total_price) AS total FROM Bookings WHERE status = 'completed'";
    $total = $conn->query($sql_income)->fetch_assoc()['total'];
    $content = "Total income: $" . $total;

    $sql_report = "INSERT INTO Reports (admin_id, report_type, content) VALUES (?, ?, ?)";
    $stmt_report = $conn->prepare($sql_report);
    $stmt_report->bind_param("iss", $admin_id, $report_type, $content);
    $stmt_report->execute();
    echo "Report generated.";
}

// List reports, etc.
?>
<a href="?generate_report=1">Generate Financial Report</a>