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

$error = '';
$success = '';

// Check for password reset success
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = 'Your password has been reset successfully. Please login with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            // Allow login with either username or email
            $stmt = $pdo->prepare("SELECT user_id, username, faculty_name, email, password, is_admin, is_active, first_login, account_expires_at FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $username, 'email' => $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Check if account is active
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                    $error = 'Your account has been disabled. Please contact the administrator.';
                } 
                // Check if account expired (unconfigured after 24 hours)
                elseif (isset($user['first_login']) && $user['first_login'] == 1 && 
                        isset($user['account_expires_at']) && 
                        strtotime($user['account_expires_at']) < time()) {
                    $error = 'Your account has expired. You did not configure it within 24 hours. Please contact the administrator.';
                } 
                else {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['faculty_name'] = $user['faculty_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    $_SESSION['first_login'] = $user['first_login'];
                    
                    // Redirect based on first login status
                    if (isset($user['first_login']) && $user['first_login'] == 1) {
                        // First time login - force to settings page
                        $_SESSION['info'] = 'Welcome! Please complete your account setup by updating your credentials.';
                        if ($user['is_admin']) {
                            header("Location: admin/settings.php");
                        } else {
                            header("Location: user/settings.php");
                        }
                    } else {
                        // Regular login
                        if ($user['is_admin']) {
                            header("Location: admin/dashboard.php");
                        } else {
                            header("Location: user/concernlist.php");
                        }
                    }
                    exit();
                }
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
            error_log($e->getMessage());
        }
    } else {
        $error = 'Please enter your username/email and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Norzagaray College IT Assistance Desk</title>
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

        .login-container {
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

        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
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

        /* Right Side - Login Form */
        .login-section {
            width: 50%;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .login-box {
            width: 100%;
            max-width: 440px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-header h2 {
            color: #1e3c72;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            color: #666;
            font-size: 0.95rem;
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

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #1e3c72;
        }

        .remember-me label {
            margin: 0;
            cursor: pointer;
            color: #555;
            font-weight: 400;
        }

        .forgot-password {
            color: #1e3c72;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid #c00;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid #28a745;
        }

        .btn-login {
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

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.4);
        }

        .btn-login:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-login:disabled {
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

        .btn-login.loading .loading-spinner {
            display: block;
        }

        .btn-login.loading .btn-text {
            display: none;
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 0.85rem;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .login-container {
                flex-direction: column;
            }

            .branding-section,
            .login-section {
                width: 100%;
            }

            .branding-section {
                min-height: 40vh;
                padding: 40px 30px;
            }

            .login-section {
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

            .login-section {
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

            .login-header h2 {
                font-size: 1.75rem;
            }

            .login-box {
                max-width: 100%;
            }
        }
    </style>
    <script>
        // Toggle password visibility
        function togglePassword(event) {
            event.preventDefault();
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('togglePassword');
            
            if (passwordInput && toggleBtn) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleBtn.innerHTML = '&#128586;'; // 🙊 closed eyes
                    toggleBtn.setAttribute('aria-label', 'Hide password');
                } else {
                    passwordInput.type = 'password';
                    toggleBtn.innerHTML = '&#128065;'; // 👁 open eye
                    toggleBtn.setAttribute('aria-label', 'Show password');
                }
            }
            return false;
        }

        // Form submission with loading state
        function handleSubmit(event) {
            const form = event.target;
            const submitBtn = form.querySelector('.btn-login');
            const username = form.querySelector('#username').value.trim();
            const password = form.querySelector('#password').value.trim();

            if (!username || !password) {
                event.preventDefault();
                return false;
            }

            // Add loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        }

        // Auto-focus username field on page load
        window.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('username');
            if (usernameInput && !usernameInput.value) {
                usernameInput.focus();
            }

            // Attach password toggle event listener
            const toggleBtn = document.getElementById('togglePassword');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const passwordInput = document.getElementById('password');
                    if (passwordInput) {
                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            // Change to eye-slash icon
                            this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
                            this.setAttribute('aria-label', 'Hide password');
                            this.setAttribute('title', 'Hide password');
                        } else {
                            passwordInput.type = 'password';
                            // Change to eye icon
                            this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
                            this.setAttribute('aria-label', 'Show password');
                            this.setAttribute('title', 'Show password');
                        }
                    }
                });
            }
        });

        // Remove loading state if form returns with error
        window.addEventListener('pageshow', function(event) {
            const submitBtn = document.querySelector('.btn-login');
            if (submitBtn) {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });
    </script>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="branding-section">
            <div class="branding-content">
                <div class="college-logo">
                    <img src="assets/images/norzagaray-college-logo.png" alt="Norzagaray College Logo">
                </div>
                <h1>Norzagaray College</h1>
                <p class="subtitle">IT Assistance Desk</p>
                <p class="tagline">Your trusted partner for technical support and IT solutions</p>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-section">
            <div class="login-box">
                <div class="login-header">
                    <h2>Sign In</h2>
                    <p>Enter your credentials to continue</p>
                </div>

                <?php if ($success): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" onsubmit="handleSubmit(event)">
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </span>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-control" 
                                placeholder="Enter your username or email"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
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
                                placeholder="Enter your password"
                                required
                            >
                            <button 
                                type="button" 
                                class="password-toggle" 
                                id="togglePassword"
                                aria-label="Show password"
                                title="Show password"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="remember-forgot">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn-login">
                        <span class="btn-text">Login</span>
                        <div class="loading-spinner"></div>
                    </button>

                    <div class="login-footer">
                        &copy; <?php echo date('Y'); ?> Norzagaray College IT Assistance Desk
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
