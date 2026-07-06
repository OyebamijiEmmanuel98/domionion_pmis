-- Revert script for changes made today
USE pmis_dominion;

-- 5. Delete test leave applications using Short Leave, then remove Short Leave type
DELETE FROM `leave_applications` WHERE `leave_type_id` = (SELECT `id` FROM `leave_types` WHERE `leave_name` = 'Short Leave of Absence');
DELETE FROM `leave_types` WHERE `leave_name` = 'Short Leave of Absence';
