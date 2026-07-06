<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * LEAVE TYPES MANAGEMENT
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

$pageTitle = 'Leave Types';
$breadcrumbs = ['Leave Types' => null];

$errors = [];
$editMode = false;
$editData = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['form_action'] ?? '';
        
        if ($action === 'add' || $action === 'edit') {
            $leaveName = sanitizeInput($_POST['leave_name'] ?? '');
            $maxDays = intval($_POST['max_days'] ?? 0);
            $description = sanitizeInput($_POST['description'] ?? '');
            $leaveTypeId = intval($_POST['leave_type_id'] ?? 0);
            
            // Validation
            if (empty($leaveName)) {
                $errors[] = 'Leave type name is required';
            }
            if ($maxDays < 0) {
                $errors[] = 'Maximum days cannot be negative';
            }
            
            if (empty($errors)) {
                try {
                    if ($action === 'add') {
                        $stmt = $pdo->prepare("INSERT INTO leave_types (leave_name, max_days, description) VALUES (?, ?, ?)");
                        $stmt->execute([$leaveName, $maxDays, $description]);
                        logActivity('CREATE', 'leave_types', $pdo->lastInsertId(), "Created leave type: $leaveName");
                        setFlashMessage('success', "Leave type '$leaveName' added successfully");
                    } else {
                        $stmt = $pdo->prepare("UPDATE leave_types SET leave_name = ?, max_days = ?, description = ? WHERE id = ?");
                        $stmt->execute([$leaveName, $maxDays, $description, $leaveTypeId]);
                        logActivity('UPDATE', 'leave_types', $leaveTypeId, "Updated leave type: $leaveName");
                        setFlashMessage('success', "Leave type '$leaveName' updated successfully");
                    }
                    header("Location: types.php");
                    exit();
                } catch (PDOException $e) {
                    error_log("Leave Type Error: " . $e->getMessage());
                    $errors[] = 'Error saving leave type';
                }
            }
        } elseif ($action === 'delete') {
            $leaveTypeId = intval($_POST['leave_type_id'] ?? 0);
            
            try {
                // Check if leave type is in use
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM leave_applications WHERE leave_type_id = ?");
                $checkStmt->execute([$leaveTypeId]);
                $usage = $checkStmt->fetch();
                
                if ($usage['count'] > 0) {
                    setFlashMessage('error', 'Cannot delete this leave type — it is used in existing leave applications');
                } else {
                    $stmt = $pdo->prepare("DELETE FROM leave_types WHERE id = ?");
                    $stmt->execute([$leaveTypeId]);
                    logActivity('DELETE', 'leave_types', $leaveTypeId, "Deleted leave type");
                    setFlashMessage('success', 'Leave type deleted successfully');
                }
                header("Location: types.php");
                exit();
            } catch (PDOException $e) {
                error_log("Delete Leave Type Error: " . $e->getMessage());
                setFlashMessage('error', 'Error deleting leave type');
                header("Location: types.php");
                exit();
            }
        }
    }
}

// Check if editing
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    try {
        $editStmt = $pdo->prepare("SELECT * FROM leave_types WHERE id = ?");
        $editStmt->execute([$editId]);
        $editData = $editStmt->fetch();
        if ($editData) {
            $editMode = true;
        }
    } catch (PDOException $e) {
        error_log("Edit Leave Type Error: " . $e->getMessage());
    }
}

// Get all leave types with usage count
try {
    $stmt = $pdo->query("
        SELECT lt.*, 
               (SELECT COUNT(*) FROM leave_applications WHERE leave_type_id = lt.id) as usage_count
        FROM leave_types lt
        ORDER BY lt.leave_name ASC
    ");
    $leaveTypes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Leave Types List Error: " . $e->getMessage());
    setFlashMessage('error', 'Error loading leave types');
    $leaveTypes = [];
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <span class="alert-icon"><i class="fas fa-times-circle"></i></span>
        <div>
            <?php foreach ($errors as $error): ?>
                <div><?php echo escapeOutput($error); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="grid-2">
    <!-- Add/Edit Leave Type Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas <?php echo $editMode ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
                <?php echo $editMode ? 'Edit Leave Type' : 'Add Leave Type'; ?>
            </h3>
            <?php if ($editMode): ?>
                <a href="types.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Cancel</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="form_action" value="<?php echo $editMode ? 'edit' : 'add'; ?>">
                <?php if ($editMode): ?>
                    <input type="hidden" name="leave_type_id" value="<?php echo $editData['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label required" for="leave_name">Leave Type Name</label>
                    <input type="text" id="leave_name" name="leave_name" class="form-control" 
                           placeholder="e.g. Annual Leave"
                           value="<?php echo escapeOutput($editMode ? $editData['leave_name'] : ($_POST['leave_name'] ?? '')); ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="max_days">Maximum Days Per Year</label>
                    <input type="number" id="max_days" name="max_days" class="form-control" 
                           min="0" max="365" placeholder="e.g. 30"
                           value="<?php echo escapeOutput($editMode ? $editData['max_days'] : ($_POST['max_days'] ?? '0')); ?>"
                           required>
                    <span class="form-hint">Set to 0 for unlimited days</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" 
                              placeholder="Brief description of this leave type..."><?php echo escapeOutput($editMode ? $editData['description'] : ($_POST['description'] ?? '')); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas <?php echo $editMode ? 'fa-save' : 'fa-plus'; ?>"></i>
                    <?php echo $editMode ? 'Update Leave Type' : 'Add Leave Type'; ?>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Leave Types List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list"></i> All Leave Types</h3>
            <span class="badge badge-info"><?php echo count($leaveTypes); ?> types</span>
        </div>
        <div class="card-body">
            <?php if (!empty($leaveTypes)): ?>
                <div class="table-container">
                    <table class="data-table" id="leaveTypesTable">
                        <thead>
                            <tr>
                                <th>Leave Type</th>
                                <th>Max Days</th>
                                <th>Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaveTypes as $type): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo escapeOutput($type['leave_name']); ?></strong>
                                        <?php if ($type['description']): ?>
                                            <br><small class="text-muted"><?php echo escapeOutput(truncateText($type['description'], 50)); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $type['max_days'] > 0 ? $type['max_days'] . ' days' : 'Unlimited'; ?></span>
                                    </td>
                                    <td><?php echo $type['usage_count']; ?> applications</td>
                                    <td class="actions">
                                        <a href="types.php?edit=<?php echo $type['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($type['usage_count'] == 0): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="form_action" value="delete">
                                                <input type="hidden" name="leave_type_id" value="<?php echo $type['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"
                                                        onclick="return confirm('Are you sure you want to delete this leave type?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-danger" disabled title="Cannot delete — in use">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center" style="padding: 40px;">
                    <p class="text-muted">No leave types found. Add your first leave type using the form.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.grid-2 {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 24px;
}

@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>
