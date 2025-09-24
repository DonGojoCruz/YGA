<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Set content type to JSON
header('Content-Type: application/json');

// Build analytics data (last 7 days Present vs Absent)
require_once __DIR__ . '/../db/db_connect.php';

// Total students
$totalStudents = 0;
$res = $conn->query("SELECT COUNT(*) AS cnt FROM students");
if ($res && $row = $res->fetch_assoc()) {
  $totalStudents = (int)$row['cnt'];
}

// Get all registered RFID tags from students table
$registeredRFIDs = [];
$rfidQuery = "SELECT rfid_tag FROM students WHERE rfid_tag IS NOT NULL AND rfid_tag != ''";
$rfidResult = $conn->query($rfidQuery);
if ($rfidResult && $rfidResult->num_rows > 0) {
    while ($row = $rfidResult->fetch_assoc()) {
        $registeredRFIDs[$row['rfid_tag']] = true;
    }
}

$dates = [];
$presentCounts = [];
$absentCounts = [];
$logDir = __DIR__ . '/../logs/';

// Weekly data (last 7 days)
for ($i = 6; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-{$i} day"));
  $label = date('M j', strtotime($date));
  $file = $logDir . "rfid_log_{$date}.csv";
  $presentUIDs = [];
  if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $idx => $line) {
      if ($idx === 0) continue; // header
      $parts = str_getcsv($line);
      $uid = isset($parts[0]) ? trim($parts[0]) : '';
      // Only count if UID is registered in students table
      if ($uid !== '' && isset($registeredRFIDs[$uid])) { 
        $presentUIDs[$uid] = true; 
      }
    }
  }
  $present = count($presentUIDs);
  $absent = max(0, $totalStudents - $present);
  $dates[] = $label;
  $presentCounts[] = $present;
  $absentCounts[] = $absent;
}

// Monthly data (last 6 months)
$mLabels = [];
$mPresent = [];
$mAbsent = [];
for ($i = 5; $i >= 0; $i--) {
  $month = date('Y-m', strtotime("-{$i} month"));
  $label = date('M Y', strtotime($month . '-01'));
  
  // Get all days in this month
  $startDate = date('Y-m-01', strtotime($month . '-01'));
  $endDate = date('Y-m-t', strtotime($month . '-01'));
  
  $monthPresentUIDs = [];
  $currentDate = $startDate;
  
  while ($currentDate <= $endDate) {
    $file = $logDir . "rfid_log_{$currentDate}.csv";
    if (file_exists($file)) {
      $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      foreach ($lines as $idx => $line) {
        if ($idx === 0) continue; // header
        $parts = str_getcsv($line);
        $uid = isset($parts[0]) ? trim($parts[0]) : '';
        // Only count if UID is registered in students table
        if ($uid !== '' && isset($registeredRFIDs[$uid])) { 
          $monthPresentUIDs[$uid] = true; 
        }
      }
    }
    $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
  }
  
  $present = count($monthPresentUIDs);
  $absent = max(0, $totalStudents - $present);
  $mLabels[] = $label;
  $mPresent[] = $present;
  $mAbsent[] = $absent;
}

// Daily data (today only)
$dLabels = ['Today'];
$todayDate = date('Y-m-d');
$todayFile = $logDir . "rfid_log_{$todayDate}.csv";
$todayPresentUIDs = [];
if (file_exists($todayFile)) {
  $lines = file($todayFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $idx => $line) {
    if ($idx === 0) continue;
    $parts = str_getcsv($line);
    $uid = isset($parts[0]) ? trim($parts[0]) : '';
    // Only count if UID is registered in students table
    if ($uid !== '' && isset($registeredRFIDs[$uid])) { 
      $todayPresentUIDs[$uid] = true; 
    }
  }
}
$dPresent = [count($todayPresentUIDs)];
$dAbsent = [max(0, $totalStudents - $dPresent[0])];

// Card stats
$presentToday = $dPresent[0];
$absentToday = $dAbsent[0];

// Return data as JSON
echo json_encode([
    'totalStudents' => $totalStudents,
    'presentToday' => $presentToday,
    'absentToday' => $absentToday,
    'daily' => [
        'labels' => $dLabels,
        'present' => $dPresent,
        'absent' => $dAbsent
    ],
    'weekly' => [
        'labels' => $dates,
        'present' => $presentCounts,
        'absent' => $absentCounts
    ],
    'monthly' => [
        'labels' => $mLabels,
        'present' => $mPresent,
        'absent' => $mAbsent
    ]
]);
?>






