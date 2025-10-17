<?php
session_start();
include 'db_connect.php'; // Ensure database connection

// Check if admin is authenticated via session
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized: No active admin session");
}

// Fetch user details using session admin_id
$stmt = $conn->prepare("SELECT user_id, role FROM Users WHERE user_id = ? AND role = 'admin'");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Unauthorized: Invalid admin session");
}

// Get admin_id from Admins table
$sql_admin = "SELECT admin_id FROM Admins WHERE user_id = ?";
$stmt_admin = $conn->prepare($sql_admin);
if (!$stmt_admin) {
    die("Prepare failed: " . $conn->error);
}
$stmt_admin->bind_param("i", $user['user_id']);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$admin_row = $result_admin->fetch_assoc();
$stmt_admin->close();

if (!$admin_row) {
    // Log the error for debugging (optional)
    error_log("No admin record found for user_id " . $user['user_id']);
    die("Error: No admin record found for user_id " . htmlspecialchars($user['user_id']));
}
$admin_id = $admin_row['admin_id'];

// Handle report generation
if (isset($_GET['generate_report'])) {
    $report_type = 'financial report';
    // Query total income
    $sql_income = "SELECT SUM(total_price) AS total FROM Bookings WHERE status = 'completed'";
    $result = $conn->query($sql_income);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    $total = $result->fetch_assoc()['total'] ?? 0;
    $content = "Total income: $" . number_format($total, 2);

    // Insert report into Reports table
    $sql_report = "INSERT INTO Reports (admin_id, report_type, content) VALUES (?, ?, ?)";
    $stmt_report = $conn->prepare($sql_report);
    if (!$stmt_report) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt_report->bind_param("iss", $admin_id, $report_type, $content);
    $stmt_report->execute();
    $stmt_report->close();
    echo "Report generated.";
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h2>Welcome, Admin</h2>
    <a href="?generate_report=1">Generate Financial Report</a>
    <!-- Add other admin features (e.g., manage users, cars, bookings) -->
</body>
</html>