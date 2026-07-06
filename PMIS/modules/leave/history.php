<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * LEAVE HISTORY
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

// Require login
requireLogin();

$pageTitle = 'Leave History';
$breadcrumbs = ['Leave History' => null];

$staffId = getCurrentStaffId();

if (!$staffId) {
    setFlashMessage('error', 'Your account is not linked to a staff record');
    header("Location: ../../dashboard.php");
    exit();
}

// Get leave history
try {
    $stmt = $pdo->prepare("
        SELECT la.*, lt.leave_name, lt.max_days,
               u.username as reviewed_by_name
        FROM leave_applications la
        JOIN leave_types lt ON la.leave_type_id = lt.id
        LEFT JOIN users u ON la.reviewed_by = u.id
        WHERE la.staff_id = ?
        ORDER BY la.applied_at DESC
    ");
    $stmt->execute([$staffId]);
    $leaveHistory = $stmt->fetchAll();
    
    // Get leave statistics
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status IN ('hod_approved','dean_approved') THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status IN ('rejected','hod_rejected','dean_rejected') THEN 1 ELSE 0 END) as rejected
        FROM leave_applications
        WHERE staff_id = ?
    ");
    $statsStmt->execute([$staffId]);
    $stats = $statsStmt->fetch();
    
} catch (PDOException $e) {
    error_log("Leave History Error: " . $e->getMessage());
    setFlashMessage('error', 'Error loading leave history');
    $leaveHistory = [];
    $stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'approved' => 0, 'rejected' => 0];
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Statistics -->
<div class="dashboard-stats" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Applications</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(49,130,206,0.1);color:#3182ce;"><i class="fas fa-spinner"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
            <div class="stat-label">In Review</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['approved']; ?></div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon danger"><i class="fas fa-times-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['rejected']; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
</div>

<!-- Leave History -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">My Leave Applications</h3>
        <a href="apply.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Apply for Leave</a>
    </div>
    <div class="card-body">
        <?php if (!empty($leaveHistory)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Duration</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Comment</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaveHistory as $leave): ?>
                            <tr>
                                <td><?php echo escapeOutput($leave['leave_name']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td><?php echo escapeOutput(truncateText($leave['reason'], 40)); ?></td>
                                <td>
                                    <?php 
                                        $statusLabels = [
                                            'pending' => 'Pending HOD',
                                            'hod_approved' => 'HOD Approved',
                                            'hod_rejected' => 'HOD Rejected',
                                            'dean_approved' => 'Dean Approved',
                                            'dean_rejected' => 'Dean Rejected',
                                            'approved' => 'Fully Approved',
                                            'rejected' => 'Rejected'
                                        ];
                                        $label = $statusLabels[$leave['status']] ?? ucfirst($leave['status']);
                                    ?>
                                    <span class="badge <?php echo getStatusBadgeClass($leave['status']); ?>">
                                        <?php echo $label; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($leave['hod_comment']): ?>
                                        <?php echo escapeOutput($leave['hod_comment']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($leave['applied_at']); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center" style="padding: 40px;">
                <p style="color: #718096; margin-bottom: 20px;">You haven't applied for any leave yet.</p>
                <a href="apply.php" class="btn btn-primary">Apply for Leave</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
