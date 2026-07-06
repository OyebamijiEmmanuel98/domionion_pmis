<?php
/**
 * =====================================================
 * ACADEMIC APPRAISAL - PART B (HOD Assessment)
 * =====================================================
 * HOD fills: Assessment scores, overall grade, recommendation
 */

require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

requireHOD();

$evalId = $_GET['id'] ?? 0;
$errors = [];

$evalStmt = $pdo->prepare("
    SELECT ae.*, s.first_name, s.last_name, s.staff_id AS sid, d.department_name
    FROM academic_evaluations ae
    JOIN staff s ON ae.staff_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE ae.id = ?
");
$evalStmt->execute([$evalId]);
$eval = $evalStmt->fetch();

if (!$eval) { setFlashMessage('error', 'Not found'); header("Location: list.php"); exit(); }
if ($eval['status'] !== 'part_b_pending') { setFlashMessage('info', 'Part B not available'); header("Location: view.php?id=$evalId"); exit(); }

$pageTitle = 'Academic Appraisal — Part B';
$breadcrumbs = ['Assessments' => null, 'Academic Appraisals' => 'modules/assessments/academic_eval/list.php', 'Part B' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $teaching = intval($_POST['teaching_score'] ?? 0);
        $research = intval($_POST['research_score'] ?? 0);
        $admin = intval($_POST['admin_score'] ?? 0);
        $community = intval($_POST['community_score'] ?? 0);
        $overall = ($teaching + $research + $admin + $community) / 4;
        
        $grade = 'E';
        if ($overall >= 80) $grade = 'A';
        elseif ($overall >= 65) $grade = 'B';
        elseif ($overall >= 50) $grade = 'C';
        elseif ($overall >= 40) $grade = 'D';
        
        if (empty($errors)) {
            try {
                $pdo->prepare("UPDATE academic_evaluations SET 
                    hod_assessment_summary=?, teaching_score=?, research_score=?, admin_score=?, community_score=?,
                    overall_score=?, overall_grade=?, hod_recommendation=?,
                    part_b_signed_at=NOW(), part_b_signed_by=?, status='part_c_pending'
                    WHERE id=?")->execute([
                    sanitizeInput($_POST['hod_assessment_summary'] ?? ''),
                    $teaching, $research, $admin, $community,
                    round($overall, 2), $grade,
                    sanitizeInput($_POST['hod_recommendation'] ?? ''),
                    getCurrentUserId(), $evalId
                ]);
                logActivity('FILL_ACADEMIC_EVAL_PART_B', 'academic_evaluations', $evalId, 'HOD assessment completed');
                setFlashMessage('success', 'Part B submitted. Staff can now provide response (Part C).');
                header("Location: list.php"); exit();
            } catch (PDOException $e) {
                error_log("Part B Error: " . $e->getMessage());
                $errors[] = 'Error saving.';
            }
        }
    }
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<style>
.appraisal-form { max-width: 900px; margin: 0 auto; }
.part-header { background: linear-gradient(135deg, #2d5a87, #3182ce); color: #fff; padding: 1.5rem 2rem; border-radius: 12px 12px 0 0; }
.part-header h2 { margin: 0; font-size: 1.2rem; }
.part-header p { margin: 0.5rem 0 0; opacity: 0.85; font-size: 0.85rem; }
.part-body { background: #fff; padding: 2rem; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 12px 12px; margin-bottom: 1.5rem; }
.score-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0; }
.score-item { padding: 1rem; background: #f7fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
.score-item label { display: block; font-weight: 600; color: #4a5568; margin-bottom: 0.5rem; }
.score-item input[type="range"] { width: 100%; }
.score-value { text-align: center; font-size: 1.5rem; font-weight: 700; color: #1e3a5f; }
.overall-display { text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #1e3a5f, #2d5a87); color: #fff; border-radius: 12px; margin-top: 1rem; }
.overall-display .score { font-size: 2.5rem; font-weight: 700; }
.overall-display .grade { font-size: 1.2rem; opacity: 0.9; }
</style>

<div class="appraisal-form">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul style="margin:0;padding-left:20px;"><?php foreach ($errors as $e): ?><li><?php echo escapeOutput($e); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?php echo csrfField(); ?>
        
        <div class="part-header">
            <h2><i class="fas fa-user-tie"></i> PART B — HOD ASSESSMENT</h2>
            <p>Appraisal for: <?php echo escapeOutput($eval['last_name'] . ', ' . $eval['first_name'] . ' (' . $eval['sid'] . ')'); ?></p>
        </div>
        
        <div class="part-body">
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label">Assessment Summary</label>
                <textarea name="hod_assessment_summary" class="form-control" rows="4" placeholder="Provide an overall assessment of the staff member's performance..."><?php echo escapeOutput($eval['hod_assessment_summary'] ?? ''); ?></textarea>
            </div>
            
            <h4 style="color:#1e3a5f;margin-bottom:1rem;"><i class="fas fa-star"></i> Performance Scores (0-100)</h4>
            
            <div class="score-grid">
                <div class="score-item">
                    <label><i class="fas fa-chalkboard-teacher"></i> Teaching</label>
                    <input type="range" name="teaching_score" min="0" max="100" value="<?php echo $eval['teaching_score'] ?? 50; ?>" oninput="updateScores()" id="teachingScore">
                    <div class="score-value" id="teachingVal"><?php echo $eval['teaching_score'] ?? 50; ?></div>
                </div>
                <div class="score-item">
                    <label><i class="fas fa-flask"></i> Research & Publications</label>
                    <input type="range" name="research_score" min="0" max="100" value="<?php echo $eval['research_score'] ?? 50; ?>" oninput="updateScores()" id="researchScore">
                    <div class="score-value" id="researchVal"><?php echo $eval['research_score'] ?? 50; ?></div>
                </div>
                <div class="score-item">
                    <label><i class="fas fa-building"></i> Administration</label>
                    <input type="range" name="admin_score" min="0" max="100" value="<?php echo $eval['admin_score'] ?? 50; ?>" oninput="updateScores()" id="adminScore">
                    <div class="score-value" id="adminVal"><?php echo $eval['admin_score'] ?? 50; ?></div>
                </div>
                <div class="score-item">
                    <label><i class="fas fa-hands-helping"></i> Community Service</label>
                    <input type="range" name="community_score" min="0" max="100" value="<?php echo $eval['community_score'] ?? 50; ?>" oninput="updateScores()" id="communityScore">
                    <div class="score-value" id="communityVal"><?php echo $eval['community_score'] ?? 50; ?></div>
                </div>
            </div>
            
            <div class="overall-display" id="overallDisplay">
                <div class="score" id="overallScore">50.00</div>
                <div class="grade">Grade: <strong id="overallGrade">C</strong></div>
            </div>
            
            <div class="form-group" style="margin-top:1.5rem;">
                <label class="form-label">HOD Recommendation</label>
                <textarea name="hod_recommendation" class="form-control" rows="3" placeholder="Recommendations for promotion, training, etc..."><?php echo escapeOutput($eval['hod_recommendation'] ?? ''); ?></textarea>
            </div>
            
            <div style="margin-top:2rem;display:flex;gap:1rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Submit Part B</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
function updateScores() {
    var t = parseInt(document.getElementById('teachingScore').value);
    var r = parseInt(document.getElementById('researchScore').value);
    var a = parseInt(document.getElementById('adminScore').value);
    var c = parseInt(document.getElementById('communityScore').value);
    
    document.getElementById('teachingVal').textContent = t;
    document.getElementById('researchVal').textContent = r;
    document.getElementById('adminVal').textContent = a;
    document.getElementById('communityVal').textContent = c;
    
    var overall = (t + r + a + c) / 4;
    document.getElementById('overallScore').textContent = overall.toFixed(2);
    
    var grade = 'E';
    if (overall >= 80) grade = 'A';
    else if (overall >= 65) grade = 'B';
    else if (overall >= 50) grade = 'C';
    else if (overall >= 40) grade = 'D';
    document.getElementById('overallGrade').textContent = grade;
}
updateScores();
</script>

<?php require_once '../../../includes/footer.php'; ?>
