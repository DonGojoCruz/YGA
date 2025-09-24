-- Database setup script for Young Generation Academy RFID Entry & Attendance System
-- Run this script in your MySQL database to create the necessary tables
-- Database name: samaria

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','staff') NOT NULL DEFAULT 'staff',
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create section table
CREATE TABLE IF NOT EXISTS `section` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `grade_level` varchar(20) NOT NULL,
  `section` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create teachers table
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('Male','Female') NOT NULL DEFAULT 'Male',
  `middle_initial` varchar(10) DEFAULT NULL,
  `grade_level` varchar(20) DEFAULT NULL,
  `sections` text DEFAULT NULL,
  `advisory` varchar(50) DEFAULT NULL,
  `advisory_section_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_teachers_advisory_section` (`advisory_section_id`),
  CONSTRAINT `fk_teachers_advisory_section` FOREIGN KEY (`advisory_section_id`) REFERENCES `section` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create subjects table
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `subject_description` text DEFAULT NULL,
  `grade_level` varchar(20) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `section_id` (`section_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `idx_subject_code` (`subject_code`),
  CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `section` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subjects_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create students table
CREATE TABLE IF NOT EXISTS `students` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `rfid_tag` varchar(50) NOT NULL,
  `lrn` varchar(50) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_initial` char(50) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `guardian` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `grade_level` varchar(255) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo_path` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `email_verification_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `rfid_tag` (`rfid_tag`),
  UNIQUE KEY `unique_lrn` (`lrn`),
  KEY `section_id` (`section_id`),
  KEY `idx_email_verification_token` (`email_verification_token`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `section` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create attendance_logs table
CREATE TABLE IF NOT EXISTS `attendance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rfid_uid` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `scan_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rfid_time` (`rfid_uid`,`scan_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create email_verification_tokens table
CREATE TABLE IF NOT EXISTS `email_verification_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `student_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `role`, `full_name`, `email`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'admin@younggenerationacademy.edu.ph');

-- Insert sample sections
INSERT INTO `section` (`grade_level`, `section`, `password`) VALUES
('Nursery', 'Banana', 'password123'),
('Kinder 1', 'Apple', 'password123'),
('Kinder 2', 'Orange', 'password123'),
('1', 'Watermelon', 'password123'),
('1', 'Mango', 'password123'),
('2', 'Grapes', 'password123'),
('7', 'MAGSAYSAY', 'password123'),
('7', 'OSMEÑA', 'password123'),
('7', 'QUEZON', 'password123'),
('8', 'EINSTEIN', 'password123'),
('11', 'ABM-SY', 'password123'),
('11', 'GAS - DA VINCI', 'password123');

-- Insert sample teachers
INSERT INTO `teachers` (`teacher_id`, `first_name`, `last_name`, `gender`, `middle_initial`, `grade_level`, `sections`, `advisory`) VALUES
('T001', 'Maria', 'Almazan', 'Female', 'A', 'Nursery', 'Banana,Watermelon,Mango', 'Banana'),
('T002', 'Juan', 'Santos', 'Male', 'B', 'Kinder 1', 'Apple,Orange', 'Apple'),
('T003', 'Ana', 'Cruz', 'Female', 'C', '2', 'Grapes', 'Grapes');

-- Insert sample subjects
INSERT INTO `subjects` (`subject_name`, `subject_code`, `subject_description`, `grade_level`, `section_id`, `teacher_id`) VALUES
('Mathematics', 'MATH', 'Basic Mathematics', 'Nursery', 1, 1),
('English', 'ENG', 'English Language', 'Nursery', 1, 1),
('Science', 'SCI', 'Basic Science', 'Nursery', 1, 1),
('Mathematics', 'MATH', 'Grade 1 Mathematics', '1', 4, 1),
('English', 'ENG', 'Grade 1 English', '1', 4, 1),
('Science', 'SCI', 'Grade 1 Science', '1', 4, 1),
('Filipino', 'FIL', 'Grade 1 Filipino', '1', 4, 1),
('Mathematics', 'MATH', 'Grade 1 Mathematics', '1', 5, 1),
('English', 'ENG', 'Grade 1 English', '1', 5, 1),
('Science', 'SCI', 'Grade 1 Science', '1', 5, 1),
('Filipino', 'FIL', 'Grade 1 Filipino', '1', 5, 1);

-- Note: The password hash above is for 'admin123'
-- To create a new password hash, you can use PHP's password_hash() function
-- Example: echo password_hash('your_password', PASSWORD_DEFAULT);