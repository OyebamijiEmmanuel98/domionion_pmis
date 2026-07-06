-- =====================================================
-- DOMINION UNIVERSITY, IBADAN
-- PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
-- LEAVE MODULE ENHANCEMENT MIGRATION
-- =====================================================
-- Adds new columns to leave_applications for multi-level
-- approval workflow, handover details, and document uploads
-- =====================================================

USE pmis_dominion;

-- Add new columns for the enhanced leave application form
ALTER TABLE leave_applications 
ADD COLUMN reliever_name VARCHAR(200) NULL COMMENT 'Person covering duties during leave' AFTER reason,
ADD COLUMN is_applicant_hod ENUM('Yes','No') DEFAULT 'No' COMMENT 'Is the applicant an HOD?' AFTER reliever_name,
ADD COLUMN acting_hod_name VARCHAR(200) NULL COMMENT 'Acting HOD name if applicant is HOD' AFTER is_applicant_hod,
ADD COLUMN acting_hod_most_senior ENUM('Yes','No') NULL COMMENT 'Is Acting HOD the most senior?' AFTER acting_hod_name,
ADD COLUMN applicant_signature VARCHAR(200) NULL COMMENT 'Typed name as signature' AFTER acting_hod_most_senior,
ADD COLUMN supporting_doc VARCHAR(500) NULL COMMENT 'Path to uploaded supporting document' AFTER applicant_signature,
ADD COLUMN hod_status ENUM('pending','approved','rejected') DEFAULT 'pending' COMMENT 'HOD approval status' AFTER supporting_doc,
ADD COLUMN dean_comment TEXT NULL COMMENT 'Dean/Registrar review comment' AFTER hod_status,
ADD COLUMN dean_status ENUM('pending','approved','rejected') DEFAULT 'pending' COMMENT 'Dean approval status' AFTER dean_comment,
ADD COLUMN dean_reviewed_by INT NULL COMMENT 'Dean/Registrar who reviewed' AFTER dean_status,
ADD COLUMN dean_reviewed_at TIMESTAMP NULL COMMENT 'When dean reviewed' AFTER dean_reviewed_by,
ADD COLUMN vc_status ENUM('pending','approved','rejected') DEFAULT 'pending' COMMENT 'VC final approval status' AFTER dean_reviewed_at,
ADD COLUMN vc_signature VARCHAR(200) NULL COMMENT 'VC typed signature' AFTER vc_status,
ADD COLUMN vc_reviewed_at TIMESTAMP NULL COMMENT 'When VC made final decision' AFTER vc_signature;

-- Update existing status column to include new workflow states
ALTER TABLE leave_applications 
MODIFY COLUMN status ENUM('pending','hod_approved','hod_rejected','dean_approved','dean_rejected','approved','rejected') DEFAULT 'pending';
