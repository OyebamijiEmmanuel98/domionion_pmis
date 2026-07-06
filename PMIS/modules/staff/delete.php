<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * DELETE STAFF
 * =====================================================
 * 
 * @author Final Year Project
 * @version 1.0
 */

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/role_check.php';

// Require HR or Admin access
requireHR();

$staffRecordId = $_GET['id'] ?? 0;

// Get staff data
try {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
    $stmt->execute([$staffRecordId]);
    $staffRecord = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Delete Staff Error: " . $e->getMessage());
    $staffRecord = false;
}

if (!$staffRecord) {
    setFlashMessage('error', 'Staff record not found');
    header("Location: list.php");
    exit();
}

try {
    // Check if staff has a linked user account
    $userCheck = $pdo->prepare("SELECT id FROM users WHERE staff_id = ?");
    $userCheck->execute([$staffRecordId]);
    $linkedUser = $userCheck->fetch();
    
    if ($linkedUser) {
        // Unlink user account first (set staff_id to NULL)
        $unlinkStmt = $pdo->prepare("UPDATE users SET staff_id = NULL WHERE staff_id = ?");
        $unlinkStmt->execute([$staffRecordId]);
    }
    
    // Delete related leave applications
    $deleteLeave = $pdo->prepare("DELETE FROM leave_applications WHERE staff_id = ?");
    $deleteLeave->execute([$staffRecordId]);
    
    // Delete related assessments
    $deleteAssessments = $pdo->prepare("DELETE FROM assessments WHERE staff_id = ?");
    $deleteAssessments->execute([$staffRecordId]);
    
    // Delete the staff record
    $stmt = $pdo->prepare("DELETE FROM staff WHERE id = ?");
    $stmt->execute([$staffRecordId]);
    
    // Log activity
    logActivity('DELETE', 'staff', $staffRecordId, 'Deleted staff: ' . $staffRecord['staff_id'] . ' - ' . $staffRecord['first_name'] . ' ' . $staffRecord['last_name']);
    
    setFlashMessage('success', 'Staff record for ' . $staffRecord['first_name'] . ' ' . $staffRecord['last_name'] . ' has been deleted');
    
} catch (PDOException $e) {
    error_log("Delete Staff Error: " . $e->getMessage());
    setFlashMessage('error', 'Error deleting staff record. The staff may have related records that prevent deletion.');
}

header("Location: list.php");
exit();
