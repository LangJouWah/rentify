<?php
// signup_process.php
include 'auth.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $contact_info = $_POST['contact_info'] ?? null;
    if (signup($name, $email, $password, $role, $contact_info)) {
        echo "Signup successful! <a href='login.php'>Log in</a>";
    } else {
        echo "Error signing up.";
    }
}
?>