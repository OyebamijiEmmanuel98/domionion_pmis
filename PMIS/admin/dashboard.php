<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * ADMIN DASHBOARD
 * =====================================================
 * 
 * This is the main dashboard for System Administrators.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Include required files
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/role_check.php';

// Require admin access
requireAdmin();

// Page title
$pageTitle = 'Admin Dashboard';

// Get dashboard statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    
    // Active users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $activeUsers = $stmt->fetch()['total'];
    
    // Inactive users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'inactive'");
    $inactiveUsers = $stmt->fetch()['total'];
    
    // Total staff
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff");
    $totalStaff = $stmt->fetch()['total'];
    
    // Total departments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM departments");
    $totalDepartments = $stmt->fetch()['total'];
    
    // Recent activities
    $stmt = $pdo->query("
        SELECT al.*, u.username 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $recentActivities = $stmt->fetchAll();
    
    // Recent logins
    $stmt = $pdo->query("
        SELECT ll.*, u.username 
        FROM login_logs ll 
        JOIN users u ON ll.user_id = u.id 
        ORDER BY ll.login_time DESC 
        LIMIT 10
    ");
    $recentLogins = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    setFlashMessage('error', 'Error loading dashboard data');
}

// Include header
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<style>
.clickable-stat {
    cursor: pointer;
    transition: all 0.3s ease;
}
.clickable-stat:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 20px rgba(0,0,0,0.1);
}
</style>

<!-- Dashboard Stats -->
<div class="dashboard-stats">
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/users/list.php'">
        <div class="stat-icon primary"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($totalUsers ?? 0); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/users/list.php'">
        <div class="stat-icon success"><i class="fas fa-user-check"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($activeUsers ?? 0); ?></div>
            <div class="stat-label">Active Users</div>
        </div>
    </div>
    
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/users/list.php'">
        <div class="stat-icon warning"><i class="fas fa-user-slash"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($inactiveUsers ?? 0); ?></div>
            <div class="stat-label">Inactive Users</div>
        </div>
    </div>
    
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/staff/list.php'">
        <div class="stat-icon primary"><i class="fas fa-user-tie"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($totalStaff ?? 0); ?></div>
            <div class="stat-label">Total Staff</div>
        </div>
    </div>
    
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/departments/list.php'">
        <div class="stat-icon success"><i class="fas fa-building"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($totalDepartments ?? 0); ?></div>
            <div class="stat-label">Departments</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 flex-wrap">
            <a href="../modules/users/add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New User</a>
            <a href="../modules/users/list.php" class="btn btn-secondary"><i class="fas fa-users"></i> Manage Users</a>
            <a href="../modules/logs/activity.php" class="btn btn-info"><i class="fas fa-clipboard-list"></i> View Activity Logs</a>
            <a href="../modules/reports/index.php" class="btn btn-success"><i class="fas fa-file-alt"></i> Generate Reports</a>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Recent Activities -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Activities</h3>
            <a href="../modules/logs/activity.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php if (!empty($recentActivities)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivities as $activity): ?>
                                <tr>
                                    <td><?php echo escapeOutput($activity['username'] ?? 'System'); ?></td>
                                    <td><?php echo escapeOutput($activity['action']); ?></td>
                                    <td><?php echo formatDateTime($activity['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No recent activities</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Logins -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Logins</h3>
            <a href="../modules/logs/login_logs.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php if (!empty($recentLogins)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Login Time</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogins as $login): ?>
                                <tr>
                                    <td><?php echo escapeOutput($login['username']); ?></td>
                                    <td><?php echo formatDateTime($login['login_time']); ?></td>
                                    <td><?php echo escapeOutput($login['ip_address'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No recent logins</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Include footer
require_once '../includes/footer.php';
