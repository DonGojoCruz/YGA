<?php
// Suppress all output except what we explicitly echo
ob_start();

require_once '../db/db_connect.php';

// Clear any output buffer content
ob_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get form data
    $teacherId = trim($_POST['teacherId'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $middleInitial = trim($_POST['middleInitial'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $gradeLevel = trim($_POST['gradeLevel'] ?? '');
    $advisory = trim($_POST['advisory'] ?? ''); // Advisory section (optional)

    // Validation
    $errors = [];

    if (empty($teacherId)) {
        $errors[] = ['field' => 'teacherId', 'message' => 'Teacher ID is required'];
    }

    if (empty($firstName)) {
        $errors[] = ['field' => 'firstName', 'message' => 'First name is required'];
    }

    if (empty($lastName)) {
        $errors[] = ['field' => 'lastName', 'message' => 'Last name is required'];
    }

    if (empty($gender)) {
        $errors[] = ['field' => 'gender', 'message' => 'Gender is required'];
    }

    if (empty($gradeLevel)) {
        $errors[] = ['field' => 'gradeLevel', 'message' => 'Grade level is required'];
    }

    // Sections validation removed

    // Check if advisory section is already assigned to another teacher
    if (!empty($advisory)) {
        $checkAdvisory = $conn->prepare("SELECT id FROM teachers WHERE advisory_section_id = (SELECT id FROM section WHERE section = ?)");
        $checkAdvisory->bind_param("s", $advisory);
        $checkAdvisory->execute();
        $advisoryResult = $checkAdvisory->get_result();
        
        if ($advisoryResult->num_rows > 0) {
            $errors[] = ['field' => 'advisory', 'message' => 'This section is already assigned as advisory to another teacher'];
        }
    }

    if (!empty($errors)) {
        $firstError = $errors[0];
        echo json_encode([
            'status' => 'error',
            'field' => $firstError['field'],
            'message' => $firstError['message']
        ]);
        exit;
    }

    // Check if teacher ID already exists
    $checkStmt = $conn->prepare("SELECT id FROM teachers WHERE teacher_id = ?");
    $checkStmt->bind_param("s", $teacherId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'field' => 'teacherId',
            'message' => 'Teacher ID already exists'
        ]);
        exit;
    }

    // Get advisory section ID if provided
    $advisorySectionId = null;
    if (!empty($advisory)) {
        $advisoryStmt = $conn->prepare("SELECT id FROM section WHERE section = ?");
        $advisoryStmt->bind_param("s", $advisory);
        $advisoryStmt->execute();
        $advisoryResult = $advisoryStmt->get_result();
        if ($advisoryResult->num_rows > 0) {
            $advisorySectionId = $advisoryResult->fetch_assoc()['id'];
        }
    }

    // Primary section handling removed

    // Insert teacher
    $stmt = $conn->prepare("
        INSERT INTO teachers (
            teacher_id, first_name, last_name, middle_initial, gender,
            grade_level, advisory_section_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssi",
        $teacherId,
        $firstName,
        $lastName,
        $middleInitial,
        $gender,
        $gradeLevel,
        $advisorySectionId
    );

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Teacher registered successfully',
            'teacher_id' => $teacherId
        ]);
    } else {
        throw new Exception('Failed to save teacher: ' . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    error_log('Error in save_teacher.php: ' . $e->getMessage());
    // Clear any output before sending JSON
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while saving the teacher'
    ]);
}

$conn->close();
?>
