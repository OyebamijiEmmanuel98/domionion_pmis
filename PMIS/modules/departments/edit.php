<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * EDIT DEPARTMENT
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

$pageTitle = 'Edit Department';
$breadcrumbs = ['Departments' => 'modules/departments/list.php', 'Edit Department' => null];

$errors = [];
$departmentId = $_GET['id'] ?? 0;

// Get department data
$stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->execute([$departmentId]);
$department = $stmt->fetch();

if (!$department) {
    setFlashMessage('error', 'Department not found');
    header("Location: list.php");
    exit();
}

// Get all staff for HOD selection
$stmt = $pdo->query("
    SELECT s.id, s.staff_id, CONCAT(s.first_name, ' ', s.last_name) as name, d.department_name
    FROM staff s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.status = 'active'
    ORDER BY s.first_name, s.last_name
");
$staffList = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get input
        $departmentName = sanitizeInput($_POST['department_name'] ?? '');
        $departmentCode = sanitizeInput($_POST['department_code'] ?? '');
        $hodStaffId = $_POST['hod_staff_id'] ?? null;
        $description = sanitizeInput($_POST['description'] ?? '');
        
        // Validation
        if (empty($departmentName)) {
            $errors[] = 'Department name is required';
        }
        
        // Check if department code already exists (excluding current)
        if (!empty($departmentCode)) {
            $stmt = $pdo->prepare("SELECT id FROM departments WHERE department_code = ? AND id != ?");
            $stmt->execute([$departmentCode, $departmentId]);
            if ($stmt->fetch()) {
                $errors[] = 'Department code already exists';
            }
        }
        
        // If no errors, update
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE departments 
                    SET department_name = :name, 
                        department_code = :code, 
                        hod_staff_id = :hod_id,
                        description = :description
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    ':name' => $departmentName,
                    ':code' => !empty($departmentCode) ? $departmentCode : null,
                    ':hod_id' => !empty($hodStaffId) ? $hodStaffId : null,
                    ':description' => !empty($description) ? $description : null,
                    ':id' => $departmentId
                ]);
                
                // Log activity
                logActivity('UPDATE', 'departments', $departmentId, 'Updated department: ' . $departmentName);
                
                setFlashMessage('success', 'Department updated successfully');
                header("Location: list.php");
                exit();
                
            } catch (PDOException $e) {
                error_log("Edit Department Error: " . $e->getMessage());
                $errors[] = 'Error updating department. Please try again.';
            }
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Department</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escapeOutput($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Department Name</label>
                    <input type="text" name="department_name" class="form-control" 
                           value="<?php echo escapeOutput($department['department_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Department Code</label>
                    <input type="text" name="department_code" class="form-control" 
                           value="<?php echo escapeOutput($department['department_code'] ?? ''); ?>"
                           placeholder="e.g., CSC, MTH">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Head of Department (HOD)</label>
                <select name="hod_staff_id" class="form-control">
                    <option value="">-- Select HOD --</option>
                    <?php foreach ($staffList as $staff): ?>
                        <option value="<?php echo $staff['id']; ?>" 
                                <?php echo ($department['hod_staff_id'] == $staff['id']) ? 'selected' : ''; ?>>
                            <?php echo escapeOutput($staff['name'] . ' (' . $staff['staff_id'] . ')'); ?>
                            <?php if ($staff['department_name']): ?>
                                - <?php echo escapeOutput($staff['department_name']); ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-hint">Select the staff member who heads this department</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?php echo escapeOutput($department['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Department</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
