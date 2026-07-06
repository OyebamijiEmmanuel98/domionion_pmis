<?php
/**
 * =====================================================
 * ACADEMIC APPRAISAL - PART C (Staff Comments/Response)
 * =====================================================
 */

require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

requireLogin();

$evalId = $_GET['id'] ?? 0;
$errors = [];

$evalStmt = $pdo->prepare("SELECT ae.*, s.first_name, s.last_name, s.staff_id AS sid FROM academic_evaluations ae JOIN staff s ON ae.staff_id = s.id WHERE ae.id = ?");
$evalStmt->execute([$evalId]);
$eval = $evalStmt->fetch();

if (!$eval) { setFlashMessage('error', 'Not found'); header("Location: list.php"); exit(); }
if ($eval['status'] !== 'part_c_pending') { setFlashMessage('info', 'Part C not available'); header("Location: view.php?id=$evalId"); exit(); }

$pageTitle = 'Academic Appraisal — Part C';
$breadcrumbs = ['Assessments' => null, 'Academic Appraisals' => 'modules/assessments/academic_eval/list.php', 'Part C' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid request.'; }
    else {
        if (empty($errors)) {
            try {
                $pdo->prepare("UPDATE academic_evaluations SET staff_comments=?, staff_agrees=?, part_c_signed_at=NOW(), status='part_d_pending' WHERE id=?")->execute([
                    sanitizeInput($_POST['staff_comments'] ?? ''),
                    $_POST['staff_agrees'] ?? 'Yes',
                    $evalId
                ]);
                logActivity('FILL_ACADEMIC_EVAL_PART_C', 'academic_evaluations', $evalId, 'Staff response submitted');
                setFlashMessage('success', 'Part C submitted. Awaiting Dean review (Part D).');
                header("Location: list.php"); exit();
            } catch (PDOException $e) { error_log("Part C Error: " . $e->getMessage()); $errors[] = 'Error saving.'; }
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
        
        <div style="background:linear-gradient(135deg,#2d5a87,#4299e1);color:#fff;padding:1.5rem 2rem;border-radius:12px 12px 0 0;">
            <h2 style="margin:0;font-size:1.2rem;"><i class="fas fa-comment-alt"></i> PART C — STAFF COMMENTS / RESPONSE</h2>
            <p style="margin:0.5rem 0 0;opacity:0.85;font-size:0.85rem;">Your response to the HOD's assessment</p>
        </div>
        
        <div style="background:#fff;padding:2rem;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;">
            <!-- Show HOD's scores for reference -->
            <div style="padding:1rem;background:#f7fafc;border-radius:8px;margin-bottom:1.5rem;border:1px solid #e2e8f0;">
                <h4 style="color:#1e3a5f;margin-bottom:0.75rem;font-size:0.95rem;"><i class="fas fa-chart-bar"></i> HOD Assessment Summary</h4>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.5rem;font-size:0.85rem;">
                    <div>Teaching: <strong><?php echo $eval['teaching_score'] ?? 'N/A'; ?>/100</strong></div>
                    <div>Research: <strong><?php echo $eval['research_score'] ?? 'N/A'; ?>/100</strong></div>
                    <div>Administration: <strong><?php echo $eval['admin_score'] ?? 'N/A'; ?>/100</strong></div>
                    <div>Community: <strong><?php echo $eval['community_score'] ?? 'N/A'; ?>/100</strong></div>
                </div>
                <div style="margin-top:0.75rem;font-weight:700;color:#1e3a5f;">Overall: <?php echo $eval['overall_score'] ?? 'N/A'; ?> (Grade <?php echo $eval['overall_grade'] ?? 'N/A'; ?>)</div>
                <?php if ($eval['hod_recommendation']): ?>
                    <div style="margin-top:0.5rem;color:#4a5568;">Recommendation: <?php echo escapeOutput($eval['hod_recommendation']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label class="form-label required">Do you agree with the assessment?</label>
                <select name="staff_agrees" class="form-control" required>
                    <option value="Yes">Yes, I agree</option>
                    <option value="Partially">Partially agree</option>
                    <option value="No">No, I disagree</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-top:1rem;">
                <label class="form-label">Comments / Response</label>
                <textarea name="staff_comments" class="form-control" rows="5" placeholder="Provide your comments or response to the HOD's assessment..."><?php echo escapeOutput($eval['staff_comments'] ?? ''); ?></textarea>
            </div>
            
            <div style="margin-top:2rem;display:flex;gap:1rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Submit Part C</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php require_once '../../../includes/footer.php'; ?>
