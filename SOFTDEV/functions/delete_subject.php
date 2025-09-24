<?php
include __DIR__ . '/../db/db_connect.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $subject_id = intval($_POST['subject_id']);
    
    if ($subject_id <= 0) {
        throw new Exception('Invalid subject ID');
    }
    
    $sql = "DELETE FROM subjects WHERE id = $subject_id";
    
    if ($conn->query($sql)) {
        if ($conn->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Subject deleted successfully']);
        } else {
            throw new Exception('Subject not found');
        }
    } else {
        throw new Exception('Database error: ' . $conn->error);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
