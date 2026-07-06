<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * ADD ASSESSMENT
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

// Require HOD or higher access
requireHOD();

$pageTitle = 'Add Assessment';
$breadcrumbs = ['Assessments' => 'modules/assessments/list.php', 'Add' => null];

$errors = [];
$formData = [
    'staff_id' => $_GET['staff_id'] ?? '',
    'assessment_date' => date('Y-m-d'),
    'report' => '',
    'recommendation' => ''
];

// Get staff list based on role
if (isAdmin() || isHR()) {
    $staffStmt = $pdo->query("
        SELECT s.id, s.staff_id, CONCAT(s.first_name, ' ', s.last_name) as name, d.department_name
        FROM staff s
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE s.status = 'active'
        ORDER BY s.last_name, s.first_name
    ");
} else {
    // HOD can only assess their department staff
    $departmentId = getCurrentUserDepartmentId();
    $staffStmt = $pdo->prepare("
        SELECT s.id, s.staff_id, CONCAT(s.first_name, ' ', s.last_name) as name, d.department_name
        FROM staff s
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE s.status = 'active' AND s.department_id = ?
        ORDER BY s.last_name, s.first_name
    ");
    $staffStmt->execute([$departmentId]);
}
$staffList = $staffStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get input
        $formData['staff_id'] = $_POST['staff_id'] ?? '';
        $formData['assessment_date'] = $_POST['assessment_date'] ?? '';
        $formData['report'] = sanitizeInput($_POST['report'] ?? '');
        $formData['recommendation'] = sanitizeInput($_POST['recommendation'] ?? '');
        
        // Validation
        if (empty($formData['staff_id'])) {
            $errors[] = 'Please select a staff member';
        }
        
        if (empty($formData['assessment_date'])) {
            $errors[] = 'Assessment date is required';
        }
        
        if (empty($formData['report'])) {
            $errors[] = 'Assessment report is required';
        }
        
        // If no errors, insert
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO assessments (staff_id, assessor_user_id, assessment_date, report, recommendation)
                    VALUES (:staff_id, :assessor_id, :assessment_date, :report, :recommendation)
                ");
                
                $stmt->execute([
                    ':staff_id' => $formData['staff_id'],
                    ':assessor_id' => getCurrentUserId(),
                    ':assessment_date' => $formData['assessment_date'],
                    ':report' => $formData['report'],
                    ':recommendation' => !empty($formData['recommendation']) ? $formData['recommendation'] : null
                ]);
                
                $assessmentId = $pdo->lastInsertId();
                
                // Log activity
                logActivity('CREATE', 'assessments', $assessmentId, 'Added assessment for staff ID: ' . $formData['staff_id']);
                
                setFlashMessage('success', 'Assessment added successfully');
                header("Location: list.php");
                exit();
                
            } catch (PDOException $e) {
                error_log("Add Assessment Error: " . $e->getMessage());
                $errors[] = 'Error adding assessment. Please try again.';
            }
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add Staff Assessment</h3>
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
        
        <form method="POST" action="" data-validate>
            <?php echo csrfField(); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Staff Member</label>
                    <select name="staff_id" class="form-control" required>
                        <option value="">-- Select Staff --</option>
                        <?php foreach ($staffList as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>" 
                                    <?php echo ($formData['staff_id'] == $staff['id']) ? 'selected' : ''; ?>>
                                <?php echo escapeOutput($staff['name'] . ' (' . $staff['staff_id'] . ')'); ?>
                                <?php if ($staff['department_name']): ?>
                                    - <?php echo escapeOutput($staff['department_name']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Assessment Date</label>
                    <input type="date" name="assessment_date" class="form-control" 
                           value="<?php echo escapeOutput($formData['assessment_date']); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label required">Assessment Report</label>
                <textarea name="report" class="form-control" rows="6" required
                          placeholder="Enter detailed assessment report including performance, achievements, areas for improvement, etc."><?php echo escapeOutput($formData['report']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Recommendation</label>
                <textarea name="recommendation" class="form-control" rows="3"
                          placeholder="Enter any recommendations for promotion, training, or other actions"><?php echo escapeOutput($formData['recommendation']); ?></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Assessment</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
