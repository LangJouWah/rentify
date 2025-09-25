<?php
include 'auth.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $token = login($email, $password);
    if ($token) {
        // Set JWT token in an HttpOnly cookie
        setcookie('jwt_token', $token, time() + 3600, '/', '', false, true); // HttpOnly cookie, expires in 1 hour
        header("Location: dashboard.php");
    } else {
        echo "Invalid credentials.";
    }
}
?>