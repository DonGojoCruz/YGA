<?php
session_start();
require_once __DIR__ . '/db/db_connect.php';

// Prevent caching and back button issues
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user has selected a section
if (!isset($_SESSION['selected_section_id']) || !isset($_SESSION['selected_grade_level']) || !isset($_SESSION['selected_section'])) {
    header('Location: searchBar.php');
    exit;
}

$gradeLevel = $_SESSION['selected_grade_level'];
$section = $_SESSION['selected_section'];
$sectionId = $_SESSION['selected_section_id'];

// Format grade level display
$gradeLevelDisplay = is_numeric($gradeLevel) ? "Grade Level: " . $gradeLevel : "Grade Level: " . $gradeLevel;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Young Gen. Academy - Attendance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/viewAttendance-inline.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f0f0;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #2c5f5f, #1a4040);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-content {
            flex: 1;
            text-align: center;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .header-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .header-info .grade-level {
            font-size: 1.1rem;
            font-weight: 600;
            color: #e0f7fa;
            margin: 0;
        }

        .header-info .section-name {
            font-size: 1rem;
            color: #b2dfdb;
            margin: 0;
        }

        .back-btn {
            background: linear-gradient(135deg, #2aa7b8, #1e8a9a);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            box-shadow: 0 2px 5px rgba(42, 167, 184, 0.2);
        }

        .back-btn:hover {
            background: linear-gradient(135deg, #1e8a9a, #2aa7b8);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(42, 167, 184, 0.3);
        }

        .display-rfid-btn {
            background: linear-gradient(135deg, #24a83d, #1e8f34);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            box-shadow: 0 2px 5px rgba(36, 168, 61, 0.2);
        }

        .display-rfid-btn:hover {
            background: linear-gradient(135deg, #1e8f34, #24a83d);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(36, 168, 61, 0.3);
        }

        .exit-btn {
            background: linear-gradient(135deg, #e03643, #c12e39);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            box-shadow: 0 2px 5px rgba(224, 54, 67, 0.2);
        }

        .exit-btn:hover {
            background: linear-gradient(135deg, #c12e39, #e03643);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(224, 54, 67, 0.3);
        }

        .content {
            max-width: 100%;
            margin: 20px auto;
            padding: 0 20px;
        }

        .main-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            min-height: 80vh;
            align-items: start;
        }

        .left-panel {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }



        .attendance-stats-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #20B2AA;
        }

        .attendance-stats-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .stats-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
            font-weight: 700;
            color: #20B2AA;
        }

        .stats-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(45deg, #4CAF50, #8BC34A);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .attendance-stats-content {
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .attendance-stats-content .no-data {
            text-align: center;
            color: #999;
            font-style: italic;
            font-size: 1rem;
        }

        .see-all-btn {
            background: linear-gradient(135deg, #0ea5b5, #0b8ea0);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(14, 165, 181, 0.2);
        }

        .see-all-btn:hover {
            background: linear-gradient(135deg, #0b8ea0, #0ea5b5);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(14, 165, 181, 0.3);
        }

        .subject-attendance {
            margin-bottom: 0;
            margin-right: 20px;
            flex: 1;
            min-width: 200px;
        }

        .subject-attendance:last-child {
            margin-right: 0;
        }

        .subjects-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .subjects-row:last-child {
            margin-bottom: 0;
        }

        .subject-label {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 4px;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        /* Consistent background color for all subject labels */
        .subject-label {
            background: #f5f5f5 !important;
            color: #333 !important;
        }

        .student-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-left: 0;
        }

        .student-item {
            padding: 6px 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #333;
            border-left: 3px solid #ddd;
        }

        .student-item.top-student {
            background: #e8f5e8;
            border-left-color: #4CAF50;
            font-weight: 700;
            font-size: 1rem;
        }

        .student-item.least-student {
            background: #ffeaea;
            border-left-color: #f44336;
            font-weight: 700;
            font-size: 1rem;
        }

        .student-item:not(.top-student):not(.least-student) {
            margin-left: 20px;
            font-size: 0.85rem;
            font-weight: 400;
        }

        .subjects-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .subjects-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .subjects-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
        }

        .subjects-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(45deg, #ff6b6b, #feca57);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .full-attendance-btn {
            background: linear-gradient(135deg, #2aa7b8, #1e8a9a);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(42, 167, 184, 0.2);
        }

        .full-attendance-btn:hover {
            background: linear-gradient(135deg, #1e8a9a, #2aa7b8);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(42, 167, 184, 0.3);
        }

        .subject-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #ddd;
            transition: all 0.3s ease;
        }

        .subject-card:hover {
            background: #e3f2fd;
            border-left-color: #2196f3;
            transform: translateX(5px);
        }

        .subject-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .subject-code {
            display: inline-block;
            background: #e0e0e0;
            color: #666;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .teacher-name {
            color: #666;
            font-style: italic;
            font-size: 0.95rem;
        }

        .no-subjects {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 40px 0;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #4a9eff, #007bff);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }

        .close-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 25px;
            max-height: calc(90vh - 100px);
            overflow-y: auto;
        }

        .attendance-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .date-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-selector label {
            font-weight: 600;
            color: #333;
        }

        .date-selector input[type="date"] {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .date-selector input[type="date"]:focus {
            outline: none;
            border-color: #007bff;
        }

        .export-btn {
            background: linear-gradient(135deg, #24a83d, #1e8f34);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(36, 168, 61, 0.2);
        }

        .export-btn:hover {
            background: linear-gradient(135deg, #1e8f34, #24a83d);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(36, 168, 61, 0.3);
        }

        .attendance-table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .attendance-table th {
            background: #f8f9fa;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .attendance-table th.student-col {
            width: 200px;
            min-width: 200px;
            max-width: 200px;
            text-align: left;
        }

        .attendance-table th.subject-col {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
            text-align: center;
        }

        .attendance-table td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
        }

        .attendance-table td.student-name {
            width: 200px;
            min-width: 200px;
            max-width: 200px;
            text-align: left;
        }

        .attendance-table td.subject-cell {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
            text-align: center;
        }

        .student-col {
            min-width: 200px;
            text-align: left !important;
            background: white;
            position: sticky;
            left: 0;
            z-index: 5;
            border-right: 2px solid #ddd;
        }

        .subject-col {
            min-width: 100px;
        }

        .student-name {
            font-weight: 600;
            color: #333;
            text-align: left;
            background: white;
            position: sticky;
            left: 0;
            z-index: 5;
            border-right: 2px solid #ddd;
        }

        .attendance-cell {
            font-weight: 600;
            border-radius: 4px;
        }

        .attendance-cell.present {
            background: #d4edda;
            color: #155724;
        }

        .attendance-cell.late {
            background: #fff3cd;
            color: #856404;
        }

        .attendance-cell.absent {
            background: #f8d7da;
            color: #721c24;
        }

        .no-students, .error {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .error {
            color: #dc3545;
        }

        /* Subject Attendance Modal Styles */
        .attendance-controls {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            gap: 20px;
        }

        .attendance-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            flex: 1;
            max-width: 800px;
        }

        .export-controls {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .save-edits-btn {
            background: linear-gradient(135deg, #24a83d, #1e8f34);
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 0.5rem;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(36, 168, 61, 0.2);
        }

        .save-edits-btn:hover {
            background: linear-gradient(135deg, #1e8f34, #24a83d);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(36, 168, 61, 0.3);
        }

        .save-edits-btn:disabled {
            transform: none;
        }

        .export-btn {
            background: linear-gradient(135deg, #24a83d, #1e8f34);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(36, 168, 61, 0.2);
        }

        .export-btn:hover {
            background: linear-gradient(135deg, #1e8f34, #24a83d);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(36, 168, 61, 0.3);
        }

        .summary-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .summary-card.present {
            border-left-color: #22c55e;
        }

        .summary-card.late {
            border-left-color: #f59e0b;
        }

        .summary-card.absent {
            border-left-color: #ef4444;
        }

        .summary-card.total {
            border-left-color: #6366f1;
        }

        .summary-card.clickable:hover {
            background: #f8fafc;
        }

        .summary-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .summary-icon.present {
            background: #22c55e;
        }

        .summary-icon.late {
            background: #f59e0b;
        }

        .summary-icon.absent {
            background: #ef4444;
        }

        .summary-icon.total {
            background: #6366f1;
        }

        .summary-info .summary-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1;
        }

        .summary-info .summary-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 2px;
        }

        .attendance-table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table th {
            background: #f8fafc;
            color: #374151;
            font-weight: 600;
            padding: 15px 12px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }

        .attendance-table th:nth-child(1) { width: 4%; }
        .attendance-table th:nth-child(2) { width: 20%; }
        .attendance-table th:nth-child(3) { width: 13%; }
        .attendance-table th:nth-child(4) { width: 13%; }
        .attendance-table th:nth-child(5) { width: 15%; }
        .attendance-table th:last-child { width: 360px; }

        .attendance-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.9rem;
        }

        .attendance-table tbody tr:hover {
            background: #f8fafc;
        }

        .attendance-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status-editor {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-select {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .status-select:focus {
            outline: none;
            border-color: #2aa7b8;
            box-shadow: 0 0 0 3px rgba(42, 167, 184, 0.1);
        }

        .status-select.present {
            background: #f0fdf4;
            border-color: #22c55e;
            color: #15803d;
        }

        .status-select.late {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #d97706;
        }

        .status-select.absent {
            background: #fef2f2;
            border-color: #ef4444;
            color: #dc2626;
        }

        .edit-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: #ffffff;
            margin-top: 0.25rem;
            margin-left: 8px;
            padding: 6px 10px;
            border-radius: 6px;
            line-height: 1;
            height: 36px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .edit-indicator.edited {
            background: #f59e0b;
            border: 2px solid #f59e0b;
            color: #ffffff;
        }

        .edit-indicator.saved {
            background: #16a34a;
            border: 2px solid #16a34a;
            color: #ffffff;
        }

        .edit-indicator i {
            color: #ffffff !important;
            font-size: 0.9rem;
        }

        .description-editor {
            width: 100%;
        }
        
        .description-input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: none;
        }
        
        .description-input.show {
            display: block;
        }

        /* Enlarge subject attendance modal */
        #subjectAttendanceModal .modal-content {
            width: 1150px;
            max-width: 92vw;
        }


        /* Daily/Monthly Full Attendance Modal styles */
        #dailyAttendanceModal .modal-content {
            width: 1150px;
            max-width: 95vw;
        }

        .attendance-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .tab-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            background: #e5f3f3;
            color: #0f766e;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .tab-btn:hover {
            background: #d1f2eb;
            transform: translateY(-1px);
        }

        .tab-btn.active {
            background: #169e9b;
            color: #fff;
            box-shadow: 0 2px 8px rgba(22, 158, 155, 0.3);
        }

        .tab-btn i {
            font-size: 1rem;
        }

        .daily-attendance-controls,
        .monthly-attendance-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .date-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-selector label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .date-selector input[type="date"] {
            padding: 8px 12px;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }

        .date-selector input[type="date"]:focus {
            outline: none;
            border-color: #169e9b;
            box-shadow: 0 0 0 3px rgba(22, 158, 155, 0.1);
        }

        .month-year-selector {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .calendar-picker {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .calendar-picker label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .calendar-input-wrapper {
            position: relative;
        }

        .calendar-input {
            padding: 8px 12px;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }

        .calendar-input:focus {
            outline: none;
            border-color: #169e9b;
            box-shadow: 0 0 0 3px rgba(22, 158, 155, 0.1);
        }

        .calendar-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
        }

        .subject-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .subject-filter label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .subject-select-wrapper {
            position: relative;
        }

        .subject-select {
            padding: 8px 12px;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s;
            appearance: none;
            padding-right: 35px;
        }

        .subject-select:focus {
            outline: none;
            border-color: #169e9b;
            box-shadow: 0 0 0 3px rgba(22, 158, 155, 0.1);
        }

        .subject-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
            font-size: 0.8rem;
        }

        .daily-legend, .monthly-legend {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .legend-title { 
            font-weight: 700; 
            color: #374151; 
            font-size: 0.9rem;
        }
        
        .legend-items { 
            display: flex; 
            gap: 20px; 
            flex-wrap: wrap;
        }
        
        .legend-item { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            font-size: 0.85rem; 
            color: #374151; 
        }
        
        .legend-color { 
            width: 16px; 
            height: 16px; 
            border-radius: 3px; 
            border: 1px solid #d1d5db; 
        }
        
        .legend-color.present { 
            background: #d4edda; 
            border-color: #a3d9a5; 
        }
        
        .legend-color.absent { 
            background: #f8d7da; 
            border-color: #f1aeb5; 
        }
        
        .legend-color.late { 
            background: #fff3cd; 
            border-color: #ffe08a; 
        }
        
        .legend-color.weekend { 
            background: #dbeafe; 
            border-color: #93c5fd; 
        }

        .daily-attendance-table-container,
        .monthly-attendance-table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-top: 10px;
        }

        .daily-attendance-table,
        .monthly-attendance-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 500px;
        }

        .daily-attendance-table th,
        .daily-attendance-table td,
        .monthly-attendance-table th,
        .monthly-attendance-table td {
            white-space: nowrap;
            text-align: center;
            padding: 12px 8px;
            border-bottom: 1px solid #e9ecef;
        }

        .daily-attendance-table th,
        .monthly-attendance-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #374151;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .daily-attendance-table th:first-child,
        .monthly-attendance-table th:first-child {
            text-align: left;
            position: sticky;
            left: 0;
            background: #f8f9fa;
            z-index: 11;
            min-width: 200px;
        }

        .daily-attendance-table td:first-child,
        .monthly-attendance-table td:first-child {
            text-align: left;
            position: sticky;
            left: 0;
            background: white;
            z-index: 5;
            font-weight: 600;
            color: #374151;
            min-width: 200px;
        }

        .attendance-cell {
            font-weight: 600;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .attendance-cell.present {
            background: #d4edda;
            color: #155724;
        }

        .attendance-cell.late {
            background: #fff3cd;
            color: #856404;
        }

        .attendance-cell.absent {
            background: #f8d7da;
            color: #721c24;
        }

        .monthly-attendance-table th.weekend,
        .monthly-attendance-table td.weekend {
            background: #dbeafe;
            color: #1e40af;
        }

        .monthly-attendance-table td.weekend.attendance-cell {
            background: #dbeafe;
            color: #1e40af;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }

        .no-students {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }


        /* Exit Confirmation Modal */
        .exit-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }

        .exit-modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .exit-modal h3 {
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .exit-modal p {
            color: #666;
            font-size: 1rem;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .exit-modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .exit-modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            min-width: 80px;
        }

        .exit-modal-yes {
            background: #dc3545;
            color: white;
        }

        .exit-modal-yes:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .exit-modal-no {
            background: #6c757d;
            color: white;
        }

        .exit-modal-no:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        /* Subjects Grid Modal Styles */
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .subject-card-modal {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .subject-card-modal:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #20B2AA;
        }

        .subject-card-modal.science {
            background: linear-gradient(135deg, #e8f5e8, #f1f8e9);
            border-color: #4CAF50;
        }

        .subject-card-modal.math {
            background: linear-gradient(135deg, #fff8e1, #fff3c4);
            border-color: #FFC107;
        }

        .subject-card-modal.history {
            background: linear-gradient(135deg, #efebe9, #f3e5f5);
            border-color: #795548;
        }

        .subject-card-modal.analytics {
            background: linear-gradient(135deg, #eceff1, #f5f5f5);
            border-color: #607D8B;
        }

        .subject-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .subject-stats {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
        }

        .subject-click-hint {
            font-size: 0.8rem;
            color: #20B2AA;
            font-style: italic;
            font-weight: 600;
        }

        /* Students List Modal Styles */
        .students-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .student-item-modal {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #ddd;
            transition: all 0.2s ease;
        }

        .student-item-modal:hover {
            background: #e3f2fd;
            border-left-color: #2196f3;
            transform: translateX(5px);
        }

        .student-item-modal.top-student {
            background: #e8f5e8;
            border-left-color: #4CAF50;
        }

        .student-item-modal.least-student {
            background: #ffeaea;
            border-left-color: #f44336;
        }

        .student-info {
            display: flex;
            flex-direction: column;
        }

        .student-name {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }

        .student-attendance {
            font-size: 0.9rem;
            color: #666;
            margin-top: 2px;
        }

        .attendance-percentage {
            font-size: 1.1rem;
            font-weight: 700;
            color: #20B2AA;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .header-content {
                order: 1;
            }
            
            .header h1 {
                font-size: 1.4rem;
                margin-bottom: 6px;
            }
            
            .header-info .grade-level {
                font-size: 1rem;
            }
            
            .header-info .section-name {
                font-size: 0.9rem;
            }
            
            .back-btn {
                order: 0;
                align-self: flex-start;
                font-size: 0.85rem;
                padding: 8px 12px;
            }
            
            .exit-btn {
                order: 2;
                align-self: flex-end;
                font-size: 0.85rem;
                padding: 8px 16px;
            }

            /* Daily/Monthly modal mobile styles */
            #dailyAttendanceModal .modal-content {
                width: 98%;
                max-width: 98vw;
                margin: 10px;
            }

            .attendance-tabs {
                flex-direction: column;
                gap: 8px;
            }

            .tab-btn {
                justify-content: center;
                padding: 10px 16px;
                font-size: 0.9rem;
            }

            .daily-attendance-controls,
            .monthly-attendance-controls {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .month-year-selector {
                flex-direction: column;
                gap: 15px;
            }

            .calendar-picker,
            .subject-filter {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .daily-legend,
            .monthly-legend {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .legend-items {
                flex-direction: column;
                gap: 8px;
            }

            .daily-attendance-table-container,
            .monthly-attendance-table-container {
                font-size: 0.8rem;
            }

            .daily-attendance-table th,
            .daily-attendance-table td,
            .monthly-attendance-table th,
            .monthly-attendance-table td {
                padding: 8px 4px;
                font-size: 0.75rem;
            }

            .daily-attendance-table th:first-child,
            .monthly-attendance-table th:first-child {
                min-width: 120px;
            }

            .daily-attendance-table td:first-child,
            .monthly-attendance-table td:first-child {
                min-width: 120px;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 12px;
                gap: 12px;
            }
            
            .header h1 {
                font-size: 1.2rem;
            }
            
            .back-btn, .exit-btn {
                font-size: 0.8rem;
                padding: 6px 10px;
            }
            
            .exit-modal-content {
                padding: 25px 20px;
                margin: 10px;
            }
            
            .exit-modal h3 {
                font-size: 1.2rem;
            }
            
            .exit-modal p {
                font-size: 0.95rem;
            }
            
            .exit-modal-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .exit-modal-btn {
                width: 100%;
                padding: 14px;
            }
            
            .content {
                margin: 20px auto;
                padding: 0 15px;
            }

            .main-layout {
                grid-template-columns: 1fr;
                gap: 20px;
                min-height: auto;
            }

            .left-panel {
                order: 1;
                gap: 15px;
            }

            .right-panel {
                order: 2;
                gap: 15px;
            }

            .attendance-stats-container {
                padding: 20px;
            }

            .stats-title {
                font-size: 1.1rem;
            }

            .stats-icon {
                width: 20px;
                height: 20px;
                font-size: 12px;
            }

            .subjects-row {
                flex-direction: column;
                gap: 10px;
            }

            .subject-attendance {
                margin-right: 0;
                margin-bottom: 15px;
                min-width: auto;
            }

            
            .subjects-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .modal-content {
                width: 98%;
                margin: 10px;
            }

            .modal-header h2 {
                font-size: 1.1rem;
            }

            .attendance-controls {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .date-selector {
                justify-content: center;
            }

            .stats-container {
                flex-direction: column;
                gap: 10px;
            }

            .stat-card {
                min-width: auto;
            }

            .subject-export-btn {
                margin-left: 0;
                width: 100%;
            }

            .subject-attendance-table-container {
                font-size: 14px;
            }

            .subject-attendance-table th,
            .subject-attendance-table td {
                padding: 8px 6px;
            }

            .status-dropdown {
                font-size: 12px;
                padding: 4px 6px;
            }

            .subjects-grid {
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 15px 0;
            }

            .subject-card-modal {
                padding: 15px;
            }

            .subject-title {
                font-size: 1.1rem;
            }

            .subject-stats {
                font-size: 0.85rem;
            }

            .student-item-modal {
                padding: 12px;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .student-info {
                width: 100%;
            }

            .attendance-percentage {
                font-size: 1rem;
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-controls">
            <button class="back-btn" onclick="goBack()">
                ← Back to Menu
            </button>
            <button class="display-rfid-btn" onclick="startDisplayRFID()">
                📡 Display RFID
            </button>
        </div>
        <div class="header-content">
        <h1>Young Gen. Academy</h1>
            <div class="header-info">
            <div class="grade-level"><?php echo htmlspecialchars($gradeLevelDisplay); ?></div>
            <div class="section-name">Section: <?php echo htmlspecialchars($section); ?></div>
        </div>
        </div>
        <button class="exit-btn" onclick="showExitModal()">Exit</button>
    </header>

    <div class="content">
        <!-- Main Layout Grid -->
        <div class="main-layout">
            <!-- Left Side - Subjects -->
            <div class="left-panel">
        <div class="subjects-container">
            <div class="subjects-header">
                <div class="subjects-title">
                    <div class="subjects-icon">📚</div>
                    Subjects
                </div>
                <button class="full-attendance-btn" onclick="viewFullAttendance()">Full Attendance</button>
            </div>

            <?php
            // Get subjects for this section
            $subjects = [];
            if ($stmt = $conn->prepare('
                SELECT s.id AS subject_id, s.subject_name, s.subject_code, s.subject_description,
                       CASE 
                           WHEN t.first_name IS NOT NULL AND t.last_name IS NOT NULL 
                           THEN CONCAT(IF(t.gender = "Female", "Ms. ", "Mr. "), t.first_name, " ", t.last_name)
                           ELSE "No teacher assigned"
                       END as teacher_name
                FROM subjects s 
                LEFT JOIN teachers t ON s.teacher_id = t.id 
                WHERE s.section_id = ? 
                ORDER BY s.subject_name ASC
            ')) {
                $stmt->bind_param('i', $sectionId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $subjects[] = $row;
                    }
                }
                $stmt->close();
            }

            if (count($subjects) > 0):
                foreach ($subjects as $subject): ?>
                    <div class="subject-card" onclick="viewSubjectAttendance(<?php echo (int)$subject['subject_id']; ?>, '<?php echo htmlspecialchars($subject['subject_name']); ?>')">
                        <div class="subject-name"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                        <div class="subject-code"><?php echo htmlspecialchars($subject['subject_code']); ?></div>
                        <div class="teacher-name"><?php echo htmlspecialchars($subject['teacher_name']); ?></div>
                    </div>
                <?php endforeach;
            else: ?>
                <div class="no-subjects">
                    No subjects found for this section.
                </div>
            <?php endif; ?>
        </div>
    </div>

            <!-- Right Side - Attendance Stats -->
            <div class="right-panel">
                <!-- Top Attendance Container -->
                <div class="attendance-stats-container">
                    <div class="attendance-stats-header">
                        <div class="stats-title">
                            <div class="stats-icon">📊</div>
                            Top Attendance
            </div>
                        <button class="see-all-btn" onclick="viewAllSubjects('top')">See Top Students</button>
                        </div>
                    <div class="attendance-stats-content" id="topAttendanceContent">
                        <div class="no-data">Loading attendance data...</div>
                        </div>
                    </div>
                    
                <!-- Least Attendance Container -->
                <div class="attendance-stats-container">
                    <div class="attendance-stats-header">
                        <div class="stats-title">
                            <div class="stats-icon">📉</div>
                            Least Attendance
                        </div>
                        <button class="see-all-btn" onclick="viewAllSubjects('least')">See All Students</button>
                    </div>
                    <div class="attendance-stats-content" id="leastAttendanceContent">
                        <div class="no-data">Loading attendance data...</div>
                    </div>
                </div>
                        </div>
                    </div>
                </div>

    <!-- Subject Attendance Modal -->
    <div id="subjectAttendanceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="subjectAttendanceTitle">Subject Attendance</h2>
                <span class="close" onclick="closeSubjectAttendanceModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="attendance-controls">
                    <div class="attendance-summary" id="attendanceSummary">
                        <!-- Summary cards will be loaded here -->
                    </div>
                    <div class="export-controls">
                        <button class="save-edits-btn" id="saveEditsBtn" onclick="saveAttendanceEdits()">
                            <i class="fa-solid fa-save"></i>
                            <span>Save Edits</span>
                        </button>
                        <button class="export-btn" id="exportBtn" onclick="exportSubjectAttendance()">
                            <i class="fa-solid fa-download"></i>
                            <span>Export</span>
                        </button>
                    </div>
                </div>
                
                <div class="attendance-table-container">
                    <div class="attendance-note">
                        Note: Only today's attendance can be edited.
                    </div>
                    <table class="attendance-table" id="attendanceTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Attendance data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily/Monthly Full Attendance Modal (mirrors subAttendance) -->
    <div id="dailyAttendanceModal" class="modal">
        <div class="modal-content daily-attendance-modal">
            <div class="modal-header">
                <h2><?php echo htmlspecialchars($gradeLevelDisplay); ?> - <?php echo htmlspecialchars($section); ?> - Full Subject Attendance</h2>
                <span class="close-btn" onclick="closeDailyAttendanceModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="attendance-tabs">
                    <button class="tab-btn active" id="dailyTab" onclick="switchTab('daily')">
                        <i class="fa-solid fa-calendar-day"></i>
                        <span>Daily</span>
                    </button>
                    <button class="tab-btn" id="monthlyTab" onclick="switchTab('monthly')">
                        <i class="fa-solid fa-calendar"></i>
                        <span>Monthly</span>
                    </button>
                </div>
                
                <div class="daily-attendance-controls" id="dailyControls">
                    <div class="date-selector">
                        <label for="attendanceDate">Select Date:</label>
                        <input type="date" id="attendanceDate" onchange="loadDailyAttendance()">
                    </div>
                    <div class="export-controls">
                        <button class="export-btn" id="exportDailyBtn" onclick="exportDailyAttendance()">
                            <i class="fa-solid fa-download"></i>
                            <span>Export</span>
                        </button>
                    </div>
                </div>
                
                <div class="monthly-attendance-controls" id="monthlyControls">
                    <div class="month-year-selector">
                        <div class="calendar-picker">
                            <label for="monthYearPicker">Select Month & Year:</label>
                            <div class="calendar-input-wrapper">
                                <input type="month" id="monthYearPicker" class="calendar-input" onchange="loadMonthlyAttendance()">
                                <i class="fa-solid fa-calendar-alt calendar-icon"></i>
                            </div>
                        </div>
                        <div class="subject-filter">
                            <label for="subjectFilter">Filter by Subject:</label>
                            <div class="subject-select-wrapper">
                                <select id="subjectFilter" class="subject-select" onchange="loadMonthlyAttendance()">
                                    <option value="all">All Subjects (Combined)</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo (int)$subject['subject_id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fa-solid fa-chevron-down subject-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="export-controls">
                        <button class="export-btn" id="exportMonthlyBtn" onclick="exportMonthlyAttendance()">
                            <i class="fa-solid fa-download"></i>
                            <span>Export</span>
                        </button>
                    </div>
                </div>

                <!-- Daily Legend -->
                <div class="daily-legend" id="dailyLegend">
                    <div class="legend-title">Legend:</div>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="legend-color present"></div>
                            <span>Present</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color absent"></div>
                            <span>Absent</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color late"></div>
                            <span>Late</span>
                        </div>
                    </div>
                </div>

                <!-- Monthly Legend -->
                <div class="monthly-legend" id="monthlyLegend">
                    <div class="legend-title">Legend:</div>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="legend-color present"></div>
                            <span>Present</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color absent"></div>
                            <span>Absent</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color late"></div>
                            <span>Late</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color weekend"></div>
                            <span>Weekends (Saturday & Sunday)</span>
                        </div>
                    </div>
                </div>

                <div class="daily-attendance-table-container" id="dailyTableContainer">
                    <table class="daily-attendance-table" id="dailyAttendanceTable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <!-- Subject columns will be dynamically added here -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Daily attendance data will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <div class="monthly-attendance-table-container" id="monthlyTableContainer">
                    <table class="monthly-attendance-table" id="monthlyAttendanceTable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <!-- Date columns will be dynamically added here -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Monthly attendance data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Exit Confirmation Modal -->
    <div class="exit-modal" id="exitModal">
        <div class="exit-modal-content">
            <h3>Confirm Exit</h3>
            <p>Are you sure you want to exit and return to the search page? This will clear your current session.</p>
            <div class="exit-modal-buttons">
                <button class="exit-modal-btn exit-modal-yes" onclick="confirmExit()">Yes</button>
                <button class="exit-modal-btn exit-modal-no" onclick="closeExitModal()">No</button>
            </div>
        </div>
    </div>

    <!-- Top Students Modal -->
    <div class="modal" id="topStudentsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Top Students - All Subjects</h2>
                <span class="close-btn" onclick="closeTopStudentsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="subjects-grid" id="topSubjectsGrid">
                    <!-- Subjects will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Least Attendance Modal -->
    <div class="modal" id="leastAttendanceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Students with Least Attendance - All Subjects</h2>
                <span class="close-btn" onclick="closeLeastAttendanceModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="subjects-grid" id="leastSubjectsGrid">
                    <!-- Subjects will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Subject Students Modal -->
    <div class="modal" id="subjectStudentsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="subjectStudentsTitle">Subject Students</h2>
                <span class="close-btn" onclick="closeSubjectStudentsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="students-list" id="studentsList">
                    <!-- Students will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function goBack() {
            // Navigate directly to section dashboard
            window.location.href = 'sectionDashboard.php';
        }

        // Function to start display RFID scripts and navigate
        async function startDisplayRFID() {
            const button = document.querySelector('.display-rfid-btn');
            const originalText = button.innerHTML;
            
            try {
                // Show loading state
                button.disabled = true;
                button.innerHTML = '<span class="loading-spinner"></span> Loading...';
                
                // First stop any running RFID scripts
                await executeStopRFIDScript();
                
                // Start runRFID scripts for display
                await executeStartDisplayRFIDScript();
                
                // Navigate to display page
                window.location.href = 'displayRFID.php';
            } catch (error) {
                console.error('Error starting display RFID:', error);
                alert('Error starting RFID display. Please try again.');
                
                // Restore button state on error
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }

        // Function to execute stop RFID script
        function executeStopRFIDScript() {
            return fetch('functions/execute_rfid_script.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=stop'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('RFID stop script executed successfully');
                    return data;
                } else {
                    console.warn('Failed to execute RFID stop script:', data.message);
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error executing RFID stop script:', error);
                throw error;
            });
        }

        // Function to execute start display RFID script
        function executeStartDisplayRFIDScript() {
            return fetch('functions/execute_rfid_script.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=start_display'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('RFID display script executed successfully');
                    return data;
                } else {
                    console.warn('Failed to execute RFID display script:', data.message);
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error executing RFID display script:', error);
                throw error;
            });
        }

        function showExitModal() {
            document.getElementById('exitModal').style.display = 'flex';
        }

        function closeExitModal() {
            document.getElementById('exitModal').style.display = 'none';
        }

        function confirmExit() {
            // Close the modal first
            closeExitModal();
            
            // Clear session data on server side first
            fetch('functions/clear_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clear_attendance_session'
            })
            .then(response => response.json())
            .then(data => {
                // Replace current page in history to prevent back navigation
                window.location.replace('searchBar.php');
                
                // Additional security: clear browser history for this session
                if (window.history && window.history.pushState) {
                    window.history.replaceState(null, '', 'searchBar.php');
                    window.history.pushState(null, '', 'searchBar.php');
                    
                    // Prevent back button from working
                    window.addEventListener('popstate', function(event) {
                        window.location.replace('searchBar.php');
                    });
                }
            })
            .catch(error => {
                console.error('Error clearing session:', error);
                // Still redirect even if session clear fails
                window.location.replace('searchBar.php');
            });
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const exitModal = document.getElementById('exitModal');
            const topStudentsModal = document.getElementById('topStudentsModal');
            const leastAttendanceModal = document.getElementById('leastAttendanceModal');
            const subjectStudentsModal = document.getElementById('subjectStudentsModal');
            const dailyAttendanceModal = document.getElementById('dailyAttendanceModal');
            const subjectAttendanceModal = document.getElementById('subjectAttendanceModal');
            
            if (event.target === exitModal) {
                closeExitModal();
            }
            if (event.target === topStudentsModal) {
                closeTopStudentsModal();
            }
            if (event.target === leastAttendanceModal) {
                closeLeastAttendanceModal();
            }
            if (event.target === subjectStudentsModal) {
                closeSubjectStudentsModal();
            }
            if (event.target === dailyAttendanceModal) {
                closeDailyAttendanceModal();
            }
            if (event.target === subjectAttendanceModal) {
                closeSubjectAttendanceModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDailyAttendanceModal();
                closeSubjectAttendanceModal();
                closeTopStudentsModal();
                closeLeastAttendanceModal();
                closeSubjectStudentsModal();
                closeExitModal();
            }
        });

        // Daily/Monthly modal behavior
        const sectionId = <?php echo (int)$sectionId; ?>;
        let currentDailyAttendanceData = null;
        let currentMonthlyAttendanceData = null;
        let currentTab = 'daily';

        // Helper function to convert attendance status to single letter
        function getStatusLetter(status) {
            switch (status?.toLowerCase()) {
                case 'present': return 'P';
                case 'absent': return 'A';
                case 'late': return 'L';
                default: return 'A'; // Default to Absent
            }
        }

        function viewFullAttendance() {
            const modal = document.getElementById('dailyAttendanceModal');
            const dateInput = document.getElementById('attendanceDate');
            const monthYearPicker = document.getElementById('monthYearPicker');
            
            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            dateInput.value = today;
            
            // Set current month and year for monthly tab
            const currentDate = new Date();
            const currentMonthYear = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}`;
            monthYearPicker.value = currentMonthYear;
            
            // Show modal
            modal.style.display = 'flex';
            
            // Reset to daily tab and load daily attendance data
            switchTab('daily');
            
            // Force style refresh
            setTimeout(() => {
                const tabs = modal.querySelectorAll('.tab-btn');
                tabs.forEach(tab => {
                    tab.style.display = 'none';
                    tab.offsetHeight; // Trigger reflow
                    tab.style.display = 'flex';
                });
            }, 100);
        }

        function closeDailyAttendanceModal() {
            const modal = document.getElementById('dailyAttendanceModal');
            modal.style.display = 'none';
            modal.classList.remove('monthly-view');
            currentDailyAttendanceData = null;
            currentMonthlyAttendanceData = null;
            currentTab = 'daily';
        }

        // Switch between daily and monthly tabs
        function switchTab(tab) {
            const dailyTab = document.getElementById('dailyTab');
            const monthlyTab = document.getElementById('monthlyTab');
            const dailyControls = document.getElementById('dailyControls');
            const monthlyControls = document.getElementById('monthlyControls');
            const dailyTableContainer = document.getElementById('dailyTableContainer');
            const monthlyTableContainer = document.getElementById('monthlyTableContainer');
            const dailyLegend = document.getElementById('dailyLegend');
            const monthlyLegend = document.getElementById('monthlyLegend');
            const modal = document.getElementById('dailyAttendanceModal');
            
            if (tab === 'daily') {
                dailyTab.classList.add('active');
                monthlyTab.classList.remove('active');
                dailyControls.style.display = 'flex';
                monthlyControls.style.display = 'none';
                dailyLegend.style.display = 'flex';
                monthlyLegend.style.display = 'none';
                dailyTableContainer.style.display = 'block';
                monthlyTableContainer.style.display = 'none';
                modal.classList.remove('monthly-view');
                currentTab = 'daily';
                
                // Load daily attendance if not already loaded
                if (!currentDailyAttendanceData) {
                    loadDailyAttendance();
                }
            } else {
                dailyTab.classList.remove('active');
                monthlyTab.classList.add('active');
                dailyControls.style.display = 'none';
                monthlyControls.style.display = 'flex';
                dailyLegend.style.display = 'none';
                monthlyLegend.style.display = 'flex';
                dailyTableContainer.style.display = 'none';
                monthlyTableContainer.style.display = 'block';
                modal.classList.add('monthly-view');
                currentTab = 'monthly';
                
                // Load monthly attendance
                loadMonthlyAttendance();
            }
        }

        // Load daily attendance data
        function loadDailyAttendance() {
            const dateInput = document.getElementById('attendanceDate');
            const selectedDate = dateInput.value;
            
            if (!selectedDate) {
                alert('Please select a date');
                return;
            }
            
            const tableBody = document.querySelector('#dailyAttendanceTable tbody');
            const tableHead = document.querySelector('#dailyAttendanceTable thead tr');
            
            // Show loading state
            tableBody.innerHTML = '<tr><td colspan="100%" class="loading">Loading daily attendance data...</td></tr>';
            
            // Fetch daily attendance data
            fetch(`functions/get_subject_attendance_data.php?action=get_daily_attendance&section_id=${sectionId}&date=${selectedDate}`)
                .then(response => response.json())
                .then(data => {
                    currentDailyAttendanceData = data;
                    
                    if (!data.students || data.students.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="100%" class="no-students">No students found for this section.</td></tr>';
                        return;
                    }
                    
                    // Create subject columns header
                    const subjectColumns = data.subjects.map(subject => 
                        `<th>${subject.name}</th>`
                    ).join('');
                    
                    tableHead.innerHTML = `<th>Student Name</th>${subjectColumns}`;
                    
                    // Create student rows with attendance for each subject
                    const tableHTML = data.students.map(student => {
                        // Format name as "Last Name, First Name Middle Name"
                        let formattedName = student.name;
                        if (student.last_name && student.first_name) {
                            const middleName = student.middle_name || student.middle_initial || '';
                            const middlePart = middleName ? ` ${middleName}` : '';
                            formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                        }
                        
                        const subjectCells = data.subjects.map(subject => {
                            const attendance = student.attendance[subject.id] || { status: 'absent' };
                            const statusClass = attendance.status || 'absent';
                            return `<td class="attendance-cell ${statusClass}">${getStatusLetter(attendance.status)}</td>`;
                        }).join('');
                        
                        return `
                            <tr>
                                <td class="student-name">${formattedName}</td>
                                ${subjectCells}
                            </tr>
                        `;
                    }).join('');
                    
                    tableBody.innerHTML = tableHTML;
                })
                .catch(error => {
                    console.error('Error loading daily attendance data:', error);
                    tableBody.innerHTML = '<tr><td colspan="100%" class="error">Error loading daily attendance data. Please try again.</td></tr>';
                });
        }

        // Load monthly attendance data
        function loadMonthlyAttendance() {
            const monthYearPicker = document.getElementById('monthYearPicker');
            const subjectFilter = document.getElementById('subjectFilter');
            const selectedMonthYear = monthYearPicker.value;
            const selectedSubject = subjectFilter.value;
            
            if (!selectedMonthYear) {
                alert('Please select a month and year');
                return;
            }
            
            const [selectedYear, selectedMonth] = selectedMonthYear.split('-');
            
            const tableBody = document.querySelector('#monthlyAttendanceTable tbody');
            const tableHead = document.querySelector('#monthlyAttendanceTable thead tr');
            
            // Show loading state
            tableBody.innerHTML = '<tr><td colspan="100%" class="loading">Loading monthly attendance data...</td></tr>';
            
            // Build URL with subject filter if specific subject is selected
            let url = `functions/get_subject_attendance_data.php?action=get_monthly_attendance&section_id=${sectionId}&month=${selectedMonth}&year=${selectedYear}`;
            if (selectedSubject !== 'all') {
                url += `&subject_id=${selectedSubject}`;
            }
            
            // Fetch monthly attendance data
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    currentMonthlyAttendanceData = data;
                    
                    if (!data.students || data.students.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="100%" class="no-students">No students found for this section.</td></tr>';
                        return;
                    }
                    
                    if (!data.dates || data.dates.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="100%" class="no-students">No attendance data found for this month.</td></tr>';
                        return;
                    }
                    
                    // Create date columns header with dynamic days based on month
                    const daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();
                    const dateColumns = [];
                    
                    for (let day = 1; day <= daysInMonth; day++) {
                        const dayString = String(day).padStart(2, '0');
                        const fullDate = `${selectedYear}-${selectedMonth}-${dayString}`;
                        const dateObj = new Date(fullDate);
                        const dayOfWeek = dateObj.toLocaleDateString('en-US', { weekday: 'short' });
                        const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6; // Sunday = 0, Saturday = 6
                        const weekendClass = isWeekend ? ' weekend' : '';
                        dateColumns.push(`<th class="${weekendClass}" title="${dayOfWeek}, ${fullDate}">${day}</th>`);
                    }
                    
                    tableHead.innerHTML = `<th>Student Name</th>${dateColumns.join('')}`;
                    
                    // Create student rows with attendance for each date
                    const tableHTML = data.students.map(student => {
                        // Format name as "Last Name, First Name Middle Name"
                        let formattedName = student.name;
                        if (student.last_name && student.first_name) {
                            const middleName = student.middle_name || student.middle_initial || '';
                            const middlePart = middleName ? ` ${middleName}` : '';
                            formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                        }
                        
                        // Create cells for each day of the month
                        const dateCells = [];
                        for (let day = 1; day <= daysInMonth; day++) {
                            const dayString = String(day).padStart(2, '0');
                            const fullDate = `${selectedYear}-${selectedMonth}-${dayString}`;
                            const dateObj = new Date(fullDate);
                            const attendance = student.attendance[fullDate] || { status: 'absent' };
                            const statusClass = attendance.status || 'absent';
                            const statusDisplay = getStatusLetter(attendance.status);
                            const dayOfWeek = dateObj.toLocaleDateString('en-US', { weekday: 'short' });
                            const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6;
                            const weekendClass = isWeekend ? ' weekend' : '';
                            
                            dateCells.push(`<td class="attendance-cell ${statusClass}${weekendClass}" title="${dayOfWeek}, ${fullDate}: ${attendance.status || 'Absent'}">${statusDisplay}</td>`);
                        }
                        
                        return `
                            <tr>
                                <td class="student-name">${formattedName}</td>
                                ${dateCells.join('')}
                            </tr>
                        `;
                    }).join('');
                    
                    tableBody.innerHTML = tableHTML;
                    
                    // Force apply weekend styling after table is loaded
                    setTimeout(() => {
                        const weekendCells = document.querySelectorAll('#dailyAttendanceModal .monthly-attendance-table td.weekend');
                        weekendCells.forEach(cell => {
                            if (!cell.classList.contains('attendance-cell')) {
                                cell.style.backgroundColor = '#dbeafe';
                                cell.style.color = '#1e40af';
                                cell.style.borderColor = '#93c5fd';
                            }
                        });
                    }, 100);
                })
                .catch(error => {
                    console.error('Error loading monthly attendance data:', error);
                    tableBody.innerHTML = '<tr><td colspan="100%" class="error">Error loading monthly attendance data. Please try again.</td></tr>';
                });
        }

        // Export daily attendance data
        function exportDailyAttendance() {
            if (!currentDailyAttendanceData || !currentDailyAttendanceData.students) return;
            
            const dateInput = document.getElementById('attendanceDate');
            const selectedDate = dateInput.value;
            
            // Get section info from modal title
            const title = `<?php echo htmlspecialchars($gradeLevelDisplay); ?> - <?php echo htmlspecialchars($section); ?> - Full Subject Attendance`;
            
            // Create CSV content
            const csvContent = [
                `"Grade Level","<?php echo htmlspecialchars($gradeLevelDisplay); ?>"`,
                `"Section","<?php echo htmlspecialchars($section); ?>"`,
                `"Date","${selectedDate}"`,
                `"Export Date","${new Date().toLocaleDateString()}"`,
                '', // Empty line separator
                '"Legend:"',
                '"Present","P"',
                '"Absent","A"',
                '"Late","L"',
                '', // Empty line separator
                `"Student Name",${currentDailyAttendanceData.subjects.map(s => `"${s.name}"`).join(',')}`,
                ...currentDailyAttendanceData.students.map(student => {
                    // Format name as "Last Name, First Name Middle Name"
                    let formattedName = student.name;
                    if (student.last_name && student.first_name) {
                        const middleName = student.middle_name || student.middle_initial || '';
                        const middlePart = middleName ? ` ${middleName}` : '';
                        formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                    }
                    
                    const subjectData = currentDailyAttendanceData.subjects.map(subject => {
                        const attendance = student.attendance[subject.id] || { status: 'absent' };
                        return getStatusLetter(attendance.status);
                    }).join(',');
                    
                    return `"${formattedName}",${subjectData}`;
                })
            ].join('\n');
            
            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            
            // Get current date for filename
            const now = new Date();
            const dateStr = now.toISOString().split('T')[0];
            const filename = `${title.replace(/\s+/g, '_')}_${selectedDate}_${dateStr}.csv`;
            
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Export monthly attendance data
        function exportMonthlyAttendance() {
            if (!currentMonthlyAttendanceData || !currentMonthlyAttendanceData.students) return;
            
            const monthYearPicker = document.getElementById('monthYearPicker');
            const subjectFilter = document.getElementById('subjectFilter');
            const selectedMonthYear = monthYearPicker.value;
            const selectedSubject = subjectFilter.value;
            
            if (!selectedMonthYear) {
                alert('Please select a month and year');
                return;
            }
            
            const [selectedYear, selectedMonth] = selectedMonthYear.split('-');
            
            // Get section info from modal title
            const title = `<?php echo htmlspecialchars($gradeLevelDisplay); ?> - <?php echo htmlspecialchars($section); ?> - Full Subject Attendance`;
            
            // Get subject name for filename
            const subjectName = selectedSubject === 'all' ? 'All_Subjects' : subjectFilter.options[subjectFilter.selectedIndex].text.replace(/\s+/g, '_');
            
            // Create CSV content
            const csvContent = [
                `"Grade Level","<?php echo htmlspecialchars($gradeLevelDisplay); ?>"`,
                `"Section","<?php echo htmlspecialchars($section); ?>"`,
                `"Subject Filter","${selectedSubject === 'all' ? 'All Subjects (Combined)' : subjectFilter.options[subjectFilter.selectedIndex].text}"`,
                `"Month","${getMonthName(selectedMonth)}"`,
                `"Year","${selectedYear}"`,
                `"Export Date","${new Date().toLocaleDateString()}"`,
                '', // Empty line separator
                '"Legend:"',
                '"Present","P"',
                '"Absent","A"',
                '"Late","L"',
                '', // Empty line separator
                // Create dynamic header for all days of the month
                (() => {
                    const daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();
                    const dayHeaders = [];
                    for (let day = 1; day <= daysInMonth; day++) {
                        dayHeaders.push(`${day}`);
                    }
                    return `"Student Name",${dayHeaders.join(',')}`;
                })(),
                ...currentMonthlyAttendanceData.students.map(student => {
                    // Format name as "Last Name, First Name Middle Name"
                    let formattedName = student.name;
                    if (student.last_name && student.first_name) {
                        const middleName = student.middle_name || student.middle_initial || '';
                        const middlePart = middleName ? ` ${middleName}` : '';
                        formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                    }
                    
                    // Create data for all days of the month
                    const daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();
                    const dateData = [];
                    for (let day = 1; day <= daysInMonth; day++) {
                        const dayString = String(day).padStart(2, '0');
                        const fullDate = `${selectedYear}-${selectedMonth}-${dayString}`;
                        const attendance = student.attendance[fullDate] || { status: 'absent' };
                        dateData.push(getStatusLetter(attendance.status));
                    }
                    const dateDataString = dateData.join(',');
                    
                    return `"${formattedName}",${dateDataString}`;
                })
            ].join('\n');
            
            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            
            // Get current date for filename
            const now = new Date();
            const dateStr = now.toISOString().split('T')[0];
            const monthName = getMonthName(selectedMonth);
            const filename = `${<?php echo json_encode($gradeLevelDisplay); ?>}_<?php echo addslashes($section); ?>_${subjectName}_Monthly_Attendance_${monthName}_${selectedYear}_${dateStr}.csv`;
            
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Helper function to get month name
        function getMonthName(monthNumber) {
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            return months[parseInt(monthNumber) - 1];
        }


        // Track edits and state for subject attendance
        let currentAttendanceData = null;
        let currentFilter = 'all';
        let editedStatuses = new Set();
        let editDescriptions = {};
        let initialStatuses = {};
        let initialDescriptions = {};
        let hasSavedEdits = false;
        let currentSubjectId = null;

        // View subject attendance
        function viewSubjectAttendance(subjectId, subjectName) {
            const modal = document.getElementById('subjectAttendanceModal');
            const title = document.getElementById('subjectAttendanceTitle');
            const summaryContainer = document.getElementById('attendanceSummary');
            const tableBody = document.querySelector('#attendanceTable tbody');
            
            const gradeDisplay = `<?php echo htmlspecialchars($gradeLevelDisplay); ?>`;
            const sectionName = `<?php echo htmlspecialchars($section); ?>`;
            title.textContent = `${gradeDisplay} - ${sectionName} - ${subjectName} Attendance`;
            currentSubjectId = subjectId;
            
            // Show loading state
            summaryContainer.innerHTML = '<div class="loading">Loading attendance data...</div>';
            tableBody.innerHTML = '';
            modal.style.display = 'flex';
            
            // Fetch attendance data from database
            fetch(`functions/get_subject_attendance_data.php?action=get_attendance&subject_id=${subjectId}&section_id=<?php echo $sectionId; ?>`)
                .then(response => response.json())
                .then(data => {
                    currentAttendanceData = { students: data };
                    
                    if (currentAttendanceData.students.length === 0) {
                        summaryContainer.innerHTML = '<div class="no-data">No students registered in this section.</div>';
                        tableBody.innerHTML = '';
                        return;
                    }
                    
                    // Reset edit tracking
                    editedStatuses.clear();
                    editDescriptions = {};
                    initialStatuses = {};
                    initialDescriptions = {};
                    hasSavedEdits = false;
                    
                    const presentCount = currentAttendanceData.students.filter(s => s.status === 'present').length;
                    const lateCount = currentAttendanceData.students.filter(s => s.status === 'late').length;
                    const absentCount = currentAttendanceData.students.filter(s => s.status === 'absent').length;
                    const totalCount = currentAttendanceData.students.length;
                    
                    const summaryHTML = `
                        <div class="summary-card present clickable" onclick="filterAttendance('present')">
                            <div class="summary-icon present">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                            <div class="summary-info">
                                <div class="summary-number">${presentCount}</div>
                                <div class="summary-label">Present</div>
                            </div>
                        </div>
                        <div class="summary-card late clickable" onclick="filterAttendance('late')">
                            <div class="summary-icon late">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="summary-info">
                                <div class="summary-number">${lateCount}</div>
                                <div class="summary-label">Late</div>
                            </div>
                        </div>
                        <div class="summary-card absent clickable" onclick="filterAttendance('absent')">
                            <div class="summary-icon absent">
                                <i class="fa-solid fa-times-circle"></i>
                            </div>
                            <div class="summary-info">
                                <div class="summary-number">${absentCount}</div>
                                <div class="summary-label">Absent</div>
                            </div>
                        </div>
                        <div class="summary-card total clickable" onclick="filterAttendance('all')">
                            <div class="summary-icon total">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div class="summary-info">
                                <div class="summary-number">${totalCount}</div>
                                <div class="summary-label">Total</div>
                            </div>
                        </div>
                    `;
                    
                    const tableHTML = currentAttendanceData.students.map((student, index) => {
                        // Format name as "Last Name, First Name Middle Name"
                        let formattedName = student.name;
                        if (student.last_name && student.first_name) {
                            const middleName = student.middle_name || student.middle_initial || '';
                            const middlePart = middleName ? ` ${middleName}` : '';
                            formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                        }
                        
                        // Check if student has existing description from CSV
                        const existingDescription = student.description || '';
                        const hasExistingDescription = existingDescription.trim() !== '';
                        
                        // Capture initial values for change detection
                        initialStatuses[index] = student.status || 'absent';
                        initialDescriptions[index] = existingDescription;
                        
                        return `
                        <tr class="attendance-row" data-status="${student.status}">
                            <td>${index + 1}</td>
                            <td>${formattedName}</td>
                            <td>${student.date}</td>
                            <td>${student.time}</td>
                            <td>
                                <div class="status-editor">
                                    <select class="status-select ${student.status}" onchange="updateStatus(${index}, this.value)" data-index="${index}">
                                        <option value="present" ${student.status === 'present' ? 'selected' : ''}>Present</option>
                                        <option value="late" ${student.status === 'late' ? 'selected' : ''}>Late</option>
                                        <option value="absent" ${student.status === 'absent' ? 'selected' : ''}>Absent</option>
                                    </select>
                                    <span class="edit-indicator ${hasExistingDescription ? 'saved' : ''} ${hasExistingDescription ? '' : 'hidden'}" id="edit-indicator-${index}">
                                        <i class="fa-solid fa-${hasExistingDescription ? 'check-circle' : 'edit'}"></i>
                                        <span>${hasExistingDescription ? 'Saved' : 'Edited'}</span>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="description-editor">
                                    <input type="text" class="description-input show" id="description-${index}" 
                                           placeholder="Enter reason for edit..." value="${existingDescription}" 
                                           onchange="updateDescription(${index}, this.value)">
                                </div>
                            </td>
                        </tr>
                    `;
                    }).join('');
                    
                    summaryContainer.innerHTML = summaryHTML;
                    tableBody.innerHTML = tableHTML;
                    
                    // Ensure save button is visible from the start
                    const saveBtn = document.getElementById('saveEditsBtn');
                    if (saveBtn) {
                        saveBtn.style.display = 'flex';
                        saveBtn.disabled = true;
                    }
                    
                    updateSaveButtonState();
                })
                .catch(error => {
                    console.error('Error loading attendance data:', error);
                    summaryContainer.innerHTML = '<div class="error">Error loading attendance data. Please try again.</div>';
                });
        }

        // Close subject attendance modal
        function closeSubjectAttendanceModal() {
            const modal = document.getElementById('subjectAttendanceModal');
            modal.style.display = 'none';
        }

        // Update student status
        function updateStatus(studentIndex, newStatus) {
            if (!currentAttendanceData || !currentAttendanceData.students) return;
            
            // Store original status if not already stored
            if (!currentAttendanceData.students[studentIndex].original_status) {
                currentAttendanceData.students[studentIndex].original_status = currentAttendanceData.students[studentIndex].status;
            }
            
            // Update the data
            currentAttendanceData.students[studentIndex].status = newStatus;
            
            // Update the row's data-status attribute
            const select = document.querySelector(`select[data-index="${studentIndex}"]`);
            const row = select.closest('tr');
            if (row) {
                row.setAttribute('data-status', newStatus);
            }
            
            // Update dropdown class for color change
            select.className = `status-select ${newStatus}`;
            
            // Mark as edited
            editedStatuses.add(studentIndex);
            
            // Show edit indicator
            const editIndicator = document.getElementById(`edit-indicator-${studentIndex}`);
            if (editIndicator) {
                editIndicator.style.display = 'flex';
                editIndicator.className = 'edit-indicator edited';
                editIndicator.innerHTML = '<i class="fa-solid fa-edit"></i> <span>Edited</span>';
            }
            
            // Focus on description input
            const descriptionInput = document.getElementById(`description-${studentIndex}`);
            if (descriptionInput && !descriptionInput.readOnly) {
                descriptionInput.focus();
            }
            
            // Update summary counts
            updateSummaryCounts();
            updateSaveButtonState();
            
            // Reapply current filter after status update
            filterAttendance(currentFilter);
        }
        
        // Update description for edited attendance
        function updateDescription(studentIndex, description) {
            editDescriptions[studentIndex] = description;
            // Mark this row as edited even if status didn't change
            editedStatuses.add(studentIndex);
            updateSaveButtonState();
        }

        // Update save button state
        function updateSaveButtonState() {
            const saveBtn = document.getElementById('saveEditsBtn');
            if (!saveBtn) return;
            
            const hasEdits = editedStatuses.size > 0 || Object.keys(editDescriptions).length > 0;
            saveBtn.disabled = !hasEdits;
            // Always keep the button visible, just enable/disable it
            saveBtn.style.display = 'flex';
        }

        // Update summary counts
        function updateSummaryCounts() {
            if (!currentAttendanceData || !currentAttendanceData.students) return;
            
            const presentCount = currentAttendanceData.students.filter(s => s.status === 'present').length;
            const lateCount = currentAttendanceData.students.filter(s => s.status === 'late').length;
            const absentCount = currentAttendanceData.students.filter(s => s.status === 'absent').length;
            const totalCount = currentAttendanceData.students.length;
            
            // Update summary cards
            const summaryCards = document.querySelectorAll('.summary-card');
            if (summaryCards.length >= 4) {
                summaryCards[0].querySelector('.summary-number').textContent = presentCount;
                summaryCards[1].querySelector('.summary-number').textContent = lateCount;
                summaryCards[2].querySelector('.summary-number').textContent = absentCount;
                summaryCards[3].querySelector('.summary-number').textContent = totalCount;
            }
        }

        // Filter attendance by status
        function filterAttendance(status) {
            if (!currentAttendanceData) return;
            
            const rows = document.querySelectorAll('.attendance-row');
            const summaryCards = document.querySelectorAll('.summary-card');
            
            // Remove active class from all summary cards
            summaryCards.forEach(card => card.classList.remove('active'));
            
            // Add active class to clicked card
            let activeCard;
            switch(status) {
                case 'present':
                    activeCard = document.querySelector('.summary-card.present');
                    break;
                case 'late':
                    activeCard = document.querySelector('.summary-card.late');
                    break;
                case 'absent':
                    activeCard = document.querySelector('.summary-card.absent');
                    break;
                case 'all':
                default:
                    activeCard = document.querySelector('.summary-card.total');
                    break;
            }
            if (activeCard) activeCard.classList.add('active');
            
            // Update current filter
            currentFilter = status;
            
            // Filter table rows
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Save attendance edits
        function saveAttendanceEdits() {
            if (editedStatuses.size === 0 && Object.keys(editDescriptions).length === 0) {
                alert('No edits to save.');
                return;
            }
            
            // Get current subject and section info from modal title
            const title = document.getElementById('subjectAttendanceTitle').textContent;
            const titleParts = title.split(' - ');
            const gradeLevel = titleParts[0];
            const section = titleParts[1];
            const subject = titleParts[2].replace(' Attendance', '');
            
            // Prepare edit data
            const editsToSave = [];
            editedStatuses.forEach(studentIndex => {
                const student = currentAttendanceData.students[studentIndex];
                const description = editDescriptions[studentIndex] || '';
                
                // Format name as "Last Name, First Name Middle Name"
                let formattedName = student.name;
                if (student.last_name && student.first_name) {
                    const middleName = student.middle_name || student.middle_initial || '';
                    const middlePart = middleName ? ` ${middleName}` : '';
                    formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                }
                
                editsToSave.push({
                    student_name: formattedName,
                    student_id: student.id,
                    rfid_tag: student.rfid_tag || '',
                    old_status: student.original_status || student.status,
                    new_status: student.status,
                    description: description,
                    date: student.date,
                    time: student.time
                });
            });
            
            // Show loading state
            const saveBtn = document.getElementById('saveEditsBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Saving...</span>';
            saveBtn.disabled = true;
            
            // Send to backend
            fetch('functions/save_attendance_edits.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    subject: subject,
                    section: section,
                    grade_level: gradeLevel,
                    edits: editsToSave
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Attendance edits saved successfully!');
                    
                    // Update edit indicators to show "saved" status
                    editedStatuses.forEach(studentIndex => {
                        const editIndicator = document.getElementById(`edit-indicator-${studentIndex}`);
                        if (editIndicator) {
                            editIndicator.style.display = 'flex';
                            editIndicator.className = 'edit-indicator saved';
                            editIndicator.innerHTML = '<i class="fa-solid fa-check-circle"></i> <span>Saved</span>';
                        }
                    });
                    
                    // Clear edit tracking
                    editedStatuses.clear();
                    hasSavedEdits = true;
                    updateSaveButtonState();
                } else {
                    alert('Error saving edits: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error saving edits:', error);
                alert('Error saving edits. Please try again.');
            })
            .finally(() => {
                // Restore button state
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        }

        // Export subject attendance data
        function exportSubjectAttendance() {
            if (!currentAttendanceData || !currentAttendanceData.students) return;
            
            // Get filtered data based on current filter
            let dataToExport = currentAttendanceData.students;
            if (currentFilter !== 'all') {
                dataToExport = currentAttendanceData.students.filter(student => student.status === currentFilter);
            }
            
            // Get section and subject info from modal title
            const title = document.getElementById('subjectAttendanceTitle').textContent;
            const titleParts = title.split(' - ');
            const gradeLevel = titleParts[0];
            const section = titleParts[1];
            const subject = titleParts[2].replace(' Attendance', '');
            
            // Create CSV content with section and subject info at the top
            const csvContent = [
                `"Grade Level","${gradeLevel}"`,
                `"Section","${section}"`,
                `"Subject","${subject}"`,
                `"Export Date","${new Date().toLocaleDateString()}"`,
                `"Filter Applied","${currentFilter === 'all' ? 'All Students' : currentFilter.charAt(0).toUpperCase() + currentFilter.slice(1)}"`,
                '', // Empty line separator
                '"Legend:"',
                '"Present","P"',
                '"Absent","A"',
                '"Late","L"',
                '', // Empty line separator
                '#,Student Name,Date,Time,Status,Description',
                ...dataToExport.map((student, index) => {
                    // Format name as "Last Name, First Name Middle Name"
                    let formattedName = student.name;
                    if (student.last_name && student.first_name) {
                        const middleName = student.middle_name || student.middle_initial || '';
                        const middlePart = middleName ? ` ${middleName}` : '';
                        formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                    }
                    
                    // Get description if edited
                    const description = editDescriptions[index] || student.description || '';
                    
                    return [
                        index + 1,
                        `"${formattedName}"`,
                        student.date,
                        student.time,
                        student.status.charAt(0).toUpperCase() + student.status.slice(1),
                        `"${description}"`
                    ].join(',');
                })
            ].join('\n');
            
            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            
            // Get current date for filename
            const now = new Date();
            const dateStr = now.toISOString().split('T')[0];
            const filename = `${title.replace(/\s+/g, '_')}_${dateStr}.csv`;
            
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Prevent back navigation and clear session on page unload
        window.addEventListener('beforeunload', function(event) {
            // Clear session when user tries to navigate away
            navigator.sendBeacon('functions/clear_session.php', 'action=clear_attendance_session');
        });

        // Additional protection against back button
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Page was loaded from cache (back button), redirect to search
                window.location.replace('searchBar.php');
            }
        });

        // Prevent caching of this page
        window.addEventListener('load', function() {
            if (window.history && window.history.pushState) {
                // Replace current history entry to prevent back navigation
                window.history.replaceState(null, '', 'viewAttendance.php');
            }
            
            // Validate session on page load
            validateSession();
            
            // Load attendance statistics on page load
            loadAttendanceStatistics();
        });
        
        // Validate session to prevent unauthorized access
        function validateSession() {
            fetch('functions/validate_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=validate_attendance_session'
            })
            .then(response => response.json())
            .then(data => {
                if (!data.valid) {
                    // Session is invalid, redirect to search
                    window.location.replace('searchBar.php');
                }
            })
            .catch(error => {
                console.error('Error validating session:', error);
                // If validation fails, redirect to search for security
                window.location.replace('searchBar.php');
            });
        }

        // Global variables to store fetched data
        let topAttendanceData = [];
        let leastAttendanceData = [];

        // Load attendance statistics
        function loadAttendanceStatistics() {
            // Fetch real data from database
            fetchAttendanceStatistics('top');
            fetchAttendanceStatistics('least');
        }

        // Fetch attendance statistics from database
        function fetchAttendanceStatistics(type) {
            const containerId = type === 'top' ? 'topAttendanceContent' : 'leastAttendanceContent';
            
            fetch(`functions/get_attendance_statistics.php?type=${type}&section_id=<?php echo $sectionId; ?>`)
                .then(response => {
                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    // Get response text first to check if it's valid JSON
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Invalid JSON response:', text);
                            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Store data globally
                        if (type === 'top') {
                            topAttendanceData = data.subjects;
                        } else {
                            leastAttendanceData = data.subjects;
                        }
                        displayAttendanceData(containerId, data.subjects, type);
                    } else {
                        console.error('Server error:', data.message);
                        document.getElementById(containerId).innerHTML = '<div class="no-data">Error: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching attendance statistics:', error);
                    document.getElementById(containerId).innerHTML = '<div class="no-data">Error loading attendance data: ' + error.message + '</div>';
                });
        }

        // Display attendance data in the specified container
        function displayAttendanceData(containerId, data, type) {
            const container = document.getElementById(containerId);
            
            if (!data || data.length === 0) {
                container.innerHTML = '<div class="no-data">No attendance data available</div>';
                return;
            }

            // Limit to only 3 subjects per container
            const limitedData = data.slice(0, 3);
            
            let html = '';
            
            // Group subjects into rows (3 subjects per row)
            const subjectsPerRow = 3;
            for (let i = 0; i < limitedData.length; i += subjectsPerRow) {
                const rowSubjects = limitedData.slice(i, i + subjectsPerRow);
                html += '<div class="subjects-row">';
                
                rowSubjects.forEach(subject => {
                    html += `
                        <div class="subject-attendance">
                            <div class="subject-label ${subject.subject.toLowerCase()}">${subject.subject}</div>
                            <div class="student-list">
                                ${subject.students.map((student, index) => {
                                    const isFirst = index === 0;
                                    const studentClass = isFirst ? (type === 'top' ? 'top-student' : 'least-student') : '';
                                    return `<div class="student-item ${studentClass}">
                                        ${index + 1}.) ${student.name}
                                    </div>`;
                                }).join('')}
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
            }

            container.innerHTML = html;
        }

        // View all subjects function - now opens modals
        function viewAllSubjects(type) {
            if (type === 'top') {
                openTopStudentsModal();
            } else if (type === 'least') {
                openLeastAttendanceModal();
            }
        }

        // Open Top Students Modal
        function openTopStudentsModal() {
            const modal = document.getElementById('topStudentsModal');
            const grid = document.getElementById('topSubjectsGrid');
            
            // Show loading state
            grid.innerHTML = '<div class="no-data">Loading subjects...</div>';
            modal.style.display = 'flex';
            
            // Fetch subjects from database
            fetch(`functions/get_attendance_statistics.php?type=top&section_id=<?php echo $sectionId; ?>&modal=true`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '';
                        data.subjects.forEach(subject => {
                            const totalStudents = subject.students.length;
                            const avgAttendance = Math.round(subject.students.reduce((sum, student) => sum + student.attendance, 0) / totalStudents);
                            
                            html += `
                                <div class="subject-card-modal ${subject.subject.toLowerCase()}" onclick="showSubjectStudents('${subject.subject}', 'top')">
                                    <div class="subject-title">${subject.subject}</div>
                                    <div class="subject-stats">${totalStudents} students • Avg: ${avgAttendance}%</div>
                                    <div class="subject-click-hint">Click to view students</div>
                                </div>
                            `;
                        });
                        grid.innerHTML = html;
                    } else {
                        grid.innerHTML = '<div class="no-data">Error loading subjects</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching subjects:', error);
                    grid.innerHTML = '<div class="no-data">Error loading subjects</div>';
                });
        }

        // Open Least Attendance Modal
        function openLeastAttendanceModal() {
            const modal = document.getElementById('leastAttendanceModal');
            const grid = document.getElementById('leastSubjectsGrid');
            
            // Show loading state
            grid.innerHTML = '<div class="no-data">Loading subjects...</div>';
            modal.style.display = 'flex';
            
            // Fetch subjects from database
            fetch(`functions/get_attendance_statistics.php?type=least&section_id=<?php echo $sectionId; ?>&modal=true`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '';
                        data.subjects.forEach(subject => {
                            const totalStudents = subject.students.length;
                            const avgAttendance = Math.round(subject.students.reduce((sum, student) => sum + student.attendance, 0) / totalStudents);
                            
                            html += `
                                <div class="subject-card-modal ${subject.subject.toLowerCase()}" onclick="showSubjectStudents('${subject.subject}', 'least')">
                                    <div class="subject-title">${subject.subject}</div>
                                    <div class="subject-stats">${totalStudents} students • Avg: ${avgAttendance}%</div>
                                    <div class="subject-click-hint">Click to view students</div>
                                </div>
                            `;
                        });
                        grid.innerHTML = html;
                    } else {
                        grid.innerHTML = '<div class="no-data">Error loading subjects</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching subjects:', error);
                    grid.innerHTML = '<div class="no-data">Error loading subjects</div>';
                });
        }

        // Show students for a specific subject
        function showSubjectStudents(subjectName, type) {
            const modal = document.getElementById('subjectStudentsModal');
            const title = document.getElementById('subjectStudentsTitle');
            const studentsList = document.getElementById('studentsList');
            
            title.textContent = `${subjectName} - ${type === 'top' ? 'Top Students' : 'Least Attendance'}`;
            
            // Show loading state
            studentsList.innerHTML = '<div class="no-data">Loading students...</div>';
            modal.style.display = 'flex';
            
            // Fetch students for specific subject from database
            fetch(`functions/get_attendance_statistics.php?type=${type}&section_id=<?php echo $sectionId; ?>&subject=${encodeURIComponent(subjectName)}&students=true`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '';
                        data.students.forEach((student, index) => {
                            const studentClass = type === 'top' ? 'top-student' : 'least-student';
                            html += `
                                <div class="student-item-modal ${studentClass}">
                                    <div class="student-info">
                                        <div class="student-name">${index + 1}.) ${student.name}</div>
                                        <div class="student-attendance">Student ID: ${student.id || 'N/A'}</div>
                                    </div>
                                    <div class="attendance-percentage">${student.attendance}%</div>
                                </div>
                            `;
                        });
                        studentsList.innerHTML = html;
                    } else {
                        studentsList.innerHTML = '<div class="no-data">Error loading students</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching students:', error);
                    studentsList.innerHTML = '<div class="no-data">Error loading students</div>';
                });
        }

        // Close modal functions
        function closeTopStudentsModal() {
            document.getElementById('topStudentsModal').style.display = 'none';
        }

        function closeLeastAttendanceModal() {
            document.getElementById('leastAttendanceModal').style.display = 'none';
        }

        function closeSubjectStudentsModal() {
            document.getElementById('subjectStudentsModal').style.display = 'none';
        }
    </script>
</body>
</html>
