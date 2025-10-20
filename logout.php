<?php
// Start session and clear the JWT token cookie
session_start();

// Clear the JWT token cookie by setting expiration to past
setcookie('jwt_token', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'domain' => '',
    'secure' => false, // Set to true in production with HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Clear any session data
session_unset();
session_destroy();

// Add security headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-800 font-sans">
    <header class="bg-gray-900 border-b border-gray-700">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-teal-400">Rentify</h1>
            <div class="space-x-4">
                <a href="index.php" class="text-gray-300 hover:text-teal-400 transition">Home</a>
            </div>
        </nav>
    </header>
    
    <main class="container mx-auto px-4 py-8 text-center min-h-screen flex items-center justify-center">
        <div class="bg-gray-900 p-8 rounded-lg border border-gray-700 w-full max-w-md">
            <div class="mb-6">
                <i class="fas fa-sign-out-alt text-teal-400 text-5xl mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-100 mb-2">Logging Out</h2>
            </div>
            
            <!-- Loading Animation -->
            <div class="flex justify-center mb-6">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-teal-400"></div>
            </div>
            
            <p class="text-teal-400 text-lg mb-4">
                <i class="fas fa-check-circle mr-2"></i>You have been logged out successfully
            </p>
            
            <p class="text-gray-400 mb-6">Redirecting to home page...</p>
            
            <div class="bg-gray-800 p-4 rounded-lg mb-6">
                <p class="text-sm text-gray-300">
                    <i class="fas fa-shield-alt text-teal-400 mr-2"></i>
                    Your session has been securely terminated
                </p>
            </div>
            
            <!-- Manual redirect option -->
            <div class="mt-4">
                <a href="index.php" class="inline-block bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition">
                    <i class="fas fa-home mr-2"></i>Go Home Now
                </a>
            </div>
        </div>
    </main>
    
    <footer class="bg-gray-900 border-t border-gray-700 text-gray-400 text-center py-4">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
    </footer>

    <script>
        // Enhanced redirect with smooth animation and security checks
        document.addEventListener('DOMContentLoaded', function() {
            // Clear any client-side storage
            localStorage.removeItem('rentify_session');
            sessionStorage.clear();
            
            // Force clear cookies (additional measure)
            document.cookie = "jwt_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            
            // Redirect after delay
            setTimeout(() => {
                // Force a hard redirect to prevent caching
                window.location.replace('index.php');
            }, 2000);
            
            // Add pulsing animation to the checkmark
            const checkIcon = document.querySelector('.fa-check-circle');
            if (checkIcon) {
                setInterval(() => {
                    checkIcon.classList.toggle('text-teal-300');
                }, 1000);
            }
            
            // Prevent back navigation after logout
            window.history.pushState(null, null, window.location.href);
            window.onpopstate = function() {
                window.history.go(1);
            };
        });
    </script>
    
    <style>
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fa-check-circle {
            transition: color 0.5s ease-in-out;
        }
        
        /* Prevent text selection for better UX */
        body {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
    </style>
</body>
</html>
