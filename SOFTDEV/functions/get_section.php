<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include database connection
include __DIR__ . '/../db/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if section ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Section ID is required']);
    exit();
}

$sectionId = intval($_GET['id']);

if ($sectionId <= 0) {
    echo json_encode(['error' => 'Invalid section ID']);
    exit();
}

try {
    // Get section information
    $stmt = $conn->prepare("SELECT id, grade_level, section, password FROM section WHERE id = ?");
    $stmt->bind_param("i", $sectionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Section not found']);
        exit();
    }
    
    $section = $result->fetch_assoc();
    
    // Get adviser information
    $adviserStmt = $conn->prepare("SELECT id, first_name, last_name FROM teachers WHERE advisory = ?");
    $adviserStmt->bind_param("s", $section['section']);
    $adviserStmt->execute();
    $adviserResult = $adviserStmt->get_result();
    
    $adviser = null;
    if ($adviserResult->num_rows > 0) {
        $adviser = $adviserResult->fetch_assoc();
    }
    
    // Get student count
    $studentStmt = $conn->prepare("SELECT COUNT(*) as student_count FROM students WHERE section_id = ?");
    $studentStmt->bind_param("i", $sectionId);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();
    $studentData = $studentResult->fetch_assoc();
    
    $response = [
        'id' => $section['id'],
        'grade_level' => $section['grade_level'],
        'section' => $section['section'],
        'password' => $section['password'],
        'adviser' => $adviser,
        'student_count' => $studentData['student_count']
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Error in get_section.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Failed to retrieve section data']);
}

$conn->close();
?>
