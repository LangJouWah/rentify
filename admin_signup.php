<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign Up - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function validateForm() {
            const email = document.querySelector('input[name="email"]').value;
            const password = document.querySelector('input[name="password"]').value;
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email (e.g., admin@example.com).');
                return false;
            }
            if (!passwordRegex.test(password)) {
                alert('Password must be at least 8 characters, including one uppercase, one lowercase, one number, and one special character.');
                return false;
            }
            return true;
        }
    </script>
</head>
<body class="bg-gray-800 font-sans">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-gray-900 p-8 rounded-lg border border-gray-700 w-full max-w-md">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-100">Admin Sign Up</h2>
            <form action="admin_signup_process.php" method="POST" onsubmit="return validateForm()">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(bin2hex(random_bytes(32))); ?>">
                <input type="hidden" name="role" value="admin">
                <div class="mb-4">
                    <label class="block text-gray-100 mb-2" for="name">Name</label>
                    <input type="text" name="name" id="name" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600" placeholder="Full Name" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-100 mb-2" for="email">Email</label>
                    <input type="email" name="email" id="email" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600" placeholder="Admin Email" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-100 mb-2" for="password">Password</label>
                    <input type="password" name="password" id="password" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600" placeholder="Password" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-100 mb-2" for="contact_info">Contact Info</label>
                    <input type="text" name="contact_info" id="contact_info" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600" placeholder="Phone Number">
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white p-3 rounded-lg hover:bg-indigo-700 transition">Sign Up</button>
            </form>
            <p class="mt-4 text-center text-gray-100">Already have an account? <a href="admin_login.php" class="text-indigo-400 hover:underline">Log In</a></p>
            
        </div>
    </div>
</body>
</html>