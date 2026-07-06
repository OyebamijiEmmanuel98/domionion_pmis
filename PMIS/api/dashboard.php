<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * MAIN DASHBOARD REDIRECT
 * =====================================================
 * 
 * This page redirects users to their role-specific dashboard.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Include required files
require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require login
requireLogin();

// Redirect to role-specific dashboard
redirectToDashboard();
