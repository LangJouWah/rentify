<?php
session_start();

ini_set('session.gc_maxlifetime', 300); // 5 minutes
session_set_cookie_params(300);

// Prevent browser from caching pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "db_connect.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

function generateOTP($length = 6) {
    $digits = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $digits[random_int(0, strlen($digits) - 1)];
    }
    return $otp;
}

// Auto cleanup expired or used OTPs
function cleanupExpiredOTPs($conn) {
    $stmt = $conn->prepare("
        DELETE FROM password_reset_otps 
        WHERE expires_at < NOW() 
           OR used = 1
    ");
    $stmt->execute();
}

// Call at top of your script
cleanupExpiredOTPs($conn);

function sendOTPEmail($recipientEmail, $recipientName, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'systembec0@gmail.com';
        $mail->Password = 'uzda yasn cmeg uyje';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom('systembec0@gmail.com', 'BEC HRMS Clinic System');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Password Reset - BEC HRMS Clinic System';
        
        // Enhanced HTML email template
        $htmlMessage = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #800000, #a83232); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                .otp-box { background: #fff; padding: 20px; border: 3px solid #800000; border-radius: 10px; font-size: 32px; font-weight: bold; text-align: center; margin: 20px 0; letter-spacing: 8px; color: #800000; }
                .warning { background: #fff3cd; padding: 12px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 15px 0; }
                .timer { background: #e7f3ff; padding: 10px; border-radius: 5px; border-left: 4px solid #007bff; margin: 10px 0; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BEC HRMS Clinic System</h1>
                </div>
                <div class='content'>
                    <h2>Password Reset OTP</h2>
                    <p>Dear $recipientName,</p>
                    <p>You have requested a password reset for your BEC HRMS Clinic System account.</p>
                    <p>Use the following OTP to verify your identity:</p>
                    <div class='otp-box'>$otp</div>
                    <div class='timer'>
                        ⏰ This OTP will expire in 5 minutes
                    </div>
                    <div class='warning'>
                        <strong>Important:</strong> Do not share this OTP with anyone. Our support team will never ask for your OTP.
                    </div>
                    <p>If you did not request this password reset, please contact our support team immediately.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " BEC HRMS Clinic System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $htmlMessage;
        $mail->AltBody = "OTP: $otp\n\nDear $recipientName,\n\nYou have requested a password reset. Use this OTP to verify your identity. This OTP will expire in 5 minutes.\n\nIf you didn't request this, please contact support.";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email sending failed to $recipientEmail: " . $mail->ErrorInfo);
        return false;
    }
}

function handleForgotPassword($conn, $email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => "Please enter a valid email address."];
    }

    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $user_name = $user['name'];
        $user_email = $user['email'];

        // Generate OTP
        $otp = generateOTP();
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        // Store OTP in database
        $stmt = $conn->prepare("INSERT INTO password_reset_otps (user_id, otp, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $otp, $expires_at);
        
        if ($stmt->execute()) {
            // Send OTP email
            if (sendOTPEmail($user_email, $user_name, $otp)) {
                $_SESSION['otp_user_id'] = $user_id;
                $_SESSION['otp_email'] = $user_email;
                $_SESSION['otp_verified'] = false;
                $_SESSION['otp_sent_time'] = time(); // Store the time when OTP was sent
                return ['success' => "An OTP has been sent to your email. Please check your inbox and enter the OTP to reset your password."];
            } else {
                return ['error' => "Failed to send OTP email. Please try again later."];
            }
        } else {
            return ['error' => "Failed to generate OTP. Please try again later."];
        }
    } else {
        return ['error' => "No account found with this email."];
    }
}

function verifyOTP($conn, $user_id, $otp) {
    $stmt = $conn->prepare("SELECT id FROM password_reset_otps WHERE user_id = ? AND otp = ? AND expires_at > NOW() AND used = 0");
    $stmt->bind_param("is", $user_id, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        // Mark OTP as used
        $stmt = $conn->prepare("UPDATE password_reset_otps SET used = 1 WHERE user_id = ? AND otp = ?");
        $stmt->bind_param("is", $user_id, $otp);
        $stmt->execute();
        
        return true;
    }
    return false;
}

function resetPassword($conn, $user_id, $new_password) {
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $user_id);
    return $stmt->execute();
}

$success = $error = "";
$step = 1; // 1: Email input, 2: OTP input, 3: New password input

// Check for form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_error'] = "Invalid form submission. Please try again.";
    } else {
        // Check if this is a resend request first
        if (isset($_POST['resend_otp'])) {
            // Handle OTP resend request
            if (isset($_SESSION['otp_user_id']) && isset($_SESSION['otp_email'])) {
                $current_time = time();
                $last_sent_time = $_SESSION['otp_sent_time'] ?? 0;
                $cooldown_period = 60; // 60 seconds cooldown
                
                if (($current_time - $last_sent_time) < $cooldown_period) {
                    $remaining_time = $cooldown_period - ($current_time - $last_sent_time);
                    $_SESSION['flash_error'] = "Please wait $remaining_time seconds before requesting a new OTP.";
                } else {
                    // Resend OTP
                    $user_id = $_SESSION['otp_user_id'];
                    $user_email = $_SESSION['otp_email'];
                    
                    // Get user name
                    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $user_name = $user['name'];
                    
                    // Generate new OTP
                    $otp = generateOTP();
                    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    // Store new OTP in database
                    $stmt = $conn->prepare("INSERT INTO password_reset_otps (user_id, otp, expires_at) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $user_id, $otp, $expires_at);
                    
                    if ($stmt->execute()) {
                        // Send new OTP email
                        if (sendOTPEmail($user_email, $user_name, $otp)) {
                            $_SESSION['otp_sent_time'] = time(); // Update sent time
                            $_SESSION['flash_success'] = "A new OTP has been sent to your email.";
                        } else {
                            $_SESSION['flash_error'] = "Failed to send OTP email. Please try again later.";
                        }
                    } else {
                        $_SESSION['flash_error'] = "Failed to generate new OTP. Please try again later.";
                    }
                }
            } else {
                $_SESSION['flash_error'] = "Session expired. Please start over.";
                $step = 1;
            }
        } elseif (isset($_POST['email'])) {
            // Step 1: Email submission
            $email = trim($_POST['email'] ?? '');
            $response = handleForgotPassword($conn, $email);

            if (isset($response['success'])) {
                $_SESSION['flash_success'] = $response['success'];
                $step = 2;
            } else {
                $_SESSION['flash_error'] = $response['error'];
            }
        } elseif (isset($_POST['otp'])) {
            // Step 2: OTP verification
            $otp = trim($_POST['otp'] ?? '');
            if (isset($_SESSION['otp_user_id'])) {
                if (verifyOTP($conn, $_SESSION['otp_user_id'], $otp)) {
                    $_SESSION['otp_verified'] = true;
                    $_SESSION['flash_success'] = "OTP verified successfully. You can now set your new password.";
                    $step = 3;
                } else {
                    $_SESSION['flash_error'] = "Invalid or expired OTP. Please try again.";
                    $step = 2;
                }
            } else {
                $_SESSION['flash_error'] = "Session expired. Please start over.";
                $step = 1;
            }
        } elseif (isset($_POST['new_password'])) {
            // Step 3: Password reset
            if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] && isset($_SESSION['otp_user_id'])) {
                $new_password = trim($_POST['new_password'] ?? '');
                $confirm_password = trim($_POST['confirm_password'] ?? '');
                
                if (strlen($new_password) < 8) {
                    $_SESSION['flash_error'] = "Password must be at least 8 characters long.";
                    $step = 3;
                } elseif ($new_password !== $confirm_password) {
                    $_SESSION['flash_error'] = "Passwords do not match.";
                    $step = 3;
                } else {
                    if (resetPassword($conn, $_SESSION['otp_user_id'], $new_password)) {
                        $_SESSION['flash_success'] = "Password reset successfully! You can now log in with your new password.";
                        // Clear OTP session
                        unset($_SESSION['otp_user_id'], $_SESSION['otp_email'], $_SESSION['otp_verified'], $_SESSION['otp_sent_time']);
                        $step = 1;
                    } else {
                        $_SESSION['flash_error'] = "Failed to reset password. Please try again.";
                        $step = 3;
                    }
                }
            } else {
                $_SESSION['flash_error'] = "OTP verification required. Please start over.";
                $step = 1;
            }
        }
    }

    // Redirect to same page to prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Determine current step from session
if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified']) {
    $step = 3;
} elseif (isset($_SESSION['otp_user_id'])) {
    $step = 2;
} else {
    $step = 1;
}

// Calculate remaining time for resend
$resend_cooldown = 60; // 60 seconds
$remaining_time = 0;
if (isset($_SESSION['otp_sent_time'])) {
    $elapsed_time = time() - $_SESSION['otp_sent_time'];
    $remaining_time = max(0, $resend_cooldown - $elapsed_time);
}

// Show flash messages
if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Forgot Password - Health Record Management</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Inter', sans-serif;
    background: #5a1a1a; /* Maroon background */
    color: #333;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    position: relative;
    overflow: hidden;
  }

  /* Background image - always present but covered by gradient */
  .background-image {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('OIP.jpg') center/cover no-repeat;
    z-index: -2;
    opacity: 0;
    animation: imagePulse 9s infinite;
  }

  /* Animated gradient mask that reveals the image */
  .gradient-mask {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(-45deg, #8e0e00, #a71d31, #3c0d0d, #5a1a1a);
    background-size: 400% 400%;
    animation: gradientBG 12s ease infinite;
    z-index: -1;
    opacity: 1;
    mask-image: linear-gradient(to right, transparent 0%, black 25%, black 75%, transparent 100%);
    mask-size: 200% 100%;
    mask-position: -100% 0;
    animation: gradientBG 12s ease infinite, waveReveal 12s ease infinite;
  }

  /* Keyframes for gradient movement - 1 second maroon */
  @keyframes gradientBG {
    0%, 11.1% { 
      background-position: 0% 50%; 
    }
    33.3%, 50% { 
      background-position: 100% 50%; 
    }
    66.6%, 88.9% { 
      background-position: 0% 50%; 
    }
    100% { 
      background-position: 0% 50%; 
    }
  }

  /* Keyframes for revealing the image through the wave */
  @keyframes waveReveal {
    0%, 11.1% {
      mask-position: -100% 0;
    }
    33.3% {
      mask-position: 0% 0;
    }
    50% {
      mask-position: 100% 0;
    }
    66.6% {
      mask-position: 0% 0;
    }
    88.9%, 100% {
      mask-position: -100% 0;
    }
  }

  /* Make the image visible only during the wave */
  @keyframes imagePulse {
    0%, 11.1% {
      opacity: 0;
    }
    33.3%, 50% {
      opacity: 1;
    }
    66.6%, 100% {
      opacity: 0;
    }
  }

  header {
    text-align: center;
    padding: 2rem 1rem 1rem;
    position: relative;
    z-index: 1;
  }

  header h1 {
    font-size: 1.8rem;
    color: white;
    font-weight: 700;
    line-height: 1.4;
    text-shadow: 0 2px 6px rgba(0,0,0,0.4);
  }

  .container {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    position: relative;
    z-index: 1;
  }

  .card {
    background: #fff;
    width: 100%;
    max-width: 420px;
    padding: 2.5rem 2rem;
    border-radius: 18px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    animation: fadeInUp 0.6s ease;
    text-align: center;
  }

  .logo {
    margin-bottom: 1.2rem;
  }

  .logo img {
    width: 90px;
    height: 90px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #a71d31;
    background: #fff;
    padding: 4px;
    box-shadow: 0 4px 12px rgba(167, 29, 49, 0.4);
  }

  .card h2 {
    font-size: 1.6rem;
    font-weight: 700;
    color: #2c2c2c;
    margin-bottom: 1.8rem;
  }

  .step-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    position: relative;
  }

  .step-indicator::before {
    content: '';
    position: absolute;
    top: 15px;
    left: 10%;
    right: 10%;
    height: 2px;
    background: #ddd;
    z-index: 1;
  }

  .step {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #666;
    position: relative;
    z-index: 2;
  }

  .step.active {
    background: #a71d31;
    color: white;
  }

  .step.completed {
    background: #4caf50;
    color: white;
  }

  .step-label {
    position: absolute;
    top: 35px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.7rem;
    white-space: nowrap;
    color: #666;
  }

  .success, .error {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    text-align: center;
    font-weight: 600;
  }

  .success {
    background: #eafaf1;
    color: #155724;
    border: 1px solid #c3e6cb;
  }

  .error {
    background: #fdecea;
    color: #b71c1c;
    border: 1px solid #f5c6cb;
  }

  .form-control {
    margin-bottom: 1.2rem;
  }

  input[type="email"],
  input[type="text"],
  input[type="password"] {
    width: 100%;
    padding: 14px 15px;
    border: 1.8px solid #d1d9e6;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background-color: #fafafa;
  }

  input:focus {
    border-color: #a71d31;
    background: #fff;
    box-shadow: 0 0 8px rgba(167, 29, 49, 0.4);
    outline: none;
  }

  .otp-input {
    font-size: 1.2rem !important;
    text-align: center;
    letter-spacing: 8px;
    font-weight: bold;
  }

  button {
    width: 100%;
    padding: 14px 0;
    background: linear-gradient(135deg, #6e0b14, #a71d31);
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    color: white;
    box-shadow: 0 4px 15px rgba(167, 29, 49, 0.4);
  }

  button:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(167, 29, 49, 0.6);
  }

  button:disabled {
    background: #cccccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
  }

  .back-link {
    display: block;
    margin-top: 1.5rem;
    color: #6e0b14;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s ease;
  }

  .back-link:hover {
    color: #a71d31;
    text-decoration: underline;
  }

  .resend-otp {
    margin-top: 1rem;
    font-size: 0.9rem;
    color: #666;
  }

  .resend-otp a {
    color: #a71d31;
    text-decoration: none;
    font-weight: 600;
  }

  .resend-otp a:hover:not(.disabled) {
    text-decoration: underline;
  }

  .resend-otp a.disabled {
    color: #999;
    cursor: not-allowed;
    text-decoration: none;
  }

  .timer {
    font-weight: bold;
    color: #a71d31;
  }

  .resend-button {
    background: none;
    border: none;
    color: #a71d31;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
    font-size: 0.9rem;
    font-family: inherit;
  }

  .resend-button:hover:not(:disabled) {
    text-decoration: underline;
  }

  .resend-button:disabled {
    color: #999;
    cursor: not-allowed;
    text-decoration: none;
  }

  footer {
    text-align: center;
    padding: 1.5rem 1rem;
    font-size: 0.9rem;
    color: #f1f1f1;
    text-shadow: 0 1px 3px rgba(0,0,0,0.4);
    position: relative;
    z-index: 1;
  }

  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @media (max-width: 480px) {
    header h1 { font-size: 1.4rem; }
    .card { padding: 2rem 1.5rem; }
    .step-label { font-size: 0.6rem; }
  }
</style>
</head>
<body>

<!-- Background image that will be revealed -->
<div class="background-image"></div>

<!-- Gradient mask that will animate and reveal the image -->
<div class="gradient-mask"></div>

<header>
  <h1>Health Record Management System<br>Batangas Eastern Colleges</h1>
</header>

<div class="container">
  <div class="card">
    <div class="logo">
      <img src="logo.png" alt="School Logo">
    </div>
    <h2 id="pageTitle">Forgot Password</h2>

    <!-- Step Indicator -->
    <div class="step-indicator">
      <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
        1
        <div class="step-label">Email</div>
      </div>
      <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">
        2
        <div class="step-label">OTP</div>
      </div>
      <div class="step <?= $step >= 3 ? 'active' : '' ?>">
        3
        <div class="step-label">Password</div>
      </div>
    </div>

    <?php if ($success): ?>
      <p class="success" role="alert"><?= htmlspecialchars($success) ?></p>
    <?php elseif ($error): ?>
      <p class="error" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Step 1: Email Input -->
    <?php if ($step === 1): ?>
    <form method="POST" novalidate>
      <div class="form-control">
        <input 
          type="email" 
          name="email" 
          placeholder="Enter your email" 
          required 
          aria-required="true"
          value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
        />
      </div>
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
      <button type="submit">Send OTP</button>
    </form>
    <?php endif; ?>

    <!-- Step 2: OTP Input -->
    <?php if ($step === 2): ?>
    <form method="POST" novalidate id="otpForm">
      <div class="form-control">
        <input 
          type="text" 
          name="otp" 
          placeholder="Enter 6-digit OTP" 
          required 
          aria-required="true"
          maxlength="6"
          pattern="[0-9]{6}"
          class="otp-input"
        />
      </div>
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
      <button type="submit">Verify OTP</button>
      <div class="resend-otp">
        <?php if ($remaining_time > 0): ?>
          <span class="timer">Resend available in <span id="countdown"><?= $remaining_time ?></span> seconds</span>
        <?php else: ?>
          <form method="POST" style="display: inline;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
            <input type="hidden" name="resend_otp" value="1" />
            <button type="submit" class="resend-button">Resend OTP</button>
          </form>
        <?php endif; ?>
      </div>
    </form>
    <?php endif; ?>

    <!-- Step 3: New Password Input -->
    <?php if ($step === 3): ?>
    <form method="POST" novalidate>
      <div class="form-control">
        <input 
          type="password" 
          name="new_password" 
          placeholder="New password (min. 8 characters)" 
          required 
          aria-required="true"
          minlength="8"
        />
      </div>
      <div class="form-control">
        <input 
          type="password" 
          name="confirm_password" 
          placeholder="Confirm new password" 
          required 
          aria-required="true"
          minlength="8"
        />
      </div>
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
      <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>

    <a href="backtologin.php" class="back-link">← Back to Login</a>
  </div>
</div>

<footer>
  <p>&copy; <?= date("Y") ?> Batangas Eastern Colleges | Health Record Management System</p>
</footer>

<script>
// Countdown timer for OTP resend
<?php if ($step === 2 && $remaining_time > 0): ?>
let timeLeft = <?= $remaining_time ?>;
const countdownElement = document.getElementById('countdown');

const countdownTimer = setInterval(function() {
    timeLeft--;
    countdownElement.textContent = timeLeft;
    
    if (timeLeft <= 0) {
        clearInterval(countdownTimer);
        location.reload(); // Reload to show the resend button
    }
}, 1000);
<?php endif; ?>

// Auto-submit OTP when 6 digits are entered
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.querySelector('.otp-input');
    if (otpInput) {
        otpInput.addEventListener('input', function() {
            if (this.value.length === 6) {
                document.getElementById('otpForm').submit();
            }
        });
    }
});
</script>

</body>
</html>