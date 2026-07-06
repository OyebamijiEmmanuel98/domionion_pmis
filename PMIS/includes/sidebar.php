<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * SIDEBAR NAVIGATION FILE
 * =====================================================
 * 
 * This file contains the sidebar navigation menu.
 * It dynamically shows menu items based on user role.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Get current user role
$userRole = getCurrentUserRole();
$baseUrl = getBaseUrl();

// Helper function to check if menu item is active
function isMenuActive($pages, $dirs = []) {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));
    
    if (in_array($currentPage, $pages)) {
        return true;
    }
    
    if (in_array($currentDir, $dirs)) {
        return true;
    }
    
    return false;
}
?>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <!-- Sidebar Header / Logo -->
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon"><img src="<?php echo $baseUrl; ?>assets/images/logo.png" alt="DU Logo" style="width: 40px; height: 40px; object-fit: contain;"></div>
            <div class="logo-text">
                Dominion University
                <small>Personnel Management System</small>
            </div>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav>
        <ul class="nav-menu">
            
            <!-- Dashboard - Available to all roles -->
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>dashboard.php" 
                   class="nav-link <?php echo isMenuActive(['dashboard', 'index']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            
            <?php if (isAdmin()): ?>
            <!-- Admin Menu Items -->
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/users/list.php" 
                   class="nav-link <?php echo isMenuActive([], ['users']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
                    <span class="nav-text">User Management</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/logs/activity.php" 
                   class="nav-link <?php echo isMenuActive(['activity', 'login_logs'], ['logs']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-clipboard-list"></i></span>
                    <span class="nav-text">System Logs</span>
                </a>
            </li>
            
            <?php endif; ?>
            
            <?php if (isAdmin() || isHR()): ?>
            <!-- HR / Admin Menu Items -->
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/staff/list.php" 
                   class="nav-link <?php echo isMenuActive([], ['staff']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-user-tie"></i></span>
                    <span class="nav-text">Staff Management</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/departments/list.php" 
                   class="nav-link <?php echo isMenuActive([], ['departments']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-building"></i></span>
                    <span class="nav-text">Departments</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/leave/types.php" 
                   class="nav-link <?php echo isMenuActive(['types'], ['leave']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-calendar-alt"></i></span>
                    <span class="nav-text">Leave Types</span>
                </a>
            </li>
            

            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/assessments/performance_eval/list.php" 
                   class="nav-link <?php echo isMenuActive([], ['performance_eval']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-clipboard-list"></i></span>
                    <span class="nav-text">Performance Evaluation</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/reports/index.php" 
                   class="nav-link <?php echo isMenuActive(['index'], ['reports']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
                    <span class="nav-text">Reports</span>
                </a>
            </li>
            
            <?php endif; ?>
            
            <?php if (isHOD()): ?>
            <!-- HOD Menu Items -->
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/staff/department.php" 
                   class="nav-link <?php echo isMenuActive(['department'], ['staff']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-users"></i></span>
                    <span class="nav-text">Department Staff</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/leave/review.php" 
                   class="nav-link <?php echo isMenuActive(['review'], ['leave']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-check-circle"></i></span>
                    <span class="nav-text">Review Leave</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/reports/department.php" 
                   class="nav-link <?php echo isMenuActive(['department'], ['reports']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
                    <span class="nav-text">Department Report</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/assessments/performance_eval/list.php" 
                   class="nav-link <?php echo isMenuActive([], ['performance_eval']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-clipboard-list"></i></span>
                    <span class="nav-text">Performance Evaluation</span>
                </a>
            </li>
            
            <?php endif; ?>
            
            <?php if (isStaff()): ?>
            <!-- Staff Menu Items -->
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>staff/profile.php" 
                   class="nav-link <?php echo isMenuActive(['profile'], ['staff']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-user"></i></span>
                    <span class="nav-text">My Profile</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/leave/apply.php" 
                   class="nav-link <?php echo isMenuActive(['apply'], ['leave']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-paper-plane"></i></span>
                    <span class="nav-text">Apply for Leave</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>modules/leave/history.php" 
                   class="nav-link <?php echo isMenuActive(['history'], ['leave']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-history"></i></span>
                    <span class="nav-text">Leave History</span>
                </a>
            </li>
            
            <?php endif; ?>
            
            <!-- Common Menu Items -->
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>change_password.php" 
                   class="nav-link <?php echo isMenuActive(['change_password']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fas fa-lock"></i></span>
                    <span class="nav-text">Change Password</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>logout.php" class="nav-link">
                    <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
            
        </ul>
    </nav>
</aside>

<!-- Main Content Area -->
<main class="main-content">
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle" title="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title"><?php echo isset($pageTitle) ? escapeOutput($pageTitle) : 'Dashboard'; ?></h1>
        </div>
        
        <div class="header-right">
            <!-- User Menu -->
            <div class="user-menu" onclick="toggleUserDropdown()">
                <div class="user-avatar"><?php echo $currentUserInitials; ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo escapeOutput($currentUserName); ?></div>
                    <div class="user-role"><?php echo escapeOutput($currentUserRole); ?></div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Content Area -->
    <div class="content">
        <!-- Breadcrumb -->
        <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
        <nav class="breadcrumb">
            <a href="<?php echo $baseUrl; ?>dashboard.php">Home</a>
            <?php foreach ($breadcrumbs as $name => $url): ?>
                <span class="breadcrumb-separator">/</span>
                <?php if ($url): ?>
                    <a href="<?php echo $baseUrl . $url; ?>"><?php echo escapeOutput($name); ?></a>
                <?php else: ?>
                    <span class="breadcrumb-current"><?php echo escapeOutput($name); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
        
        <!-- Flash Messages -->
        <?php echo displayFlashMessages(); ?>
