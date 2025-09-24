<?php
session_start();

// Prevent caching and going back to previous pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = "Dashboard";

// Build analytics data (last 7 days Present vs Absent)
require_once __DIR__ . '/db/db_connect.php';

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
$logDir = __DIR__ . '/logs/';
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

// Monthly (last 6 months)
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

// Daily (today only as a single bar)
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
?>
<!doctype html>
<html lang="en">
<head>
  <title><?php echo $page_title; ?></title>
  <?php include 'includes/head.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="css/dashboard-inline.css">
</head>
<body>
  <?php include 'includes/header.php'; ?>

  <div class="layout">
    <?php include 'includes/sidebar.php'; ?>

    <main>
      <h1 class="page-title"><?php echo $page_title; ?></h1>

      <section class="cards">
        <article class="card blue" aria-label="Total Students">
          <div class="stat-row">
            <div>
              <div class="stat-value" id="totalStudents"><?php echo $totalStudents; ?></div>
              <div class="label">Total Students</div>
            </div>
            <div class="stat-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
          </div>
          <div class="card-footer"></div>
        </article>

        <article class="card green" aria-label="Present Today">
          <div class="stat-row">
            <div>
              <div class="stat-value" id="presentToday"><?php echo $presentToday; ?></div>
              <div class="label">Present Today</div>
            </div>
            <div class="stat-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
          </div>
          <div class="card-footer"></div>
        </article>

        <article class="card red" aria-label="Absent Today">
          <div class="stat-row">
            <div>
              <div class="stat-value" id="absentToday"><?php echo $absentToday; ?></div>
              <div class="label">Absent Today</div>
            </div>
            <div class="stat-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
            </div>
          </div>
          <div class="card-footer"></div>
        </article>
      </section>

      <section class="analytics" aria-label="Attendance Overview" style="margin-top: 24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
          <h2 style="font-family: 'Playfair Display', Georgia, serif; font-size: 20px; margin: 0;">Attendance Overview</h2>
          <div style="display:flex;gap:8px;">
            <button type="button" class="range-btn" data-range="daily">Today</button>
            <button type="button" class="range-btn active" data-range="weekly">Weekly</button>
            <button type="button" class="range-btn" data-range="monthly">Month</button>
          </div>
        </div>
        <div class="legend"><span class="dot present"></span>Present <span class="dot absent" style="margin-left:16px"></span>Absent</div>
        <div id="chartContainer" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;height:320px;">
          <canvas id="attendanceChart" aria-label="Present vs Absent chart"></canvas>
        </div>
      </section>
    </main>
  </div>
  <?php include 'includes/scripts.php'; ?>
  <script>
    (function(){
      const datasets = {
        daily: {
          labels: <?php echo json_encode($dLabels); ?>,
          present: <?php echo json_encode($dPresent); ?>,
          absent: <?php echo json_encode($dAbsent); ?>,
          height: 220
        },
        weekly: {
          labels: <?php echo json_encode($dates); ?>,
          present: <?php echo json_encode($presentCounts); ?>,
          absent: <?php echo json_encode($absentCounts); ?>,
          height: 300
        },
        monthly: {
          labels: <?php echo json_encode($mLabels); ?>,
          present: <?php echo json_encode($mPresent); ?>,
          absent: <?php echo json_encode($mAbsent); ?>,
          height: 300
        }
      };

      const container = document.getElementById('chartContainer');
      const ctx = document.getElementById('attendanceChart').getContext('2d');
      let chart;
      let currentRange = 'weekly';

      function render(range) {
        currentRange = range;
        const data = datasets[range];
        container.style.height = data.height + 'px';
        
        // If chart exists, update its data instead of recreating
        if (chart) {
          chart.data.labels = data.labels;
          chart.data.datasets[0].data = data.present;
          chart.data.datasets[1].data = data.absent;
          chart.update('none'); // Update without animation to prevent line refreshing
          return;
        }
        
        // Create new chart only if it doesn't exist
        const config = {
        type: 'bar',
        data: {
          labels: data.labels,
          datasets: [
            {
              label: 'Present',
              data: data.present,
              backgroundColor: 'rgba(22,163,74,0.85)',
              borderColor: '#16a34a',
              borderWidth: 1,
              borderRadius: 6,
              barPercentage: 0.6,
              categoryPercentage: 0.6,
            },
            {
              label: 'Absent',
              data: data.absent,
              backgroundColor: 'rgba(220,38,38,0.85)',
              borderColor: '#dc2626',
              borderWidth: 1,
              borderRadius: 6,
              barPercentage: 0.6,
              categoryPercentage: 0.6,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: {
            duration: 0 // Disable animations to prevent line refreshing
          },
          transitions: {
            active: {
              animation: {
                duration: 0
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0, color: '#64748b' },
              grid: { color: '#e5e7eb' }
            },
            x: {
              ticks: { color: '#64748b' },
              grid: { display: false }
            }
          },
          plugins: {
            legend: { display: false },
            tooltip: { mode: 'index', intersect: false }
          }
                  }
        };
        chart = new Chart(ctx, config);
      }

      // Function to fetch fresh data from server
      async function fetchFreshData() {
        try {
          const response = await fetch('functions/get_dashboard_data.php');
          if (!response.ok) {
            throw new Error('Failed to fetch data');
          }
          
          const data = await response.json();
          
          // Update card statistics
          document.getElementById('totalStudents').textContent = data.totalStudents;
          document.getElementById('presentToday').textContent = data.presentToday;
          document.getElementById('absentToday').textContent = data.absentToday;
          
          // Update datasets
          datasets.daily.present = data.daily.present;
          datasets.daily.absent = data.daily.absent;
          datasets.weekly.present = data.weekly.present;
          datasets.weekly.absent = data.weekly.absent;
          datasets.monthly.present = data.monthly.present;
          datasets.monthly.absent = data.monthly.absent;
          
          // Update chart data smoothly without recreating
          if (chart) {
            chart.data.labels = datasets[currentRange].labels;
            chart.data.datasets[0].data = datasets[currentRange].present;
            chart.data.datasets[1].data = datasets[currentRange].absent;
            chart.update('none'); // Update without animation
          }
          
        } catch (error) {
          console.error('Error fetching fresh data:', error);
        }
      }



      // initialize with weekly
      render('weekly');

      // toggle buttons
      document.querySelectorAll('.range-btn').forEach(btn => {
        btn.addEventListener('click', function(){
          document.querySelectorAll('.range-btn').forEach(b => {
            b.classList.remove('active');
            b.style.background = '#fff';
            b.style.color = '';
          });
          this.classList.add('active');
          this.style.background = '#17a2b8';
          this.style.color = '#fff';
          render(this.dataset.range);
        });
      });

      // Auto-refresh functionality
      function refreshDashboardData() {
        fetchFreshData();
      }

      // Auto-refresh every 5 seconds (5000ms) - more reasonable interval
      setInterval(refreshDashboardData, 5000);
      
      // Initial fetch after page load
      setTimeout(refreshDashboardData, 1000);
    })();
  </script>
</body>
</html> 