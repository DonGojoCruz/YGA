<?php
// Handle different include contexts
if (file_exists('../db/db_connect.php')) {
    require_once '../db/db_connect.php';
    require_once 'email_config.php';
    require_once '../vendor/autoload.php';
} else {
    require_once 'db/db_connect.php';
    require_once 'functions/email_config.php';
    require_once 'vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Email Verification System
 * Handles token generation, email sending, and verification
 */

class EmailVerification {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Generate a secure random token
     */
    private function generateToken() {
        return bin2hex(random_bytes(VERIFICATION_TOKEN_LENGTH / 2));
    }
    
    /**
     * Create verification token and store in database
     */
    public function createVerificationToken($email, $studentData) {
        try {
            // Clean email
            $email = trim(strtolower($email));
            
            // Check if email already has a pending verification
            $checkStmt = $this->conn->prepare("
                SELECT id FROM " . VERIFICATION_TABLE . " 
                WHERE email = ? AND is_used = FALSE AND expires_at > NOW()
            ");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                // Delete existing pending verification
                $deleteStmt = $this->conn->prepare("DELETE FROM " . VERIFICATION_TABLE . " WHERE email = ? AND is_used = FALSE");
                $deleteStmt->bind_param("s", $email);
                $deleteStmt->execute();
            }
            
            // Generate new token
            $token = $this->generateToken();
            $expiresAt = date('Y-m-d H:i:s', time() + VERIFICATION_TOKEN_EXPIRY);
            $studentDataJson = json_encode($studentData);
            
            // Insert new verification token
            $insertStmt = $this->conn->prepare("
                INSERT INTO " . VERIFICATION_TABLE . " (email, token, student_data, expires_at) 
                VALUES (?, ?, ?, ?)
            ");
            $insertStmt->bind_param("ssss", $email, $token, $studentDataJson, $expiresAt);
            
            if ($insertStmt->execute()) {
                return [
                    'status' => 'success',
                    'token' => $token,
                    'expires_at' => $expiresAt
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Failed to create verification token: ' . $insertStmt->error
                ];
            }
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error creating verification token: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send verification email
     */
    public function sendVerificationEmail($email, $token, $studentName) {
        try {
            $mail = new PHPMailer(true);
            
            // Enable verbose debug output (disabled for production)
            // $mail->SMTPDebug = 2; // Enable verbose debug output
            // $mail->Debugoutput = 'error_log'; // Send debug output to error log
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Additional SMTP settings for Gmail
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Recipients
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($email, $studentName);
            $mail->addReplyTo(REPLY_TO_EMAIL, REPLY_TO_NAME);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = VERIFICATION_SUBJECT;
            
            $verificationUrl = VERIFICATION_BASE_URL . '?token=' . $token;
            
            $mail->Body = $this->getVerificationEmailTemplate($studentName, $verificationUrl);
            $mail->AltBody = $this->getVerificationEmailText($studentName, $verificationUrl);
            
            $mail->send();
            
            return [
                'status' => 'success',
                'message' => 'Verification email sent successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to send verification email: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify email token and complete student registration
     */
    public function verifyEmailToken($token) {
        try {
            // Get token data
            $stmt = $this->conn->prepare("
                SELECT * FROM " . VERIFICATION_TABLE . " 
                WHERE token = ? AND is_used = FALSE AND expires_at > NOW()
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid or expired verification token'
                ];
            }
            
            $tokenData = $result->fetch_assoc();
            $studentData = json_decode($tokenData['student_data'], true);
            
            if (!$studentData) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid student data in token'
                ];
            }
            
            // Mark token as used
            $updateStmt = $this->conn->prepare("
                UPDATE " . VERIFICATION_TABLE . " 
                SET is_used = TRUE, verified_at = NOW() 
                WHERE token = ?
            ");
            $updateStmt->bind_param("s", $token);
            $updateStmt->execute();
            
            // Save student to database
            $saveResult = $this->saveVerifiedStudent($studentData);
            
            if ($saveResult['status'] === 'success') {
                // Send confirmation email
                $this->sendConfirmationEmail($tokenData['email'], $studentData['first_name'] . ' ' . $studentData['last_name']);
                
                return [
                    'status' => 'success',
                    'message' => 'Email verified and student registered successfully!',
                    'student_id' => $saveResult['student_id']
                ];
            } else {
                return $saveResult;
            }
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error verifying email: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Save verified student to database
     */
    private function saveVerifiedStudent($studentData) {
        try {
            // Get section_id if grade_level and section are provided
            $sectionId = null;
            if (!empty($studentData['grade_level']) && !empty($studentData['section'])) {
                $sectionQuery = $this->conn->prepare("SELECT id FROM section WHERE grade_level = ? AND section = ?");
                $sectionQuery->bind_param("ss", $studentData['grade_level'], $studentData['section']);
                $sectionQuery->execute();
                $sectionResult = $sectionQuery->get_result();
                
                if ($sectionResult->num_rows > 0) {
                    $sectionRow = $sectionResult->fetch_assoc();
                    $sectionId = $sectionRow['id'];
                }
            }
            
            // Handle photo upload if exists
            $photoPath = null;
            if (isset($studentData['photo_path']) && !empty($studentData['photo_path'])) {
                $photoPath = $studentData['photo_path'];
            }
            
            // Insert student with email verified
            $sql = "INSERT INTO students 
                (rfid_tag, lrn, last_name, first_name, middle_initial, birthdate, gender, guardian, email, grade_level, section_id, photo_path, email_verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [
                    'status' => 'error',
                    'message' => 'SQL prepare failed: ' . $this->conn->error
                ];
            }
            
            $stmt->bind_param("ssssssssssis",
                $studentData['rfid_tag'],
                $studentData['lrn'],
                $studentData['last_name'],
                $studentData['first_name'],
                $studentData['middle_initial'],
                $studentData['birthdate'],
                $studentData['gender'],
                $studentData['guardian'],
                $studentData['email'],
                $studentData['grade_level'],
                $sectionId,
                $photoPath
            );
            
            if ($stmt->execute()) {
                return [
                    'status' => 'success',
                    'message' => 'Student saved successfully!',
                    'student_id' => $this->conn->insert_id
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Failed to save student: ' . $stmt->error
                ];
            }
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error saving student: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send confirmation email after successful verification
     */
    private function sendConfirmationEmail($email, $studentName) {
        try {
            $mail = new PHPMailer(true);
            
            // Enable verbose debug output (disabled for production)
            // $mail->SMTPDebug = 2; // Enable verbose debug output
            // $mail->Debugoutput = 'error_log'; // Send debug output to error log
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Additional SMTP settings for Gmail
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Recipients
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($email, $studentName);
            $mail->addReplyTo(REPLY_TO_EMAIL, REPLY_TO_NAME);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = VERIFICATION_SUCCESS_SUBJECT;
            $mail->Body = $this->getConfirmationEmailTemplate($studentName);
            $mail->AltBody = $this->getConfirmationEmailText($studentName);
            
            $mail->send();
            
        } catch (Exception $e) {
            // Log error but don't fail the verification process
            error_log('Failed to send confirmation email: ' . $e->getMessage());
        }
    }
    
    /**
     * Get verification email HTML template
     */
    private function getVerificationEmailTemplate($studentName, $verificationUrl) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .button { display: inline-block; background: #4CAF50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .button:hover { background: #45a049; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Email Verification Required</h1>
                </div>
                <div class='content'>
                    <h2>Hello {$studentName}!</h2>
                    <p>Thank you for registering in our Class Management System. To complete your registration, please verify your email address by clicking the button below:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$verificationUrl}' class='button'>Verify Email Address</a>
                    </div>
                    
                    <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 3px;'>{$verificationUrl}</p>
                    
                    <p><strong>Important:</strong> This verification link will expire in 24 hours for security reasons.</p>
                    
                    <p>If you didn't register for this account, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get verification email text version
     */
    private function getVerificationEmailText($studentName, $verificationUrl) {
        return "
Email Verification Required

Hello {$studentName}!

Thank you for registering in our Class Management System. To complete your registration, please verify your email address by visiting the following link:

{$verificationUrl}

Important: This verification link will expire in 24 hours for security reasons.

If you didn't register for this account, please ignore this email.

This is an automated message. Please do not reply to this email.
        ";
    }
    
    /**
     * Get confirmation email HTML template
     */
    private function getConfirmationEmailTemplate($studentName) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verified Successfully</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .success { color: #4CAF50; font-weight: bold; font-size: 18px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Email Verified Successfully!</h1>
                </div>
                <div class='content'>
                    <h2>Hello {$studentName}!</h2>
                    <p class='success'>✓ Your email has been successfully verified!</p>
                    
                    <p>Your student registration is now complete. You can now access all features of the Class Management System.</p>
                    
                    <p>Thank you for using our system!</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get confirmation email text version
     */
    private function getConfirmationEmailText($studentName) {
        return "
Email Verified Successfully!

Hello {$studentName}!

✓ Your email has been successfully verified!

Your student registration is now complete. You can now access all features of the Class Management System.

Thank you for using our system!

This is an automated message. Please do not reply to this email.
        ";
    }
}
?>
