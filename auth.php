<?php
// auth.php - Authentication functions
include 'db_connect.php';
include 'jwt.php';

function signup($name, $email, $password, $role, $contact_info = null) {
    global $conn;
    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO Users (name, email, password, role, contact_info) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $name, $email, $hashed_pass, $role, $contact_info);
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        if ($role == 'owner') {
            // Insert into Owners (drivers_license and address can be updated later)
            $sql_owner = "INSERT INTO Owners (user_id) VALUES (?)";
            $stmt_owner = $conn->prepare($sql_owner);
            $stmt_owner->bind_param("i", $user_id);
            $stmt_owner->execute();
        } elseif ($role == 'admin') {
            // Insert into Admins
            $sql_admin = "INSERT INTO Admins (user_id) VALUES (?)";
            $stmt_admin = $conn->prepare($sql_admin);
            $stmt_admin->bind_param("i", $user_id);
            $stmt_admin->execute();
        }
        return true;
    }
    return false;
}

function login($email, $password) {
    global $conn;
    $sql = "SELECT user_id, password, role FROM Users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $payload = [
                'user_id' => $row['user_id'],
                'role' => $row['role'],
                'exp' => time() + 3600 // 1 hour expiration
            ];
            return generate_jwt($payload);
        }
    }
    return false;
}

function get_user_from_token($token) {
    $payload = verify_jwt($token);
    if ($payload) {
        return $payload;
    }
    return false;
}

function is_role($token, $required_role) {
    $user = get_user_from_token($token);
    return $user && $user['role'] === $required_role;
}
?>