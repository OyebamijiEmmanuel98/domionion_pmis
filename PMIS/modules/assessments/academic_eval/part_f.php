<?php
/**
 * =====================================================
 * ACADEMIC APPRAISAL - PART F (A&P Committee)
 * =====================================================
 * Appointments & Promotion Committee final decision
 */

require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

requireAdmin(); // Only admin can fill committee decisions

$evalId = $_GET['id'] ?? 0;
$errors = [];

$evalStmt = $pdo->prepare("SELECT ae.*, s.first_name, s.last_name, s.staff_id AS sid FROM academic_evaluations ae JOIN staff s ON ae.staff_id = s.id WHERE ae.id = ?");
$evalStmt->execute([$evalId]);
$eval = $evalStmt->fetch();

if (!$eval) { setFlashMessage('error', 'Not found'); header("Location: list.php"); exit(); }
if ($eval['status'] !== 'part_f_pending') { setFlashMessage('info', 'Part F not available'); header("Location: view.php?id=$evalId"); exit(); }

$pageTitle = 'Academic Appraisal — Part F';
$breadcrumbs = ['Assessments' => null, 'Academic Appraisals' => 'modules/assessments/academic_eval/list.php', 'Part F' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid request.'; }
    else {
        if (empty($errors)) {
            try {
                $pdo->prepare("UPDATE academic_evaluations SET 
                    committee_decision=?, committee_decision_details=?, committee_effective_date=?,
                    part_f_signed_at=NOW(), part_f_signed_by=?, part_f_signer_name=?, status='completed'
                    WHERE id=?")->execute([
                    $_POST['committee_decision'] ?? 'defer',
                    sanitizeInput($_POST['committee_decision_details'] ?? ''),
                    !empty($_POST['committee_effective_date']) ? $_POST['committee_effective_date'] : null,
                    getCurrentUserId(),
                    sanitizeInput($_POST['committee_signer_name'] ?? ''),
                    $evalId
                ]);
                logActivity('FILL_ACADEMIC_EVAL_PART_F', 'academic_evaluations', $evalId, 'A&P Committee decision recorded - Appraisal completed');
                setFlashMessage('success', 'Academic appraisal completed successfully!');
                header("Location: list.php"); exit();
            } catch (PDOException $e) { error_log("Part F Error: " . $e->getMessage()); $errors[] = 'Error saving.'; }
        }
    }
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div style="max-width:700px;margin:0 auto;">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul style="margin:0;padding-left:20px;"><?php foreach ($errors as $e): ?><li><?php echo escapeOutput($e); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?php echo csrfField(); ?>
        
        <div style="background:linear-gradient(135deg,#744210,#d69e2e);color:#fff;padding:1.5rem 2rem;border-radius:12px 12px 0 0;">
            <h2 style="margin:0;font-size:1.2rem;"><i class="fas fa-gavel"></i> PART F — APPOINTMENTS & PROMOTION COMMITTEE</h2>
            <p style="margin:0.5rem 0 0;opacity:0.85;font-size:0.85rem;">Staff: <?php echo escapeOutput($eval['last_name'] . ', ' . $eval['first_name'] . ' (' . $eval['sid'] . ')'); ?> · Score: <?php echo $eval['overall_score']; ?> (<?php echo $eval['overall_grade']; ?>)</p>
        </div>
        
        <div style="background:#fff;padding:2rem;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;">
            <div class="form-group">
                <label class="form-label required">Committee Decision</label>
                <select name="committee_decision" class="form-control" required>
                    <option value="">-- Select Decision --</option>
                    <option value="promote">Promote</option>
                    <option value="do_not_promote">Do Not Promote</option>
                    <option value="defer">Defer Decision</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-top:1rem;">
                <label class="form-label">Decision Details / Reasons</label>
                <textarea name="committee_decision_details" class="form-control" rows="4" placeholder="Provide details and reasons for the decision..."><?php echo escapeOutput($eval['committee_decision_details'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row" style="margin-top:1rem;">
                <div class="form-group">
                    <label class="form-label">Effective Date</label>
                    <input type="date" name="committee_effective_date" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label required">Signed By (Committee Chair)</label>
                    <input type="text" name="committee_signer_name" class="form-control" placeholder="Full name of committee chair" required>
                </div>
            </div>
            
            <div style="margin-top:2rem;display:flex;gap:1rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check-double"></i> Complete Appraisal</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php require_once '../../../includes/footer.php'; ?>
