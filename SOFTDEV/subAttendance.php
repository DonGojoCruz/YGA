<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

?>
<!doctype html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <title>Subject Attendance</title>
    <link rel="stylesheet" href="css/subAttendance.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main>
            <div class="page-header">
                <h1 class="page-title">Attendance Records</h1>
                <div class="search-container">
                    <div class="search-bar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchInput" placeholder="Search Section" />
                    </div>
                </div>
            </div>
            
            <div class="content-body">
                
                <!-- Exit Log Table -->
                <div class="exit-log-container">
                    <div class="exit-log-header">
                        <h2 class="exit-log-title">
                            <i class="fa-solid fa-door-open"></i>
                            Today's Exit Log
                        </h2>
                        <div class="exit-log-controls">
                            <button class="refresh-exit-log-btn" onclick="loadExitLogs()" title="Refresh Exit Log">
                                <i class="fa-solid fa-refresh"></i>
                            </button>
                        </div>
                    </div>
                    <div class="exit-log-table-container">
                        <div class="exit-log-loading" id="exitLogLoading">Loading exit logs...</div>
                        <table class="exit-log-table" id="exitLogTable" style="display: none;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Grade</th>
                                    <th>Section</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="exitLogTableBody">
                                <!-- Exit log data will be loaded here -->
                            </tbody>
                        </table>
                        <div class="exit-log-empty" id="exitLogEmpty" style="display: none;">
                            <div class="empty-state">
                                <i class="fa-solid fa-door-closed"></i>
                                <h3>No exits recorded today</h3>
                                <p>Student exit logs will appear here when students scan their RFID tags to exit.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Grade Levels Container -->
                <div class="grade-levels-container" id="gradeLevelsContainer">
                    <!-- Grade levels with sections will be loaded here -->
                </div>
            </div>
        </main>
    </div>

    <!-- Subject Attendance Modal -->
    <div id="subjectAttendanceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="subjectAttendanceTitle">Subject Attendance</h2>
                <span class="close" onclick="closeSubjectAttendanceModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="attendance-controls">
                    <div class="attendance-summary" id="attendanceSummary">
                        <!-- Summary cards will be loaded here -->
                    </div>
                    <div class="export-controls">
                        <button class="save-edits-btn" id="saveEditsBtn" onclick="saveAttendanceEdits()">
                            <i class="fa-solid fa-save"></i>
                            <span>Save Edits</span>
                        </button>
                        <button class="export-btn" id="exportBtn" onclick="exportAttendance()">
                            <i class="fa-solid fa-download"></i>
                            <span>Export</span>
                        </button>
                    </div>
                </div>
                
                <div class="attendance-table-container">
                    <div style="font-size: 12px; color: #6b7280; margin: 8px 0 12px 0;">
                        Note: Only today's attendance can be edited.
                    </div>
                        <table class="attendance-table" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                        <tbody>
                            <!-- Attendance data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Attendance Modal -->
    <div id="dailyAttendanceModal" class="modal">
        <div class="modal-content daily-attendance-modal">
            <div class="modal-header">
                <h2 id="dailyAttendanceTitle">Daily Full Subject Attendance</h2>
                <span class="close" onclick="closeDailyAttendanceModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="attendance-tabs">
                    <button class="tab-btn active" id="dailyTab" onclick="switchTab('daily')">
                        <i class="fa-solid fa-calendar-day"></i>
                        <span>Daily</span>
                    </button>
                    <button class="tab-btn" id="monthlyTab" onclick="switchTab('monthly')">
                        <i class="fa-solid fa-calendar"></i>
                        <span>Monthly</span>
                    </button>
                </div>
                
                <div class="daily-attendance-controls" id="dailyControls">
                    <div class="date-selector">
                        <label for="attendanceDate">Select Date:</label>
                        <input type="date" id="attendanceDate" onchange="loadDailyAttendance()">
                    </div>
                    <div class="export-controls">
                        <button class="export-btn" id="exportDailyBtn" onclick="exportDailyAttendance()">
                            <i class="fa-solid fa-download"></i>
                            <span>Export</span>
                        </button>
                    </div>
                </div>
                
                <div class="monthly-attendance-controls" id="monthlyControls" style="display: none;">
                    <div class="month-year-selector">
                        <div class="calendar-picker">
                            <label for="monthYearPicker">Select Month & Year:</label>
                            <div class="calendar-input-wrapper">
                                <input type="month" id="monthYearPicker" class="calendar-input" onchange="loadMonthlyAttendance()">
                                <i class="fa-solid fa-calendar-alt calendar-icon"></i>
                            </div>
                        </div>
                        <div class="subject-filter">
                            <label for="subjectFilter">Filter by Subject:</label>
                            <div class="subject-select-wrapper">
                                <select id="subjectFilter" class="subject-select" onchange="loadMonthlyAttendance()">
                                    <option value="all">All Subjects (Combined)</option>
                                </select>
                                <i class="fa-solid fa-chevron-down subject-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="export-controls">
                        <button class="export-btn" id="exportMonthlyBtn" onclick="exportMonthlyAttendance()">
                            <i class="fa-solid fa-download"></i>
                            <span>Export</span>
                        </button>
                    </div>
                </div>
                
                <!-- Daily Legend -->
                <div class="daily-legend" id="dailyLegend" style="display: none;">
                    <div class="legend-title">Legend:</div>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="legend-color present"></div>
                            <span>Present</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color absent"></div>
                            <span>Absent</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color late"></div>
                            <span>Late</span>
                        </div>
                    </div>
                </div>

                <!-- Monthly Legend -->
                <div class="monthly-legend" id="monthlyLegend" style="display: none;">
                    <div class="legend-title">Legend:</div>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="legend-color present"></div>
                            <span>Present</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color absent"></div>
                            <span>Absent</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color late"></div>
                            <span>Late</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color weekend"></div>
                            <span>Weekends (Saturday & Sunday)</span>
                        </div>
                    </div>
                </div>
                
                <div class="daily-attendance-table-container" id="dailyTableContainer">
                    <table class="daily-attendance-table" id="dailyAttendanceTable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <!-- Subject columns will be dynamically added here -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Daily attendance data will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <div class="monthly-attendance-table-container" id="monthlyTableContainer" style="display: none;">
                    <table class="monthly-attendance-table" id="monthlyAttendanceTable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <!-- Date columns will be dynamically added here -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Monthly attendance data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables for real data
        let sectionsData = [];
        let attendanceData = {};

        // Helper function to convert attendance status to single letter
        function getStatusLetter(status) {
            switch (status?.toLowerCase()) {
                case 'present': return 'P';
                case 'absent': return 'A';
                case 'late': return 'L';
                default: return 'A'; // Default to Absent
            }
        }

        // Load sections grouped by grade level
        function loadSections() {
            const container = document.getElementById('gradeLevelsContainer');
            container.innerHTML = '<div class="loading">Loading sections...</div>';

            // Fetch sections from database
            fetch('functions/get_subject_attendance_data.php?action=get_sections')
                .then(response => response.json())
                .then(data => {
                    sectionsData = data;
                    lastSectionsData = data; // Store for comparison
                    displaySections(data);
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    container.innerHTML = '<div class="error">Error loading sections. Please try again.</div>';
                });
        }

        // Display sections grouped by grade level
        function displaySections(sections) {
            const container = document.getElementById('gradeLevelsContainer');
            container.innerHTML = '';

            if (sections.length === 0) {
                container.innerHTML = `
                    <div class="no-sections" style="
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        padding: 60px 20px;
                        text-align: center;
                        color: #666;
                        min-height: 300px;
                    ">
                        <div style="font-size: 64px; margin-bottom: 20px; opacity: 0.5;">📊</div>
                        <h3 style="margin: 0 0 10px 0; font-size: 1.5rem; color: #444;">No sections found</h3>
                        <p style="margin: 0; font-size: 1rem;">No sections with subjects available for attendance tracking.</p>
                    </div>
                `;
                return;
            }

            // Group sections by grade level
            const groupedSections = sections.reduce((groups, section) => {
                const gradeLevel = section.grade_level;
                if (!groups[gradeLevel]) {
                    groups[gradeLevel] = [];
                }
                groups[gradeLevel].push(section);
                return groups;
            }, {});

            // Sort grade levels
            const sortedGradeLevels = Object.keys(groupedSections).sort((a, b) => {
                const order = ['Nursery', 'Kinder 1', 'Kinder 2'];
                const aIndex = order.indexOf(a);
                const bIndex = order.indexOf(b);
                
                if (aIndex !== -1 && bIndex !== -1) return aIndex - bIndex;
                if (aIndex !== -1) return -1;
                if (bIndex !== -1) return 1;
                
                return parseInt(a) - parseInt(b);
            });

            // Create grade level containers
            sortedGradeLevels.forEach(gradeLevel => {
                const gradeDisplay = /^\d+$/.test(gradeLevel) ? `Grade ${gradeLevel}` : gradeLevel;
                const sections = groupedSections[gradeLevel];
                
                const gradeContainer = document.createElement('div');
                gradeContainer.className = 'grade-level-container';
                gradeContainer.innerHTML = `
                    <div class="grade-level-header">
                        <h2 class="grade-level-title">${gradeDisplay}</h2>
                        <div class="grade-level-info">
                            <span class="sections-count">${sections.length} Section${sections.length !== 1 ? 's' : ''}</span>
                            <button class="toggle-grade-btn" onclick="toggleGradeLevel('${gradeLevel}')">
                                <i class="fa-solid fa-chevron-down"></i>
                                <span>Hide</span>
                            </button>
                        </div>
                    </div>
                    <div class="sections-grid" id="sections-${gradeLevel}">
                        ${sections.map(section => createSectionCard(section)).join('')}
                    </div>
                `;
                container.appendChild(gradeContainer);
            });
        }

        // Create individual section card
        function createSectionCard(section) {
            const gradeDisplay = /^\d+$/.test(section.grade_level) ? `Grade ${section.grade_level}` : section.grade_level;
            
            return `
                <div class="section-card" onclick="viewDailyAttendance('${section.grade_level}', '${section.section}', ${section.id})">
                    <div class="card-header">
                        <h3 class="section-title">${section.section}</h3>
                        <div class="adviser-info">
                            <i class="fa-solid fa-chalkboard-user"></i>
                            <span>${section.adviser_name}</span>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="subjects-section">
                            <h4 class="subjects-title">
                                <span>Subjects</span>
                                <button class="show-all-btn" onclick="event.stopPropagation(); toggleSubjects(this, ${section.id})">Show All</button>
                            </h4>
                            <div class="subjects-list collapsed" id="subjects-${section.id}">
                                ${section.subjects.map(subject => `
                                    <div class="subject-item" onclick="event.stopPropagation(); viewSubjectAttendance(${subject.id}, '${subject.name}', '${section.grade_level}', '${section.section}')">
                                        <div class="subject-info">
                                            <div class="subject-name">${subject.name}</div>
                                            <div class="subject-details">
                                                <span class="subject-code">${subject.code}</span>
                                                <span class="subject-teacher">${subject.teacher}</span>
                                            </div>
                                        </div>
                                        <div class="subject-arrow">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Toggle grade level visibility
        function toggleGradeLevel(gradeLevel) {
            const sectionsGrid = document.getElementById(`sections-${gradeLevel}`);
            const toggleBtn = document.querySelector(`[onclick="toggleGradeLevel('${gradeLevel}')"]`);
            const icon = toggleBtn.querySelector('i');
            const text = toggleBtn.querySelector('span');
            
            // Check if sections are currently visible (either no display style or display !== 'none')
            const isVisible = sectionsGrid.style.display !== 'none';
            
            if (isVisible) {
                // Hide sections
                sectionsGrid.style.display = 'none';
                icon.className = 'fa-solid fa-chevron-right';
                text.textContent = 'Show';
            } else {
                // Show sections
                sectionsGrid.style.display = 'grid';
                icon.className = 'fa-solid fa-chevron-down';
                text.textContent = 'Hide';
            }
        }

        // Toggle subjects visibility
        function toggleSubjects(button, sectionId) {
            const subjectsList = document.getElementById(`subjects-${sectionId}`);
            const isCollapsed = subjectsList.classList.contains('collapsed');
            
            if (isCollapsed) {
                subjectsList.classList.remove('collapsed');
                button.textContent = 'Show Less';
            } else {
                subjectsList.classList.add('collapsed');
                button.textContent = 'Show All';
            }
        }

        // Global variables to store current attendance data and filter
        let currentAttendanceData = null;
        let currentFilter = 'all';
        let editedStatuses = new Set(); // Track which students have edited status
        let editDescriptions = {}; // Track edit descriptions {studentIndex: description}
        let initialStatuses = {}; // Initial status per index
        let initialDescriptions = {}; // Initial description per index
        let hasSavedEdits = false; // Track if there are saved edits to preserve
        
        // Global variables for daily attendance
        let currentDailyAttendanceData = null;
        let currentSectionId = null;
        
        // Global variables for monthly attendance
        let currentMonthlyAttendanceData = null;
        let currentTab = 'daily';

        // View subject attendance
        function viewSubjectAttendance(subjectId, subjectName, gradeLevel, sectionName) {
            const modal = document.getElementById('subjectAttendanceModal');
            const title = document.getElementById('subjectAttendanceTitle');
            const summaryContainer = document.getElementById('attendanceSummary');
            const tableBody = document.querySelector('#attendanceTable tbody');
            
            const gradeDisplay = /^\d+$/.test(gradeLevel) ? `Grade ${gradeLevel}` : gradeLevel;
            title.textContent = `${gradeDisplay} - ${sectionName} - ${subjectName} Attendance`;
            
            // Show loading state
            summaryContainer.innerHTML = '<div class="loading">Loading attendance data...</div>';
            tableBody.innerHTML = '';
            modal.classList.add('show');
            
            // Start auto-refresh for RFID updates
            startAutoRefresh();
            
            // Get section ID from sectionsData
            const section = sectionsData.find(s => s.section === sectionName && s.grade_level === gradeLevel);
            if (!section) {
                summaryContainer.innerHTML = '<div class="no-data">Section not found.</div>';
                return;
            }
            
            // Fetch attendance data from database
            fetch(`functions/get_subject_attendance_data.php?action=get_attendance&subject_id=${subjectId}&section_id=${section.id}`)
                .then(response => response.json())
                .then(data => {
                    currentAttendanceData = { students: data };
                    
                    if (currentAttendanceData.students.length === 0) {
                        summaryContainer.innerHTML = '<div class="no-data">No students registered in this section.</div>';
                        tableBody.innerHTML = '';
                        return;
                    }
                    
                    const presentCount = currentAttendanceData.students.filter(s => s.status === 'present').length;
                    const lateCount = currentAttendanceData.students.filter(s => s.status === 'late').length;
                    const absentCount = currentAttendanceData.students.filter(s => s.status === 'absent').length;
                    const totalCount = currentAttendanceData.students.length;
                    
                    const summaryHTML = `
                        <div class="summary-card present clickable" onclick="filterAttendance('present')">
                            <div class="summary-icon">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                            <div class="summary-info">
                                <div class="summary-number">${presentCount}</div>
                                <div class="summary-label">Present</div>
                            </div>
                        </div>
                        <div class="summary-card late clickable" onclick="filterAttendance('late')">
                            <div class="summary-icon">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="summary-info">
                                <div class="summary-number">${lateCount}</div>
                                <div class="summary-label">Late</div>
                            </div>
                        </div>
                        <div class="summary-card absent clickable" onclick="filterAttendance('absent')">
                            <div class="summary-icon">
                                <i class="fa-solid fa-times-circle"></i>
                            </div>
                            <div class="summary-info">
                                <div class="summary-number">${absentCount}</div>
                                <div class="summary-label">Absent</div>
                            </div>
                        </div>
                        <div class="summary-card total clickable" onclick="filterAttendance('all')">
                            <div class="summary-icon">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div class="summary-info">
                                <div class="summary-number">${totalCount}</div>
                                <div class="summary-label">Total</div>
                            </div>
                        </div>
                    `;
                    
                    const tableHTML = currentAttendanceData.students.map((student, index) => {
                        // Format name as "Last Name, First Name Middle Name"
                        let formattedName = student.name;
                        if (student.last_name && student.first_name) {
                            const middleName = student.middle_name || student.middle_initial || '';
                            const middlePart = middleName ? ` ${middleName}` : '';
                            formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                        }
                        
                        // Check if student has existing description from CSV
                        const existingDescription = student.description || '';
                        const hasExistingDescription = existingDescription.trim() !== '';
                        
                        return `
                        <tr class="attendance-row" data-status="${student.status}">
                            <td>${index + 1}</td>
                            <td>${formattedName}</td>
                            <td>${student.date}</td>
                            <td>${student.time}</td>
                            <td>
                                <div class="status-editor">
                                    <select class="status-select ${student.status}" onchange="updateStatus(${index}, this.value)" data-index="${index}" id="status-${index}" name="status[]">
                                        <option value="present" ${student.status === 'present' ? 'selected' : ''}>Present</option>
                                        <option value="late" ${student.status === 'late' ? 'selected' : ''}>Late</option>
                                        <option value="absent" ${student.status === 'absent' ? 'selected' : ''}>Absent</option>
                                    </select>
                                    <span class="edit-indicator" id="edit-indicator-${index}" style="${hasExistingDescription ? 'display: flex; color: #28a745;' : 'display: none;'}">
                                        <i class="fa-solid fa-${hasExistingDescription ? 'check-circle' : 'edit'}"></i>
                                        <span>${hasExistingDescription ? 'Saved' : 'Edited'}</span>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="description-editor">
                                    <input type="text" class="description-input show" id="description-${index}" name="description[]" 
                                           placeholder="Enter reason for edit..." value="${existingDescription}" 
                                           onchange="updateDescription(${index}, this.value)">
                                </div>
                            </td>
                        </tr>
                    `;
                    }).join('');
                    
                    summaryContainer.innerHTML = summaryHTML;
                    tableBody.innerHTML = tableHTML;
                    
                    // Reset filter and edited statuses
                    currentFilter = 'all';
                    editedStatuses.clear();
                    editDescriptions = {};
                    initialStatuses = {};
                    initialDescriptions = {};
                    
                    // Capture initial values for change detection
                    currentAttendanceData.students.forEach((student, index) => {
                        initialStatuses[index] = student.status || 'absent';
                        initialDescriptions[index] = (student.description || '');
                    });
                    updateSaveButtonState();
                })
                .catch(error => {
                    console.error('Error loading attendance data:', error);
                    summaryContainer.innerHTML = '<div class="error">Error loading attendance data. Please try again.</div>';
                });
        }

        // Update student status
        function updateStatus(studentIndex, newStatus) {
            if (!currentAttendanceData || !currentAttendanceData.students) return;
            
            // Store original status if not already stored
            if (!currentAttendanceData.students[studentIndex].original_status) {
                currentAttendanceData.students[studentIndex].original_status = currentAttendanceData.students[studentIndex].status;
            }
            
            // Update the data
            currentAttendanceData.students[studentIndex].status = newStatus;
            
            // Update the row's data-status attribute
            const row = document.querySelector(`tr[data-status]`).parentNode.querySelectorAll('tr')[studentIndex];
            if (row) {
                row.setAttribute('data-status', newStatus);
            }
            
            // Update dropdown class for color change
            const dropdown = document.querySelector(`select[data-index="${studentIndex}"]`);
            if (dropdown) {
                dropdown.className = `status-select ${newStatus}`;
            }
            
            // Mark as edited
            editedStatuses.add(studentIndex);
            
            // Show edit indicator
            const editIndicator = document.getElementById(`edit-indicator-${studentIndex}`);
            if (editIndicator) {
                editIndicator.style.display = 'flex';
            }
            
            // Show description input
            const descriptionInput = document.getElementById(`description-${studentIndex}`);
            if (descriptionInput) {
                descriptionInput.classList.add('show');
                descriptionInput.focus();
            }
            
            // Enable save button
            const saveBtn = document.getElementById('saveEditsBtn');
            if (saveBtn) {
                // Button state depends on whether anything changed from initial
                updateSaveButtonState();
            }
            
            // Update summary counts
            updateSummaryCounts();
        }
        
        // Update description for edited attendance
        function updateDescription(studentIndex, description) {
            editDescriptions[studentIndex] = description;
            // Mark this row as edited even if status didn't change
            editedStatuses.add(studentIndex);
            const saveBtn = document.getElementById('saveEditsBtn');
            if (saveBtn) {
                updateSaveButtonState();
            }
        }

        // Keep Save button enabled (except while actively saving)
        function updateSaveButtonState() {
            const saveBtn = document.getElementById('saveEditsBtn');
            if (!saveBtn) return;
            saveBtn.disabled = false;
        }

        // Update summary counts
        function updateSummaryCounts() {
            if (!currentAttendanceData || !currentAttendanceData.students) return;
            
            const presentCount = currentAttendanceData.students.filter(s => s.status === 'present').length;
            const lateCount = currentAttendanceData.students.filter(s => s.status === 'late').length;
            const absentCount = currentAttendanceData.students.filter(s => s.status === 'absent').length;
            const totalCount = currentAttendanceData.students.length;
            
            // Update summary cards
            const summaryCards = document.querySelectorAll('.summary-card');
            if (summaryCards.length >= 4) {
                summaryCards[0].querySelector('.summary-number').textContent = presentCount;
                summaryCards[1].querySelector('.summary-number').textContent = lateCount;
                summaryCards[2].querySelector('.summary-number').textContent = absentCount;
                summaryCards[3].querySelector('.summary-number').textContent = totalCount;
            }
        }

        // Save attendance edits to CSV log file
        function saveAttendanceEdits() {
            if (editedStatuses.size === 0 && Object.keys(editDescriptions).length === 0) {
                alert('No edits to save.');
                return;
            }
            
            // Get current subject and section info from modal title
            const title = document.getElementById('subjectAttendanceTitle').textContent;
            const titleParts = title.split(' - ');
            const gradeLevel = titleParts[0];
            const section = titleParts[1];
            const subject = titleParts[2].replace(' Attendance', '');
            
            // Prepare edit data
            const editsToSave = [];
            editedStatuses.forEach(studentIndex => {
                const student = currentAttendanceData.students[studentIndex];
                const description = editDescriptions[studentIndex] || '';
                
                // Format name as "Last Name, First Name Middle Name"
                let formattedName = student.name;
                if (student.last_name && student.first_name) {
                    const middleName = student.middle_name || student.middle_initial || '';
                    const middlePart = middleName ? ` ${middleName}` : '';
                    formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                }
                
                editsToSave.push({
                    student_name: formattedName,
                    student_id: student.id,
                    rfid_tag: student.rfid_tag || '',
                    old_status: student.original_status || student.status,
                    new_status: student.status,
                    description: description,
                    date: student.date,
                    time: student.time
                });
            });
            
            // Show loading state
            const saveBtn = document.getElementById('saveEditsBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Saving...</span>';
            saveBtn.disabled = true;
            
            // Send to backend
            fetch('functions/save_attendance_edits.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    subject: subject,
                    section: section,
                    grade_level: gradeLevel,
                    edits: editsToSave
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Attendance edits saved successfully!');
                    
                    // Keep the button visible but disable it until further edits
                    saveBtn.disabled = true;
                    
                    // Keep edit indicators visible but change them to "saved" indicators
                    document.querySelectorAll('.edit-indicator').forEach((indicator, index) => {
                        if (editedStatuses.has(index)) {
                            indicator.innerHTML = '<i class="fa-solid fa-check-circle"></i> <span>Saved</span>';
                            indicator.style.color = '#28a745';
                        }
                    });
                    
                    // Keep description inputs visible and editable
                    document.querySelectorAll('.description-input.show').forEach(input => {
                        input.readOnly = false;
                        input.style.backgroundColor = '';
                        input.style.color = '';
                    });
                    
                    // Update current attendance data to reflect the saved changes
                    // Don't update time - keep original time from when student was actually scanned
                    editedStatuses.forEach(studentIndex => {
                        const student = currentAttendanceData.students[studentIndex];
                        // Keep original time and date - only status and description change
                    });
                    
                    // Update the table display with saved changes
                    updateAttendanceTableAfterSave();
                    
                    // Clear edit tracking but don't reset the visual changes
                    editedStatuses.clear();
                    hasSavedEdits = true;

                    // Update initial maps to reflect just-saved state
                    currentAttendanceData.students.forEach((student, index) => {
                        initialStatuses[index] = student.status || 'absent';
                        initialDescriptions[index] = (editDescriptions.hasOwnProperty(index) ? editDescriptions[index] : (student.description || ''));
                    });
                    updateSaveButtonState();
                } else {
                    alert('Error saving edits: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error saving edits:', error);
                alert('Error saving edits. Please try again.');
            })
            .finally(() => {
                // Restore button state
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        }

        // Update attendance table after saving edits
        function updateAttendanceTableAfterSave() {
            const tableBody = document.querySelector('#attendanceTable tbody');
            
            // Update table rows with new data
            const tableHTML = currentAttendanceData.students.map((student, index) => {
                // Format name as "Last Name, First Name Middle Name"
                let formattedName = student.name;
                if (student.last_name && student.first_name) {
                    const middleName = student.middle_name || student.middle_initial || '';
                    const middlePart = middleName ? ` ${middleName}` : '';
                    formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                }
                
                // Check if this student was edited
                const description = editDescriptions[index] || '';
                const wasEdited = description !== '';
                
                return `
                <tr class="attendance-row" data-status="${student.status}">
                    <td>${index + 1}</td>
                    <td>${formattedName}</td>
                    <td>${student.date}</td>
                    <td>${student.time}</td>
                    <td>
                        <div class="status-editor">
                            <select class="status-select ${student.status}" onchange="updateStatus(${index}, this.value)" data-index="${index}" id="status-${index}" name="status[]">
                                <option value="present" ${student.status === 'present' ? 'selected' : ''}>Present</option>
                                <option value="late" ${student.status === 'late' ? 'selected' : ''}>Late</option>
                                <option value="absent" ${student.status === 'absent' ? 'selected' : ''}>Absent</option>
                            </select>
                            <span class="edit-indicator" id="edit-indicator-${index}" style="${wasEdited ? 'display: flex; color: #28a745;' : 'display: none;'}">
                                <i class="fa-solid fa-check-circle"></i>
                                <span>Saved</span>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="description-editor">
                            <input type="text" class="description-input show" id="description-${index}" name="description[]" 
                                   placeholder="Enter reason for edit..." value="${description}" 
                                   onchange="updateDescription(${index}, this.value)">
                        </div>
                    </td>
                </tr>
            `;
            }).join('');
            
            tableBody.innerHTML = tableHTML;
            updateSummaryCounts();
        }

        // Close subject attendance modal
        function closeSubjectAttendanceModal() {
            const modal = document.getElementById('subjectAttendanceModal');
            modal.classList.remove('show');
            
            // Stop auto-refresh when modal is closed
            stopAutoRefresh();
            
            // Reset flags when modal is closed
            // Don't reset hasSavedEdits flag to preserve saved data across modal reopens
        }

        // View daily attendance for a section
        function viewDailyAttendance(gradeLevel, sectionName, sectionId) {
            const modal = document.getElementById('dailyAttendanceModal');
            const title = document.getElementById('dailyAttendanceTitle');
            const dateInput = document.getElementById('attendanceDate');
            
            const gradeDisplay = /^\d+$/.test(gradeLevel) ? `Grade ${gradeLevel}` : gradeLevel;
            title.textContent = `${gradeDisplay} - ${sectionName} - Full Subject Attendance`;
            
            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            dateInput.value = today;
            
            // Set current month and year for monthly tab
            const currentDate = new Date();
            const monthYearPicker = document.getElementById('monthYearPicker');
            const currentMonthYear = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}`;
            monthYearPicker.value = currentMonthYear;
            
            // Store current section info
            currentSectionId = sectionId;
            
            // Populate subject filter dropdown
            populateSubjectFilter(gradeLevel, sectionName);
            
            // Show modal
            modal.classList.add('show');
            
            // Reset to daily tab and load daily attendance data
            switchTab('daily');
            
            // Force style refresh
            setTimeout(() => {
                const tabs = modal.querySelectorAll('.tab-btn');
                tabs.forEach(tab => {
                    tab.style.display = 'none';
                    tab.offsetHeight; // Trigger reflow
                    tab.style.display = 'flex';
                });
            }, 100);
        }

        // Populate subject filter dropdown
        function populateSubjectFilter(gradeLevel, sectionName) {
            const subjectFilter = document.getElementById('subjectFilter');
            
            // Find the section data
            const section = sectionsData.find(s => s.section === sectionName && s.grade_level === gradeLevel);
            if (!section || !section.subjects) {
                return;
            }
            
            // Clear existing options except the first one
            subjectFilter.innerHTML = '<option value="all">All Subjects (Combined)</option>';
            
            // Add subject options
            section.subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject.id;
                option.textContent = subject.name;
                subjectFilter.appendChild(option);
            });
        }

        // Load daily attendance data
        function loadDailyAttendance() {
            if (!currentSectionId) return;
            
            const dateInput = document.getElementById('attendanceDate');
            const selectedDate = dateInput.value;
            
            if (!selectedDate) {
                alert('Please select a date');
                return;
            }
            
            const tableBody = document.querySelector('#dailyAttendanceTable tbody');
            const tableHead = document.querySelector('#dailyAttendanceTable thead tr');
            
            // Show loading state
            tableBody.innerHTML = '<tr><td colspan="100%" class="loading">Loading daily attendance data...</td></tr>';
            
            // Fetch daily attendance data
            fetch(`functions/get_subject_attendance_data.php?action=get_daily_attendance&section_id=${currentSectionId}&date=${selectedDate}`)
                .then(response => response.json())
                .then(data => {
                    currentDailyAttendanceData = data;
                    
                    if (!data.students || data.students.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="100%" class="no-data">No students found for this section.</td></tr>';
                        return;
                    }
                    
                    // Create subject columns header
                    const subjectColumns = data.subjects.map(subject => 
                        `<th>${subject.name}</th>`
                    ).join('');
                    
                    tableHead.innerHTML = `<th>Student Name</th>${subjectColumns}`;
                    
                    // Create student rows with attendance for each subject
                    const tableHTML = data.students.map(student => {
                        // Format name as "Last Name, First Name Middle Name"
                        let formattedName = student.name;
                        if (student.last_name && student.first_name) {
                            const middleName = student.middle_name || student.middle_initial || '';
                            const middlePart = middleName ? ` ${middleName}` : '';
                            formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                        }
                        
                        const subjectCells = data.subjects.map(subject => {
                            const attendance = student.attendance[subject.id] || { status: 'absent' };
                            const statusClass = attendance.status || 'absent';
                            return `<td class="attendance-cell ${statusClass}">${getStatusLetter(attendance.status)}</td>`;
                        }).join('');
                        
                        return `
                            <tr>
                                <td class="student-name">${formattedName}</td>
                                ${subjectCells}
                            </tr>
                        `;
                    }).join('');
                    
                    tableBody.innerHTML = tableHTML;
                })
                .catch(error => {
                    console.error('Error loading daily attendance data:', error);
                    tableBody.innerHTML = '<tr><td colspan="100%" class="error">Error loading daily attendance data. Please try again.</td></tr>';
                });
        }

        // Switch between daily and monthly tabs
        function switchTab(tab) {
            const dailyTab = document.getElementById('dailyTab');
            const monthlyTab = document.getElementById('monthlyTab');
            const dailyControls = document.getElementById('dailyControls');
            const monthlyControls = document.getElementById('monthlyControls');
            const dailyTableContainer = document.getElementById('dailyTableContainer');
            const monthlyTableContainer = document.getElementById('monthlyTableContainer');
            const dailyLegend = document.getElementById('dailyLegend');
            const monthlyLegend = document.getElementById('monthlyLegend');
            const modal = document.getElementById('dailyAttendanceModal');
            
            if (tab === 'daily') {
                dailyTab.classList.add('active');
                monthlyTab.classList.remove('active');
                dailyControls.style.display = 'flex';
                monthlyControls.style.display = 'none';
                dailyLegend.style.display = 'flex';
                monthlyLegend.style.display = 'none';
                dailyTableContainer.style.display = 'block';
                monthlyTableContainer.style.display = 'none';
                modal.classList.remove('monthly-view');
                currentTab = 'daily';
                
                // Load daily attendance if not already loaded
                if (!currentDailyAttendanceData) {
                    loadDailyAttendance();
                }
            } else {
                dailyTab.classList.remove('active');
                monthlyTab.classList.add('active');
                dailyControls.style.display = 'none';
                monthlyControls.style.display = 'flex';
                dailyLegend.style.display = 'none';
                monthlyLegend.style.display = 'flex';
                dailyTableContainer.style.display = 'none';
                monthlyTableContainer.style.display = 'block';
                modal.classList.add('monthly-view');
                currentTab = 'monthly';
                
                // Load monthly attendance
                loadMonthlyAttendance();
            }
        }

        // Load monthly attendance data
        function loadMonthlyAttendance() {
            if (!currentSectionId) return;
            
            const monthYearPicker = document.getElementById('monthYearPicker');
            const subjectFilter = document.getElementById('subjectFilter');
            const selectedMonthYear = monthYearPicker.value;
            const selectedSubject = subjectFilter.value;
            
            if (!selectedMonthYear) {
                alert('Please select a month and year');
                return;
            }
            
            const [selectedYear, selectedMonth] = selectedMonthYear.split('-');
            
            const tableBody = document.querySelector('#monthlyAttendanceTable tbody');
            const tableHead = document.querySelector('#monthlyAttendanceTable thead tr');
            
            // Show loading state
            tableBody.innerHTML = '<tr><td colspan="100%" class="loading">Loading monthly attendance data...</td></tr>';
            
            // Build URL with subject filter if specific subject is selected
            let url = `functions/get_subject_attendance_data.php?action=get_monthly_attendance&section_id=${currentSectionId}&month=${selectedMonth}&year=${selectedYear}`;
            if (selectedSubject !== 'all') {
                url += `&subject_id=${selectedSubject}`;
            }
            
            // Fetch monthly attendance data
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    currentMonthlyAttendanceData = data;
                    
                    if (!data.students || data.students.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="100%" class="no-data">No students found for this section.</td></tr>';
                        return;
                    }
                    
                    if (!data.dates || data.dates.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="100%" class="no-data">No attendance data found for this month.</td></tr>';
                        return;
                    }
                    
                    // Create date columns header with dynamic days based on month
                    const daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();
                    const dateColumns = [];
                    
                    for (let day = 1; day <= daysInMonth; day++) {
                        const dayString = String(day).padStart(2, '0');
                        const fullDate = `${selectedYear}-${selectedMonth}-${dayString}`;
                        const dateObj = new Date(fullDate);
                        const dayOfWeek = dateObj.toLocaleDateString('en-US', { weekday: 'short' });
                        const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6; // Sunday = 0, Saturday = 6
                        const weekendClass = isWeekend ? ' weekend' : '';
                        dateColumns.push(`<th class="${weekendClass}" title="${dayOfWeek}, ${fullDate}">${day}</th>`);
                    }
                    
                    tableHead.innerHTML = `<th>Student Name</th>${dateColumns.join('')}`;
                    
                    // Create student rows with attendance for each date
                    const tableHTML = data.students.map(student => {
                        // Format name as "Last Name, First Name Middle Name"
                        let formattedName = student.name;
                        if (student.last_name && student.first_name) {
                            const middleName = student.middle_name || student.middle_initial || '';
                            const middlePart = middleName ? ` ${middleName}` : '';
                            formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                        }
                        
                        // Create cells for each day of the month
                        const dateCells = [];
                        for (let day = 1; day <= daysInMonth; day++) {
                            const dayString = String(day).padStart(2, '0');
                            const fullDate = `${selectedYear}-${selectedMonth}-${dayString}`;
                            const dateObj = new Date(fullDate);
                            const attendance = student.attendance[fullDate] || { status: 'absent' };
                            const statusClass = attendance.status || 'absent';
                            const statusDisplay = getStatusLetter(attendance.status);
                            const dayOfWeek = dateObj.toLocaleDateString('en-US', { weekday: 'short' });
                            const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6;
                            const weekendClass = isWeekend ? ' weekend' : '';
                            
                            dateCells.push(`<td class="attendance-cell ${statusClass}${weekendClass}" title="${dayOfWeek}, ${fullDate}: ${attendance.status || 'Absent'}">${statusDisplay}</td>`);
                        }
                        
                        return `
                            <tr>
                                <td class="student-name">${formattedName}</td>
                                ${dateCells.join('')}
                            </tr>
                        `;
                    }).join('');
                    
                    tableBody.innerHTML = tableHTML;
                    
                    // Force apply weekend styling after table is loaded
                    setTimeout(() => {
                        const weekendCells = document.querySelectorAll('#dailyAttendanceModal .monthly-attendance-table td.weekend');
                        weekendCells.forEach(cell => {
                            if (!cell.classList.contains('attendance-cell')) {
                                cell.style.backgroundColor = '#dbeafe';
                                cell.style.color = '#1e40af';
                                cell.style.borderColor = '#93c5fd';
                            }
                        });
                    }, 100);
                })
                .catch(error => {
                    console.error('Error loading monthly attendance data:', error);
                    tableBody.innerHTML = '<tr><td colspan="100%" class="error">Error loading monthly attendance data. Please try again.</td></tr>';
                });
        }

        // Close daily attendance modal
        function closeDailyAttendanceModal() {
            const modal = document.getElementById('dailyAttendanceModal');
            modal.classList.remove('show');
            modal.classList.remove('monthly-view');
            currentDailyAttendanceData = null;
            currentMonthlyAttendanceData = null;
            currentSectionId = null;
            currentTab = 'daily';
        }

        // Export daily attendance data
        function exportDailyAttendance() {
            if (!currentDailyAttendanceData || !currentDailyAttendanceData.students) return;
            
            const dateInput = document.getElementById('attendanceDate');
            const selectedDate = dateInput.value;
            
            // Get section info from modal title
            const title = document.getElementById('dailyAttendanceTitle').textContent;
            const titleParts = title.split(' - ');
            const gradeLevel = titleParts[0];
            const section = titleParts[1];
            
            // Create CSV content
            const csvContent = [
                `"Grade Level","${gradeLevel}"`,
                `"Section","${section}"`,
                `"Date","${selectedDate}"`,
                `"Export Date","${new Date().toLocaleDateString()}"`,
                '', // Empty line separator
                `"Student Name",${currentDailyAttendanceData.subjects.map(s => `"${s.name}"`).join(',')}`,
                ...currentDailyAttendanceData.students.map(student => {
                    // Format name as "Last Name, First Name Middle Name"
                    let formattedName = student.name;
                    if (student.last_name && student.first_name) {
                        const middleName = student.middle_name || student.middle_initial || '';
                        const middlePart = middleName ? ` ${middleName}` : '';
                        formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                    }
                    
                    const subjectData = currentDailyAttendanceData.subjects.map(subject => {
                        const attendance = student.attendance[subject.id] || { status: 'absent' };
                        return getStatusLetter(attendance.status);
                    }).join(',');
                    
                    return `"${formattedName}",${subjectData}`;
                })
            ].join('\n');
            
            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            
            // Get current date for filename
            const now = new Date();
            const dateStr = now.toISOString().split('T')[0];
            const filename = `${title.replace(/\s+/g, '_')}_${selectedDate}_${dateStr}.csv`;
            
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Export monthly attendance data
        function exportMonthlyAttendance() {
            if (!currentMonthlyAttendanceData || !currentMonthlyAttendanceData.students) return;
            
            const monthYearPicker = document.getElementById('monthYearPicker');
            const subjectFilter = document.getElementById('subjectFilter');
            const selectedMonthYear = monthYearPicker.value;
            const selectedSubject = subjectFilter.value;
            
            if (!selectedMonthYear) {
                alert('Please select a month and year');
                return;
            }
            
            const [selectedYear, selectedMonth] = selectedMonthYear.split('-');
            
            // Get section info from modal title
            const title = document.getElementById('dailyAttendanceTitle').textContent;
            const titleParts = title.split(' - ');
            const gradeLevel = titleParts[0];
            const section = titleParts[1];
            
            // Get subject name for filename
            const subjectName = selectedSubject === 'all' ? 'All_Subjects' : subjectFilter.options[subjectFilter.selectedIndex].text.replace(/\s+/g, '_');
            
            // Create CSV content
            const csvContent = [
                `"Grade Level","${gradeLevel}"`,
                `"Section","${section}"`,
                `"Subject Filter","${selectedSubject === 'all' ? 'All Subjects (Combined)' : subjectFilter.options[subjectFilter.selectedIndex].text}"`,
                `"Month","${getMonthName(selectedMonth)}"`,
                `"Year","${selectedYear}"`,
                `"Export Date","${new Date().toLocaleDateString()}"`,
                '', // Empty line separator
                // Create dynamic header for all days of the month
                (() => {
                    const daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();
                    const dayHeaders = [];
                    for (let day = 1; day <= daysInMonth; day++) {
                        dayHeaders.push(`${day}`);
                    }
                    return `"Student Name",${dayHeaders.join(',')}`;
                })(),
                ...currentMonthlyAttendanceData.students.map(student => {
                    // Format name as "Last Name, First Name Middle Name"
                    let formattedName = student.name;
                    if (student.last_name && student.first_name) {
                        const middleName = student.middle_name || student.middle_initial || '';
                        const middlePart = middleName ? ` ${middleName}` : '';
                        formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                    }
                    
                    // Create data for all days of the month
                    const daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();
                    const dateData = [];
                    for (let day = 1; day <= daysInMonth; day++) {
                        const dayString = String(day).padStart(2, '0');
                        const fullDate = `${selectedYear}-${selectedMonth}-${dayString}`;
                        const attendance = student.attendance[fullDate] || { status: 'absent' };
                        dateData.push(getStatusLetter(attendance.status));
                    }
                    const dateDataString = dateData.join(',');
                    
                    return `"${formattedName}",${dateDataString}`;
                })
            ].join('\n');
            
            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            
            // Get current date for filename
            const now = new Date();
            const dateStr = now.toISOString().split('T')[0];
            const monthName = getMonthName(selectedMonth);
            const filename = `${gradeLevel.replace(/\s+/g, '_')}_${section}_${subjectName}_Monthly_Attendance_${monthName}_${selectedYear}_${dateStr}.csv`;
            
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Helper function to get month name
        function getMonthName(monthNumber) {
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            return months[parseInt(monthNumber) - 1];
        }

        // Search functionality
        function setupSearch() {
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const cards = document.querySelectorAll('.section-card');
                const gradeContainers = document.querySelectorAll('.grade-level-container');
                
                let visibleSections = 0;
                
                cards.forEach(card => {
                    const title = card.querySelector('.section-title').textContent.toLowerCase();
                    const adviser = card.querySelector('.adviser-info span').textContent.toLowerCase();
                    
                    if (title.includes(searchTerm) || adviser.includes(searchTerm)) {
                        card.style.display = 'block';
                        visibleSections++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show/hide grade level containers based on visible sections
                gradeContainers.forEach(container => {
                    const sectionsGrid = container.querySelector('.sections-grid');
                    const visibleCards = sectionsGrid.querySelectorAll('.section-card[style*="block"], .section-card:not([style*="none"])');
                    
                    if (visibleCards.length > 0) {
                        container.style.display = 'block';
                        sectionsGrid.style.display = 'grid';
                    } else {
                        container.style.display = 'none';
                    }
                });
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const subjectModal = document.getElementById('subjectAttendanceModal');
            const dailyModal = document.getElementById('dailyAttendanceModal');
            
            if (event.target === subjectModal) {
                closeSubjectAttendanceModal();
            } else if (event.target === dailyModal) {
                closeDailyAttendanceModal();
            }
        }

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSubjectAttendanceModal();
                closeDailyAttendanceModal();
            }
        });

        // Filter attendance by status
        function filterAttendance(status) {
            if (!currentAttendanceData) return;
            
            const rows = document.querySelectorAll('.attendance-row');
            const summaryCards = document.querySelectorAll('.summary-card');
            
            // Remove active class from all summary cards
            summaryCards.forEach(card => card.classList.remove('active'));
            
            // Add active class to clicked card
            event.target.closest('.summary-card').classList.add('active');
            
            // Update current filter
            currentFilter = status;
            
            // Filter table rows
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Export attendance data
        function exportAttendance() {
            if (!currentAttendanceData || !currentAttendanceData.students) return;
            
            // Get filtered data based on current filter
            let dataToExport = currentAttendanceData.students;
            if (currentFilter !== 'all') {
                dataToExport = currentAttendanceData.students.filter(student => student.status === currentFilter);
            }
            
            // Get section and subject info from modal title
            const title = document.getElementById('subjectAttendanceTitle').textContent;
            const titleParts = title.split(' - ');
            const gradeLevel = titleParts[0];
            const section = titleParts[1];
            const subject = titleParts[2].replace(' Attendance', '');
            
            // Create CSV content with section and subject info at the top
            const csvContent = [
                `"Grade Level","${gradeLevel}"`,
                `"Section","${section}"`,
                `"Subject","${subject}"`,
                `"Export Date","${new Date().toLocaleDateString()}"`,
                `"Filter Applied","${currentFilter === 'all' ? 'All Students' : currentFilter.charAt(0).toUpperCase() + currentFilter.slice(1)}"`,
                '', // Empty line separator
                '#,Student Name,Date,Time,Status,Description',
                ...dataToExport.map((student, index) => {
                    // Format name as "Last Name, First Name Middle Name"
                    let formattedName = student.name;
                    if (student.last_name && student.first_name) {
                        const middleName = student.middle_name || student.middle_initial || '';
                        const middlePart = middleName ? ` ${middleName}` : '';
                        formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                    }
                    
                    // Get description from student data or editDescriptions
                    const originalIndex = currentAttendanceData.students.findIndex(s => s.id === student.id);
                    const description = editDescriptions[originalIndex] || student.description || '';
                    
                    return [
                        index + 1,
                        `"${formattedName}"`,
                        student.date,
                        student.time,
                        getStatusLetter(student.status),
                        `"${description}"`
                    ].join(',');
                })
            ].join('\n');
            
            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            
            // Get current date for filename
            const now = new Date();
            const dateStr = now.toISOString().split('T')[0];
            const filename = `${title.replace(/\s+/g, '_')}_${dateStr}.csv`;
            
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Auto-refresh attendance data when RFID is scanned
        let refreshInterval = null;
        
        function startAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            
            // Refresh every 2 seconds when attendance modal is open
            refreshInterval = setInterval(function() {
                if (currentAttendanceData && document.getElementById('subjectAttendanceModal').classList.contains('show')) {
                    refreshAttendanceData();
                }
            }, 2000);
        }
        
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
        }
        
        function refreshAttendanceData() {
            if (!currentAttendanceData || !currentAttendanceData.students) return;
            
            // Don't auto-refresh if there are saved edits to preserve user changes
            if (hasSavedEdits || Object.keys(editDescriptions).length > 0) {
                console.log('Skipping auto-refresh to preserve saved edits');
                return;
            }
            
            // Get current subject and section info from modal title
            const title = document.getElementById('subjectAttendanceTitle').textContent;
            const titleParts = title.split(' - ');
            const gradeLevel = titleParts[0];
            const section = titleParts[1];
            const subject = titleParts[2].replace(' Attendance', '');
            
            // Find section ID
            const sectionData = sectionsData.find(s => s.section === section && s.grade_level === gradeLevel);
            if (!sectionData) return;
            
            // Find subject ID
            const subjectData = sectionData.subjects.find(s => s.name === subject);
            if (!subjectData) return;
            
            // Fetch updated attendance data
            fetch(`functions/get_subject_attendance_data.php?action=get_attendance&subject_id=${subjectData.id}&section_id=${sectionData.id}`)
                .then(response => response.json())
                .then(data => {
                    // Check if data has changed (only for non-edited students)
                    const hasChanges = JSON.stringify(data) !== JSON.stringify(currentAttendanceData.students);
                    
                    if (hasChanges) {
                        // Preserve manually edited data
                        data.forEach((newStudent, index) => {
                            if (editDescriptions[index]) {
                                // Keep the saved edit data
                                newStudent.description = editDescriptions[index];
                                newStudent.time = currentAttendanceData.students[index].time;
                                newStudent.status = currentAttendanceData.students[index].status;
                            }
                        });
                        
                        currentAttendanceData = { students: data };
                        updateAttendanceTable();
                        updateSummaryCounts();
                        console.log('Attendance data refreshed from RFID (preserving edits)');
                    }
                })
                .catch(error => {
                    console.error('Error refreshing attendance data:', error);
                });
        }
        
        function updateAttendanceTable() {
            if (!currentAttendanceData || !currentAttendanceData.students) return;
            
            const tableBody = document.querySelector('#attendanceTable tbody');
            const summaryContainer = document.getElementById('attendanceSummary');
            
            if (currentAttendanceData.students.length === 0) {
                summaryContainer.innerHTML = '<div class="no-data">No students registered in this section.</div>';
                tableBody.innerHTML = '';
                return;
            }
            
            const presentCount = currentAttendanceData.students.filter(s => s.status === 'present').length;
            const lateCount = currentAttendanceData.students.filter(s => s.status === 'late').length;
            const absentCount = currentAttendanceData.students.filter(s => s.status === 'absent').length;
            const totalCount = currentAttendanceData.students.length;
            
            const summaryHTML = `
                <div class="summary-card" onclick="filterAttendance('present')">
                    <div class="summary-icon present">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-number">${presentCount}</div>
                        <div class="summary-label">Present</div>
                    </div>
                </div>
                <div class="summary-card" onclick="filterAttendance('late')">
                    <div class="summary-icon late">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-number">${lateCount}</div>
                        <div class="summary-label">Late</div>
                    </div>
                </div>
                <div class="summary-card" onclick="filterAttendance('absent')">
                    <div class="summary-icon absent">
                        <i class="fa-solid fa-times"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-number">${absentCount}</div>
                        <div class="summary-label">Absent</div>
                    </div>
                </div>
                <div class="summary-card" onclick="filterAttendance('all')">
                    <div class="summary-icon total">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-number">${totalCount}</div>
                        <div class="summary-label">Total</div>
                    </div>
                </div>
                <button class="export-btn" onclick="exportAttendance()">
                    <i class="fa-solid fa-download"></i>
                    Export
                </button>
            `;
            
            summaryContainer.innerHTML = summaryHTML;
            
            // Update table
            tableBody.innerHTML = currentAttendanceData.students.map((student, index) => {
                // Format name as "Last Name, First Name Middle Name"
                let formattedName = student.name;
                if (student.last_name && student.first_name) {
                    const middleName = student.middle_name || student.middle_initial || '';
                    const middlePart = middleName ? ` ${middleName}` : '';
                    formattedName = `${student.last_name}, ${student.first_name}${middlePart}`;
                }
                
                return `
                <tr class="attendance-row" data-status="${student.status}">
                    <td>${index + 1}</td>
                    <td>${formattedName}</td>
                    <td>${student.date}</td>
                    <td>${student.time}</td>
                    <td>
                        <select class="status-select" onchange="updateStudentStatus(${student.id}, this.value)" id="status-${index}" name="status[]">
                            <option value="present" ${student.status === 'present' ? 'selected' : ''}>Present</option>
                            <option value="late" ${student.status === 'late' ? 'selected' : ''}>Late</option>
                            <option value="absent" ${student.status === 'absent' ? 'selected' : ''}>Absent</option>
                        </select>
                    </td>
                </tr>
            `;
            }).join('');
        }

        // Load exit logs
        function loadExitLogs(showLoading = true) {
            const loadingDiv = document.getElementById('exitLogLoading');
            const table = document.getElementById('exitLogTable');
            const emptyDiv = document.getElementById('exitLogEmpty');
            const tableBody = document.getElementById('exitLogTableBody');
            
            // Show loading state only if requested (not for auto-refresh)
            if (showLoading) {
                loadingDiv.style.display = 'block';
                table.style.display = 'none';
                emptyDiv.style.display = 'none';
            }
            
            fetch('functions/get_exit_log_data.php?action=get_exit_logs')
                .then(response => response.json())
                .then(data => {
                    loadingDiv.style.display = 'none';
                    
                    if (data.success && data.data.length > 0) {
                        // Show table with data
                        table.style.display = 'table';
                        emptyDiv.style.display = 'none';
                        
                        const tableHTML = data.data.map((exit, index) => `
                            <tr class="exit-log-row">
                                <td>${index + 1}</td>
                                <td class="student-name">${exit.name}</td>
                                <td class="grade-level">${exit.grade}</td>
                                <td class="section-name">${exit.section}</td>
                                <td class="exit-time">${exit.time}</td>
                                <td class="exit-status">
                                    <span class="status-badge exit">${exit.type}</span>
                                </td>
                            </tr>
                        `).join('');
                        
                        tableBody.innerHTML = tableHTML;
                    } else {
                        // Show empty state
                        table.style.display = 'none';
                        emptyDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading exit logs:', error);
                    loadingDiv.style.display = 'none';
                    table.style.display = 'none';
                    emptyDiv.style.display = 'block';
                    
                    // Update empty state with error message
                    const emptyState = emptyDiv.querySelector('.empty-state');
                    emptyState.innerHTML = `
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <h3>Error loading exit logs</h3>
                        <p>Unable to load exit log data. Please try refreshing the page.</p>
                    `;
                });
        }

        // Auto-refresh variables
        let pageRefreshInterval = null;
        let sectionsRefreshInterval = null;
        let isPageVisible = true;
        let lastSectionsData = null;

        // Start auto-refresh for main page data
        function startPageAutoRefresh() {
            if (pageRefreshInterval) {
                clearInterval(pageRefreshInterval);
            }
            
            // Refresh exit logs every 3 seconds (frequent updates needed)
            pageRefreshInterval = setInterval(function() {
                if (isPageVisible) {
                    console.log('Auto-refreshing exit logs...');
                    loadExitLogs(false); // Silent refresh for exit logs
                }
            }, 3000);
            
            // Refresh sections data every 30 seconds (less frequent, sections don't change often)
            if (sectionsRefreshInterval) {
                clearInterval(sectionsRefreshInterval);
            }
            sectionsRefreshInterval = setInterval(function() {
                if (isPageVisible) {
                    console.log('Auto-refreshing sections data...');
                    loadSectionsSilently(); // Silent refresh for sections
                }
            }, 30000);
        }


        // Silent sections loading (no visual disruption)
        function loadSectionsSilently() {
            // Use the same endpoint and schema as the initial load
            fetch('functions/get_subject_attendance_data.php?action=get_sections')
                .then(response => response.json())
                .then(data => {
                    // Validate payload shape before using it
                    const isValidArray = Array.isArray(data) && data.every(s => typeof s === 'object' && 'grade_level' in s && 'section' in s);
                    if (!isValidArray) {
                        console.warn('Skipping silent sections update due to invalid payload shape');
                        return; // Do not clobber existing UI with bad data
                    }

                    // Only update if data has actually changed
                    if (JSON.stringify(data) !== JSON.stringify(lastSectionsData)) {
                        console.log('Sections data changed, updating silently...');
                        lastSectionsData = data;
                        sectionsData = data;
                        displaySections(data);
                    } else {
                        // No change
                    }
                })
                .catch(error => {
                    console.error('Error loading sections silently:', error);
                });
        }

        // Stop auto-refresh
        function stopPageAutoRefresh() {
            if (pageRefreshInterval) {
                clearInterval(pageRefreshInterval);
                pageRefreshInterval = null;
            }
            if (sectionsRefreshInterval) {
                clearInterval(sectionsRefreshInterval);
                sectionsRefreshInterval = null;
            }
        }

        // Handle page visibility changes
        document.addEventListener('visibilitychange', function() {
            isPageVisible = !document.hidden;
            if (isPageVisible) {
                console.log('Page became visible, starting auto-refresh');
                startPageAutoRefresh();
                // Immediately refresh only exit logs when page becomes visible
                loadExitLogs(false); // Silent refresh
                // Sections will refresh automatically every 30 seconds
            } else {
                console.log('Page became hidden, stopping auto-refresh');
                stopPageAutoRefresh();
            }
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadSections();
            loadExitLogs(); // Load exit logs on page load
            setupSearch();
            startPageAutoRefresh(); // Start auto-refresh
        });
    </script>

    <?php include 'includes/scripts.php'; ?>
</body>
</html>
