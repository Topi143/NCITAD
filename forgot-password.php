<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/email_helper.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['is_admin']) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/concernlist.php");
    }
    exit();
}

$message = '';
$message_type = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'error';
        } else {
            try {
                // Check if email exists in database
                $stmt = $pdo->prepare("SELECT user_id, username, faculty_name, email FROM users WHERE email = :email");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Generate secure random token
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Delete any existing reset tokens for this user
                    $deleteStmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
                    $deleteStmt->execute(['user_id' => $user['user_id']]);
                    
                    // Insert new reset token
                    $insertStmt = $pdo->prepare("
                        INSERT INTO password_resets (user_id, token, expiry) 
                        VALUES (:user_id, :token, :expiry)
                    ");
                    $insertStmt->execute([
                        'user_id' => $user['user_id'],
                        'token' => $token,
                        'expiry' => $expiry
                    ]);
                    
                    // Create reset link
                    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
                    
                    // Prepare email
                    $subject = "Password Reset Request - NCITAD";
                    $htmlMessage = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
                            .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                            .header h1 { margin: 0; font-size: 24px; }
                            .content { background: white; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                            .button { display: inline-block; padding: 14px 30px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
                            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0; border-radius: 4px; }
                            .info-box { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 12px; margin: 15px 0; border-radius: 4px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>🔐 Password Reset Request</h1>
                            </div>
                            <div class='content'>
                                <p>Hello <strong>" . htmlspecialchars($user['faculty_name']) . "</strong>,</p>
                                
                                <p>We received a request to reset the password for your NCITAD account (<strong>" . htmlspecialchars($user['username']) . "</strong>).</p>
                                
                                <div class='info-box'>
                                    <strong>📧 Email:</strong> " . htmlspecialchars($user['email']) . "
                                </div>
                                
                                <p>Click the button below to reset your password:</p>
                                
                                <div style='text-align: center;'>
                                    <a href='" . $resetLink . "' class='button'>Reset My Password</a>
                                </div>
                                
                                <p>Or copy and paste this link into your browser:</p>
                                <p style='word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px;'>" . $resetLink . "</p>
                                
                                <div class='warning'>
                                    <strong>⚠️ Important:</strong> This link will expire in <strong>1 hour</strong> for security reasons.
                                </div>
                                
                                <p><strong>If you didn't request this password reset:</strong></p>
                                <ul>
                                    <li>You can safely ignore this email</li>
                                    <li>Your password will remain unchanged</li>
                                    <li>Consider changing your password if you suspect unauthorized access</li>
                                </ul>
                                
                                <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;'>
                                
                                <p style='font-size: 12px; color: #666;'>
                                    <strong>Security Tip:</strong> Never share your password with anyone. NCITAD staff will never ask for your password via email.
                                </p>
                            </div>
                            <div class='footer'>
                                <p><strong>Norzagaray College IT Assistance Desk</strong></p>
                                <p>&copy; " . date('Y') . " NCITAD. All rights reserved.</p>
                                <p>This is an automated message, please do not reply to this email.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    // Send email with correct parameter order: ($to, $toName, $subject, $htmlBody, $textBody = '')
                    $emailResult = sendEmail($user['email'], $user['faculty_name'], $subject, $htmlMessage);
                    
                    if ($emailResult['success']) {
                        // Email sent successfully to existing account
                        $message = 'If an account with that email exists, password reset instructions have been sent. Please check your inbox.';
                        $message_type = 'success';
                        error_log("Password reset email sent to: " . $user['email']);
                    } else {
                        // Failed to send email, but don't reveal if account exists
                        $message = 'If an account with that email exists, password reset instructions have been sent.';
                        $message_type = 'success'; // Still show success to prevent enumeration
                        error_log("Email sending failed for " . $user['email'] . ": " . $emailResult['error']);
                    }
                } else {
                    // Email doesn't exist in database - show same message for security (prevent email enumeration)
                    // NO EMAIL IS SENT in this case
                    $message = 'If an account with that email exists, password reset instructions have been sent.';
                    $message_type = 'success';
                    error_log("Password reset attempted for non-existent email: " . $email);
                }
            } catch (PDOException $e) {
                // Log detailed error for debugging
                error_log("Password reset database error: " . $e->getMessage());
                error_log("Error code: " . $e->getCode());
                
                // Check if table doesn't exist
                if (strpos($e->getMessage(), "doesn't exist") !== false || $e->getCode() == '42S02') {
                    $message = 'System error: Password reset table not found. Please contact administrator.';
                } else {
                    $message = 'An error occurred. Please try again later. Error: ' . $e->getMessage();
                }
                $message_type = 'error';
            }
        }
    } else {
        $message = 'Please enter your email address.';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Norzagaray College IT Assistance Desk</title>
    <link rel="icon" type="image/png" href="assets/images/norzagaray-college-logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        .forgot-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        /* Left Side - Branding */
        .branding-section {
            width: 50%;
            background: url('assets/images/norzagaray-college.png') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 40px;
            position: relative;
            overflow: hidden;
        }

        .branding-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.5) 0%, rgba(42, 82, 152, 0.5) 100%);
            z-index: 1;
        }

        .branding-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
            max-width: 500px;
        }

        .college-logo {
            width: 250px;
            height: 250px;
            margin: 0 auto 30px;
            animation: float 3s ease-in-out infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 10px 30px rgba(0,0,0,0.4));
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .college-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .branding-content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.3);
            letter-spacing: -0.5px;
        }

        .branding-content .subtitle {
            font-size: 1.3rem;
            margin-bottom: 12px;
            font-weight: 600;
            opacity: 0.95;
        }

        .branding-content .tagline {
            font-size: 1rem;
            opacity: 0.85;
            line-height: 1.6;
            font-weight: 300;
        }

        /* Right Side - Forgot Password Form */
        .forgot-section {
            width: 50%;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            overflow-y: auto;
        }

        .forgot-box {
            width: 100%;
            max-width: 440px;
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .forgot-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
        }

        .forgot-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }

        .forgot-header h2 {
            color: #1e3c72;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .forgot-header p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 13px 15px 13px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
            font-family: inherit;
            color: #333;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            pointer-events: none;
            width: 18px;
            height: 18px;
        }

        .input-icon svg {
            width: 100%;
            height: 100%;
            fill: currentColor;
        }

        .form-control:focus {
            outline: none;
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        }

        .form-control::placeholder {
            color: #999;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid #28a745;
            display: flex;
            align-items: start;
            gap: 10px;
        }

        .success-message svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            fill: #28a745;
            margin-top: 2px;
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid #c00;
            display: flex;
            align-items: start;
            gap: 10px;
        }

        .error-message svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            fill: #c00;
            margin-top: 2px;
        }

        .btn-reset {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
        }

        .btn-reset:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.4);
        }

        .btn-reset:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-reset:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-reset.loading .loading-spinner {
            display: block;
        }

        .btn-reset.loading .btn-text {
            display: none;
        }

        .back-to-login {
            text-align: center;
            margin-top: 25px;
        }

        .back-to-login a {
            color: #1e3c72;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .back-to-login a:hover {
            gap: 12px;
        }

        .back-to-login svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #0c5460;
        }

        .info-box strong {
            display: block;
            margin-bottom: 8px;
            color: #004085;
        }

        .info-box ul {
            margin: 10px 0 0 20px;
        }

        .info-box li {
            margin-bottom: 5px;
        }

        .forgot-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 0.85rem;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .forgot-container {
                flex-direction: column;
            }

            .branding-section,
            .forgot-section {
                width: 100%;
            }

            .branding-section {
                min-height: 40vh;
                padding: 40px 30px;
            }

            .forgot-section {
                min-height: 60vh;
                padding: 30px 20px;
            }

            .branding-content h1 {
                font-size: 2rem;
            }

            .branding-content .subtitle {
                font-size: 1.15rem;
            }

            .college-logo {
                width: 180px;
                height: 180px;
                margin-bottom: 25px;
                padding: 20px;
            }
        }

        @media (max-width: 600px) {
            .branding-section {
                min-height: 35vh;
                padding: 30px 20px;
            }

            .forgot-section {
                min-height: 65vh;
                padding: 25px 20px;
            }

            .branding-content h1 {
                font-size: 1.75rem;
            }

            .branding-content .subtitle {
                font-size: 1.05rem;
            }

            .branding-content .tagline {
                font-size: 0.9rem;
            }

            .college-logo {
                width: 150px;
                height: 150px;
                padding: 18px;
            }

            .forgot-header h2 {
                font-size: 1.75rem;
            }

            .forgot-box {
                max-width: 100%;
            }
        }
    </style>
    <script>
        function handleSubmit(event) {
            const form = event.target;
            const submitBtn = form.querySelector('.btn-reset');
            const email = form.querySelector('#email').value.trim();

            if (!email) {
                event.preventDefault();
                return false;
            }

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        }

        window.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });

        window.addEventListener('pageshow', function(event) {
            const submitBtn = document.querySelector('.btn-reset');
            if (submitBtn) {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });
    </script>
</head>
<body>
    <div class="forgot-container">
        <!-- Left Side - Branding -->
        <div class="branding-section">
            <div class="branding-content">
                <div class="college-logo">
                    <img src="assets/images/norzagaray-college-logo.png" alt="Norzagaray College Logo">
                </div>
                <h1>Norzagaray College</h1>
                <p class="subtitle">IT Assistance Desk</p>
                <p class="tagline">Reset your password securely</p>
            </div>
        </div>

        <!-- Right Side - Forgot Password Form -->
        <div class="forgot-section">
            <div class="forgot-box">
                <div class="forgot-header">
                    <div class="forgot-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                        </svg>
                    </div>
                    <h2>Forgot Password?</h2>
                    <p>Enter your email address and we'll send you instructions to reset your password.</p>
                </div>

                <?php if ($message): ?>
                    <?php if ($message_type === 'success'): ?>
                        <div class="success-message">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            <span><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="error-message">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                            </svg>
                            <span><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="POST" onsubmit="handleSubmit(event)">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                </svg>
                            </span>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="Enter your registered email"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="info-box">
                        <strong>📧 What happens next?</strong>
                        <ul>
                            <li>Check your email inbox for a password reset link</li>
                            <li>The link will be valid for 1 hour</li>
                            <li>Click the link to create a new password</li>
                            <li>Check your spam folder if you don't see the email</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn-reset">
                        <span class="btn-text">Send Reset Link</span>
                        <div class="loading-spinner"></div>
                    </button>

                    <div class="back-to-login">
                        <a href="login.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                            </svg>
                            Back to Login
                        </a>
                    </div>

                    <div class="forgot-footer">
                        &copy; <?php echo date('Y'); ?> Norzagaray College IT Assistance Desk
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
