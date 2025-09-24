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
    // Get the latest teacher ID from the database
    $query = "SELECT teacher_id FROM teachers ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    $nextNumber = 1; // Default to T001 if no teachers exist
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastTeacherId = $row['teacher_id'];
        
        // Extract the number part from the teacher ID (e.g., T001 -> 001)
        if (preg_match('/^T(\d+)$/', $lastTeacherId, $matches)) {
            $lastNumber = intval($matches[1]);
            $nextNumber = $lastNumber + 1;
        }
    }
    
    // Format the next teacher ID with leading zeros (T001, T002, etc.)
    $nextTeacherId = 'T' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    
    // Check if this ID already exists (in case of manual entries)
    $checkQuery = "SELECT COUNT(*) as count FROM teachers WHERE teacher_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $nextTeacherId);
    $stmt->execute();
    $checkResult = $stmt->get_result();
    $countRow = $checkResult->fetch_assoc();
    
    // If ID exists, find the next available ID
    while ($countRow['count'] > 0) {
        $nextNumber++;
        $nextTeacherId = 'T' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $nextTeacherId);
        $stmt->execute();
        $checkResult = $stmt->get_result();
        $countRow = $checkResult->fetch_assoc();
    }
    
    echo json_encode([
        'success' => true,
        'next_teacher_id' => $nextTeacherId
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_next_teacher_id.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate next teacher ID'
    ]);
}

$conn->close();
?>















