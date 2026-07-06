<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * ACADEMIC STAFF APPRAISAL - INITIATE
 * =====================================================
 */

require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

requireHOD();

$pageTitle = 'Initiate Academic Appraisal';
$breadcrumbs = ['Assessments' => null, 'Academic Appraisals' => 'modules/assessments/academic_eval/list.php', 'Initiate' => null];

$errors = [];

// Get academic staff
if (isAdmin() || isHR()) {
    $staffStmt = $pdo->query("SELECT id, staff_id, first_name, last_name, department_id FROM staff WHERE staff_type = 'academic' AND status = 'active' ORDER BY last_name");
} else {
    $deptId = getCurrentUserDepartmentId();
    $staffStmt = $pdo->prepare("SELECT id, staff_id, first_name, last_name, department_id FROM staff WHERE staff_type = 'academic' AND status = 'active' AND department_id = ? ORDER BY last_name");
    $staffStmt->execute([$deptId]);
}
$staffList = $staffStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $selectedStaff = $_POST['staff_id'] ?? '';
        $periodFrom = $_POST['period_from'] ?? '';
        $periodTo = $_POST['period_to'] ?? '';
        
        if (empty($selectedStaff)) $errors[] = 'Please select a staff member';
        if (empty($periodFrom)) $errors[] = 'Start period is required';
        if (empty($periodTo)) $errors[] = 'End period is required';
        
        if (empty($errors)) {
            // Check if there's already an active evaluation for this staff
            $checkStmt = $pdo->prepare("SELECT id FROM academic_evaluations WHERE staff_id = ? AND status != 'completed' LIMIT 1");
            $checkStmt->execute([$selectedStaff]);
            if ($checkStmt->fetch()) {
                $errors[] = 'This staff member already has an active evaluation in progress';
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO academic_evaluations (staff_id, period_from, period_to, status, initiated_by)
                    VALUES (?, ?, ?, 'part_a_pending', ?)
                ");
                $stmt->execute([$selectedStaff, $periodFrom, $periodTo, getCurrentUserId()]);
                $evalId = $pdo->lastInsertId();
                
                logActivity('INITIATE_ACADEMIC_EVAL', 'academic_evaluations', $evalId, 'Initiated academic appraisal');
                setFlashMessage('success', 'Academic appraisal initiated. Staff can now fill Part A.');
                header("Location: list.php");
                exit();
            } catch (PDOException $e) {
                error_log("Initiate Academic Eval Error: " . $e->getMessage());
                $errors[] = 'Error initiating appraisal.';
            }
        }
    }
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus-circle" style="margin-right:8px;color:#3182ce;"></i> Initiate Academic Appraisal</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin:0;padding-left:20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escapeOutput($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="form-group">
                <label class="form-label required">Select Academic Staff Member</label>
                <select name="staff_id" class="form-control" required>
                    <option value="">-- Select Staff --</option>
                    <?php foreach ($staffList as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo escapeOutput($s['last_name'] . ', ' . $s['first_name'] . ' (' . $s['staff_id'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row" style="margin-top: 1rem;">
                <div class="form-group">
                    <label class="form-label required">Appraisal Period From</label>
                    <input type="date" name="period_from" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">Appraisal Period To</label>
                    <input type="date" name="period_to" class="form-control" required>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; padding: 1rem; background: #ebf8ff; border-radius: 8px; border: 1px solid #bee3f8;">
                <p style="color: #2b6cb0; font-size: 0.85rem; margin: 0;">
                    <i class="fas fa-info-circle"></i> After initiation, the appraisal will go through 6 stages: 
                    <strong>Part A</strong> (Staff) → <strong>Part B</strong> (HOD) → <strong>Part C</strong> (Staff Response) → 
                    <strong>Part D</strong> (Dean) → <strong>Part E</strong> (HR) → <strong>Part F</strong> (A&P Committee)
                </p>
            </div>
            
            <div class="form-group" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Initiate Appraisal</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
