<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * SESSION MANAGEMENT FILE
 * =====================================================
 * 
 * This file handles session initialization and management.
 * Include this file at the beginning of every protected page.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters before starting session
    
    // Session cookie parameters for security
    // These settings help prevent session hijacking
    session_set_cookie_params([
        'lifetime' => 3600,           // Session expires after 1 hour of inactivity
        'path' => '/',                // Available across entire domain
        'domain' => '',               // Current domain only
        'secure' => false,            // Set to true if using HTTPS
        'httponly' => true,           // Prevent JavaScript access to session cookie
        'samesite' => 'Lax'           // CSRF protection
    ]);
    
    // Start the session
    session_start();
    
    // Regenerate session ID periodically to prevent fixation attacks
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        // Regenerate session ID every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['username']) && 
           isset($_SESSION['role']) &&
           !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user ID
 * 
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current logged-in username
 * 
 * @return string|null Username if logged in, null otherwise
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Get current user role
 * 
 * @return string|null Role name if logged in, null otherwise
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user's staff ID (if linked to staff record)
 * 
 * @return int|null Staff ID if linked, null otherwise
 */
function getCurrentStaffId() {
    return $_SESSION['staff_id'] ?? null;
}

/**
 * Get current user's department ID (if HOD or staff)
 * 
 * @return int|null Department ID if available, null otherwise
 */
function getCurrentUserDepartmentId() {
    return $_SESSION['department_id'] ?? null;
}

/**
 * Set session data after successful login
 * 
 * @param array $userData Array containing user information
 */
function setUserSession($userData) {
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['email'] = $userData['email'] ?? null;
    $_SESSION['role_id'] = $userData['role_id'];
    $_SESSION['role'] = $userData['role_name'];
    $_SESSION['staff_id'] = $userData['staff_id'] ?? null;
    $_SESSION['department_id'] = $userData['department_id'] ?? null;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['created'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Update last activity timestamp
 * Call this on every page load to track user activity
 */
function updateLastActivity() {
    $_SESSION['last_activity'] = time();
}

/**
 * Check for session timeout (inactivity)
 * 
 * @param int $timeout Timeout in seconds (default: 3600 = 1 hour)
 * @return bool True if session has timed out, false otherwise
 */
function isSessionTimedOut($timeout = 3600) {
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        return $inactive > $timeout;
    }
    return false;
}

/**
 * Clear all session data (for logout)
 */
function clearSession() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Redirect to login page if not logged in
 * Use this at the beginning of protected pages
 * 
 * @param string $message Optional message to display on login page
 */
function requireLogin($message = '') {
    if (!isLoggedIn()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
        $msgParam = !empty($message) ? '&message=' . urlencode($message) : '';
        header("Location: /PMIS/login.php?redirect={$redirect}{$msgParam}");
        exit();
    }
    
    // Check for session timeout
    if (isSessionTimedOut()) {
        clearSession();
        header("Location: /PMIS/login.php?timeout=1");
        exit();
    }
    
    // Update last activity
    updateLastActivity();
}

/**
 * Redirect to dashboard based on user role
 */
function redirectToDashboard() {
    $role = getCurrentUserRole();
    
    switch ($role) {
        case 'admin':
            header("Location: /PMIS/admin/dashboard.php");
            break;
        case 'hr':
            header("Location: /PMIS/hr/dashboard.php");
            break;
        case 'hod':
            header("Location: /PMIS/hod/dashboard.php");
            break;
        case 'staff':
            header("Location: /PMIS/staff/dashboard.php");
            break;
        default:
            header("Location: /PMIS/dashboard.php");
    }
    exit();
}
