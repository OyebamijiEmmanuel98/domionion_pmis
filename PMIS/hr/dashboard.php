<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * HR DASHBOARD
 * =====================================================
 * 
 * This is the main dashboard for HR users.
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

// Require HR or Admin access
requireHR();

// Page title
$pageTitle = 'HR Dashboard';

// Get dashboard statistics
try {
    // Total staff
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff");
    $totalStaff = $stmt->fetch()['total'];
    
    // Academic staff
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff WHERE staff_type = 'academic'");
    $academicStaff = $stmt->fetch()['total'];
    
    // Non-academic staff
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff WHERE staff_type = 'non_academic'");
    $nonAcademicStaff = $stmt->fetch()['total'];
    
    // Total departments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM departments");
    $totalDepartments = $stmt->fetch()['total'];
    
    // Pending leave requests
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM leave_applications WHERE status = 'pending'");
    $pendingLeave = $stmt->fetch()['total'];
    
    // Recent staff registrations
    $stmt = $pdo->query("
        SELECT s.*, d.department_name 
        FROM staff s 
        LEFT JOIN departments d ON s.department_id = d.id 
        ORDER BY s.created_at DESC 
        LIMIT 5
    ");
    $recentStaff = $stmt->fetchAll();
    
    // Recent assessments
    $stmt = $pdo->query("
        SELECT a.*, s.first_name, s.last_name, s.staff_id 
        FROM assessments a 
        JOIN staff s ON a.staff_id = s.id 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
    $recentAssessments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("HR Dashboard Error: " . $e->getMessage());
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
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/staff/list.php'">
        <div class="stat-icon primary"><i class="fas fa-user-tie"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($totalStaff ?? 0); ?></div>
            <div class="stat-label">Total Staff</div>
        </div>
    </div>
    
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/reports/staff_by_type.php'">
        <div class="stat-icon success"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($academicStaff ?? 0); ?></div>
            <div class="stat-label">Academic Staff</div>
        </div>
    </div>
    
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/reports/staff_by_type.php'">
        <div class="stat-icon warning"><i class="fas fa-tools"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($nonAcademicStaff ?? 0); ?></div>
            <div class="stat-label">Non-Academic Staff</div>
        </div>
    </div>
    
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/departments/list.php'">
        <div class="stat-icon primary"><i class="fas fa-building"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($totalDepartments ?? 0); ?></div>
            <div class="stat-label">Departments</div>
        </div>
    </div>
    
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/leave/review.php'">
        <div class="stat-icon danger"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($pendingLeave ?? 0); ?></div>
            <div class="stat-label">Pending Leave</div>
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
            <a href="../modules/staff/add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Staff</a>
            <a href="../modules/staff/list.php" class="btn btn-secondary"><i class="fas fa-user-tie"></i> View All Staff</a>
            <a href="../modules/leave/review.php" class="btn btn-warning"><i class="fas fa-clipboard-list"></i> Review Leave</a>
            <a href="../modules/assessments/add.php" class="btn btn-success"><i class="fas fa-chart-bar"></i> Add Assessment</a>
            <a href="../modules/reports/index.php" class="btn btn-info"><i class="fas fa-file-alt"></i> Reports</a>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Recent Staff Registrations -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Staff Registrations</h3>
            <a href="../modules/staff/list.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php if (!empty($recentStaff)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Staff ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentStaff as $staff): ?>
                                <tr>
                                    <td><?php echo escapeOutput($staff['staff_id']); ?></td>
                                    <td><?php echo escapeOutput($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                                    <td><?php echo escapeOutput($staff['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatDate($staff['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No recent registrations</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Assessments -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Assessments</h3>
            <a href="../modules/assessments/list.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php if (!empty($recentAssessments)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Date</th>
                                <th>Report</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAssessments as $assessment): ?>
                                <tr>
                                    <td><?php echo escapeOutput($assessment['first_name'] . ' ' . $assessment['last_name']); ?></td>
                                    <td><?php echo formatDate($assessment['assessment_date']); ?></td>
                                    <td><?php echo escapeOutput(truncateText($assessment['report'], 50)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No recent assessments</p>
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
