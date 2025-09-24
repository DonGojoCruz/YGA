<?php
// Suppress all output except what we explicitly echo
ob_start();

require_once '../db/db_connect.php';

// Clear any output buffer content
ob_clean();

header('Content-Type: application/json');

try {
    // Get filter parameters
    $gradeLevel = $_GET['grade_level'] ?? '';
    $section = $_GET['section'] ?? '';

    // Build the SQL query with proper joins using the new schema
    $sql = "SELECT DISTINCT t.id, t.teacher_id, t.first_name, t.last_name, t.middle_initial,
                   t.grade_level, t.advisory_section_id,
                   COALESCE(adv_section.section, '') as advisory,
                   adv_section.grade_level as advisory_grade_level
            FROM teachers t
            LEFT JOIN section adv_section ON t.advisory_section_id = adv_section.id";

    $where = [];
    $params = [];
    $types = '';

    if (!empty($gradeLevel)) {
        $where[] = 't.grade_level = ?';
        $params[] = $gradeLevel;
        $types .= 's';
    }

    // Note: Section filtering is removed since we no longer have sections

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= " GROUP BY t.id, t.teacher_id, t.first_name, t.last_name, t.middle_initial,
                     t.grade_level, t.advisory_section_id, adv_section.section, adv_section.grade_level";

    // Custom ordering: Nursery, Kinder 1, Kinder 2 first, then numeric grades, then others
    $sql .= " ORDER BY
              CASE 
                WHEN t.grade_level = 'Nursery' THEN 1
                WHEN t.grade_level = 'Kinder 1' THEN 2
                WHEN t.grade_level = 'Kinder 2' THEN 3
                WHEN t.grade_level REGEXP '^[0-9]+$' THEN 4
                ELSE 5
              END,
              CAST(t.grade_level AS UNSIGNED),
              t.last_name, t.first_name ASC";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $teachers = [];

    while ($row = $result->fetch_assoc()) {
        // Clean up the advisory field - replace NULL with empty string
        $row['advisory'] = $row['advisory'] ?: '';
        
        $teachers[] = $row;
    }

    echo json_encode($teachers);

} catch (Exception $e) {
    error_log('Error in get_teachers.php: ' . $e->getMessage());
    // Clear any output before sending JSON
    ob_clean();
    echo json_encode([
        'error' => true,
        'message' => 'Failed to retrieve teachers'
    ]);
}

$conn->close();
?>