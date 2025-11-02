<?php
session_start();
require_once '../includes/config.php';

// Check admin status
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found";
    header("Location: dashboard.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid form submission";
        header("Location: settings.php");
        exit();
    }

    // Update Profile Information
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $username = trim($_POST['username']);
        $faculty_name = trim($_POST['faculty_name']);
        $email = trim($_POST['email']);
        
        try {
            // Validation
            if (empty($username) || empty($faculty_name) || empty($email)) {
                throw new Exception("All fields are required");
            }
            
            if (strlen($username) < 3 || strlen($username) > 50) {
                throw new Exception("Username must be between 3 and 50 characters");
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            // Check if username is taken by another user
            if ($username !== $user['username']) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND user_id != ?");
                $stmt->execute([$username, $user_id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Username already taken by another user");
                }
            }
            
            // Check if email is taken by another user
            if ($email !== $user['email']) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Email already registered to another user");
                }
            }
            
            // Update user information
            $stmt = $pdo->prepare("UPDATE users SET username = ?, faculty_name = ?, email = ? WHERE user_id = ?");
            $stmt->execute([$username, $faculty_name, $email, $user_id]);
            
            // Update session variables
            $_SESSION['username'] = $username;
            $_SESSION['faculty_name'] = $faculty_name;
            $_SESSION['email'] = $email;
            
            // Mark account as configured if it's first login and profile was updated
            if (isset($_SESSION['first_login']) && $_SESSION['first_login'] == 1 && $faculty_name !== 'New User') {
                $stmt = $pdo->prepare("UPDATE users SET first_login = 0, account_expires_at = NULL WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['first_login'] = 0;
            }
            
            $_SESSION['success'] = "Profile updated successfully";
            header("Location: settings.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
            header("Location: settings.php");
            exit();
        }
    }
    
    // Change Password
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        try {
            // Validation
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception("All password fields are required");
            }
            
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            // Check new password length
            if (strlen($new_password) < 6) {
                throw new Exception("New password must be at least 6 characters long");
            }
            
            // Check if passwords match
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }
            
            // Check if new password is different from current
            if ($current_password === $new_password) {
                throw new Exception("New password must be different from current password");
            }
            
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            // Mark account as configured (no longer first login) and clear expiration
            if (isset($_SESSION['first_login']) && $_SESSION['first_login'] == 1) {
                $stmt = $pdo->prepare("UPDATE users SET first_login = 0, account_expires_at = NULL WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['first_login'] = 0;
                $_SESSION['success'] = "Account setup completed! Password changed successfully!";
            } else {
                $_SESSION['success'] = "Password changed successfully";
            }
            
            header("Location: settings.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
            header("Location: settings.php");
            exit();
        }
    }
}

// Refresh user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$page_title = 'Account Settings - NCITAD';
include 'base.php';
?>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-gear-fill text-blue-600"></i>
                Account Settings
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">Manage your profile and security settings</p>
        </div>
    </div>
</div>

<!-- Settings Content -->
<div class="px-4 pb-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        
        <!-- Profile Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-3">
                    <h2 class="text-white font-bold flex items-center">
                        <i class="bi bi-person-circle mr-2"></i>Profile Overview
                    </h2>
                </div>
                
                <div class="p-4">
                    <div class="flex flex-col items-center mb-4">
                        <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-4xl font-bold text-white shadow-lg mb-3">
                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($user['faculty_name']) ?></h3>
                        <p class="text-sm text-gray-500">@<?= htmlspecialchars($user['username']) ?></p>
                        <span class="mt-2 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                            <i class="bi bi-shield-check"></i> Administrator
                        </span>
                    </div>
                    
                    <div class="space-y-3 border-t pt-4">
                        <div class="flex items-center gap-2 text-sm">
                            <i class="bi bi-envelope-fill text-gray-400"></i>
                            <span class="text-gray-700 truncate"><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="bi bi-calendar-check-fill text-gray-400"></i>
                            <span class="text-gray-700">Created <?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="bi bi-clock-history text-gray-400"></i>
                            <span class="text-gray-700">Updated <?= date('M d, Y', strtotime($user['updated_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Settings Forms -->
        <div class="lg:col-span-2 space-y-4">
            
            <!-- Update Profile Form -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-green-600 px-4 py-3">
                    <h2 class="text-white font-bold flex items-center">
                        <i class="bi bi-person-lines-fill mr-2"></i>Update Profile Information
                    </h2>
                </div>
                
                <form method="POST" class="p-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="bi bi-person-badge text-green-600"></i> Username
                            </label>
                            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Used for login</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="bi bi-envelope-fill text-green-600"></i> Email Address
                            </label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Primary contact email</p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="bi bi-person-fill text-green-600"></i> Full Name
                        </label>
                        <input type="text" name="faculty_name" value="<?= htmlspecialchars($user['faculty_name']) ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Your complete name as it should appear</p>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="px-6 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold transition-colors shadow-md hover:shadow-lg flex items-center gap-2 text-sm">
                            <i class="bi bi-check-circle"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Change Password Form -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-3">
                    <h2 class="text-white font-bold flex items-center">
                        <i class="bi bi-shield-lock-fill mr-2"></i>Change Password
                    </h2>
                </div>
                
                <form method="POST" class="p-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded mb-4">
                        <div class="flex items-start">
                            <i class="bi bi-info-circle-fill text-yellow-600 mr-2 mt-0.5"></i>
                            <div>
                                <p class="font-semibold text-yellow-800 text-sm">Security Tip</p>
                                <p class="text-xs text-yellow-700 mt-1">Use a strong password with at least 6 characters, including letters, numbers, and symbols.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="bi bi-key-fill text-orange-600"></i> Current Password
                        </label>
                        <input type="password" name="current_password"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Enter your current password"
                               required>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="bi bi-lock-fill text-orange-600"></i> New Password
                            </label>
                            <input type="password" name="new_password" id="new_password"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                   placeholder="Enter new password"
                                   minlength="6"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="bi bi-shield-check text-orange-600"></i> Confirm New Password
                            </label>
                            <input type="password" name="confirm_password" id="confirm_password"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                   placeholder="Confirm new password"
                                   minlength="6"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Must match new password</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="px-6 py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-semibold transition-colors shadow-md hover:shadow-lg flex items-center gap-2 text-sm">
                            <i class="bi bi-shield-lock"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
        
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('new_password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        confirmPassword.dispatchEvent(new Event('input'));
    }
});
</script>

<?php include 'footer.php'; ?>
