<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * INDEX / LANDING PAGE
 * =====================================================
 * 
 * This is the landing page that redirects to login or dashboard.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Include required files
require_once(__DIR__ . '/../config/db.php');
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to appropriate dashboard
    redirectToDashboard();
} else {
    // Redirect to login page
    header("Location: login.php");
    exit();
}
