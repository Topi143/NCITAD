<?php
/**
 * Email Configuration for NCITAD System
 * 
 * This file contains email settings for sending system notifications.
 * 
 * SETUP INSTRUCTIONS:
 * 
 * Option 1: Gmail SMTP (Recommended for Testing)
 * ------------------------------------------------
 * 1. Go to https://myaccount.google.com/apppasswords
 * 2. Create an App Password for "Mail"
 * 3. Use your Gmail address and the generated App Password below
 * 
 * Option 2: Local SMTP (XAMPP Mercury)
 * ------------------------------------------------
 * 1. Configure Mercury Mail in XAMPP Control Panel
 * 2. Set SMTP_HOST to 'localhost'
 * 3. Set SMTP_PORT to 25
 * 4. Leave SMTP_USERNAME and SMTP_PASSWORD empty
 * 
 * Option 3: Other SMTP Services
 * ------------------------------------------------
 * - Outlook: smtp.office365.com:587
 * - Yahoo: smtp.mail.yahoo.com:587
 * - SendGrid, Mailgun, etc.
 */

// Email Sending Method
define('EMAIL_METHOD', 'smtp'); // 'smtp' or 'mail' (PHP mail() function)

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com'); // SMTP server address
define('SMTP_PORT', 587); // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'
define('SMTP_AUTH', true); // Enable SMTP authentication

// Use environment variables in production, hardcoded for development
// IMPORTANT: In production, set these as environment variables and use getenv()
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: 'matthewjohnsantos2004@gmail.com'); // Your email address
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: 'mhor cogd ufhj garm'); // Your email password or App Password

// Sender Information
define('EMAIL_FROM_ADDRESS', 'no-reply@ncitad.edu'); // From email address
define('EMAIL_FROM_NAME', 'NCITAD System'); // From name
define('EMAIL_REPLY_TO', 'support@ncitad.edu'); // Reply-to address

// System URLs (update for production)
define('SYSTEM_URL', 'http://localhost/ncitad'); // Base URL of your system
define('LOGIN_URL', SYSTEM_URL . '/login.php'); // Login page URL

// Email Settings
define('EMAIL_DEBUG', 0); // 0=off, 1=client messages, 2=client and server messages
define('EMAIL_CHARSET', 'UTF-8'); // Email character set

// Enable/Disable Email Sending
define('EMAIL_ENABLED', true); // Set to false to disable all email sending

?>
