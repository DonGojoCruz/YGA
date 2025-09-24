<?php
header('Content-Type: application/json');

try {
    // First, check for manage.txt (for student management)
    $manageFile = '../manage.txt';
    $rfidFile = null;
    $fileType = '';
    
    if (file_exists($manageFile)) {
        $rfidFile = $manageFile;
        $fileType = 'manage';
    } else {
        // Fallback to subject-specific files
        $configFile = '../rfid_config.txt';
        $currentSubject = 'english'; // default
        
        if (file_exists($configFile)) {
            $configLines = file($configFile, FILE_IGNORE_NEW_LINES);
            foreach ($configLines as $line) {
                if (strpos($line, 'TARGET_SUBJECT=') === 0) {
                    $subjectFromConfig = trim(substr($line, 15));
                    if (!empty($subjectFromConfig)) {
                        $currentSubject = $subjectFromConfig;
                    }
                    break;
                }
            }
        }
        
        // Generate subject-specific RFID file path (same logic as Python script)
        $subjectKey = strtolower(str_replace([' ', '-'], '_', $currentSubject));
        $rfidFile = "../{$subjectKey}rfid.txt";
        $fileType = 'subject';
    }
    
    $debug = [
        'rfid_file' => $rfidFile,
        'file_type' => $fileType,
        'file_exists' => file_exists($rfidFile),
        'file_size' => file_exists($rfidFile) ? filesize($rfidFile) : 0
    ];
    
    if (!file_exists($rfidFile)) {
        echo json_encode([
            'error' => true, 
            'message' => 'RFID data file not found',
            'debug' => $debug
        ]);
        exit;
    }
    
    $rfidData = trim(file_get_contents($rfidFile));
    
    if (empty($rfidData)) {
        echo json_encode([
            'error' => true, 
            'message' => 'No RFID data available',
            'debug' => $debug
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true, 
        'rfid' => $rfidData,
        'debug' => $debug
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => 'Error reading RFID data: ' . $e->getMessage()]);
}
?>









