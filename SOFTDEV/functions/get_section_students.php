<?php
// Check if we're being called from the root directory or from within functions
if (file_exists('../db/db_connect.php')) {
    require_once '../db/db_connect.php';
} else {
    require_once 'db/db_connect.php';
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method not allowed']);
    exit;
}

try {
    $sectionId = $_GET['section_id'] ?? '';
    
    if (empty($sectionId)) {
        echo json_encode(['error' => true, 'message' => 'Section ID is required']);
        exit;
    }
    
    // Get section information
    $sectionQuery = "SELECT s.id, s.grade_level, s.section, 
                            t.first_name AS adviser_first_name, 
                            t.last_name AS adviser_last_name,
                            t.gender AS adviser_gender
                     FROM section s
                     LEFT JOIN teachers t ON t.advisory_section_id = s.id
                     WHERE s.id = ?";
    
    $sectionStmt = $conn->prepare($sectionQuery);
    $sectionStmt->bind_param("i", $sectionId);
    $sectionStmt->execute();
    $sectionResult = $sectionStmt->get_result();
    
    if ($sectionResult->num_rows === 0) {
        echo json_encode(['error' => true, 'message' => 'Section not found']);
        exit;
    }
    
    $sectionData = $sectionResult->fetch_assoc();
    
    // Get students in this section
    $studentsQuery = "SELECT student_id, lrn, first_name, last_name, middle_initial, 
                             rfid_tag, email, birthdate, gender, guardian
                      FROM students 
                      WHERE section_id = ? 
                      ORDER BY last_name, first_name";
    
    $studentsStmt = $conn->prepare($studentsQuery);
    $studentsStmt->bind_param("i", $sectionId);
    $studentsStmt->execute();
    $studentsResult = $studentsStmt->get_result();
    
    $students = [];
    while ($row = $studentsResult->fetch_assoc()) {
        $students[] = [
            'student_id' => $row['student_id'],
            'lrn' => $row['lrn'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'middle_initial' => $row['middle_initial'],
            'full_name' => $row['last_name'] . ', ' . $row['first_name'] . ($row['middle_initial'] ? ' ' . $row['middle_initial'] : ''),
            'rfid_number' => $row['rfid_tag'],
            'contact_number' => '', // Not available in current schema
            'email' => $row['email'],
            'address' => '', // Not available in current schema
            'date_of_birth' => $row['birthdate'],
            'gender' => $row['gender'],
            'parent_name' => $row['guardian'],
            'parent_contact' => '' // Not available in current schema
        ];
    }
    
    // Get subjects for this grade level
    $sample_subjects = [
        '1' => ['Mathematics', 'English', 'Science', 'Filipino', 'Araling Panlipunan'],
        '2' => ['Mathematics', 'English', 'Science', 'Filipino', 'Araling Panlipunan', 'MAPEH'],
        '7' => ['Mathematics', 'English', 'Science', 'Filipino', 'Araling Panlipunan', 'MAPEH', 'TLE', 'Values Education'],
        '8' => ['Mathematics', 'English', 'Science', 'Filipino', 'Araling Panlipunan', 'MAPEH', 'TLE', 'Values Education'],
        '9' => ['Mathematics', 'English', 'Science', 'Filipino', 'Araling Panlipunan', 'MAPEH', 'TLE', 'Values Education'],
        '10' => ['Mathematics', 'English', 'Science', 'Filipino', 'Araling Panlipunan', 'MAPEH', 'TLE', 'Values Education']
    ];
    
    $subjects = $sample_subjects[$sectionData['grade_level']] ?? ['Mathematics', 'English', 'Science'];
    
    $response = [
        'section' => [
            'id' => $sectionData['id'],
            'grade_level' => $sectionData['grade_level'],
            'section' => $sectionData['section'],
            'grade_label' => is_numeric($sectionData['grade_level']) ? "Grade " . $sectionData['grade_level'] : $sectionData['grade_level'],
            'adviser_name' => (!empty($sectionData['adviser_first_name']) && !empty($sectionData['adviser_last_name'])) 
                            ? (($sectionData['adviser_gender'] === 'Female') ? 'Ms.' : 'Mr.') . ' ' . $sectionData['adviser_first_name'] . ' ' . $sectionData['adviser_last_name'] 
                            : 'No adviser assigned',
            'subjects' => $subjects
        ],
        'students' => $students,
        'total_students' => count($students)
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Error in get_section_students.php: ' . $e->getMessage());
    echo json_encode(['error' => true, 'message' => 'Failed to retrieve section data']);
}

$conn->close();
?>
