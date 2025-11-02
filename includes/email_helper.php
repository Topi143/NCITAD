<?php
/**
 * Email Helper Functions for NCITAD System
 * 
 * This file contains functions for sending emails using PHPMailer or PHP mail()
 */

require_once __DIR__ . '/email_config.php';

/**
 * Send email using configured method
 * 
 * @param string $to Recipient email address
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $htmlBody HTML body content
 * @param string $textBody Plain text body (optional)
 * @return array ['success' => bool, 'message' => string, 'error' => string]
 */
function sendEmail($to, $toName, $subject, $htmlBody, $textBody = '') {
    // Check if email is enabled
    if (!EMAIL_ENABLED) {
        $message = "Email sending is disabled. Email to {$to} was not sent.";
        error_log($message);
        return ['success' => false, 'message' => 'Email sending is currently disabled', 'error' => $message];
    }
    
    // Validate email address
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address: {$to}";
        error_log($message);
        return ['success' => false, 'message' => 'Invalid email address', 'error' => $message];
    }
    
    try {
        if (EMAIL_METHOD === 'smtp' && file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
            $result = sendEmailSMTP($to, $toName, $subject, $htmlBody, $textBody);
        } else {
            $result = sendEmailPHPMail($to, $toName, $subject, $htmlBody);
        }
        
        if ($result) {
            return ['success' => true, 'message' => 'Email sent successfully', 'error' => ''];
        } else {
            return ['success' => false, 'message' => 'Failed to send email', 'error' => 'Email sending failed'];
        }
    } catch (Exception $e) {
        $errorMsg = "Email Error: " . $e->getMessage();
        error_log($errorMsg);
        return ['success' => false, 'message' => 'Failed to send email', 'error' => $errorMsg];
    }
}

/**
 * Send email using PHPMailer with SMTP
 */
function sendEmailSMTP($to, $toName, $subject, $htmlBody, $textBody = '') {
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
    require_once __DIR__ . '/PHPMailer/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = EMAIL_DEBUG;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = EMAIL_CHARSET;
        
        // Timeout settings - reduced for faster failure
        $mail->Timeout = 10; // Reduced from 30 to 10 seconds
        $mail->SMTPKeepAlive = false;
        
        // Recipients
        $mail->setFrom(SMTP_USERNAME, EMAIL_FROM_NAME); // Use SMTP username as sender
        $mail->addAddress($to, $toName);
        $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        if (!empty($textBody)) {
            $mail->AltBody = $textBody;
        } else {
            // Generate plain text version from HTML
            $mail->AltBody = strip_tags($htmlBody);
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send email using PHP's mail() function
 */
function sendEmailPHPMail($to, $toName, $subject, $htmlBody) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=" . EMAIL_CHARSET . "\r\n";
    $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDRESS . ">" . "\r\n";
    $headers .= "Reply-To: " . EMAIL_REPLY_TO . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $htmlBody, $headers);
}

/**
 * Send new user credentials email
 */
function sendNewUserCredentialsEmail($email, $username, $password, $facultyName, $isAdmin) {
    $roleText = $isAdmin ? 'Administrator' : 'User';
    $subject = "Your NCITAD Account Credentials";
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 20px auto; background: white; }
            .header { background: linear-gradient(to right, #3b82f6, #2563eb); color: white; padding: 30px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .header p { margin: 10px 0 0 0; opacity: 0.9; font-size: 14px; }
            .content { padding: 30px; }
            .credentials { background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3b82f6; }
            .credential-item { margin: 15px 0; }
            .credential-label { font-weight: bold; color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
            .credential-value { font-size: 16px; color: #1f2937; font-weight: 600; font-family: 'Courier New', monospace; }
            .password-box { background: #fef3c7; padding: 20px; border-radius: 8px; border: 2px solid #fbbf24; margin: 20px 0; text-align: center; }
            .password-box .label { font-weight: bold; color: #92400e; font-size: 12px; margin-bottom: 10px; }
            .password-box .value { font-size: 24px; color: #92400e; font-weight: bold; font-family: 'Courier New', monospace; letter-spacing: 2px; }
            .password-box .note { font-size: 12px; color: #92400e; margin-top: 10px; }
            .warning { background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; }
            .warning strong { color: #991b1b; }
            .warning ul { margin: 10px 0; padding-left: 20px; }
            .warning li { margin: 5px 0; }
            .btn { display: inline-block; background: #3b82f6; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; }
            .btn:hover { background: #2563eb; }
            .footer { text-align: center; color: #6b7280; font-size: 12px; padding: 20px; border-top: 1px solid #e5e7eb; }
            @media only screen and (max-width: 600px) {
                .content { padding: 20px; }
                .password-box .value { font-size: 20px; }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Welcome to NCITAD</h1>
                <p>Your account has been created successfully</p>
            </div>
            <div class='content'>
                <p>Hello <strong>{$facultyName}</strong>,</p>
                <p>An administrator has created an account for you in the <strong>NCITAD Ticketing System</strong>. You can now access the system to submit and manage IT support requests.</p>
                
                <div class='credentials'>
                    <div class='credential-item'>
                        <div class='credential-label'>👤 Full Name</div>
                        <div class='credential-value'>{$facultyName}</div>
                    </div>
                    <div class='credential-item'>
                        <div class='credential-label'>📧 Email Address</div>
                        <div class='credential-value'>{$email}</div>
                    </div>
                    <div class='credential-item'>
                        <div class='credential-label'>🔐 Username</div>
                        <div class='credential-value'>{$username}</div>
                    </div>
                    <div class='credential-item'>
                        <div class='credential-label'>👑 Account Role</div>
                        <div class='credential-value'>{$roleText}</div>
                    </div>
                </div>
                
                <div class='password-box'>
                    <div class='label'>⚠️ TEMPORARY PASSWORD</div>
                    <div class='value'>{$password}</div>
                    <div class='note'><strong>Important:</strong> Please change this password immediately after your first login.</div>
                </div>
                
                <div style='text-align: center;'>
                    <a href='" . LOGIN_URL . "' class='btn'>🚀 Login to Your Account</a>
                </div>
                
                <div class='warning'>
                    <strong>🔒 Security Notice:</strong>
                    <ul>
                        <li>Keep your credentials confidential and secure</li>
                        <li>Change your password immediately after first login</li>
                        <li>Never share your password with anyone</li>
                        <li>Contact an administrator if you didn't request this account</li>
                    </ul>
                </div>
                
                <p>If you have any questions or need assistance, please contact your system administrator.</p>
                
                <p style='margin-top: 30px;'>Best regards,<br><strong>NCITAD Support Team</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated message from NCITAD Ticketing System.</p>
                <p>Please do not reply to this email.</p>
                <p style='margin-top: 10px; color: #9ca3af;'>© " . date('Y') . " NCITAD. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $textBody = "Welcome to NCITAD

Hello {$facultyName},

An administrator has created an account for you in the NCITAD Ticketing System.

Your Login Credentials:
- Full Name: {$facultyName}
- Email: {$email}
- Username: {$username}
- Password: {$password}
- Role: {$roleText}

Login URL: " . LOGIN_URL . "

IMPORTANT: Please change your password immediately after your first login.

Security Notice:
- Keep your credentials confidential
- Never share your password with anyone
- Contact an administrator if you didn't request this account

Best regards,
NCITAD Support Team

---
This is an automated message. Please do not reply to this email.
";
    
    return sendEmail($email, $facultyName, $subject, $htmlBody, $textBody);
}

?>
