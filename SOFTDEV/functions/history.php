<?php
session_start();

// Prevent caching and going back to previous pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../db/db_connect.php';

$logsDir = '../logs';
$exits = [];

/**
 * Simple cache so we only query each UID once.
 */
$studentCache = [];
function fetchStudentByUid(mysqli $conn, string $uid, array &$cache): array {
  if (isset($cache[$uid])) return $cache[$uid];

  $sql = "SELECT 
              s.first_name,
              s.last_name,
              s.grade_level,
              COALESCE(sec.section, '-') AS section_name
          FROM students s
          LEFT JOIN section sec ON sec.id = s.section_id
          WHERE s.rfid_tag = ?
          LIMIT 1";
  $data = [
      'name'    => 'Unknown',
      'grade'   => '-',
      'section' => '-',
  ];

  if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param('s', $uid);
      if ($stmt->execute()) {
          $res = $stmt->get_result();
          if ($row = $res->fetch_assoc()) {
              $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
              $data['name'] = $fullName !== '' ? $fullName : 'Unknown';

              $gradeRaw = $row['grade_level'] ?? '';
              if (is_numeric($gradeRaw)) {
                  $data['grade'] = 'Grade ' . $gradeRaw;
              } else if (!empty($gradeRaw)) {
                  $data['grade'] = $gradeRaw;
              } else {
                  $data['grade'] = '-';
              }

              $data['section'] = $row['section_name'] ?? '-';
          }
      }
      $stmt->close();
  }

  return $cache[$uid] = $data;
}

// Define logs directory
$logsDir = '../logs';

// Only process today's exit logs
$today = date('Y-m-d');
$todayFile = $logsDir . "/rfid_exit_log_{$today}.csv";
$latestDate = $today;

if (file_exists($todayFile)) {
    $lines = file($todayFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines) {
        foreach ($lines as $line) {
            $parts = array_map('trim', str_getcsv($line));
            if (count($parts) < 3) continue;

            list($uidRaw, $date, $time) = $parts;
            $uid = trim($uidRaw);

            // Fetch student (cached)
            $info = fetchStudentByUid($conn, $uid, $studentCache);

            // Skip unknown UIDs
            if ($info['name'] === 'Unknown') continue;

            $exits[] = [
                'name'    => $info['name'],
                'grade'   => $info['grade'],
                'section' => $info['section'],
                'date'    => $date,
                'time'    => $time
            ];
        }
    }
}

// Sort by time (newest first) within the latest date
usort($exits, function($a, $b) {
    return strtotime($b['time']) <=> strtotime($a['time']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Today's Exit History - <?php echo date('F j, Y'); ?></title>
  <style>
    body {
      font-family: "Segoe UI", Arial, sans-serif;
      margin: 0; padding: 0;
      background: #f5f6fa; color: #2c3e50;
    }
    .top-bar {
      position: sticky; top: 0; z-index: 100;
      display: flex; align-items: center; gap: 14px;
      padding: 16px 20px; background: #fff;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .btn {
      display: inline-flex; align-items: center; gap: 8px;
      text-decoration: none; cursor: pointer;
      padding: 8px 14px; border-radius: 10px; border: 1px solid #f3a8b7;
      background: #f4b6c2; color: #7a2e3a; font-weight: 700;
      transition: transform .04s ease, box-shadow .2s ease, background .2s;
    }
    .btn:active { transform: translateY(1px); }
    .btn:hover { background: #f3a8b7; }
    .title { font-size: 20px; font-weight: 700; margin: 0; }
    .date-display { 
      font-size: 16px; 
      color: #666; 
      margin-top: 4px; 
      font-weight: 500;
    }

    .container { padding: 20px; max-width: 1100px; margin: auto; }

    .search-row {
      display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
      margin: 16px 0 20px;
    }
    .search-input {
      flex: 1 1 320px; max-width: 480px;
      padding: 12px 14px; border-radius: 10px;
      border: 1px solid #dcdde1; font-size: 15px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .search-input:focus {
      outline: none; border-color: #f4b6c2;
      box-shadow: 0 0 0 3px rgba(244,182,194,0.35);
    }

    .card {
      background: #fff; border-radius: 14px; overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #ececec;
    }
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: 14px 16px; text-align: left; font-size: 15px; }
    th {
      background: #f4b6c2; color: #7a2e3a;
      text-transform: uppercase; letter-spacing: .04em; font-size: 13px;
    }
    tr:nth-child(even) td { background: #fafafa; }
    tr:hover td { background: #fdf1f4; transition: background .2s; }
    .muted { color: #7f8c8d; }
  </style>
</head>
<body>
  <div class="top-bar">
    <a class="btn" href="../showRFID.php">⬅ Back</a>
    <div>
      <h1 class="title">Today's Exit History</h1>
      <div class="date-display">Showing data for: <?php echo date('F j, Y'); ?></div>
    </div>
  </div>

  <div class="container">
    <div class="search-row">
      <input id="search" class="search-input" type="text" placeholder="Search by name, grade, section, or date..." autocomplete="off">
    </div>

    <div class="card">
      <table id="historyTable" aria-label="Exit history">
        <thead>
          <tr>
            <th>Name</th>
            <th>Grade</th>
            <th>Section</th>
            <th>Date</th>
            <th>Time</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($exits)): ?>
            <tr><td colspan="5" class="muted" style="text-align:center; padding:20px;">
              No exits recorded for today (<?php echo date('F j, Y'); ?>).
            </td></tr>
          <?php else: ?>
            <?php foreach ($exits as $exit): ?>
              <tr>
                <td><?= htmlspecialchars($exit['name']) ?></td>
                <td><?= htmlspecialchars($exit['grade']) ?></td>
                <td><?= htmlspecialchars($exit['section']) ?></td>
                <td><?= htmlspecialchars($exit['date']) ?></td>
                <td><?= htmlspecialchars($exit['time']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    // Client-side search
    const searchInput = document.getElementById('search');
    const rows = document.querySelectorAll('#historyTable tbody tr');

    function filterRows() {
      const q = searchInput.value.toLowerCase();
      rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    }
    searchInput.addEventListener('input', filterRows);
  </script>
</body>
</html>
