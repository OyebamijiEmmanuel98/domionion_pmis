-- =====================================================
-- DOMINION UNIVERSITY, IBADAN
-- PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
-- ACADEMIC STAFF APPRAISAL MIGRATION
-- =====================================================
-- Tables for the Annual Appraisal for Academic Staff
-- 6 Parts: A (Staff), B (HOD), C (Staff Comments),
--          D (Dean), E (HR), F (A&P Committee)
-- =====================================================

USE pmis_dominion;

-- =====================================================
-- MASTER TABLE: ACADEMIC_EVALUATIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS academic_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    period_from DATE NOT NULL,
    period_to DATE NOT NULL,
    status ENUM(
        'part_a_pending',
        'part_b_pending',
        'part_c_pending',
        'part_d_pending',
        'part_e_pending',
        'part_f_pending',
        'completed'
    ) DEFAULT 'part_a_pending',

    -- PART A: Personal/Employment Details (Staff fills)
    title VARCHAR(20),
    date_of_birth DATE,
    age INT,
    marital_status VARCHAR(50),
    faculty VARCHAR(255),
    department VARCHAR(255),
    date_first_appointment DATE,
    grade_first_appointment VARCHAR(200),
    present_rank VARCHAR(200),
    date_present_rank DATE,
    next_below_rank VARCHAR(200),
    
    -- A: Academic qualifications (stored in ae_qualifications)
    -- A: Teaching info
    teaching_summary TEXT COMMENT 'Summary of teaching activities during period',
    
    -- A: Research
    research_summary TEXT COMMENT 'Summary of research activities',
    
    -- A: Admin & Community Service
    admin_duties TEXT,
    community_service TEXT,
    
    -- A: Signature
    part_a_signed_at TIMESTAMP NULL,
    part_a_signed_by INT NULL,

    -- PART B: HOD Assessment
    hod_assessment_summary TEXT,
    teaching_score INT COMMENT 'Score out of 100',
    research_score INT COMMENT 'Score out of 100',
    admin_score INT COMMENT 'Score out of 100',
    community_score INT COMMENT 'Score out of 100',
    overall_score DECIMAL(5,2),
    overall_grade VARCHAR(5) COMMENT 'A, B, C, D, E',
    hod_recommendation TEXT,
    
    part_b_signed_at TIMESTAMP NULL,
    part_b_signed_by INT NULL,

    -- PART C: Staff Comments/Response
    staff_comments TEXT,
    staff_agrees ENUM('Yes','No','Partially'),
    
    part_c_signed_at TIMESTAMP NULL,

    -- PART D: Dean of Faculty
    dean_comments TEXT,
    dean_recommendation ENUM('promotion','increment','maintain','probation'),
    dean_promotion_to VARCHAR(200),
    dean_promotion_date DATE,
    
    part_d_signed_at TIMESTAMP NULL,
    part_d_signed_by INT NULL,
    part_d_signer_name VARCHAR(200),

    -- PART E: HR Officer
    hr_score_year1 DECIMAL(5,2),
    hr_score_year2 DECIMAL(5,2),
    hr_score_year3 DECIMAL(5,2),
    hr_notes TEXT,
    
    part_e_signed_at TIMESTAMP NULL,
    part_e_signed_by INT NULL,

    -- PART F: A&P Committee
    committee_decision ENUM('promote','do_not_promote','defer','other'),
    committee_decision_details TEXT,
    committee_effective_date DATE,
    
    part_f_signed_at TIMESTAMP NULL,
    part_f_signed_by INT NULL,
    part_f_signer_name VARCHAR(200),

    -- Metadata
    initiated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (part_a_signed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (part_b_signed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (part_d_signed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (part_e_signed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (part_f_signed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX idx_ae_staff (staff_id),
    INDEX idx_ae_status (status),
    INDEX idx_ae_period (period_from, period_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- A: Qualifications
-- =====================================================
CREATE TABLE IF NOT EXISTS ae_qualifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    degree VARCHAR(255),
    class_grade VARCHAR(100),
    institution VARCHAR(255),
    year_obtained YEAR,
    FOREIGN KEY (evaluation_id) REFERENCES academic_evaluations(id) ON DELETE CASCADE,
    INDEX idx_aeq_eval (evaluation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- A: Courses Taught
-- =====================================================
CREATE TABLE IF NOT EXISTS ae_courses_taught (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    course_code VARCHAR(20),
    course_title VARCHAR(255),
    level VARCHAR(50),
    semester VARCHAR(20),
    FOREIGN KEY (evaluation_id) REFERENCES academic_evaluations(id) ON DELETE CASCADE,
    INDEX idx_aect_eval (evaluation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- A: Publications
-- =====================================================
CREATE TABLE IF NOT EXISTS ae_publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    pub_type ENUM('journal','conference','book','chapter','other'),
    title VARCHAR(500),
    authors TEXT,
    journal_name VARCHAR(255),
    year_published YEAR,
    FOREIGN KEY (evaluation_id) REFERENCES academic_evaluations(id) ON DELETE CASCADE,
    INDEX idx_aep_eval (evaluation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- A: Graduate Students Supervised
-- =====================================================
CREATE TABLE IF NOT EXISTS ae_graduate_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    student_name VARCHAR(200),
    programme VARCHAR(100) COMMENT 'MSc, PhD, etc.',
    thesis_title VARCHAR(500),
    status ENUM('completed','ongoing'),
    FOREIGN KEY (evaluation_id) REFERENCES academic_evaluations(id) ON DELETE CASCADE,
    INDEX idx_aegs_eval (evaluation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
