<?php
session_start();

// Prevent caching and going back to previous pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../db/db_connect.php';

// Get parameters
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$gradeFilter = isset($_GET['grade']) ? $_GET['grade'] : '';
$sectionFilter = isset($_GET['section']) ? $_GET['section'] : '';

// Read log file for that date
$logDir = __DIR__ . "/../logs/";
$logFile = $logDir . "rfid_log_" . $selectedDate . ".csv";
$presentUIDs = [];

if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $i => $line) {
        if ($i === 0) continue; // skip header
        $parts = explode(",", $line);
        if (count($parts) >= 1) {
            $uid = trim($parts[0]);
            if (!empty($uid)) {
                $presentUIDs[$uid] = true;
            }
        }
    }
}

// Build SQL query with optional filtering
$whereConditions = [];
$bindTypes = "";
$bindValues = [];

if ($gradeFilter) {
    $whereConditions[] = "s.grade_level = ?";
    $bindTypes .= "s";
    $bindValues[] = $gradeFilter;
}

if ($sectionFilter) {
    $whereConditions[] = "sec.section = ?";
    $bindTypes .= "s";
    $bindValues[] = $sectionFilter;
}

$sql = "SELECT s.rfid_tag, s.lrn, s.first_name, s.last_name, s.grade_level, sec.section AS section_name
        FROM students s
        LEFT JOIN section sec ON s.section_id = sec.id";

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY
          CASE 
            WHEN s.grade_level = 'Nursery' THEN 1
            WHEN s.grade_level = 'Kinder 1' THEN 2
            WHEN s.grade_level = 'Kinder 2' THEN 3
            WHEN s.grade_level REGEXP '^[0-9]+$' THEN 4
            ELSE 5
          END,
          CAST(s.grade_level AS UNSIGNED),
          sec.section ASC,
          s.last_name ASC";



// Prepare and execute statement
$stmt = $conn->prepare($sql);
if (!empty($bindValues)) {
    $stmt->bind_param($bindTypes, ...$bindValues);
}
$stmt->execute();
$result = $stmt->get_result();

$students = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $uid = $row['rfid_tag'];
        $present = isset($presentUIDs[$uid]);
        
        $students[] = [
            'lrn' => $row['lrn'],
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'grade_level' => $row['grade_level'],
            'section' => $row['section_name'],
            'present' => $present,
            'absent' => !$present
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'date' => $selectedDate,
    'grade_filter' => $gradeFilter,
    'section_filter' => $sectionFilter,
    'students' => $students,
    'total_present' => count(array_filter($students, function($s) { return $s['present']; })),
    'total_absent' => count(array_filter($students, function($s) { return $s['absent']; }))
]);
?>
















