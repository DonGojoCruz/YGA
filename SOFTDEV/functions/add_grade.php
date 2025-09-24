<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

include __DIR__ . '/../db/db_connect.php'; // adjust path if needed

if (isset($_POST['save_section'])) {
    $gradeLevel = trim($_POST['gradeLevel']);
    $section = trim($_POST['section']);
    $password = trim($_POST['password']);

    if (!empty($gradeLevel) && !empty($section)) {
        // Check if the grade level and section combination already exists
        $check_stmt = $conn->prepare("SELECT id FROM section WHERE grade_level = ? AND section = ? AND password = ?");
        $check_stmt->bind_param("sss", $gradeLevel, $section, $password);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            header("Location: ../sections.php?error=" . urlencode("Grade Level {$gradeLevel} - {$section} already exists"));
            exit();
        }
        
        $check_stmt->close();
        
        // Insert new grade level and section
        $stmt = $conn->prepare("INSERT INTO section (grade_level, section, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $gradeLevel, $section, $password);

        if ($stmt->execute()) {
            $newSectionId = $conn->insert_id;
            header("Location: ../sections.php?success=1&new_section_id=" . $newSectionId . "&grade_level=" . urlencode($gradeLevel) . "&section=" . urlencode($section));
            exit();
        } else {
            header("Location: ../sections.php?error=" . urlencode($conn->error));
            exit();
        }
        
        $stmt->close();
    } else {
        header("Location: ../sections.php?error=" . urlencode("Please fill in all fields"));
        exit();
    }
} else {
    // If accessed directly without form submission, redirect back
    header("Location: ../sections.php");
    exit();
}
?>
