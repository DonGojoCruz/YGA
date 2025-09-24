<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
include __DIR__ . '/../db/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$sectionId = intval($_POST['section_id']);
$gradeLevel = trim($_POST['grade_level']);
$section = trim($_POST['section']);
$oldPassword = trim($_POST['old_password']);
$newPassword = trim($_POST['new_password']);
$confirmPassword = trim($_POST['confirm_password']);

// Validate input
if (empty($gradeLevel) || empty($section) || empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate password confirmation
if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
    exit();
}

// Validate password strength
if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
    exit();
}

if ($sectionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid section ID']);
    exit();
}

try {
    // Check if the section exists and verify old password
    $checkStmt = $conn->prepare("SELECT id, password FROM section WHERE id = ?");
    $checkStmt->bind_param("i", $sectionId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Section not found']);
        exit();
    }
    
    $sectionData = $checkResult->fetch_assoc();
    
    // Verify old password
    if ($sectionData['password'] !== $oldPassword) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit();
    }
    
    // Check if the new grade level and section combination already exists (excluding current section)
    $duplicateStmt = $conn->prepare("SELECT id FROM section WHERE grade_level = ? AND section = ? AND id != ?");
    $duplicateStmt->bind_param("ssi", $gradeLevel, $section, $sectionId);
    $duplicateStmt->execute();
    $duplicateResult = $duplicateStmt->get_result();
    
    if ($duplicateResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Grade Level {$gradeLevel} - {$section} already exists"]);
        exit();
    }
    
    // Update the section
    $updateStmt = $conn->prepare("UPDATE section SET grade_level = ?, section = ?, password = ? WHERE id = ?");
    $updateStmt->bind_param("sssi", $gradeLevel, $section, $newPassword, $sectionId);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Section updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made to the section']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update section: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    error_log('Error in edit_section.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating the section']);
}

$conn->close();
?>
