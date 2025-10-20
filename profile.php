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

// Handle form submission for updating profile
$update_success = false;
$update_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact_info = trim($_POST['contact_info']);
    
    // Basic validation
    if (empty($name) || empty($email)) {
        $update_error = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update_error = "Please enter a valid email address.";
    } else {
        // Check if email is already taken by another user
        $check_email_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt_check = $conn->prepare($check_email_sql);
        $stmt_check->bind_param("si", $email, $user['user_id']);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $update_error = "This email is already registered by another user.";
        } else {
            // Update user information
            $update_sql = "UPDATE users SET name = ?, email = ?, contact_info = ? WHERE user_id = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("sssi", $name, $email, $contact_info, $user['user_id']);
            
            if ($stmt_update->execute()) {
                $update_success = true;
                // Refresh user data
                $user_data['name'] = $name;
                $user_data['email'] = $email;
                $user_data['contact_info'] = $contact_info;
            } else {
                $update_error = "Failed to update profile. Please try again.";
            }
            $stmt_update->close();
        }
        $stmt_check->close();
    }
}

// Get user's booking statistics
$booking_stats_sql = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as upcoming_bookings,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
    FROM bookings 
    WHERE user_id = ?";
$stmt_stats = $conn->prepare($booking_stats_sql);
$stmt_stats->bind_param("i", $user['user_id']);
$stmt_stats->execute();
$booking_stats_result = $stmt_stats->get_result();
$booking_stats = $booking_stats_result->fetch_assoc();
$stmt_stats->close();

// Initialize stats if null
if (!$booking_stats) {
    $booking_stats = [
        'total_bookings' => 0,
        'completed_bookings' => 0,
        'upcoming_bookings' => 0,
        'cancelled_bookings' => 0
    ];
}

// Get recent bookings
$recent_bookings_sql = "SELECT b.*, c.brand, c.model, c.image 
    FROM bookings b 
    JOIN cars c ON b.car_id = c.car_id 
    WHERE b.user_id = ? 
    ORDER BY b.booking_date DESC 
    LIMIT 5";
$stmt_recent = $conn->prepare($recent_bookings_sql);
$stmt_recent->bind_param("i", $user['user_id']);
$stmt_recent->execute();
$recent_bookings = $stmt_recent->get_result();
$stmt_recent->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function toggleProfileDropdown() {
            document.getElementById('profileDropdown').classList.toggle('hidden');
        }
        
        function toggleEditForm() {
            document.getElementById('viewProfile').classList.toggle('hidden');
            document.getElementById('editProfile').classList.toggle('hidden');
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
        <div class="max-w-4xl mx-auto">
            <!-- Success/Error Messages -->
            <?php if ($update_success): ?>
                <div class="bg-green-900 border border-green-700 text-green-100 px-4 py-3 rounded-lg mb-6">
                    Profile updated successfully!
                </div>
            <?php endif; ?>
            
            <?php if ($update_error): ?>
                <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded-lg mb-6">
                    <?php echo htmlspecialchars($update_error); ?>
                </div>
            <?php endif; ?>
            
            <div class="bg-gray-900 rounded-lg shadow border border-gray-700 overflow-hidden">
                <!-- Profile Header -->
                <div class="bg-teal-700 px-6 py-8">
                    <div class="flex items-center">
                        <div class="w-20 h-20 bg-teal-600 rounded-full flex items-center justify-center text-2xl font-bold">
                            <?php echo strtoupper(substr($user_data['name'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div class="ml-6">
                            <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($user_data['name'] ?? 'User'); ?></h1>
                            <p class="text-teal-100">Customer</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <!-- Stats Section -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-gray-800 p-4 rounded-lg text-center">
                            <p class="text-2xl font-bold text-teal-400"><?php echo $booking_stats['total_bookings']; ?></p>
                            <p class="text-gray-300">Total Bookings</p>
                        </div>
                        <div class="bg-gray-800 p-4 rounded-lg text-center">
                            <p class="text-2xl font-bold text-green-400"><?php echo $booking_stats['completed_bookings']; ?></p>
                            <p class="text-gray-300">Completed</p>
                        </div>
                        <div class="bg-gray-800 p-4 rounded-lg text-center">
                            <p class="text-2xl font-bold text-blue-400"><?php echo $booking_stats['upcoming_bookings']; ?></p>
                            <p class="text-gray-300">Upcoming</p>
                        </div>
                        <div class="bg-gray-800 p-4 rounded-lg text-center">
                            <p class="text-2xl font-bold text-red-400"><?php echo $booking_stats['cancelled_bookings']; ?></p>
                            <p class="text-gray-300">Cancelled</p>
                        </div>
                    </div>
                    
                    <!-- Profile Information (View Mode) -->
                    <div id="viewProfile">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold">Profile Information</h2>
                            <button onclick="toggleEditForm()" class="bg-teal-600 hover:bg-teal-700 text-gray-100 px-4 py-2 rounded-lg transition">
                                Edit Profile
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-400 text-sm font-medium mb-1">Full Name</label>
                                <p class="text-gray-100"><?php echo htmlspecialchars($user_data['name'] ?? 'Not set'); ?></p>
                            </div>
                            <div>
                                <label class="block text-gray-400 text-sm font-medium mb-1">Email Address</label>
                                <p class="text-gray-100"><?php echo htmlspecialchars($user_data['email'] ?? 'Not set'); ?></p>
                            </div>
                            <div>
                                <label class="block text-gray-400 text-sm font-medium mb-1">Contact Information</label>
                                <p class="text-gray-100"><?php echo htmlspecialchars($user_data['contact_info'] ?? 'Not provided'); ?></p>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Profile Edit Form -->
                    <div id="editProfile" class="hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold">Edit Profile</h2>
                            <button onclick="toggleEditForm()" class="bg-gray-700 hover:bg-gray-600 text-gray-100 px-4 py-2 rounded-lg transition">
                                Cancel
                            </button>
                        </div>
                        
                        <form method="POST" action="profile.php">
                            <div class="space-y-4">
                                <div>
                                    <label for="name" class="block text-gray-400 text-sm font-medium mb-1">Full Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>" 
                                        class="w-full p-3 border border-gray-700 bg-gray-800 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                                </div>
                                <div>
                                    <label for="email" class="block text-gray-400 text-sm font-medium mb-1">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" 
                                        class="w-full p-3 border border-gray-700 bg-gray-800 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                                </div>
                                <div>
                                    <label for="contact_info" class="block text-gray-400 text-sm font-medium mb-1">Phone Number</label>
                                    <input type="text" id="contact_info" name="contact_info" value="<?php echo htmlspecialchars($user_data['contact_info'] ?? ''); ?>" 
                                        class="w-full p-3 border border-gray-700 bg-gray-800 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" 
                                        placeholder="Enter your phone number">
                                </div>
                                <div class="pt-4">
                                    <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-gray-100 px-6 py-3 rounded-lg transition">
                                        Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Recent Bookings Section -->
                    <div class="mt-12">
                        <h2 class="text-xl font-semibold mb-6">Recent Bookings</h2>
                        
                        <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
                            <div class="space-y-4">
                                <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                    <div class="bg-gray-800 p-4 rounded-lg flex items-center">
                                        <img src="<?php echo $booking['image'] ?: 'Uploads/cars/placeholder.jpg'; ?>" 
                                            alt="Car Image" class="w-16 h-16 object-cover rounded-lg">
                                        <div class="ml-4 flex-1">
                                            <h3 class="font-semibold"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></h3>
                                            <p class="text-gray-400 text-sm">
                                                <?php echo date('M j, Y', strtotime($booking['start_date'])); ?> - 
                                                <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                                            </p>
                                            <p class="text-gray-400 text-sm">Total: ₱<?php echo number_format($booking['total_amount'], 2); ?></p>
                                        </div>
                                        <div>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium 
                                                <?php 
                                                if ($booking['status'] == 'confirmed') echo 'bg-blue-900 text-blue-100';
                                                elseif ($booking['status'] == 'completed') echo 'bg-green-900 text-green-100';
                                                else echo 'bg-red-900 text-red-100';
                                                ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <div class="mt-6 text-center">
                                <a href="booking_history.php" class="text-teal-400 hover:text-teal-300 font-medium">
                                    View All Booking History →
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <p class="text-gray-400">You haven't made any bookings yet.</p>
                                <a href="dashboard.php" class="inline-block mt-4 bg-teal-600 hover:bg-teal-700 text-gray-100 px-6 py-2 rounded-lg transition">
                                    Browse Cars
                                </a>
                            </div>
                        <?php endif; ?>
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
