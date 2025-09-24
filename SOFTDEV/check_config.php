<?php
// Check current RFID configuration
$config_file = "rfid_config.txt";
$grade = 'Not set';
$section = 'Not set';

if (file_exists($config_file)) {
    $content = file_get_contents($config_file);
    $lines = explode("\n", $content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'TARGET_GRADE=') === 0) {
            $grade = str_replace('TARGET_GRADE=', '', $line);
        }
        if (strpos($line, 'TARGET_SECTION=') === 0) {
            $section = str_replace('TARGET_SECTION=', '', $line);
        }
    }
}

echo "Current RFID Configuration:\n";
echo "Grade: $grade\n";
echo "Section: $section\n";
echo "Config file exists: " . (file_exists($config_file) ? 'Yes' : 'No') . "\n";
echo "Config file content:\n";
echo file_exists($config_file) ? file_get_contents($config_file) : 'File not found';
?>


