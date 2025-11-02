<?php
session_start();
require_once 'includes/config.php';

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
$token = $_GET['token'] ?? '';
$valid_token = false;
$user_data = null;

// Verify token
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("
            SELECT pr.user_id, pr.expiry, u.username, u.faculty_name, u.email 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.user_id
            WHERE pr.token = :token
        ");
        $stmt->execute(['token' => $token]);
        $reset_data = $stmt->fetch();
        
        if ($reset_data) {
            // Check if token has expired
            if (strtotime($reset_data['expiry']) > time()) {
                $valid_token = true;
                $user_data = $reset_data;
            } else {
                $message = 'This password reset link has expired. Please request a new one.';
                $message_type = 'error';
            }
        } else {
            $message = 'Invalid password reset link. Please check the link or request a new one.';
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = 'An error occurred. Please try again later.';
        $message_type = 'error';
        error_log($e->getMessage());
    }
} else {
    $message = 'No reset token provided. Please use the link from your email.';
    $message_type = 'error';
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = 'Please fill in all fields.';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $message_type = 'error';
    } else {
        try {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update user's password
            $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
            $updateStmt->execute([
                'password' => $hashed_password,
                'user_id' => $user_data['user_id']
            ]);
            
            // Delete the used reset token
            $deleteStmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
            $deleteStmt->execute(['token' => $token]);
            
            // Set success message and redirect flag
            $_SESSION['password_reset_success'] = true;
            header("Location: login.php?reset=success");
            exit();
        } catch (PDOException $e) {
            $message = 'An error occurred while updating your password. Please try again.';
            $message_type = 'error';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Norzagaray College IT Assistance Desk</title>
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

        .reset-container {
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

        /* Right Side - Reset Password Form */
        .reset-section {
            width: 50%;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            overflow-y: auto;
        }

        .reset-box {
            width: 100%;
            max-width: 440px;
        }

        .reset-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .reset-icon {
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

        .reset-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }

        .reset-header h2 {
            color: #1e3c72;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .reset-header p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .user-info-box {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 8px;
            text-align: center;
        }

        .user-info-box strong {
            display: block;
            color: #1e3c72;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .user-info-box span {
            color: #666;
            font-size: 0.9rem;
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

        .input-wrapper .form-control {
            padding-right: 45px;
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

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 6px;
            line-height: 1;
            transition: color 0.2s;
            z-index: 10;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:focus {
            outline: none;
        }

        .password-toggle svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
            display: block;
        }

        .password-toggle:hover {
            color: #1e3c72;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 0.85rem;
        }

        .strength-meter {
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
        }

        .strength-meter-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 3px;
        }

        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }

        .password-requirements {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 12px 15px;
            margin-top: 15px;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .password-requirements ul {
            margin: 8px 0 0 20px;
            color: #0c5460;
        }

        .password-requirements li {
            margin-bottom: 4px;
        }

        .requirement-met {
            color: #28a745;
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
            margin-top: 10px;
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

        .reset-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 0.85rem;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .reset-container {
                flex-direction: column;
            }

            .branding-section,
            .reset-section {
                width: 100%;
            }

            .branding-section {
                min-height: 40vh;
                padding: 40px 30px;
            }

            .reset-section {
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

            .reset-section {
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

            .reset-header h2 {
                font-size: 1.75rem;
            }

            .reset-box {
                max-width: 100%;
            }
        }
    </style>
    <script>
        function togglePassword(fieldId, btnId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleBtn = document.getElementById(btnId);
            
            if (passwordInput && toggleBtn) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
                    toggleBtn.setAttribute('title', 'Hide password');
                } else {
                    passwordInput.type = 'password';
                    toggleBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
                    toggleBtn.setAttribute('title', 'Show password');
                }
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthFill = document.querySelector('.strength-meter-fill');
            const strengthText = document.getElementById('strength-text');
            
            if (!password) {
                strengthFill.className = 'strength-meter-fill';
                strengthFill.style.width = '0%';
                strengthText.textContent = '';
                return;
            }

            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            strengthFill.className = 'strength-meter-fill';
            
            if (strength <= 2) {
                strengthFill.classList.add('strength-weak');
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#dc3545';
            } else if (strength === 3) {
                strengthFill.classList.add('strength-medium');
                strengthText.textContent = 'Medium strength';
                strengthText.style.color = '#ffc107';
            } else {
                strengthFill.classList.add('strength-strong');
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#28a745';
            }
        }

        function validatePasswords() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.querySelector('.btn-reset');
            
            if (password && confirmPassword) {
                if (password === confirmPassword && password.length >= 6) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            }
        }

        function handleSubmit(event) {
            const form = event.target;
            const submitBtn = form.querySelector('.btn-reset');
            const password = form.querySelector('#password').value;
            const confirmPassword = form.querySelector('#confirm_password').value;

            if (!password || !confirmPassword || password !== confirmPassword || password.length < 6) {
                event.preventDefault();
                return false;
            }

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        }

        window.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength();
                    validatePasswords();
                });
            }
            
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', validatePasswords);
            }

            // Password toggle buttons
            const toggleBtn1 = document.getElementById('togglePassword1');
            const toggleBtn2 = document.getElementById('togglePassword2');
            
            if (toggleBtn1) {
                toggleBtn1.addEventListener('click', function(e) {
                    e.preventDefault();
                    togglePassword('password', 'togglePassword1');
                });
            }
            
            if (toggleBtn2) {
                toggleBtn2.addEventListener('click', function(e) {
                    e.preventDefault();
                    togglePassword('confirm_password', 'togglePassword2');
                });
            }
        });

        window.addEventListener('pageshow', function(event) {
            const submitBtn = document.querySelector('.btn-reset');
            if (submitBtn) {
                submitBtn.classList.remove('loading');
            }
        });
    </script>
</head>
<body>
    <div class="reset-container">
        <!-- Left Side - Branding -->
        <div class="branding-section">
            <div class="branding-content">
                <div class="college-logo">
                    <img src="assets/images/norzagaray-college-logo.png" alt="Norzagaray College Logo">
                </div>
                <h1>Norzagaray College</h1>
                <p class="subtitle">IT Assistance Desk</p>
                <p class="tagline">Create a new secure password</p>
            </div>
        </div>

        <!-- Right Side - Reset Password Form -->
        <div class="reset-section">
            <div class="reset-box">
                <div class="reset-header">
                    <div class="reset-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                        </svg>
                    </div>
                    <h2>Reset Password</h2>
                    <p>Enter your new password below</p>
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
                        <div class="back-to-login">
                            <a href="forgot-password.php">Request a new reset link</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($valid_token && $user_data): ?>
                    <div class="user-info-box">
                        <strong><?php echo htmlspecialchars($user_data['faculty_name']); ?></strong>
                        <span><?php echo htmlspecialchars($user_data['email']); ?></span>
                    </div>

                    <form method="POST" onsubmit="handleSubmit(event)">
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <div class="input-wrapper">
                                <span class="input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                                    </svg>
                                </span>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-control" 
                                    placeholder="Enter new password"
                                    required
                                    minlength="6"
                                    autofocus
                                >
                                <button 
                                    type="button" 
                                    class="password-toggle" 
                                    id="togglePassword1"
                                    title="Show password"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-meter-fill"></div>
                                </div>
                                <span id="strength-text" style="font-size: 0.85rem; margin-top: 5px; display: block;"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="input-wrapper">
                                <span class="input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                                    </svg>
                                </span>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="form-control" 
                                    placeholder="Confirm new password"
                                    required
                                    minlength="6"
                                >
                                <button 
                                    type="button" 
                                    class="password-toggle" 
                                    id="togglePassword2"
                                    title="Show password"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="password-requirements">
                            <strong>Password Requirements:</strong>
                            <ul>
                                <li>At least 6 characters long</li>
                                <li>Mix of uppercase and lowercase letters (recommended)</li>
                                <li>Include numbers (recommended)</li>
                                <li>Include special characters (recommended)</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn-reset" disabled>
                            <span class="btn-text">Reset Password</span>
                            <div class="loading-spinner"></div>
                        </button>
                    </form>
                <?php endif; ?>

                <div class="back-to-login">
                    <a href="login.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                        </svg>
                        Back to Login
                    </a>
                </div>

                <div class="reset-footer">
                    &copy; <?php echo date('Y'); ?> Norzagaray College IT Assistance Desk
                </div>
            </div>
        </div>
    </div>
</body>
</html>
