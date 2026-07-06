<?php
/**
 * =====================================================
 * ACADEMIC APPRAISAL - PART D (Dean of Faculty)
 * =====================================================
 * Dean fills: Comments, recommendation for promotion/increment
 */

require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

requireHOD(); // Dean or higher

$evalId = $_GET['id'] ?? 0;
$errors = [];

$evalStmt = $pdo->prepare("SELECT ae.*, s.first_name, s.last_name, s.staff_id AS sid, d.department_name FROM academic_evaluations ae JOIN staff s ON ae.staff_id = s.id LEFT JOIN departments d ON s.department_id = d.id WHERE ae.id = ?");
$evalStmt->execute([$evalId]);
$eval = $evalStmt->fetch();

if (!$eval) { setFlashMessage('error', 'Not found'); header("Location: list.php"); exit(); }
if ($eval['status'] !== 'part_d_pending') { setFlashMessage('info', 'Part D not available'); header("Location: view.php?id=$evalId"); exit(); }

$pageTitle = 'Academic Appraisal — Part D';
$breadcrumbs = ['Assessments' => null, 'Academic Appraisals' => 'modules/assessments/academic_eval/list.php', 'Part D' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid request.'; }
    else {
        if (empty($errors)) {
            try {
                $pdo->prepare("UPDATE academic_evaluations SET 
                    dean_comments=?, dean_recommendation=?, dean_promotion_to=?, dean_promotion_date=?,
                    part_d_signed_at=NOW(), part_d_signed_by=?, part_d_signer_name=?, status='part_e_pending'
                    WHERE id=?")->execute([
                    sanitizeInput($_POST['dean_comments'] ?? ''),
                    $_POST['dean_recommendation'] ?? 'maintain',
                    sanitizeInput($_POST['dean_promotion_to'] ?? ''),
                    !empty($_POST['dean_promotion_date']) ? $_POST['dean_promotion_date'] : null,
                    getCurrentUserId(),
                    sanitizeInput($_POST['dean_name'] ?? ''),
                    $evalId
                ]);
                logActivity('FILL_ACADEMIC_EVAL_PART_D', 'academic_evaluations', $evalId, 'Dean review completed');
                setFlashMessage('success', 'Part D submitted. Awaiting HR review (Part E).');
                header("Location: list.php"); exit();
            } catch (PDOException $e) { error_log("Part D Error: " . $e->getMessage()); $errors[] = 'Error saving.'; }
        }
    }
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div style="max-width:800px;margin:0 auto;">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul style="margin:0;padding-left:20px;"><?php foreach ($errors as $e): ?><li><?php echo escapeOutput($e); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?php echo csrfField(); ?>
        
        <div style="background:linear-gradient(135deg,#553c9a,#805ad5);color:#fff;padding:1.5rem 2rem;border-radius:12px 12px 0 0;">
            <h2 style="margin:0;font-size:1.2rem;"><i class="fas fa-university"></i> PART D — DEAN OF FACULTY</h2>
            <p style="margin:0.5rem 0 0;opacity:0.85;font-size:0.85rem;">Staff: <?php echo escapeOutput($eval['last_name'] . ', ' . $eval['first_name']); ?> · Overall Score: <?php echo $eval['overall_score']; ?> (<?php echo $eval['overall_grade']; ?>)</p>
        </div>
        
        <div style="background:#fff;padding:2rem;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;">
            <div class="form-group">
                <label class="form-label">Dean's Name</label>
                <input type="text" name="dean_name" class="form-control" placeholder="Full name" required>
            </div>
            
            <div class="form-group" style="margin-top:1rem;">
                <label class="form-label">Comments</label>
                <textarea name="dean_comments" class="form-control" rows="4" placeholder="Dean's comments on the appraisal..."><?php echo escapeOutput($eval['dean_comments'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row" style="margin-top:1rem;">
                <div class="form-group">
                    <label class="form-label required">Recommendation</label>
                    <select name="dean_recommendation" class="form-control" required>
                        <option value="maintain">Maintain Current Position</option>
                        <option value="promotion">Recommend for Promotion</option>
                        <option value="increment">Recommend for Increment</option>
                        <option value="probation">Place on Probation</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Promotion To (if applicable)</label>
                    <input type="text" name="dean_promotion_to" class="form-control" placeholder="e.g. Senior Lecturer">
                </div>
            </div>
            
            <div class="form-group" style="margin-top:1rem;">
                <label class="form-label">Effective Date (if applicable)</label>
                <input type="date" name="dean_promotion_date" class="form-control">
            </div>
            
            <div style="margin-top:2rem;display:flex;gap:1rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Submit Part D</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php require_once '../../../includes/footer.php'; ?>
