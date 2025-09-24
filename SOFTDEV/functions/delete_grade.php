<?php
// Prevent any output before JSON response
ob_start();

// Disable error display and enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();

// Function to send JSON response and exit
function sendJsonResponse($success, $message) {
    // Clear any previous output
    ob_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Unauthorized access');
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

// Check if ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    sendJsonResponse(false, 'No ID provided');
}

try {
    // Include database connection
    require_once '../db/db_connect.php';
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    $id = intval($_POST['id']);
    
    if ($id <= 0) {
        sendJsonResponse(false, 'Invalid ID provided');
    }
    
    // Check if the section exists first
    $check_section = $conn->prepare("SELECT id, grade_level, section FROM section WHERE id = ?");
    
    if (!$check_section) {
        throw new Exception('Failed to prepare section check statement: ' . $conn->error);
    }
    
    $check_section->bind_param("i", $id);
    
    if (!$check_section->execute()) {
        throw new Exception('Failed to execute section check: ' . $check_section->error);
    }
    
    $section_result = $check_section->get_result();
    
    if ($section_result->num_rows === 0) {
        $check_section->close();
        $conn->close();
        sendJsonResponse(false, 'Section not found');
    }
    
    $section_data = $section_result->fetch_assoc();
    $check_section->close();
    
    // Check if students table exists before trying to query it
    $table_exists = $conn->query("SHOW TABLES LIKE 'students'");
    
    if ($table_exists && $table_exists->num_rows > 0) {
        // Students table exists, check for enrolled students
        $check_students = $conn->prepare("SELECT COUNT(*) as student_count FROM students WHERE section_id = ?");
        
        if (!$check_students) {
            throw new Exception('Failed to prepare student check statement: ' . $conn->error);
        }
        
        $check_students->bind_param("i", $id);
        
        if (!$check_students->execute()) {
            throw new Exception('Failed to execute student check: ' . $check_students->error);
        }
        
        $student_result = $check_students->get_result();
        $student_data = $student_result->fetch_assoc();
        $student_count = $student_data['student_count'];
        
        if ($student_count > 0) {
            $check_students->close();
            $conn->close();
            sendJsonResponse(false, "Cannot delete section. There are {$student_count} student(s) enrolled in this section.");
        }
        
        $check_students->close();
    }
    
    // Delete the section
    $delete_stmt = $conn->prepare("DELETE FROM section WHERE id = ?");
    
    if (!$delete_stmt) {
        throw new Exception('Failed to prepare delete statement: ' . $conn->error);
    }
    
    $delete_stmt->bind_param("i", $id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception('Failed to execute delete: ' . $delete_stmt->error);
    }
    
    if ($delete_stmt->affected_rows > 0) {
        $delete_stmt->close();
        $conn->close();
        sendJsonResponse(true, 'Section deleted successfully');
    } else {
        $delete_stmt->close();
        $conn->close();
        sendJsonResponse(false, 'Section not found or already deleted');
    }
    
} catch (Exception $e) {
    // Log the error
    error_log('Delete section error: ' . $e->getMessage());
    
    // Close connections if they exist
    if (isset($check_section)) {
        $check_section->close();
    }
    if (isset($check_students)) {
        $check_students->close();
    }
    if (isset($delete_stmt)) {
        $delete_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
    
    sendJsonResponse(false, 'An error occurred while deleting the section: ' . $e->getMessage());
}
?>