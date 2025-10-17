<?php
session_start();
include 'db_connect.php'; // Include the database connection file

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method');
}

// Sanitize and validate input
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    die('Email and password are required');
}

// Ensure $conn is defined
if (!isset($conn)) {
    die('Database connection failed');
}

// Prepare and execute the query
$stmt = $conn->prepare("SELECT user_id, password FROM Users WHERE email = ? AND role = 'admin'");
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['admin_id'] = $user['user_id'];
    header("Location: admin_dashboard.php");
    exit; // Ensure no further code is executed after redirection
} else {
    echo "Invalid admin credentials.";
}

$stmt->close();
$conn->close();
?>