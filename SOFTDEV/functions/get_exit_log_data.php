<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../db/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'get_exit_logs') {
    try {
        $logsDir = __DIR__ . '/../logs';
        $exits = [];
        
        // Get today's date
        $today = date('Y-m-d');
        $todayFile = $logsDir . "/exitrfid_log_{$today}.csv";
        
        // Student cache to avoid repeated database queries
        $studentCache = [];
        
        function fetchStudentByUid(mysqli $conn, string $uid, array &$cache): array {
            if (isset($cache[$uid])) return $cache[$uid];

            $sql = "SELECT 
                        s.first_name,
                        s.last_name,
                        s.middle_initial,
                        s.grade_level,
                        COALESCE(sec.section, '-') AS section_name
                    FROM students s
                    LEFT JOIN section sec ON sec.id = s.section_id
                    WHERE s.rfid_tag = ?
                    LIMIT 1";
            
            $data = [
                'name'    => 'Unknown',
                'grade'   => '-',
                'section' => '-',
                'first_name' => '',
                'last_name' => '',
                'middle_initial' => ''
            ];

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('s', $uid);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        $data['first_name'] = $row['first_name'] ?? '';
                        $data['last_name'] = $row['last_name'] ?? '';
                        $data['middle_initial'] = $row['middle_initial'] ?? '';
                        
                        // Format name as "Last Name, First Name Middle Initial"
                        $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                        if ($fullName !== '') {
                            $middleInitial = $row['middle_initial'] ?? '';
                            $middlePart = $middleInitial ? " ${middleInitial}" : '';
                            $data['name'] = $row['last_name'] . ', ' . $row['first_name'] . $middlePart;
                        } else {
                            $data['name'] = 'Unknown';
                        }

                        $gradeRaw = $row['grade_level'] ?? '';
                        if (is_numeric($gradeRaw)) {
                            $data['grade'] = 'Grade ' . $gradeRaw;
                        } else if (!empty($gradeRaw)) {
                            $data['grade'] = $gradeRaw;
                        } else {
                            $data['grade'] = '-';
                        }

                        $data['section'] = $row['section_name'] ?? '-';
                    }
                }
                $stmt->close();
            }

            return $cache[$uid] = $data;
        }
        
        // Check if today's exit log file exists
        if (file_exists($todayFile)) {
            $lines = file($todayFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            // Skip header row if it exists
            if (!empty($lines) && strpos($lines[0], 'UID,Student_Name') !== false) {
                array_shift($lines);
            }
            
            if ($lines) {
                foreach ($lines as $line) {
                    $parts = array_map('trim', str_getcsv($line));
                    if (count($parts) < 4) continue; // Need at least 4 parts: UID,Date,Time,Type
                    
                    // Handle different CSV formats
                    if (count($parts) >= 7) {
                        // New format: UID,Student_Name,Date,Time,Type,Grade,Section
                        list($uid, $studentName, $date, $time, $type, $grade, $section) = $parts;
                    } else {
                        // Old format: UID,Date,Time,Type
                        list($uid, $date, $time, $type) = $parts;
                        $studentName = '';
                        $grade = '';
                        $section = '';
                    }
                    
                    // Fetch student info from database for additional details
                    $info = fetchStudentByUid($conn, $uid, $studentCache);
                    
                    // Skip unknown UIDs
                    if ($info['name'] === 'Unknown') continue;
                    
                    $exits[] = [
                        'uid' => $uid,
                        'name' => $info['name'],
                        'grade' => $info['grade'],
                        'section' => $info['section'],
                        'date' => $date,
                        'time' => $time,
                        'type' => $type
                    ];
                }
            }
        }
        
        // Sort by time (newest first)
        usort($exits, function($a, $b) {
            return strtotime($b['time']) <=> strtotime($a['time']);
        });
        
        echo json_encode([
            'success' => true,
            'data' => $exits,
            'total' => count($exits),
            'date' => $today
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading exit logs: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}
?>
