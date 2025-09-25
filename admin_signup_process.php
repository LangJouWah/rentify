<?php
session_start();
include 'db_connect.php'; // Ensure this connects to your database

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method');
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$contact_info = $_POST['contact_info'] ?? '';

if ($role !== 'admin') {
    die('Invalid role for admin signup');
}

if (empty($name) || empty($email) || empty($password)) {
    die('Missing required fields');
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO Users (name, email, password, role, contact_info) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $email, $password_hash, $role, $contact_info);

if ($stmt->execute()) {
    header("Location: admin_login.html");
    exit;
} else {
    echo "Error: " . $conn->error;
}

$stmt->close();
$conn->close();
?>