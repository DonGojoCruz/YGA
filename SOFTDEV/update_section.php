<?php
// Update section and subject configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = $_POST['grade'] ?? '';
    $section = $_POST['section'] ?? '';
    $subject = $_POST['subject'] ?? '';
    
    if (!empty($grade) && !empty($section)) {
        // Write to config file
        $config_content = "TARGET_GRADE=" . $grade . "\n";
        $config_content .= "TARGET_SECTION=" . $section . "\n";
        $config_content .= "TARGET_SUBJECT=" . $subject . "\n";
        $config_content .= "EXIT_LOGGING=false\n";
        
        // Log the update for debugging
        error_log("Updating RFID config: Grade $grade - Section $section - Subject $subject");
        
        if (file_put_contents('rfid_config.txt', $config_content) !== false) {
            // Verify the file was written correctly
            $written_content = file_get_contents('rfid_config.txt');
            error_log("Config file content: " . $written_content);
            echo 'success';
        } else {
            error_log("Failed to write config file");
            echo 'Failed to write config file';
        }
    } else {
        error_log("Invalid grade or section: grade='$grade', section='$section'");
        echo 'Invalid grade or section';
    }
} else {
    echo 'Invalid request method';
}
?>
