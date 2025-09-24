<?php
require_once '../db/db_connect.php';

/**
 * Get all students from the database with optional filters
 *
 * @param string|null $gradeLevel
 * @param string|null $section
 * @return array
 */
function getStudents($gradeLevel = null, $section = null) {
    global $conn;

    try {
        $sql = "SELECT s.student_id AS id,
                       s.rfid_tag,
                       s.lrn,
                       s.last_name,
                       s.first_name,
                       s.middle_initial,
                       s.guardian,
                       s.email,
                       s.grade_level,
                       sec.section AS section_name
                FROM students s
                LEFT JOIN section sec ON s.section_id = sec.id";

        $where = [];
        $params = [];
        $types  = '';

        if ($gradeLevel !== null && $gradeLevel !== '') {
            $where[] = 's.grade_level = ?';
            $params[] = $gradeLevel;
            $types   .= 's';
        }

        if (!empty($section)) {
            $where[] = 'sec.section = ?';
            $params[] = $section;
            $types   .= 's';
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY s.last_name, s.first_name ASC';

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
        if ($result === false) {
            throw new Exception('Failed to get result: ' . $stmt->error);
        }

        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        return $students;

    } catch (Exception $e) {
        return [
            'error' => true,
            'message' => 'Failed to fetch students: ' . $e->getMessage()
        ];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $gradeLevel = isset($_GET['grade_level']) && $_GET['grade_level'] !== ''
        ? $_GET['grade_level']
        : null;
    $section = isset($_GET['section']) && $_GET['section'] !== ''
        ? $_GET['section']
        : null;

    $students = getStudents($gradeLevel, $section);
    echo json_encode($students, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
