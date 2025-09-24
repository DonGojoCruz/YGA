<?php
// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../db/db_connect.php';

// Check if user has selected a section
if (!isset($_SESSION['selected_section_id']) || !isset($_SESSION['selected_grade_level'])) {
    echo json_encode(['success' => false, 'message' => 'No section selected']);
    exit;
}

$sectionId = $_SESSION['selected_section_id'];
$gradeLevel = $_SESSION['selected_grade_level'];
$section = $_SESSION['selected_section'];
$type = $_GET['type'] ?? 'top'; // 'top' or 'least'
$modal = isset($_GET['modal']) && $_GET['modal'] === 'true'; // true if called from modal
$subject = $_GET['subject'] ?? null; // specific subject if requesting students
$students = isset($_GET['students']) && $_GET['students'] === 'true'; // true if requesting student list

try {
    if ($students && $subject) {
        // Get students for a specific subject
        $studentData = getStudentsForSubject($conn, $sectionId, $subject, $type);
        echo json_encode(['success' => true, 'students' => $studentData]);
    } elseif ($modal) {
        // Get subjects for modal display
        $subjects = getSubjectsForModal($conn, $sectionId, $type);
        echo json_encode(['success' => true, 'subjects' => $subjects]);
    } else {
        // Get attendance statistics for main display
        $subjects = getAttendanceStatistics($conn, $sectionId, $type);
        echo json_encode(['success' => true, 'subjects' => $subjects]);
    }
} catch (Exception $e) {
    error_log("Error in get_attendance_statistics.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

function getAttendanceStatistics($conn, $sectionId, $type) {
    // Get attendance data from CSV log files
    $logDirectory = __DIR__ . '/../logs/';
    $subjectFiles = glob($logDirectory . '*rfid_log_*.csv');
    
    if (empty($subjectFiles)) {
        return getSampleSubjects($type);
    }
    
    // Extract unique subjects from file names (exclude exit logs)
    $subjectNames = [];
    foreach ($subjectFiles as $file) {
        $fileName = basename($file);
        if (preg_match('/(.+)rfid_log_\d{4}-\d{2}-\d{2}\.csv/', $fileName, $matches)) {
            $subject = ucfirst($matches[1]); // Capitalize first letter
            
            // Skip exit logs - exit is not a subject
            if (strtolower($subject) === 'exit') {
                continue;
            }
            
            if (!in_array($subject, $subjectNames)) {
                $subjectNames[] = $subject;
            }
        }
    }
    
    
    $subjects = [];
    
    foreach ($subjectNames as $subjectName) {
        // Get students with their attendance data for this subject
        $students = getStudentsWithAttendanceFromLogs($conn, $sectionId, $subjectName, $type);
        
        if (!empty($students)) {
            // For Top Attendance: only include subjects that have students with actual attendance (> 0)
            // For Least Attendance: include all subjects (including those with 0 attendance)
            if ($type === 'top') {
                // Check if any student has attendance > 0
                $hasAttendance = false;
                foreach ($students as $student) {
                    if ($student['attendance'] > 0) {
                        $hasAttendance = true;
                        break;
                    }
                }
                
                if ($hasAttendance) {
                    $subjects[] = [
                        'subject' => $subjectName,
                        'students' => $students
                    ];
                }
            } else {
                // For least attendance, include all subjects (even with 0 attendance)
                $subjects[] = [
                    'subject' => $subjectName,
                    'students' => $students
                ];
            }
        }
    }
    
    // If no subjects found, return sample data
    if (empty($subjects)) {
        return getSampleSubjects($type);
    }
    
    return $subjects;
}

function getSampleSubjects($type) {
    // Return empty array instead of dummy data when no log files exist
    return [];
}

function getSubjectsForModal($conn, $sectionId, $type) {
    // Use the same logic as getAttendanceStatistics
    return getAttendanceStatistics($conn, $sectionId, $type);
}

function getStudentsForSubject($conn, $sectionId, $subjectName, $type) {
    // Use the same approach as getStudentsWithAttendanceFromLogs
    return getStudentsWithAttendanceFromLogs($conn, $sectionId, $subjectName, $type);
}

function getStudentsWithAttendanceFromLogs($conn, $sectionId, $subjectName, $type) {
    // Get all students in this specific section
    $studentsQuery = "
        SELECT s.student_id, s.lrn, s.first_name, s.last_name, s.middle_initial, s.rfid_tag
        FROM students s 
        WHERE s.section_id = ? 
        ORDER BY s.last_name ASC, s.first_name ASC
    ";
    
    $stmt = $conn->prepare($studentsQuery);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param('i', $sectionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    
    $students = [];
    $allAttendanceData = [];
    
    // Read attendance logs for this subject - get all log files for this subject across all dates
    $subject = strtolower($subjectName);
    $logFiles = glob(__DIR__ . "/../logs/{$subject}rfid_log_*.csv");
    
    
    // Parse all log files to get attendance data from ALL dates
    $attendanceByRfid = [];
    $uniqueDates = [];
    
    foreach ($logFiles as $logFile) {
        if (($handle = fopen($logFile, "r")) !== FALSE) {
            // Extract date from filename as backup
            $fileName = basename($logFile);
            $fileDate = null;
            if (preg_match('/rfid_log_(\d{4}-\d{2}-\d{2})\.csv/', $fileName, $matches)) {
                $fileDate = $matches[1];
            }
            
            // Skip header row
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) >= 3) {
                    $uid = trim($data[0]);
                    $csvDate = $data[1];
                    $time = $data[2];
                    
                    // Use file date if CSV date seems incorrect or use CSV date
                    $date = $fileDate ? $fileDate : $csvDate;
                    
                    // Track ALL unique dates (not just current date)
                    if (!in_array($date, $uniqueDates)) {
                        $uniqueDates[] = $date;
                    }
                    
                    // Track attendance by RFID for ALL dates
                    if (!isset($attendanceByRfid[$uid])) {
                        $attendanceByRfid[$uid] = [];
                    }
                    
                    // Only count one attendance per day per student (but for ALL dates)
                    if (!in_array($date, $attendanceByRfid[$uid])) {
                        $attendanceByRfid[$uid][] = $date;
                    }
                }
            }
            fclose($handle);
        }
    }
    
    $totalDays = count($uniqueDates);
    
    // Process each student - include ALL students even those with no attendance
    while ($student = $result->fetch_assoc()) {
        $rfid = trim($student['rfid_tag']);
        
        // Calculate present days (0 if no attendance record)
        $presentDays = 0;
        if ($rfid && isset($attendanceByRfid[$rfid])) {
            $presentDays = count($attendanceByRfid[$rfid]);
        }
        
        // Format name
        $fullName = formatStudentName($student);
        
        // Include all students in the data (even those with 0 attendance)
        $allAttendanceData[] = [
            'id' => $student['lrn'], // Use LRN instead of student_id
            'name' => $fullName,
            'attendance' => $presentDays, // Show attendance days (could be 0)
            'present_days' => $presentDays,
            'total_days' => $totalDays
        ];
    }
    
    $stmt->close();
    
    // If no students found, return empty data
    if (empty($allAttendanceData)) {
        return [];
    }
    
    // Sort by attendance days
    if ($type === 'top') {
        // Sort descending for top attendance (most days)
        usort($allAttendanceData, function($a, $b) {
            return $b['attendance'] - $a['attendance'];
        });
    } else {
        // Sort ascending for least attendance (fewest days)
        usort($allAttendanceData, function($a, $b) {
            return $a['attendance'] - $b['attendance'];
        });
    }
    
    // Return top 3 students
    return array_slice($allAttendanceData, 0, 3);
}

function getStudentsWithAttendance($conn, $gradeLevel, $subjectName, $type) {
    // First, let's check if students table exists and get its structure
    $checkTable = "SHOW TABLES LIKE 'students'";
    $result = $conn->query($checkTable);
    
    if ($result->num_rows === 0) {
        // If students table doesn't exist, return empty data
        return [];
    }
    
    // Get all students in this section with their RFID tags
    $studentsQuery = "
        SELECT s.student_id, s.first_name, s.last_name, s.middle_initial, s.rfid_tag
        FROM students s 
        WHERE s.grade_level = ? 
        ORDER BY s.last_name ASC, s.first_name ASC
    ";
    
    $stmt = $conn->prepare($studentsQuery);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param('i', $gradeLevel);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    
    while ($student = $result->fetch_assoc()) {
        // Calculate attendance percentage for this subject using RFID logs
        $attendanceData = calculateAttendancePercentage($conn, $student['student_id'], $subjectName);
        
        // Only include students with attendance data
        if ($attendanceData['total_days'] > 0) {
            // Format name
            $fullName = formatStudentName($student);
            
            $students[] = [
                'id' => $student['student_id'],
                'name' => $fullName,
                'attendance' => $attendanceData['attendance'],
                'present_days' => $attendanceData['present_days'],
                'total_days' => $attendanceData['total_days']
            ];
        }
    }
    
    $stmt->close();
    
    // If no students found, return empty data
    if (empty($students)) {
        return [];
    }
    
    // Sort by attendance percentage
    if ($type === 'top') {
        // Sort descending for top attendance
        usort($students, function($a, $b) {
            return $b['attendance'] - $a['attendance'];
        });
    } else {
        // Sort ascending for least attendance
        usort($students, function($a, $b) {
            return $a['attendance'] - $b['attendance'];
        });
    }
    
    // Return top 3 students
    return array_slice($students, 0, 3);
}

function getSampleStudents($type) {
    // Return empty array instead of dummy data when no log files exist
    return [];
}

function calculateAttendancePercentage($conn, $studentId, $subjectName) {
    // Get student's RFID from database
    $rfidQuery = "SELECT rfid_tag FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($rfidQuery);
    if (!$stmt) {
        return ['present_days' => 0, 'total_days' => 0];
    }
    
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if (!$student || !$student['rfid_tag']) {
        return ['present_days' => 0, 'total_days' => 0];
    }
    
    $rfid = $student['rfid_tag'];
    $subject = strtolower($subjectName);
    
    // Get all log files for this subject
    $logFiles = glob(__DIR__ . "/../logs/{$subject}rfid_log_*.csv");
    if (empty($logFiles)) {
        return ['present_days' => 0, 'total_days' => 0];
    }
    
    $presentDays = 0;
    $totalDays = 0;
    $processedDates = [];
    
    foreach ($logFiles as $logFile) {
        if (($handle = fopen($logFile, "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) >= 3) {
                    $uid = $data[0];
                    $date = $data[1];
                    
                    // Count unique dates as total days
                    if (!in_array($date, $processedDates)) {
                        $processedDates[] = $date;
                        $totalDays++;
                    }
                    
                    // If this is our student's RFID and we haven't counted this date yet
                    if (trim($uid) === trim($rfid)) {
                        $presentDays++;
                        break; // One presence per day is enough
                    }
                }
            }
            fclose($handle);
        }
    }
    
    // Calculate percentage
    if ($totalDays > 0) {
        $percentage = round(($presentDays / $totalDays) * 100);
    } else {
        $percentage = 0;
    }
    
    return [
        'present_days' => $presentDays,
        'total_days' => $totalDays,
        'attendance' => $percentage
    ];
}

function formatStudentName($student) {
    $lastName = $student['last_name'] ?? '';
    $firstName = $student['first_name'] ?? '';
    $middleInitial = $student['middle_initial'] ?? '';
    
    $middlePart = $middleInitial ? " $middleInitial." : '';
    return "$lastName, $firstName$middlePart";
}
?>
