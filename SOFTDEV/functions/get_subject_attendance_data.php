<?php
// Check if we're being called from the root directory or from within functions
if (file_exists('../db/db_connect.php')) {
    require_once '../db/db_connect.php';
} else {
    require_once 'db/db_connect.php';
}

/**
 * Get all sections with their subjects and teachers
 * @return array
 */
function getSectionsWithSubjects() {
    global $conn;
    
    try {
        $sql = "SELECT 
                    s.id as section_id,
                    s.grade_level,
                    s.section,
                    t.first_name as adviser_first_name,
                    t.last_name as adviser_last_name,
                    t.gender as adviser_gender,
                    GROUP_CONCAT(
                        CONCAT(
                            sub.id, '|',
                            sub.subject_name, '|',
                            COALESCE(sub.subject_code, ''), '|',
                            COALESCE(t2.first_name, ''), '|',
                            COALESCE(t2.last_name, ''), '|',
                            COALESCE(t2.gender, '')
                        ) 
                        ORDER BY sub.subject_name 
                        SEPARATOR '||'
                    ) as subjects_data
                FROM section s
                LEFT JOIN teachers t ON t.advisory_section_id = s.id
                LEFT JOIN subjects sub ON sub.section_id = s.id
                LEFT JOIN teachers t2 ON t2.id = sub.teacher_id
                GROUP BY s.id, s.grade_level, s.section
                ORDER BY 
                    CASE 
                        WHEN s.grade_level = 'Nursery' THEN 1
                        WHEN s.grade_level = 'Kinder 1' THEN 2
                        WHEN s.grade_level = 'Kinder 2' THEN 3
                        ELSE CAST(s.grade_level AS UNSIGNED) + 3
                    END,
                    s.section";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            error_log("Query failed: " . $conn->error);
            // Return basic sections without subjects if query fails
            return getBasicSections();
        }
        
        $sections = [];
        
        while ($row = $result->fetch_assoc()) {
            // Format adviser name with gender prefix
            $adviserFirstName = $row['adviser_first_name'] ?? '';
            $adviserLastName = $row['adviser_last_name'] ?? '';
            $adviserGender = $row['adviser_gender'] ?? 'Male';
            $adviserName = '';
            if (!empty($adviserFirstName) && !empty($adviserLastName)) {
                $prefix = ($adviserGender === 'Female') ? 'Ms.' : 'Mr.';
                $adviserName = $prefix . ' ' . trim($adviserFirstName . ' ' . $adviserLastName);
            } else {
                $adviserName = 'No Adviser yet';
            }
            
            $section = [
                'id' => $row['section_id'],
                'grade_level' => $row['grade_level'],
                'section' => $row['section'],
                'adviser_name' => $adviserName,
                'subjects' => []
            ];
            
            // Parse subjects data from GROUP_CONCAT
            if (!empty($row['subjects_data'])) {
                $subjectsData = explode('||', $row['subjects_data']);
                foreach ($subjectsData as $subjectData) {
                    if (!empty($subjectData)) {
                        $parts = explode('|', $subjectData);
                        
                        // Handle malformed entries where first part might be empty due to consecutive separators
                        if (empty($parts[0]) && count($parts) > 1) {
                            // Remove the first empty element and shift the array
                            array_shift($parts);
                        }
                        
                        // Skip entries that still don't have valid subject ID and name
                        if (count($parts) < 2 || empty($parts[0]) || empty($parts[1])) {
                            continue;
                        }
                        
                        $teacherName = '';
                        
                        // Ensure we have at least 6 parts by padding with empty strings
                        while (count($parts) < 6) {
                            $parts[] = '';
                        }
                        
                        // Handle teacher name with gender prefix
                        // Check if we have valid teacher data (non-empty first and last names)
                        if (!empty($parts[3]) && !empty($parts[4])) {
                            $gender = !empty($parts[5]) ? $parts[5] : 'Male';
                            $prefix = ($gender === 'Female') ? 'Ms.' : 'Mr.';
                            $teacherName = $prefix . ' ' . trim($parts[3] . ' ' . $parts[4]);
                        } else {
                            $teacherName = 'No teacher assigned';
                        }
                        
                        // Use actual subject_code from database, fallback to generated if empty
                        $subjectCode = !empty($parts[2]) ? $parts[2] : generateSubjectCode($parts[1], $row['grade_level']);
                        
                        $section['subjects'][] = [
                            'id' => $parts[0],
                            'name' => $parts[1],
                            'code' => $subjectCode,
                            'teacher' => $teacherName
                        ];
                    }
                }
            }
            
            $sections[] = $section;
        }
        
        return $sections;
        
    } catch (Exception $e) {
        error_log("Error in getSectionsWithSubjects: " . $e->getMessage());
        return getBasicSections();
    }
}

/**
 * Get basic sections without subjects (fallback)
 * @return array
 */
function getBasicSections() {
    global $conn;
    
    try {
        $sql = "SELECT id, grade_level, section FROM section ORDER BY 
                    CASE 
                        WHEN grade_level = 'Nursery' THEN 1
                        WHEN grade_level = 'Kinder 1' THEN 2
                        WHEN grade_level = 'Kinder 2' THEN 3
                        ELSE CAST(grade_level AS UNSIGNED) + 3
                    END,
                    section";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            return [];
        }
        
        $sections = [];
        while ($row = $result->fetch_assoc()) {
            $sections[] = [
                'id' => $row['id'],
                'grade_level' => $row['grade_level'],
                'section' => $row['section'],
                'adviser_name' => 'No Adviser yet',
                'subjects' => []
            ];
        }
        
        return $sections;
        
    } catch (Exception $e) {
        error_log("Error in getBasicSections: " . $e->getMessage());
        return [];
    }
}

/**
 * Get students for a specific section
 * @param int $sectionId
 * @return array
 */
function getStudentsBySection($sectionId) {
    global $conn;
    
    try {
        // First check if section exists
        $checkSql = "SELECT id, grade_level, section FROM section WHERE id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $sectionId);
        $checkStmt->execute();
        $sectionResult = $checkStmt->get_result();
        
        if ($sectionResult->num_rows === 0) {
            error_log("Section ID $sectionId not found");
            return [];
        }
        
        $sectionData = $sectionResult->fetch_assoc();
        $checkStmt->close();
        
        $sql = "SELECT 
                    st.student_id as id,
                    st.student_id,
                    CONCAT(st.last_name, ', ', st.first_name, 
                           CASE 
                               WHEN st.middle_initial IS NOT NULL AND st.middle_initial != '' 
                               THEN CONCAT(' ', st.middle_initial)
                               ELSE ''
                           END) as name,
                    st.first_name,
                    st.last_name,
                    st.middle_initial,
                    st.grade_level,
                    s.section
                FROM students st
                LEFT JOIN section s ON s.id = st.section_id
                WHERE st.section_id = ?
                ORDER BY st.last_name, st.first_name";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = [
                'id' => $row['id'],
                'student_id' => $row['student_id'],
                'name' => $row['name'],
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'middle_initial' => $row['middle_initial'] ?? '',
                'grade_level' => $row['grade_level'],
                'section' => $row['section']
            ];
        }
        
        $stmt->close();
        
        // Log for debugging
        error_log("Found " . count($students) . " students in section $sectionId");
        
        return $students;
        
    } catch (Exception $e) {
        error_log("Error in getStudentsBySection: " . $e->getMessage());
        return [];
    }
}

/**
 * Generate subject code based on subject name and grade level
 * @param string $subjectName
 * @param string $gradeLevel
 * @return string
 */
function generateSubjectCode($subjectName, $gradeLevel) {
    $subjectCodes = [
        'English' => 'ENG',
        'Mathematics' => 'MATH',
        'Science' => 'SCI',
        'Filipino' => 'FIL',
        'Social Studies' => 'SOC',
        'Physical Education' => 'PE',
        'Music' => 'MUS',
        'Art' => 'ART',
        'Values Education' => 'VAL'
    ];
    
    $code = $subjectCodes[$subjectName] ?? substr(strtoupper($subjectName), 0, 3);
    $gradeNum = is_numeric($gradeLevel) ? $gradeLevel : '0';
    
    return $code . $gradeNum . '01';
}

/**
 * Get attendance data for a specific subject (check RFID logs for present/late status)
 * @param int $subjectId
 * @param int $sectionId
 * @return array
 */
function getSubjectAttendanceData($subjectId, $sectionId) {
    try {
        error_log("Getting attendance data for subject $subjectId, section $sectionId");
        
        $students = getStudentsBySection($sectionId);
        $today = date('Y-m-d');
        
        error_log("Found " . count($students) . " students for section $sectionId");
        
        // Get section and subject information
        $sectionInfo = getSectionInfo($sectionId);
        $subjectInfo = getSubjectInfo($subjectId);
        
        if (!$sectionInfo || !$subjectInfo) {
            error_log("Section or subject info not found");
            return [];
        }
        
        // Read attendance records from both RFID and CSV files
        $rfidRecords = readAttendanceFromRFID($subjectInfo['subject_name'], $today);
        $csvRecords = readAttendanceFromCSV($sectionInfo['section'], $subjectInfo['subject_name'], $today);
        
        // Merge RFID and CSV records (RFID takes priority)
        $attendanceRecords = array_merge($csvRecords, $rfidRecords);
        
        // Create attendance data for all students
        $attendanceData = [];
        foreach ($students as $student) {
            $status = 'absent'; // Default to absent
            $time = '-';
            $description = '';
            
            // Check if student has attendance record
            $rfidTag = getStudentRFIDTag($student['id']);
            if ($rfidTag && isset($attendanceRecords[$rfidTag])) {
                $record = $attendanceRecords[$rfidTag];
                $time = $record['time'];
                $description = $record['description'] ?? '';
                
                // Check if this is a manual edit with explicit status
                if (isset($record['manual_status']) && $record['manual_status'] !== null) {
                    // Use the manually set status
                    $status = $record['manual_status'];
                } else {
                    // Use normal status determination for actual RFID scans
                    $status = determineAttendanceStatus($record['time']);
                }
            }
            
            $attendanceData[] = [
                'id' => $student['id'],
                'name' => $student['name'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'middle_initial' => $student['middle_initial'],
                'rfid_tag' => $rfidTag,
                'status' => $status,
                'date' => $today,
                'time' => $time,
                'description' => $description
            ];
        }
        
        error_log("Returning " . count($attendanceData) . " attendance records");
        return $attendanceData;
    } catch (Exception $e) {
        error_log("Error in getSubjectAttendanceData: " . $e->getMessage());
        return ['error' => 'Failed to get attendance data: ' . $e->getMessage()];
    }
}

/**
 * Get section information
 * @param int $sectionId
 * @return array|null
 */
function getSectionInfo($sectionId) {
    global $conn;
    
    $sql = "SELECT id, grade_level, section FROM section WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $sectionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Get subject information
 * @param int $subjectId
 * @return array|null
 */
function getSubjectInfo($subjectId) {
    global $conn;
    
    $sql = "SELECT id, subject_name FROM subjects WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subjectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Get student's RFID tag
 * @param int $studentId
 * @return string|null
 */
function getStudentRFIDTag($studentId) {
    global $conn;
    
    $sql = "SELECT rfid_tag FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = $result->fetch_assoc();
    return $row ? $row['rfid_tag'] : null;
}

/**
 * Read attendance records from englishrfid.txt file
 * @param string $subjectName
 * @param string $date
 * @return array
 */
function readAttendanceFromRFID($subjectName, $date) {
    $attendanceRecords = [];
    
    // Determine the correct RFID file based on subject
    $rfidPath = getRFIDFilePath($subjectName);
    
    if (!file_exists($rfidPath)) {
        error_log("RFID file not found: $rfidPath");
        return $attendanceRecords;
    }
    
    $content = trim(file_get_contents($rfidPath));
    if (empty($content)) {
        error_log("RFID file is empty: $rfidPath");
        return $attendanceRecords;
    }
    
    // Parse the RFID data: UID|timestamp|subject
    $parts = explode("|", $content);
    if (count($parts) >= 3) {
        $uid = trim($parts[0]);
        $timestamp = trim($parts[1]);
        $subject = trim($parts[2]);
        
        // Check if this is for the correct subject and date
        if ($subject === $subjectName) {
            $recordDate = date('Y-m-d', strtotime($timestamp));
            if ($recordDate === $date) {
                $time = date('H:i:s', strtotime($timestamp));
                $attendanceRecords[$uid] = [
                    'date' => $recordDate,
                    'time' => $time,
                    'subject' => $subject,
                    'description' => '' // RFID records don't have descriptions initially
                ];
                error_log("Found RFID record: UID=$uid, Time=$time, Subject=$subject");
            }
        }
    }
    
    error_log("Read " . count($attendanceRecords) . " attendance records from RFID");
    return $attendanceRecords;
}

/**
 * Get the correct RFID file path based on subject name
 * @param string $subjectName
 * @return string
 */
function getRFIDFilePath($subjectName) {
    // Generate subject-specific RFID file path using the same naming convention as Python script
    $subjectKey = strtolower(str_replace([' ', '-'], '_', $subjectName));
    return "C:/xampp/htdocs/SOFTDEV/{$subjectKey}rfid.txt";
}

// Old English-specific function removed - now using dynamic readAttendanceFromCSV function

// Old Math-specific function removed - now using dynamic readAttendanceFromCSV function

/**
 * Read attendance records from CSV files
 * @param string $sectionName
 * @param string $subjectName
 * @param string $date
 * @return array
 */
function readAttendanceFromCSV($sectionName, $subjectName, $date) {
    $attendanceRecords = [];
    
    // Generate subject-specific CSV file path
    $subjectKey = strtolower(str_replace([' ', '-'], '_', $subjectName));
    $csvPath = "C:/xampp/htdocs/SOFTDEV/logs/{$subjectKey}rfid_log_{$date}.csv";
    
    if (!file_exists($csvPath)) {
        error_log("CSV file not found: $csvPath");
        return $attendanceRecords;
    }
    
    $file = fopen($csvPath, 'r');
    if (!$file) {
        error_log("Could not open CSV file: $csvPath");
        return $attendanceRecords;
    }
    
    // Skip header row
    fgetcsv($file);
    
    // Read attendance records
    while (($row = fgetcsv($file)) !== false) {
        if (count($row) >= 4) {
            $uid = trim($row[0]);
            $recordDate = trim($row[1]);
            $time = trim($row[2]);
            $subject = trim($row[3]);
            $description = isset($row[4]) ? trim($row[4]) : '';
            
            // Convert date format from "DD MM YYYY" to "YYYY-MM-DD" if needed
            $formattedDate = $recordDate;
            if (preg_match('/^(\d{2})\s+(\d{2})\s+(\d{4})$/', $recordDate, $matches)) {
                $formattedDate = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
            
            // Check if this is a manual edit entry (subject contains status)
            $actualSubject = $subject;
            $manualStatus = null;
            if (strpos($subject, '_PRESENT') !== false) {
                $actualSubject = str_replace('_PRESENT', '', $subject);
                $manualStatus = 'present';
            } elseif (strpos($subject, '_LATE') !== false) {
                $actualSubject = str_replace('_LATE', '', $subject);
                $manualStatus = 'late';
            } elseif (strpos($subject, '_ABSENT') !== false) {
                $actualSubject = str_replace('_ABSENT', '', $subject);
                $manualStatus = 'absent';
            }
            
            // Only process records for the correct subject and date
            if ($actualSubject === $subjectName && $formattedDate === $date) {
                $attendanceRecords[$uid] = [
                    'date' => $formattedDate,
                    'time' => $time,
                    'subject' => $actualSubject,
                    'description' => $description,
                    'manual_status' => $manualStatus
                ];
            }
        }
    }
    
    fclose($file);
    error_log("Read " . count($attendanceRecords) . " attendance records from CSV: $csvPath");
    
    return $attendanceRecords;
}

/**
 * Determine attendance status based on time
 * @param string $time
 * @return string
 */
function determineAttendanceStatus($time) {
    if ($time === '-') {
        return 'absent';
    }
    
    // All RFID scans are considered present (late is only for manual edits)
    return 'present';
}

/**
 * Get daily attendance data for all subjects in a section
 * @param int $sectionId
 * @param string $date
 * @return array
 */
function getDailyAttendanceData($sectionId, $date) {
    try {
        error_log("Getting daily attendance data for section $sectionId on $date");
        
        // Get section information
        $sectionInfo = getSectionInfo($sectionId);
        if (!$sectionInfo) {
            error_log("Section info not found for ID: $sectionId");
            return ['error' => 'Section not found'];
        }
        
        // Get all subjects for this section
        $subjects = getSubjectsBySection($sectionId);
        if (empty($subjects)) {
            error_log("No subjects found for section $sectionId");
            return ['error' => 'No subjects found for this section'];
        }
        
        // Get all students in the section
        $students = getStudentsBySection($sectionId);
        if (empty($students)) {
            error_log("No students found for section $sectionId");
            return ['error' => 'No students found for this section'];
        }
        
        // Prepare the response data
        $response = [
            'subjects' => $subjects,
            'students' => []
        ];
        
        // For each student, get attendance for all subjects
        foreach ($students as $student) {
            $studentAttendance = [
                'id' => $student['id'],
                'name' => $student['name'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'middle_initial' => $student['middle_initial'],
                'attendance' => []
            ];
            
            // Check attendance for each subject
            foreach ($subjects as $subject) {
                $attendanceStatus = 'absent'; // Default to absent
                $attendanceTime = '-';
                
                // Get student's RFID tag
                $rfidTag = getStudentRFIDTag($student['id']);
                
                if ($rfidTag) {
                    // Check CSV records for this subject first (more reliable for daily attendance)
                    $csvRecords = readAttendanceFromCSV($sectionInfo['section'], $subject['name'], $date);
                    if (isset($csvRecords[$rfidTag])) {
                        $record = $csvRecords[$rfidTag];
                        // Check if this is a manual edit with explicit status
                        if (isset($record['manual_status']) && $record['manual_status'] !== null) {
                            $attendanceStatus = $record['manual_status'];
                        } else {
                            $attendanceStatus = determineAttendanceStatus($record['time']);
                        }
                        $attendanceTime = $record['time'];
                    } else {
                        // Check RFID records for this subject as fallback
                        $rfidRecords = readAttendanceFromRFID($subject['name'], $date);
                        if (isset($rfidRecords[$rfidTag])) {
                            $record = $rfidRecords[$rfidTag];
                            $attendanceStatus = determineAttendanceStatus($record['time']);
                            $attendanceTime = $record['time'];
                        }
                    }
                }
                
                $studentAttendance['attendance'][$subject['id']] = [
                    'status' => $attendanceStatus,
                    'time' => $attendanceTime
                ];
            }
            
            $response['students'][] = $studentAttendance;
        }
        
        error_log("Returning daily attendance data for " . count($response['students']) . " students and " . count($response['subjects']) . " subjects");
        return $response;
        
    } catch (Exception $e) {
        error_log("Error in getDailyAttendanceData: " . $e->getMessage());
        return ['error' => 'Failed to get daily attendance data: ' . $e->getMessage()];
    }
}

/**
 * Get all subjects for a specific section
 * @param int $sectionId
 * @return array
 */
function getSubjectsBySection($sectionId) {
    global $conn;
    
    try {
        $sql = "SELECT 
                    s.id,
                    s.subject_name as name,
                    s.subject_code as code,
                    t.first_name as teacher_first_name,
                    t.last_name as teacher_last_name,
                    t.gender as teacher_gender
                FROM subjects s
                LEFT JOIN teachers t ON t.id = s.teacher_id
                WHERE s.section_id = ?
                ORDER BY s.subject_name";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            // Format teacher name with gender prefix
            $teacherName = '';
            if (!empty($row['teacher_first_name']) && !empty($row['teacher_last_name'])) {
                $gender = $row['teacher_gender'] ?? 'Male';
                $prefix = ($gender === 'Female') ? 'Ms.' : 'Mr.';
                $teacherName = $prefix . ' ' . trim($row['teacher_first_name'] . ' ' . $row['teacher_last_name']);
            } else {
                $teacherName = 'No teacher assigned';
            }
            
            $subjects[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'code' => $row['code'] ?: generateSubjectCode($row['name'], ''),
                'teacher' => $teacherName
            ];
        }
        
        $stmt->close();
        return $subjects;
        
    } catch (Exception $e) {
        error_log("Error in getSubjectsBySection: " . $e->getMessage());
        return [];
    }
}

/**
 * Get monthly attendance data for all subjects in a section
 * @param int $sectionId
 * @param string $month (01-12)
 * @param string $year (YYYY)
 * @param int|null $subjectId Optional subject filter
 * @return array
 */
function getMonthlyAttendanceData($sectionId, $month, $year, $subjectId = null) {
    try {
        error_log("Getting monthly attendance data for section $sectionId, month $month, year $year");
        
        // Get section information
        $sectionInfo = getSectionInfo($sectionId);
        if (!$sectionInfo) {
            error_log("Section info not found for ID: $sectionId");
            return ['error' => 'Section not found'];
        }
        
        // Get all students in the section
        $students = getStudentsBySection($sectionId);
        if (empty($students)) {
            error_log("No students found for section $sectionId");
            return ['error' => 'No students found for this section'];
        }
        
        // Generate all dates in the specified month
        $dates = getDatesInMonth($month, $year);
        
        // Get subject information if filtering by specific subject
        $subjectInfo = null;
        if ($subjectId && $subjectId !== 'all') {
            $subjectInfo = getSubjectInfo($subjectId);
            if (!$subjectInfo) {
                error_log("Subject info not found for ID: $subjectId");
                return ['error' => 'Subject not found'];
            }
        }
        
        // Prepare the response data
        $response = [
            'dates' => $dates,
            'students' => []
        ];
        
        // For each student, get attendance for each date
        foreach ($students as $student) {
            $studentAttendance = [
                'id' => $student['id'],
                'name' => $student['name'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'middle_initial' => $student['middle_initial'],
                'attendance' => []
            ];
            
            // Get student's RFID tag
            $rfidTag = getStudentRFIDTag($student['id']);
            
            // Check attendance for each date
            foreach ($dates as $date) {
                $attendanceStatus = 'absent'; // Default to absent
                
                if ($rfidTag) {
                    if ($subjectInfo) {
                        // Check attendance for specific subject only
                        $attendanceStatus = checkSubjectDateAttendance($rfidTag, $date, $subjectInfo['subject_name']);
                    } else {
                        // Check attendance across all subjects
                        $hasAttendance = checkDateAttendance($rfidTag, $date, $sectionInfo['section']);
                        if ($hasAttendance) {
                            $attendanceStatus = 'present';
                        }
                    }
                }
                
                $studentAttendance['attendance'][$date] = [
                    'status' => $attendanceStatus
                ];
            }
            
            $response['students'][] = $studentAttendance;
        }
        
        error_log("Returning monthly attendance data for " . count($response['students']) . " students across " . count($response['dates']) . " dates");
        return $response;
        
    } catch (Exception $e) {
        error_log("Error in getMonthlyAttendanceData: " . $e->getMessage());
        return ['error' => 'Failed to get monthly attendance data: ' . $e->getMessage()];
    }
}

/**
 * Generate all dates in a given month and year
 * @param string $month (01-12)
 * @param string $year (YYYY)
 * @return array
 */
function getDatesInMonth($month, $year) {
    $dates = [];
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, intval($month), intval($year));
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%s-%s-%02d', $year, $month, $day);
        $dates[] = $date;
    }
    
    return $dates;
}

/**
 * Check if a student has attendance on a specific date
 * @param string $rfidTag
 * @param string $date
 * @param string $sectionName
 * @return bool
 */
function checkDateAttendance($rfidTag, $date, $sectionName) {
    // Get all subject log files for this date
    $logDirectory = __DIR__ . '/../logs/';
    $pattern = $logDirectory . '*rfid_log_' . $date . '.csv';
    $logFiles = glob($pattern);
    
    if (empty($logFiles)) {
        return false;
    }
    
    // Check each log file for the student's RFID tag
    foreach ($logFiles as $logFile) {
        if (($handle = fopen($logFile, "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) >= 3) {
                    $uid = trim($data[0]);
                    
                    if ($uid === $rfidTag) {
                        fclose($handle);
                        return true;
                    }
                }
            }
            fclose($handle);
        }
    }
    
    return false;
}

/**
 * Check if a student has attendance for a specific subject on a specific date
 * @param string $rfidTag
 * @param string $date
 * @param string $subjectName
 * @return string ('present', 'late', 'absent')
 */
function checkSubjectDateAttendance($rfidTag, $date, $subjectName) {
    // Generate subject-specific CSV file path
    $subjectKey = strtolower(str_replace([' ', '-'], '_', $subjectName));
    $csvPath = __DIR__ . "/../logs/{$subjectKey}rfid_log_{$date}.csv";
    
    if (!file_exists($csvPath)) {
        return 'absent';
    }
    
    $file = fopen($csvPath, 'r');
    if (!$file) {
        return 'absent';
    }
    
    // Skip header row
    fgetcsv($file);
    
    // Read attendance records
    while (($row = fgetcsv($file)) !== false) {
        if (count($row) >= 4) {
            $uid = trim($row[0]);
            $recordDate = trim($row[1]);
            $time = trim($row[2]);
            $subject = trim($row[3]);
            $description = isset($row[4]) ? trim($row[4]) : '';
            
            // Convert date format from "DD MM YYYY" to "YYYY-MM-DD" if needed
            $formattedDate = $recordDate;
            if (preg_match('/^(\d{2})\s+(\d{2})\s+(\d{4})$/', $recordDate, $matches)) {
                $formattedDate = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
            
            if ($uid === $rfidTag && $formattedDate === $date) {
                // Check if this is a manual edit entry (subject contains status)
                if (strpos($subject, '_PRESENT') !== false) {
                    fclose($file);
                    return 'present';
                } elseif (strpos($subject, '_LATE') !== false) {
                    fclose($file);
                    return 'late';
                } else {
                    // Normal RFID scan - determine status based on time
                    fclose($file);
                    return determineAttendanceStatus($time);
                }
            }
        }
    }
    
    fclose($file);
    return 'absent';
}

// Handle AJAX requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    // Set content type to JSON
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'get_sections':
                echo json_encode(getSectionsWithSubjects());
                break;
                
            case 'get_students':
                if (isset($_GET['section_id'])) {
                    echo json_encode(getStudentsBySection($_GET['section_id']));
                } else {
                    echo json_encode(['error' => 'Section ID required']);
                }
                break;
                
            case 'get_attendance':
                if (isset($_GET['subject_id']) && isset($_GET['section_id'])) {
                    $result = getSubjectAttendanceData($_GET['subject_id'], $_GET['section_id']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['error' => 'Subject ID and Section ID required']);
                }
                break;
                
            case 'get_daily_attendance':
                if (isset($_GET['section_id']) && isset($_GET['date'])) {
                    $result = getDailyAttendanceData($_GET['section_id'], $_GET['date']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['error' => 'Section ID and Date required']);
                }
                break;
                
            case 'get_monthly_attendance':
                if (isset($_GET['section_id']) && isset($_GET['month']) && isset($_GET['year'])) {
                    $subjectId = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;
                    $result = getMonthlyAttendanceData($_GET['section_id'], $_GET['month'], $_GET['year'], $subjectId);
                    echo json_encode($result);
                } else {
                    echo json_encode(['error' => 'Section ID, Month, and Year required']);
                }
                break;
                
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } catch (Exception $e) {
        error_log("Error in get_subject_attendance_data.php: " . $e->getMessage());
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    } catch (Error $e) {
        error_log("Fatal error in get_subject_attendance_data.php: " . $e->getMessage());
        echo json_encode(['error' => 'Fatal error: ' . $e->getMessage()]);
    }
    exit;
}
?>
