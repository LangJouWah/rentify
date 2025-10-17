<?php
session_start();
include 'db_connect.php'; // Ensure this connects to your database

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method');
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}

// Clear the CSRF token after use
unset($_SESSION['csrf_token']);

// Sanitize and validate input
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? '';
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
$password = $_POST['password'] ?? '';
$role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING) ?? '';
$contact_info = filter_input(INPUT_POST, 'contact_info', FILTER_SANITIZE_STRING) ?? '';

if ($role !== 'admin') {
    die('Invalid role for admin signup');
}

if (empty($name) || empty($email) || empty($password)) {
    die('Missing required fields');
}

// Check for duplicate email
$stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    die('Email already exists');
}
$stmt->close();

// Start a transaction to ensure both inserts succeed
$conn->begin_transaction();

try {
    // Insert into Users table
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO Users (name, email, password, role, contact_info) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $password_hash, $role, $contact_info);
    if (!$stmt->execute()) {
        throw new Exception("Error creating user: " . $conn->error);
    }
    $user_id = $conn->insert_id; // Get the new user_id
    $stmt->close();

    // Insert into Admins table
    $stmt_admin = $conn->prepare("INSERT INTO Admins (user_id) VALUES (?)");
    $stmt_admin->bind_param("i", $user_id);
    if (!$stmt_admin->execute()) {
        throw new Exception("Error creating admin record: " . $conn->error);
    }
    $stmt_admin->close();

    // Commit the transaction
    $conn->commit();
    header("Location: admin_login.html");
    exit;
} catch (Exception $e) {
    // Roll back the transaction on error
    $conn->rollback();
    die($e->getMessage());
}

$conn->close();
?>