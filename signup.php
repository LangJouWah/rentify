<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
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
        
        function updatePasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            const requirements = document.getElementById('password-requirements');
            
            let strength = 0;
            let feedback = [];
            
            // Check requirements
            if (password.length >= 8) strength += 25;
            if (/[a-z]/.test(password)) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[@$!%*?&]/.test(password)) strength += 10;
            
            // Update strength bar
            strengthBar.style.width = strength + '%';
            
            // Update strength color and text
            if (strength < 40) {
                strengthBar.className = 'h-1 rounded-full bg-red-500 transition-all duration-300';
                strengthText.textContent = 'Weak';
                strengthText.className = 'text-red-500 text-sm font-medium';
            } else if (strength < 70) {
                strengthBar.className = 'h-1 rounded-full bg-yellow-500 transition-all duration-300';
                strengthText.textContent = 'Medium';
                strengthText.className = 'text-yellow-500 text-sm font-medium';
            } else {
                strengthBar.className = 'h-1 rounded-full bg-green-500 transition-all duration-300';
                strengthText.textContent = 'Strong';
                strengthText.className = 'text-green-500 text-sm font-medium';
            }
            
            // Update requirements list
            const requirementItems = requirements.querySelectorAll('li');
            requirementItems[0].className = password.length >= 8 ? 'text-green-500 text-sm' : 'text-gray-400 text-sm';
            requirementItems[1].className = /[a-z]/.test(password) ? 'text-green-500 text-sm' : 'text-gray-400 text-sm';
            requirementItems[2].className = /[A-Z]/.test(password) ? 'text-green-500 text-sm' : 'text-gray-400 text-sm';
            requirementItems[3].className = /[0-9]/.test(password) ? 'text-green-500 text-sm' : 'text-gray-400 text-sm';
            requirementItems[4].className = /[@$!%*?&]/.test(password) ? 'text-green-500 text-sm' : 'text-gray-400 text-sm';
        }
    </script>
</head>
<body class="bg-gray-800 font-sans">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-gray-900 p-8 rounded-lg border border-gray-700 w-full max-w-md">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-100">User Sign Up</h2>
            <form action="signup_process.php" method="POST" onsubmit="return validateForm()">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(bin2hex(random_bytes(32))); ?>">
                <div class="mb-4">
                    <label class="block text-gray-100 mb-2" for="name">Name</label>
                    <input type="text" name="name" id="name" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" placeholder="Full Name" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-100 mb-2" for="email">Email</label>
                    <input type="email" name="email" id="email" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" placeholder="Email" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-100 mb-2" for="password">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" 
                               class="w-full p-3 pr-10 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" 
                               placeholder="Password" 
                               required
                               oninput="updatePasswordStrength()">
                        <button type="button" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-200"
                                onclick="togglePasswordVisibility()">
                            <i id="toggle-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <!-- Password Strength Meter -->
                    <div class="mt-3">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-gray-400 text-sm">Password strength:</span>
                            <span id="strength-text" class="text-sm font-medium">None</span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-1">
                            <div id="strength-bar" class="h-1 rounded-full bg-gray-500 transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <!-- Password Requirements -->
                    <div id="password-requirements" class="mt-3 space-y-1">
                        <p class="text-gray-400 text-sm mb-2">Password must contain:</p>
                        <ul class="space-y-1">
                            <li id="req-length" class="text-gray-400 text-sm">• At least 8 characters</li>
                            <li id="req-lowercase" class="text-gray-400 text-sm">• One lowercase letter</li>
                            <li id="req-uppercase" class="text-gray-400 text-sm">• One uppercase letter</li>
                            <li id="req-number" class="text-gray-400 text-sm">• One number</li>
                            <li id="req-special" class="text-gray-400 text-sm">• One special character (@$!%*?&)</li>
                        </ul>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-100 mb-2" for="role">Role</label>
                    <select name="role" id="role" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" required>
                        <option value="customer">Customer</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-100 mb-2" for="contact_info">Contact Info</label>
                    <input type="text" name="contact_info" id="contact_info" class="w-full p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600" placeholder="Phone Number">
                </div>
                <button type="submit" class="w-full bg-teal-600 text-white p-3 rounded-lg hover:bg-teal-700 transition">Sign Up</button>
            </form>
            <p class="mt-4 text-center text-gray-100">Already have an account? <a href="login.php" class="text-teal-400 hover:underline">Log In</a></p>
        </div>
    </div>
</body>
</html>
