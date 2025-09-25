<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Car - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function validateForm() {
            const startDate = new Date(document.querySelector('input[name="start_date"]').value);
            const endDate = new Date(document.querySelector('input[name="end_date"]').value);
            const today = new Date();
            if (startDate < today.setHours(0, 0, 0, 0)) {
                alert('Start date cannot be in the past.');
                return false;
            }
            if (endDate <= startDate) {
                alert('End date must be after start date.');
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
                <a href="cars.php" class="hover:underline">Back to Cars</a>
                <a href="logout.php" class="hover:underline">Log Out</a>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6">Book a Car</h2>
        <?php
        include 'auth.php';
        $token = $_GET['token'] ?? '';
        $user = get_user_from_token($token);
        if (!$user || $user['role'] !== 'customer') {
            echo '<p class="text-red-600">Unauthorized. Please <a href="login.html" class="text-blue-600 hover:underline">log in</a> as a customer.</p>';
            exit;
        }
        $car_id = $_GET['car_id'];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $sql_check = "SELECT * FROM Bookings WHERE car_id = ? AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?) OR (start_date >= ? AND end_date <= ?)) AND status != 'cancelled'";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("issssss", $car_id, $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            if ($result->num_rows > 0) {
                echo '<p class="text-red-600">Car not available for selected dates.</p>';
            } else {
                $sql_car = "SELECT price FROM Cars WHERE car_id = ?";
                $stmt_car = $conn->prepare($sql_car);
                $stmt_car->bind_param("i", $car_id);
                $stmt_car->execute();
                $car = $stmt_car->get_result()->fetch_assoc();
                $days = (strtotime($end_date) - strtotime($start_date)) / 86400;
                $total_price = $car['price'] * $days;
                $sql_booking = "INSERT INTO Bookings (user_id, car_id, start_date, end_date, total_price) VALUES (?, ?, ?, ?, ?)";
                $stmt_booking = $conn->prepare($sql_booking);
                $stmt_booking->bind_param("iissd", $user['user_id'], $car_id, $start_date, $end_date, $total_price);
                if ($stmt_booking->execute()) {
                    $booking_id = $stmt_booking->insert_id;
                    $sql_update = "UPDATE Cars SET status = 'rented' WHERE car_id = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("i", $car_id);
                    $stmt_update->execute();
                    echo '<p class="text-green-600">Booking successful! <a href="pay.php?booking_id=' . $booking_id . '&token=' . $token . '" class="text-blue-600 hover:underline">Proceed to Payment</a></p>';
                } else {
                    echo '<p class="text-red-600">Error creating booking.</p>';
                }
            }
        }
        ?>
        <div class="bg-white p-6 rounded-lg shadow max-w-md mx-auto">
            <form method="POST" onsubmit="return validateForm()">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2" for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2" for="end_date">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition">Book</button>
            </form>
        </div>
    </main>
    <footer class="bg-gray-800 text-white text-center py-4">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
    </footer>
</body>
</html>