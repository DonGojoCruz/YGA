<?php
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exit Logging Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Exit Logging Test</h1>
    
    <div class="test-section">
        <h3>1. Test Config File</h3>
        <button onclick="testConfig()">Check Config File</button>
        <div id="configResult"></div>
    </div>
    
    <div class="test-section">
        <h3>2. Test Start Exit Logging</h3>
        <button onclick="testStartExit()">Start Exit Logging</button>
        <div id="startResult"></div>
    </div>
    
    <div class="test-section">
        <h3>3. Test Stop Exit Logging</h3>
        <button onclick="testStopExit()">Stop Exit Logging</button>
        <div id="stopResult"></div>
    </div>
    
    <div class="test-section">
        <h3>4. Test File Reading</h3>
        <button onclick="testFileReading()">Test File Reading</button>
        <div id="fileResult"></div>
    </div>
    
    <div class="test-section">
        <h3>5. Create Test Exit File</h3>
        <button onclick="createTestFile()">Create Test Exit File</button>
        <div id="testFileResult"></div>
    </div>

    <script>
        function testConfig() {
            fetch('get_rfid_data.php')
                .then(response => response.json())
                .then(data => {
                    const result = document.getElementById('configResult');
                    result.innerHTML = `
                        <div class="info">Config Status:</div>
                        <div>Exit Logging: ${data.debug.exit_logging}</div>
                        <div>File Type: ${data.debug.file_type}</div>
                        <div>Target Grade: ${data.target_grade}</div>
                        <div>Target Section: ${data.target_section}</div>
                        <div>RFID File: ${data.debug.rfid_file}</div>
                        <div>File Exists: ${data.debug.file_exists}</div>
                    `;
                })
                .catch(error => {
                    document.getElementById('configResult').innerHTML = `<div class="error">Error: ${error.message}</div>`;
                });
        }
        
        function testStartExit() {
            fetch('functions/start_exit_logging.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=start'
            })
            .then(response => response.json())
            .then(data => {
                const result = document.getElementById('startResult');
                if (data.success) {
                    result.innerHTML = `<div class="success">✅ ${data.message}</div>`;
                } else {
                    result.innerHTML = `<div class="error">❌ ${data.message}</div>`;
                }
            })
            .catch(error => {
                document.getElementById('startResult').innerHTML = `<div class="error">Error: ${error.message}</div>`;
            });
        }
        
        function testStopExit() {
            fetch('functions/stop_exit_logging.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=stop'
            })
            .then(response => response.json())
            .then(data => {
                const result = document.getElementById('stopResult');
                if (data.success) {
                    result.innerHTML = `<div class="success">✅ ${data.message}</div>`;
                } else {
                    result.innerHTML = `<div class="error">❌ ${data.message}</div>`;
                }
            })
            .catch(error => {
                document.getElementById('stopResult').innerHTML = `<div class="error">Error: ${error.message}</div>`;
            });
        }
        
        function testFileReading() {
            fetch('get_rfid_data.php')
                .then(response => response.json())
                .then(data => {
                    const result = document.getElementById('fileResult');
                    result.innerHTML = `
                        <div class="info">File Reading Test:</div>
                        <div>Has Scan: ${data.has_scan}</div>
                        <div>Has Student: ${data.has_student}</div>
                        <div>Subject: ${data.subject}</div>
                        <div>Scan Time: ${data.scan_time}</div>
                        <div>Student Data: ${data.student_data ? JSON.stringify(data.student_data) : 'None'}</div>
                    `;
                })
                .catch(error => {
                    document.getElementById('fileResult').innerHTML = `<div class="error">Error: ${error.message}</div>`;
                });
        }
        
        function createTestFile() {
            // Create a test exit display file
            const testContent = "TEST123|2024-01-15 14:30:00|John Doe|2|Guava|EXIT";
            
            fetch('create_test_exit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'content=' + encodeURIComponent(testContent)
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('testFileResult').innerHTML = `<div class="info">${data}</div>`;
            })
            .catch(error => {
                document.getElementById('testFileResult').innerHTML = `<div class="error">Error: ${error.message}</div>`;
            });
        }
    </script>
</body>
</html>





