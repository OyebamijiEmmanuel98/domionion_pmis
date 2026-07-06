<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * HEADER INCLUDE FILE
 * =====================================================
 * 
 * This file contains the HTML header and common head elements.
 * Include this at the beginning of every page.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Prevent direct access
if (!defined('PMIS_SYSTEM')) {
    define('PMIS_SYSTEM', true);
}

// Get current page info for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Get user info for display
$currentUserName = getCurrentUsername() ?? 'Guest';
$currentUserRole = getRoleDisplayName(getCurrentUserRole() ?? 'Unknown');
$currentUserInitials = getInitials($currentUserName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Page Title -->
    <title><?php echo isset($pageTitle) ? escapeOutput($pageTitle) . ' - ' : ''; ?>PMIS - Dominion University</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo getBaseUrl(); ?>assets/images/favicon.ico">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/style.css">
    
    <!-- Page-specific styles -->
    <?php if (isset($pageStyles)): ?>
    <style><?php echo $pageStyles; ?></style>
    <?php endif; ?>
    
    <!-- Custom styles for this page -->
    <?php if (isset($customCSS)): ?>
    <link rel="stylesheet" href="<?php echo getBaseUrl() . $customCSS; ?>">
    <?php endif; ?>
</head>
<body>
    <div class="app-container">
