<?php
session_start();
require_once __DIR__ . '/db/db_connect.php';

// Prevent caching and back button issues
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user has selected a section
if (!isset($_SESSION['selected_section_id']) || !isset($_SESSION['selected_grade_level']) || !isset($_SESSION['selected_section'])) {
    header('Location: searchBar.php');
    exit;
}

$gradeLevel = $_SESSION['selected_grade_level'];
$section = $_SESSION['selected_section'];
$sectionId = $_SESSION['selected_section_id'];

// Format grade level display
$gradeLevelDisplay = is_numeric($gradeLevel) ? "Grade " . $gradeLevel : $gradeLevel;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Young Gen. Academy - Section Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
            /* Hide scrollbar for webkit browsers */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }

        /* Hide scrollbar for webkit browsers */
        body::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for all elements */
        * {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }

        *::-webkit-scrollbar {
            display: none;
        }

        /* Subtle pattern background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.3) 2px, transparent 2px),
                radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.2) 1px, transparent 1px);
            background-size: 60px 60px, 40px 40px;
            z-index: -1;
        }

        .main-layout {
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 1200px;
            margin: 0 auto;
            min-height: calc(100vh - 40px);
            gap: 40px;
        }

        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 450px;
            width: 100%;
            text-align: center;
            position: relative;
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #2d3748;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-header p {
            color: #666;
            font-size: 1rem;
            margin: 0;
        }

        .section-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
            text-align: left;
        }

        .grade-level {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .section-name {
            font-size: 1rem;
            color: #666;
            font-weight: 500;
        }

        .buttons-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-btn {
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            padding: 20px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 16px;
            text-align: left;
            position: relative;
            overflow: hidden;
        }

        .dashboard-btn:hover {
            border-color: #0ea5b7;
            box-shadow: 0 4px 12px rgba(14, 165, 183, 0.15);
            transform: translateY(-2px);
        }

        .dashboard-btn:active {
            transform: translateY(0);
        }

        .dashboard-btn.attendance {
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #e5e7eb;
        }

        .dashboard-btn.attendance:hover {
            border-color: #0ea5b7;
            box-shadow: 0 4px 12px rgba(14, 165, 183, 0.15);
        }

        .btn-icon {
            width: 48px;
            height: 48px;
            background: #f0f9ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .btn-icon svg {
            width: 24px;
            height: 24px;
            stroke: #0ea5b7;
            fill: none;
            stroke-width: 2;
        }

        .btn-content {
            flex: 1;
        }

        .btn-title {
            font-weight: 600;
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .btn-desc {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.3;
        }

        .exit-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
            font-size: 0.95rem;
        }

        .exit-btn:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        .veritap-brand {
            position: absolute;
            bottom: 15px;
            right: 20px;
            color: #4a9eff;
            font-size: 0.9rem;
            font-weight: 600;
            font-style: italic;
        }

        /* Exit Confirmation Modal */
        .exit-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }

        .exit-modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            padding: 40px 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: modalFadeIn 0.3s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .exit-modal h3 {
            color: #2d3748;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .exit-modal p {
            color: #666;
            font-size: 1rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .exit-modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .exit-modal-btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            min-width: 90px;
        }

        .exit-modal-yes {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .exit-modal-yes:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        .exit-modal-no {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .exit-modal-no:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }

        /* Mobile tablet styles */
        @media (max-width: 768px) {
            .main-layout {
                padding: 20px;
                min-height: auto;
            }
            
            .form-header h2 {
                font-size: 1.6rem;
                line-height: 1.3;
            }
            
            .dashboard-container {
                padding: 30px 25px;
                margin: 0;
                border-radius: 15px;
                max-width: 100%;
            }
            
            .section-info {
                padding: 20px;
            }
            
            .buttons-container {
                gap: 16px;
            }
            
            .dashboard-btn {
                padding: 18px;
                gap: 14px;
            }
            
            .btn-icon {
                width: 44px;
                height: 44px;
            }
            
            .btn-icon svg {
                width: 22px;
                height: 22px;
            }
            
            .btn-title {
                font-size: 15px;
            }
            
            .btn-desc {
                font-size: 13px;
            }
        }

        /* Small mobile styles */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .main-layout {
                padding: 10px;
            }
            
            .form-header h2 {
                font-size: 1.4rem;
                line-height: 1.2;
            }
            
            .dashboard-container {
                padding: 25px 20px;
                border-radius: 12px;
            }
            
            .section-info {
                padding: 15px;
            }
            
            .dashboard-btn {
                padding: 16px;
                gap: 12px;
            }
            
            .btn-icon {
                width: 40px;
                height: 40px;
            }
            
            .btn-icon svg {
                width: 20px;
                height: 20px;
            }
            
            .btn-title {
                font-size: 14px;
            }
            
            .btn-desc {
                font-size: 12px;
            }
        }

        /* Extra small mobile styles */
        @media (max-width: 360px) {
            .form-header h2 {
                font-size: 1.2rem;
            }
            
            .dashboard-container {
                padding: 20px 15px;
            }
            
            .section-info {
                padding: 12px;
            }
            
            .dashboard-btn {
                padding: 14px;
                gap: 10px;
            }
            
            .btn-icon {
                width: 36px;
                height: 36px;
            }
            
            .btn-icon svg {
                width: 18px;
                height: 18px;
            }
            
            .btn-title {
                font-size: 13px;
            }
            
            .btn-desc {
                font-size: 11px;
            }
            
            .exit-modal-content {
                padding: 30px 20px;
                margin: 10px;
                border-radius: 15px;
            }
            
            .exit-modal h3 {
                font-size: 1.2rem;
            }
            
            .exit-modal p {
                font-size: 0.95rem;
                margin-bottom: 25px;
            }
            
            .exit-modal-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .exit-modal-btn {
                width: 100%;
                padding: 12px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-layout">
        <div class="dashboard-container">
            <div class="form-header">
                <h2>Young Generation Academy</h2>
                <p>Select your action to continue</p>
            </div>
            
            <div class="section-info">
                <div class="grade-level"><?php echo htmlspecialchars($gradeLevelDisplay); ?></div>
                <div class="section-name">Section: <?php echo htmlspecialchars($section); ?></div>
            </div>

            <div class="buttons-container">
                <button class="dashboard-btn" onclick="startDisplayRFID()">
                    <div class="btn-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                    </div>
                    <div class="btn-content">
                        <div class="btn-title">Display RFID</div>
                        <div class="btn-desc">Start RFID scanning and display</div>
                    </div>
                </button>
                <a href="viewAttendance.php" class="dashboard-btn attendance">
                    <div class="btn-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        </svg>
                    </div>
                    <div class="btn-content">
                        <div class="btn-title">View Attendance</div>
                        <div class="btn-desc">Check detailed attendance records</div>
                    </div>
                </a>
            </div>

            <button class="exit-btn" onclick="showExitModal()">
                Exit
            </button>

            <div class="veritap-brand">VeriTap™</div>
        </div>
    </div>

    <!-- Exit Confirmation Modal -->
    <div class="exit-modal" id="exitModal">
        <div class="exit-modal-content">
            <h3>Confirm Exit</h3>
            <p>Are you sure you want to exit and return to the search page? This will clear your current session.</p>
            <div class="exit-modal-buttons">
                <button class="exit-modal-btn exit-modal-yes" onclick="confirmExit()">Yes</button>
                <button class="exit-modal-btn exit-modal-no" onclick="closeExitModal()">No</button>
            </div>
        </div>
    </div>
</body>

    <script>
        // Function to start display RFID scripts and navigate
        async function startDisplayRFID() {
            const button = document.querySelector('.dashboard-btn');
            const originalText = button.textContent;
            
            try {
                // Show loading state
                button.disabled = true;
                button.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;margin-right:8px;vertical-align:-2px;animation:spin 0.8s linear infinite"></span> Loading...';
                
                // First stop any running RFID scripts
                await executeStopRFIDScript();
                
                // Start runRFID scripts for display
                await executeStartDisplayRFIDScript();
                
                // Navigate to display page
                window.location.href = 'displayRFID.php';
            } catch (error) {
                console.error('Error starting display RFID:', error);
                alert('Error starting RFID display. Please try again.');
                
                // Restore button state on error
                button.disabled = false;
                button.textContent = originalText;
            }
        }

        // Function to execute stop RFID script
        function executeStopRFIDScript() {
            return fetch('functions/execute_rfid_script.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=stop'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('RFID stop script executed successfully');
                    return data;
                } else {
                    console.warn('Failed to execute RFID stop script:', data.message);
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error executing RFID stop script:', error);
                throw error;
            });
        }

        // Function to execute start display RFID script
        function executeStartDisplayRFIDScript() {
            return fetch('functions/execute_rfid_script.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=start_display'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('RFID display script executed successfully');
                    return data;
                } else {
                    console.warn('Failed to execute RFID display script:', data.message);
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error executing RFID display script:', error);
                throw error;
            });
        }

        function showExitModal() {
            document.getElementById('exitModal').style.display = 'flex';
        }

        function closeExitModal() {
            document.getElementById('exitModal').style.display = 'none';
        }

        async function confirmExit() {
            // Show loading message inside modal while stopping Python
            const modal = document.getElementById('exitModal');
            const content = modal.querySelector('.exit-modal-content');
            const original = content.innerHTML;
            content.innerHTML = '<h3>Exiting...</h3><p>Please wait while the scanner stops.</p><div style="margin-top:10px"><span style="display:inline-block;width:18px;height:18px;border:3px solid #ddd;border-top-color:#dc3545;border-radius:50%;animation:spin 0.8s linear infinite"></span></div>';

            try {
                // Stop RFID scripts
                await fetch('functions/execute_rfid_script.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=stop'
                });
            } catch (e) {
                console.warn('Stop RFID error on exit:', e);
            }

            // Clear session data
            try {
                await fetch('functions/clear_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=clear_attendance_session'
                });
            } catch (e) {
                console.warn('Clear session error on exit:', e);
            }

            // Navigate safely
            window.location.replace('searchBar.php');
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const exitModal = document.getElementById('exitModal');
            if (event.target === exitModal) {
                closeExitModal();
            }
        });

        // Note: Removed automatic session clearing on beforeunload to prevent 
        // clearing session when navigating to Display RFID or View Attendance

        // Additional protection against back button
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Page was loaded from cache (back button), redirect to search
                window.location.replace('searchBar.php');
            }
        });

        // Prevent caching of this page
        window.addEventListener('load', function() {
            if (window.history && window.history.pushState) {
                // Replace current history entry to prevent back navigation
                window.history.replaceState(null, '', 'sectionDashboard.php');
            }
        });
    </script>
</body>
</html>
