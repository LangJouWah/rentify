<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Car - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-800 font-sans flex items-center justify-center min-h-screen">
    <div class="w-full">
        <header class="bg-teal-600 text-gray-100">
            <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold">Rentify</h1>
                <div class="space-x-4">
                    <a href="owner_dashboard.php" class="hover:underline">Dashboard</a>
                    <a href="owner_cars.php" class="hover:underline">My Cars</a>
                    <a href="logout.php" class="hover:underline">Log Out</a>
                </div>
            </nav>
        </header>
        <main class="container mx-auto px-4 py-8 flex justify-center">
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

            // Handle form submission
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $brand = $_POST['brand'];
                $model = $_POST['model'];
                $year = $_POST['year'];
                $type = $_POST['type'];
                $capacity = $_POST['capacity'];
                $fuel_type = $_POST['fuel_type'];
                $transmission = $_POST['transmission'];
                $price = $_POST['price'];

                // Handle image upload
                $image_path = 'Uploads/cars/placeholder.jpg';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 2 * 1024 * 1024; // 2MB
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
                        } else {
                            echo '<p class="text-red-400">Error uploading image.</p>';
                        }
                    } else {
                        echo '<p class="text-red-400">Invalid file type or size. Only JPEG, PNG, or GIF up to 2MB allowed.</p>';
                    }
                }

                // Insert car into database
                $sql_add = "INSERT INTO Cars (owner_id, brand, model, year, type, capacity, fuel_type, transmission, price, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')";
                $stmt_add = $conn->prepare($sql_add);
                $stmt_add->bind_param("issisisdss", $owner_id, $brand, $model, $year, $type, $capacity, $fuel_type, $transmission, $price, $image_path);
                if ($stmt_add->execute()) {
                    echo '<p class="text-green-400">Car added successfully! <a href="owner_cars.php" class="text-teal-400 hover:underline">View your cars</a>.</p>';
                } else {
                    echo '<p class="text-red-400">Error adding car: ' . $conn->error . '</p>';
                }
                $stmt_add->close();
            }
            ?>
            <div class="bg-gray-900 p-6 rounded-lg shadow max-w-md w-full border border-gray-700">
                <h3 class="text-xl font-semibold mb-4 text-gray-100">Add New Car</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="brand">Brand</label>
                        <input type="text" name="brand" id="brand" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="model">Model</label>
                        <input type="text" name="model" id="model" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="year">Year</label>
                        <input type="number" name="year" id="year" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="type">Type</label>
                        <select name="type" id="type" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                            <option value="sedan">Sedan</option>
                            <option value="SUV">SUV</option>
                            <option value="convertible">Convertible</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="capacity">Capacity</label>
                        <input type="number" name="capacity" id="capacity" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="fuel_type">Fuel Type</label>
                        <select name="fuel_type" id="fuel_type" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                            <option value="petrol">Petrol</option>
                            <option value="diesel">Diesel</option>
                            <option value="electric">Electric</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="transmission">Transmission</label>
                        <select name="transmission" id="transmission" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                            <option value="manual">Manual</option>
                            <option value="automatic">Automatic</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2" for="price">Price per Day (₱)</label>
                        <input type="number" name="price" id="price" step="0.01" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2" for="image">Car Image</label>
                        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg">
                    </div>
                    <button type="submit" class="w-full bg-teal-600 text-gray-100 p-3 rounded-lg hover:bg-teal-700 transition">Add Car</button>
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
    </div>
</body>
</html>