<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * APPROVED LEAVE REPORT
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

requireHR();

$pageTitle = 'Approved Leave Report';
$breadcrumbs = ['Reports' => 'modules/reports/index.php', 'Approved Leave' => null];

// Get approved leave applications
$stmt = $pdo->query("
    SELECT la.*, lt.leave_name, s.first_name, s.last_name, s.staff_id as staff_code,
           d.department_name, u.username as reviewed_by_name
    FROM leave_applications la
    JOIN leave_types lt ON la.leave_type_id = lt.id
    JOIN staff s ON la.staff_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN users u ON la.reviewed_by = u.id
    WHERE la.status = 'approved'
    ORDER BY la.reviewed_at DESC
");
$approvedLeave = $stmt->fetchAll();

// Stats
$totalDays = 0;
foreach ($approvedLeave as $leave) {
    $totalDays += $leave['total_days'];
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="dashboard-stats" style="grid-template-columns: repeat(2, 1fr);">
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo count($approvedLeave); ?></div>
            <div class="stat-label">Approved Applications</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $totalDays; ?></div>
            <div class="stat-label">Total Days Approved</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-check-circle"></i> Approved Leave Applications</h3>
        <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
    <div class="card-body">
        <?php if (!empty($approvedLeave)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Staff</th>
                            <th>Department</th>
                            <th>Leave Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Days</th>
                            <th>Approved By</th>
                            <th>Date Approved</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($approvedLeave as $leave): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td><?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?></td>
                                <td><?php echo escapeOutput($leave['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($leave['leave_name']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?></td>
                                <td><?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td><?php echo escapeOutput($leave['reviewed_by_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDateTime($leave['reviewed_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #e2e8f0;">
                <p><strong>Total Records:</strong> <?php echo count($approvedLeave); ?> | <strong>Total Days:</strong> <?php echo $totalDays; ?></p>
                <p class="text-muted">Generated: <?php echo date('M d, Y h:i A'); ?></p>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No approved leave applications found</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
