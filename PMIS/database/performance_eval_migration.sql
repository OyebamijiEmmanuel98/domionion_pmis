-- =====================================================
-- DOMINION UNIVERSITY, IBADAN
-- PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
-- PERFORMANCE EVALUATION MIGRATION
-- =====================================================
-- Run this migration to add tables for
-- Annual Performance Evaluation Report (Non-Academic Staff)
-- =====================================================

USE pmis_dominion;

-- =====================================================
-- TABLE: PERFORMANCE_EVALUATIONS
-- Master record for each evaluation cycle
-- =====================================================
CREATE TABLE IF NOT EXISTS performance_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    period_from DATE NOT NULL,
    period_to DATE NOT NULL,
    status ENUM(
        'part_a_pending',
        'part_b_pending',
        'part_b_staff_pending',
        'part_c_pending',
        'part_d_pending',
        'completed'
    ) DEFAULT 'part_a_pending',

    -- PART A fields (completed by staff)
    title VARCHAR(20),
    date_of_birth DATE,
    age INT,
    marital_status VARCHAR(50),
    college_dept_unit VARCHAR(255),
    date_first_appointment DATE,
    grade_first_appointment VARCHAR(200),
    current_grade_status VARCHAR(200),
    date_current_grade DATE,
    appointment_confirmed ENUM('Yes', 'No'),
    date_of_confirmation DATE,
    acting_appointment TEXT,
    present_salary VARCHAR(100),
    grade_level_step VARCHAR(100),
    courses_conferences TEXT,

    -- Part A: Q13 - Experience
    job_description_prior TEXT,
    main_duties_during_period TEXT,
    adhoc_duties TEXT,

    -- Part A: Q14-16
    other_activities_university TEXT,
    other_activities_outside TEXT,
    publications TEXT,

    part_a_signed_at TIMESTAMP NULL,
    part_a_signed_by INT NULL,

    -- PART B Section A fields (completed by supervisor/HOD)
    sick_leave_with_cert INT DEFAULT 0,
    sick_leave_without_cert INT DEFAULT 0,
    sanctions_details TEXT,
    main_work_performed TEXT,
    training_recommended TEXT,
    other_useful_info TEXT,
    overall_performance_rating ENUM('A', 'B', 'C', 'D', 'E'),
    overall_performance_score DECIMAL(3,2),
    
    part_b_supervisor_name VARCHAR(200),
    part_b_signed_at TIMESTAMP NULL,
    part_b_signed_by INT NULL,

    -- PART B Section B fields (staff acknowledgment)
    staff_comments TEXT,
    part_b_staff_signed_at TIMESTAMP NULL,

    -- PART C fields (HR Officer)
    avg_score_year1 DECIMAL(3,2),
    avg_score_year2 DECIMAL(3,2),
    avg_score_year3 DECIMAL(3,2),
    
    part_c_signed_at TIMESTAMP NULL,
    part_c_signed_by INT NULL,

    -- PART D fields (HOD / Departmental Committee)
    training_needs TEXT,
    training_how_met TEXT,

    -- D: Recommendation for Annual Increment
    increment_recommendation ENUM('grant', 'do_not_grant', 'delay'),
    increment_reasons TEXT,

    -- D: Recommendation for Confirmation
    confirmation_recommendation ENUM('confirm_retiring_age', 'extend_six_months', 'terminate'),
    confirmation_effective_date DATE,

    -- D: Consideration for Promotion
    promotion_recommendation ENUM('normal', 'accelerated', 'not_recommended'),
    promotion_to_grade VARCHAR(200),
    promotion_date DATE,
    promotion_reasons TEXT,

    years_served_under_hod DECIMAL(4,1),

    part_d_signed_at TIMESTAMP NULL,
    part_d_signed_by INT NULL,
    part_d_signer_name VARCHAR(200),

    initiated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (part_a_signed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (part_b_signed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (part_c_signed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (part_d_signed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX idx_pe_staff (staff_id),
    INDEX idx_pe_status (status),
    INDEX idx_pe_period (period_from, period_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: PE_QUALIFICATIONS_ACADEMIC
-- Part A Q12(a): Academic qualifications
-- =====================================================
CREATE TABLE IF NOT EXISTS pe_qualifications_academic (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    university_degree VARCHAR(255),
    class_grade VARCHAR(100),
    institution VARCHAR(255),
    date_of_award DATE,

    FOREIGN KEY (evaluation_id) REFERENCES performance_evaluations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_peqa_eval (evaluation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: PE_QUALIFICATIONS_PROFESSIONAL
-- Part A Q12(b): Professional qualifications
-- =====================================================
CREATE TABLE IF NOT EXISTS pe_qualifications_professional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    qualification VARCHAR(255),
    awarding_body VARCHAR(255),
    date_of_award DATE,

    FOREIGN KEY (evaluation_id) REFERENCES performance_evaluations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_peqp_eval (evaluation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: PE_PERFORMANCE_RATINGS
-- Part B Section A: 17 performance aspect ratings
-- =====================================================
CREATE TABLE IF NOT EXISTS pe_performance_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    aspect_key VARCHAR(5) NOT NULL COMMENT 'a through q',
    aspect_name VARCHAR(200) NOT NULL,
    rating INT NOT NULL COMMENT '1=E(Unsatisfactory) to 5=A(Outstanding)',

    FOREIGN KEY (evaluation_id) REFERENCES performance_evaluations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_pepr_eval (evaluation_id),
    UNIQUE KEY uk_eval_aspect (evaluation_id, aspect_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
