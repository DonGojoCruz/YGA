<?php
/**
 * Email Configuration for PHPMailer
 * Configuration for sending verification emails
 */

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'dmmy36381@gmail.com');
define('SMTP_PASSWORD', 'gwqo zdfs pmsf yoki');
define('SMTP_ENCRYPTION', 'tls');

// Email Settings
define('FROM_EMAIL', 'dmmy36381@gmail.com');
define('FROM_NAME', 'Young Generation Academy');
define('REPLY_TO_EMAIL', 'dmmy36381@gmail.com');
define('REPLY_TO_NAME', 'Young Generation Academy');

// Verification Settings
define('VERIFICATION_TOKEN_LENGTH', 32);
define('VERIFICATION_TOKEN_EXPIRY', 24 * 60 * 60); // 24 hours in seconds
define('VERIFICATION_BASE_URL', 'http://localhost/SOFTDEV/functions/verify_direct.php');

// Email Templates
define('VERIFICATION_SUBJECT', 'Email Verification - Student Registration');
define('VERIFICATION_SUCCESS_SUBJECT', 'Email Verified Successfully - Student Registration');

// Database table for email verification tokens
define('VERIFICATION_TABLE', 'email_verification_tokens');
?>
