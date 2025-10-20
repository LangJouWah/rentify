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
$car_id = $_GET['car_id'] ?? '';
if (!$car_id) {
    $_SESSION['error'] = 'Invalid car ID.';
    header('Location: dashboard.php');
    exit;
}

// Fetch car details - REMOVED the status filter
$sql = "SELECT Cars.*, COALESCE(AVG(Reviews.rating), 0) as avg_rating 
        FROM Cars 
        LEFT JOIN Reviews ON Cars.car_id = Reviews.car_id 
        WHERE Cars.car_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $car_id);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();

if (!$car) {
    $_SESSION['error'] = 'Car not found.';
    header('Location: dashboard.php');
    exit;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Details - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <a href="dashboard.php" class="text-gray-100 hover:underline">Back to Dashboard</a>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8">
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
            echo htmlspecialchars($_SESSION['success']);
            echo '<button type="button" class="absolute top-0 right-0 px-4 py-3 text-green-400" onclick="this.parentElement.remove()">X</button>';
            echo '</div>';
            unset($_SESSION['success']);
        }
        ?>
        
        <?php if ($car['status'] !== 'available'): ?>
            <div class="bg-yellow-900 border border-yellow-700 text-yellow-400 px-4 py-3 rounded relative mb-4" role="alert">
                This car is currently <?php echo htmlspecialchars($car['status']); ?> and cannot be booked at the moment.
            </div>
        <?php endif; ?>

        <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
            <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h2>
            <img src="<?php echo $car['image'] ?: 'Uploads/cars/placeholder.jpg'; ?>" alt="Car Image" class="w-full h-64 object-cover rounded-lg mb-4">
            <p class="text-gray-300">Year: <?php echo htmlspecialchars($car['year']); ?></p>
            <p class="text-gray-300">Type: <?php echo htmlspecialchars($car['type']); ?></p>
            <p class="text-gray-300">Location: <?php echo htmlspecialchars($car['location']); ?></p>
            <p class="text-gray-300">Price: ₱<?php echo number_format($car['price'], 2); ?>/day</p>
            <p class="text-gray-300">Fuel Type: <?php echo htmlspecialchars($car['fuel_type']); ?></p>
            <p class="text-gray-300">Transmission: <?php echo htmlspecialchars($car['transmission']); ?></p>
            <p class="text-gray-300">Rating: <?php echo number_format($car['avg_rating'], 1); ?> ★</p>
            <p class="text-gray-300">Status: <span class="<?php echo $car['status'] === 'available' ? 'text-green-400' : 'text-yellow-400'; ?>"><?php echo ucfirst($car['status']); ?></span></p>
            
            <?php if ($car['status'] === 'available'): ?>
                <a href="book.php?car_id=<?php echo $car['car_id']; ?>" class="mt-4 inline-block bg-green-500 text-gray-100 p-3 rounded-lg hover:bg-green-600 transition">Reserve Now</a>
            <?php else: ?>
                <button class="mt-4 inline-block bg-gray-500 text-gray-300 p-3 rounded-lg cursor-not-allowed" disabled>Currently Unavailable</button>
            <?php endif; ?>
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
