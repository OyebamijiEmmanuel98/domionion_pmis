<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * DEPARTMENT REPORT (FOR HOD)
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

requireHOD();

$pageTitle = 'Department Report';
$breadcrumbs = ['Reports' => null, 'Department Report' => null];

$departmentId = getCurrentUserDepartmentId();

// Get department info
$deptStmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
$deptStmt->execute([$departmentId]);
$department = $deptStmt->fetch();

// Get department staff
$staffStmt = $pdo->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM leave_applications WHERE staff_id = s.id AND status = 'approved') as leave_taken
    FROM staff s
    WHERE s.department_id = ? AND s.status = 'active'
    ORDER BY s.last_name, s.first_name
");
$staffStmt->execute([$departmentId]);
$staffList = $staffStmt->fetchAll();

// Leave stats for department
$leaveStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN la.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN la.status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN la.status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM leave_applications la
    JOIN staff s ON la.staff_id = s.id
    WHERE s.department_id = ?
");
$leaveStmt->execute([$departmentId]);
$leaveStats = $leaveStmt->fetch();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-building"></i> <?php echo escapeOutput($department['department_name'] ?? 'My Department'); ?></h3>
        <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<div class="dashboard-stats" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo count($staffList); ?></div>
            <div class="stat-label">Department Staff</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $leaveStats['pending'] ?? 0; ?></div>
            <div class="stat-label">Pending Leave</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $leaveStats['approved'] ?? 0; ?></div>
            <div class="stat-label">Approved Leave</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger"><i class="fas fa-times-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $leaveStats['rejected'] ?? 0; ?></div>
            <div class="stat-label">Rejected Leave</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users"></i> Department Staff</h3>
        <span class="text-muted">Generated: <?php echo date('M d, Y h:i A'); ?></span>
    </div>
    <div class="card-body">
        <?php if (!empty($staffList)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Status/Rank</th>
                            <th>Type</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Leave Taken</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($staffList as $staff): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td><?php echo escapeOutput($staff['staff_id']); ?></td>
                                <td><?php echo escapeOutput($staff['last_name'] . ', ' . $staff['first_name']); ?></td>
                                <td><?php echo escapeOutput($staff['rank']); ?></td>
                                <td>
                                    <span class="badge <?php echo $staff['staff_type'] == 'academic' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $staff['staff_type'])); ?>
                                    </span>
                                </td>
                                <td><?php echo escapeOutput($staff['email'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($staff['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo $staff['leave_taken']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #e2e8f0;">
                <p><strong>Total Staff:</strong> <?php echo count($staffList); ?></p>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No staff found in this department</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
