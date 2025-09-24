<?php
header('Content-Type: application/json');

include '../db/db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Subject ID is required']);
    exit();
}

$subjectId = intval($_GET['id']);

try {
    // Get subject details with section and teacher information
    $sql = "SELECT s.*, 
                   sec.grade_level, 
                   sec.section as section_name,
                   t.first_name, 
                   t.last_name
            FROM subjects s
            LEFT JOIN section sec ON s.section_id = sec.id
            LEFT JOIN teachers t ON s.teacher_id = t.id
            WHERE s.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subjectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $subject = $result->fetch_assoc();
        
        // Format teacher name
        if ($subject['first_name'] && $subject['last_name']) {
            $subject['teacher_name'] = $subject['first_name'] . ' ' . $subject['last_name'];
        } else {
            $subject['teacher_name'] = 'Not assigned';
        }
        
        echo json_encode([
            'success' => true,
            'subject' => $subject
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Subject not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} finally {
    $stmt->close();
    $conn->close();
}
?>