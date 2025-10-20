<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function toggleProfileDropdown() {
            document.getElementById('profileDropdown').classList.toggle('hidden');
        }
    </script>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="p-2 text-gray-100 hover:bg-teal-700 rounded-lg transition" title="Dashboard">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </a>
                    
                    <div class="relative">
                        <button onclick="toggleProfileDropdown()" class="flex items-center space-x-2">
                            <span class="text-gray-100"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-gray-900 border border-gray-700 rounded-lg shadow-lg">
                            <a href="profile.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Account Settings</a>
                            <a href="messages.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Messages</a>
                            <a href="booking_history.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Booking History</a>
                            <a href="wishlist.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Wishlist</a>
                            <a href="logout.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Log Out</a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Page Header -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold mb-2">Complete Your Payment</h1>
                <p class="text-gray-400">Secure payment for your car rental booking</p>
            </div>

            <?php
            include 'auth.php';
            include 'db_connect.php';
            
            // Get token from cookie instead of URL for security
            $token = $_COOKIE['jwt_token'] ?? '';
            $user = get_user_from_token($token);
            if (!$user || $user['role'] !== 'customer') {
                echo '<div class="bg-red-900 border border-red-700 text-red-100 p-6 rounded-lg mb-6 text-center">
                        <p class="text-lg font-semibold mb-2">Unauthorized Access</p>
                        <p>Please <a href="login.php" class="text-teal-400 hover:underline">log in</a> as a customer to continue.</p>
                      </div>';
                exit;
            }
            
            $booking_id = $_GET['booking_id'] ?? null;
            if (!$booking_id) {
                echo '<div class="bg-red-900 border border-red-700 text-red-100 p-6 rounded-lg mb-6 text-center">
                        <p class="text-lg font-semibold">Invalid Booking ID</p>
                        <p>Please check your booking and try again.</p>
                      </div>';
                exit;
            }
            
            // Fetch booking details to verify ownership and amount
            $sql_booking = "SELECT b.*, c.brand, c.model, c.price, c.image 
                           FROM Bookings b 
                           JOIN Cars c ON b.car_id = c.car_id 
                           WHERE b.booking_id = ? AND b.user_id = ?";
            $stmt_booking = $conn->prepare($sql_booking);
            $stmt_booking->bind_param("ii", $booking_id, $user['user_id']);
            $stmt_booking->execute();
            $booking = $stmt_booking->get_result()->fetch_assoc();
            
            if (!$booking) {
                echo '<div class="bg-red-900 border border-red-700 text-red-100 p-6 rounded-lg mb-6 text-center">
                        <p class="text-lg font-semibold">Booking Not Found</p>
                        <p>This booking does not exist or you do not have permission to access it.</p>
                      </div>';
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
                    
                    echo '<div class="bg-teal-900 border border-teal-700 text-teal-100 p-8 rounded-lg mb-6 text-center">
                            <div class="text-6xl mb-4">✅</div>
                            <h2 class="text-2xl font-bold mb-4">Payment Completed Successfully!</h2>
                            <p class="text-lg mb-4">Your booking for <strong>' . htmlspecialchars($booking['brand'] . ' ' . $booking['model']) . '</strong> is now confirmed.</p>
                            <div class="flex justify-center space-x-4 mt-6">
                                <a href="dashboard.php" class="bg-teal-600 hover:bg-teal-700 text-gray-100 px-6 py-3 rounded-lg transition">Return to Dashboard</a>
                                <a href="booking_history.php" class="bg-gray-700 hover:bg-gray-600 text-gray-100 px-6 py-3 rounded-lg transition">View Booking History</a>
                            </div>
                          </div>';
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    echo '<div class="bg-red-900 border border-red-700 text-red-100 p-6 rounded-lg mb-6 text-center">
                            <div class="text-4xl mb-4">❌</div>
                            <h2 class="text-xl font-bold mb-2">Payment Failed</h2>
                            <p>Please try again or contact support if the problem persists.</p>
                          </div>';
                }
            }
            ?>
            
            <?php if ($_SERVER['REQUEST_METHOD'] != 'POST' || isset($e)): ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Booking Summary -->
                <div class="bg-gray-900 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-xl font-semibold mb-4 text-teal-400">Booking Summary</h3>
                    
                    <div class="flex items-start space-x-4 mb-6">
                        <img src="<?php echo $booking['image'] ?: 'Uploads/cars/placeholder.jpg'; ?>" 
                             alt="Car Image" 
                             class="w-20 h-20 object-cover rounded-lg">
                        <div>
                            <h4 class="font-semibold text-lg"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></h4>
                            <p class="text-gray-400 text-sm">Daily Rate: ₱<?php echo number_format($booking['price'], 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Booking Dates:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($booking['start_date'] . ' to ' . $booking['end_date']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Booking ID:</span>
                            <span class="font-mono text-teal-400">#<?php echo $booking_id; ?></span>
                        </div>
                        <div class="flex justify-between text-lg font-semibold border-t border-gray-700 pt-3 mt-3">
                            <span class="text-gray-100">Total Amount:</span>
                            <span class="text-teal-400">₱<?php echo number_format($booking['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="bg-gray-900 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-xl font-semibold mb-6 text-teal-400">Payment Details</h3>
                    
                    <form method="POST">
                        <div class="mb-6">
                            <label class="block text-gray-300 mb-3 font-medium" for="payment_method">Payment Method</label>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input type="radio" id="credit_card" name="payment_method" value="credit card" checked class="hidden peer">
                                    <label for="credit_card" class="flex items-center p-4 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-800 peer-checked:border-teal-500 peer-checked:bg-teal-900/20 w-full">
                                        <div class="w-6 h-6 border-2 border-gray-600 rounded-full mr-3 flex items-center justify-center peer-checked:border-teal-500">
                                            <div class="w-3 h-3 bg-teal-500 rounded-full hidden peer-checked:block"></div>
                                        </div>
                                        <div>
                                            <span class="font-medium">Credit Card</span>
                                            <p class="text-sm text-gray-400">Pay with your credit card</p>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="radio" id="paypal" name="payment_method" value="PayPal" class="hidden peer">
                                    <label for="paypal" class="flex items-center p-4 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-800 peer-checked:border-teal-500 peer-checked:bg-teal-900/20 w-full">
                                        <div class="w-6 h-6 border-2 border-gray-600 rounded-full mr-3 flex items-center justify-center peer-checked:border-teal-500">
                                            <div class="w-3 h-3 bg-teal-500 rounded-full hidden peer-checked:block"></div>
                                        </div>
                                        <div>
                                            <span class="font-medium">PayPal</span>
                                            <p class="text-sm text-gray-400">Pay with your PayPal account</p>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="radio" id="gcash" name="payment_method" value="gcash" class="hidden peer">
                                    <label for="gcash" class="flex items-center p-4 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-800 peer-checked:border-teal-500 peer-checked:bg-teal-900/20 w-full">
                                        <div class="w-6 h-6 border-2 border-gray-600 rounded-full mr-3 flex items-center justify-center peer-checked:border-teal-500">
                                            <div class="w-3 h-3 bg-teal-500 rounded-full hidden peer-checked:block"></div>
                                        </div>
                                        <div>
                                            <span class="font-medium">GCash</span>
                                            <p class="text-sm text-gray-400">Pay using GCash mobile wallet</p>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer" class="hidden peer">
                                    <label for="bank_transfer" class="flex items-center p-4 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-800 peer-checked:border-teal-500 peer-checked:bg-teal-900/20 w-full">
                                        <div class="w-6 h-6 border-2 border-gray-600 rounded-full mr-3 flex items-center justify-center peer-checked:border-teal-500">
                                            <div class="w-3 h-3 bg-teal-500 rounded-full hidden peer-checked:block"></div>
                                        </div>
                                        <div>
                                            <span class="font-medium">Bank Transfer</span>
                                            <p class="text-sm text-gray-400">Direct bank transfer</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-800 rounded-lg p-4 mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-400">Total to pay:</span>
                                <span class="text-2xl font-bold text-teal-400">₱<?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                            <p class="text-sm text-gray-500">By completing this payment, you agree to our terms and conditions.</p>
                        </div>
                        
                        <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-gray-100 p-4 rounded-lg font-semibold text-lg transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-gray-900">
                            Pay Now - ₱<?php echo number_format($booking['total_amount'], 2); ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="bg-gray-900 text-gray-100 text-center py-4 mt-12">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
        <div class="mt-2">
            <a href="https://rentify.com/terms" class="text-gray-400 hover:text-gray-200 mx-2">Terms of Service</a>
            <a href="https://rentify.com/privacy" class="text-gray-400 hover:text-gray-200 mx-2">Privacy Policy</a>
        </div>
    </footer>
</body>
</html>
