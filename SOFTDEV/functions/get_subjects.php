<?php
include __DIR__ . '/../db/db_connect.php';

header('Content-Type: application/json');

try {
    $grade_level = isset($_GET['grade_level']) ? $conn->real_escape_string($_GET['grade_level']) : '';
    $section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;
    
    $sql = "SELECT s.id, s.subject_name, s.subject_code, s.subject_description, s.grade_level, s.section_id, s.teacher_id,
                   sec.section as section_name, sec.grade_level as section_grade,
                   t.first_name, t.last_name, t.gender
            FROM subjects s
            LEFT JOIN section sec ON s.section_id = sec.id
            LEFT JOIN teachers t ON s.teacher_id = t.id
            WHERE 1=1";
    
    if (!empty($grade_level)) {
        $sql .= " AND s.grade_level = '$grade_level'";
    }
    
    if ($section_id > 0) {
        $sql .= " AND s.section_id = $section_id";
    }
    
    $sql .= " ORDER BY s.grade_level, sec.section, s.subject_name";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = [
            'id' => $row['id'],
            'subject_name' => $row['subject_name'],
            'subject_code' => $row['subject_code'],
            'subject_description' => $row['subject_description'],
            'grade_level' => $row['grade_level'],
            'section_id' => $row['section_id'],
            'section_name' => $row['section_name'],
            'section_grade' => $row['section_grade'],
            'teacher_id' => $row['teacher_id'],
            'teacher_name' => $row['first_name'] && $row['last_name'] ? 
                (($row['gender'] === 'Female') ? 'Ms.' : 'Mr.') . ' ' . $row['first_name'] . ' ' . $row['last_name'] : 'No teacher assigned'
        ];
    }
    
    echo json_encode(['success' => true, 'subjects' => $subjects]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>