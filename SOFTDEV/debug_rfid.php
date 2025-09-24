<?php
echo "<h1>RFID Debug Center</h1>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

echo "<h2>1. Config File Check</h2>";
$config_file = "rfid_config.txt";
if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    echo "<pre>" . htmlspecialchars($config_content) . "</pre>";
    
    // Parse config
    $lines = explode("\n", $config_content);
    $TARGET_GRADE = '1';
    $TARGET_SECTION = 'Mango';
    $TARGET_SUBJECT = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'TARGET_GRADE=') === 0) {
            $TARGET_GRADE = trim(str_replace('TARGET_GRADE=', '', $line));
        }
        if (strpos($line, 'TARGET_SECTION=') === 0) {
            $TARGET_SECTION = trim(str_replace('TARGET_SECTION=', '', $line));
        }
        if (strpos($line, 'TARGET_SUBJECT=') === 0) {
            $TARGET_SUBJECT = trim(str_replace('TARGET_SUBJECT=', '', $line));
        }
    }
    
    echo "<p>Parsed - Grade: '$TARGET_GRADE', Section: '$TARGET_SECTION', Subject: '$TARGET_SUBJECT'</p>";
} else {
    echo "<p>❌ Config file not found</p>";
}

echo "<h2>2. File Path Calculation</h2>";
$subject_key = strtolower(str_replace([' ', '-'], '_', $TARGET_SUBJECT));
if (empty($subject_key)) {
    $subject_key = 'english';
}
$rfid_file = __DIR__ . "/" . $subject_key . "rfid.txt";
echo "<p>Subject key: '$subject_key'</p>";
echo "<p>RFID file path: $rfid_file</p>";

echo "<h2>3. File Status</h2>";
if (file_exists($rfid_file)) {
    echo "<p>✅ File exists</p>";
    echo "<p>Size: " . filesize($rfid_file) . " bytes</p>";
    echo "<p>Last modified: " . date('Y-m-d H:i:s', filemtime($rfid_file)) . "</p>";
    $content = file_get_contents($rfid_file);
    echo "<p>Content: '$content'</p>";
    
    if (!empty($content)) {
        $parts = explode('|', $content);
        echo "<p>Parts: " . count($parts) . "</p>";
        if (count($parts) >= 3) {
            echo "<p>UID: '" . trim($parts[0]) . "'</p>";
            echo "<p>Time: '" . trim($parts[1]) . "'</p>";
            echo "<p>Subject: '" . trim($parts[2]) . "'</p>";
        }
    }
} else {
    echo "<p>❌ File does not exist</p>";
}

echo "<h2>4. Python Test File</h2>";
$test_file = __DIR__ . "/python_test.txt";
if (file_exists($test_file)) {
    echo "<p>✅ Python test file exists</p>";
    $content = file_get_contents($test_file);
    echo "<p>Content: '$content'</p>";
} else {
    echo "<p>❌ Python test file not found - Python script may not be running</p>";
}

echo "<h2>5. All RFID Files</h2>";
$files = scandir(__DIR__);
$rfid_files = array_filter($files, function($file) {
    return strpos($file, 'rfid.txt') !== false;
});

if (empty($rfid_files)) {
    echo "<p>No RFID files found</p>";
} else {
    echo "<ul>";
    foreach ($rfid_files as $file) {
        $file_path = __DIR__ . "/" . $file;
        echo "<li>$file - Size: " . filesize($file_path) . " bytes - Modified: " . date('Y-m-d H:i:s', filemtime($file_path)) . "</li>";
    }
    echo "</ul>";
}

echo "<h2>6. Test get_rfid_data.php</h2>";
$url = "http://localhost/SOFTDEV/get_rfid_data.php";
$response = file_get_contents($url);

if ($response) {
    $data = json_decode($response, true);
    echo "<h3>Response:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";
} else {
    echo "<p>❌ Failed to get response from get_rfid_data.php</p>";
}

echo "<h2>7. Manual File Creation Test</h2>";
$test_uid = "TEST123456";
$test_time = date('Y-m-d H:i:s');
$test_subject = "English";
$test_content = "$test_uid|$test_time|$test_subject";

echo "<p>Creating test file with content: '$test_content'</p>";
file_put_contents($rfid_file, $test_content);

echo "<p>File created. Now testing get_rfid_data.php again...</p>";

$response2 = file_get_contents($url);
if ($response2) {
    $data2 = json_decode($response2, true);
    echo "<h3>Response after manual file creation:</h3>";
    echo "<pre>" . print_r($data2, true) . "</pre>";
} else {
    echo "<p>❌ Failed to get response after manual file creation</p>";
}

// Clean up
unlink($rfid_file);
echo "<p>Test file cleaned up</p>";

// Auto-refresh every 3 seconds
echo "<script>setTimeout(function(){ window.location.reload(); }, 3000);</script>";
?>





