<?php
echo "<h2>File Monitoring</h2>";

$rfid_file = __DIR__ . "/englishrfid.txt";
$exit_file = __DIR__ . "/exitrfid.txt";

echo "<h3>Current Status:</h3>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

echo "<h3>englishrfid.txt:</h3>";
if (file_exists($rfid_file)) {
    echo "<p>✅ File exists</p>";
    echo "<p>Size: " . filesize($rfid_file) . " bytes</p>";
    echo "<p>Last modified: " . date('Y-m-d H:i:s', filemtime($rfid_file)) . "</p>";
    $content = file_get_contents($rfid_file);
    echo "<p>Content: '$content'</p>";
} else {
    echo "<p>❌ File does not exist</p>";
}

echo "<h3>exitrfid.txt:</h3>";
if (file_exists($exit_file)) {
    echo "<p>✅ File exists</p>";
    echo "<p>Size: " . filesize($exit_file) . " bytes</p>";
    echo "<p>Last modified: " . date('Y-m-d H:i:s', filemtime($exit_file)) . "</p>";
    $content = file_get_contents($exit_file);
    echo "<p>Content: '$content'</p>";
} else {
    echo "<p>❌ File does not exist</p>";
}

echo "<h3>All RFID files in directory:</h3>";
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

echo "<h3>Config File:</h3>";
$config_file = "rfid_config.txt";
if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    echo "<pre>" . htmlspecialchars($config_content) . "</pre>";
} else {
    echo "<p>Config file not found</p>";
}

// Auto-refresh every 2 seconds
echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
?>





