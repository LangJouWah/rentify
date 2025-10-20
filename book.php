<?php
session_start();
include 'auth.php';
include 'db_connect.php';

// Retrieve token and verify user
$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user || $user['role'] !== 'customer') {
    $_SESSION['error'] = 'Unauthorized. Please log in as a customer.';
    header('Location: login.php');
    exit;
}

// Get car_id from URL
$car_id = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
if (!$car_id) {
    $_SESSION['error'] = 'Invalid car ID.';
    header('Location: dashboard.php');
    exit;
}

// Fetch car details
$sql = "SELECT brand, model, price FROM Cars WHERE car_id = ? AND status = 'available'";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    $_SESSION['error'] = 'Database error: Unable to prepare query.';
    header('Location: dashboard.php');
    exit;
}
$stmt->bind_param('i', $car_id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();

if (!$car) {
    $_SESSION['error'] = 'Car not found or unavailable.';
    header('Location: dashboard.php');
    exit;
}
$stmt->close();


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    // Server-side date validation
    $today = date('Y-m-d');
    if (!$start_date || !$end_date) {
        $_SESSION['error'] = 'Please select start and end dates.';
    } elseif (strtotime($start_date) < strtotime($today)) {
        $_SESSION['error'] = 'Start date cannot be in the past.';
    } elseif (strtotime($end_date) <= strtotime($start_date)) {
        $_SESSION['error'] = 'End date must be after start date.';
    } else {
        // Check for overlapping bookings
        $sql_check = "SELECT * FROM Bookings WHERE car_id = ? AND (
            (start_date <= ? AND end_date >= ?) OR 
            (start_date <= ? AND end_date >= ?) OR 
            (start_date >= ? AND end_date <= ?)
        ) AND status != 'cancelled'";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param('issssss', $car_id, $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = 'Car not available for selected dates.';
        } else {
            // Calculate total price
            $days = (strtotime($end_date) - strtotime($start_date)) / 86400;
            $total_amount = $car['price'] * $days;

            // Insert booking
            $sql_booking = "INSERT INTO Bookings (user_id, car_id, start_date, end_date, total_amount, status) VALUES (?, ?, ?, ?, ?, 'confirmed')";
            $stmt_booking = $conn->prepare($sql_booking);
            if ($stmt_booking === false) {
                $_SESSION['error'] = 'Database error: ' . $conn->error;
            } else {
                $stmt_booking->bind_param('iissd', $user['user_id'], $car_id, $start_date, $end_date, $total_amount);
                if ($stmt_booking->execute()) {
                    $booking_id = $stmt_booking->insert_id;

                    // Update car status
                    $sql_update = "UPDATE Cars SET status = 'rented' WHERE car_id = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param('i', $car_id);
                    $stmt_update->execute();
                    $stmt_update->close();

                    // Redirect directly to payment
                    header('Location: pay.php?booking_id=' . $booking_id);
                    exit;
                } else {
                    $_SESSION['error'] = 'Error creating booking: ' . $stmt_booking->error;
                }
                $stmt_booking->close();
            }
        }
        $stmt_check->close();
    }
}
?>

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
            today.setHours(0, 0, 0, 0);
            if (startDate < today) {
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
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="space-x-4">
                <a href="dashboard.php" class="hover:underline">Back to Dashboard</a>
                <a href="logout.php" class="hover:underline">Log Out</a>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6">Book <?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h2>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="bg-red-900 border border-red-700 text-red-400 px-4 py-3 rounded relative mb-4" role="alert">';
            echo htmlspecialchars($_SESSION['error']);
            echo '<button type="button" class="absolute top-0 right-0 px-4 py-3 text-red-400" onclick="this.parentElement.remove()">X</button>';
            echo '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="bg-green-900 border border-green-700 text-green-400 px-4 py-3 rounded relative mb-4" role="alert">';
            echo $_SESSION['success']; // Don't use htmlspecialchars here since it contains HTML
            echo '<button type="button" class="absolute top-0 right-0 px-4 py-3 text-green-400" onclick="this.parentElement.remove()">X</button>';
            echo '</div>';
            unset($_SESSION['success']);
        }
        ?>
        <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700 max-w-md mx-auto">
            <form method="POST" onsubmit="return validateForm()">
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2" for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-300 mb-2" for="end_date">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <button type="submit" class="w-full bg-green-500 text-gray-100 p-3 rounded-lg hover:bg-green-600 transition">Book</button>
            </form>
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
