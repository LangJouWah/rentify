<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function toggleProfileDropdown() {
            document.getElementById('profileDropdown').classList.toggle('hidden');
        }
        
        // Function to show notification badge for unread messages
        function updateMessageBadge(count) {
            const badge = document.getElementById('messageBadge');
            if (count > 0) {
                badge.classList.remove('hidden');
                badge.textContent = count;
            } else {
                badge.classList.add('hidden');
            }
        }
    </script>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="flex items-center space-x-4">
                <form method="GET" class="hidden md:flex items-center">
                    <input type="text" name="search" placeholder="Search by brand, model, or type" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="p-2 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 w-64">
                    <button type="submit" class="bg-teal-600 text-gray-100 p-2 rounded-lg hover:bg-teal-700 transition ml-2">Search</button>
                </form>
                <div class="flex items-center space-x-4">
                    <!-- Messages Link with Badge -->
                    <a href="messages.php" class="relative p-2 text-gray-100 hover:bg-teal-700 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <?php
                        // Get unread message count for customer
                        $unread_count = 0;
                        if (isset($user['user_id'])) {
                            $unread_sql = "SELECT COUNT(*) as unread_count 
                                          FROM messages m 
                                          JOIN conversations c ON m.conversation_id = c.conversation_id 
                                          WHERE c.customer_id = ? AND m.receiver_id = ? AND m.is_read = 0";
                            $stmt_unread = $conn->prepare($unread_sql);
                            $stmt_unread->bind_param("ii", $user['user_id'], $user['user_id']);
                            $stmt_unread->execute();
                            $unread_result = $stmt_unread->get_result();
                            if ($unread_result) {
                                $unread_data = $unread_result->fetch_assoc();
                                $unread_count = $unread_data['unread_count'] ?? 0;
                            }
                            $stmt_unread->close();
                        }
                        ?>
                        <?php if ($unread_count > 0): ?>
                            <span id="messageBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php else: ?>
                            <span id="messageBadge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"></span>
                        <?php endif; ?>
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
                            <a href="logout.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Log Out</a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8">
        <?php
        include 'auth.php';
        include 'db_connect.php';

        // Retrieve token from cookie
        $token = $_COOKIE['jwt_token'] ?? '';
        $user = get_user_from_token($token);
        if (!$user || $user['role'] !== 'customer') {
            echo '<p class="text-red-400">Unauthorized. Please <a href="login.php" class="text-teal-400 hover:underline">log in</a> as a customer.</p>';
            exit;
        }

        // Initialize filter parameters
        $search_query = trim($_GET['search'] ?? '');
        $type = $_GET['type'] ?? '';
        $location = $_GET['location'] ?? '';
        $min_price = $_GET['min_price'] ?? '';
        $max_price = $_GET['max_price'] ?? '';
        $rating = $_GET['rating'] ?? '';
        $fuel_type = $_GET['fuel_type'] ?? '';
        $transmission = $_GET['transmission'] ?? '';

        $sql_where = " WHERE status = 'available'";
        $params = [];
        $param_types = '';

        if ($search_query) {
            $sql_where .= " AND (brand LIKE ? OR model LIKE ? OR type LIKE ?)";
            $search_term = "%$search_query%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $param_types .= 'sss';
        }
        if ($type) {
            $sql_where .= " AND type = ?";
            $params[] = $type;
            $param_types .= 's';
        }
        if ($location) {
            $sql_where .= " AND location LIKE ?";
            $params[] = "%$location%";
            $param_types .= 's';
        }
        if ($min_price !== '') {
            $sql_where .= " AND price >= ?";
            $params[] = $min_price;
            $param_types .= 'd';
        }
        if ($max_price !== '') {
            $sql_where .= " AND price <= ?";
            $params[] = $max_price;
            $param_types .= 'd';
        }
        if ($rating) {
            $sql_where .= " AND (SELECT COALESCE(AVG(rating), 0) FROM Reviews WHERE Reviews.car_id = Cars.car_id) >= ?";
            $params[] = $rating;
            $param_types .= 'i';
        }
        if ($fuel_type) {
            $sql_where .= " AND fuel_type = ?";
            $params[] = $fuel_type;
            $param_types .= 's';
        }
        if ($transmission) {
            $sql_where .= " AND transmission = ?";
            $params[] = $transmission;
            $param_types .= 's';
        }

        // Query to fetch available cars with average ratings
        $sql_cars = "SELECT Cars.*, COALESCE(AVG(Reviews.rating), 0) as avg_rating 
                     FROM Cars 
                     LEFT JOIN Reviews ON Cars.car_id = Reviews.car_id" . $sql_where . 
                     " GROUP BY Cars.car_id";
        $stmt_cars = $conn->prepare($sql_cars);
        if ($stmt_cars === false) {
            error_log("Car query preparation failed: " . $conn->error);
            echo '<p class="text-red-400">Error loading cars. Please try again.</p>';
        } else {
            if (!empty($params)) {
                $stmt_cars->bind_param($param_types, ...$params);
            }
            $stmt_cars->execute();
            $result_cars = $stmt_cars->get_result();
        }

        // Query for trending cars
        $sql_trending = "SELECT Cars.*, COALESCE(AVG(Reviews.rating), 0) as avg_rating, COUNT(Bookings.booking_id) as booking_count 
                         FROM Cars 
                         LEFT JOIN Reviews ON Cars.car_id = Reviews.car_id 
                         LEFT JOIN Bookings ON Cars.car_id = Bookings.car_id 
                         WHERE Cars.status = 'available' 
                         GROUP BY Cars.car_id 
                         ORDER BY booking_count DESC, avg_rating DESC 
                         LIMIT 3";
        $stmt_trending = $conn->prepare($sql_trending);
        if ($stmt_trending === false) {
            error_log("Trending query preparation failed: " . $conn->error);
            $result_trending = false;
        } else {
            $stmt_trending->execute();
            $result_trending = $stmt_trending->get_result();
        }

        // Query for promotions - FIXED: Added location field
        $sql_promos = "SELECT p.*, c.brand, c.model, c.location 
                       FROM Promotions p 
                       JOIN Cars c ON p.car_id = c.car_id 
                       WHERE p.end_date >= CURDATE() 
                       LIMIT 3";
        $stmt_promos = $conn->prepare($sql_promos);
        if ($stmt_promos === false) {
            error_log("Promotions query preparation failed: " . $conn->error);
            $result_promos = false;
        } else {
            $stmt_promos->execute();
            $result_promos = $stmt_promos->get_result();
        }
        ?>
        
        <!-- Quick Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-900 p-6 rounded-lg border border-gray-700 text-center">
                <div class="text-2xl font-bold text-teal-400">
                    <?php 
                    $total_cars_sql = "SELECT COUNT(*) as total FROM cars WHERE status = 'available'";
                    $result = $conn->query($total_cars_sql);
                    echo $result->fetch_assoc()['total'] ?? 0;
                    ?>
                </div>
                <div class="text-gray-400">Available Cars</div>
            </div>
            <div class="bg-gray-900 p-6 rounded-lg border border-gray-700 text-center">
                <div class="text-2xl font-bold text-blue-400"><?php echo $unread_count; ?></div>
                <div class="text-gray-400">Unread Messages</div>
            </div>
            <div class="bg-gray-900 p-6 rounded-lg border border-gray-700 text-center">
                <?php
                $active_bookings_sql = "SELECT COUNT(*) as active FROM bookings WHERE user_id = ? AND status = 'confirmed'";
                $stmt_active = $conn->prepare($active_bookings_sql);
                $stmt_active->bind_param("i", $user['user_id']);
                $stmt_active->execute();
                $active_result = $stmt_active->get_result();
                $active_bookings = $active_result->fetch_assoc()['active'] ?? 0;
                $stmt_active->close();
                ?>
                <div class="text-2xl font-bold text-green-400"><?php echo $active_bookings; ?></div>
                <div class="text-gray-400">Active Bookings</div>
            </div>
            <div class="bg-gray-900 p-6 rounded-lg border border-gray-700 text-center">
                <?php
                $total_bookings_sql = "SELECT COUNT(*) as total FROM bookings WHERE user_id = ?";
                $stmt_total = $conn->prepare($total_bookings_sql);
                $stmt_total->bind_param("i", $user['user_id']);
                $stmt_total->execute();
                $total_result = $stmt_total->get_result();
                $total_bookings = $total_result->fetch_assoc()['total'] ?? 0;
                $stmt_total->close();
                ?>
                <div class="text-2xl font-bold text-purple-400"><?php echo $total_bookings; ?></div>
                <div class="text-gray-400">Total Bookings</div>
            </div>
        </div>

        <!-- Promotions Banner -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Special Offers</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php
                if (!$result_promos || $result_promos->num_rows == 0) {
                    echo '<p class="text-gray-300">No current promotions.</p>';
                } else {
                    while ($row = $result_promos->fetch_assoc()) {
                        echo '<div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">';
                        echo '<h4 class="text-lg font-semibold">' . htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']) . '</h4>';
                        echo '<p class="text-gray-300">Location: ' . htmlspecialchars($row['location']) . '</p>';
                        echo '<p class="text-gray-300">Promo Code: ' . htmlspecialchars($row['promo_code']) . '</p>';
                        echo '<p class="text-gray-300">Discount: ' . $row['discount_percentage'] . '% off</p>';
                        echo '<p class="text-gray-300">Valid until: ' . htmlspecialchars($row['end_date']) . '</p>';
                        echo '</div>';
                    }
                    $stmt_promos->close();
                }
                ?>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            <!-- Filter Sidebar -->
            <aside class="md:w-1/4">
                <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
                    <h3 class="text-xl font-semibold mb-4">Filters</h3>
                    <form method="GET">
                        <div class="mb-4">
                            <label class="block text-gray-300 mb-2" for="type">Car Type</label>
                            <select name="type" id="type" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                                <option value="">All Types</option>
                                <option value="sedan" <?php echo $type == 'sedan' ? 'selected' : ''; ?>>Sedan</option>
                                <option value="SUV" <?php echo $type == 'SUV' ? 'selected' : ''; ?>>SUV</option>
                                <option value="convertible" <?php echo $type == 'convertible' ? 'selected' : ''; ?>>Convertible</option>
                                <option value="other" <?php echo $type == 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-300 mb-2" for="location">Location</label>
                            <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($location); ?>" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" placeholder="e.g., Manila">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-300 mb-2">Price Range (per day)</label>
                            <input type="number" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>" placeholder="Min Price" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 mb-2">
                            <input type="number" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>" placeholder="Max Price" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-300 mb-2" for="rating">Minimum Rating</label>
                            <select name="rating" id="rating" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                                <option value="">Any Rating</option>
                                <option value="1" <?php echo $rating == '1' ? 'selected' : ''; ?>>1 Star</option>
                                <option value="2" <?php echo $rating == '2' ? 'selected' : ''; ?>>2 Stars</option>
                                <option value="3" <?php echo $rating == '3' ? 'selected' : ''; ?>>3 Stars</option>
                                <option value="4" <?php echo $rating == '4' ? 'selected' : ''; ?>>4 Stars</option>
                                <option value="5" <?php echo $rating == '5' ? 'selected' : ''; ?>>5 Stars</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-300 mb-2" for="fuel_type">Fuel Type</label>
                            <select name="fuel_type" id="fuel_type" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                                <option value="">All Fuel Types</option>
                                <option value="petrol" <?php echo $fuel_type == 'petrol' ? 'selected' : ''; ?>>Petrol</option>
                                <option value="diesel" <?php echo $fuel_type == 'diesel' ? 'selected' : ''; ?>>Diesel</option>
                                <option value="electric" <?php echo $fuel_type == 'electric' ? 'selected' : ''; ?>>Electric</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-300 mb-2" for="transmission">Transmission</label>
                            <select name="transmission" id="transmission" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                                <option value="">All Transmissions</option>
                                <option value="manual" <?php echo $transmission == 'manual' ? 'selected' : ''; ?>>Manual</option>
                                <option value="automatic" <?php echo $transmission == 'automatic' ? 'selected' : ''; ?>>Automatic</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-teal-600 text-gray-100 p-3 rounded-lg hover:bg-teal-700 transition">Apply Filters</button>
                    </form>
                </div>
                <!-- Quick Links -->
                <div class="mt-6 bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
                    <h3 class="text-xl font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="messages.php" class="text-teal-400 hover:underline flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            Messages
                            <?php if ($unread_count > 0): ?>
                                <span class="ml-2 bg-red-500 text-white text-xs rounded-full px-2 py-1"><?php echo $unread_count; ?> new</span>
                            <?php endif; ?>
                        </a></li>
                        <li><a href="booking_history.php" class="text-teal-400 hover:underline">Booking History</a></li>
                        <li><a href="help.php" class="text-teal-400 hover:underline">Help/FAQ</a></li>
                    </ul>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="md:w-3/4">
                <!-- Trending Cars -->
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-4">Trending Cars</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php
                        if (!$result_trending || $result_trending->num_rows == 0) {
                            echo '<p class="text-gray-300">No trending cars available.</p>';
                        } else {
                            while ($row = $result_trending->fetch_assoc()) {
                                echo '<div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">';
                                echo '<img src="' . ($row['image'] ?: 'Uploads/cars/placeholder.jpg') . '" alt="Car Image" class="w-full h-48 object-cover rounded-lg mb-4">';
                                echo '<h4 class="text-lg font-semibold">' . htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']) . '</h4>';
                                echo '<p class="text-gray-300">Year: ' . htmlspecialchars($row['year']) . '</p>';
                                echo '<p class="text-gray-300">Type: ' . htmlspecialchars($row['type']) . '</p>';
                                echo '<p class="text-gray-300">Location: ' . htmlspecialchars($row['location']) . '</p>';
                                echo '<p class="text-gray-300">Price: ₱' . number_format($row['price'], 2) . '/day</p>';
                                echo '<p class="text-gray-300">Rating: ' . number_format($row['avg_rating'], 1) . ' (' . $row['booking_count'] . ' bookings)</p>';
                                echo '<div class="mt-4 flex space-x-2">';
                                echo '<a href="car_details.php?car_id=' . $row['car_id'] . '" class="bg-teal-600 text-gray-100 p-2 rounded-lg hover:bg-teal-700 transition">View Details</a>';
                                echo '<a href="book.php?car_id=' . $row['car_id'] . '" class="bg-green-500 text-gray-100 p-2 rounded-lg hover:bg-green-600 transition">Reserve</a>';
                                echo '<a href="chat.php?car_id=' . $row['car_id'] . '" class="bg-blue-500 text-gray-100 p-2 rounded-lg hover:bg-blue-600 transition">Chat with Owner</a>';
                                echo '</div>';
                                echo '</div>';
                            }
                            $stmt_trending->close();
                        }
                        ?>
                    </div>
                </div>

                <!-- Car Listings -->
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-4">Available Cars</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php
                        if (!$result_cars || $result_cars->num_rows == 0) {
                            echo '<p class="text-gray-300">No cars available.</p>';
                        } else {
                            while ($row = $result_cars->fetch_assoc()) {
                                echo '<div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">';
                                echo '<img src="' . ($row['image'] ?: 'Uploads/cars/placeholder.jpg') . '" alt="Car Image" class="w-full h-48 object-cover rounded-lg mb-4">';
                                echo '<h4 class="text-lg font-semibold">' . htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']) . '</h4>';
                                echo '<p class="text-gray-300">Year: ' . htmlspecialchars($row['year']) . '</p>';
                                echo '<p class="text-gray-300">Type: ' . htmlspecialchars($row['type']) . '</p>';
                                echo '<p class="text-gray-300">Location: ' . htmlspecialchars($row['location']) . '</p>';
                                echo '<p class="text-gray-300">Price: ₱' . number_format($row['price'], 2) . '/day</p>';
                                echo '<p class="text-gray-300">Rating: ' . number_format($row['avg_rating'], 1) . ' ★</p>';
                                echo '<div class="mt-4 flex space-x-2">';
                                echo '<a href="car_details.php?car_id=' . $row['car_id'] . '" class="bg-teal-600 text-gray-100 p-2 rounded-lg hover:bg-teal-700 transition">View Details</a>';
                                echo '<a href="book.php?car_id=' . $row['car_id'] . '" class="bg-green-500 text-gray-100 p-2 rounded-lg hover:bg-green-600 transition">Reserve</a>';
                                echo '<a href="chat.php?car_id=' . $row['car_id'] . '" class="bg-blue-500 text-gray-100 p-2 rounded-lg hover:bg-blue-600 transition">Chat with Owner</a>';
                                echo '</div>';
                                echo '</div>';
                            }
                            $stmt_cars->close();
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="bg-gray-900 text-gray-100 text-center py-4">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
        <div class="mt-2">
            <a href="https://rentify.com/terms" class="text-gray-400 hover:text-gray-200 mx-2">Terms of Service</a>
            <a href="https://rentify.com/privacy" class="text-gray-400 hover:text-gray-200 mx-2">Privacy Policy</a>
        </div>
    </footer>
</body>
</html>
