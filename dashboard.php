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
        include 'auth.php';

        // Retrieve token from cookie instead of GET parameter
        $token = $_COOKIE['jwt_token'] ?? '';
        $user = get_user_from_token($token);
        if (!$user) {
            echo '<p class="text-red-600">Unauthorized. Please <a href="login.html" class="text-blue-600 hover:underline">log in</a>.</p>';
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