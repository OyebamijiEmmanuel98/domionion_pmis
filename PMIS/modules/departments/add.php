<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * ADD DEPARTMENT
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

$pageTitle = 'Add Department';
$breadcrumbs = ['Departments' => 'modules/departments/list.php', 'Add Department' => null];

$errors = [];
$formData = [
    'department_name' => '',
    'department_code' => '',
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get input
        $formData['department_name'] = sanitizeInput($_POST['department_name'] ?? '');
        $formData['department_code'] = sanitizeInput($_POST['department_code'] ?? '');
        $formData['description'] = sanitizeInput($_POST['description'] ?? '');
        
        // Validation
        if (empty($formData['department_name'])) {
            $errors[] = 'Department name is required';
        }
        
        // Check if department code already exists
        if (!empty($formData['department_code'])) {
            if (recordExists($pdo, 'departments', 'department_code', $formData['department_code'])) {
                $errors[] = 'Department code already exists';
            }
        }
        
        // If no errors, insert
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO departments (department_name, department_code, description)
                    VALUES (:name, :code, :description)
                ");
                
                $stmt->execute([
                    ':name' => $formData['department_name'],
                    ':code' => !empty($formData['department_code']) ? $formData['department_code'] : null,
                    ':description' => !empty($formData['description']) ? $formData['description'] : null
                ]);
                
                $deptId = $pdo->lastInsertId();
                
                // Log activity
                logActivity('CREATE', 'departments', $deptId, 'Created new department: ' . $formData['department_name']);
                
                setFlashMessage('success', 'Department added successfully');
                header("Location: list.php");
                exit();
                
            } catch (PDOException $e) {
                error_log("Add Department Error: " . $e->getMessage());
                $errors[] = 'Error adding department. Please try again.';
            }
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Department</h3>
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
                           value="<?php echo escapeOutput($formData['department_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Department Code</label>
                    <input type="text" name="department_code" class="form-control" 
                           value="<?php echo escapeOutput($formData['department_code']); ?>"
                           placeholder="e.g., CSC, MTH">
                    <small class="form-hint">Short code for the department (optional)</small>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?php echo escapeOutput($formData['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Department</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
