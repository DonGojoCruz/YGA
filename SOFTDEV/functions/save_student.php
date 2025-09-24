<?php
require_once '../db/db_connect.php';
require_once 'email_verification.php';

// Normalize a name: trim, collapse spaces, lowercase then capitalize each word
function normalizeName($name) {
    $name = trim((string)$name);
    if ($name === '') { return $name; }
    // Keep letters, spaces, hyphens, and apostrophes; remove other characters
    $name = preg_replace("/[^A-Za-z\s'\-]/", '', $name);
    // Collapse multiple spaces
    $name = preg_replace('/\s+/', ' ', $name);
    // Lowercase everything first
    $lower = strtolower($name);
    // Capitalize each word, respecting hyphens and apostrophes
    $capitalized = preg_replace_callback("/(^|[\s'\-])(\p{L})/u", function($m) {
        return $m[1] . strtoupper($m[2]);
    }, $lower);
    return $capitalized;
}

/**
 * Save student with email verification
 * 
 * @param array $studentData Array containing student information
 * @return array Response array with status and message
 */
function saveStudentWithEmailVerification($studentData) {
    global $conn;
    
    try {
        // Validate required fields
        $requiredFields = ['rfid_tag', 'last_name', 'first_name'];
        foreach ($requiredFields as $field) {
            if (empty(trim($studentData[$field]))) {
                $fieldName = str_replace('_', '', ucwords($field, '_'));
                return [
                    'status' => 'error',
                    'field' => $fieldName,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.'
                ];
            }
        }
        
        // Validate RFID Tag format and length
        if (!empty($studentData['rfid_tag'])) {
            $rfidTag = trim($studentData['rfid_tag']);
            
            // Check RFID tag length (typically 8-16 characters)
            if (strlen($rfidTag) < 8 || strlen($rfidTag) > 16) {
                return [
                    'status' => 'error',
                    'field' => 'rfidTag',
                    'message' => 'RFID Tag must be between 8 and 16 characters long.'
                ];
            }
        }
        
        // Validate LRN format (12 digits for Philippine LRN)
        if (!empty($studentData['lrn'])) {
            $lrn = trim($studentData['lrn']);
            
            // Check if LRN is exactly 12 digits
            if (!preg_match('/^\d{12}$/', $lrn)) {
                return [
                    'status' => 'error',
                    'field' => 'lrn',
                    'message' => 'LRN must be exactly 12 digits long.'
                ];
            }
            
            // Check if LRN starts with valid Philippine LRN prefixes
            $validPrefixes = ['136', '137', '138', '139', '140', '141', '142', '143', '144', '145', '146', '147', '148', '149', '150'];
            $lrnPrefix = substr($lrn, 0, 3);
            if (!in_array($lrnPrefix, $validPrefixes)) {
                return [
                    'status' => 'error',
                    'field' => 'lrn',
                    'message' => 'LRN must start with a valid Philippine school division code (136-150).'
                ];
            }
        }
        
        // Validate email format if provided
        if (!empty($studentData['email'])) {
            $email = trim($studentData['email']);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'status' => 'error',
                    'field' => 'email',
                    'message' => 'Please enter a valid email address.'
                ];
            }
            
            // Check email length
            if (strlen($email) > 100) {
                return [
                    'status' => 'error',
                    'field' => 'email',
                    'message' => 'Email address is too long. Maximum 100 characters allowed.'
                ];
            }
        }
        
        // Normalize name-like fields
        $studentData['last_name'] = normalizeName($studentData['last_name'] ?? '');
        $studentData['first_name'] = normalizeName($studentData['first_name'] ?? '');
        $studentData['middle_initial'] = normalizeName($studentData['middle_initial'] ?? '');
        $studentData['guardian'] = normalizeName($studentData['guardian'] ?? '');

        // Collect all validation errors
        $errors = [];
        
        // Check if RFID tag already exists
        $rfidCheck = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE rfid_tag = ?");
        $rfidCheck->bind_param("s", $studentData['rfid_tag']);
        $rfidCheck->execute();
        $rfidResult = $rfidCheck->get_result();
        
        if ($rfidResult->num_rows > 0) {
            $existingStudent = $rfidResult->fetch_assoc();
            $errors[] = [
                'field' => 'rfidTag',
                'message' => "RFID Tag is already used."
            ];
        }
        
        // Check if LRN already exists (if provided)
        if (!empty($studentData['lrn'])) {
            $lrnCheck = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE lrn = ?");
            $lrnCheck->bind_param("s", $studentData['lrn']);
            $lrnCheck->execute();
            $lrnResult = $lrnCheck->get_result();
            
            if ($lrnResult->num_rows > 0) {
                $existingStudent = $lrnResult->fetch_assoc();
                $errors[] = [
                    'field' => 'lrn',
                    'message' => "LRN is already used."
                ];
            }
        }
        
        // Check if email already exists (if provided)
        if (!empty($studentData['email'])) {
            $emailCheck = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE email = ?");
            $emailCheck->bind_param("s", $studentData['email']);
            $emailCheck->execute();
            $emailResult = $emailCheck->get_result();
            
            if ($emailResult->num_rows > 0) {
                $existingStudent = $emailResult->fetch_assoc();
                $errors[] = [
                    'field' => 'email',
                    'message' => "Email is already registered."
                ];
            }
        }
        
        // If there are validation errors, return them
        if (!empty($errors)) {
            return [
                'status' => 'error',
                'errors' => $errors
            ];
        }
        
        // Handle photo upload if exists
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['photo']['name']);
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
                $photoPath = 'uploads/' . $fileName; // relative path for DB
            }
        }
        
        // Add photo path to student data
        $studentData['photo_path'] = $photoPath;
        
        // If email is provided, send verification email
        if (!empty($studentData['email'])) {
            $emailVerification = new EmailVerification($conn);
            
            // Create verification token
            $tokenResult = $emailVerification->createVerificationToken($studentData['email'], $studentData);
            
            if ($tokenResult['status'] !== 'success') {
                return $tokenResult;
            }
            
            // Send verification email
            $studentName = $studentData['first_name'] . ' ' . $studentData['last_name'];
            $emailResult = $emailVerification->sendVerificationEmail($studentData['email'], $tokenResult['token'], $studentName);
            
            if ($emailResult['status'] !== 'success') {
                return $emailResult;
            }
            
            return [
                'status' => 'success',
                'message' => 'Verification email sent to ' . $studentData['email'] . '. Please check your email and click the verification link to complete registration.',
                'requires_verification' => true
            ];
        } else {
            // No email provided, save directly without verification
            return saveStudentDirectly($studentData);
        }
        
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ];
    }
}

/**
 * Save student data directly to the students table (without email verification)
 * 
 * @param array $studentData Array containing student information
 * @return array Response array with status and message
 */
function saveStudentDirectly($studentData) {
    global $conn;
    
    try {
        // Validate required fields
        $requiredFields = ['rfid_tag', 'last_name', 'first_name'];
        foreach ($requiredFields as $field) {
            if (empty(trim($studentData[$field]))) {
                $fieldName = str_replace('_', '', ucwords($field, '_'));
                return [
                    'status' => 'error',
                    'field' => $fieldName,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.'
                ];
            }
        }
        
                 // Validate RFID Tag format and length
         if (!empty($studentData['rfid_tag'])) {
             $rfidTag = trim($studentData['rfid_tag']);
             
             // Check RFID tag length (typically 8-16 characters)
             if (strlen($rfidTag) < 8 || strlen($rfidTag) > 16) {
                 return [
                     'status' => 'error',
                     'message' => 'RFID Tag must be between 8 and 16 characters long.'
                 ];
             }
         }
        
        // Validate LRN format (12 digits for Philippine LRN)
        if (!empty($studentData['lrn'])) {
            $lrn = trim($studentData['lrn']);
            
            // Check if LRN is exactly 12 digits
            if (!preg_match('/^\d{12}$/', $lrn)) {
                return [
                    'status' => 'error',
                    'field' => 'lrn',
                    'message' => 'LRN must be exactly 12 digits long.'
                ];
            }
            
            // Check if LRN starts with valid Philippine LRN prefixes
            $validPrefixes = ['136', '137', '138', '139', '140', '141', '142', '143', '144', '145', '146', '147', '148', '149', '150'];
            $lrnPrefix = substr($lrn, 0, 3);
            if (!in_array($lrnPrefix, $validPrefixes)) {
                return [
                    'status' => 'error',
                    'field' => 'lrn',
                    'message' => 'LRN must start with a valid Philippine school division code (136-150).'
                ];
            }
        }
        
        // Validate email format if provided
        if (!empty($studentData['email'])) {
            $email = trim($studentData['email']);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'status' => 'error',
                    'field' => 'email',
                    'message' => 'Please enter a valid email address.'
                ];
            }
            
            // Check email length
            if (strlen($email) > 100) {
                return [
                    'status' => 'error',
                    'field' => 'email',
                    'message' => 'Email address is too long. Maximum 100 characters allowed.'
                ];
            }
        }
        
        // Collect all validation errors
        $errors = [];
        
        // Check if RFID tag already exists
        $rfidCheck = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE rfid_tag = ?");
        $rfidCheck->bind_param("s", $studentData['rfid_tag']);
        $rfidCheck->execute();
        $rfidResult = $rfidCheck->get_result();
        
        if ($rfidResult->num_rows > 0) {
            $existingStudent = $rfidResult->fetch_assoc();
            $errors[] = [
                'field' => 'rfidTag',
                'message' => "RFID Tag is already used."
            ];
        }
        
        // Check if LRN already exists (if provided)
        if (!empty($studentData['lrn'])) {
            $lrnCheck = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE lrn = ?");
            $lrnCheck->bind_param("s", $studentData['lrn']);
            $lrnCheck->execute();
            $lrnResult = $lrnCheck->get_result();
            
            if ($lrnResult->num_rows > 0) {
                $existingStudent = $lrnResult->fetch_assoc();
                $errors[] = [
                    'field' => 'lrn',
                    'message' => "LRN is already used."
                ];
            }
        }
        
        // Check if email already exists (if provided)
        if (!empty($studentData['email'])) {
            $emailCheck = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE email = ?");
            $emailCheck->bind_param("s", $studentData['email']);
            $emailCheck->execute();
            $emailResult = $emailCheck->get_result();
            
            if ($emailResult->num_rows > 0) {
                $existingStudent = $emailResult->fetch_assoc();
                $errors[] = [
                    'field' => 'email',
                    'message' => "Email is already registered."
                ];
            }
        }
        
        // If there are validation errors, return them
        if (!empty($errors)) {
            return [
                'status' => 'error',
                'errors' => $errors
            ];
        }
        
        // Get section_id if grade_level and section are provided
        $sectionId = null;
        if (!empty($studentData['grade_level']) && !empty($studentData['section'])) {
            $sectionQuery = $conn->prepare("SELECT id FROM section WHERE grade_level = ? AND section = ?");
            // Treat grade_level as string to support values like 'Nursery', 'Kinder 1', etc.
            $sectionQuery->bind_param("ss", $studentData['grade_level'], $studentData['section']);
            $sectionQuery->execute();
            $sectionResult = $sectionQuery->get_result();
            
            if ($sectionResult->num_rows > 0) {
                $sectionRow = $sectionResult->fetch_assoc();
                $sectionId = $sectionRow['id'];
            }
        }

        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['photo']['name']);
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
                $photoPath = 'uploads/' . $fileName; // relative path for DB
            }
        }
        
        // Prepare the insert statement
        $sql = "INSERT INTO students 
            (rfid_tag, lrn, last_name, first_name, middle_initial, birthdate, gender, guardian, email, grade_level, section_id, photo_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssis",
            $studentData['rfid_tag'],
            $studentData['lrn'],
            $studentData['last_name'],
            $studentData['first_name'],
            $studentData['middle_initial'],
            $studentData['birthdate'],
            $studentData['gender'],
            $studentData['guardian'],
            $studentData['email'],
            $studentData['grade_level'],
            $sectionId,
            $photoPath
        );
        
        if ($stmt->execute()) {
            // Delete showrfid.txt file instantly after successful save
            $showrfid_path = "C:/xampp/htdocs/SOFTDEV/showrfid.txt";
            if (file_exists($showrfid_path)) {
                unlink($showrfid_path);
            }
            
            // Execute stop RFID script after successful save
            try {
                $stopVbsPath = "C:/xampp/htdocs/SOFTDEV/stopRFID.vbs";
                $stopBatPath = "C:/xampp/htdocs/SOFTDEV/stopRFID.bat";
                
                if (file_exists($stopVbsPath)) {
                    $command = 'cscript //nologo "' . $stopVbsPath . '"';
                    shell_exec($command . ' 2>&1');
                } elseif (file_exists($stopBatPath)) {
                    $command = 'cmd /c "' . $stopBatPath . '"';
                    shell_exec($command . ' 2>&1');
                }
            } catch (Exception $e) {
                // Log error but don't fail the save operation
                error_log('Failed to execute stop RFID script: ' . $e->getMessage());
            }
            
            return [
                'status' => 'success',
                'message' => 'Student saved successfully!',
                'student_id' => $conn->insert_id
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to save student: ' . $stmt->error
            ];
        }
        
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ];
    }
}

/**
 * Update student with email verification
 * 
 * @param int $studentId Student ID to update
 * @param array $studentData Array containing student information
 * @return array Response array with status and message
 */
function updateStudentWithEmailVerification($studentId, $studentData) {
    global $conn;
    
    try {
        // Validate student ID
        if ($studentId <= 0) {
            return [
                'status' => 'error',
                'message' => 'Invalid student ID.'
            ];
        }
        
        // Check if student exists
        $checkStmt = $conn->prepare("SELECT student_id, email FROM students WHERE student_id = ?");
        $checkStmt->bind_param("i", $studentId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'status' => 'error',
                'message' => 'Student not found.'
            ];
        }
        
        $existingStudent = $result->fetch_assoc();
        $checkStmt->close();
        
        // Validate required fields
        $requiredFields = ['rfid_tag', 'last_name', 'first_name'];
        foreach ($requiredFields as $field) {
            if (empty(trim($studentData[$field]))) {
                $fieldName = str_replace('_', '', ucwords($field, '_'));
                return [
                    'status' => 'error',
                    'field' => $fieldName,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.'
                ];
            }
        }
        
        // Validate RFID Tag format and length
        if (!empty($studentData['rfid_tag'])) {
            $rfidTag = trim($studentData['rfid_tag']);
            
            // Check RFID tag length (typically 8-16 characters)
            if (strlen($rfidTag) < 8 || strlen($rfidTag) > 16) {
                return [
                    'status' => 'error',
                    'field' => 'rfidTag',
                    'message' => 'RFID tag must be 8-16 characters long.'
                ];
            }
            
            // Check if RFID tag already exists for another student
            $rfidCheckStmt = $conn->prepare("SELECT student_id FROM students WHERE rfid_tag = ? AND student_id != ?");
            $rfidCheckStmt->bind_param("si", $rfidTag, $studentId);
            $rfidCheckStmt->execute();
            $rfidResult = $rfidCheckStmt->get_result();
            
            if ($rfidResult->num_rows > 0) {
                $rfidCheckStmt->close();
                return [
                    'status' => 'error',
                    'field' => 'rfidTag',
                    'message' => 'This RFID tag is already registered to another student.'
                ];
            }
            $rfidCheckStmt->close();
        }
        
        // Validate LRN if provided
        if (!empty($studentData['lrn'])) {
            $lrn = trim($studentData['lrn']);
            
            // Check if LRN already exists for another student
            $lrnCheckStmt = $conn->prepare("SELECT student_id FROM students WHERE lrn = ? AND student_id != ?");
            $lrnCheckStmt->bind_param("si", $lrn, $studentId);
            $lrnCheckStmt->execute();
            $lrnResult = $lrnCheckStmt->get_result();
            
            if ($lrnResult->num_rows > 0) {
                $lrnCheckStmt->close();
                return [
                    'status' => 'error',
                    'field' => 'lrn',
                    'message' => 'This LRN is already registered to another student.'
                ];
            }
            $lrnCheckStmt->close();
        }
        
        // Normalize name fields
        $studentData['last_name'] = normalizeName($studentData['last_name']);
        $studentData['first_name'] = normalizeName($studentData['first_name']);
        $studentData['middle_initial'] = normalizeName($studentData['middle_initial']);
        $studentData['guardian'] = normalizeName($studentData['guardian']);
        
        // Get section ID
        $sectionId = null;
        if (!empty($studentData['grade_level']) && !empty($studentData['section'])) {
            $sectionStmt = $conn->prepare("SELECT id FROM section WHERE grade_level = ? AND section = ?");
            $sectionStmt->bind_param("ss", $studentData['grade_level'], $studentData['section']);
            $sectionStmt->execute();
            $sectionResult = $sectionStmt->get_result();
            
            if ($sectionResult->num_rows > 0) {
                $sectionRow = $sectionResult->fetch_assoc();
                $sectionId = $sectionRow['id'];
            }
            $sectionStmt->close();
        }
        
        // Handle photo upload if provided
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = 'student_' . $studentId . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                    $photoPath = 'uploads/' . $fileName;
                }
            }
        }
        
        // Check if email changed and requires verification
        $emailChanged = false;
        $requiresVerification = false;
        
        // Normalize emails for comparison (trim and lowercase)
        $newEmail = !empty($studentData['email']) ? trim(strtolower($studentData['email'])) : '';
        $existingEmail = !empty($existingStudent['email']) ? trim(strtolower($existingStudent['email'])) : '';
        
        // Only check for email changes if a new email is provided
        if (!empty($newEmail) && $newEmail !== $existingEmail) {
            $emailChanged = true;
            
            // Use the original (non-normalized) email for database operations
            $originalNewEmail = trim($studentData['email']);
            
            // Check if email already exists for another student (case-insensitive)
            $emailCheckStmt = $conn->prepare("SELECT student_id FROM students WHERE LOWER(TRIM(email)) = ? AND student_id != ?");
            $emailCheckStmt->bind_param("si", $newEmail, $studentId);
            $emailCheckStmt->execute();
            $emailResult = $emailCheckStmt->get_result();
            
            if ($emailResult->num_rows > 0) {
                $emailCheckStmt->close();
                return [
                    'status' => 'error',
                    'field' => 'email',
                    'message' => 'This email is already registered to another student.'
                ];
            }
            $emailCheckStmt->close();
            
            // Email verification is required for new/changed emails
            $requiresVerification = true;
            
            // Log the email change for debugging
            error_log("Email changed for student ID $studentId: '$existingEmail' -> '$newEmail'");
        } else {
            // Log when email is not changed
            error_log("Email not changed for student ID $studentId: keeping '$existingEmail'");
        }
        
        // Prepare UPDATE statement
        $updateFields = [];
        $updateValues = [];
        $updateTypes = '';
        
        // Add fields to update
        $fieldsToUpdate = [
            'rfid_tag' => 's',
            'lrn' => 's',
            'last_name' => 's',
            'first_name' => 's',
            'middle_initial' => 's',
            'birthdate' => 's',
            'gender' => 's',
            'guardian' => 's',
            'email' => 's',
            'grade_level' => 's',
            'section_id' => 'i'
        ];
        
        foreach ($fieldsToUpdate as $field => $type) {
            if ($field === 'section_id') {
                $updateFields[] = "section_id = ?";
                $updateValues[] = $sectionId;
                $updateTypes .= $type;
            } else {
                $updateFields[] = "$field = ?";
                $updateValues[] = $studentData[$field];
                $updateTypes .= $type;
            }
        }
        
        // Add photo path if provided
        if ($photoPath !== null) {
            $updateFields[] = "photo_path = ?";
            $updateValues[] = $photoPath;
            $updateTypes .= 's';
        }
        
        // Add student ID for WHERE clause
        $updateValues[] = $studentId;
        $updateTypes .= 'i';
        
        $sql = "UPDATE students SET " . implode(', ', $updateFields) . " WHERE student_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($updateTypes, ...$updateValues);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Send verification email only if email actually changed
            if ($emailChanged && $requiresVerification && !empty($studentData['email'])) {
                $emailToVerify = trim($studentData['email']); // Use original format
                $emailResult = sendVerificationEmail($emailToVerify, $studentData['first_name'], $studentData['last_name']);
                
                if ($emailResult['success']) {
                    error_log("Verification email sent successfully to $emailToVerify for student ID $studentId");
                    return [
                        'status' => 'success',
                        'requires_verification' => true,
                        'message' => 'Student updated successfully! A verification email has been sent to ' . $emailToVerify . '. Please check your inbox and click the verification link.',
                        'student_id' => $studentId
                    ];
                } else {
                    // Student updated but email failed
                    error_log("Failed to send verification email to $emailToVerify for student ID $studentId");
                    return [
                        'status' => 'success',
                        'message' => 'Student updated successfully, but failed to send verification email. Please contact administrator.',
                        'student_id' => $studentId
                    ];
                }
            }
            
            // Return success message (no email verification needed)
            $message = 'Student updated successfully!';
            if (!empty($existingStudent['email']) && !empty($studentData['email'])) {
                $message .= ' Email unchanged - no verification required.';
            }
            
            return [
                'status' => 'success',
                'message' => $message,
                'student_id' => $studentId
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to update student: ' . $stmt->error
            ];
        }
        
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Check if this is an edit operation
    $isEdit = isset($_POST['is_edit']) && $_POST['is_edit'] === '1';
    $studentId = $isEdit ? (int)($_POST['student_id'] ?? 0) : 0;
    
    // Get form data
    $studentData = [
        'rfid_tag' => $_POST['rfidTag'] ?? '',
        'lrn' => $_POST['lrn'] ?? '',
        'last_name' => $_POST['lastName'] ?? '',
        'first_name' => $_POST['firstName'] ?? '',
        'middle_initial' => $_POST['middleInitial'] ?? '',
        'birthdate' => $_POST['birthdate'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'guardian' => $_POST['guardian'] ?? '',
        'email' => $_POST['email'] ?? '',
        'grade_level' => $_POST['gradeLevel'] ?? '',
        'section' => $_POST['section'] ?? ''
    ];
    
    // Clean empty strings to null for database
    foreach ($studentData as $key => $value) {
        if ($value === '') {
            $studentData[$key] = null;
        }
    }
    
    if ($isEdit && $studentId > 0) {
        $result = updateStudentWithEmailVerification($studentId, $studentData);
    } else {
        $result = saveStudentWithEmailVerification($studentData);
    }
    
    echo json_encode($result);
    exit;
}
?>
