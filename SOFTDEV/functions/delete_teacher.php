<?php
// Suppress all output except what we explicitly echo
ob_start();

require_once '../db/db_connect.php';

// Clear any output buffer content
ob_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get teacher ID from POST data
    $teacherId = $_POST['id'] ?? '';

    if (empty($teacherId) || !is_numeric($teacherId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid teacher ID'
        ]);
        exit;
    }

    // Check if teacher exists
    $checkStmt = $conn->prepare("SELECT teacher_id, first_name, last_name FROM teachers WHERE id = ?");
    $checkStmt->bind_param("i", $teacherId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher not found'
        ]);
        exit;
    }

    $teacher = $result->fetch_assoc();

    // Delete the teacher
    $deleteStmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
    $deleteStmt->bind_param("i", $teacherId);

    if ($deleteStmt->execute()) {
        if ($deleteStmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Teacher deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No teacher was deleted'
            ]);
        }
    } else {
        throw new Exception('Failed to delete teacher: ' . $deleteStmt->error);
    }

    $deleteStmt->close();

} catch (Exception $e) {
    error_log('Error in delete_teacher.php: ' . $e->getMessage());
    // Clear any output before sending JSON
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting the teacher'
    ]);
}

$conn->close();
?>
