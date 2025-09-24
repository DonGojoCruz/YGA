<?php
session_start();
require_once 'db/db_connect.php';

// Set Manila timezone
date_default_timezone_set('Asia/Manila');

// Prevent caching and back button issues
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user has selected a section
if (!isset($_SESSION['selected_section_id']) || !isset($_SESSION['selected_grade_level']) || !isset($_SESSION['selected_section'])) {
    // Clear any existing session data
    session_destroy();
    header('Location: searchBar.php');
    exit;
}

// Use session data for the target section
$TARGET_GRADE = $_SESSION['selected_grade_level'];
$TARGET_SECTION = $_SESSION['selected_section'];
$TARGET_SECTION_ID = $_SESSION['selected_section_id'];

// Get available sections from database
$sections_query = "SELECT id, grade_level, section FROM section ORDER BY grade_level, section";
$sections_result = $conn->query($sections_query);
$available_sections = [];
while ($row = $sections_result->fetch_assoc()) {
    $available_sections[] = $row;
}

// Update config file to reflect the current session
$config_file = "rfid_config.txt";

// Read existing subject from config file to preserve it
$existing_subject = '';
if (file_exists($config_file)) {
    $config_lines = file($config_file, FILE_IGNORE_NEW_LINES);
    foreach ($config_lines as $line) {
        if (strpos($line, 'TARGET_SUBJECT=') === 0) {
            $existing_subject = trim(substr($line, 15));
            break;
        }
    }
}

$config_content = "TARGET_GRADE={$TARGET_GRADE}\nTARGET_SECTION={$TARGET_SECTION}\nTARGET_SUBJECT={$existing_subject}\nEXIT_LOGGING=false\n";
file_put_contents($config_file, $config_content);

// Read current subject from config file to restore state on refresh
$current_subject = '--';
if (file_exists($config_file)) {
    $config_lines = file($config_file, FILE_IGNORE_NEW_LINES);
    foreach ($config_lines as $line) {
        if (strpos($line, 'TARGET_SUBJECT=') === 0) {
            $current_subject = trim(substr($line, 15));
            break;
        }
    }
}

// Student data will be fetched via AJAX
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Student Display</title>
    <link rel="stylesheet" href="css/displayRFID.css">
</head>
<body>
    <div class="rfid-container" id="rfidContainer">
        <!-- Controls Header -->
        <div class="controls-header">
            <!-- Left side buttons -->
            <div class="controls-left">
                <!-- Back Button -->
                <a href="#" class="back-btn" onclick="stopThenNavigate('sectionDashboard.php'); return false;">
                    ← Back to Menu
                </a>

                <!-- View Attendance Button -->
                <div class="view-attendance">
                    <a href="#" onclick="stopThenNavigate('viewAttendance.php?grade=<?php echo urlencode($TARGET_GRADE); ?>&section=<?php echo urlencode($TARGET_SECTION); ?>'); return false;">View Attendance</a>
                </div>
            </div>

            <!-- Center section with Current Section, Select Subject, and Time Out -->
            <div class="controls-center">
                <!-- Section Display (Read-only) -->
                <div class="section-display">
                    <div class="section-info-display">
                        <strong>Current Section:</strong><br>
                        Grade <?php echo htmlspecialchars($TARGET_GRADE); ?> - <?php echo htmlspecialchars($TARGET_SECTION); ?>
                    </div>
                </div>

                <!-- Subject Picker -->
                <div class="subject-picker">
                    <button onclick="showSubjectModal()">
                        📖 Select Subject
                        <div class="current-section" id="currentSubject">Current: <?php echo htmlspecialchars($current_subject); ?></div>
                    </button>
                </div>

                <!-- Time out Button -->
                <div class="timeout-btn-container">
                    <button class="timeout-btn" onclick="toggleExitLogging()" id="timeoutBtn">
                        Time Out
                    </button>
                </div>
            </div>

            <!-- Right side - Exit Button only -->
            <div class="controls-right">
                <!-- Exit Button -->
                <div class="exit-btn-container">
                    <button class="exit-btn" onclick="logout()">
                        Exit
                    </button>
                </div>
            </div>
        </div>


        <!-- Main horizontal content area -->
        <div class="main-content">
            <!-- Photo section -->
            <div class="photo-section">
                <div class="default-avatar">--</div>
            </div>
            
            <!-- Info section -->
            <div class="info-section">
                <h1 class="student-name">--</h1>
                <div class="lrn-display" id="lrnDisplay">LRN: --</div>
            </div>
        </div>
        
        <!-- Additional details grid -->
        <div class="student-details" id="studentDetails">
            <div class="detail-item">
                <div class="detail-label">Time:</div>
                <div class="detail-value">--:-- --</div>
            </div>
            
            <div class="detail-divider"></div>
            
            <div class="detail-item">
                <div class="detail-label">Date:</div>
                <div class="detail-value">--</div>
            </div>
        </div>
        
        <!-- Section Restriction Status -->
        <div class="section-restriction" id="sectionRestriction">
            Grade <?php echo htmlspecialchars($TARGET_GRADE); ?> - <?php echo htmlspecialchars($TARGET_SECTION); ?> Scanner Ready
        </div>
    </div>
    
    <!-- Password Modal -->
    <div id="passwordModal" class="password-modal">
        <div class="password-modal-content">
            <h3>Enter Password</h3>
            <p>Please enter password to select subject:</p>
            <div id="passwordError" class="password-error"></div>
            <input type="password" id="passwordInput" placeholder="Enter password..." />
            <div class="password-modal-buttons">
                <button class="password-btn password-ok" onclick="submitPassword()">OK</button>
                <button class="password-btn password-cancel" onclick="cancelPassword()">Cancel</button>
            </div>
        </div>
    </div>
    
    <!-- Exit Confirmation Modal -->
    <div id="exitModal" class="password-modal">
        <div class="password-modal-content">
            <h3>Confirm Exit</h3>
            <p>Are you sure you want to exit and return to the search page? This will clear your current session.</p>
            <div class="password-modal-buttons">
                <button class="password-btn password-cancel" onclick="confirmExit()">Yes</button>
                <button class="password-btn password-ok" onclick="cancelExit()">No</button>
            </div>
        </div>
    </div>
    
    <!-- Subject Selection Modal -->
    <div id="subjectModal" class="password-modal">
        <div class="password-modal-content">
            <h3>Select Subject</h3>
            <p>Choose a subject for the current session:</p>
            <div id="subjectList" class="subject-list">
                <!-- Subjects will be loaded here -->
            </div>
            <div class="password-modal-buttons">
                <button class="password-btn password-cancel" onclick="hideSubjectModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Password Prompt Modal for Time Out -->
    <div id="exitPasswordModal" class="password-modal">
        <div class="password-modal-content">
            <h3>Enter Password</h3>
            <p>Please enter password to access Time Out mode:</p>
            <div id="exitPasswordError" class="password-error"></div>
            <input type="password" id="exitPasswordInput" placeholder="Enter password..." />
            <div class="password-modal-buttons">
                <button class="password-btn password-ok" onclick="verifyExitPassword()">OK</button>
                <button class="password-btn password-cancel" onclick="closeExitPasswordModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Stop RFID scripts then navigate
        function stopThenNavigate(targetUrl) {
            fetch('functions/execute_rfid_script.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=stop'
            })
            .then(r => r.json())
            .catch(() => ({}))
            .finally(() => {
                window.location.href = targetUrl;
            });
        }

        let currentStudentData = null;
        let detailsHidden = false;
        let hideDetailsTimer = null;
        let consecutiveFailures = 0;
        let exitLoggingActive = false;
        let subjectSelected = false; // Track if a subject is currently selected
        const FAILURE_THRESHOLD = 3; // number of failed polls before showing disconnected
        
        // Function to update UI with new data
        function updateUI(data) {
            const container = document.getElementById('rfidContainer');
            const avatar = container.querySelector('.default-avatar');
            const name = container.querySelector('.student-name');
            const lrnDisplay = document.getElementById('lrnDisplay');
            const sectionRestriction = document.getElementById('sectionRestriction');
            const detailValues = container.querySelectorAll('.detail-value');
            
            if (data.has_scan && data.has_student) {
                // Show student data
                currentStudentData = data.student_data;
                
                // Update avatar
                if (data.student_data.photo_path && data.student_data.photo_path !== '') {
                    avatar.innerHTML = `<img src="${data.student_data.photo_path}" alt="Student Photo" class="student-photo student-photo-inline">`;
                } else {
                    const initials = (data.student_data.first_name.charAt(0) + data.student_data.last_name.charAt(0)).toUpperCase();
                    avatar.innerHTML = initials;
                }
                
                // Update prominent info
                name.textContent = `${data.student_data.first_name} ${data.student_data.last_name}`;
                lrnDisplay.textContent = `LRN: ${data.student_data.lrn || '--'}`;
                
                // Update time and date
                if (data.scan_time) {
                    console.log('🕐 Scan time received:', data.scan_time);
                    const timeParts = data.scan_time.split(' ');
                    const time = timeParts[1] || '--:-- --';
                    const date = timeParts[0] || '--';
                    console.log('🕐 Parsed time:', time, 'date:', date);
                    detailValues[0].textContent = time;
                    detailValues[1].textContent = date;
                } else {
                    console.log('⚠️ No scan_time received');
                    detailValues[0].textContent = '--:-- --';
                    detailValues[1].textContent = '--';
                }
                
                // Update section restriction based on mode
                if (data.debug && data.debug.exit_logging === 'true') {
                    const studentName = data.subject ? data.subject.replace('Exit Logging - ', '') : 'Student';
                    sectionRestriction.innerHTML = `🚪 EXIT LOGGING - Grade ${data.target_grade} - ${data.target_section} - ${studentName} Exited`;
                } else {
                sectionRestriction.innerHTML = `✓ Authorized for Grade ${data.target_grade} - ${data.target_section} Section`;
                }
                
                // Show details and start hide timer
                const studentDetails = document.getElementById('studentDetails');
                studentDetails.classList.remove('hide-details');
                detailsHidden = false;
                
                // Clear existing timer
                if (hideDetailsTimer) {
                    clearTimeout(hideDetailsTimer);
                }
                
                // Hide details after 5 seconds
                hideDetailsTimer = setTimeout(() => {
                    studentDetails.classList.add('hide-details');
                    detailsHidden = true;
                }, 5000);
            } else if (data.has_scan && data.subject === 'Choose Subject First.') {
                // Show "Choose Subject First." message
                currentStudentData = null;
                avatar.innerHTML = '⚠️';
                name.textContent = 'Choose Subject First.';
                lrnDisplay.textContent = 'Please assign a subject in configuration';
                
                sectionRestriction.innerHTML = `Grade ${data.target_grade} - ${data.target_section} - No Subject Assigned`;
                
                // Update additional details
                // Update time and date only
                if (data.scan_time) {
                    const timeParts = data.scan_time.split(' ');
                    detailValues[0].textContent = timeParts[1] || '--:-- --';
                    detailValues[1].textContent = timeParts[0] || '--';
                } else {
                    detailValues[0].textContent = '--:-- --';
                    detailValues[1].textContent = '--';
                }
                
                // Show details and start hide timer
                const studentDetails = document.getElementById('studentDetails');
                studentDetails.classList.remove('hide-details');
                detailsHidden = false;
                
                // Clear existing timer
                if (hideDetailsTimer) {
                    clearTimeout(hideDetailsTimer);
                }
                
                // Hide details after 5 seconds
                hideDetailsTimer = setTimeout(() => {
                    studentDetails.classList.add('hide-details');
                    detailsHidden = true;
                }, 5000);
                
            } else {
                // Show placeholder data
                currentStudentData = null;
                avatar.innerHTML = '--';
                name.textContent = '--';
                lrnDisplay.textContent = 'LRN: --';
                
                sectionRestriction.innerHTML = `Grade ${data.target_grade} - ${data.target_section} Scanner Ready`;
                
                // Reset details to placeholders (time, date only)
                detailValues[0].textContent = '--:-- --';
                detailValues[1].textContent = '--';
                
                // Show details
                const studentDetails = document.getElementById('studentDetails');
                studentDetails.classList.remove('hide-details');
                detailsHidden = false;
            }
        }
        
        // Function to fetch latest data
        function fetchLatestData() {
            const sectionRestriction = document.getElementById('sectionRestriction');

            // Add a short timeout so a hung request counts as a failure
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3500);

            fetch('get_rfid_data.php', { signal: controller.signal })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    consecutiveFailures = 0;
                    sectionRestriction.classList.remove('disconnected');
                    
                    // Debug logging
                    console.log('RFID Data received:', data);
                    if (data.debug) {
                        console.log('Debug info:', data.debug);
                        console.log('File exists:', data.debug.file_exists);
                        console.log('File size:', data.debug.file_size);
                        console.log('RFID file path:', data.debug.rfid_file);
                        console.log('Exit logging status:', data.debug.exit_logging);
                        console.log('File type:', data.debug.file_type);
                    }
                    
                    updateUI(data);
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    consecutiveFailures += 1;
                    if (consecutiveFailures >= FAILURE_THRESHOLD) {
                        sectionRestriction.classList.add('disconnected');
                        sectionRestriction.textContent = '✗ Scanner Disconnected — Check Arduino/USB';
                    }
                    console.error('Error fetching data:', error);
                });
        }
        
        // Auto-fetch data every 2 seconds
        setInterval(fetchLatestData, 2000);
        
        // Initial data fetch
        fetchLatestData();
        
        // Load subjects for current section on page load
        const currentGrade = '<?php echo $TARGET_GRADE; ?>';
        const currentSection = '<?php echo $TARGET_SECTION; ?>';
        loadSubjectsForSection(currentGrade, currentSection);
        
        // Check exit logging state on page load
        checkExitLoggingState();
        
        
        // Subject picker functionality
        function showSubjectModal() {
            // Load subjects and show modal
            loadSubjectsForModal();
            document.getElementById('subjectModal').style.display = 'flex';
        }
        
        function hideSubjectModal() {
            document.getElementById('subjectModal').style.display = 'none';
        }
        
        function selectSubject(subjectId, subjectName) {
            // Skip password for empty subject (clearing selection)
            if (subjectName === '--') {
                updateSubject(subjectId, subjectName);
                hideSubjectModal();
                return;
            }
            
            // Show password modal
            showPasswordModal(subjectId, subjectName);
            hideSubjectModal();
        }
        
        function showPasswordModal(subjectId, subjectName) {
            // Store the subject info for later use
            window.pendingSubject = { id: subjectId, name: subjectName };
            
            // Hide any previous error messages
            hidePasswordError();
            
            // Show the modal
            document.getElementById('passwordModal').style.display = 'flex';
            document.getElementById('passwordInput').value = '';
            document.getElementById('passwordInput').focus();
        }
        
        function hidePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            window.pendingSubject = null;
            hidePasswordError();
        }
        
        function showPasswordError(message) {
            const errorDiv = document.getElementById('passwordError');
            errorDiv.textContent = message;
            errorDiv.classList.add('show');
        }
        
        function hidePasswordError() {
            const errorDiv = document.getElementById('passwordError');
            errorDiv.textContent = '';
            errorDiv.classList.remove('show');
        }
        
        function submitPassword() {
            const password = document.getElementById('passwordInput').value;
            if (!password.trim()) {
                showPasswordError('Please enter a password');
                return;
            }
            
            if (window.pendingSubject) {
                verifyPassword(password, window.pendingSubject.id, window.pendingSubject.name);
            }
        }
        
        function cancelPassword() {
            hidePasswordModal();
        }
        
        function verifyPassword(password, subjectId, subjectName) {
            fetch('functions/verify_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `password=${encodeURIComponent(password)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hidePasswordModal();
                    updateSubject(subjectId, subjectName);
                } else {
                    showPasswordError(data.message || 'Invalid password. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error verifying password:', error);
                showPasswordError('Error verifying password. Please try again.');
            });
        }
        
        function updateSubject(subjectId, subjectName) {
            // Show loading state
            const button = document.querySelector('.subject-picker button');
            const originalText = button.innerHTML;
            button.innerHTML = '🔄 Updating...';
            button.disabled = true;
            
            // Get current grade and section from PHP
            const currentGrade = '<?php echo $TARGET_GRADE; ?>';
            const currentSection = '<?php echo $TARGET_SECTION; ?>';
            
            // Update config file
            fetch('update_section.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `grade=${encodeURIComponent(currentGrade)}&section=${encodeURIComponent(currentSection)}&subject=${encodeURIComponent(subjectName)}`
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'success') {
                    console.log(`Subject updated to ${subjectName}`);
                    // Update button text
                    button.innerHTML = `📖 Select Subject<div class="current-section">Current: ${subjectName}</div>`;
                    button.disabled = false;
                    
                    // Update timeout button state based on subject selection
                    const timeoutBtn = document.getElementById('timeoutBtn');
                    if (subjectName === '--' || subjectName === '') {
                        // No subject selected - enable timeout functionality
                        subjectSelected = false;
                        timeoutBtn.disabled = false;
                        timeoutBtn.classList.remove('disabled');
                        timeoutBtn.textContent = 'Time Out';
                        timeoutBtn.classList.remove('exit-logging');
                        console.log('✅ Timeout button enabled - no subject selected');
                    } else {
                        // Subject selected - keep button clickable but prevent functionality
                        subjectSelected = true;
                        timeoutBtn.disabled = false; // Keep clickable
                        timeoutBtn.classList.remove('disabled');
                        timeoutBtn.textContent = 'Time Out';
                        timeoutBtn.classList.remove('exit-logging');
                        console.log('⚠️ Timeout button clickable but functionality disabled - subject selected');
                    }
                    
                    // Fetch updated data
                    fetchLatestData();
                } else {
                    console.error('Failed to update subject:', data);
                    alert('Failed to update subject: ' + data);
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error updating subject:', error);
                alert('Error updating subject: ' + error);
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
        
        // Function to clear the selected subject
        function clearSelectedSubject() {
            console.log('🔄 Clearing selected subject...');
            
            // Get current grade and section from PHP
            const currentGrade = '<?php echo $TARGET_GRADE; ?>';
            const currentSection = '<?php echo $TARGET_SECTION; ?>';
            
            // Update config file to clear subject
            fetch('update_section.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `grade=${encodeURIComponent(currentGrade)}&section=${encodeURIComponent(currentSection)}&subject=--`
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'success') {
                    console.log('✅ Subject cleared successfully');
                    
                    // Update button text to show no subject selected
                    const button = document.querySelector('.subject-picker button');
                    button.innerHTML = '📖 Select Subject<div class="current-section">Current: --</div>';
                    button.disabled = false;
                    
                    // Update subjectSelected state
                    subjectSelected = false;
                    
                    // Update timeout button state
                    const timeoutBtn = document.getElementById('timeoutBtn');
                    timeoutBtn.disabled = false;
                    timeoutBtn.classList.remove('disabled');
                    timeoutBtn.textContent = 'Stop Exit Logging'; // Keep as "Stop Exit Logging" since we're in exit mode
                    timeoutBtn.classList.add('exit-logging');
                    
                    console.log('✅ Subject cleared and UI updated');
                } else {
                    console.error('❌ Failed to clear subject:', data);
                }
            })
            .catch(error => {
                console.error('❌ Error clearing subject:', error);
            });
        }
        
        // Load subjects for a specific section (legacy function for dropdown)
        function loadSubjectsForSection(grade, section) {
            // This function is kept for compatibility but no longer used
            // The modal will load subjects when needed
        }
        
        // Load subjects for the modal
        function loadSubjectsForModal() {
            const currentGrade = '<?php echo $TARGET_GRADE; ?>';
            const currentSection = '<?php echo $TARGET_SECTION; ?>';
            
            fetch('get_subjects.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `grade=${encodeURIComponent(currentGrade)}&section=${encodeURIComponent(currentSection)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.subjects) {
                    const subjectList = document.getElementById('subjectList');
                    subjectList.innerHTML = '';
                    
                    // Add subject options
                    data.subjects.forEach(subject => {
                        const option = document.createElement('div');
                        option.className = 'subject-modal-option';
                        option.textContent = subject.name;
                        option.onclick = () => selectSubject(subject.id, subject.name);
                        subjectList.appendChild(option);
                    });
                } else {
                    console.error('Failed to load subjects:', data.error);
                    const subjectList = document.getElementById('subjectList');
                    subjectList.innerHTML = '<div class="subject-modal-option">No subjects available</div>';
                }
            })
            .catch(error => {
                console.error('Error loading subjects:', error);
                const subjectList = document.getElementById('subjectList');
                subjectList.innerHTML = '<div class="subject-modal-option">Error loading subjects</div>';
            });
        }
        
        
        // Modal keyboard support
        document.addEventListener('keydown', function(event) {
            const passwordModal = document.getElementById('passwordModal');
            const exitModal = document.getElementById('exitModal');
            const subjectModal = document.getElementById('subjectModal');
            
            if (passwordModal.style.display === 'flex') {
                if (event.key === 'Enter') {
                    submitPassword();
                } else if (event.key === 'Escape') {
                    cancelPassword();
                }
            } else if (exitModal.style.display === 'flex') {
                if (event.key === 'Enter' || event.key === 'y' || event.key === 'Y') {
                    confirmExit();
                } else if (event.key === 'Escape' || event.key === 'n' || event.key === 'N') {
                    cancelExit();
                }
            } else if (subjectModal.style.display === 'flex') {
                if (event.key === 'Escape') {
                    hideSubjectModal();
                }
            }
        });
        
        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            const passwordModal = document.getElementById('passwordModal');
            const exitModal = document.getElementById('exitModal');
            const subjectModal = document.getElementById('subjectModal');
            
            if (event.target === passwordModal) {
                cancelPassword();
            } else if (event.target === exitModal) {
                cancelExit();
            } else if (event.target === subjectModal) {
                hideSubjectModal();
            }
        });
        
        // Logout function
        function logout() {
            document.getElementById('exitModal').style.display = 'flex';
        }
        
        function confirmExit() {
            // Stop RFID scripts first
            fetch('functions/execute_rfid_script.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=stop'
            })
            .then(r => r.json())
            .catch(() => ({}))
            .finally(() => {
                // Destroy session and redirect to search bar
                fetch('functions/clear_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=clear_display_session'
                })
                .then(() => {
                    // Replace current page in history to prevent back navigation
                    window.location.replace('searchBar.php');
                    
                    // Additional security: clear browser history for this session
                    if (window.history && window.history.pushState) {
                        window.history.replaceState(null, '', 'searchBar.php');
                        window.history.pushState(null, '', 'searchBar.php');
                        
                        // Prevent back button from working
                        window.addEventListener('popstate', function(event) {
                            window.location.replace('searchBar.php');
                        });
                    }
                })
                .catch(() => {
                    // Fallback if clear_session.php doesn't exist
                    window.location.replace('searchBar.php');
                });
            });
        }
        
        function cancelExit() {
            document.getElementById('exitModal').style.display = 'none';
        }
        
        // Check exit logging state on page load
        function checkExitLoggingState() {
            fetch('get_rfid_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.debug && data.debug.exit_logging === 'true') {
                        // Exit logging is active, update UI
                        exitLoggingActive = true;
                        const timeoutBtn = document.getElementById('timeoutBtn');
                        timeoutBtn.textContent = 'Stop Exit Logging';
                        timeoutBtn.classList.add('exit-logging');
                        
                        // Update section restriction to show exit logging mode
                        const sectionRestriction = document.getElementById('sectionRestriction');
                        sectionRestriction.innerHTML = `🚪 EXIT LOGGING MODE - Grade ${currentGrade} - ${currentSection}`;
                        sectionRestriction.style.background = '#ffebee';
                        sectionRestriction.style.borderColor = '#dc2626';
                        sectionRestriction.style.color = '#b91c1c';
                    }
                })
                .catch(error => {
                    console.log('Could not check exit logging state:', error);
                });
        }
        
        // Exit Logging Functions
        function toggleExitLogging() {
            console.log('🔄 Toggle exit logging called. Current state:', exitLoggingActive);
            const timeoutBtn = document.getElementById('timeoutBtn');
            
            if (exitLoggingActive) {
                console.log('🛑 Currently active, stopping exit logging...');
                // Stop exit logging
                stopExitLogging();
            } else {
                console.log('🚪 Currently inactive, requesting password for exit logging...');
                // Show password prompt for exit logging
                showExitPasswordPrompt();
            }
        }
        
        // Function to clear subject and then start exit logging
        function clearSelectedSubjectAndStartExitLogging() {
            console.log('🔄 Clearing subject and starting exit logging...');
            
            // Get current grade and section from PHP
            const currentGrade = '<?php echo $TARGET_GRADE; ?>';
            const currentSection = '<?php echo $TARGET_SECTION; ?>';
            
            // Update config file to clear subject
            fetch('update_section.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `grade=${encodeURIComponent(currentGrade)}&section=${encodeURIComponent(currentSection)}&subject=--`
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'success') {
                    console.log('✅ Subject cleared successfully, now starting exit logging');
                    
                    // Update UI to show cleared subject
                    const button = document.querySelector('.subject-picker button');
                    button.innerHTML = '📖 Select Subject<div class="current-section">Current: --</div>';
                    button.disabled = false;
                    
                    // Update subjectSelected state
                    subjectSelected = false;
                    
                    // Now start exit logging
                    startExitLogging();
                } else {
                    console.error('❌ Failed to clear subject:', data);
                    alert('Failed to clear subject: ' + data);
                }
            })
            .catch(error => {
                console.error('❌ Error clearing subject:', error);
                alert('Error clearing subject: ' + error.message);
            });
        }
        
        function startExitLogging() {
            console.log('🚪 Starting exit logging...');
            
            fetch('functions/start_exit_logging.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=start'
            })
            .then(response => {
                console.log('🚪 Exit logging response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('🚪 Exit logging response data:', data);
                if (data.success) {
                    exitLoggingActive = true;
                    const timeoutBtn = document.getElementById('timeoutBtn');
                    timeoutBtn.textContent = 'Stop Exit Logging';
                    timeoutBtn.classList.add('exit-logging');
                    
                    // Subject is already cleared before calling this function
                    
                    // Update section restriction to show exit logging mode
                    const sectionRestriction = document.getElementById('sectionRestriction');
                    sectionRestriction.innerHTML = `🚪 EXIT LOGGING MODE - Grade <?php echo $TARGET_GRADE; ?> - <?php echo $TARGET_SECTION; ?>`;
                    sectionRestriction.style.background = '#ffebee';
                    sectionRestriction.style.borderColor = '#dc2626';
                    sectionRestriction.style.color = '#b91c1c';
                    
                    console.log('✅ Exit logging started successfully');
                } else {
                    console.error('❌ Failed to start exit logging:', data.message);
                    alert('Failed to start exit logging: ' + data.message);
                }
            })
            .catch(error => {
                console.error('❌ Error starting exit logging:', error);
                alert('Error starting exit logging: ' + error.message);
            });
        }
        
        function stopExitLogging() {
            console.log('🛑 Stopping exit logging...');
            
            fetch('functions/stop_exit_logging.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=stop'
            })
            .then(response => {
                console.log('🛑 Stop exit logging response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('🛑 Stop exit logging response data:', data);
                if (data.success) {
                    exitLoggingActive = false;
                    const timeoutBtn = document.getElementById('timeoutBtn');
                    timeoutBtn.textContent = 'Time Out';
                    timeoutBtn.classList.remove('exit-logging');
                    
                    // Reset section restriction to normal mode
                    const sectionRestriction = document.getElementById('sectionRestriction');
                    sectionRestriction.innerHTML = `Grade <?php echo $TARGET_GRADE; ?> - <?php echo $TARGET_SECTION; ?> Scanner Ready`;
                    sectionRestriction.style.background = '#fff3e0';
                    sectionRestriction.style.borderColor = '#ff9800';
                    sectionRestriction.style.color = '#e65100';
                    
                    console.log('✅ Exit logging stopped successfully');
                } else {
                    console.error('❌ Failed to stop exit logging:', data.message);
                    alert('Failed to stop exit logging: ' + data.message);
                }
            })
            .catch(error => {
                console.error('❌ Error stopping exit logging:', error);
                alert('Error stopping exit logging: ' + error.message);
            });
        }
        
        // Prevent back navigation and clear session on page unload
        window.addEventListener('beforeunload', function(event) {
            // Clear session when user tries to navigate away
            navigator.sendBeacon('functions/clear_session.php', 'action=clear_display_session');
        });

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
                window.history.replaceState(null, '', 'displayRFID.php');
            }
            
            // Validate session on page load
            validateSession();
            
            // Set initial timeout button state based on current subject
            const currentSubject = '<?php echo htmlspecialchars($current_subject); ?>';
            const timeoutBtn = document.getElementById('timeoutBtn');
            
            if (currentSubject === '--' || currentSubject === '') {
                // No subject selected - enable timeout functionality
                subjectSelected = false;
                timeoutBtn.disabled = false;
                timeoutBtn.classList.remove('disabled');
                console.log('✅ Initial state: Timeout button enabled - no subject selected');
            } else {
                // Subject selected - keep button clickable but prevent functionality
                subjectSelected = true;
                timeoutBtn.disabled = false; // Keep clickable
                timeoutBtn.classList.remove('disabled');
                console.log('⚠️ Initial state: Timeout button clickable but functionality disabled - subject selected');
            }
        });
        
        // Validate session to prevent unauthorized access
        function validateSession() {
            fetch('functions/validate_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=validate_display_session'
            })
            .then(response => response.json())
            .then(data => {
                if (!data.valid) {
                    // Session is invalid, redirect to search
                    window.location.replace('searchBar.php');
                }
            })
            .catch(error => {
                console.error('Error validating session:', error);
                // If validation fails, redirect to search for security
                window.location.replace('searchBar.php');
            });
        }

        // Exit Password Functions
        function showExitPasswordPrompt() {
            console.log('🔒 Showing exit password prompt');
            const modal = document.getElementById('exitPasswordModal');
            const passwordInput = document.getElementById('exitPasswordInput');
            const errorDiv = document.getElementById('exitPasswordError');
            
            // Clear previous input and errors
            passwordInput.value = '';
            errorDiv.textContent = '';
            errorDiv.style.display = 'none';
            
            // Show modal
            modal.style.display = 'flex';
            passwordInput.focus();
        }

        function closeExitPasswordModal() {
            console.log('❌ Closing exit password modal');
            const modal = document.getElementById('exitPasswordModal');
            modal.style.display = 'none';
        }

        function verifyExitPassword() {
            const passwordInput = document.getElementById('exitPasswordInput');
            const errorDiv = document.getElementById('exitPasswordError');
            const enteredPassword = passwordInput.value.trim();
            
            if (enteredPassword === '') {
                errorDiv.textContent = 'Please enter a password.';
                errorDiv.style.display = 'block';
                passwordInput.focus();
                return;
            }
            
            // Validate password against database
            fetch('functions/validate_timeout_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    password: enteredPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Exit password verified successfully');
                    closeExitPasswordModal();
                    
                    // Clear subject first, then start exit logging
                    if (subjectSelected) {
                        console.log('🔄 Subject is selected - clearing it first before starting exit logging');
                        clearSelectedSubjectAndStartExitLogging();
                    } else {
                        console.log('✅ No subject selected - starting exit logging directly');
                        startExitLogging();
                    }
                } else {
                    console.log('❌ Incorrect exit password entered:', data.message);
                    errorDiv.textContent = data.message || 'Incorrect password. Please try again.';
                    errorDiv.style.display = 'block';
                    passwordInput.value = '';
                    passwordInput.focus();
                }
            })
            .catch(error => {
                console.error('❌ Error validating password:', error);
                errorDiv.textContent = 'Error validating password. Please try again.';
                errorDiv.style.display = 'block';
                passwordInput.value = '';
                passwordInput.focus();
            });
        }

        // Handle Enter key in password input
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('exitPasswordInput');
            if (passwordInput) {
                passwordInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        verifyExitPassword();
                    }
                });
            }
        });
        
        // Removed auto-close behavior so the tab/window isn't closed on tap or Escape
    </script>
</body>
</html>


