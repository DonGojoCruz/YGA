<?php
session_start();
require_once '../db/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => true, 'message' => 'Unauthorized - Please log in']);
    exit();
}

// Check if student_id is provided
if (!isset($_GET['student_id']) || empty($_GET['student_id']) || $_GET['student_id'] === 'undefined') {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Student ID is required or invalid']);
    exit();
}

$student_id = intval($_GET['student_id']);

// Check if student_id is a valid integer
if ($student_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Invalid student ID format']);
    exit();
}

try {
    // Get student data with section information
    $sql = "SELECT 
                s.student_id,
                s.lrn,
                s.first_name,
                s.last_name,
                s.middle_initial,
                s.birthdate,
                s.gender,
                s.guardian,
                s.email,
                s.rfid_tag,
                s.grade_level,
                s.section_id,
                sec.section as section_name,
                s.photo_path
            FROM students s
            LEFT JOIN section sec ON s.section_id = sec.id
            WHERE s.student_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => true, 'message' => 'Student not found']);
        exit();
    }
    
    $student = $result->fetch_assoc();
    $stmt->close();
    
    // Format birthdate for HTML date input (YYYY-MM-DD)
    if ($student['birthdate']) {
        $student['birthdate'] = date('Y-m-d', strtotime($student['birthdate']));
    }
    
    // Return student data
    echo json_encode([
        'error' => false,
        'student' => $student
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_student.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Database error occurred']);
}
?>
