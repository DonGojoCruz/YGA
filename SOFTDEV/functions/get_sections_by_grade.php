<?php
session_start();
require_once __DIR__ . '/../db/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get grade parameter
$grade = isset($_GET['grade']) ? $_GET['grade'] : '';

if (empty($grade)) {
    echo json_encode(['success' => false, 'message' => 'Grade parameter required']);
    exit();
}

// Get sections for the specified grade
$sql = "SELECT DISTINCT s.section FROM section s WHERE s.grade_level = ? ORDER BY s.section ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $grade);
$stmt->execute();
$result = $stmt->get_result();

$sections = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sections[] = htmlspecialchars($row['section']);
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'grade' => $grade,
    'sections' => $sections
]);
?>
