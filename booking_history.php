<?php
include 'auth.php';
include 'db_connect.php';

// Retrieve token from cookie
$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user || $user['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

// Get complete user data from database
$user_sql = "SELECT * FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($user_sql);
$stmt_user->bind_param("i", $user['user_id']);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// Handle filters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$booking_sql = "SELECT b.*, c.brand, c.model, c.image, c.location, 
                o.user_id as owner_user_id, u.name as owner_name,
                p.payment_status, p.payment_method
                FROM bookings b 
                JOIN cars c ON b.car_id = c.car_id 
                JOIN owners o ON c.owner_id = o.owner_id
                JOIN users u ON o.user_id = u.user_id
                LEFT JOIN payments p ON b.booking_id = p.booking_id
                WHERE b.user_id = ?";

$params = [$user['user_id']];
$param_types = "i";

if ($status_filter) {
    $booking_sql .= " AND b.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if ($date_from) {
    $booking_sql .= " AND b.start_date >= ?";
    $params[] = $date_from;
    $param_types .= "s";
}

if ($date_to) {
    $booking_sql .= " AND b.end_date <= ?";
    $params[] = $date_to;
    $param_types .= "s";
}

$booking_sql .= " ORDER BY b.booking_date DESC";

$stmt_bookings = $conn->prepare($booking_sql);
if (!empty($params)) {
    $stmt_bookings->bind_param($param_types, ...$params);
}
$stmt_bookings->execute();
$bookings = $stmt_bookings->get_result();
$stmt_bookings->close();

// Get booking statistics for filter badges
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM bookings WHERE user_id = ?";
$stmt_stats = $conn->prepare($stats_sql);
$stmt_stats->bind_param("i", $user['user_id']);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function toggleProfileDropdown() {
            document.getElementById('profileDropdown').classList.toggle('hidden');
        }
        
        function clearFilters() {
            document.getElementById('status').value = '';
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
            document.getElementById('filterForm').submit();
        }
        
        function cancelBooking(bookingId, paymentStatus) {
            if (paymentStatus === 'completed') {
                alert('Cannot cancel booking. Payment has already been completed. Please contact support for assistance.');
                return;
            }
            
            if (confirm('Are you sure you want to cancel this booking?')) {
                fetch('cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'booking_id=' + bookingId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to cancel booking: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error cancelling booking');
                });
            }
        }
    </script>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button onclick="toggleProfileDropdown()" class="flex items-center space-x-2">
                        <span class="text-gray-100"><?php echo htmlspecialchars($user_data['name'] ?? 'User'); ?></span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-gray-900 border border-gray-700 rounded-lg shadow-lg">
                        <a href="dashboard.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Dashboard</a>
                        <a href="profile.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Account Settings</a>
                        <a href="booking_history.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Booking History</a>
                        <a href="wishlist.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Wishlist</a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Log Out</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Booking History</h1>
                <p class="text-gray-400">View and manage all your car rental bookings</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-gray-900 p-6 rounded-lg border border-gray-700">
                    <div class="text-2xl font-bold text-teal-400"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="text-gray-400">Total Bookings</div>
                </div>
                <div class="bg-gray-900 p-6 rounded-lg border border-gray-700">
                    <div class="text-2xl font-bold text-blue-400"><?php echo $stats['confirmed'] ?? 0; ?></div>
                    <div class="text-gray-400">Confirmed</div>
                </div>
                <div class="bg-gray-900 p-6 rounded-lg border border-gray-700">
                    <div class="text-2xl font-bold text-green-400"><?php echo $stats['completed'] ?? 0; ?></div>
                    <div class="text-gray-400">Completed</div>
                </div>
                <div class="bg-gray-900 p-6 rounded-lg border border-gray-700">
                    <div class="text-2xl font-bold text-red-400"><?php echo $stats['cancelled'] ?? 0; ?></div>
                    <div class="text-gray-400">Cancelled</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-900 p-6 rounded-lg border border-gray-700 mb-8">
                <h2 class="text-xl font-semibold mb-4">Filters</h2>
                <form id="filterForm" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="status" class="block text-gray-400 text-sm font-medium mb-2">Status</label>
                        <select id="status" name="status" class="w-full p-3 border border-gray-700 bg-gray-800 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                            <option value="">All Status</option>
                            <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label for="date_from" class="block text-gray-400 text-sm font-medium mb-2">From Date</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                            class="w-full p-3 border border-gray-700 bg-gray-800 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label for="date_to" class="block text-gray-400 text-sm font-medium mb-2">To Date</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                            class="w-full p-3 border border-gray-700 bg-gray-800 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-gray-100 px-6 py-3 rounded-lg transition flex-1">
                            Apply Filters
                        </button>
                        <button type="button" onclick="clearFilters()" class="bg-gray-700 hover:bg-gray-600 text-gray-100 px-4 py-3 rounded-lg transition">
                            Clear
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bookings List -->
            <div class="bg-gray-900 rounded-lg border border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h2 class="text-xl font-semibold">Your Bookings</h2>
                </div>
                
                <div class="divide-y divide-gray-700">
                    <?php if ($bookings && $bookings->num_rows > 0): ?>
                        <?php while ($booking = $bookings->fetch_assoc()): ?>
                            <?php 
                            $canCancel = ($booking['status'] == 'confirmed' && $booking['payment_status'] != 'completed');
                            ?>
                            <div class="p-6 hover:bg-gray-800 transition">
                                <div class="flex flex-col md:flex-row md:items-center justify-between">
                                    <div class="flex items-start space-x-4 mb-4 md:mb-0">
                                        <img src="<?php echo $booking['image'] ?: 'Uploads/cars/placeholder.jpg'; ?>" 
                                            alt="Car Image" class="w-20 h-20 object-cover rounded-lg">
                                        <div>
                                            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></h3>
                                            <p class="text-gray-400 text-sm">Location: <?php echo htmlspecialchars($booking['location']); ?></p>
                                            <p class="text-gray-400 text-sm">Owner: <?php echo htmlspecialchars($booking['owner_name']); ?></p>
                                            <p class="text-gray-400 text-sm">
                                                <?php echo date('M j, Y', strtotime($booking['start_date'])); ?> - 
                                                <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col items-end space-y-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium 
                                                <?php 
                                                if ($booking['status'] == 'confirmed') echo 'bg-blue-900 text-blue-100';
                                                elseif ($booking['status'] == 'completed') echo 'bg-green-900 text-green-100';
                                                else echo 'bg-red-900 text-red-100';
                                                ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium 
                                                <?php 
                                                if ($booking['payment_status'] == 'completed') echo 'bg-green-900 text-green-100';
                                                elseif ($booking['payment_status'] == 'pending') echo 'bg-yellow-900 text-yellow-100';
                                                else echo 'bg-red-900 text-red-100';
                                                ?>">
                                                Payment: <?php echo ucfirst($booking['payment_status'] ?? 'pending'); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="text-right">
                                            <p class="text-lg font-semibold text-teal-400">₱<?php echo number_format($booking['total_amount'], 2); ?></p>
                                            <p class="text-gray-400 text-sm">Booked on: <?php echo date('M j, Y g:i A', strtotime($booking['booking_date'])); ?></p>
                                        </div>
                                        
                                        <div class="flex space-x-2 mt-2">
                                            <?php if ($canCancel): ?>
                                                <button onclick="cancelBooking(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['payment_status'] ?? 'pending'; ?>')" 
                                                    class="bg-red-600 hover:bg-red-700 text-gray-100 px-3 py-1 rounded text-sm transition">
                                                    Cancel Booking
                                                </button>
                                            <?php elseif ($booking['status'] == 'confirmed' && $booking['payment_status'] == 'completed'): ?>
                                                <button class="bg-gray-500 text-gray-300 px-3 py-1 rounded text-sm cursor-not-allowed" 
                                                    title="Cannot cancel - payment completed">
                                                    Cancel Disabled
                                                </button>
                                            <?php endif; ?>
                                            
                                            <a href="car_details.php?car_id=<?php echo $booking['car_id']; ?>" 
                                                class="bg-gray-700 hover:bg-gray-600 text-gray-100 px-3 py-1 rounded text-sm transition">
                                                View Car
                                            </a>
                                            
                                            <?php if ($booking['status'] == 'completed'): ?>
                                                <a href="review.php?car_id=<?php echo $booking['car_id']; ?>&booking_id=<?php echo $booking['booking_id']; ?>" 
                                                    class="bg-teal-600 hover:bg-teal-700 text-gray-100 px-3 py-1 rounded text-sm transition">
                                                    Write Review
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="text-gray-400 text-6xl mb-4">🚗</div>
                            <h3 class="text-xl font-semibold text-gray-300 mb-2">No bookings found</h3>
                            <p class="text-gray-500 mb-6">You haven't made any bookings yet.</p>
                            <a href="dashboard.php" class="bg-teal-600 hover:bg-teal-700 text-gray-100 px-6 py-3 rounded-lg transition">
                                Browse Available Cars
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Help Section -->
            <div class="mt-8 bg-gray-900 p-6 rounded-lg border border-gray-700">
                <h3 class="text-lg font-semibold mb-4">Need Help?</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <h4 class="font-semibold text-teal-400 mb-2">Booking Status</h4>
                        <ul class="text-gray-400 space-y-1">
                            <li>• <span class="text-blue-400">Confirmed</span> - Your booking is active</li>
                            <li>• <span class="text-green-400">Completed</span> - Rental period has ended</li>
                            <li>• <span class="text-red-400">Cancelled</span> - Booking was cancelled</li>
                        </ul>
                        <h4 class="font-semibold text-teal-400 mt-4 mb-2">Cancellation Policy</h4>
                        <ul class="text-gray-400 space-y-1">
                            <li>• Bookings can only be cancelled if payment is not completed</li>
                            <li>• Once payment is completed, cancellation requires support assistance</li>
                            <li>• Contact support for payment-related cancellations</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-teal-400 mb-2">Support</h4>
                        <p class="text-gray-400">If you have any issues with your bookings or need to cancel a paid booking, please contact our support team.</p>
                        <a href="contact.php" class="text-teal-400 hover:text-teal-300 mt-2 inline-block">Contact Support →</a>
                    </div>
                </div>
            </div>
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