<?php
header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = $_POST['content'];
    $file_path = __DIR__ . "/exit_display.txt";
    
    if (file_put_contents($file_path, $content) !== false) {
        echo "✅ Test exit file created successfully at: " . $file_path;
        echo "\nContent: " . $content;
    } else {
        echo "❌ Failed to create test exit file";
    }
} else {
    echo "❌ Invalid request";
}
?>





