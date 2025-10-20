<?php
include 'auth.php';
include 'db_connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Retrieve token from cookie
$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user || $user['role'] !== 'owner') {
    echo '<p class="text-red-400">Unauthorized. Please <a href="login.php" class="text-teal-400 hover:underline">log in</a> as an owner.</p>';
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("Action received: " . ($_POST['action'] ?? 'none'));
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update' && isset($_POST['car_id'])) {
            $car_id = $_POST['car_id'];
            $brand = $_POST['brand'];
            $model = $_POST['model'];
            $year = $_POST['year'];
            $type = $_POST['type'];
            $capacity = $_POST['capacity'];
            $fuel_type = $_POST['fuel_type'];
            $transmission = $_POST['transmission'];
            $price = $_POST['price'];
            $status = $_POST['status'] ?? 'available'; // Add default value
            $location = $_POST['location'];

            $image_path = $_POST['existing_image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024;
                $file_type = $_FILES['image']['type'];
                $file_size = $_FILES['image']['size'];
                $file_tmp = $_FILES['image']['tmp_name'];
                $file_name = uniqid('car_') . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);

                if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                    $upload_dir = 'Uploads/cars/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $destination = $upload_dir . $file_name;
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $image_path = $destination;
                        if ($_POST['existing_image'] != 'Uploads/cars/placeholder.jpg' && file_exists($_POST['existing_image'])) {
                            unlink($_POST['existing_image']);
                        }
                    } else {
                        echo '<p class="text-red-400">Error uploading image.</p>';
                    }
                } else {
                    echo '<p class="text-red-400">Invalid file type or size. Only JPEG, PNG, or GIF up to 2MB allowed.</p>';
                }
            }

            $sql_update = "UPDATE Cars SET brand = ?, model = ?, year = ?, type = ?, capacity = ?, fuel_type = ?, transmission = ?, price = ?, image = ?, status = ?, location = ? WHERE car_id = ? AND owner_id = ?";
$stmt_update = $conn->prepare($sql_update);
// Count the parameters: 13 parameters = 13 characters in bind_param string
$stmt_update->bind_param("ssiisisdsssii", 
    $brand, 
    $model, 
    $year, 
    $type, 
    $capacity, 
    $fuel_type, 
    $transmission, 
    $price, 
    $image_path, 
    $status, 
    $location, 
    $car_id, 
    $owner_id
);
            if ($stmt_update->execute()) {
                echo '<p class="text-green-400">Car updated successfully!</p>';
            } else {
                echo '<p class="text-red-400">Error updating car: ' . $conn->error . '</p>';
            }
            $stmt_update->close();
        } elseif ($_POST['action'] == 'delete' && isset($_POST['car_id'])) {
            $car_id = $_POST['car_id'];
            $sql_check = "SELECT booking_id FROM Bookings WHERE car_id = ? AND status = 'confirmed'";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("i", $car_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo '<p class="text-red-400">Cannot delete car with active bookings.</p>';
                $stmt_check->close();
            } else {
                $stmt_check->close();
                $sql_image = "SELECT image FROM Cars WHERE car_id = ? AND owner_id = ?";
                $stmt_image = $conn->prepare($sql_image);
                $stmt_image->bind_param("ii", $car_id, $owner_id);
                $stmt_image->execute();
                $image_result = $stmt_image->get_result();
                if ($image_result->num_rows > 0) {
                    $image_path = $image_result->fetch_assoc()['image'];
                    if ($image_path != 'Uploads/cars/placeholder.jpg' && file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                $stmt_image->close();

                $sql_delete = "DELETE FROM Cars WHERE car_id = ? AND owner_id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("ii", $car_id, $owner_id);
                if ($stmt_delete->execute()) {
                    echo '<p class="text-green-400">Car deleted successfully!</p>';
                } else {
                    echo '<p class="text-red-400">Error deleting car: ' . $conn->error . '</p>';
                }
                $stmt_delete->close();
            }
        } elseif ($_POST['action'] == 'add_promo' && isset($_POST['car_id'], $_POST['promo_code'], $_POST['discount_percentage'], $_POST['start_date'], $_POST['end_date'])) {
            $car_id = $_POST['car_id'];
            $promo_code = $_POST['promo_code'];
            $discount_percentage = $_POST['discount_percentage'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];

            $sql_promo = "INSERT INTO Promotions (car_id, promo_code, discount_percentage, start_date, end_date) VALUES (?, ?, ?, ?, ?)";
            $stmt_promo = $conn->prepare($sql_promo);
            $stmt_promo->bind_param("isdss", $car_id, $promo_code, $discount_percentage, $start_date, $end_date);
            if ($stmt_promo->execute()) {
                echo '<p class="text-green-400">Promo added successfully!</p>';
            } else {
                echo '<p class="text-red-400">Error adding promo: ' . $conn->error . '</p>';
            }
            $stmt_promo->close();
        } elseif ($_POST['action'] == 'batch_update' && isset($_POST['car_ids'], $_POST['batch_status'])) {
            $car_ids = $_POST['car_ids'] ?? [];
            $status = $_POST['batch_status'];
            foreach ($car_ids as $car_id) {
                $sql_check = "SELECT booking_id FROM Bookings WHERE car_id = ? AND status = 'confirmed'";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("i", $car_id);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows == 0) {
                    $sql_batch = "UPDATE Cars SET status = ? WHERE car_id = ? AND owner_id = ?";
                    $stmt_batch = $conn->prepare($sql_batch);
                    $stmt_batch->bind_param("sii", $status, $car_id, $owner_id);
                    $stmt_batch->execute();
                    $stmt_batch->close();
                }
                $stmt_check->close();
            }
            echo '<p class="text-green-400">Batch update successful!</p>';
        } elseif ($_POST['action'] == 'respond_review' && isset($_POST['review_id'], $_POST['response'])) {
            $review_id = $_POST['review_id'];
            $response = $_POST['response'];
            $sql_response = "UPDATE Reviews SET response = ? WHERE review_id = ? AND car_id IN (SELECT car_id FROM Cars WHERE owner_id = ?)";
            $stmt_response = $conn->prepare($sql_response);
            $stmt_response->bind_param("sii", $response, $review_id, $owner_id);
            if ($stmt_response->execute()) {
                echo '<p class="text-green-400">Review response submitted!</p>';
            } else {
                echo '<p class="text-red-400">Error submitting response: ' . $conn->error . '</p>';
            }
            $stmt_response->close();
        } else {
            echo '<p class="text-red-400">Invalid action or missing required fields.</p>';
        }
    }
}

// Fetch car data for editing
$edit_car = null;
if (isset($_GET['edit_car_id'])) {
    error_log("Edit car ID requested: " . $_GET['edit_car_id']);
    $edit_car_id = $_GET['edit_car_id'];
    $sql_edit = "SELECT * FROM Cars WHERE car_id = ? AND owner_id = ?";
    $stmt_edit = $conn->prepare($sql_edit);
    $stmt_edit->bind_param("ii", $edit_car_id, $owner_id);
    $stmt_edit->execute();
    $edit_result = $stmt_edit->get_result();
    if ($edit_result->num_rows > 0) {
        $edit_car = $edit_result->fetch_assoc();
    } else {
        echo '<p class="text-red-400">Error: Car not found or you do not own this car.</p>';
    }
    $stmt_edit->close();
}

// Overview Panel
$sql_total_cars = "SELECT COUNT(*) as total FROM Cars WHERE owner_id = ?";
$stmt_total_cars = $conn->prepare($sql_total_cars);
$stmt_total_cars->bind_param("i", $owner_id);
$stmt_total_cars->execute();
$total_cars = $stmt_total_cars->get_result()->fetch_assoc()['total'];
$stmt_total_cars->close();

$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
$sql_earnings = "SELECT SUM(total_amount) as earnings FROM Bookings WHERE car_id IN (SELECT car_id FROM Cars WHERE owner_id = ?) AND status = 'completed' AND payment_status = 'completed' AND start_date BETWEEN ? AND ?";
$stmt_earnings = $conn->prepare($sql_earnings);
$stmt_earnings->bind_param("iss", $owner_id, $month_start, $month_end);
$stmt_earnings->execute();
$earnings = $stmt_earnings->get_result()->fetch_assoc()['earnings'] ?? 0;
$stmt_earnings->close();

$sql_upcoming = "SELECT COUNT(*) as upcoming FROM Bookings WHERE car_id IN (SELECT car_id FROM Cars WHERE owner_id = ?) AND status = 'confirmed' AND start_date >= CURDATE()";
$stmt_upcoming = $conn->prepare($sql_upcoming);
$stmt_upcoming->bind_param("i", $owner_id);
$stmt_upcoming->execute();
$upcoming_bookings = $stmt_upcoming->get_result()->fetch_assoc()['upcoming'];
$stmt_upcoming->close();

$sql_occupancy = "SELECT (SUM(DATEDIFF(LEAST(end_date, ?), GREATEST(start_date, ?))) / (COUNT(*) * DATEDIFF(?, ?))) * 100 as rate FROM Bookings b JOIN Cars c ON b.car_id = c.car_id WHERE c.owner_id = ? AND b.status IN ('confirmed', 'completed')";
$stmt_occupancy = $conn->prepare($sql_occupancy);
$stmt_occupancy->bind_param("ssssi", $month_end, $month_start, $month_end, $month_start, $owner_id);
$stmt_occupancy->execute();
$occupancy_rate = $stmt_occupancy->get_result()->fetch_assoc()['rate'] ?? 0;
$stmt_occupancy->close();

$sql_available = "SELECT COUNT(*) as available FROM Cars WHERE owner_id = ? AND status = 'available'";
$stmt_available = $conn->prepare($sql_available);
$stmt_available->bind_param("i", $owner_id);
$stmt_available->execute();
$vehicles_available = $stmt_available->get_result()->fetch_assoc()['available'];
$stmt_available->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="space-x-4">
                <a href="owner_cars.php" class="hover:underline">My Cars</a>
                <a href="add_car.php" class="hover:underline">Add Car</a>
                <a href="bookings.php" class="hover:underline">Bookings</a>
                <a href="owner_dashboard.php#messages" class="hover:underline">Messages</a>
                <a href="logout.php" class="hover:underline">Log Out</a>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6">Owner Dashboard</h2>

        <!-- Overview Panel -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Overview</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="bg-gray-900 p-4 rounded-lg shadow text-center border border-gray-700">
                    <h4 class="text-lg font-semibold">Total Cars Listed</h4>
                    <p class="text-2xl"><?php echo $total_cars; ?></p>
                </div>
                <div class="bg-gray-900 p-4 rounded-lg shadow text-center border border-gray-700">
                    <h4 class="text-lg font-semibold">Current Month’s Earnings</h4>
                    <p class="text-2xl">₱<?php echo number_format($earnings, 2); ?></p>
                </div>
                <div class="bg-gray-900 p-4 rounded-lg shadow text-center border border-gray-700">
                    <h4 class="text-lg font-semibold">Upcoming Bookings</h4>
                    <p class="text-2xl"><?php echo $upcoming_bookings; ?></p>
                </div>
                <div class="bg-gray-900 p-4 rounded-lg shadow text-center border border-gray-700">
                    <h4 class="text-lg font-semibold">Occupancy Rate</h4>
                    <p class="text-2xl"><?php echo number_format($occupancy_rate, 1); ?>%</p>
                </div>
                <div class="bg-gray-900 p-4 rounded-lg shadow text-center border border-gray-700">
                    <h4 class="text-lg font-semibold">Vehicles Available Now</h4>
                    <p class="text-2xl"><?php echo $vehicles_available; ?></p>
                </div>
            </div>
        </div>

        <!-- Fleet Management -->
        <?php
        $brand = $_GET['brand'] ?? '';
        $model = $_GET['model'] ?? '';
        $status_filter = $_GET['status'] ?? '';
        $sql_cars = "SELECT c.*, MIN(b.start_date) as next_booking FROM Cars c LEFT JOIN Bookings b ON c.car_id = b.car_id AND b.status = 'confirmed' AND b.start_date >= CURDATE() WHERE c.owner_id = ?";
        $params = [$owner_id];
        $types = "i";
        if ($brand) {
            $sql_cars .= " AND c.brand LIKE ?";
            $params[] = "%$brand%";
            $types .= "s";
        }
        if ($model) {
            $sql_cars .= " AND c.model LIKE ?";
            $params[] = "%$model%";
            $types .= "s";
        }
        if ($status_filter) {
            $sql_cars .= " AND c.status = ?";
            $params[] = $status_filter;
            $types .= "s";
        }
        $sql_cars .= " GROUP BY c.car_id";
        $stmt_cars = $conn->prepare($sql_cars);
        $stmt_cars->bind_param($types, ...$params);
        $stmt_cars->execute();
        $result_cars = $stmt_cars->get_result();
        ?>
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Fleet Management</h3>
            <?php if (isset($_GET['edit_car_id']) && !$edit_car) {
                echo '<p class="text-red-400">Error: Car not found or you do not own this car.</p>';
            } ?>
            <form class="mb-6 flex flex-col md:flex-row gap-4" method="GET">
                <input type="text" name="brand" placeholder="Brand (e.g., Toyota)" value="<?php echo htmlspecialchars($brand); ?>" class="p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                <input type="text" name="model" placeholder="Model (e.g., Corolla)" value="<?php echo htmlspecialchars($model); ?>" class="p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                <select name="status" class="p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                    <option value="">All Statuses</option>
                    <option value="available" <?php echo $status_filter == 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="rented" <?php echo $status_filter == 'rented' ? 'selected' : ''; ?>>Rented</option>
                    <option value="under maintenance" <?php echo $status_filter == 'under maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                </select>
                <button type="submit" class="bg-teal-600 text-gray-100 p-3 rounded-lg hover:bg-teal-700 transition">Search</button>
            </form>
            <form method="POST" class="mb-6" id="batch-update-form">
                <input type="hidden" name="action" value="batch_update">
                <div class="flex gap-4">
                    <select name="batch_status" class="p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg">
                        <option value="available">Available</option>
                        <option value="under maintenance">Under Maintenance</option>
                    </select>
                    <button type="submit" class="bg-teal-600 text-gray-100 p-3 rounded-lg hover:bg-teal-700 transition">Batch Update Status</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                    <?php
                    if ($result_cars->num_rows == 0) {
                        echo '<p class="text-gray-300">No cars listed. <a href="add_car.php" class="text-teal-400 hover:underline">Add a car</a>.</p>';
                    } else {
                        while ($row = $result_cars->fetch_assoc()) {
                            $sql_maintenance = "SELECT next_service_date FROM Maintenance WHERE car_id = ? AND next_service_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                            $stmt_maintenance = $conn->prepare($sql_maintenance);
                            $stmt_maintenance->bind_param("i", $row['car_id']);
                            $stmt_maintenance->execute();
                            $maintenance_due = $stmt_maintenance->get_result()->num_rows > 0;
                            $stmt_maintenance->close();

                            $sql_promo = "SELECT promo_code, discount_percentage FROM Promotions WHERE car_id = ? AND end_date >= CURDATE() LIMIT 1";
                            $stmt_promo = $conn->prepare($sql_promo);
                            $stmt_promo->bind_param("i", $row['car_id']);
                            $stmt_promo->execute();
                            $promo = $stmt_promo->get_result()->fetch_assoc();
                            $stmt_promo->close();

                            echo '<div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">';
                            echo '<input type="checkbox" name="car_ids[]" value="' . $row['car_id'] . '" class="mb-2">';
                            echo '<img src="' . ($row['image'] ?: 'Uploads/cars/placeholder.jpg') . '" alt="Car Image" class="w-full h-48 object-cover rounded-lg mb-4">';
                            echo '<h4 class="text-lg font-semibold">' . htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']) . ' (' . $row['year'] . ')</h4>';
                            echo '<p class="text-gray-300">Location: ' . htmlspecialchars($row['location']) . '</p>';
                            echo '<p class="text-gray-300">Status: <span class="' . ($row['status'] == 'available' ? 'text-green-400' : ($row['status'] == 'rented' ? 'text-teal-400' : 'text-red-400')) . '">' . htmlspecialchars($row['status']) . '</span></p>';
                            echo '<p class="text-gray-300">Next Booking: ' . ($row['next_booking'] ? htmlspecialchars($row['next_booking']) : 'None') . '</p>';
                            if ($maintenance_due) {
                                echo '<p class="text-red-400">Maintenance Due!</p>';
                            }
                            if ($promo) {
                                echo '<p class="text-green-400">Promo: ' . htmlspecialchars($promo['promo_code']) . ' (' . $promo['discount_percentage'] . '% off)</p>';
                            }
                            echo '<div class="mt-4 flex space-x-2">';
                            echo '<a href="owner_dashboard.php?edit_car_id=' . $row['car_id'] . '&brand=' . urlencode($brand) . '&model=' . urlencode($model) . '&status=' . urlencode($status_filter) . '" class="bg-yellow-500 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-600 transition">Edit</a>';
                            echo '<form method="POST" action="owner_dashboard.php" onsubmit="return confirm(\'Are you sure you want to unlist this car?\');" id="delete-form-' . $row['car_id'] . '">';
                            echo '<input type="hidden" name="action" value="delete">';
                            echo '<input type="hidden" name="car_id" value="' . $row['car_id'] . '">';
                            echo '<button type="submit" class="bg-red-500 text-gray-100 px-4 py-2 rounded-lg hover:bg-red-600 transition">Unlist</button>';
                            echo '</form>';
                            echo '<button type="button" onclick="togglePromoForm(' . $row['car_id'] . ')" class="bg-green-500 text-gray-100 px-4 py-2 rounded-lg hover:bg-green-600 transition">Add Promo</button>';
                            echo '</div>';
                            echo '<form id="promo-form-' . $row['car_id'] . '" method="POST" action="owner_dashboard.php" class="hidden mt-4">';
                            echo '<input type="hidden" name="action" value="add_promo">';
                            echo '<input type="hidden" name="car_id" value="' . $row['car_id'] . '">';
                            echo '<div class="mb-2"><input type="text" name="promo_code" placeholder="Promo Code" class="w-full p-2 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg" required></div>';
                            echo '<div class="mb-2"><input type="number" name="discount_percentage" placeholder="Discount %" class="w-full p-2 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg" step="0.01" required></div>';
                            echo '<div class="mb-2"><input type="date" name="start_date" class="w-full p-2 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg" required></div>';
                            echo '<div class="mb-2"><input type="date" name="end_date" class="w-full p-2 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg" required></div>';
                            echo '<button type="submit" class="bg-teal-600 text-gray-100 px-4 py-2 rounded-lg hover:bg-teal-700 transition">Save Promo</button>';
                            echo '</form>';
                            echo '</div>';
                        }
                    }
                    $stmt_cars->close();
                    ?>
                </div>
            </form>
            <script>
                function togglePromoForm(carId) {
                    const form = document.getElementById(`promo-form-${carId}`);
                    form.classList.toggle('hidden');
                }
            </script>
        </div>

        <!-- Booking Requests / History -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Booking Requests & History</h3>
            <?php
            $sql_bookings = "SELECT b.*, c.brand, c.model, c.location, u.name, u.contact_info FROM Bookings b JOIN Cars c ON b.car_id = c.car_id JOIN Users u ON b.user_id = u.user_id WHERE c.owner_id = ? ORDER BY b.booking_id DESC";
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
                            <th class="p-3">Location</th>
                            <th class="p-3">Dates</th>
                            <th class="p-3">Amount</th>
                            <th class="p-3">Payment</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = $result_bookings->fetch_assoc()) {
                            echo '<tr class="border-b border-gray-700">';
                            echo '<td class="p-3">' . htmlspecialchars($row['name']) . '<br>' . htmlspecialchars($row['contact_info']) . '</td>';
                            echo '<td class="p-3">' . htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']) . '</td>';
                            echo '<td class="p-3">' . htmlspecialchars($row['location']) . '</td>';
                            echo '<td class="p-3">' . htmlspecialchars($row['start_date']) . ' to ' . htmlspecialchars($row['end_date']) . '</td>';
                            echo '<td class="p-3">₱' . number_format($row['total_amount'], 2) . '</td>';
                            echo '<td class="p-3">' . htmlspecialchars($row['payment_status']) . '</td>';
                            echo '<td class="p-3">' . ($row['status'] ?: 'Pending') . '</td>';
                            echo '<td class="p-3">';
                            if ($row['status'] === NULL) {
                                echo '<a href="bookings.php?action=accept&booking_id=' . $row['booking_id'] . '" class="text-green-400 hover:underline mr-2">Accept</a>';
                                echo '<a href="bookings.php?action=reject&booking_id=' . $row['booking_id'] . '" class="text-red-400 hover:underline">Reject</a>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        $stmt_bookings->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Messages Section -->
        <div class="mb-8" id="messages">
            <h3 class="text-xl font-semibold mb-4">Messages</h3>
            <?php
            // Fetch messages for the owner's cars
            // Changed `sent_at` to `created_at` to match common database conventions
            $sql_messages = "
                SELECT m.message_id, m.car_id, m.sender_id, m.receiver_id, m.message AS message_text, m.created_at, m.is_read,
                       c.brand, c.model, u.name AS sender_name
                FROM Messages m
                JOIN Cars c ON m.car_id = c.car_id
                JOIN Users u ON m.sender_id = u.user_id
                WHERE c.owner_id = ? AND m.receiver_id = ?
                ORDER BY m.created_at DESC";
            $stmt_messages = $conn->prepare($sql_messages);
            if ($stmt_messages === false) {
                error_log("Messages query preparation failed: " . $conn->error);
                echo '<p class="text-red-400">Error preparing messages query: ' . htmlspecialchars($conn->error) . '</p>';
            } else {
                $stmt_messages->bind_param("ii", $owner_id, $user['user_id']);
                if ($stmt_messages->execute()) {
                    $result_messages = $stmt_messages->get_result();

                    // Group messages by car_id and sender_id
                    $conversations = [];
                    while ($row = $result_messages->fetch_assoc()) {
                        $conversation_key = $row['car_id'] . '-' . $row['sender_id'];
                        if (!isset($conversations[$conversation_key])) {
                            $conversations[$conversation_key] = [
                                'car_id' => $row['car_id'],
                                'brand' => $row['brand'],
                                'model' => $row['model'],
                                'sender_id' => $row['sender_id'],
                                'sender_name' => $row['sender_name'],
                                'messages' => [],
                                'unread_count' => 0,
                            ];
                        }
                        $conversations[$conversation_key]['messages'][] = $row;
                        if (!$row['is_read'] && $row['sender_id'] != $user['user_id']) {
                            $conversations[$conversation_key]['unread_count']++;
                        }
                    }
                    $stmt_messages->close();
                    ?>
                    <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
                        <?php if (empty($conversations)): ?>
                            <p class="text-gray-300">No messages yet.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($conversations as $conv): ?>
                                    <div class="border-b border-gray-700 pb-4">
                                        <div class="flex justify-between items-center">
                                            <h4 class="text-lg font-semibold">
                                                <?php echo htmlspecialchars($conv['brand'] . ' ' . $conv['model']); ?> - 
                                                <?php echo htmlspecialchars($conv['sender_name']); ?>
                                                <?php if ($conv['unread_count'] > 0): ?>
                                                    <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                                        <?php echo $conv['unread_count']; ?> Unread
                                                    </span>
                                                <?php endif; ?>
                                            </h4>
                                            <a href="chat.php?car_id=<?php echo $conv['car_id']; ?>&customer_id=<?php echo $conv['sender_id']; ?>" 
                                               class="bg-blue-500 text-gray-100 px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                                                View Conversation
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                } else {
                    error_log("Messages query execution failed: " . $stmt_messages->error);
                    echo '<p class="text-red-400">Error executing messages query: ' . htmlspecialchars($stmt_messages->error) . '</p>';
                    $stmt_messages->close();
                }
            }
            ?>
        </div>

        <!-- Earnings & Payments -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Earnings & Payments</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
                    <h4 class="text-lg font-semibold mb-2">Earnings Overview</h4>
                    <p class="text-gray-300">Total Earnings: ₱<?php echo number_format($earnings, 2); ?></p>
                    <p class="text-gray-300">Pending Payouts: ₱<?php
                    $sql_pending = "SELECT SUM(total_amount) as pending FROM Bookings WHERE car_id IN (SELECT car_id FROM Cars WHERE owner_id = ?) AND payment_status = 'pending'";
                    $stmt_pending = $conn->prepare($sql_pending);
                    $stmt_pending->bind_param("i", $owner_id);
                    $stmt_pending->execute();
                    echo number_format($stmt_pending->get_result()->fetch_assoc()['pending'] ?? 0, 2);
                    $stmt_pending->close();
                    ?></p>
                    <a href="export_earnings.php" class="mt-2 inline-block bg-teal-600 text-gray-100 px-4 py-2 rounded-lg hover:bg-teal-700 transition">Export Earnings (CSV)</a>
                </div>
                <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
                    <?php
                    $sql_total_rentals = "SELECT COUNT(*) as total_rentals FROM Bookings WHERE car_id IN (SELECT car_id FROM Cars WHERE owner_id = ?) AND status = 'completed' AND start_date BETWEEN ? AND ?";
                    $stmt_total_rentals = $conn->prepare($sql_total_rentals);
                    $stmt_total_rentals->bind_param("iss", $owner_id, $month_start, $month_end);
                    $stmt_total_rentals->execute();
                    $total_rentals = $stmt_total_rentals->get_result()->fetch_assoc()['total_rentals'] ?? 0;
                    $stmt_total_rentals->close();

                    $sql_maintenance = "SELECT COUNT(*) as maintenance FROM Cars WHERE owner_id = ? AND status = 'under maintenance'";
                    $stmt_maintenance = $conn->prepare($sql_maintenance);
                    $stmt_maintenance->bind_param("i", $owner_id);
                    $stmt_maintenance->execute();
                    $maintenance_cars = $stmt_maintenance->get_result()->fetch_assoc()['maintenance'] ?? 0;
                    $stmt_maintenance->close();
                    ?>
                    <h4 class="text-lg font-semibold mb-4">Performance Snapshot</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-gray-800 p-4 rounded-lg border border-gray-700 text-center">
                            <h5 class="text-base font-semibold mb-2">Total Rentals</h5>
                            <p class="text-2xl text-teal-400"><?php echo $total_rentals; ?></p>
                            <p class="text-sm text-gray-400 mt-1">Sep 2025</p>
                        </div>
                        <div class="bg-gray-800 p-4 rounded-lg border border-gray-700 text-center">
                            <h5 class="text-base font-semibold mb-2">Revenue (₱)</h5>
                            <p class="text-2xl text-teal-400"><?php echo number_format($earnings, 2); ?></p>
                            <p class="text-sm text-gray-400 mt-1">Sep 2025</p>
                        </div>
                        <div class="bg-gray-800 p-4 rounded-lg border border-gray-700 text-center">
                            <h5 class="text-base font-semibold mb-2">Available Cars</h5>
                            <p class="text-2xl text-teal-400"><?php echo $vehicles_available; ?>/<?php echo $total_cars; ?></p>
                            <p class="text-sm text-gray-400 mt-1">Today</p>
                        </div>
                        <div class="bg-gray-800 p-4 rounded-lg border border-gray-700 text-center">
                            <h5 class="text-base font-semibold mb-2">Cars in Maintenance</h5>
                            <p class="text-2xl text-teal-400"><?php echo $maintenance_cars; ?></p>
                            <p class="text-sm text-gray-400 mt-1">Today</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications / Alerts -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Notifications</h3>
            <?php
            $sql_notifications = "SELECT * FROM Notifications WHERE owner_id = ? AND is_read = FALSE ORDER BY notification_id DESC LIMIT 5";
            $stmt_notifications = $conn->prepare($sql_notifications);
            $stmt_notifications->bind_param("i", $owner_id);
            $stmt_notifications->execute();
            $result_notifications = $stmt_notifications->get_result();
            ?>
            <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
                <?php
                if ($result_notifications->num_rows == 0) {
                    echo '<p class="text-gray-300">No new notifications.</p>';
                } else {
                    while ($row = $result_notifications->fetch_assoc()) {
                        echo '<p class="text-gray-300 mb-2">' . htmlspecialchars($row['message']) . ' <span class="text-sm text-gray-400">(' . $row['created_at'] . ')</span></p>';
                    }
                }
                $stmt_notifications->close();
                ?>
            </div>
        </div>

        <!-- Calendar View -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Calendar View</h3>
            <?php
            $current_month = date('m');
            $current_year = date('Y');
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
            $sql_calendar = "SELECT c.car_id, c.brand, c.model, b.start_date, b.end_date, c.status FROM Cars c LEFT JOIN Bookings b ON c.car_id = b.car_id AND b.status IN ('confirmed') AND b.start_date <= ? AND b.end_date >= ? WHERE c.owner_id = ?";
            $stmt_calendar = $conn->prepare($sql_calendar);
            $start = "$current_year-$current_month-01";
            $end = "$current_year-$current_month-$days_in_month";
            $stmt_calendar->bind_param("ssi", $end, $start, $owner_id);
            $stmt_calendar->execute();
            $calendar_data = $stmt_calendar->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_calendar->close();
            ?>
            <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700 overflow-x-auto">
                <table class="w-full text-center">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="p-3">Car</th>
                            <?php for ($day = 1; $day <= $days_in_month; $day++) {
                                echo '<th class="p-3">' . $day . '</th>';
                            } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $cars = [];
                        foreach ($calendar_data as $row) {
                            $car_id = $row['car_id'];
                            if (!isset($cars[$car_id])) {
                                $cars[$car_id] = ['brand' => $row['brand'], 'model' => $row['model'], 'days' => array_fill(1, $days_in_month, $row['status'])];
                            }
                            if ($row['start_date'] && $row['end_date'] && $row['status'] == 'confirmed') {
                                $start_day = max(1, (int)date('d', strtotime($row['start_date'])));
                                $end_day = min($days_in_month, (int)date('d', strtotime($row['end_date'])));
                                for ($day = $start_day; $day <= $end_day; $day++) {
                                    $cars[$car_id]['days'][$day] = 'rented';
                                }
                            }
                        }
                        foreach ($cars as $car_id => $car) {
                            echo '<tr>';
                            echo '<td class="p-3">' . htmlspecialchars($car['brand']) . ' ' . htmlspecialchars($car['model']) . '</td>';
                            foreach ($car['days'] as $day => $status) {
                                $bg = $status == 'rented' ? 'bg-green-400' : ($status == 'under maintenance' ? 'bg-red-400' : 'bg-gray-700');
                                echo '<td class="p-1 ' . $bg . '"></td>';
                            }
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ratings & Reviews -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Ratings & Reviews</h3>
            <?php
            $sql_reviews = "SELECT r.*, c.brand, c.model, u.name FROM Reviews r JOIN Cars c ON r.car_id = c.car_id JOIN Users u ON r.user_id = u.user_id WHERE c.owner_id = ? ORDER BY r.created_at DESC";
            $stmt_reviews = $conn->prepare($sql_reviews);
            $stmt_reviews->bind_param("i", $owner_id);
            $stmt_reviews->execute();
            $result_reviews = $stmt_reviews->get_result();
            ?>
            <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
                <?php
                if ($result_reviews->num_rows == 0) {
                    echo '<p class="text-gray-300">No reviews yet.</p>';
                } else {
                    while ($row = $result_reviews->fetch_assoc()) {
                        echo '<div class="mb-4">';
                        echo '<p class="text-gray-300"><strong>' . htmlspecialchars($row['name']) . '</strong> on ' . htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']) . ': ' . str_repeat('⭐', $row['rating']) . '</p>';
                        echo '<p class="text-gray-300">' . htmlspecialchars($row['comment']) . '</p>';
                        if ($row['response']) {
                            echo '<p class="text-gray-400 italic">Response: ' . htmlspecialchars($row['response']) . '</p>';
                        } else {
                            echo '<form method="POST" action="owner_dashboard.php" class="mt-2">';
                            echo '<input type="hidden" name="action" value="respond_review">';
                            echo '<input type="hidden" name="review_id" value="' . $row['review_id'] . '">';
                            echo '<textarea name="response" class="w-full p-2 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg" placeholder="Respond to review"></textarea>';
                            echo '<button type="submit" class="bg-teal-600 text-gray-100 px-4 py-2 rounded-lg hover:bg-teal-700 transition mt-2">Submit Response</button>';
                            echo '</form>';
                        }
                        echo '</div>';
                    }
                }
                $stmt_reviews->close();
                ?>
            </div>
        </div>

        <!-- Vehicle Maintenance Tracker -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Vehicle Maintenance Tracker</h3>
            <?php
            $sql_maintenance = "SELECT m.*, c.brand, c.model FROM Maintenance m JOIN Cars c ON m.car_id = c.car_id WHERE c.owner_id = ? ORDER BY m.next_service_date";
            $stmt_maintenance = $conn->prepare($sql_maintenance);
            $stmt_maintenance->bind_param("i", $owner_id);
            $stmt_maintenance->execute();
            $result_maintenance = $stmt_maintenance->get_result();
            ?>
            <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700 overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="p-3">Car</th>
                            <th class="p-3">Last Service</th>
                            <th class="p-3">Next Service</th>
                            <th class="p-3">Mileage</th>
                            <th class="p-3">Service Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = $result_maintenance->fetch_assoc()) {
                            echo '<tr class="border-b border-gray-700">';
                            echo '<td class="p-3">' . htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']) . '</td>';
                            echo '<td class="p-3">' . htmlspecialchars($row['last_service_date']) . '</td>';
                            echo '<td class="p-3">' . htmlspecialchars($row['next_service_date']) . '</td>';
                            echo '<td class="p-3">' . htmlspecialchars($row['mileage']) . '</td>';
                            echo '<td class="p-3">' . htmlspecialchars($row['service_type']) . '</td>';
                            echo '</tr>';
                        }
                        $stmt_maintenance->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Promotions & Pricing -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Promotions & Pricing</h3>
            <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
                <p class="text-gray-300">Manage promotions in the Fleet Management section above.</p>
            </div>
        </div>

        <!-- Support & Disputes -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Support & Disputes</h3>
            <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
                <p><a href="mailto:support@rentify.com" class="text-teal-400 hover:underline">Contact Support</a></p>
                <p><a href="https://rentify.com/disputes" class="text-teal-400 hover:underline">Dispute Resolution Center</a></p>
                <p><a href="https://rentify.com/guidelines" class="text-teal-400 hover:underline">Claims & Damages Guidelines</a></p>
            </div>
        </div>

        <!-- Edit Car Modal -->
        <?php if ($edit_car): ?>
        <div id="editCarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700 max-w-md w-full max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold">Edit Car</h3>
                    <a href="owner_dashboard.php?brand=<?php echo urlencode($brand); ?>&model=<?php echo urlencode($model); ?>&status=<?php echo urlencode($status_filter); ?>" class="text-gray-400 hover:text-gray-200 text-2xl">&times;</a>
                </div>
                <form method="POST" enctype="multipart/form-data" action="owner_dashboard.php">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="car_id" value="<?php echo $edit_car['car_id']; ?>">
                    <input type="hidden" name="existing_image" value="<?php echo $edit_car['image']; ?>">
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="brand">Brand</label>
                        <input type="text" name="brand" id="brand" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" value="<?php echo htmlspecialchars($edit_car['brand']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="model">Model</label>
                        <input type="text" name="model" id="model" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" value="<?php echo htmlspecialchars($edit_car['model']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="year">Year</label>
                        <input type="number" name="year" id="year" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" value="<?php echo $edit_car['year']; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="type">Type</label>
                        <select name="type" id="type" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                            <option value="sedan" <?php echo $edit_car['type'] == 'sedan' ? 'selected' : ''; ?>>Sedan</option>
                            <option value="SUV" <?php echo $edit_car['type'] == 'SUV' ? 'selected' : ''; ?>>SUV</option>
                            <option value="convertible" <?php echo $edit_car['type'] == 'convertible' ? 'selected' : ''; ?>>Convertible</option>
                            <option value="other" <?php echo $edit_car['type'] == 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="capacity">Capacity</label>
                        <input type="number" name="capacity" id="capacity" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" value="<?php echo $edit_car['capacity']; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="fuel_type">Fuel Type</label>
                        <select name="fuel_type" id="fuel_type" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                            <option value="petrol" <?php echo $edit_car['fuel_type'] == 'petrol' ? 'selected' : ''; ?>>Petrol</option>
                            <option value="diesel" <?php echo $edit_car['fuel_type'] == 'diesel' ? 'selected' : ''; ?>>Diesel</option>
                            <option value="electric" <?php echo $edit_car['fuel_type'] == 'electric' ? 'selected' : ''; ?>>Electric</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="transmission">Transmission</label>
                        <select name="transmission" id="transmission" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                            <option value="manual" <?php echo $edit_car['transmission'] == 'manual' ? 'selected' : ''; ?>>Manual</option>
                            <option value="automatic" <?php echo $edit_car['transmission'] == 'automatic' ? 'selected' : ''; ?>>Automatic</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="price">Price per Day (₱)</label>
                        <input type="number" name="price" id="price" step="0.01" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" value="<?php echo $edit_car['price']; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="location">Location</label>
                        <input type="text" name="location" id="location" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" value="<?php echo htmlspecialchars($edit_car['location'] ?? 'Manila'); ?>" required>
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2" for="image">Car Image</label>
                        <p class="text-gray-300 mb-2">Current Image: <img src="<?php echo $edit_car['image'] ?: 'Uploads/cars/placeholder.jpg'; ?>" alt="Current Car Image" class="w-32 h-32 object-cover rounded-lg"></p>
                        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg">
                    </div>
                    <button type="submit" class="w-full bg-teal-600 text-gray-100 p-3 rounded-lg hover:bg-teal-700 transition">Update Car</button>
                    <a href="owner_dashboard.php?brand=<?php echo urlencode($brand); ?>&model=<?php echo urlencode($model); ?>&status=<?php echo urlencode($status_filter); ?>" class="block text-center mt-4 text-teal-400 hover:underline">Cancel</a>
                </form>
            </div>
        </div>
        <script>
            // Close modal when clicking outside
            document.getElementById('editCarModal')?.addEventListener('click', function(event) {
                if (event.target === this) {
                    window.location.href = 'owner_dashboard.php?brand=<?php echo urlencode($brand); ?>&model=<?php echo urlencode($model); ?>&status=<?php echo urlencode($status_filter); ?>';
                }
            });
        </script>
        <?php endif; ?>
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
