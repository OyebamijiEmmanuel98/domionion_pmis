-- =====================================================
-- DOMINION UNIVERSITY, IBADAN
-- PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
-- POST FIELD MIGRATION
-- =====================================================
-- Adds a 'post' column to the staff table
-- =====================================================

USE pmis_dominion;

-- Add post column after rank
ALTER TABLE staff 
ADD COLUMN post VARCHAR(200) NULL COMMENT 'Staff post/designation e.g. Lecturer, Admin Staff, Warden' 
AFTER rank;
