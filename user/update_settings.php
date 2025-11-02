<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: settings.php");
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    // Update Profile Information
    $username = trim($_POST['username']);
    $faculty_name = trim($_POST['faculty_name']);
    $email = trim($_POST['email']);

    // Validate inputs
    if (empty($username) || empty($faculty_name) || empty($email)) {
        $_SESSION['error'] = 'All fields are required.';
        header("Location: settings.php");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format.';
        header("Location: settings.php");
        exit();
    }

    // Check if username already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $stmt->execute([$username, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Username already taken. Please choose another.';
        header("Location: settings.php");
        exit();
    }

    // Check if email already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Email already in use. Please use another email.';
        header("Location: settings.php");
        exit();
    }

    // Update user information
    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, faculty_name = ?, email = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->execute([$username, $faculty_name, $email, $_SESSION['user_id']]);
        
        // Update session variables
        $_SESSION['username'] = $username;
        $_SESSION['faculty_name'] = $faculty_name;
        $_SESSION['email'] = $email;
        
        $_SESSION['success'] = 'Profile updated successfully!';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error updating profile. Please try again.';
    }

    header("Location: settings.php");
    exit();

} elseif ($action === 'change_password') {
    // Change Password
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = 'All password fields are required.';
        header("Location: settings.php");
        exit();
    }

    // Check password length
    if (strlen($new_password) < 6) {
        $_SESSION['error'] = 'New password must be at least 6 characters long.';
        header("Location: settings.php");
        exit();
    }

    // Check if new passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'New passwords do not match.';
        header("Location: settings.php");
        exit();
    }

    // Get current password hash from database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = 'Current password is incorrect.';
        header("Location: settings.php");
        exit();
    }

    // Check if new password is same as current password
    if (password_verify($new_password, $user['password'])) {
        $_SESSION['error'] = 'New password must be different from current password.';
        header("Location: settings.php");
        exit();
    }

    // Hash new password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password
    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->execute([$new_password_hash, $_SESSION['user_id']]);
        
        // Mark account as configured (no longer first login) and clear expiration
        if (isset($_SESSION['first_login']) && $_SESSION['first_login'] == 1) {
            $stmt = $pdo->prepare("UPDATE users SET first_login = 0, account_expires_at = NULL WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $_SESSION['first_login'] = 0;
            $_SESSION['success'] = 'Account setup completed! Password changed successfully!';
        } else {
            $_SESSION['success'] = 'Password changed successfully!';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error changing password. Please try again.';
    }

    header("Location: settings.php");
    exit();

} else {
    // Invalid action
    header("Location: settings.php");
    exit();
}
?>
