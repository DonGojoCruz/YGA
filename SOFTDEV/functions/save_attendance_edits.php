<?php
session_start();

// Only accept POST requests with JSON content
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['subject']) || !isset($input['edits']) || !is_array($input['edits'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit();
}

$subject = $input['subject'];
$section = $input['section'] ?? '';
$gradeLevel = $input['grade_level'] ?? '';
$edits = $input['edits'];

// If attendance session is not set, but a logged-in user initiated the request,
// initialize session context from the provided payload. Otherwise, block.
if (!isset($_SESSION['selected_section_id']) || !isset($_SESSION['selected_grade_level']) || !isset($_SESSION['selected_section'])) {
    if (isset($_SESSION['user_id']) && $section !== '' && $gradeLevel !== '') {
        // Best-effort context; section id may be unknown here
        $_SESSION['selected_grade_level'] = $gradeLevel;
        $_SESSION['selected_section'] = $section;
        if (!isset($_SESSION['selected_section_id'])) {
            $_SESSION['selected_section_id'] = null; // placeholder
        }
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized - No section selected']);
        exit();
    }
}

try {
    // Generate subject-specific CSV file path
    $subjectKey = strtolower(str_replace([' ', '-'], '_', $subject));
    $date = date('Y-m-d');
    $csvPath = "C:/xampp/htdocs/SOFTDEV/logs/{$subjectKey}rfid_log_{$date}.csv";
    
    // Ensure the logs directory exists
    $logsDir = dirname($csvPath);
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    
    // Check if CSV file exists, create if not
    if (!file_exists($csvPath)) {
        file_put_contents($csvPath, "UID,Date,Time,Subject,Description\n");
    }
    
    // Read existing CSV content
    $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    // Check if header needs to be updated to include Description column
    if (isset($lines[0]) && !strpos($lines[0], 'Description')) {
        $lines[0] = "UID,Date,Time,Subject,Description";
    }
    
    // Process each edit
    foreach ($edits as $edit) {
        $studentName = $edit['student_name'];
        $rfidTag = $edit['rfid_tag'];
        $oldStatus = $edit['old_status'];
        $newStatus = $edit['new_status'];
        $description = $edit['description'];
        $originalTime = $edit['time']; // Keep original time from when student was scanned
        $originalDate = $edit['date']; // Keep original date from when student was scanned
        
        // Remove any existing entries for this student on this date
        $lines = array_filter($lines, function($line) use ($rfidTag, $originalDate) {
            // Skip header line
            if (strpos($line, 'UID,Date,Time,Subject') === 0) {
                return true;
            }
            
            // Parse the line to check if it's for the same student and date
            $parts = explode(',', $line);
            if (count($parts) >= 2) {
                $lineRfid = trim($parts[0]);
                $lineDate = trim($parts[1]);
                // Keep the line if it's NOT for the same student and date
                return !($lineRfid === $rfidTag && $lineDate === $originalDate);
            }
            return true;
        });
        
        // Add new entry for the updated status
        $subjectWithStatus = $subject . "_" . strtoupper($newStatus);
        $lines[] = "{$rfidTag},{$originalDate},{$originalTime},{$subjectWithStatus},{$description}";
    }
    
    // Write updated content back to CSV file
    $newContent = implode("\n", $lines) . "\n";
    
    if (file_put_contents($csvPath, $newContent) === false) {
        throw new Exception("Failed to write to CSV file: {$csvPath}");
    }
    
    // Log the action
    $userId = $_SESSION['user_id'] ?? 'unknown';
    $sectionInfo = $_SESSION['selected_grade_level'] . '-' . $_SESSION['selected_section'];
    error_log("Attendance edits saved for subject '{$subject}' in section '{$sectionInfo}' by user ID {$userId}. " . count($edits) . " edits saved to {$csvPath}");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Attendance edits saved successfully',
        'edits_count' => count($edits),
        'file_path' => $csvPath
    ]);
    
} catch (Exception $e) {
    error_log("Error saving attendance edits: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error saving attendance edits: ' . $e->getMessage()
    ]);
}
?>
