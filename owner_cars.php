<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cars - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="space-x-4">
                <a href="owner_dashboard.php" class="hover:underline">Dashboard</a>
                <a href="add_car.php" class="hover:underline">Add Car</a>
                <a href="logout.php" class="hover:underline">Log Out</a>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6">My Cars</h2>
        <?php
        include 'auth.php';
        include 'db_connect.php';

        // Retrieve token from cookie
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

        // Handle search
        $brand = $_GET['brand'] ?? '';
        $model = $_GET['model'] ?? '';
        $sql_cars = "SELECT * FROM Cars WHERE owner_id = ?";
        $params = [$owner_id];
        $types = "i";
        if ($brand) {
            $sql_cars .= " AND brand LIKE ?";
            $params[] = "%$brand%";
            $types .= "s";
        }
        if ($model) {
            $sql_cars .= " AND model LIKE ?";
            $params[] = "%$model%";
            $types .= "s";
        }
        $stmt_cars = $conn->prepare($sql_cars);
        $stmt_cars->bind_param($types, ...$params);
        $stmt_cars->execute();
        $result = $stmt_cars->get_result();
        ?>
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Your Cars</h3>
            <form class="mb-6 flex flex-col md:flex-row gap-4" method="GET">
                <input type="text" name="brand" placeholder="Brand (e.g., Toyota)" value="<?php echo htmlspecialchars($brand); ?>" class="p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                <input type="text" name="model" placeholder="Model (e.g., Corolla)" value="<?php echo htmlspecialchars($model); ?>" class="p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                <button type="submit" class="bg-teal-600 text-gray-100 p-3 rounded-lg hover:bg-teal-700 transition">Search</button>
            </form>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php
                if ($result->num_rows == 0) {
                    echo '<p class="text-gray-300">No cars listed. <a href="add_car.php" class="text-teal-400 hover:underline">Add a car</a>.</p>';
                } else {
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">';
                        echo '<img src="' . ($row['image'] ?: 'Uploads/cars/placeholder.jpg') . '" alt="Car Image" class="w-full h-48 object-cover rounded-lg mb-4">';
                        echo '<h4 class="text-lg font-semibold">' . htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']) . '</h4>';
                        echo '<p class="text-gray-300">Status: ' . htmlspecialchars($row['status']) . '</p>';
                        echo '<p class="text-gray-300">Price: ₱' . number_format($row['price'], 2) . '/day</p>';
                        echo '<div class="mt-4 flex space-x-2">';
                        echo '<a href="owner_dashboard.php?edit_car_id=' . $row['car_id'] . '" class="bg-yellow-500 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-600 transition">Edit</a>';
                        echo '<form method="POST" action="owner_dashboard.php" onsubmit="return confirm(\'Are you sure you want to delete this car?\');">';
                        echo '<input type="hidden" name="action" value="delete">';
                        echo '<input type="hidden" name="car_id" value="' . $row['car_id'] . '">';
                        echo '<button type="submit" class="bg-red-500 text-gray-100 px-4 py-2 rounded-lg hover:bg-red-600 transition">Delete</button>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
                $stmt_cars->close();
                ?>
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