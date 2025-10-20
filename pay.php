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
                <a href="dashboard.php" class="hover:underline">Back to Dashboard</a>
                <a href="logout.php" class="hover:underline">Log Out</a>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6">Payment</h2>
        <?php
        include 'auth.php';
        include 'db_connect.php';
        
        // Get token from cookie instead of URL for security
        $token = $_COOKIE['jwt_token'] ?? '';
        $user = get_user_from_token($token);
        if (!$user || $user['role'] !== 'customer') {
            echo '<p class="text-red-600">Unauthorized. Please <a href="login.php" class="text-blue-600 hover:underline">log in</a> as a customer.</p>';
            exit;
        }
        
        $booking_id = $_GET['booking_id'] ?? null;
        if (!$booking_id) {
            echo '<p class="text-red-600">Invalid booking ID.</p>';
            exit;
        }
        
        // Fetch booking details to verify ownership and amount
        $sql_booking = "SELECT b.*, c.brand, c.model, c.price 
                       FROM Bookings b 
                       JOIN Cars c ON b.car_id = c.car_id 
                       WHERE b.booking_id = ? AND b.user_id = ?";
        $stmt_booking = $conn->prepare($sql_booking);
        $stmt_booking->bind_param("ii", $booking_id, $user['user_id']);
        $stmt_booking->execute();
        $booking = $stmt_booking->get_result()->fetch_assoc();
        
        if (!$booking) {
            echo '<p class="text-red-600">Booking not found or you do not have permission to pay for this booking.</p>';
            exit;
        }
        $stmt_booking->close();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $payment_method = $_POST['payment_method'] ?? 'credit card';
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // 1. Insert into Payments table
                $sql_payment = "INSERT INTO Payments (booking_id, payment_method, payment_status) VALUES (?, ?, 'completed')";
                $stmt_payment = $conn->prepare($sql_payment);
                $stmt_payment->bind_param("is", $booking_id, $payment_method);
                $stmt_payment->execute();
                $stmt_payment->close();
                
                // 2. Update Bookings table payment_status
                $sql_update_booking = "UPDATE Bookings SET payment_status = 'completed' WHERE booking_id = ?";
                $stmt_update = $conn->prepare($sql_update_booking);
                $stmt_update->bind_param("i", $booking_id);
                $stmt_update->execute();
                $stmt_update->close();
                
                // Commit transaction
                $conn->commit();
                
                echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                echo '<p class="font-bold">Payment completed successfully!</p>';
                echo '<p>Your booking for ' . htmlspecialchars($booking['brand'] . ' ' . $booking['model']) . ' is now confirmed.</p>';
                echo '<a href="dashboard.php" class="text-blue-600 hover:underline mt-2 inline-block">Return to Dashboard</a>';
                echo '</div>';
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">';
                echo '<p class="font-bold">Payment failed. Please try again.</p>';
                echo '</div>';
            }
        }
        ?>
        
        <?php if ($_SERVER['REQUEST_METHOD'] != 'POST' || isset($e)): ?>
        <div class="bg-white p-6 rounded-lg shadow max-w-md mx-auto">
            <div class="mb-6">
                <h3 class="text-xl font-semibold mb-2">Booking Summary</h3>
                <p class="text-gray-700">Car: <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></p>
                <p class="text-gray-700">Dates: <?php echo htmlspecialchars($booking['start_date'] . ' to ' . $booking['end_date']); ?></p>
                <p class="text-gray-700 font-bold text-lg">Total Amount: ₱<?php echo number_format($booking['total_amount'], 2); ?></p>
            </div>
            
            <form method="POST">
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2" for="payment_method">Payment Method</label>
                    <select name="payment_method" id="payment_method" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                        <option value="credit card">Credit Card</option>
                        <option value="PayPal">PayPal</option>
                        <option value="gcash">GCash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition">Pay Now</button>
            </form>
        </div>
        <?php endif; ?>
    </main>
    <footer class="bg-gray-800 text-white text-center py-4">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
    </footer>
</body>
</html>
