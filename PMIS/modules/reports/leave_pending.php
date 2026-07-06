<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * PENDING LEAVE REPORT
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

$pageTitle = 'Pending Leave Report';
$breadcrumbs = ['Reports' => 'modules/reports/index.php', 'Pending Leave' => null];

// Get pending leave applications
$stmt = $pdo->query("
    SELECT la.*, lt.leave_name, s.first_name, s.last_name, s.staff_id as staff_code,
           d.department_name
    FROM leave_applications la
    JOIN leave_types lt ON la.leave_type_id = lt.id
    JOIN staff s ON la.staff_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE la.status = 'pending'
    ORDER BY la.applied_at ASC
");
$pendingLeave = $stmt->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-hourglass-half"></i> Pending Leave Applications</h3>
        <div>
            <span class="badge badge-warning"><?php echo count($pendingLeave); ?> pending</span>
            <button type="button" class="btn btn-success btn-sm" onclick="window.print()" style="margin-left: 10px;">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($pendingLeave)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Leave Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Applied</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($pendingLeave as $leave): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td><?php echo escapeOutput($leave['staff_code']); ?></td>
                                <td><?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?></td>
                                <td><?php echo escapeOutput($leave['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($leave['leave_name']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?></td>
                                <td><?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td><?php echo escapeOutput(truncateText($leave['reason'], 30)); ?></td>
                                <td><?php echo formatDate($leave['applied_at']); ?></td>
                                <td>
                                    <a href="../leave/review.php" class="btn btn-sm btn-warning">
                                        <i class="fas fa-check"></i> Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #e2e8f0;">
                <p><strong>Total Pending:</strong> <?php echo count($pendingLeave); ?></p>
                <p class="text-muted">Generated: <?php echo date('M d, Y h:i A'); ?></p>
            </div>
        <?php else: ?>
            <div class="text-center" style="padding: 40px;">
                <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success-color); margin-bottom: 15px;"></i>
                <p class="text-muted">No pending leave applications. All caught up!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
