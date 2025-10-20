<?php
session_start();
include 'db_connect.php'; // Ensure database connection

// Check if admin is authenticated via session
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized: No active admin session");
}

// Fetch user details using session admin_id
$stmt = $conn->prepare("SELECT user_id, role FROM users WHERE user_id = ? AND role = 'admin'");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Unauthorized: Invalid admin session");
}

// Get admin_id from Admins table
$sql_admin = "SELECT admin_id FROM Admins WHERE user_id = ?";
$stmt_admin = $conn->prepare($sql_admin);
if (!$stmt_admin) {
    die("Prepare failed: " . $conn->error);
}
$stmt_admin->bind_param("i", $user['user_id']);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$admin_row = $result_admin->fetch_assoc();
$stmt_admin->close();

if (!$admin_row) {
    error_log("No admin record found for user_id " . $user['user_id']);
    die("Error: No admin record found for user_id " . htmlspecialchars($user['user_id']));
}
$admin_id = $admin_row['admin_id'];

// Handle financial report generation
if (isset($_GET['generate_report'])) {
    $report_type = 'financial report';
    $sql_income = "SELECT SUM(total_price) AS total FROM bookings WHERE status = 'completed'";
    $result = $conn->query($sql_income);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    $total = $result->fetch_assoc()['total'] ?? 0;
    $content = "Total income: $" . number_format($total, 2);

    $sql_report = "INSERT INTO reports (admin_id, report_type, content) VALUES (?, ?, ?)";
    $stmt_report = $conn->prepare($sql_report);
    if (!$stmt_report) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt_report->bind_param("iss", $admin_id, $report_type, $content);
    $stmt_report->execute();
    $stmt_report->close();
    $success_message = "Financial report generated.";
}

// Fetch customer reports about cars
$sql_customer_reports = "SELECT cr.report_id, cr.car_id, cr.description, c.brand, c.model, u.name AS customer_name 
                        FROM customerreports cr 
                        JOIN cars c ON cr.car_id = c.car_id 
                        JOIN users u ON cr.customer_id = u.user_id 
                        WHERE cr.status = 'pending'";
$result_customer_reports = $conn->query($sql_customer_reports);
if (!$result_customer_reports) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .table-container {
            max-height: 300px;
            overflow-y: auto;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
        }
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
        }
        .approve-btn {
            background-color: #2b6cb0;
        }
        .remove-btn {
            background-color: #c53030;
        }
        .status-pending { color: #f59e0b; }
        .status-approved { color: #10b981; }
        .status-rejected { color: #ef4444; }
        .status-removed { color: #6b7280; }
    </style>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify Admin Dashboard</h1>
            <div class="space-x-4">
                <a href="logout.php" class="hover:underline">Log Out</a>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
            <h2 class="text-2xl font-semibold mb-4">Admin Dashboard</h2>

            <!-- Success Message -->
            <?php if (isset($success_message)): ?>
                <p class="text-green-400 mb-4"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>

            <!-- Financial Report -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-2">Financial Report</h3>
                <a href="?generate_report=1" class="bg-teal-600 text-gray-100 p-2 rounded-lg hover:bg-teal-700">Generate Financial Report</a>
            </div>

            <!-- Car Approval Section -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-4">Pending Car Approvals</h3>
                <?php
                $sql_pending = "SELECT c.car_id, c.brand, c.model, c.year, c.owner_id, u.name AS owner_name 
                                FROM cars c 
                                JOIN owners o ON c.owner_id = o.owner_id 
                                JOIN users u ON o.user_id = u.user_id 
                                WHERE c.approvalstatus = 'pending'";
                $stmt_pending = $conn->prepare($sql_pending);
                if (!$stmt_pending) {
                    die("Prepare failed: " . $conn->error);
                }
                $stmt_pending->execute();
                $result_pending = $stmt_pending->get_result();
                ?>
                <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700 overflow-x-auto table-container">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="p-3">Car</th>
                                <th class="p-3">Owner</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_pending->num_rows == 0) {
                                echo '<tr><td colspan="3" class="p-3 text-gray-300">No cars pending approval.</td></tr>';
                            } else {
                                while ($row = $result_pending->fetch_assoc()) {
                                    echo '<tr class="border-b border-gray-700">';
                                    echo '<td class="p-3">' . htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']) . ' (' . $row['year'] . ')</td>';
                                    echo '<td class="p-3">' . htmlspecialchars($row['owner_name']) . '</td>';
                                    echo '<td class="p-3">';
                                    echo '<form method="POST" action="approve_car.php" class="inline-block">';
                                    echo '<input type="hidden" name="car_id" value="' . $row['car_id'] . '">';
                                    echo '<input type="hidden" name="action" value="approve">';
                                    echo '<button type="submit" class="action-btn bg-green-500 text-gray-100 px-4 py-2 rounded-lg hover:bg-green-600 transition mr-2">Approve</button>';
                                    echo '</form>';
                                    echo '<form method="POST" action="approve_car.php" class="inline-block">';
                                    echo '<input type="hidden" name="car_id" value="' . $row['car_id'] . '">';
                                    echo '<input type="hidden" name="action" value="reject">';
                                    echo '<button type="submit" class="action-btn bg-red-500 text-gray-100 px-4 py-2 rounded-lg hover:bg-red-600 transition mr-2">Reject</button>';
                                    echo '</form>';
                                    echo '<form method="POST" action="remove_car.php" class="inline-block">';
                                    echo '<input type="hidden" name="car_id" value="' . $row['car_id'] . '">';
                                    echo '<button type="submit" class="action-btn bg-gray-500 text-gray-100 px-4 py-2 rounded-lg hover:bg-gray-600 transition">Remove</button>';
                                    echo '</form>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            }
                            $stmt_pending->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Customer Reports -->
            <div>
                <h3 class="text-xl font-semibold mb-2">Customer Reports</h3>
                <div class="table-container">
                    <table class="w-full border-collapse border border-gray-700">
                        <thead>
                            <tr class="bg-gray-800">
                                <th>Report ID</th>
                                <th>Car ID</th>
                                <th>Car</th>
                                <th>Customer</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_customer_reports->num_rows > 0): ?>
                                <?php while ($report = $result_customer_reports->fetch_assoc()): ?>
                                    <tr class="border-t border-gray-700">
                                        <td><?php echo htmlspecialchars($report['report_id']); ?></td>
                                        <td><?php echo htmlspecialchars($report['car_id']); ?></td>
                                        <td><?php echo htmlspecialchars($report['brand'] . ' ' . $report['model']); ?></td>
                                        <td><?php echo htmlspecialchars($report['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($report['description']); ?></td>
                                        <td>
                                            <form method="POST" action="remove_car.php" class="inline-block">
                                                <input type="hidden" name="car_id" value="<?php echo $report['car_id']; ?>">
                                                <button type="submit" class="action-btn remove-btn bg-red-500 text-gray-100 px-4 py-2 rounded-lg hover:bg-red-600 transition">Remove Car</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No pending customer reports.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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

    <script>
        function approveCar(carId, action) {
            if (confirm(`Are you sure you want to ${action} this car?`)) {
                const form = document.querySelector(`form[action="approve_car.php"] input[value="${carId}"][name="car_id"]`).closest('form');
                form.querySelector('input[name="action"]').value = action;
                form.submit();
            }
        }

        function removeCar(carId) {
            if (confirm('Are you sure you want to remove this car?')) {
                const form = document.querySelector(`form[action="remove_car.php"] input[value="${carId}"][name="car_id"]`).closest('form');
                form.submit();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
