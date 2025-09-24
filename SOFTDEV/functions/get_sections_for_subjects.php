<?php
include __DIR__ . '/../db/db_connect.php';

header('Content-Type: application/json');

try {
    $grade_level = isset($_GET['grade_level']) ? $conn->real_escape_string($_GET['grade_level']) : '';
    
    $sql = "SELECT id, grade_level, section FROM section";
    
    if (!empty($grade_level)) {
        $sql .= " WHERE grade_level = '$grade_level'";
    }
    
    $sql .= " ORDER BY 
              CASE 
                WHEN grade_level = 'Nursery' THEN 1
                WHEN grade_level = 'Kinder 1' THEN 2
                WHEN grade_level = 'Kinder 2' THEN 3
                WHEN grade_level REGEXP '^[0-9]+$' THEN 4
                ELSE 5
              END,
              CAST(grade_level AS UNSIGNED),
              section ASC";
    
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
            'display_name' => is_numeric($row['grade_level']) ? 
                "Grade " . $row['grade_level'] . " - " . $row['section'] : 
                $row['grade_level'] . " - " . $row['section']
        ];
    }
    
    echo json_encode(['success' => true, 'sections' => $sections]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
