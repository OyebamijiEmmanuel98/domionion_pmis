<?php
/**
 * =====================================================
 * ACADEMIC APPRAISAL - PART E (HR Officer)
 * =====================================================
 * HR fills: Historical scores and notes
 */

require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

requireHR();

$evalId = $_GET['id'] ?? 0;
$errors = [];

$evalStmt = $pdo->prepare("SELECT ae.*, s.first_name, s.last_name, s.staff_id AS sid FROM academic_evaluations ae JOIN staff s ON ae.staff_id = s.id WHERE ae.id = ?");
$evalStmt->execute([$evalId]);
$eval = $evalStmt->fetch();

if (!$eval) { setFlashMessage('error', 'Not found'); header("Location: list.php"); exit(); }
if ($eval['status'] !== 'part_e_pending') { setFlashMessage('info', 'Part E not available'); header("Location: view.php?id=$evalId"); exit(); }

$pageTitle = 'Academic Appraisal — Part E';
$breadcrumbs = ['Assessments' => null, 'Academic Appraisals' => 'modules/assessments/academic_eval/list.php', 'Part E' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid request.'; }
    else {
        if (empty($errors)) {
            try {
                $pdo->prepare("UPDATE academic_evaluations SET 
                    hr_score_year1=?, hr_score_year2=?, hr_score_year3=?, hr_notes=?,
                    part_e_signed_at=NOW(), part_e_signed_by=?, status='part_f_pending'
                    WHERE id=?")->execute([
                    floatval($_POST['hr_score_year1'] ?? 0),
                    floatval($_POST['hr_score_year2'] ?? 0),
                    floatval($_POST['hr_score_year3'] ?? 0),
                    sanitizeInput($_POST['hr_notes'] ?? ''),
                    getCurrentUserId(), $evalId
                ]);
                logActivity('FILL_ACADEMIC_EVAL_PART_E', 'academic_evaluations', $evalId, 'HR review completed');
                setFlashMessage('success', 'Part E submitted. Awaiting A&P Committee (Part F).');
                header("Location: list.php"); exit();
            } catch (PDOException $e) { error_log("Part E Error: " . $e->getMessage()); $errors[] = 'Error saving.'; }
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
        
        <div style="background:linear-gradient(135deg,#276749,#38a169);color:#fff;padding:1.5rem 2rem;border-radius:12px 12px 0 0;">
            <h2 style="margin:0;font-size:1.2rem;"><i class="fas fa-user-shield"></i> PART E — HR OFFICER</h2>
            <p style="margin:0.5rem 0 0;opacity:0.85;font-size:0.85rem;">Staff: <?php echo escapeOutput($eval['last_name'] . ', ' . $eval['first_name'] . ' (' . $eval['sid'] . ')'); ?></p>
        </div>
        
        <div style="background:#fff;padding:2rem;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;">
            <h4 style="color:#276749;margin-bottom:1rem;"><i class="fas fa-history"></i> Historical Performance Scores</h4>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Year 1 Average Score</label>
                    <input type="number" name="hr_score_year1" class="form-control" step="0.01" min="0" max="100" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Year 2 Average Score</label>
                    <input type="number" name="hr_score_year2" class="form-control" step="0.01" min="0" max="100" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Year 3 Average Score</label>
                    <input type="number" name="hr_score_year3" class="form-control" step="0.01" min="0" max="100" placeholder="0.00">
                </div>
            </div>
            
            <div class="form-group" style="margin-top:1rem;">
                <label class="form-label">HR Notes</label>
                <textarea name="hr_notes" class="form-control" rows="4" placeholder="Any additional HR notes..."><?php echo escapeOutput($eval['hr_notes'] ?? ''); ?></textarea>
            </div>
            
            <div style="margin-top:2rem;display:flex;gap:1rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Submit Part E</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php require_once '../../../includes/footer.php'; ?>
