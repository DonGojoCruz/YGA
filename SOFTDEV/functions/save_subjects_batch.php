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
    // Get form data
    $sectionId = trim($_POST['section_id'] ?? '');
    $gradeLevel = trim($_POST['grade_level'] ?? '');
    $subjects = $_POST['subjects'] ?? [];

    // Validation
    if (empty($sectionId)) {
        echo json_encode(['success' => false, 'message' => 'Section is required']);
        exit;
    }

    if (empty($gradeLevel)) {
        echo json_encode(['success' => false, 'message' => 'Grade level is required']);
        exit;
    }

    if (empty($subjects) || !is_array($subjects)) {
        echo json_encode(['success' => false, 'message' => 'At least one subject is required']);
        exit;
    }

    // Validate each subject
    $validSubjects = [];
    foreach ($subjects as $index => $subject) {
        $subjectName = trim($subject['subject_name'] ?? '');
        $subjectCode = trim($subject['subject_code'] ?? '');
        $subjectDescription = trim($subject['subject_description'] ?? '');
        $teacherId = trim($subject['teacher_id'] ?? '');

        // Skip empty subjects
        if (empty($subjectName)) {
            continue;
        }

        $validSubjects[] = [
            'subject_name' => $subjectName,
            'subject_code' => $subjectCode,
            'subject_description' => $subjectDescription,
            'teacher_id' => $teacherId ?: null
        ];
    }

    if (empty($validSubjects)) {
        echo json_encode(['success' => false, 'message' => 'At least one valid subject is required']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    $successCount = 0;
    $errors = [];

    foreach ($validSubjects as $subject) {
        // Check if subject already exists for this section
        $checkStmt = $conn->prepare("SELECT id FROM subjects WHERE section_id = ? AND subject_name = ?");
        $checkStmt->bind_param("is", $sectionId, $subject['subject_name']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Subject '{$subject['subject_name']}' already exists for this section";
            continue;
        }

        // Insert subject
        $stmt = $conn->prepare("
            INSERT INTO subjects (
                section_id, subject_name, subject_code, subject_description, teacher_id, grade_level
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isssis",
            $sectionId,
            $subject['subject_name'],
            $subject['subject_code'],
            $subject['subject_description'],
            $subject['teacher_id'],
            $gradeLevel
        );

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = "Failed to add subject '{$subject['subject_name']}': " . $stmt->error;
        }

        $stmt->close();
    }

    if ($successCount > 0) {
        $conn->commit();
        $message = "Successfully added {$successCount} subject(s)";
        if (!empty($errors)) {
            $message .= ". " . count($errors) . " subject(s) had errors: " . implode(', ', $errors);
        }
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to add any subjects. Errors: ' . implode(', ', $errors)]);
    }

} catch (Exception $e) {
    $conn->rollback();
    error_log('Error in save_subjects_batch.php: ' . $e->getMessage());
    // Clear any output before sending JSON
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while saving the subjects'
    ]);
}

$conn->close();
?>
