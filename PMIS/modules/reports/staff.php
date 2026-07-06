<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * STAFF REPORT
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

$pageTitle = 'Staff Report';
$breadcrumbs = ['Reports' => 'modules/reports/index.php', 'Staff Report' => null];

// Get filter parameters
$departmentFilter = $_GET['department'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? 'active';

// Build query
$sql = "
    SELECT s.*, d.department_name
    FROM staff s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE 1=1
";
$params = [];

if (!empty($departmentFilter)) {
    $sql .= " AND s.department_id = :dept";
    $params[':dept'] = $departmentFilter;
}

if (!empty($typeFilter)) {
    $sql .= " AND s.staff_type = :type";
    $params[':type'] = $typeFilter;
}

if (!empty($statusFilter)) {
    $sql .= " AND s.status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY s.last_name, s.first_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$staffList = $stmt->fetchAll();

// Get departments for filter
$deptStmt = $pdo->query("SELECT id, department_name FROM departments ORDER BY department_name");
$departments = $deptStmt->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Filter Card -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Filter Report</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-control">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                    <?php echo ($departmentFilter == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo escapeOutput($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Staff Type</label>
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="academic" <?php echo ($typeFilter == 'academic') ? 'selected' : ''; ?>>Academic</option>
                        <option value="non_academic" <?php echo ($typeFilter == 'non_academic') ? 'selected' : ''; ?>>Non-Academic</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" <?php echo ($statusFilter == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($statusFilter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Generate Report</button>
                <a href="staff.php" class="btn btn-outline">Clear Filters</a>
                <button type="button" class="btn btn-success" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            </div>
        </form>
    </div>
</div>

<!-- Report -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Staff Report</h3>
        <span class="text-muted">Generated: <?php echo date('M d, Y h:i A'); ?></span>
    </div>
    <div class="card-body">
        <?php if (!empty($staffList)): ?>
            <div class="table-container">
                <table class="data-table" id="reportTable">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Status/Rank</th>
                            <th>Type</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($staffList as $staff): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td><?php echo escapeOutput($staff['staff_id']); ?></td>
                                <td><?php echo escapeOutput($staff['last_name'] . ', ' . $staff['first_name']); ?></td>
                                <td><?php echo escapeOutput($staff['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($staff['rank']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $staff['staff_type'])); ?></td>
                                <td><?php echo escapeOutput($staff['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($staff['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($staff['status']); ?>">
                                        <?php echo ucfirst($staff['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="report-summary" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                <p><strong>Total Records:</strong> <?php echo count($staffList); ?></p>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No staff found matching the criteria</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
