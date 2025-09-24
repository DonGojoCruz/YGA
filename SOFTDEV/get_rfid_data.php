<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON output
require_once 'db/db_connect.php';

// Set Manila timezone
date_default_timezone_set('Asia/Manila');

// Get current target section and subject from config file
$config_file = "rfid_config.txt";
$TARGET_GRADE = '1';
$TARGET_SECTION = 'Mango';
$TARGET_SUBJECT = '';
$EXIT_LOGGING = 'false';

if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    $config_lines = explode("\n", $config_content);
    foreach ($config_lines as $line) {
        $line = trim($line);
        if (strpos($line, 'TARGET_GRADE=') === 0) {
            $TARGET_GRADE = trim(str_replace('TARGET_GRADE=', '', $line));
        }
        if (strpos($line, 'TARGET_SECTION=') === 0) {
            $TARGET_SECTION = trim(str_replace('TARGET_SECTION=', '', $line));
        }
        if (strpos($line, 'TARGET_SUBJECT=') === 0) {
            $TARGET_SUBJECT = trim(str_replace('TARGET_SUBJECT=', '', $line));
        }
        if (strpos($line, 'EXIT_LOGGING=') === 0) {
            $EXIT_LOGGING = trim(str_replace('EXIT_LOGGING=', '', $line));
        }
    }
}

// Debug: Log the loaded config
error_log("PHP Config loaded - Grade: '$TARGET_GRADE', Section: '$TARGET_SECTION', Subject: '$TARGET_SUBJECT'");

// Determine which file to read based on exit logging status
$rfid_file = '';
if ($EXIT_LOGGING === 'true') {
    // Read from detailed exit logging display file
    $rfid_file = __DIR__ . "/exit_display.txt";
} else {
    // Check if subject is assigned
    if (empty($TARGET_SUBJECT) || trim($TARGET_SUBJECT) === '') {
        // No subject assigned - check for "Choose Subject First." message
        $rfid_file = __DIR__ . "/display_message.txt";
    } else {
        // Read from subject-specific file
        $subject_key = strtolower(str_replace([' ', '-'], '_', $TARGET_SUBJECT));
        $rfid_file = __DIR__ . "/" . $subject_key . "rfid.txt";
    }
}
$latest_scan = "";
$scan_time = "";
$subject = "";
$has_scan = false;

// Debug logging
error_log("DEBUG: EXIT_LOGGING status: '" . $EXIT_LOGGING . "'");
error_log("DEBUG: Looking for RFID file: " . $rfid_file);
error_log("DEBUG: TARGET_SUBJECT: '" . $TARGET_SUBJECT . "'");
if ($EXIT_LOGGING !== 'true') {
    $subject_key = strtolower(str_replace([' ', '-'], '_', $TARGET_SUBJECT));
    if (empty($subject_key)) {
        $subject_key = 'english';
    }
    error_log("DEBUG: subject_key: '" . $subject_key . "'");
}
error_log("DEBUG: File exists: " . (file_exists($rfid_file) ? 'YES' : 'NO'));
if (file_exists($rfid_file)) {
    error_log("DEBUG: File size: " . filesize($rfid_file));
    error_log("DEBUG: File permissions: " . substr(sprintf('%o', fileperms($rfid_file)), -4));
} else {
    error_log("DEBUG: File does not exist at: " . $rfid_file);
    // Check if any similar files exist
    $dir = dirname($rfid_file);
    $files = scandir($dir);
    $rfid_files = array_filter($files, function($file) {
        return strpos($file, 'rfid.txt') !== false;
    });
    error_log("DEBUG: Other RFID files in directory: " . implode(', ', $rfid_files));
}

if (file_exists($rfid_file) && filesize($rfid_file) > 0) {
    $content = file_get_contents($rfid_file);
    error_log("DEBUG: File content: '" . $content . "'");
    if (!empty($content)) {
        // Check if this is the "Choose Subject First." message
        if (trim($content) === 'Choose Subject First.') {
            $subject = 'Choose Subject First.';
            $has_scan = true;
            error_log("DEBUG: CHOOSE SUBJECT MESSAGE - Subject: '$subject'");
        } else {
            $parts = explode('|', $content);
            error_log("DEBUG: Parts count: " . count($parts));
            
            if ($EXIT_LOGGING === 'true' && count($parts) >= 6) {
                // Exit logging detailed format: UID|TIMESTAMP|STUDENT_NAME|GRADE|SECTION|EXIT
                $latest_scan = trim($parts[0]);
                $scan_time = trim($parts[1]);
                $student_name = trim($parts[2]);
                $grade = trim($parts[3]);
                $section = trim($parts[4]);
                $subject = 'Exit Logging - ' . $student_name;
                $has_scan = true;
                error_log("DEBUG: EXIT LOGGING DETAILED - UID: '$latest_scan', Time: '$scan_time', Student: '$student_name', Grade: '$grade', Section: '$section'");
            } elseif (count($parts) >= 3) {
                // Normal format: UID|TIMESTAMP|SUBJECT
                $latest_scan = trim($parts[0]);
                $scan_time = trim($parts[1]);
                $subject = trim($parts[2]);
                $has_scan = true;
                error_log("DEBUG: NORMAL SCAN - UID: '$latest_scan', Time: '$scan_time', Subject: '$subject'");
            }
        }
    }
} else {
    error_log("DEBUG: File not found or empty");
}

// Get student information from database
$student_data = null;
$attendance_count = 0;

if ($has_scan && !empty($latest_scan)) {
    // Only check database if we have a valid scan file
    error_log("DEBUG: Looking up student with UID: '$latest_scan' for Grade: '$TARGET_GRADE', Section: '$TARGET_SECTION'");
    
    $query = "SELECT s.*, sec.section, sec.grade_level as section_grade FROM students s LEFT JOIN section sec ON s.section_id = sec.id WHERE s.rfid_tag = ? AND sec.grade_level = ? AND sec.section = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $student_data = null;
    } else {
        $stmt->bind_param("sss", $latest_scan, $TARGET_GRADE, $TARGET_SECTION);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $student_data = $result->fetch_assoc();
            
            if ($student_data) {
                error_log("DEBUG: Student found: " . $student_data['first_name'] . " " . $student_data['last_name']);
                
                // Get today's attendance count for valid student
                $today = date('Y-m-d');
                $stmt2 = $conn->prepare("SELECT COUNT(*) as count FROM attendance_logs WHERE rfid_uid = ? AND DATE(scan_time) = ?");
                if ($stmt2) {
                    $stmt2->bind_param("ss", $latest_scan, $today);
                    if ($stmt2->execute()) {
                        $result2 = $stmt2->get_result();
                        $attendance_row = $result2->fetch_assoc();
                        $attendance_count = $attendance_row ? $attendance_row['count'] : 0;
                    }
                    $stmt2->close();
                }
            } else {
                error_log("DEBUG: No student found with UID: '$latest_scan' in Grade: '$TARGET_GRADE', Section: '$TARGET_SECTION'");
            }
        } else {
            error_log("DEBUG: Query execution failed: " . $stmt->error);
        }
        $stmt->close();
    }
} else {
    error_log("DEBUG: No scan data or empty UID - has_scan: " . ($has_scan ? 'true' : 'false') . ", latest_scan: '$latest_scan'");
}

// Return JSON response
header('Content-Type: application/json');

$response = [
    'has_scan' => $has_scan,
    'has_student' => $student_data !== null,
    'target_grade' => $TARGET_GRADE,
    'target_section' => $TARGET_SECTION,
    'target_subject' => $TARGET_SUBJECT,
    'student_data' => $student_data,
    'scan_time' => $scan_time,
    'subject' => $subject,
    'attendance_count' => $attendance_count,
    'debug' => [
        'rfid_file' => $rfid_file,
        'file_exists' => file_exists($rfid_file),
        'file_size' => file_exists($rfid_file) ? filesize($rfid_file) : 0,
        'latest_scan' => $latest_scan,
        'exit_logging' => $EXIT_LOGGING,
        'file_type' => $EXIT_LOGGING === 'true' ? 'exit_logging' : 'subject_attendance'
    ]
];

$json_output = json_encode($response);
if ($json_output === false) {
    error_log("JSON encoding failed: " . json_last_error_msg());
    echo '{"error":"JSON encoding failed"}';
} else {
    echo $json_output;
}
?>
