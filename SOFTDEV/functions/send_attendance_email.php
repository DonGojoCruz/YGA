<?php
/**
 * Attendance Email Notification System
 * Sends email notifications for RFID attendance confirmations
 */

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

class AttendanceEmailNotification {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Check if email notification has already been sent for this student/subject/date
     */
    private function hasEmailBeenSent($studentId, $subject, $date) {
        try {
            $query = "SELECT COUNT(*) FROM attendance_email_log WHERE student_id = ? AND subject = ? AND date = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iss", $studentId, $subject, $date);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_row()[0];
            $stmt->close();
            
            return $count > 0;
        } catch (Exception $e) {
            error_log("Error checking email log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log that email has been sent
     */
    private function logEmailSent($studentId, $subject, $date, $studentName, $studentEmail) {
        try {
            $query = "INSERT INTO attendance_email_log (student_id, subject, date, student_name, student_email, sent_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("issss", $studentId, $subject, $date, $studentName, $studentEmail);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error logging email sent: " . $e->getMessage());
        }
    }
    
    /**
     * Get student email from database
     */
    private function getStudentEmail($studentId) {
        try {
            $query = "SELECT email FROM students WHERE student_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row ? $row['email'] : null;
        } catch (Exception $e) {
            error_log("Error getting student email: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Send attendance confirmation email
     */
    public function sendAttendanceConfirmation($rfidTag, $subject, $grade, $section) {
        try {
            // Get student information
            $studentQuery = "
                SELECT s.student_id, s.first_name, s.last_name, s.email, s.lrn
                FROM students s 
                INNER JOIN section sec ON s.section_id = sec.id 
                WHERE s.rfid_tag = ? AND sec.grade_level = ? AND sec.section = ?
            ";
            $stmt = $this->conn->prepare($studentQuery);
            $stmt->bind_param("sss", $rfidTag, $grade, $section);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            $stmt->close();
            
            if (!$student) {
                error_log("Student not found for RFID: $rfidTag");
                return false;
            }
            
            $studentId = $student['student_id'];
            $studentName = $student['first_name'] . ' ' . $student['last_name'];
            $studentEmail = $student['email'];
            $lrn = $student['lrn'];
            
            // Set Manila timezone
            date_default_timezone_set('Asia/Manila');
            $date = date('Y-m-d');
            $time = date('H:i:s');
            
            // Check if email has already been sent today for this subject
            if ($this->hasEmailBeenSent($studentId, $subject, $date)) {
                error_log("Email already sent for student $studentId, subject $subject, date $date");
                return true; // Return true since we don't want to send duplicate
            }
            
            // Check if student has email
            if (empty($studentEmail)) {
                error_log("Student $studentName has no email address");
                return false;
            }
            
            // Send email
            $mail = new PHPMailer(true);
            
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
            $mail->addAddress($studentEmail, $studentName);
            $mail->addReplyTo(REPLY_TO_EMAIL, REPLY_TO_NAME);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "Attendance Confirmation - $subject";
            
            $mail->Body = $this->getAttendanceEmailTemplate($studentName, $subject, $grade, $section, $date, $time, $lrn);
            $mail->AltBody = $this->getAttendanceEmailText($studentName, $subject, $grade, $section, $date, $time, $lrn);
            
            $mail->send();
            
            // Log that email was sent
            $this->logEmailSent($studentId, $subject, $date, $studentName, $studentEmail);
            
            error_log("Attendance confirmation email sent to $studentName ($studentEmail) for $subject");
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send attendance email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send exit confirmation email
     */
    public function sendExitConfirmation($rfidTag, $grade, $section, $studentName = '') {
        try {
            // Get student information
            $studentQuery = "
                SELECT s.student_id, s.first_name, s.last_name, s.email, s.lrn
                FROM students s 
                INNER JOIN section sec ON s.section_id = sec.id 
                WHERE s.rfid_tag = ? AND sec.grade_level = ? AND sec.section = ?
            ";
            $stmt = $this->conn->prepare($studentQuery);
            $stmt->bind_param("sss", $rfidTag, $grade, $section);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            $stmt->close();
            
            if (!$student) {
                error_log("Student not found for exit RFID: $rfidTag");
                return false;
            }
            
            $studentId = $student['student_id'];
            $studentName = $student['first_name'] . ' ' . $student['last_name'];
            $studentEmail = $student['email'];
            $lrn = $student['lrn'];
            
            // Set Manila timezone
            date_default_timezone_set('Asia/Manila');
            $date = date('Y-m-d');
            $time = date('H:i:s');
            
            // Check if email has already been sent today for exit
            if ($this->hasEmailBeenSent($studentId, 'EXIT', $date)) {
                error_log("Exit email already sent for student $studentId, date $date");
                return true; // Return true since we don't want to send duplicate
            }
            
            // Check if student has email
            if (empty($studentEmail)) {
                error_log("Student $studentName has no email address");
                return false;
            }
            
            // Send email
            $mail = new PHPMailer(true);
            
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
            $mail->addAddress($studentEmail, $studentName);
            $mail->addReplyTo(REPLY_TO_EMAIL, REPLY_TO_NAME);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "Exit Confirmation - School Departure";
            
            $mail->Body = $this->getExitEmailTemplate($studentName, $grade, $section, $date, $time, $lrn);
            $mail->AltBody = $this->getExitEmailText($studentName, $grade, $section, $date, $time, $lrn);
            
            $mail->send();
            
            // Log that email was sent
            $this->logEmailSent($studentId, 'EXIT', $date, $studentName, $studentEmail);
            
            error_log("Exit confirmation email sent to $studentName ($studentEmail)");
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send exit email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get attendance email HTML template
     */
    private function getAttendanceEmailTemplate($studentName, $subject, $grade, $section, $date, $time, $lrn) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Attendance Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: 300; }
                .content { padding: 30px; }
                .attendance-card { background: #f8f9fa; border-left: 4px solid #28a745; padding: 20px; margin: 20px 0; border-radius: 5px; }
                .attendance-details { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0; }
                .detail-item { background: white; padding: 15px; border-radius: 5px; border: 1px solid #e9ecef; }
                .detail-label { font-weight: bold; color: #6c757d; font-size: 14px; margin-bottom: 5px; }
                .detail-value { color: #495057; font-size: 16px; }
                .success-icon { color: #28a745; font-size: 24px; margin-right: 10px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
                .button { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Attendance Confirmed</h1>
                </div>
                <div class='content'>
                    <h2>Hello {$studentName}!</h2>
                    <p>Your attendance has been successfully recorded for today's class.</p>
                    
                    <div class='attendance-card'>
                        <h3><span class='success-icon'>✓</span>Attendance Details</h3>
                        <div class='attendance-details'>
                            <div class='detail-item'>
                                <div class='detail-label'>Subject</div>
                                <div class='detail-value'>{$subject}</div>
                            </div>
                            <div class='detail-item'>
                                <div class='detail-label'>Grade & Section</div>
                                <div class='detail-value'>Grade {$grade} - {$section}</div>
                            </div>
                            <div class='detail-item'>
                                <div class='detail-label'>Date</div>
                                <div class='detail-value'>{$date}</div>
                            </div>
                            <div class='detail-item'>
                                <div class='detail-label'>Time</div>
                                <div class='detail-value'>{$time}</div>
                            </div>
                            <div class='detail-item'>
                                <div class='detail-label'>LRN</div>
                                <div class='detail-value'>{$lrn}</div>
                            </div>
                            <div class='detail-item'>
                                <div class='detail-label'>Status</div>
                                <div class='detail-value' style='color: #28a745; font-weight: bold;'>PRESENT</div>
                            </div>
                        </div>
                    </div>
                    
                    <p><strong>Note:</strong> This is an automated confirmation. If you have any questions about your attendance, please contact your teacher or school administration.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from Young Generation Academy.<br>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get attendance email text version
     */
    private function getAttendanceEmailText($studentName, $subject, $grade, $section, $date, $time, $lrn) {
        return "
ATTENDANCE CONFIRMATION

Hello {$studentName}!

Your attendance has been successfully recorded for today's class.

ATTENDANCE DETAILS:
- Subject: {$subject}
- Grade & Section: Grade {$grade} - {$section}
- Date: {$date}
- Time: {$time}
- LRN: {$lrn}
- Status: PRESENT

Note: This is an automated confirmation. If you have any questions about your attendance, please contact your teacher or school administration.

This is an automated message from Young Generation Academy.
Please do not reply to this email.
        ";
    }
    
    /**
     * Get exit email HTML template
     */
    private function getExitEmailTemplate($studentName, $grade, $section, $date, $time, $lrn) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Exit Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: 300; }
                .content { padding: 30px; }
                .exit-card { background: #fff5f5; border-left: 4px solid #ff6b6b; padding: 20px; margin: 20px 0; border-radius: 5px; }
                .exit-details { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0; }
                .detail-item { background: white; padding: 15px; border-radius: 5px; border: 1px solid #e9ecef; }
                .detail-label { font-weight: bold; color: #6c757d; font-size: 14px; margin-bottom: 5px; }
                .detail-value { color: #495057; font-size: 16px; }
                .exit-icon { color: #ff6b6b; font-size: 24px; margin-right: 10px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚪 Exit Confirmed</h1>
                </div>
                <div class='content'>
                    <h2>Hello {$studentName}!</h2>
                    <p>Your school departure has been successfully recorded.</p>
                    
                    <div class='exit-card'>
                        <h3><span class='exit-icon'>🚪</span>Exit Details</h3>
                        <div class='exit-details'>
                            <div class='detail-item'>
                                <div class='detail-label'>Type</div>
                                <div class='detail-value'>School Departure</div>
                            </div>
                            <div class='detail-item'>
                                <div class='detail-label'>Grade & Section</div>
                                <div class='detail-value'>Grade {$grade} - {$section}</div>
                            </div>
                            <div class='detail-item'>
                                <div class='detail-label'>Date</div>
                                <div class='detail-value'>{$date}</div>
                            </div>
                            <div class='detail-item'>
                                <div class='detail-label'>Time</div>
                                <div class='detail-value'>{$time}</div>
                            </div>
                            <div class='detail-item'>
                                <div class='detail-label'>LRN</div>
                                <div class='detail-value'>{$lrn}</div>
                            </div>
                            <div class='detail-item'>
                                <div class='detail-label'>Status</div>
                                <div class='detail-value' style='color: #ff6b6b; font-weight: bold;'>DEPARTED</div>
                            </div>
                        </div>
                    </div>
                    
                    <p><strong>Note:</strong> This is an automated confirmation. If you have any questions about your exit record, please contact your teacher or school administration.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from Young Generation Academy.<br>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get exit email text version
     */
    private function getExitEmailText($studentName, $grade, $section, $date, $time, $lrn) {
        return "
EXIT CONFIRMATION

Hello {$studentName}!

Your school departure has been successfully recorded.

EXIT DETAILS:
- Type: School Departure
- Grade & Section: Grade {$grade} - {$section}
- Date: {$date}
- Time: {$time}
- LRN: {$lrn}
- Status: DEPARTED

Note: This is an automated confirmation. If you have any questions about your exit record, please contact your teacher or school administration.

This is an automated message from Young Generation Academy.
Please do not reply to this email.
        ";
    }
}

// Handle direct API calls
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['rfid_tag']) || !isset($input['grade']) || !isset($input['section'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    $emailNotification = new AttendanceEmailNotification($conn);
    
    // Check if this is an exit logging email
    if (isset($input['type']) && $input['type'] === 'exit') {
        $result = $emailNotification->sendExitConfirmation(
            $input['rfid_tag'],
            $input['grade'],
            $input['section'],
            $input['student_name'] ?? ''
        );
    } else {
        // Regular attendance confirmation
        if (!isset($input['subject'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Subject parameter required for attendance confirmation']);
            exit;
        }
        
        $result = $emailNotification->sendAttendanceConfirmation(
            $input['rfid_tag'],
            $input['subject'],
            $input['grade'],
            $input['section']
        );
    }
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
}
?>
