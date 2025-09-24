<?php
require_once '../db/db_connect.php';

header('Content-Type: application/json');

try {
    $grade_level = isset($_GET['grade_level']) ? $conn->real_escape_string($_GET['grade_level']) : '';
    
    if (empty($grade_level)) {
        echo json_encode(['success' => false, 'message' => 'Grade level is required']);
        exit;
    }
    
    // Get distinct subjects for the given grade level
    $sql = "SELECT DISTINCT subject_name 
            FROM subjects 
            WHERE grade_level = ? 
            ORDER BY subject_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $grade_level);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject_name'];
    }
    
    echo json_encode(['success' => true, 'subjects' => $subjects]);
    
} catch (Exception $e) {
    error_log('Error in get_subjects_by_grade.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching subjects']);
}

$conn->close();
?>


