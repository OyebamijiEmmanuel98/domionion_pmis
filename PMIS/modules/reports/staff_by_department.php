<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * STAFF BY DEPARTMENT REPORT
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

$pageTitle = 'Staff by Department';
$breadcrumbs = ['Reports' => 'modules/reports/index.php', 'Staff by Department' => null];

// Get staff grouped by department
$stmt = $pdo->query("
    SELECT d.department_name, d.department_code,
           CONCAT(hod.first_name, ' ', hod.last_name) as hod_name,
           COUNT(s.id) as total_staff,
           SUM(CASE WHEN s.staff_type = 'academic' THEN 1 ELSE 0 END) as academic,
           SUM(CASE WHEN s.staff_type = 'non_academic' THEN 1 ELSE 0 END) as non_academic,
           SUM(CASE WHEN s.status = 'active' THEN 1 ELSE 0 END) as active_count
    FROM departments d
    LEFT JOIN staff s ON d.id = s.department_id
    LEFT JOIN staff hod ON d.hod_staff_id = hod.id
    GROUP BY d.id, d.department_name, d.department_code, hod.first_name, hod.last_name
    ORDER BY d.department_name
");
$departments = $stmt->fetchAll();

// Totals
$totalStaff = 0;
$totalAcademic = 0;
$totalNonAcademic = 0;
foreach ($departments as $dept) {
    $totalStaff += $dept['total_staff'];
    $totalAcademic += $dept['academic'];
    $totalNonAcademic += $dept['non_academic'];
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="dashboard-stats" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-building"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo count($departments); ?></div>
            <div class="stat-label">Departments</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $totalStaff; ?></div>
            <div class="stat-label">Total Staff</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-chart-pie"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $totalAcademic; ?> / <?php echo $totalNonAcademic; ?></div>
            <div class="stat-label">Academic / Non-Academic</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-building"></i> Staff Distribution by Department</h3>
        <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
    <div class="card-body">
        <?php if (!empty($departments)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Department</th>
                            <th>Code</th>
                            <th>HOD</th>
                            <th>Academic</th>
                            <th>Non-Academic</th>
                            <th>Total Staff</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($departments as $dept): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td><strong><?php echo escapeOutput($dept['department_name']); ?></strong></td>
                                <td><?php echo escapeOutput($dept['department_code'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($dept['hod_name'] ?? 'Not Assigned'); ?></td>
                                <td><?php echo $dept['academic'] ?? 0; ?></td>
                                <td><?php echo $dept['non_academic'] ?? 0; ?></td>
                                <td><strong><?php echo $dept['total_staff']; ?></strong></td>
                                <td>
                                    <span class="badge badge-success"><?php echo $dept['active_count'] ?? 0; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background: var(--bg-color);">
                            <td colspan="4">TOTAL</td>
                            <td><?php echo $totalAcademic; ?></td>
                            <td><?php echo $totalNonAcademic; ?></td>
                            <td><?php echo $totalStaff; ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div style="margin-top: 20px;">
                <p class="text-muted">Generated: <?php echo date('M d, Y h:i A'); ?></p>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No departments found</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
