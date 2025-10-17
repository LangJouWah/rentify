<?php
session_start();

// Include database connection
require_once 'db_connect.php';

// Include PHPMailer files
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email sending function using PHPMailer
function sendEmail($to, $subject, $message) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rentifynoreply@gmail.com';
        $mail->Password = 'jahm ygwf aqcq sijd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('rentifynoreply@gmail.com', 'Rentify');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Generate random OTP
function generateOTP($length = 6) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Check if user can request OTP (anti-spam)
function canRequestOTP($email) {
    if (!isset($_SESSION['otp_requests'])) {
        $_SESSION['otp_requests'] = [];
    }
    
    $current_time = time();
    $user_requests = $_SESSION['otp_requests'][$email] ?? [];
    
    // Remove requests older than 1 hour
    $user_requests = array_filter($user_requests, function($timestamp) use ($current_time) {
        return ($current_time - $timestamp) < 3600; // 1 hour
    });
    
    // Check if user has made more than 5 requests in the last hour
    if (count($user_requests) >= 5) {
        return false;
    }
    
    // Add current request
    $user_requests[] = $current_time;
    $_SESSION['otp_requests'][$email] = $user_requests;
    
    return true;
}

// Get time until next OTP can be requested
function getTimeUntilNextOTP($email) {
    if (!isset($_SESSION['otp_requests'][$email])) {
        return 0;
    }
    
    $current_time = time();
    $user_requests = $_SESSION['otp_requests'][$email];
    
    // Sort requests by time
    sort($user_requests);
    
    // If user has less than 5 requests, they can request immediately
    if (count($user_requests) < 5) {
        return 0;
    }
    
    // Calculate when the oldest request will be 1 hour old
    $oldest_request = $user_requests[0];
    $next_available = $oldest_request + 3600 - $current_time;
    
    return max(0, $next_available);
}

// Handle resend OTP request
if (isset($_GET['resend']) && $_GET['resend'] === 'true' && isset($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
    
    if (!canRequestOTP($email)) {
        $time_left = getTimeUntilNextOTP($email);
        $error = "Too many OTP requests. Please wait " . ceil($time_left / 60) . " minutes before requesting another OTP.";
    } else {
        // Regenerate OTP
        $otp = generateOTP();
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Update session with new OTP
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_expiry'] = $otp_expiry;
        
        // Get user name from database
        $stmt = $conn->prepare("SELECT name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Send new OTP email
        $subject = "Rentify - New Password Reset OTP";
        $message = "
            <html>
            <head>
                <style>
    body { 
        font-family: Arial, sans-serif; 
        background: #1f2937;

    }
    .container { 
        max-width: 600px; 
        margin: 0 auto; 
        padding: 20px; 
        background: #111827;
        border: 1px solid #374151;
        border-radius: 0.5rem;
        font-color: white;
    }
    .header { 
        background: #0d9488; 
        color: white; 
        padding: 20px; 
        text-align: center; 
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .content { 
        padding: 20px; 
        background: #1f2937; 
        color: #ffffffff;
        border-radius: 0 0 0.5rem 0.5rem;
        font-color: white;
    }
    .otp { 
        font-size: 32px; 
        font-weight: bold; 
        text-align: center; 
        color: #14b8a6; 
        margin: 20px 0; 
        background: #111827;
        padding: 15px;
        border-radius: 0.5rem;
        border: 1px solid #374151;
    }
    .footer { 
        text-align: center; 
        padding: 20px; 
        font-size: 12px; 
        color: #9ca3af;
        margin-top: 20px;
        border-top: 1px solid #374151;
    }
</style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Rentify</h1>
                        <h2>New Password Reset OTP</h2>
                    </div>
                    <div class='content'>
                        <p>Hello " . htmlspecialchars($user['name']) . ",</p>
                        <p>You have requested a new OTP. Use the following OTP to proceed:</p>
                        <div class='otp'>" . $otp . "</div>
                        <p>This OTP will expire in 10 minutes.</p>
                        <p>If you didn't request this reset, please ignore this email.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Rentify. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        if (sendEmail($email, $subject, $message)) {
            $success = "New OTP has been sent to your email address.";
        } else {
            $error = "Failed to send OTP. Please try again.";
        }
    }
    $show_otp_form = true;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_otp'])) {
        // Step 1: Request OTP
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check anti-spam protection
            if (!canRequestOTP($email)) {
                $time_left = getTimeUntilNextOTP($email);
                $error = "Too many OTP requests. Please wait " . ceil($time_left / 60) . " minutes before requesting another OTP.";
            } else {
                // Check if email exists in database
                $stmt = $conn->prepare("SELECT user_id, name FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Generate OTP
                    $otp = generateOTP();
                    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    
                    // Store OTP in session
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_otp'] = $otp;
                    $_SESSION['otp_expiry'] = $otp_expiry;
                    $_SESSION['otp_attempts'] = 0;
                    
                    // Send OTP email
                    $subject = "Rentify - Password Reset OTP";
                    $message = "
                        <html>
                        <head>
                            <style>
    body { 
        font-family: Arial, sans-serif; 
        background: #1f2937;
    }
    .container { 
        max-width: 600px; 
        margin: 0 auto; 
        padding: 20px; 
        background: #111827;
        border: 1px solid #374151;
        border-radius: 0.5rem;
        font-color: white;
    }
    .header { 
        background: #0d9488; 
        color: white; 
        padding: 20px; 
        text-align: center; 
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .content { 
        padding: 20px; 
        background: #1f2937; 
        color: #f3f4f6;
        border-radius: 0 0 0.5rem 0.5rem;
        font-color: white;
    }
    .otp { 
        font-size: 32px; 
        font-weight: bold; 
        text-align: center; 
        color: #14b8a6; 
        margin: 20px 0; 
        background: #111827;
        padding: 15px;
        border-radius: 0.5rem;
        border: 1px solid #374151;
    }
    .footer { 
        text-align: center; 
        padding: 20px; 
        font-size: 12px; 
        color: #9ca3af;
        margin-top: 20px;
        border-top: 1px solid #374151;
    }
</style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h1>Rentify</h1>
                                    <h2>Password Reset Request</h2>
                                </div>
                                <div class='content'>
                                    <p>Hello " . htmlspecialchars($user['name']) . ",</p>
                                    <p>You have requested to reset your password. Use the following OTP to proceed:</p>
                                    <div class='otp'>" . $otp . "</div>
                                    <p>This OTP will expire in 10 minutes.</p>
                                    <p>If you didn't request this reset, please ignore this email.</p>
                                </div>
                                <div class='footer'>
                                    <p>&copy; " . date('Y') . " Rentify. All rights reserved.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ";
                    
                    if (sendEmail($email, $subject, $message)) {
                        $success = "OTP has been sent to your email address.";
                        $show_otp_form = true;
                    } else {
                        $error = "Failed to send OTP. Please try again.";
                    }
                } else {
                    $error = "No account found with this email address.";
                }
                $stmt->close();
            }
        }
    } 
    elseif (isset($_POST['verify_otp'])) {
        // Step 2: Verify OTP
        $entered_otp = $_POST['otp'];
        $email = $_SESSION['reset_email'] ?? '';
        
        if (empty($email)) {
            $error = "Session expired. Please start over.";
            session_destroy();
        } elseif ($_SESSION['otp_attempts'] >= 3) {
            $error = "Too many failed attempts. Please request a new OTP.";
            unset($_SESSION['reset_otp'], $_SESSION['otp_attempts']);
        } elseif (time() > strtotime($_SESSION['otp_expiry'])) {
            $error = "OTP has expired. Please request a new one.";
            unset($_SESSION['reset_otp']);
        } elseif ($entered_otp === $_SESSION['reset_otp']) {
            // OTP verified successfully
            $_SESSION['otp_verified'] = true;
            $success = "OTP verified successfully. You can now set your new password.";
            $show_password_form = true;
        } else {
            $_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;
            $attempts_left = 3 - $_SESSION['otp_attempts'];
            $error = "Invalid OTP. You have {$attempts_left} attempt(s) left.";
            $show_otp_form = true;
        }
    } 
    elseif (isset($_POST['reset_password'])) {
        // Step 3: Reset Password
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $email = $_SESSION['reset_email'] ?? '';
        
        if (empty($email) || !isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
            $error = "Session expired or OTP not verified. Please start over.";
            session_destroy();
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
            $show_password_form = true;
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
            $show_password_form = true;
        } else {
            // Hash new password and update in database
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            
            if ($stmt->execute()) {
                $success = "Password has been reset successfully!";
                
                // Clear all reset-related session variables
                unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['otp_expiry'], 
                      $_SESSION['otp_attempts'], $_SESSION['otp_verified']);
                
                // Also clear OTP requests for this email
                if (isset($_SESSION['otp_requests'][$email])) {
                    unset($_SESSION['otp_requests'][$email]);
                }
                
                // Show login link
                $show_login_link = true;
            } else {
                $error = "Failed to reset password. Please try again.";
                $show_password_form = true;
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Rentify</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #1f2937;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .container {
        background: #111827;
        border: 1px solid #374151;
        border-radius: 0.5rem;
        padding: 2rem;
        width: 100%;
        max-width: 28rem;
    }
    
    .logo {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    
    .logo h1 {
        color: #14b8a6;
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        font-weight: bold;
    }
    
    .logo p {
        color: #9ca3af;
        font-size: 1.1rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    label {
        display: block;
        margin-bottom: 0.5rem;
        color: #f3f4f6;
        font-weight: 500;
    }
    
    input[type="email"],
    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #374151;
        background: #111827;
        color: #f3f4f6;
        border-radius: 0.5rem;
        font-size: 1rem;
        transition: all 0.3s;
    }
    
    input[type="email"]:focus,
    input[type="text"]:focus,
    input[type="password"]:focus {
        outline: none;
        border-color: #0d9488;
        box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2);
    }
    
    .btn {
        width: 100%;
        padding: 0.75rem;
        background: #0d9488;
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .btn:hover:not(:disabled) {
        background: #0f766e;
    }
    
    .btn:disabled {
        background: #6b7280;
        cursor: not-allowed;
    }
    
    .alert {
        padding: 0.75rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        text-align: center;
        border: 1px solid;
    }
    
    .alert-success {
        background: #064e3b;
        color: #a7f3d0;
        border-color: #047857;
    }
    
    .alert-error {
        background: #7f1d1d;
        color: #fecaca;
        border-color: #dc2626;
    }
    
    .login-link {
        text-align: center;
        margin-top: 1rem;
    }
    
    .login-link a {
        color: #14b8a6;
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .login-link a:hover {
        color: #99f6e4;
        text-decoration: underline;
    }
    
    .step-info {
        text-align: center;
        color: #9ca3af;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }
    
    .resend-info {
        text-align: center;
        margin-top: 0.75rem;
        font-size: 0.875rem;
        color: #9ca3af;
    }
    
    .timer {
        color: #ef4444;
        font-weight: bold;
    }
    
    .cooldown-message {
        background: #78350f;
        color: #fef3c7;
        padding: 0.75rem;
        border-radius: 0.5rem;
        text-align: center;
        margin-bottom: 1rem;
        border: 1px solid #d97706;
        font-size: 0.875rem;
    }
</style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Rentify</h1>
            <p>Reset Your Password</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!isset($show_otp_form) && !isset($show_password_form)): ?>
            <!-- Step 1: Request OTP Form -->
            <div class="step-info">Step 1: Enter your email address</div>
            <form method="POST" action="" id="emailForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <button type="submit" name="request_otp" class="btn" id="submitBtn">Send OTP</button>
            </form>
        <?php endif; ?>

        <?php if (isset($show_otp_form)): ?>
            <!-- Step 2: Verify OTP Form -->
            <div class="step-info">Step 2: Enter OTP sent to your email</div>
            
            <?php 
            $email = $_SESSION['reset_email'] ?? '';
            $time_left = getTimeUntilNextOTP($email);
            if ($time_left > 0): ?>
                <div class="cooldown-message">
                    You can request a new OTP in <span class="timer" id="cooldownTimer"><?php echo ceil($time_left / 60); ?></span> minutes
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="otp">6-Digit OTP</label>
                    <input type="text" id="otp" name="otp" required maxlength="6" 
                           pattern="[0-9]{6}" title="Please enter 6-digit OTP" autocomplete="off">
                </div>
                <button type="submit" name="verify_otp" class="btn">Verify OTP</button>
            </form>
            <div class="resend-info">
                OTP is valid for 10 minutes. Didn't receive it? 
                <?php if ($time_left > 0): ?>
                    <span style="color: #dc3545;">Wait <?php echo ceil($time_left / 60); ?> minutes to resend</span>
                <?php else: ?>
                    <a href="?resend=true">Resend OTP</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($show_password_form)): ?>
            <!-- Step 3: Reset Password Form -->
            <div class="step-info">Step 3: Set your new password</div>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required 
                           minlength="6" placeholder="At least 6 characters">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           minlength="6" placeholder="Re-enter your new password">
                </div>
                <button type="submit" name="reset_password" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>

        <?php if (isset($show_login_link)): ?>
            <!-- Success message with login link -->
            <div class="login-link">
                <a href="login.php" class="btn">Go to Login</a>
            </div>
        <?php else: ?>
            <div class="login-link">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Client-side validation and anti-spam
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const password = form.querySelector('input[name="new_password"]');
                    const confirmPassword = form.querySelector('input[name="confirm_password"]');
                    
                    if (password && confirmPassword) {
                        if (password.value !== confirmPassword.value) {
                            e.preventDefault();
                            alert('Passwords do not match!');
                            return false;
                        }
                        
                        if (password.value.length < 6) {
                            e.preventDefault();
                            alert('Password must be at least 6 characters long!');
                            return false;
                        }
                    }
                    
                    const otpInput = form.querySelector('input[name="otp"]');
                    if (otpInput) {
                        const otp = otpInput.value;
                        if (!/^\d{6}$/.test(otp)) {
                            e.preventDefault();
                            alert('Please enter a valid 6-digit OTP!');
                            return false;
                        }
                    }
                });
            });

            // Cooldown timer for OTP resend
            const cooldownTimer = document.getElementById('cooldownTimer');
            if (cooldownTimer) {
                let timeLeft = parseInt(cooldownTimer.textContent) * 60; // Convert to seconds
                
                const timerInterval = setInterval(() => {
                    timeLeft--;
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        location.reload(); // Reload to show resend option
                    } else {
                        const minutes = Math.ceil(timeLeft / 60);
                        cooldownTimer.textContent = minutes;
                    }
                }, 1000);
            }

            // Simple button disable to prevent double-clicks (but allow form submission)
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                const form = submitBtn.closest('form');
                form.addEventListener('submit', function() {
                    // Only disable after form is actually submitted
                    setTimeout(() => {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Sending OTP...';
                    }, 100);
                });
            }
        });
    </script>
</body>
</html>
