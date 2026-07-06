<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * DEPARTMENTS LIST
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

$pageTitle = 'Departments';
$breadcrumbs = ['Departments' => null];

// Get all departments with HOD info
try {
    $stmt = $pdo->query("
        SELECT d.*, 
               CONCAT(s.first_name, ' ', s.last_name) as hod_name,
               s.staff_id as hod_staff_code,
               (SELECT COUNT(*) FROM staff WHERE department_id = d.id AND status = 'active') as staff_count
        FROM departments d
        LEFT JOIN staff s ON d.hod_staff_id = s.id
        ORDER BY d.department_name ASC
    ");
    $departments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Departments List Error: " . $e->getMessage());
    setFlashMessage('error', 'Error loading departments');
    $departments = [];
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Departments</h3>
        <a href="add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Department</a>
    </div>
    <div class="card-body">
        <?php if (!empty($departments)): ?>
            <div class="table-container">
                <table class="data-table" id="departmentsTable">
                    <thead>
                        <tr>
                            <th>Department Name</th>
                            <th>Code</th>
                            <th>HOD</th>
                            <th>Staff Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><?php echo escapeOutput($dept['department_name']); ?></td>
                                <td><?php echo escapeOutput($dept['department_code'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($dept['hod_name']): ?>
                                        <?php echo escapeOutput($dept['hod_name']); ?>
                                        <small>(<?php echo escapeOutput($dept['hod_staff_code']); ?>)</small>
                                    <?php else: ?>
                                        <span class="text-muted">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $dept['staff_count']; ?></td>
                                <td class="actions">
                                    <a href="view.php?id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit.php?id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete.php?id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-danger" title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this department? Departments with assigned staff cannot be deleted.')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No departments found</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
