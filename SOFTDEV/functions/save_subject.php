<?php
include __DIR__ . '/../db/db_connect.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $subject_name = trim($conn->real_escape_string($_POST['subject_name']));
    $grade_level = $conn->real_escape_string($_POST['grade_level']);
    $section_id = intval($_POST['section_id']);
    $teacher_id = intval($_POST['teacher_id']);
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
    $subject_code = isset($_POST['subject_code']) ? trim($conn->real_escape_string($_POST['subject_code'])) : '';
    $subject_description = isset($_POST['subject_description']) ? trim($conn->real_escape_string($_POST['subject_description'])) : '';
    
    if (empty($subject_name) || empty($grade_level) || $section_id <= 0) {
        throw new Exception('All required fields must be filled');
    }
    
    // Check if subject already exists for this section
    $check_sql = "SELECT id FROM subjects WHERE subject_name = '$subject_name' AND section_id = $section_id";
    if ($subject_id > 0) {
        $check_sql .= " AND id != $subject_id";
    }
    
    $check_result = $conn->query($check_sql);
    if ($check_result && $check_result->num_rows > 0) {
        throw new Exception('Subject already exists for this section');
    }
    
    if ($subject_id > 0) {
        // Update existing subject
        $sql = "UPDATE subjects SET 
                subject_name = '$subject_name',
                grade_level = '$grade_level',
                section_id = $section_id,
                teacher_id = " . ($teacher_id > 0 ? $teacher_id : 'NULL') . ",
                subject_code = " . ($subject_code ? "'$subject_code'" : 'NULL') . ",
                subject_description = " . ($subject_description ? "'$subject_description'" : 'NULL') . "
                WHERE id = $subject_id";
    } else {
        // Insert new subject
        $sql = "INSERT INTO subjects (subject_name, grade_level, section_id, teacher_id, subject_code, subject_description) 
                VALUES ('$subject_name', '$grade_level', $section_id, " . 
                ($teacher_id > 0 ? $teacher_id : 'NULL') . ", " .
                ($subject_code ? "'$subject_code'" : 'NULL') . ", " .
                ($subject_description ? "'$subject_description'" : 'NULL') . ")";
    }
    
    if ($conn->query($sql)) {
        $message = $subject_id > 0 ? 'Subject updated successfully' : 'Subject added successfully';
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        throw new Exception('Database error: ' . $conn->error);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
