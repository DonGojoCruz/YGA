<?php
session_start();
require_once __DIR__ . '/../db/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['week_start'])) {
    echo json_encode(['success' => false, 'message' => 'Week start date required']);
    exit();
}

$weekStart = $_GET['week_start'];
$weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

// Get grade and section filters if provided
$gradeFilter = isset($_GET['grade']) ? $_GET['grade'] : '';
$sectionFilter = isset($_GET['section']) ? $_GET['section'] : '';

// Build SQL query with optional filtering
$sql = "SELECT s.rfid_tag, s.lrn, s.first_name, s.last_name, s.grade_level, sec.section AS section_name
        FROM students s
        LEFT JOIN section sec ON s.section_id = sec.id";

// Build WHERE clause for filters
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
        $daysPresent = 0;
        $attendanceDates = [];
        
        // Count days present for this week
        $currentDate = $weekStart;
        for ($i = 0; $i < 7; $i++) {
            $logFile = __DIR__ . "/../logs/rfid_log_" . $currentDate . ".csv";
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $lineNum => $line) {
                    if ($lineNum === 0) continue; // skip header
                    list($fileUid, $date, $time) = explode(",", $line);
                    if ($fileUid === $uid) {
                        $daysPresent++;
                        $attendanceDates[] = date('M d', strtotime($currentDate));
                        break;
                    }
                }
            }
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        
        $students[] = [
            'lrn' => $row['lrn'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'grade' => is_numeric($row['grade_level']) ? "Grade " . $row['grade_level'] : $row['grade_level'],
            'section' => $row['section_name'],
            'days_present' => $daysPresent,
            'attendance_dates' => $attendanceDates
        ];
    }
}

echo json_encode([
    'success' => true,
    'students' => $students,
    'week_start' => $weekStart,
    'week_end' => $weekEnd
]);
?>
