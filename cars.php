<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Cars - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <header class="bg-blue-600 text-white">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="space-x-4">
                <a href="index.html" class="hover:underline">Home</a>
                <a href="logout.php" class="hover:underline">Log Out</a>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6">Available Cars</h2>
        <form class="mb-8 flex flex-col md:flex-row gap-4" method="GET">
            <input type="text" name="brand" placeholder="Brand (e.g., Toyota)" class="p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
            <input type="text" name="model" placeholder="Model (e.g., Corolla)" class="p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
            <button type="submit" class="bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition">Search</button>
        </form>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php
            include 'db_connect.php';
            $brand = $_GET['brand'] ?? '';
            $model = $_GET['model'] ?? '';
            
            $sql = "SELECT * FROM Cars WHERE status = 'available'";
            $params = [];
            $types = "";
            if ($brand) {
                $sql .= " AND brand LIKE ?";
                $params[] = "%$brand%";
                $types .= "s";
            }
            if ($model) {
                $sql .= " AND model LIKE ?";
                $params[] = "%$model%";
                $types .= "s";
            }
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                echo '<p class="text-gray-600">No cars available.</p>';
            } else {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="bg-white p-6 rounded-lg shadow">';
                    echo '<img src="' . ($row['image'] ?: 'uploads/cars/placeholder.jpg') . '" alt="Car Image" class="w-full h-48 object-cover rounded-lg mb-4">';
                    echo '<h3 class="text-xl font-semibold">' . htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']) . '</h3>';
                    echo '<p class="text-gray-600">Price: ₱' . number_format($row['price'], 2) . '/day</p>';
                    echo '<p class="text-gray-600">Type: ' . htmlspecialchars($row['type']) . '</p>';
                    echo '<p class="text-gray-600">Fuel: ' . htmlspecialchars($row['fuel_type']) . '</p>';
                    echo '<a href="book.php?car_id=' . $row['car_id'] . '" class="inline-block mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Book Now</a>';
                    echo '</div>';
                }
            }
            $stmt->close();
            ?>
        </div>
    </main>
    <footer class="bg-gray-800 text-white text-center py-4">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
    </footer>
</body>
</html>