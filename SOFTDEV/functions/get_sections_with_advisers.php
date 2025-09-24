<?php
include __DIR__ . '/../db/db_connect.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT s.id, s.grade_level, s.section, 
                   t.id as teacher_id, t.first_name, t.last_name, t.gender
            FROM section s
            LEFT JOIN teachers t ON s.id = t.advisory_section_id
            ORDER BY 
              CASE 
                WHEN s.grade_level = 'Nursery' THEN 1
                WHEN s.grade_level = 'Kinder 1' THEN 2
                WHEN s.grade_level = 'Kinder 2' THEN 3
                WHEN s.grade_level REGEXP '^[0-9]+$' THEN 4
                ELSE 5
              END,
              CAST(s.grade_level AS UNSIGNED),
              s.section";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = [
            'id' => $row['id'],
            'grade_level' => $row['grade_level'],
            'section' => $row['section'],
            'teacher_id' => $row['teacher_id'],
            'adviser_id' => $row['teacher_id'],
            'adviser_name' => $row['first_name'] && $row['last_name'] ? 
                (($row['gender'] === 'Female') ? 'Ms.' : 'Mr.') . ' ' . $row['first_name'] . ' ' . $row['last_name'] : 'No adviser assigned'
        ];
    }
    
    echo json_encode(['success' => true, 'sections' => $sections]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
