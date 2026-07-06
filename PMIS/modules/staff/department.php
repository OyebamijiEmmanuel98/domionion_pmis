<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * DEPARTMENT STAFF LIST
 * =====================================================
 * 
 * Shows all staff in the current HOD's department.
 * 
 * @author Final Year Project
 * @version 1.0
 */

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/role_check.php';

// Require HOD or higher access
requireHOD();

$pageTitle = 'Department Staff';
$breadcrumbs = ['Department Staff' => null];

// Get HOD's department
$departmentId = getCurrentUserDepartmentId();
$departmentName = 'Your Department';

// Get department name
try {
    if ($departmentId) {
        $stmt = $pdo->prepare("SELECT department_name FROM departments WHERE id = ?");
        $stmt->execute([$departmentId]);
        $dept = $stmt->fetch();
        if ($dept) {
            $departmentName = $dept['department_name'];
        }
    }
} catch (PDOException $e) {
    error_log("Department Staff Error: " . $e->getMessage());
}

// Search and filter
$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query - filter by department
$sql = "
    SELECT s.*, d.department_name,
           (SELECT COUNT(*) FROM leave_applications WHERE staff_id = s.id AND status = 'pending') as pending_leave
    FROM staff s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.department_id = :dept_id
";
$params = [':dept_id' => $departmentId];

if (!empty($search)) {
    $sql .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search OR s.staff_id LIKE :search OR s.email LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if (!empty($typeFilter)) {
    $sql .= " AND s.staff_type = :type";
    $params[':type'] = $typeFilter;
}

if (!empty($statusFilter)) {
    $sql .= " AND s.status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY s.last_name ASC, s.first_name ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $staffList = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Department Staff Query Error: " . $e->getMessage());
    $staffList = [];
    setFlashMessage('error', 'Error loading staff data');
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Department Header -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-building"></i> <?php echo escapeOutput($departmentName); ?> — Staff</h3>
        <span class="badge badge-info"><?php echo count($staffList); ?> staff member<?php echo count($staffList) !== 1 ? 's' : ''; ?></span>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Filter Staff</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?php echo escapeOutput($search); ?>" 
                           placeholder="Name, Staff ID, or Email">
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
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="department.php" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Staff List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Department Staff</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($staffList)): ?>
            <div class="table-container">
                <table class="data-table" id="deptStaffTable">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Status/Rank</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffList as $staff): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($staff['passport_photo'])): ?>
                                        <img src="<?php echo escapeOutput($staff['passport_photo']); ?>" 
                                             alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #e2e8f0; 
                                                    display: flex; align-items: center; justify-content: center; font-size: 12px;">
                                            <?php echo getInitials($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo escapeOutput($staff['staff_id']); ?></td>
                                <td><?php echo escapeOutput($staff['last_name'] . ', ' . $staff['first_name']); ?></td>
                                <td><?php echo escapeOutput($staff['rank']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $staff['staff_type'])); ?></td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($staff['status']); ?>">
                                        <?php echo ucfirst($staff['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="view.php?id=<?php echo $staff['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No staff found in this department</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
