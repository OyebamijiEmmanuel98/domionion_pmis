-- =====================================================
-- DOMINION UNIVERSITY, IBADAN
-- PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
-- DATABASE SCHEMA
-- =====================================================
-- Author: Final Year Project
-- Date: 2026
-- Description: Complete database schema for PMIS
-- =====================================================

-- Drop database if exists and create new
DROP DATABASE IF EXISTS pmis_dominion;
CREATE DATABASE pmis_dominion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pmis_dominion;

-- =====================================================
-- TABLE 1: ROLES
-- Stores system user roles for access control
-- =====================================================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 2: DEPARTMENTS
-- Stores university departments information
-- =====================================================
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(20) UNIQUE,
    hod_staff_id INT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dept_name (department_name),
    INDEX idx_dept_code (department_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 3: STAFF
-- Stores all personnel/staff records
-- =====================================================
CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique staff identification number',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    date_of_birth DATE,
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    next_of_kin VARCHAR(200),
    height VARCHAR(10),
    qualification VARCHAR(255),
    department_id INT,
    rank VARCHAR(100) COMMENT 'Job title/designation',
    employment_condition ENUM('Permanent', 'Contract', 'Temporary', 'Part-time') DEFAULT 'Permanent',
    date_recruited DATE,
    reason TEXT COMMENT 'Reason for employment/remarks',
    basic_salary DECIMAL(12, 2),
    staff_type ENUM('academic', 'non_academic') NOT NULL,
    passport_photo VARCHAR(255) COMMENT 'Path to passport photo',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraint
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    -- Indexes for performance
    INDEX idx_staff_id (staff_id),
    INDEX idx_staff_name (last_name, first_name),
    INDEX idx_staff_type (staff_type),
    INDEX idx_staff_status (status),
    INDEX idx_staff_dept (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add foreign key for HOD after staff table is created
ALTER TABLE departments 
ADD FOREIGN KEY (hod_staff_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================
-- TABLE 4: USERS
-- Stores system user accounts linked to staff
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100),
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    staff_id INT NULL COMMENT 'Link to staff record if applicable',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_username (username),
    INDEX idx_user_status (status),
    INDEX idx_user_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 5: LEAVE_TYPES
-- Stores different types of leave available
-- =====================================================
CREATE TABLE leave_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leave_name VARCHAR(100) NOT NULL,
    max_days INT DEFAULT 0 COMMENT 'Maximum days allowed per year',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_leave_name (leave_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 6: LEAVE_APPLICATIONS
-- Stores staff leave applications and approvals
-- =====================================================
CREATE TABLE leave_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    hod_comment TEXT,
    hr_comment TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT NULL,
    
    -- Foreign key constraints
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_leave_staff (staff_id),
    INDEX idx_leave_status (status),
    INDEX idx_leave_dates (start_date, end_date),
    INDEX idx_leave_applied (applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 7: ASSESSMENTS
-- Stores staff performance assessments
-- =====================================================
CREATE TABLE assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    assessor_user_id INT NOT NULL,
    assessment_date DATE NOT NULL,
    report TEXT NOT NULL,
    recommendation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (assessor_user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_assess_staff (staff_id),
    INDEX idx_assess_date (assessment_date),
    INDEX idx_assessor (assessor_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 8: ACTIVITY_LOGS
-- System audit trail for tracking actions
-- =====================================================
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_log_user (user_id),
    INDEX idx_log_action (action),
    INDEX idx_log_table (table_name),
    INDEX idx_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 9: LOGIN_LOGS
-- Tracks user login/logout sessions
-- =====================================================
CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    
    -- Foreign key
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_login_user (user_id),
    INDEX idx_login_time (login_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 10: STAFF_DOCUMENTS (Optional)
-- Stores uploaded documents for staff
-- =====================================================
CREATE TABLE staff_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    document_name VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT,
    
    -- Foreign key constraints
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_doc_staff (staff_id),
    INDEX idx_doc_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SEED DATA
-- =====================================================

-- Insert Roles
INSERT INTO roles (role_name, description) VALUES
('admin', 'System Administrator - Full system access'),
('hr', 'Human Resources Unit - Manage staff and personnel records'),
('hod', 'Head of Department - Department-level management'),
('staff', 'Staff Member - Limited personal access');

-- Insert Leave Types
INSERT INTO leave_types (leave_name, max_days, description) VALUES
('Annual Leave', 30, 'Regular yearly leave entitlement'),
('Sick Leave', 14, 'Leave for medical reasons with certificate'),
('Casual Leave', 7, 'Short-term leave for urgent personal matters'),
('Maternity Leave', 90, 'Leave for female staff during pregnancy'),
('Paternity Leave', 14, 'Leave for male staff after child birth'),
('Study Leave', 365, 'Leave for further studies and professional development'),
('Compassionate Leave', 14, 'Leave for bereavement or family emergencies');

-- Insert Sample Departments
INSERT INTO departments (department_name, department_code, description) VALUES
('Computer Science', 'CSC', 'Department of Computer Science and Information Technology'),
('Mathematics', 'MTH', 'Department of Mathematical Sciences'),
('Physics', 'PHY', 'Department of Physics'),
('Chemistry', 'CHM', 'Department of Chemistry'),
('Biology', 'BIO', 'Department of Biological Sciences'),
('English', 'ENG', 'Department of English and Literary Studies'),
('History', 'HIS', 'Department of History and International Studies'),
('Economics', 'ECO', 'Department of Economics'),
('Business Administration', 'BUS', 'Department of Business Administration'),
('Accounting', 'ACC', 'Department of Accounting'),
('Law', 'LAW', 'Faculty of Law'),
('Medicine', 'MED', 'College of Medicine'),
('Nursing', 'NSG', 'Department of Nursing Sciences'),
('Library', 'LIB', 'University Library Services'),
('Registry', 'REG', 'University Registry and Administration'),
('Bursary', 'BUR', 'University Bursary Department'),
('Works and Physical Planning', 'WPP', 'Works and Physical Planning Department'),
('Security', 'SEC', 'University Security Services');

-- Insert Sample Staff (for testing)
INSERT INTO staff (staff_id, first_name, last_name, middle_name, gender, date_of_birth, 
    marital_status, address, phone, email, next_of_kin, qualification, department_id, 
    rank, employment_condition, date_recruited, basic_salary, staff_type, status) 
VALUES
('DU001', 'John', 'Adeyemi', 'Oluwaseun', 'Male', '1985-03-15', 'Married', 
    '12 University Road, Ibadan', '08012345678', 'john.adeyemi@dominion.edu.ng', 
    'Mrs. Sarah Adeyemi (Wife)', 'Ph.D Computer Science', 1, 'Senior Lecturer', 
    'Permanent', '2018-09-01', 850000.00, 'academic', 'active'),
('DU002', 'Mary', 'Okonkwo', 'Chidinma', 'Female', '1990-07-22', 'Single', 
    '45 Ring Road, Ibadan', '08023456789', 'mary.okonkwo@dominion.edu.ng', 
    'Mr. James Okonkwo (Father)', 'M.Sc Mathematics', 2, 'Assistant Lecturer', 
    'Permanent', '2020-01-15', 450000.00, 'academic', 'active'),
('DU003', 'Abdul', 'Ibrahim', 'Musa', 'Male', '1978-11-08', 'Married', 
    '78 Challenge Road, Ibadan', '08034567890', 'abdul.ibrahim@dominion.edu.ng', 
    'Mrs. Fatima Ibrahim (Wife)', 'Ph.D Physics', 3, 'Professor', 
    'Permanent', '2010-06-01', 1200000.00, 'academic', 'active'),
('DU004', 'Grace', 'Eze', 'Ngozi', 'Female', '1988-05-30', 'Married', 
    '23 Bodija Estate, Ibadan', '08045678901', 'grace.eze@dominion.edu.ng', 
    'Mr. Peter Eze (Husband)', 'M.Sc Chemistry', 4, 'Lecturer II', 
    'Permanent', '2019-03-10', 600000.00, 'academic', 'active'),
('DU005', 'Samuel', 'Ojo', 'Ayodele', 'Male', '1982-12-18', 'Married', 
    '56 Moniya Road, Ibadan', '08056789012', 'samuel.ojo@dominion.edu.ng', 
    'Mrs. Blessing Ojo (Wife)', 'B.Sc Business Administration', 9, 'Administrative Officer', 
    'Permanent', '2015-08-20', 400000.00, 'non_academic', 'active'),
('DU006', 'Fatima', 'Bello', 'Amina', 'Female', '1992-09-05', 'Single', 
    '89 Sango Road, Ibadan', '08067890123', 'fatima.bello@dominion.edu.ng', 
    'Alhaji Bello (Father)', 'B.Sc Accounting', 10, 'Account Officer', 
    'Contract', '2021-02-01', 350000.00, 'non_academic', 'active'),
('DU007', 'Emmanuel', 'Nwachukwu', 'Chukwudi', 'Male', '1975-04-12', 'Married', 
    '34 Jericho Road, Ibadan', '08078901234', 'emmanuel.nwachukwu@dominion.edu.ng', 
    'Mrs. Joy Nwachukwu (Wife)', 'Ph.D Law', 11, 'Dean of Law', 
    'Permanent', '2008-01-07', 1500000.00, 'academic', 'active'),
('DU008', 'Aisha', 'Mohammed', 'Zainab', 'Female', '1986-08-25', 'Divorced', 
    '67 Mokola Road, Ibadan', '08089012345', 'aisha.mohammed@dominion.edu.ng', 
    'Mr. Yusuf Mohammed (Brother)', 'M.Sc Nursing', 13, 'Senior Nursing Officer', 
    'Permanent', '2016-11-15', 550000.00, 'non_academic', 'active'),
('DU009', 'Olumide', 'Ajayi', 'Olamide', 'Male', '1995-01-20', 'Single', 
    '91 Ojoo Road, Ibadan', '08090123456', 'olumide.ajayi@dominion.edu.ng', 
    'Mrs. Ajayi (Mother)', 'B.Sc Library Science', 14, 'Library Assistant', 
    'Contract', '2022-06-01', 250000.00, 'non_academic', 'active'),
('DU010', 'Catherine', 'Obi', 'Nkechi', 'Female', '1980-10-03', 'Married', 
    '15 UI Road, Ibadan', '08101234567', 'catherine.obi@dominion.edu.ng', 
    'Dr. Obi (Husband)', 'Ph.D English', 6, 'Head of Department', 
    'Permanent', '2012-04-01', 950000.00, 'academic', 'active');

-- Update departments with HOD assignments
UPDATE departments SET hod_staff_id = 10 WHERE id = 6;
UPDATE departments SET hod_staff_id = 3 WHERE id = 3;
UPDATE departments SET hod_staff_id = 7 WHERE id = 11;

-- Insert Sample Users with hashed passwords
-- Password for all sample users: 'Password123!'
-- Hash generated using: password_hash('Password123!', PASSWORD_DEFAULT)

INSERT INTO users (username, email, password_hash, role_id, staff_id, status) VALUES
('admin', 'admin@dominion.edu.ng', '$2y$10$AvIKopp8Dbzrn3nx7zdfyuuBOIySyvKNd5meXoeY/pLZ769IDWtjy', 1, NULL, 'active'),
('hr_manager', 'hr@dominion.edu.ng', '$2y$10$AvIKopp8Dbzrn3nx7zdfyuuBOIySyvKNd5meXoeY/pLZ769IDWtjy', 2, NULL, 'active'),
('hod_english', 'hod.english@dominion.edu.ng', '$2y$10$AvIKopp8Dbzrn3nx7zdfyuuBOIySyvKNd5meXoeY/pLZ769IDWtjy', 3, 10, 'active'),
('hod_physics', 'hod.physics@dominion.edu.ng', '$2y$10$AvIKopp8Dbzrn3nx7zdfyuuBOIySyvKNd5meXoeY/pLZ769IDWtjy', 3, 3, 'active'),
('staff_001', 'john.adeyemi@dominion.edu.ng', '$2y$10$AvIKopp8Dbzrn3nx7zdfyuuBOIySyvKNd5meXoeY/pLZ769IDWtjy', 4, 1, 'active'),
('staff_002', 'mary.okonkwo@dominion.edu.ng', '$2y$10$AvIKopp8Dbzrn3nx7zdfyuuBOIySyvKNd5meXoeY/pLZ769IDWtjy', 4, 2, 'active'),
('staff_003', 'grace.eze@dominion.edu.ng', '$2y$10$AvIKopp8Dbzrn3nx7zdfyuuBOIySyvKNd5meXoeY/pLZ769IDWtjy', 4, 4, 'active'),
('staff_004', 'samuel.ojo@dominion.edu.ng', '$2y$10$AvIKopp8Dbzrn3nx7zdfyuuBOIySyvKNd5meXoeY/pLZ769IDWtjy', 4, 5, 'active'),
('staff_005', 'fatima.bello@dominion.edu.ng', '$2y$10$AvIKopp8Dbzrn3nx7zdfyuuBOIySyvKNd5meXoeY/pLZ769IDWtjy', 4, 6, 'active'),
('staff_006', 'aisha.mohammed@dominion.edu.ng', '$2y$10$AvIKopp8Dbzrn3nx7zdfyuuBOIySyvKNd5meXoeY/pLZ769IDWtjy', 4, 8, 'active');

-- Insert Sample Leave Applications
INSERT INTO leave_applications (staff_id, leave_type_id, start_date, end_date, total_days, reason, status, hod_comment, applied_at) VALUES
(1, 1, '2026-04-01', '2026-04-15', 15, 'Annual vacation with family', 'approved', 'Approved. Enjoy your leave.', '2026-02-15 10:30:00'),
(2, 3, '2026-03-20', '2026-03-22', 3, 'Family emergency', 'approved', 'Approved for 3 days.', '2026-03-10 14:15:00'),
(4, 2, '2026-03-15', '2026-03-19', 5, 'Medical treatment', 'pending', NULL, '2026-03-12 09:00:00'),
(5, 1, '2026-05-01', '2026-05-10', 10, 'Annual leave', 'pending', NULL, '2026-03-14 11:20:00'),
(6, 4, '2026-06-01', '2026-08-29', 90, 'Maternity leave - first child', 'approved', 'Approved. Wishing you safe delivery.', '2026-02-01 08:45:00');

-- Insert Sample Assessments
INSERT INTO assessments (staff_id, assessor_user_id, assessment_date, report, recommendation) VALUES
(1, 2, '2025-12-15', 'Dr. Adeyemi has demonstrated exceptional teaching abilities and research output. He has published 3 papers in reputable journals this year and consistently receives positive student feedback. His dedication to the department is commendable.', 'Recommend for promotion to Principal Lecturer. Consider for departmental committee leadership.'),
(2, 2, '2025-12-10', 'Miss Okonkwo is a promising young academic. She completed her Masters with distinction and shows great potential in her teaching. She needs more guidance on research publication but is eager to learn.', 'Recommend mentorship program. Support for PhD enrollment.'),
(3, 2, '2025-12-20', 'Prof. Ibrahim continues to be an asset to the university. His research grants have brought significant funding to the department. He successfully supervised 5 postgraduate students this year.', 'Recommend for Distinguished Professorship. Consider for Dean position.'),
(5, 2, '2025-11-30', 'Mr. Ojo performs his administrative duties efficiently. He has improved the filing system in his unit and shows good organizational skills.', 'Recommend for senior administrative position.'),
(10, 2, '2025-12-18', 'Dr. Obi has been an excellent HOD. Under her leadership, the English Department has grown significantly. She has secured two international collaborations and increased student enrollment by 15%.', 'Recommend for tenure. Consider for Deputy Vice-Chancellor position.');

-- Insert Sample Activity Logs
INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) VALUES
(1, 'LOGIN', NULL, NULL, 'User logged into the system', '127.0.0.1'),
(1, 'CREATE', 'users', 2, 'Created new user account: hr_manager', '127.0.0.1'),
(2, 'LOGIN', NULL, NULL, 'User logged into the system', '192.168.1.100'),
(2, 'CREATE', 'staff', 1, 'Registered new staff: John Adeyemi (DU001)', '192.168.1.100'),
(2, 'CREATE', 'staff', 2, 'Registered new staff: Mary Okonkwo (DU002)', '192.168.1.100'),
(3, 'LOGIN', NULL, NULL, 'User logged into the system', '192.168.1.101'),
(3, 'APPROVE_LEAVE', 'leave_applications', 1, 'Approved leave application for staff_id: 1', '192.168.1.101'),
(3, 'APPROVE_LEAVE', 'leave_applications', 2, 'Approved leave application for staff_id: 2', '192.168.1.101'),
(5, 'LOGIN', NULL, NULL, 'User logged into the system', '192.168.1.102'),
(5, 'APPLY_LEAVE', 'leave_applications', 1, 'Applied for Annual Leave', '192.168.1.102'),
(6, 'LOGIN', NULL, NULL, 'User logged into the system', '192.168.1.103'),
(6, 'APPLY_LEAVE', 'leave_applications', 2, 'Applied for Casual Leave', '192.168.1.103'),
(2, 'CREATE', 'assessments', 1, 'Added assessment for staff_id: 1', '192.168.1.100'),
(2, 'CREATE', 'assessments', 2, 'Added assessment for staff_id: 2', '192.168.1.100'),
(1, 'LOGOUT', NULL, NULL, 'User logged out of the system', '127.0.0.1');

-- Insert Sample Login Logs
INSERT INTO login_logs (user_id, login_time, logout_time, ip_address, user_agent) VALUES
(1, '2026-03-15 08:00:00', '2026-03-15 17:30:00', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0'),
(2, '2026-03-15 08:15:00', '2026-03-15 16:45:00', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0'),
(3, '2026-03-15 09:00:00', '2026-03-15 15:00:00', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1'),
(5, '2026-03-15 10:30:00', '2026-03-15 11:00:00', '192.168.1.102', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Firefox/121.0'),
(6, '2026-03-15 14:00:00', '2026-03-15 14:30:00', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0');

-- =====================================================
-- VIEWS FOR COMMON QUERIES
-- =====================================================

-- View: Staff with Department Info
CREATE VIEW vw_staff_department AS
SELECT s.*, d.department_name, d.department_code
FROM staff s
LEFT JOIN departments d ON s.department_id = d.id;

-- View: Leave Applications with Details
CREATE VIEW vw_leave_details AS
SELECT 
    la.*,
    s.staff_id as staff_code,
    CONCAT(s.first_name, ' ', s.last_name) as staff_name,
    s.department_id,
    d.department_name,
    lt.leave_name
FROM leave_applications la
JOIN staff s ON la.staff_id = s.id
LEFT JOIN departments d ON s.department_id = d.id
JOIN leave_types lt ON la.leave_type_id = lt.id;

-- View: Users with Roles
CREATE VIEW vw_users_roles AS
SELECT 
    u.*,
    r.role_name,
    CONCAT(s.first_name, ' ', s.last_name) as staff_name,
    s.staff_id as staff_code
FROM users u
JOIN roles r ON u.role_id = r.id
LEFT JOIN staff s ON u.staff_id = s.id;

-- View: Assessment with Staff and Assessor Info
CREATE VIEW vw_assessment_details AS
SELECT 
    a.*,
    CONCAT(s.first_name, ' ', s.last_name) as staff_name,
    s.staff_id as staff_code,
    u.username as assessor_username,
    CONCAT(staff_assessor.first_name, ' ', staff_assessor.last_name) as assessor_name
FROM assessments a
JOIN staff s ON a.staff_id = s.id
JOIN users u ON a.assessor_user_id = u.id
LEFT JOIN staff staff_assessor ON u.staff_id = staff_assessor.id;

-- =====================================================
-- STORED PROCEDURES (Optional but helpful)
-- =====================================================

DELIMITER //

-- Procedure: Get Department Staff Count
CREATE PROCEDURE sp_get_dept_staff_count(IN dept_id INT)
BEGIN
    SELECT COUNT(*) as staff_count 
    FROM staff 
    WHERE department_id = dept_id AND status = 'active';
END //

-- Procedure: Get Staff Leave Balance
CREATE PROCEDURE sp_get_staff_leave_balance(IN p_staff_id INT, IN p_leave_type_id INT, IN p_year INT)
BEGIN
    SELECT 
        lt.max_days as entitled_days,
        COALESCE(SUM(CASE WHEN status = 'approved' THEN total_days ELSE 0 END), 0) as used_days,
        lt.max_days - COALESCE(SUM(CASE WHEN status = 'approved' THEN total_days ELSE 0 END), 0) as remaining_days
    FROM leave_types lt
    LEFT JOIN leave_applications la ON lt.id = la.leave_type_id 
        AND la.staff_id = p_staff_id 
        AND YEAR(la.start_date) = p_year
    WHERE lt.id = p_leave_type_id
    GROUP BY lt.id, lt.max_days;
END //

-- Procedure: Log Activity
CREATE PROCEDURE sp_log_activity(
    IN p_user_id INT,
    IN p_action VARCHAR(100),
    IN p_table_name VARCHAR(50),
    IN p_record_id INT,
    IN p_description TEXT,
    IN p_ip_address VARCHAR(45)
)
BEGIN
    INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address)
    VALUES (p_user_id, p_action, p_table_name, p_record_id, p_description, p_ip_address);
END //

DELIMITER ;

-- =====================================================
-- END OF DATABASE SCHEMA
-- =====================================================
-- 
-- DEFAULT LOGIN CREDENTIALS FOR TESTING:
-- 
-- Admin:      username: admin          password: Password123!
-- HR:         username: hr_manager     password: Password123!
-- HOD:        username: hod_english    password: Password123!
--             username: hod_physics    password: Password123!
-- Staff:      username: staff_001      password: Password123!
--             username: staff_002      password: Password123!
--             (and so on...)
-- 
-- =====================================================