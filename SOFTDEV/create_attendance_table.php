<?php
require_once 'db/db_connect.php';

echo "Creating attendance_logs table...\n";

$sql = "
CREATE TABLE IF NOT EXISTS `attendance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rfid_uid` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `scan_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rfid_time` (`rfid_uid`,`scan_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ attendance_logs table created successfully\n";
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

// Also check what tables exist
echo "\nExisting tables in samaria database:\n";
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_row()) {
        echo "  - " . $row[0] . "\n";
    }
}

$conn->close();
?>
