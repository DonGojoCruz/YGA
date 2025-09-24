<?php
include __DIR__ . '/../db/db_connect.php';

header('Content-Type: application/json');

try {
    $section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;
    
    if ($section_id <= 0) {
        throw new Exception("Invalid section ID");
    }
    
    $sql = "SELECT s.subject_name, s.subject_code, s.subject_description,
                   t.first_name, t.last_name, t.gender
            FROM subjects s
            LEFT JOIN teachers t ON s.teacher_id = t.id
            WHERE s.section_id = $section_id
            ORDER BY s.subject_name";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $teacher_name = $row['first_name'] && $row['last_name'] ? 
            (($row['gender'] === 'Female') ? 'Ms.' : 'Mr.') . ' ' . $row['first_name'] . ' ' . $row['last_name'] : 'No teacher assigned';
            
        $subjects[] = [
            'subject_name' => $row['subject_name'],
            'subject_code' => $row['subject_code'],
            'subject_description' => $row['subject_description'],
            'teacher_name' => $teacher_name
        ];
    }
    
    echo json_encode(['success' => true, 'subjects' => $subjects]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>




