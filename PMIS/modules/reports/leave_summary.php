<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * LEAVE SUMMARY REPORT
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

$pageTitle = 'Leave Summary Report';
$breadcrumbs = ['Reports' => 'modules/reports/index.php', 'Leave Summary' => null];

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$leaveTypeFilter = $_GET['leave_type'] ?? '';

// Build query
$sql = "
    SELECT la.*, lt.leave_name, s.first_name, s.last_name, s.staff_id as staff_code,
           d.department_name, u.username as reviewed_by_name
    FROM leave_applications la
    JOIN leave_types lt ON la.leave_type_id = lt.id
    JOIN staff s ON la.staff_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN users u ON la.reviewed_by = u.id
    WHERE 1=1
";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND la.status = :status";
    $params[':status'] = $statusFilter;
}
if (!empty($leaveTypeFilter)) {
    $sql .= " AND la.leave_type_id = :leave_type";
    $params[':leave_type'] = $leaveTypeFilter;
}

$sql .= " ORDER BY la.applied_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leaveList = $stmt->fetchAll();

// Get leave types for filter
$ltStmt = $pdo->query("SELECT id, leave_name FROM leave_types ORDER BY leave_name");
$leaveTypes = $ltStmt->fetchAll();

// Stats
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM leave_applications
");
$stats = $statsStmt->fetch();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Stats -->
<div class="dashboard-stats" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['approved'] ?? 0; ?></div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger"><i class="fas fa-times-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['rejected'] ?? 0; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Filter Report</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo ($statusFilter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo ($statusFilter == 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo ($statusFilter == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Leave Type</label>
                    <select name="leave_type" class="form-control">
                        <option value="">All Types</option>
                        <?php foreach ($leaveTypes as $lt): ?>
                            <option value="<?php echo $lt['id']; ?>" <?php echo ($leaveTypeFilter == $lt['id']) ? 'selected' : ''; ?>>
                                <?php echo escapeOutput($lt['leave_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Generate Report</button>
                <a href="leave_summary.php" class="btn btn-outline">Clear Filters</a>
                <button type="button" class="btn btn-success" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            </div>
        </form>
    </div>
</div>

<!-- Report Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Leave Summary</h3>
        <span class="text-muted">Generated: <?php echo date('M d, Y h:i A'); ?></span>
    </div>
    <div class="card-body">
        <?php if (!empty($leaveList)): ?>
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
                            <th>Status</th>
                            <th>Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($leaveList as $leave): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td><?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?></td>
                                <td><?php echo escapeOutput($leave['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($leave['leave_name']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?></td>
                                <td><?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($leave['status']); ?>">
                                        <?php echo ucfirst($leave['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($leave['applied_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #e2e8f0;">
                <p><strong>Total Records:</strong> <?php echo count($leaveList); ?></p>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No leave applications found matching the criteria</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
