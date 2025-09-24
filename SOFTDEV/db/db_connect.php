<?php
/**
 * Database Connection File
 * Connects to the samaria database using XAMPP default settings
 */

// Database configuration
$host = "localhost";   // database host
$user = "root";        // default XAMPP username
$pass = "";            // default XAMPP password (empty unless you set one)
$dbname = "samaria";   // database name

// Create connection with timeout and error handling
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed. Please check your database configuration.");
}

// Set connection timeout and other settings
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10); // 10 second timeout
$conn->options(MYSQLI_OPT_READ_TIMEOUT, 30);    // 30 second read timeout

// Set charset to utf8 (optional but recommended)
$conn->set_charset("utf8");

// Optional: Set timezone if needed
// date_default_timezone_set('Asia/Manila'); // Uncomment and adjust as needed
?>
