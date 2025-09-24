<?php
session_start();

// Prevent caching and going back to previous pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require '../vendor/autoload.php'; // PhpSpreadsheet (install via Composer)

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

include __DIR__ . '/../db/db_connect.php'; // your DB connection

// Optional filters (mirror the manage page table)
$grade = isset($_GET['grade_level']) ? trim($_GET['grade_level']) : '';
$section = isset($_GET['section']) ? trim($_GET['section']) : '';

$sql = "SELECT s.lrn, s.last_name, s.first_name, s.middle_initial, s.guardian, s.email, s.rfid_tag, s.grade_level, sec.section AS section_name
        FROM students s
        LEFT JOIN section sec ON s.section_id = sec.id";

$where = [];
$params = [];
$types = '';
if ($grade !== '') {
    $where[] = "s.grade_level = ?";
    $params[] = $grade;
    $types .= 's';
}
if ($section !== '') {
    $where[] = "sec.section = ?";
    $params[] = $section;
    $types .= 's';
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

// Consistent ordering similar to UI
$sql .= " ORDER BY 
          CASE 
            WHEN s.grade_level = 'Nursery' THEN 1
            WHEN s.grade_level = 'Kinder 1' THEN 2
            WHEN s.grade_level = 'Kinder 2' THEN 3
            WHEN s.grade_level REGEXP '^[0-9]+$' THEN 4
            ELSE 5
          END,
          CAST(s.grade_level AS UNSIGNED),
          sec.section ASC,
          s.last_name ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header row (match table in manage.php)
$headers = ["LRN", "Last Name", "First Name", "MI", "Guardian", "Email", "RFID Tag", "Grade", "Section"];
$colIndex = 1;
foreach ($headers as $header) {
    $cell = Coordinate::stringFromColumnIndex($colIndex) . '1';
    $sheet->setCellValue($cell, $header);
    $colIndex++;
}

// Fill data rows
$row = 2;
while ($student = $result->fetch_assoc()) {
    $colIndex = 1;
    // Map DB fields to desired output order/labels
    $values = [
        $student['lrn'],
        $student['last_name'],
        $student['first_name'],
        $student['middle_initial'],
        $student['guardian'],
        $student['email'],
        $student['rfid_tag'],
        is_numeric($student['grade_level']) ? 'Grade ' . $student['grade_level'] : $student['grade_level'],
        $student['section_name'],
    ];
    foreach ($values as $value) {
        $cell = Coordinate::stringFromColumnIndex($colIndex) . $row;
        $sheet->setCellValue($cell, $value);
        $colIndex++;
    }
    $row++;
}

// Output file for download
$filename = "students_" . date("Y-m-d") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
?>
