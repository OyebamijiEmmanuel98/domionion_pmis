<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * LOGOUT PAGE
 * =====================================================
 * 
 * This page handles user logout.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Include required files
require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Log the logout activity before clearing session
if (isLoggedIn()) {
    logActivity('LOGOUT', null, null, 'User logged out of the system');
}

// Perform logout
logoutUser();

// Redirect to login page with logout message
header("Location: login.php?logout=1");
exit();
