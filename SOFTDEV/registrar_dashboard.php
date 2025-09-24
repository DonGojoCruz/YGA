<?php
session_start();

// Prevent caching and going back to previous pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in and is a registrar
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'registrar') {
    header("Location: index.php");
    exit();
}

$page_title = "Registrar Dashboard";

require_once __DIR__ . '/db/db_connect.php';

// Get total students
$totalStudents = 0;
$res = $conn->query("SELECT COUNT(*) AS cnt FROM students");
if ($res && $row = $res->fetch_assoc()) {
    $totalStudents = (int)$row['cnt'];
}

// Get active students (students with complete information)
$activeStudents = 0;
$activeQuery = "SELECT COUNT(*) AS cnt FROM students WHERE first_name IS NOT NULL AND last_name IS NOT NULL AND section_id IS NOT NULL";
$activeResult = $conn->query($activeQuery);
if ($activeResult && $row = $activeResult->fetch_assoc()) {
    $activeStudents = (int)$row['cnt'];
}

// Get total sections
$totalSections = 0;
$sectionsQuery = "SELECT COUNT(*) AS cnt FROM section";
$sectionsResult = $conn->query($sectionsQuery);
if ($sectionsResult && $row = $sectionsResult->fetch_assoc()) {
    $totalSections = (int)$row['cnt'];
}

// Get total subjects
$totalSubjects = 0;
$subjectsQuery = "SELECT COUNT(*) AS cnt FROM subjects";
$subjectsResult = $conn->query($subjectsQuery);
if ($subjectsResult && $row = $subjectsResult->fetch_assoc()) {
    $totalSubjects = (int)$row['cnt'];
}

// Get students added this week
$studentsThisWeek = 0;
$weekQuery = "SELECT COUNT(*) AS cnt FROM students WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$weekResult = $conn->query($weekQuery);
if ($weekResult && $row = $weekResult->fetch_assoc()) {
    $studentsThisWeek = (int)$row['cnt'];
}

// Get sections added this month
$sectionsThisMonth = 0;
$monthQuery = "SELECT COUNT(*) AS cnt FROM section WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$monthResult = $conn->query($monthQuery);
if ($monthResult && $row = $monthResult->fetch_assoc()) {
    $sectionsThisMonth = (int)$row['cnt'];
}

// Get subjects added this week
$subjectsThisWeek = 0;
$subjectsWeekQuery = "SELECT COUNT(*) AS cnt FROM subjects WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$subjectsWeekResult = $conn->query($subjectsWeekQuery);
if ($subjectsWeekResult && $row = $subjectsWeekResult->fetch_assoc()) {
    $subjectsThisWeek = (int)$row['cnt'];
}

// Calculate active percentage
$activePercentage = $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 1) : 0;

?>
<!doctype html>
<html lang="en">
<head>
    <title><?php echo $page_title; ?></title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/registrar_dashboard.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="layout">
        <?php include 'includes/registrar_sidebar.php'; ?>

        <main>
            <div class="registrar-dashboard">
                <!-- Key Metrics Cards -->
                <div class="metrics-grid">
                    <!-- Total Students Card -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon students">
                                👥
                            </div>
                        </div>
                        <div class="metric-title">Total Students</div>
                        <div class="metric-value"><?php echo number_format($totalStudents); ?></div>
                        <div class="metric-subtitle">
                            <span class="metric-change <?php echo $studentsThisWeek > 0 ? 'positive' : 'neutral'; ?>">
                                <svg class="metric-change-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                +<?php echo $studentsThisWeek; ?> this week
                            </span>
                        </div>
                    </div>

                    <!-- Active Students Card -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon active">
                                ✅
                            </div>
                        </div>
                        <div class="metric-title">Active Students</div>
                        <div class="metric-value"><?php echo number_format($activeStudents); ?></div>
                        <div class="metric-subtitle">
                            <span class="metric-change neutral">
                                <?php echo $activePercentage; ?>% completion rate
                            </span>
                        </div>
                    </div>

                    <!-- Sections Created Card -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon sections">
                                🏫
                            </div>
                        </div>
                        <div class="metric-title">Sections Created</div>
                        <div class="metric-value"><?php echo number_format($totalSections); ?></div>
                        <div class="metric-subtitle">
                            <span class="metric-change <?php echo $sectionsThisMonth > 0 ? 'positive' : 'neutral'; ?>">
                                <svg class="metric-change-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                +<?php echo $sectionsThisMonth; ?> this month
                            </span>
                        </div>
                    </div>

                    <!-- Subjects Assigned Card -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon subjects">
                                📚
                            </div>
                        </div>
                        <div class="metric-title">Subjects Assigned</div>
                        <div class="metric-value"><?php echo number_format($totalSubjects); ?></div>
                        <div class="metric-subtitle">
                            <span class="metric-change <?php echo $subjectsThisWeek > 0 ? 'positive' : 'neutral'; ?>">
                                <svg class="metric-change-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                +<?php echo $subjectsThisWeek; ?> this week
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>⚡ Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="manage.php" class="action-btn">
                            <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span class="action-text">Add New Student</span>
                        </a>
                        <a href="sections.php" class="action-btn">
                            <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span class="action-text">Create Section</span>
                        </a>
                        <a href="subjects.php" class="action-btn">
                            <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <span class="action-text">Assign Subject</span>
                        </a>
                        <a href="functions/export_students.php" class="action-btn">
                            <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="action-text">Export Data</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/scripts.php'; ?>
</body>
</html>
