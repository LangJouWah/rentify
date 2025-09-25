<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="space-x-4">
                <a href="owner_dashboard.php" class="hover:underline">Dashboard</a>
                <a href="owner_cars.php" class="hover:underline">My Cars</a>
                <a href="add_car.php" class="hover:underline">Add Car</a>
                <a href="logout.php" class="hover:underline">Log Out</a>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6">Booking Requests</h2>
        <?php
        include 'auth.php';
        include 'db_connect.php';

        // Authenticate user
        $token = $_COOKIE['jwt_token'] ?? '';
        $user = get_user_from_token($token);
        if (!$user || $user['role'] !== 'owner') {
            echo '<p class="text-red-400">Unauthorized. Please <a href="login.html" class="text-teal-400 hover:underline">log in</a> as an owner.</p>';
            exit;
        }

        // Get owner_id
        $sql_owner = "SELECT owner_id FROM Owners WHERE user_id = ?";
        $stmt_owner = $conn->prepare($sql_owner);
        $stmt_owner->bind_param("i", $user['user_id']);
        $stmt_owner->execute();
        $owner_result = $stmt_owner->get_result();
        if ($owner_result->num_rows === 0) {
            echo '<p class="text-red-400">Owner profile not found. Please contact support.</p>';
            exit;
        }
        $owner_id = $owner_result->fetch_assoc()['owner_id'];
        $stmt_owner->close();

        // Handle booking actions (accept/reject)
        if (isset($_GET['action']) && isset($_GET['booking_id'])) {
            $booking_id = $_GET['booking_id'];
            $action = $_GET['action'];
            $sql_booking = "SELECT car_id FROM Bookings WHERE booking_id = ? AND car_id IN (SELECT car_id FROM Cars WHERE owner_id = ?) AND status IS NULL";
            $stmt_booking = $conn->prepare($sql_booking);
            $stmt_booking->bind_param("ii", $booking_id, $owner_id);
            $stmt_booking->execute();
            $booking_result = $stmt_booking->get_result();
            if ($booking_result->num_rows > 0) {
                $car_id = $booking_result->fetch_assoc()['car_id'];
                if ($action == 'accept') {
                    // Update booking status to confirmed
                    $sql_update = "UPDATE Bookings SET status = 'confirmed' WHERE booking_id = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("i", $booking_id);
                    if ($stmt_update->execute()) {
                        // Update car status to rented
                        $sql_car_status = "UPDATE Cars SET status = 'rented' WHERE car_id = ?";
                        $stmt_car_status = $conn->prepare($sql_car_status);
                        $stmt_car_status->bind_param("i", $car_id);
                        $stmt_car_status->execute();
                        $stmt_car_status->close();
                        echo '<p class="text-green-400">Booking accepted successfully!</p>';
                    } else {
                        echo '<p class="text-red-400">Error accepting booking: ' . $conn->error . '</p>';
                    }
                    $stmt_update->close();
                } elseif ($action == 'reject') {
                    // Update booking status to failed
                    $sql_update = "UPDATE Bookings SET status = 'failed' WHERE booking_id = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("i", $booking_id);
                    if ($stmt_update->execute()) {
                        echo '<p class="text-green-400">Booking rejected successfully!</p>';
                    } else {
                        echo '<p class="text-red-400">Error rejecting booking: ' . $conn->error . '</p>';
                    }
                    $stmt_update->close();
                }
            } else {
                echo '<p class="text-red-400">Invalid booking or already processed.</p>';
            }
            $stmt_booking->close();
        }

        // Fetch all bookings for the owner
        $sql_bookings = "SELECT b.*, c.brand, c.model, u.name, u.contact_info 
                         FROM Bookings b 
                         JOIN Cars c ON b.car_id = c.car_id 
                         JOIN Users u ON b.user_id = u.user_id 
                         WHERE c.owner_id = ? 
                         ORDER BY b.booking_id DESC";
        $stmt_bookings = $conn->prepare($sql_bookings);
        $stmt_bookings->bind_param("i", $owner_id);
        $stmt_bookings->execute();
        $result_bookings = $stmt_bookings->get_result();
        ?>
        <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700 overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="p-3">Customer</th>
                        <th class="p-3">Car</th>
                        <th class="p-3">Dates</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Payment Status</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_bookings->num_rows == 0) {
                        echo '<tr><td colspan="7" class="p-3 text-gray-300 text-center">No bookings found.</td></tr>';
                    } else {
                        while ($row = $result_bookings->fetch_assoc()) {
                            echo '<tr class="border-b border-gray-700">';
                            echo '<td class="p-3">' . htmlspecialchars($row['name']) . '<br><span class="text-gray-400">' . htmlspecialchars($row['contact_info']) . '</span></td>';
                            echo '<td class="p-3">' . htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']) . '</td>';
                            echo '<td class="p-3">' . htmlspecialchars($row['start_date']) . ' to ' . htmlspecialchars($row['end_date']) . '</td>';
                            echo '<td class="p-3">₱' . number_format($row['total_amount'], 2) . '</td>';
                            echo '<td class="p-3">' . htmlspecialchars($row['payment_status']) . '</td>';
                            echo '<td class="p-3">' . ($row['status'] ?: 'Pending') . '</td>';
                            echo '<td class="p-3">';
                            if ($row['status'] === NULL) {
                                echo '<a href="bookings.php?action=accept&booking_id=' . $row['booking_id'] . '" class="text-green-400 hover:underline mr-2">Accept</a>';
                                echo '<a href="bookings.php?action=reject&booking_id=' . $row['booking_id'] . '" class="text-red-400 hover:underline">Reject</a>';
                            } else {
                                echo '<span class="text-gray-400">No actions</span>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                    $stmt_bookings->close();
                    ?>
                </tbody>
            </table>
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