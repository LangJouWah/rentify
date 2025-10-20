<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <main class="container mx-auto px-4 py-8 text-center">
        <?php
// Add headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include 'auth.php';

// Retrieve token from cookie
$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);

if (!$user) {
    header("Location: login.php");
    exit;
}

// Redirect based on user role
if ($user['role'] == 'customer') {
    header("Location: customer_dashboard.php");
} elseif ($user['role'] == 'owner') {
    header("Location: owner_dashboard.php");
} elseif ($user['role'] == 'admin') {
    header("Location: admin_dashboard.php");
} else {
    echo '<p class="text-red-600">Invalid user role.</p>';
    exit;
}
?>
        <p>Redirecting...</p>
        <a href="index.html" class="inline-block mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Go to Home</a>
    </main>
    <footer class="bg-gray-800 text-white text-center py-4">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
    </footer>
</body>
</html>
