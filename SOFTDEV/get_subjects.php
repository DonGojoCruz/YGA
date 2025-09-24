<?php
require_once 'db/db_connect.php';

// Get grade and section from POST data
$grade = $_POST['grade'] ?? '';
$section = $_POST['section'] ?? '';

if (empty($grade) || empty($section)) {
    echo json_encode(['error' => 'Grade and section are required']);
    exit;
}

// Get section ID first
$stmt = $conn->prepare("SELECT id FROM section WHERE grade_level = ? AND section = ?");
$stmt->bind_param("ss", $grade, $section);
$stmt->execute();
$result = $stmt->get_result();
$section_data = $result->fetch_assoc();

if (!$section_data) {
    echo json_encode(['error' => 'Section not found']);
    exit;
}

$section_id = $section_data['id'];

// Get subjects for this section
$stmt = $conn->prepare("SELECT id, subject_name, subject_code FROM subjects WHERE section_id = ? ORDER BY subject_name");
$stmt->bind_param("i", $section_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = [
        'id' => $row['id'],
        'name' => $row['subject_name'],
        'code' => $row['subject_code']
    ];
}

header('Content-Type: application/json');
echo json_encode(['subjects' => $subjects]);
?>


