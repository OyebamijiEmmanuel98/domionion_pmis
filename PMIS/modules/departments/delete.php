<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * DELETE DEPARTMENT
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

// Require Admin access
requireAdmin();

$deptId = $_GET['id'] ?? 0;

// Get department data
try {
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([$deptId]);
    $department = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Delete Department Error: " . $e->getMessage());
    $department = false;
}

if (!$department) {
    setFlashMessage('error', 'Department not found');
    header("Location: list.php");
    exit();
}

// Check if department has staff
$staffCheck = $pdo->prepare("SELECT COUNT(*) as total FROM staff WHERE department_id = ?");
$staffCheck->execute([$deptId]);
$staffCount = $staffCheck->fetch()['total'];

if ($staffCount > 0) {
    setFlashMessage('error', 'Cannot delete department "' . $department['department_name'] . '" because it has ' . $staffCount . ' staff member(s) assigned. Please reassign or remove the staff first.');
    header("Location: list.php");
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->execute([$deptId]);
    
    // Log activity
    logActivity('DELETE', 'departments', $deptId, 'Deleted department: ' . $department['department_name']);
    
    setFlashMessage('success', 'Department "' . $department['department_name'] . '" has been deleted');
    
} catch (PDOException $e) {
    error_log("Delete Department Error: " . $e->getMessage());
    setFlashMessage('error', 'Error deleting department. It may have related records.');
}

header("Location: list.php");
exit();
