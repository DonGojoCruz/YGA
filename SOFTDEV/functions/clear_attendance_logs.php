<?php
session_start();

// Prevent caching and going back to previous pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');

require_once __DIR__ . '/../db/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'clear_date':
            $date = $input['date'] ?? '';
            if (empty($date)) {
                throw new Exception('Date is required');
            }
            clearLogsForDate($conn, $date);
            break;
            
        case 'clear_before_date':
            $date = $input['date'] ?? '';
            if (empty($date)) {
                throw new Exception('Date is required');
            }
            clearLogsBeforeDate($conn, $date);
            break;
            
        case 'clear_date_range':
            $startDate = $input['start_date'] ?? '';
            $endDate = $input['end_date'] ?? '';
            if (empty($startDate) || empty($endDate)) {
                throw new Exception('Start date and end date are required');
            }
            clearLogsForDateRange($conn, $startDate, $endDate);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    echo json_encode(['success' => true, 'message' => 'Logs cleared successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function clearLogsForDate($conn, $date) {
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format');
    }
    
    // Clear logs from the logs directory
    $logFile = __DIR__ . "/../logs/rfid_log_{$date}.csv";
    if (file_exists($logFile)) {
        if (!unlink($logFile)) {
            throw new Exception('Failed to delete log file');
        }
    }
    
    // Also clear the exit log file for the same date
    $exitLogFile = __DIR__ . "/../logs/rfid_exit_log_{$date}.csv";
    if (file_exists($exitLogFile)) {
        if (!unlink($exitLogFile)) {
            throw new Exception('Failed to delete exit log file');
        }
    }
    
    // Log what we're trying to delete for debugging
    error_log("Attempting to clear logs for date: {$date}");
    error_log("Log file path: {$logFile}");
    error_log("Exit log file path: {$exitLogFile}");
    error_log("Log file exists: " . (file_exists($logFile) ? 'Yes' : 'No'));
    error_log("Exit log file exists: " . (file_exists($exitLogFile) ? 'Yes' : 'No'));
    
    // Clear logs from the database (if you have a logs table)
    // This assumes you have a table called 'attendance_logs' or similar
    $sql = "DELETE FROM attendance_logs WHERE DATE(timestamp) = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $stmt->close();
    }
    
    // Also clear from entrance_logs and exit_logs if they exist
    $entranceSql = "DELETE FROM entrance_logs WHERE DATE(timestamp) = ?";
    $stmt = $conn->prepare($entranceSql);
    if ($stmt) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $stmt->close();
    }
    
    $exitSql = "DELETE FROM exit_logs WHERE DATE(timestamp) = ?";
    $stmt = $conn->prepare($exitSql);
    if ($stmt) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $stmt->close();
    }
}

function clearLogsBeforeDate($conn, $date) {
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format');
    }
    
    // Clear log files from the logs directory
    $logDir = __DIR__ . "/../logs/";
    
    // Clear regular RFID log files
    $files = glob($logDir . "rfid_log_*.csv");
    foreach ($files as $file) {
        $filename = basename($file);
        $fileDate = str_replace("rfid_log_", "", str_replace(".csv", "", $filename));
        
        if ($fileDate < $date) {
            if (!unlink($file)) {
                throw new Exception('Failed to delete log file: ' . $filename);
            }
        }
    }
    
    // Clear exit log files
    $exitFiles = glob($logDir . "rfid_exit_log_*.csv");
    foreach ($exitFiles as $file) {
        $filename = basename($file);
        $fileDate = str_replace("rfid_exit_log_", "", str_replace(".csv", "", $filename));
        
        if ($fileDate < $date) {
            if (!unlink($file)) {
                throw new Exception('Failed to delete exit log file: ' . $filename);
            }
        }
    }
    
    // Clear logs from the database before the specified date
    $sql = "DELETE FROM attendance_logs WHERE DATE(timestamp) < ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $stmt->close();
    }
    
    // Also clear from entrance_logs and exit_logs if they exist
    $entranceSql = "DELETE FROM entrance_logs WHERE DATE(timestamp) < ?";
    $stmt = $conn->prepare($entranceSql);
    if ($stmt) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $stmt->close();
    }
    
    $exitSql = "DELETE FROM exit_logs WHERE DATE(timestamp) < ?";
    $stmt = $conn->prepare($exitSql);
    if ($stmt) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $stmt->close();
    }
}

function clearLogsForDateRange($conn, $startDate, $endDate) {
    // Validate date formats
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        throw new Exception('Invalid date format');
    }
    
    if ($startDate > $endDate) {
        throw new Exception('Start date must be before or equal to end date');
    }
    
    // Clear log files from the logs directory within the date range
    $logDir = __DIR__ . "/../logs/";
    
    // Clear regular RFID log files
    $files = glob($logDir . "rfid_log_*.csv");
    foreach ($files as $file) {
        $filename = basename($file);
        $fileDate = str_replace("rfid_log_", "", str_replace(".csv", "", $filename));
        
        if ($fileDate >= $startDate && $fileDate <= $endDate) {
            if (!unlink($file)) {
                throw new Exception('Failed to delete log file: ' . $filename);
            }
        }
    }
    
    // Clear exit log files
    $exitFiles = glob($logDir . "rfid_exit_log_*.csv");
    foreach ($exitFiles as $file) {
        $filename = basename($file);
        $fileDate = str_replace("rfid_exit_log_", "", str_replace(".csv", "", $filename));
        
        if ($fileDate >= $startDate && $fileDate <= $endDate) {
            if (!unlink($file)) {
                throw new Exception('Failed to delete exit log file: ' . $filename);
            }
        }
    }
    
    // Clear logs from the database within the date range
    $sql = "DELETE FROM attendance_logs WHERE DATE(timestamp) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $stmt->close();
    }
    
    // Also clear from entrance_logs and exit_logs if they exist
    $entranceSql = "DELETE FROM entrance_logs WHERE DATE(timestamp) BETWEEN ? AND ?";
    $stmt = $conn->prepare($entranceSql);
    if ($stmt) {
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $stmt->close();
    }
    
    $exitSql = "DELETE FROM exit_logs WHERE DATE(timestamp) BETWEEN ? AND ?";
    $stmt = $conn->prepare($exitSql);
    if ($stmt) {
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $stmt->close();
    }
}
?>
