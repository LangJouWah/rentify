<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .loading-spinner {
            display: none;
        }
        .loading .loading-spinner {
            display: inline-block;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <script>
        // Check if user is already logged in by making a request to the server
        function checkSession() {
            fetch('check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (data.loggedIn) {
                        window.location.href = 'dashboard.php';
                    }
                })
                .catch(error => {
                    console.error('Error checking session:', error);
                });
        }

        // Run session check when page loads
        window.onload = function() {
            checkSession();
        };

        // Also check when page becomes visible (user navigates back)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                checkSession();
            }
        });

        function validateForm() {
            const email = document.querySelector('input[name="email"]').value;
            const password = document.querySelector('input[name="password"]').value;
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{6,}$/;

            if (!emailRegex.test(email)) {
                alert('Please enter a valid email (e.g., user@example.com).');
                return false;
            }
            if (!passwordRegex.test(password)) {
                alert('Password must be at least 8 characters, including one uppercase, one lowercase, one number, and one special character.');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            return true;
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function handleForgotPassword() {
            window.location.href = 'forgot_password.php';
        }
    </script>
</head>
<body class="bg-gray-800 font-sans">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-gray-900 p-8 rounded-lg border border-gray-700 w-full max-w-md fade-in">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-100">User Log In</h2>
            <form action="login_process.php" method="POST" onsubmit="return validateForm()">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(bin2hex(random_bytes(32))); ?>">
                <div class="mb-4">
                    <label class="block text-gray-100 mb-2" for="email">Email</label>
                    <input type="email" name="email" id="email" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" placeholder="Email" required>
                </div>
                <div class="mb-4 relative">
                    <label class="block text-gray-100 mb-2" for="password">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" class="w-full p-3 pr-10 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" placeholder="Password" required>
                        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-200" onclick="togglePasswordVisibility()">
                            <i id="toggle-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-6 text-right">
                    <button type="button" class="text-teal-400 hover:text-teal-300 text-sm" onclick="handleForgotPassword()">Forgot Password?</button>
                </div>
                <button type="submit" class="w-full bg-teal-600 text-white p-3 rounded-lg hover:bg-teal-700 transition flex items-center justify-center">
                    <span class="loading-spinner mr-2">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                    Log In
                </button>
            </form>
            <p class="mt-4 text-center text-gray-100">Don't have an account? <a href="signup.php" class="text-teal-400 hover:underline">Sign Up</a></p>
        </div>
    </div>
</body>
</html>
