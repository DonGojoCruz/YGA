<?php
session_start();

// Note: This endpoint is used by searchBar.php before login, so we don't require user_id session

// Include database connection
include __DIR__ . '/../db/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Check if grade parameter is provided
    if (isset($_GET['grade']) && $_GET['grade'] !== '') {
        $grade = $_GET['grade'];
        
        // Prepare and execute query to get sections for the selected grade
        $stmt = $conn->prepare("SELECT DISTINCT section FROM section WHERE grade_level = ? ORDER BY section ASC");
        // Bind as string to support non-numeric grade labels like 'Nursery'
        $stmt->bind_param("s", $grade);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Fetch all sections
        $sections = [];
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row['section'];
        }
        
        // Return sections as JSON
        echo json_encode($sections);
        
        $stmt->close();
    } else {
        // Return empty array if no grade provided
        echo json_encode([]);
    }
} catch (Exception $e) {
    error_log('Error in get_sections.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch sections: ' . $e->getMessage()]);
}

$conn->close();
?>
