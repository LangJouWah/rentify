<?php
include 'auth.php';

header('Content-Type: application/json');

// Check if JWT token exists in cookies
$token = $_COOKIE['jwt_token'] ?? '';

if ($token) {
    $user = get_user_from_token($token);
    if ($user) {
        echo json_encode(['loggedIn' => true]);
        exit;
    }
}

echo json_encode(['loggedIn' => false]);
?>