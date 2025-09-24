<?php
// Try different paths for database connection
if (file_exists('../db/db_connect.php')) {
    require_once '../db/db_connect.php';
} elseif (file_exists('db/db_connect.php')) {
    require_once 'db/db_connect.php';
} else {
    die('Database connection file not found');
}

header('Content-Type: application/json');

try {
    // Get parameters
    $gradeLevel = $_GET['grade_level'] ?? '';
    $excludeTeacherId = $_GET['exclude_teacher_id'] ?? ''; // For edit mode
    
    // Build query with optional grade level filter
    $query = "SELECT section, grade_level FROM section";
    $params = [];
    
    if (!empty($gradeLevel)) {
        $query .= " WHERE grade_level = ?";
        $params[] = $gradeLevel;
    }
    
    $query .= " ORDER BY 
              CASE 
                WHEN grade_level = 'Nursery' THEN 1
                WHEN grade_level = 'Kinder 1' THEN 2
                WHEN grade_level = 'Kinder 2' THEN 3
                WHEN grade_level REGEXP '^[0-9]+$' THEN CAST(grade_level AS UNSIGNED) + 10
                ELSE 100
              END, section";
    
    if (!empty($params)) {
        // Use prepared statement when grade level is provided
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $params[0]);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Use regular query when no grade level filter
        $result = $conn->query($query);
    }
    
    if (!$result) {
        throw new Exception('Failed to fetch sections: ' . $conn->error);
    }
    
    // Get already assigned advisory sections using the new structure
    $advisoryQuery = "SELECT s.section FROM section s 
                      INNER JOIN teachers t ON s.id = t.advisory_section_id 
                      WHERE t.advisory_section_id IS NOT NULL";
    $advisoryParams = [];
    
    if (!empty($excludeTeacherId)) {
        $advisoryQuery .= " AND t.id != ?";
        $advisoryParams[] = $excludeTeacherId;
    }
    
    $advisoryStmt = $conn->prepare($advisoryQuery);
    if (!empty($advisoryParams)) {
        $advisoryStmt->bind_param("i", $advisoryParams[0]);
    }
    $advisoryStmt->execute();
    $advisoryResult = $advisoryStmt->get_result();
    
    $assignedAdvisories = [];
    while ($row = $advisoryResult->fetch_assoc()) {
        $assignedAdvisories[] = $row['section'];
    }
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        // Check if this section is already assigned as advisory
        $isAssigned = in_array($row['section'], $assignedAdvisories);
        
        $sections[] = [
            'section_name' => $row['section'],
            'grade_level' => $row['grade_level'],
            'display_name' => $row['section'] . ' (' . formatGradeLabel($row['grade_level']) . ')',
            'is_assigned' => $isAssigned
        ];
    }
    
    echo json_encode($sections);
    
} catch (Exception $e) {
    error_log('Error in get_all_sections.php: ' . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => 'Failed to retrieve sections'
    ]);
}

// Helper function to format grade label
function formatGradeLabel($grade) {
    return is_numeric($grade) ? "Grade {$grade}" : $grade;
}

$conn->close();
?>
