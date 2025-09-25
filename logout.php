<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <header class="bg-blue-600 text-white">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="space-x-4">
                <a href="index.php" class="hover:underline">Home</a>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8 text-center">
        <?php
        // Clear any token stored in cookies (if used)
        setcookie('jwt_token', '', time() - 3600, '/');
        // In a real app, you might also invalidate the token server-side (e.g., blacklist it)
        echo '<p class="text-green-600 text-xl mb-4">You have been logged out.</p>';
        echo '<p>Redirecting to home page...</p>';
        // Redirect to home page after 2 seconds
        header("refresh:2;url=index.php");
        ?>
        <a href="index.php" class="inline-block mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Go to Home</a>
    </main>
    <footer class="bg-gray-800 text-white text-center py-4">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
    </footer>
</body>
</html>