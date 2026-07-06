<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * USERS LIST
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

// Require Admin access
requireAdmin();

$pageTitle = 'User Management';
$breadcrumbs = ['Users' => null];

// Get all users with role and staff info
try {
    $stmt = $pdo->query("
        SELECT u.*, r.role_name, s.staff_id as staff_code, CONCAT(s.first_name, ' ', s.last_name) as staff_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN staff s ON u.staff_id = s.id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Users List Error: " . $e->getMessage());
    setFlashMessage('error', 'Error loading users');
    $users = [];
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">System Users</h3>
        <a href="add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add User</a>
    </div>
    <div class="card-body">
        <?php if (!empty($users)): ?>
            <div class="table-container">
                <table class="data-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Linked Staff</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo escapeOutput($user['username']); ?></td>
                                <td><?php echo escapeOutput($user['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo getRoleBadgeClass($user['role_name']); ?>">
                                        <?php echo getRoleDisplayName($user['role_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['staff_code']): ?>
                                        <?php echo escapeOutput($user['staff_name'] . ' (' . $user['staff_code'] . ')'); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not Linked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($user['status']); ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?></td>
                                <td class="actions">
                                    <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="reset_password.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Reset Password"><i class="fas fa-key"></i></a>
                                    <?php if ($user['status'] == 'active'): ?>
                                        <a href="toggle.php?id=<?php echo $user['id']; ?>&action=deactivate" 
                                           class="btn btn-sm btn-danger" title="Deactivate"
                                           onclick="return confirm('Deactivate this user?')"><i class="fas fa-pause"></i></a>
                                    <?php else: ?>
                                        <a href="toggle.php?id=<?php echo $user['id']; ?>&action=activate" 
                                           class="btn btn-sm btn-success" title="Activate"
                                           onclick="return confirm('Activate this user?')"><i class="fas fa-play"></i></a>
                                    <?php endif; ?>
                                    <a href="delete.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-danger" title="Delete"
                                       onclick="return confirm('Are you sure you want to permanently delete this user? This action cannot be undone.')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No users found</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
