<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <header class="bg-blue-600 text-white">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="space-x-4">
                <a href="cars.php" class="hover:underline">Back to Cars</a>
                <a href="logout.php" class="hover:underline">Log Out</a>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6">Payment</h2>
        <?php
        include 'auth.php';
        $token = $_GET['token'];
        $user = get_user_from_token($token);
        if (!$user) {
            echo '<p class="text-red-600">Unauthorized. Please <a href="login.html" class="text-blue-600 hover:underline">log in</a>.</p>';
            exit;
        }
        $booking_id = $_GET['booking_id'];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $payment_method = $_POST['payment_method'] ?? 'credit card';
            $sql = "INSERT INTO Payments (booking_id, payment_method, payment_status) VALUES (?, ?, 'completed')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $booking_id, $payment_method);
            if ($stmt->execute()) {
                echo '<p class="text-green-600">Payment completed successfully!</p>';
            } else {
                echo '<p class="text-red-600">Payment failed.</p>';
            }
        }
        ?>
        <div class="bg-white p-6 rounded-lg shadow max-w-md mx-auto">
            <form method="POST">
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2" for="payment_method">Payment Method</label>
                    <select name="payment_method" id="payment_method" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                        <option value="credit card">Credit Card</option>
                        <option value="PayPal">PayPal</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition">Pay Now</button>
            </form>
        </div>
    </main>
    <footer class="bg-gray-800 text-white text-center py-4">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
    </footer>
</body>
</html>