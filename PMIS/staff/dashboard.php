<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * STAFF DASHBOARD
 * =====================================================
 * 
 * This is the main dashboard for regular staff members.
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

// Require login (any role)
requireLogin();

// Page title
$pageTitle = 'Staff Dashboard';

// Get current staff ID
$staffId = getCurrentStaffId();
$staffData = null;

// Get staff information and statistics
try {
    if ($staffId) {
        // Get staff details
        $stmt = $pdo->prepare("
            SELECT s.*, d.department_name 
            FROM staff s 
            LEFT JOIN departments d ON s.department_id = d.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$staffId]);
        $staffData = $stmt->fetch();
    }
    
    // Leave statistics
    if ($staffId) {
        // Total leave applications
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leave_applications WHERE staff_id = ?");
        $stmt->execute([$staffId]);
        $totalLeave = $stmt->fetch()['total'];
        
        // Pending leave
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leave_applications WHERE staff_id = ? AND status = 'pending'");
        $stmt->execute([$staffId]);
        $pendingLeave = $stmt->fetch()['total'];
        
        // Approved leave
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leave_applications WHERE staff_id = ? AND status = 'approved'");
        $stmt->execute([$staffId]);
        $approvedLeave = $stmt->fetch()['total'];
        
        // Recent leave applications
        $stmt = $pdo->prepare("
            SELECT la.*, lt.leave_name 
            FROM leave_applications la 
            JOIN leave_types lt ON la.leave_type_id = lt.id 
            WHERE la.staff_id = ? 
            ORDER BY la.applied_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$staffId]);
        $recentLeave = $stmt->fetchAll();
        
        // Recent assessments
        $stmt = $pdo->prepare("
            SELECT a.*, u.username as assessor_name 
            FROM assessments a 
            JOIN users u ON a.assessor_user_id = u.id 
            WHERE a.staff_id = ? 
            ORDER BY a.assessment_date DESC 
            LIMIT 3
        ");
        $stmt->execute([$staffId]);
        $recentAssessments = $stmt->fetchAll();
    } else {
        $totalLeave = $pendingLeave = $approvedLeave = 0;
        $recentLeave = [];
        $recentAssessments = [];
    }
    
} catch (PDOException $e) {
    error_log("Staff Dashboard Error: " . $e->getMessage());
    setFlashMessage('error', 'Error loading dashboard data');
}

// Include header
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Welcome Message -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-hand-peace"></i> Welcome, <?php echo escapeOutput($staffData['first_name'] ?? getCurrentUsername()); ?>!
        </h3>
    </div>
    <div class="card-body">
        <?php if ($staffData): ?>
            <p>
                <strong>Staff ID:</strong> <?php echo escapeOutput($staffData['staff_id']); ?> | 
                <strong>Department:</strong> <?php echo escapeOutput($staffData['department_name'] ?? 'N/A'); ?> | 
                <strong>Status/Rank:</strong> <?php echo escapeOutput($staffData['rank']); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Dashboard Stats -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($totalLeave ?? 0); ?></div>
            <div class="stat-label">Total Applications</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($pendingLeave ?? 0); ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($approvedLeave ?? 0); ?></div>
            <div class="stat-label">Approved</div>
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
            <a href="profile.php" class="btn btn-primary"><i class="fas fa-user"></i> View Profile</a>
            <a href="../modules/leave/apply.php" class="btn btn-success"><i class="fas fa-paper-plane"></i> Apply for Leave</a>
            <a href="../modules/leave/history.php" class="btn btn-secondary"><i class="fas fa-history"></i> Leave History</a>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Recent Leave Applications -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Leave Applications</h3>
            <a href="../modules/leave/history.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php if (!empty($recentLeave)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLeave as $leave): ?>
                                <tr>
                                    <td><?php echo escapeOutput($leave['leave_name']); ?></td>
                                    <td>
                                        <?php echo formatDate($leave['start_date']); ?> - 
                                        <?php echo formatDate($leave['end_date']); ?>
                                        (<?php echo $leave['total_days']; ?> days)
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($leave['status']); ?>">
                                            <?php echo ucfirst($leave['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No leave applications yet</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Assessments -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Assessments</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($recentAssessments)): ?>
                <?php foreach ($recentAssessments as $assessment): ?>
                    <div class="assessment-item" style="padding: 15px; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <strong><?php echo formatDate($assessment['assessment_date']); ?></strong>
                            <span class="text-muted">By: <?php echo escapeOutput($assessment['assessor_name']); ?></span>
                        </div>
                        <p style="color: #4a5568; font-size: 14px;">
                            <?php echo escapeOutput(truncateText($assessment['report'], 150)); ?>
                        </p>
                        <?php if ($assessment['recommendation']): ?>
                            <div style="background: #f7fafc; padding: 10px; border-radius: 4px; margin-top: 10px; font-size: 13px;">
                                <strong>Recommendation:</strong> 
                                <?php echo escapeOutput(truncateText($assessment['recommendation'], 100)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted">No assessments yet</p>
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

.assessment-item:last-child {
    border-bottom: none !important;
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
