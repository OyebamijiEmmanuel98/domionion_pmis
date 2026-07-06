<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * STAFF BY TYPE REPORT
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

$pageTitle = 'Staff by Type';
$breadcrumbs = ['Reports' => 'modules/reports/index.php', 'Staff by Type' => null];

// Get filter
$typeFilter = $_GET['type'] ?? '';

// Get staff counts by type
$summaryStmt = $pdo->query("
    SELECT staff_type, COUNT(*) as count
    FROM staff WHERE status = 'active'
    GROUP BY staff_type
");
$typeSummary = $summaryStmt->fetchAll();
$typeCountMap = [];
foreach ($typeSummary as $ts) {
    $typeCountMap[$ts['staff_type']] = $ts['count'];
}

// Get staff list
$sql = "
    SELECT s.*, d.department_name
    FROM staff s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.status = 'active'
";
$params = [];

if (!empty($typeFilter)) {
    $sql .= " AND s.staff_type = :type";
    $params[':type'] = $typeFilter;
}

$sql .= " ORDER BY s.staff_type, s.last_name, s.first_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$staffList = $stmt->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="dashboard-stats" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo count($staffList); ?></div>
            <div class="stat-label">Total Active Staff</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $typeCountMap['academic'] ?? 0; ?></div>
            <div class="stat-label">Academic Staff</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-tools"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $typeCountMap['non_academic'] ?? 0; ?></div>
            <div class="stat-label">Non-Academic Staff</div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Filter</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Staff Type</label>
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="academic" <?php echo ($typeFilter == 'academic') ? 'selected' : ''; ?>>Academic</option>
                        <option value="non_academic" <?php echo ($typeFilter == 'non_academic') ? 'selected' : ''; ?>>Non-Academic</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="staff_by_type.php" class="btn btn-outline">Clear</a>
                <button type="button" class="btn btn-success" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            </div>
        </form>
    </div>
</div>

<!-- Report Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-tie"></i> Staff by Type</h3>
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
                            <th>Department</th>
                            <th>Status/Rank</th>
                            <th>Type</th>
                            <th>Email</th>
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
                                <td>
                                    <span class="badge <?php echo $staff['staff_type'] == 'academic' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $staff['staff_type'])); ?>
                                    </span>
                                </td>
                                <td><?php echo escapeOutput($staff['email'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #e2e8f0;">
                <p><strong>Total Records:</strong> <?php echo count($staffList); ?></p>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No staff found matching the criteria</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
