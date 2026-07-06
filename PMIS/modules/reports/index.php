<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * REPORTS DASHBOARD
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

// Require HR or Admin access
requireHR();

$pageTitle = 'Reports';
$breadcrumbs = ['Reports' => null];

// Get statistics for report cards
try {
    // Staff by department
    $deptStmt = $pdo->query("
        SELECT d.department_name, COUNT(s.id) as count
        FROM departments d
        LEFT JOIN staff s ON d.id = s.department_id AND s.status = 'active'
        GROUP BY d.id, d.department_name
        ORDER BY count DESC
    ");
    $staffByDept = $deptStmt->fetchAll();
    
    // Staff by type
    $typeStmt = $pdo->query("
        SELECT staff_type, COUNT(*) as count
        FROM staff
        WHERE status = 'active'
        GROUP BY staff_type
    ");
    $staffByType = $typeStmt->fetchAll();
    
    // Leave statistics
    $leaveStmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM leave_applications
    ");
    $leaveStats = $leaveStmt->fetch();
    
} catch (PDOException $e) {
    error_log("Reports Error: " . $e->getMessage());
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="grid-2">
    <!-- Staff Reports -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-pie"></i> Staff Reports</h3>
        </div>
        <div class="card-body">
            <div class="report-links">
                <a href="staff.php" class="report-link">
                    <span class="report-icon"><i class="fas fa-users"></i></span>
                    <div>
                        <strong>All Staff Report</strong>
                        <small>Complete list of all staff members</small>
                    </div>
                </a>
                
                <a href="staff_by_department.php" class="report-link">
                    <span class="report-icon"><i class="fas fa-building"></i></span>
                    <div>
                        <strong>Staff by Department</strong>
                        <small>Staff distribution across departments</small>
                    </div>
                </a>
                
                <a href="staff_by_type.php" class="report-link">
                    <span class="report-icon"><i class="fas fa-user-tie"></i></span>
                    <div>
                        <strong>Staff by Type</strong>
                        <small>Academic vs Non-Academic staff</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Leave Reports -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Leave Reports</h3>
        </div>
        <div class="card-body">
            <div class="report-links">
                <a href="leave_summary.php" class="report-link">
                    <span class="report-icon"><i class="fas fa-clipboard-list"></i></span>
                    <div>
                        <strong>Leave Summary</strong>
                        <small>Overview of all leave applications</small>
                    </div>
                </a>
                
                <a href="leave_pending.php" class="report-link">
                    <span class="report-icon"><i class="fas fa-hourglass-half"></i></span>
                    <div>
                        <strong>Pending Leave</strong>
                        <small>Applications awaiting approval</small>
                    </div>
                </a>
                
                <a href="leave_approved.php" class="report-link">
                    <span class="report-icon"><i class="fas fa-check-circle"></i></span>
                    <div>
                        <strong>Approved Leave</strong>
                        <small>All approved leave applications</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Statistics -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Quick Statistics</h3>
    </div>
    <div class="card-body">
        <div class="stats-grid">
            <div class="stat-box">
                <h4>Staff by Department</h4>
                <?php if (!empty($staffByDept)): ?>
                    <ul class="stat-list">
                        <?php foreach ($staffByDept as $dept): ?>
                            <li>
                                <span><?php echo escapeOutput($dept['department_name']); ?></span>
                                <strong><?php echo $dept['count']; ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <div class="stat-box">
                <h4>Staff by Type</h4>
                <?php if (!empty($staffByType)): ?>
                    <ul class="stat-list">
                        <?php foreach ($staffByType as $type): ?>
                            <li>
                                <span><?php echo ucfirst(str_replace('_', ' ', $type['staff_type'])); ?></span>
                                <strong><?php echo $type['count']; ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <div class="stat-box">
                <h4>Leave Overview</h4>
                <?php if ($leaveStats): ?>
                    <ul class="stat-list">
                        <li>
                            <span>Pending</span>
                            <strong class="text-warning"><?php echo $leaveStats['pending']; ?></strong>
                        </li>
                        <li>
                            <span>Approved</span>
                            <strong class="text-success"><?php echo $leaveStats['approved']; ?></strong>
                        </li>
                        <li>
                            <span>Rejected</span>
                            <strong class="text-danger"><?php echo $leaveStats['rejected']; ?></strong>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.report-links {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.report-link {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    transition: all 0.2s;
    color: inherit;
}

.report-link:hover {
    background: #f7fafc;
    border-color: #3182ce;
    text-decoration: none;
}

.report-icon {
    font-size: 24px;
}

.report-link div {
    display: flex;
    flex-direction: column;
}

.report-link strong {
    color: #2d3748;
}

.report-link small {
    color: #718096;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}

.stat-box h4 {
    margin-bottom: 15px;
    color: #1e3a5f;
    font-size: 16px;
}

.stat-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.stat-list li {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e2e8f0;
}

.stat-list li:last-child {
    border-bottom: none;
}

.stat-list span {
    color: #4a5568;
}

.stat-list strong {
    color: #2d3748;
}

.text-warning { color: #d69e2e; }
.text-success { color: #38a169; }
.text-danger { color: #e53e3e; }

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>
