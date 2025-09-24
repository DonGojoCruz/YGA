<?php
header('Content-Type: application/json');

// Get the project root directory
$projectRoot = dirname(__DIR__);
$vbsScript = $projectRoot . '/stopRFID.vbs';
$batScript = $projectRoot . '/stopRFID.bat';

// Check if VBS script exists
if (!file_exists($vbsScript)) {
    echo json_encode(['success' => false, 'message' => 'stopRFID.vbs not found']);
    exit;
}

try {
    // Execute the VBS script (which will run the Python script)
    $command = 'cscript //nologo "' . $vbsScript . '"';
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'All RFID processes stopped successfully'
        ]);
    } else {
        // If VBS fails, try the BAT script as fallback
        if (file_exists($batScript)) {
            $batCommand = '"' . $batScript . '"';
            exec($batCommand, $batOutput, $batReturnCode);
            
            if ($batReturnCode === 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'All RFID processes stopped successfully (via BAT script)'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to stop RFID processes. VBS return code: ' . $returnCode . ', BAT return code: ' . $batReturnCode
                ]);
            }
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to stop RFID processes. VBS return code: ' . $returnCode . ', BAT script not found'
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error stopping RFID processes: ' . $e->getMessage()
    ]);
}
?>




