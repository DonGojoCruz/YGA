<?php
include __DIR__ . '/../db/db_connect.php';

header('Content-Type: application/json');

try {
    $section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;
    
    if ($section_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid section ID']);
        exit;
    }
    
    // Get section details with adviser information
    $sql = "SELECT s.id, s.grade_level, s.section, s.password,
                   adviser.id as adviser_id, adviser.first_name as adviser_first_name, 
                   adviser.last_name as adviser_last_name, adviser.teacher_id as adviser_teacher_id,
                   adviser.subject as adviser_subject
            FROM section s
            LEFT JOIN teachers adviser ON s.id = adviser.advisory_section_id
            WHERE s.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Section not found']);
        exit;
    }
    
    $section = $result->fetch_assoc();
    
    // Get student count
    $studentCountSql = "SELECT COUNT(*) as student_count FROM students WHERE section_id = ?";
    $studentStmt = $conn->prepare($studentCountSql);
    $studentStmt->bind_param("i", $section_id);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();
    $studentCount = $studentResult->fetch_assoc()['student_count'];
    
    // Get subjects for this section
    $subjectsSql = "SELECT sub.id, sub.subject_name, sub.subject_code, sub.subject_description,
                           t.first_name, t.last_name, t.teacher_id
                    FROM subjects sub
                    LEFT JOIN teachers t ON sub.teacher_id = t.id
                    WHERE sub.section_id = ?
                    ORDER BY sub.subject_name";
    
    $subjectsStmt = $conn->prepare($subjectsSql);
    $subjectsStmt->bind_param("i", $section_id);
    $subjectsStmt->execute();
    $subjectsResult = $subjectsStmt->get_result();
    
    $subjects = [];
    while ($row = $subjectsResult->fetch_assoc()) {
        $subjects[] = [
            'id' => $row['id'],
            'subject_name' => $row['subject_name'],
            'subject_code' => $row['subject_code'],
            'subject_description' => $row['subject_description'],
            'teacher_name' => $row['first_name'] && $row['last_name'] ? 
                $row['first_name'] . ' ' . $row['last_name'] : 'No teacher assigned',
            'teacher_id' => $row['teacher_id']
        ];
    }
    
    // Format the response
    $response = [
        'success' => true,
        'section' => [
            'id' => $section['id'],
            'grade_level' => $section['grade_level'],
            'section' => $section['section'],
            'password' => $section['password'],
            'adviser_id' => $section['adviser_id'],
            'adviser_name' => $section['adviser_first_name'] && $section['adviser_last_name'] ? 
                $section['adviser_first_name'] . ' ' . $section['adviser_last_name'] : 'No adviser assigned',
            'adviser_teacher_id' => $section['adviser_teacher_id'],
            'adviser_subject' => $section['adviser_subject']
        ],
        'student_count' => $studentCount,
        'subjects' => $subjects
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>



