<?php
session_start();
header('Content-Type: application/json');

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'clear_display_session':
        // Clear display RFID session
        unset($_SESSION['selected_section_id']);
        unset($_SESSION['selected_grade_level']);
        unset($_SESSION['selected_section']);
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Display session cleared']);
        break;
        
    case 'clear_attendance_session':
        // Clear attendance view session
        unset($_SESSION['selected_section_id']);
        unset($_SESSION['selected_grade_level']);
        unset($_SESSION['selected_section']);
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Attendance session cleared']);
        break;
        
    case 'clear_all_sessions':
        // Clear all sessions
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'All sessions cleared']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>