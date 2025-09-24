<?php
/**
 * Execute RFID start or stop scripts
 * 
 * @param string $action 'start' or 'stop'
 * @return array Response array with status and message
 */
function executeRFIDScript($action) {
    try {
        $scriptPath = '';
        $success = false;
        
        if ($action === 'stop') {
            // Stop both displayRFID.py and txtRFID.py using the existing stopRFID scripts
            $stopVbsPath = 'C:/xampp/htdocs/SOFTDEV/subjectRFID/stopRFID.vbs';
            $stopBatPath = 'C:/xampp/htdocs/SOFTDEV/subjectRFID/stopRFID.bat';
            
            $stopResults = [];
            
            if (file_exists($stopVbsPath)) {
                $command = 'cscript //nologo "' . $stopVbsPath . '"';
                $output = shell_exec($command . ' 2>&1');
                $stopResults[] = 'stopRFID.vbs executed';
                $success = true;
            } elseif (file_exists($stopBatPath)) {
                $command = 'cmd /c "' . $stopBatPath . '"';
                $output = shell_exec($command . ' 2>&1');
                $stopResults[] = 'stopRFID.bat executed';
                $success = true;
            }
            
            // Wait a moment for stop operations to complete
            if (!empty($stopResults)) {
                sleep(1);
            }
            
            if (empty($stopResults)) {
                return [
                    'status' => 'error',
                    'message' => 'Stop RFID scripts not found'
                ];
            }
            
        } elseif ($action === 'start') {
            // Start txtRFID scripts for reading RFID
            $subjectRFIDDir = 'C:/xampp/htdocs/SOFTDEV/subjectRFID';
            $txtRFIDVbsPath = $subjectRFIDDir . '/txtRFID.vbs';
            $txtRFIDBatPath = $subjectRFIDDir . '/txtRFID.bat';
            
            if (file_exists($txtRFIDVbsPath)) {
                // Use VBS for better background execution
                $command = 'cscript //nologo "' . $txtRFIDVbsPath . '"';
                
                // Use shell_exec for background execution - should work since VBS handles background mode
                $output = shell_exec($command . ' 2>&1');
                
                // Wait a moment for the process to start, then verify
                sleep(2);
                $success = true; // VBS script handles background execution internally
            } elseif (file_exists($txtRFIDBatPath)) {
                // Fallback to BAT file with proper background execution
                $cmd = 'start /b cmd /c "cd /d \"' . $subjectRFIDDir . '\" && txtRFID.bat"';
                $output = shell_exec($cmd . ' 2>&1');
                
                // Wait a moment for the process to start
                sleep(2);
                $success = true; // Background process starts independently
            } else {
                return [
                    'status' => 'error',
                    'message' => 'txtRFID scripts not found'
                ];
            }
        } elseif ($action === 'start_display') {
            // Start runRFID scripts for attendance display system
            $subjectRFIDDir = 'C:/xampp/htdocs/SOFTDEV/subjectRFID';
            $runRFIDVbsPath = $subjectRFIDDir . '/runRFID.vbs';
            $runRFIDBatPath = $subjectRFIDDir . '/runRFID.bat';
            
            if (file_exists($runRFIDVbsPath)) {
                // Use VBS for better background execution
                $command = 'cscript //nologo "' . $runRFIDVbsPath . '"';
                
                // Use shell_exec for background execution
                $output = shell_exec($command . ' 2>&1');
                
                // Wait a moment for the process to start
                sleep(2);
                $success = true; // VBS script handles background execution internally
            } elseif (file_exists($runRFIDBatPath)) {
                // Fallback to BAT file with proper background execution
                $cmd = 'start /b cmd /c "cd /d \"' . $subjectRFIDDir . '\" && runRFID.bat"';
                $output = shell_exec($cmd . ' 2>&1');
                
                // Wait a moment for the process to start
                sleep(2);
                $success = true; // Background process starts independently
            } else {
                return [
                    'status' => 'error',
                    'message' => 'runRFID scripts not found'
                ];
            }
        } else {
            return [
                'status' => 'error',
                'message' => 'Invalid action specified'
            ];
        }
        
        if ($success) {
            if ($action === 'start') {
                $message = 'txtRFID script started in background';
            } elseif ($action === 'start_display') {
                $message = 'runRFID (displayRFID) script started in background';
            } else {
                $message = 'RFID stop scripts executed';
            }
            
            if (isset($stopResults)) {
                $message .= ' (' . implode(', ', $stopResults) . ')';
            }
            return [
                'status' => 'success',
                'message' => $message,
                'action' => $action
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to execute RFID script'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if (empty($action) || !in_array($action, ['start', 'stop', 'start_display'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action specified'
        ]);
        exit;
    }
    
    $result = executeRFIDScript($action);
    echo json_encode($result);
    exit;
}
?>




