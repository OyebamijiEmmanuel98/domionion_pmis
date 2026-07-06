<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * STAFF LIST
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

$pageTitle = 'Staff Management';
$breadcrumbs = ['Staff' => null];

// Search and filter
$search = $_GET['search'] ?? '';
$departmentFilter = $_GET['department'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query
$sql = "
    SELECT s.*, d.department_name,
           (SELECT COUNT(*) FROM leave_applications WHERE staff_id = s.id AND status = 'pending') as pending_leave
    FROM staff s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search OR s.staff_id LIKE :search OR s.email LIKE :search)";
    $params[':search'] = "%{$search}%";
}

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

$sql .= " ORDER BY s.created_at DESC";

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
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="list.php" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Staff List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Staff</h3>
        <a href="add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Staff</a>
    </div>
    <div class="card-body">
        <?php if (!empty($staffList)): ?>
            <div class="table-container">
                <table class="data-table" id="staffTable">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Department</th>
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
                                    <?php if ($staff['passport_photo']): ?>
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
                                <td><?php echo escapeOutput($staff['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($staff['rank']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $staff['staff_type'])); ?></td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($staff['status']); ?>">
                                        <?php echo ucfirst($staff['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="view.php?id=<?php echo $staff['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit.php?id=<?php echo $staff['id']; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete.php?id=<?php echo $staff['id']; ?>" class="btn btn-sm btn-danger" title="Delete"
                                       onclick="return confirm('Are you sure you want to permanently delete this staff record? This will also remove their leave applications and assessments. This action cannot be undone.')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No staff found</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
