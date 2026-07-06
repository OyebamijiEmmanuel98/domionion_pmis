<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * VIEW DEPARTMENT
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

$pageTitle = 'View Department';
$breadcrumbs = ['Departments' => 'modules/departments/list.php', 'View Department' => null];

$departmentId = $_GET['id'] ?? 0;

// Get department data with HOD info
$stmt = $pdo->prepare("
    SELECT d.*, CONCAT(s.first_name, ' ', s.last_name) as hod_name, s.staff_id as hod_code
    FROM departments d
    LEFT JOIN staff s ON d.hod_staff_id = s.id
    WHERE d.id = ?
");
$stmt->execute([$departmentId]);
$department = $stmt->fetch();

if (!$department) {
    setFlashMessage('error', 'Department not found');
    header("Location: list.php");
    exit();
}

// Get department staff
$stmt = $pdo->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM leave_applications WHERE staff_id = s.id AND status = 'pending') as pending_leave
    FROM staff s
    WHERE s.department_id = ?
    ORDER BY s.first_name, s.last_name
");
$stmt->execute([$departmentId]);
$staffList = $stmt->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?php echo escapeOutput($department['department_name']); ?></h3>
        <div>
            <a href="edit.php?id=<?php echo $departmentId; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
            <a href="list.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Department Code</label>
                <p><?php echo escapeOutput($department['department_code'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Head of Department</label>
                <p>
                    <?php if ($department['hod_name']): ?>
                        <?php echo escapeOutput($department['hod_name']); ?> 
                        <small>(<?php echo escapeOutput($department['hod_code']); ?>)</small>
                    <?php else: ?>
                        <span class="text-muted">Not Assigned</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Total Staff</label>
                <p><?php echo count($staffList); ?></p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Description</label>
            <p><?php echo nl2br(escapeOutput($department['description'] ?? 'No description available')); ?></p>
        </div>
    </div>
</div>

<!-- Department Staff -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Department Staff</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($staffList)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Status/Rank</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Pending Leave</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffList as $staff): ?>
                            <tr>
                                <td><?php echo escapeOutput($staff['staff_id']); ?></td>
                                <td><?php echo escapeOutput($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                                <td><?php echo escapeOutput($staff['rank']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $staff['staff_type'])); ?></td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($staff['status']); ?>">
                                        <?php echo ucfirst($staff['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($staff['pending_leave'] > 0): ?>
                                        <span class="badge badge-warning"><?php echo $staff['pending_leave']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No staff in this department</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
