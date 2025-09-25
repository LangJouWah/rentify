<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Review - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function validateForm() {
            const rating = document.querySelector('input[name="rating"]').value;
            if (rating < 1 || rating > 5) {
                alert('Rating must be between 1 and 5.');
                return false;
            }
            return true;
        }
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <header class="bg-blue-600 text-white">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="space-x-4">
                <a href="customer_dashboard.html" class="hover:underline">Dashboard</a>
                <a href="logout.php" class="hover:underline">Log Out</a>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6">Submit Review</h2>
        <?php
        include 'auth.php';
        $token = $_GET['token'];
        $user = get_user_from_token($token);
        if (!$user || $user['role'] !== 'customer') {
            echo '<p class="text-red-600">Unauthorized. Please <a href="login.html" class="text-blue-600 hover:underline">log in</a> as a customer.</p>';
            exit;
        }
        $booking_id = $_GET['booking_id'];
        $sql_check = "SELECT * FROM Bookings WHERE booking_id = ? AND user_id = ? AND status = 'completed'";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $booking_id, $user['user_id']);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows == 0) {
            echo '<p class="text-red-600">Invalid or incomplete booking.</p>';
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $rating = $_POST['rating'];
            $comment = $_POST['comment'];
            $sql = "INSERT INTO Reviews (booking_id, rating, comment) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $booking_id, $rating, $comment);
            if ($stmt->execute()) {
                echo '<p class="text-green-600">Review submitted successfully!</p>';
            } else {
                echo '<p class="text-red-600">Error submitting review.</p>';
            }
        }
        ?>
        <div class="bg-white p-6 rounded-lg shadow max-w-md mx-auto">
            <form method="POST" onsubmit="return validateForm()">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2" for="rating">Rating (1-5)</label>
                    <input type="number" name="rating" id="rating" min="1" max="5" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2" for="comment">Comment</label>
                    <textarea name="comment" id="comment" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" rows="4"></textarea>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition">Submit Review</button>
            </form>
        </div>
    </main>
    <footer class="bg-gray-800 text-white text-center py-4">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
    </footer>
</body>
</html>