<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * ROLE-BASED ACCESS CONTROL FILE
 * =====================================================
 * 
 * This file contains role checking and access control functions.
 * Include this file to protect pages based on user roles.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Include required files
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';

/**
 * =====================================================
 * ROLE VERIFICATION FUNCTIONS
 * These functions check if current user has required role
 * and redirect if not authorized
 * =====================================================
 */

/**
 * Ensure user is an Admin
 * Redirects to access denied if not
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . getBaseUrl() . "access_denied.php");
        exit();
    }
}

/**
 * Ensure user is HR or Admin
 * Redirects to access denied if not
 */
function requireHR() {
    requireLogin();
    if (!isHR() && !isAdmin()) {
        header("Location: " . getBaseUrl() . "access_denied.php");
        exit();
    }
}

/**
 * Ensure user is HOD, HR, or Admin
 * Redirects to access denied if not
 */
function requireHOD() {
    requireLogin();
    if (!isHOD() && !isHR() && !isAdmin()) {
        header("Location: " . getBaseUrl() . "access_denied.php");
        exit();
    }
}

/**
 * Ensure user is Staff (any type)
 * Redirects to access denied if not
 */
function requireStaff() {
    requireLogin();
    // Staff, HOD, HR, and Admin are all considered staff
    if (!isLoggedIn()) {
        header("Location: " . getBaseUrl() . "access_denied.php");
        exit();
    }
}

/**
 * =====================================================
 * PERMISSION CHECK FUNCTIONS
 * These functions check specific permissions without redirecting
 * =====================================================
 */

/**
 * Check if user can manage staff records
 * 
 * @return bool True if user can manage staff
 */
function canManageStaff() {
    return isAdmin() || isHR();
}

/**
 * Check if user can view all staff
 * 
 * @return bool True if user can view all staff
 */
function canViewAllStaff() {
    return isAdmin() || isHR();
}

/**
 * Check if user can view department staff
 * 
 * @param int $departmentId Department ID to check
 * @return bool True if user can view department staff
 */
function canViewDepartmentStaff($departmentId) {
    if (isAdmin() || isHR()) {
        return true;
    }
    
    if (isHOD()) {
        $userDeptId = getCurrentUserDepartmentId();
        return $userDeptId == $departmentId;
    }
    
    return false;
}

/**
 * Check if user can edit staff record
 * 
 * @param int $staffId Staff ID to check
 * @return bool True if user can edit
 */
function canEditStaff($staffId) {
    // Admin and HR can edit any staff
    if (isAdmin() || isHR()) {
        return true;
    }
    
    // Staff can only edit their own limited fields (handled separately)
    $currentStaffId = getCurrentStaffId();
    return $currentStaffId == $staffId;
}

/**
 * Check if user can manage users
 * 
 * @return bool True if user can manage users
 */
function canManageUsers() {
    return isAdmin();
}

/**
 * Check if user can manage departments
 * 
 * @return bool True if user can manage departments
 */
function canManageDepartments() {
    return isAdmin() || isHR();
}

/**
 * Check if user can approve leave
 * 
 * @param int $staffDepartmentId Department ID of staff applying for leave
 * @return bool True if user can approve
 */
function canApproveLeave($staffDepartmentId = null) {
    // HOD can approve for their department
    if (isHOD()) {
        if ($staffDepartmentId === null) {
            return true;
        }
        $userDeptId = getCurrentUserDepartmentId();
        return $userDeptId == $staffDepartmentId;
    }
    
    // HR and Admin can approve any leave
    return isHR() || isAdmin();
}

/**
 * Check if user can view leave application
 * 
 * @param int $staffId Staff ID who applied for leave
 * @param int $staffDepartmentId Department ID of staff
 * @return bool True if user can view
 */
function canViewLeave($staffId, $staffDepartmentId) {
    // User can view their own leave
    if (getCurrentStaffId() == $staffId) {
        return true;
    }
    
    // Admin and HR can view all
    if (isAdmin() || isHR()) {
        return true;
    }
    
    // HOD can view department leave
    if (isHOD()) {
        $userDeptId = getCurrentUserDepartmentId();
        return $userDeptId == $staffDepartmentId;
    }
    
    return false;
}

/**
 * Check if user can manage leave types
 * 
 * @return bool True if user can manage leave types
 */
function canManageLeaveTypes() {
    return isAdmin() || isHR();
}

/**
 * Check if user can add assessments
 * 
 * @return bool True if user can add assessments
 */
function canAddAssessment() {
    return isAdmin() || isHR() || isHOD();
}

/**
 * Check if user can view all assessments
 * 
 * @return bool True if user can view all assessments
 */
function canViewAllAssessments() {
    return isAdmin() || isHR();
}

/**
 * Check if user can generate reports
 * 
 * @return bool True if user can generate reports
 */
function canGenerateReports() {
    return isAdmin() || isHR() || isHOD();
}

/**
 * Check if user can view logs
 * 
 * @return bool True if user can view logs
 */
function canViewLogs() {
    return isAdmin();
}

/**
 * Check if user can view system settings
 * 
 * @return bool True if user can view settings
 */
function canViewSettings() {
    return isAdmin();
}

/**
 * =====================================================
 * HELPER FUNCTIONS
 * =====================================================
 */

/**
 * Get base URL for the application
 * 
 * @return string Base URL
 */
function getBaseUrl() {
    // Detect how many levels deep we are from the PMIS root
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Find the position of /pmis/ in the script path (case-insensitive)
    $pmisPos = stripos($scriptName, '/pmis/');
    if ($pmisPos !== false) {
        // Get the path after /pmis/
        $relativePath = substr($scriptName, $pmisPos + 6); // +6 for '/pmis/'
        // Count subdirectory levels
        $depth = substr_count(dirname($relativePath), '/');
        if (dirname($relativePath) !== '.' && dirname($relativePath) !== '\\' && dirname($relativePath) !== '/') {
            $depth += 1;
        }
        
        if ($depth <= 0) {
            return '';
        }
        
        return str_repeat('../', $depth);
    }
    
    return '';
}

/**
 * Get dashboard URL for current user
 * 
 * @return string Dashboard URL
 */
function getUserDashboardUrl() {
    $role = getCurrentUserRole();
    $base = getBaseUrl();
    
    switch ($role) {
        case 'admin':
            return $base . 'admin/dashboard.php';
        case 'hr':
            return $base . 'hr/dashboard.php';
        case 'hod':
            return $base . 'hod/dashboard.php';
        case 'staff':
            return $base . 'staff/dashboard.php';
        default:
            return $base . 'dashboard.php';
    }
}

/**
 * Get role display name
 * 
 * @param string $role Role name
 * @return string Display name
 */
function getRoleDisplayName($role) {
    $names = [
        'admin' => 'System Administrator',
        'hr' => 'Human Resources',
        'hod' => 'Head of Department',
        'staff' => 'Staff Member'
    ];
    
    return $names[$role] ?? ucfirst($role);
}

/**
 * Get role badge class for styling
 * 
 * @param string $role Role name
 * @return string CSS class
 */
function getRoleBadgeClass($role) {
    $classes = [
        'admin' => 'badge-admin',
        'hr' => 'badge-hr',
        'hod' => 'badge-hod',
        'staff' => 'badge-staff'
    ];
    
    return $classes[$role] ?? 'badge-default';
}

/**
 * Get status badge class for styling
 * 
 * @param string $status Status value
 * @return string CSS class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'active' => 'badge-success',
        'inactive' => 'badge-danger',
        'pending' => 'badge-warning',
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        'suspended' => 'badge-warning',
        'hod_approved' => 'badge-info',
        'hod_rejected' => 'badge-danger',
        'dean_approved' => 'badge-info',
        'dean_rejected' => 'badge-danger'
    ];
    
    return $classes[$status] ?? 'badge-default';
}
