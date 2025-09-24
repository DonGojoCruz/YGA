<?php
// Suppress all output except what we explicitly echo
ob_start();

require_once __DIR__ . '/../db/db_connect.php';

// Clear any output buffer content
ob_clean();

header('Content-Type: application/json');

try {
    $teacherId = $_GET['id'] ?? '';
    
    // Log the request for debugging
    error_log("get_teacher.php called with ID: " . $teacherId);

    if (empty($teacherId) || !is_numeric($teacherId)) {
        echo json_encode([
            'error' => true,
            'message' => 'Invalid teacher ID'
        ]);
        exit;
    }

    // Get teacher data with advisory information
    $sql = "SELECT t.*, 
                   COALESCE(adv_section.section, '') as advisory
            FROM teachers t
            LEFT JOIN section adv_section ON t.advisory_section_id = adv_section.id
            WHERE t.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'error' => true,
            'message' => 'Teacher not found'
        ]);
        exit;
    }

    $teacher = $result->fetch_assoc();

    echo json_encode($teacher);

} catch (Exception $e) {
    error_log('Error in get_teacher.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    // Clear any output before sending JSON
    ob_clean();
    echo json_encode([
        'error' => true,
        'message' => 'Failed to retrieve teacher data: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

