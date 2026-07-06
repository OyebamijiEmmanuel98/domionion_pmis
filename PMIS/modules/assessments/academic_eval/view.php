<?php
/**
 * =====================================================
 * ACADEMIC APPRAISAL - VIEW (Read-only complete view)
 * =====================================================
 */

require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

requireHOD();

$evalId = $_GET['id'] ?? 0;

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

// Get related data
$quals = $pdo->prepare("SELECT * FROM ae_qualifications WHERE evaluation_id = ?"); $quals->execute([$evalId]); $qualifications = $quals->fetchAll();
$courses = $pdo->prepare("SELECT * FROM ae_courses_taught WHERE evaluation_id = ?"); $courses->execute([$evalId]); $courseList = $courses->fetchAll();
$pubs = $pdo->prepare("SELECT * FROM ae_publications WHERE evaluation_id = ?"); $pubs->execute([$evalId]); $publications = $pubs->fetchAll();
$gs = $pdo->prepare("SELECT * FROM ae_graduate_students WHERE evaluation_id = ?"); $gs->execute([$evalId]); $gradStudents = $gs->fetchAll();

$pageTitle = 'Academic Appraisal — View';
$breadcrumbs = ['Assessments' => null, 'Academic Appraisals' => 'modules/assessments/academic_eval/list.php', 'View' => null];

// Status display
function getAEStatus($status) {
    $labels = ['part_a_pending'=>'Part A: Pending','part_b_pending'=>'Part B: Pending','part_c_pending'=>'Part C: Pending','part_d_pending'=>'Part D: Pending','part_e_pending'=>'Part E: Pending','part_f_pending'=>'Part F: Pending','completed'=>'Completed'];
    return $labels[$status] ?? $status;
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<style>
.view-section { background:#fff; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:1.5rem; overflow:hidden; }
.view-section-header { padding:1rem 1.5rem; font-weight:700; font-size:1rem; display:flex; align-items:center; gap:0.75rem; }
.view-section-body { padding:1.5rem; border-top:1px solid #e2e8f0; }
.view-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; }
.view-item label { font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; color:#a0aec0; font-weight:600; display:block; margin-bottom:4px; }
.view-item p { color:#2d3748; margin:0; font-weight:500; }
.view-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
.view-table th { background:#f7fafc; padding:8px 12px; text-align:left; font-weight:600; color:#4a5568; border:1px solid #e2e8f0; }
.view-table td { padding:8px 12px; border:1px solid #e2e8f0; }
.score-bar { height:8px; border-radius:4px; background:#e2e8f0; overflow:hidden; }
.score-bar-fill { height:100%; border-radius:4px; background:linear-gradient(90deg,#3182ce,#1e3a5f); transition:width 0.5s; }
@media(max-width:640px) { .view-grid { grid-template-columns:1fr; } }
</style>

<div style="max-width:950px;margin:0 auto;">
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#1e3a5f,#2d5a87);color:#fff;padding:1.5rem 2rem;border-radius:12px;margin-bottom:1.5rem;">
        <h2 style="margin:0;font-size:1.3rem;"><i class="fas fa-graduation-cap"></i> ANNUAL APPRAISAL FOR ACADEMIC STAFF</h2>
        <p style="margin:0.5rem 0 0;opacity:0.85;"><?php echo escapeOutput($eval['last_name'] . ', ' . $eval['first_name'] . ' (' . $eval['sid'] . ')'); ?> · Period: <?php echo formatDate($eval['period_from']); ?> – <?php echo formatDate($eval['period_to']); ?></p>
        <p style="margin:0.5rem 0 0;"><span style="background:rgba(255,255,255,0.2);padding:4px 12px;border-radius:12px;font-size:0.8rem;"><?php echo getAEStatus($eval['status']); ?></span></p>
    </div>
    
    <!-- Part A -->
    <div class="view-section">
        <div class="view-section-header" style="background:#ebf8ff;color:#2b6cb0;"><i class="fas fa-user"></i> Part A — Staff Input <?php echo $eval['part_a_signed_at'] ? '<span style="margin-left:auto;font-size:0.75rem;color:#38a169;"><i class="fas fa-check-circle"></i> Signed ' . formatDate($eval['part_a_signed_at']) . '</span>' : ''; ?></div>
        <div class="view-section-body">
            <?php if ($eval['part_a_signed_at']): ?>
                <div class="view-grid">
                    <div class="view-item"><label>Title</label><p><?php echo escapeOutput($eval['title'] ?? 'N/A'); ?></p></div>
                    <div class="view-item"><label>Faculty</label><p><?php echo escapeOutput($eval['faculty'] ?? 'N/A'); ?></p></div>
                    <div class="view-item"><label>Department</label><p><?php echo escapeOutput($eval['department'] ?? $eval['department_name'] ?? 'N/A'); ?></p></div>
                    <div class="view-item"><label>Present Rank</label><p><?php echo escapeOutput($eval['present_rank'] ?? 'N/A'); ?></p></div>
                    <div class="view-item"><label>Date First Appointed</label><p><?php echo formatDate($eval['date_first_appointment']); ?></p></div>
                    <div class="view-item"><label>Date Present Rank</label><p><?php echo formatDate($eval['date_present_rank']); ?></p></div>
                </div>
                
                <?php if (!empty($qualifications)): ?>
                <h4 style="color:#4a5568;margin:1.5rem 0 0.5rem;font-size:0.9rem;">Qualifications</h4>
                <table class="view-table"><thead><tr><th>Degree</th><th>Class/Grade</th><th>Institution</th><th>Year</th></tr></thead><tbody>
                    <?php foreach ($qualifications as $q): ?><tr><td><?php echo escapeOutput($q['degree']); ?></td><td><?php echo escapeOutput($q['class_grade']); ?></td><td><?php echo escapeOutput($q['institution']); ?></td><td><?php echo $q['year_obtained']; ?></td></tr><?php endforeach; ?>
                </tbody></table>
                <?php endif; ?>
                
                <?php if (!empty($courseList)): ?>
                <h4 style="color:#4a5568;margin:1.5rem 0 0.5rem;font-size:0.9rem;">Courses Taught</h4>
                <table class="view-table"><thead><tr><th>Code</th><th>Title</th><th>Level</th><th>Semester</th></tr></thead><tbody>
                    <?php foreach ($courseList as $c): ?><tr><td><?php echo escapeOutput($c['course_code']); ?></td><td><?php echo escapeOutput($c['course_title']); ?></td><td><?php echo escapeOutput($c['level']); ?></td><td><?php echo escapeOutput($c['semester']); ?></td></tr><?php endforeach; ?>
                </tbody></table>
                <?php endif; ?>
                
                <?php if (!empty($publications)): ?>
                <h4 style="color:#4a5568;margin:1.5rem 0 0.5rem;font-size:0.9rem;">Publications</h4>
                <table class="view-table"><thead><tr><th>Type</th><th>Title</th><th>Authors</th><th>Journal</th><th>Year</th></tr></thead><tbody>
                    <?php foreach ($publications as $p): ?><tr><td><?php echo ucfirst($p['pub_type']); ?></td><td><?php echo escapeOutput($p['title']); ?></td><td><?php echo escapeOutput($p['authors']); ?></td><td><?php echo escapeOutput($p['journal_name']); ?></td><td><?php echo $p['year_published']; ?></td></tr><?php endforeach; ?>
                </tbody></table>
                <?php endif; ?>
                
                <?php if ($eval['research_summary']): ?>
                <h4 style="color:#4a5568;margin:1.5rem 0 0.5rem;font-size:0.9rem;">Research Summary</h4>
                <p style="color:#4a5568;font-size:0.9rem;"><?php echo nl2br(escapeOutput($eval['research_summary'])); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p style="color:#a0aec0;text-align:center;padding:1rem;">Part A has not been completed yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Part B -->
    <div class="view-section">
        <div class="view-section-header" style="background:#e9d8fd;color:#553c9a;"><i class="fas fa-user-tie"></i> Part B — HOD Assessment <?php echo $eval['part_b_signed_at'] ? '<span style="margin-left:auto;font-size:0.75rem;color:#38a169;"><i class="fas fa-check-circle"></i> Signed ' . formatDate($eval['part_b_signed_at']) . '</span>' : ''; ?></div>
        <div class="view-section-body">
            <?php if ($eval['part_b_signed_at']): ?>
                <div class="view-grid">
                    <?php foreach(['Teaching'=>'teaching_score','Research'=>'research_score','Administration'=>'admin_score','Community'=>'community_score'] as $label=>$field): ?>
                    <div class="view-item" style="padding:1rem;background:#f7fafc;border-radius:8px;">
                        <label><?php echo $label; ?></label>
                        <div style="display:flex;align-items:center;gap:1rem;margin-top:4px;">
                            <div class="score-bar" style="flex:1;"><div class="score-bar-fill" style="width:<?php echo $eval[$field]; ?>%;"></div></div>
                            <strong style="color:#1e3a5f;"><?php echo $eval[$field]; ?>/100</strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align:center;margin-top:1rem;padding:1rem;background:linear-gradient(135deg,#1e3a5f,#2d5a87);color:#fff;border-radius:8px;">
                    <span style="font-size:1.5rem;font-weight:700;"><?php echo $eval['overall_score']; ?></span>
                    <span style="opacity:0.8;margin-left:0.5rem;">Grade <?php echo $eval['overall_grade']; ?></span>
                </div>
                <?php if ($eval['hod_recommendation']): ?>
                <p style="margin-top:1rem;color:#4a5568;"><strong>Recommendation:</strong> <?php echo escapeOutput($eval['hod_recommendation']); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p style="color:#a0aec0;text-align:center;padding:1rem;">Part B has not been completed yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Part C -->
    <div class="view-section">
        <div class="view-section-header" style="background:#fed7e2;color:#97266d;"><i class="fas fa-comment-alt"></i> Part C — Staff Response <?php echo $eval['part_c_signed_at'] ? '<span style="margin-left:auto;font-size:0.75rem;color:#38a169;"><i class="fas fa-check-circle"></i> Signed ' . formatDate($eval['part_c_signed_at']) . '</span>' : ''; ?></div>
        <div class="view-section-body">
            <?php if ($eval['part_c_signed_at']): ?>
                <p><strong>Agrees:</strong> <?php echo escapeOutput($eval['staff_agrees']); ?></p>
                <p style="margin-top:0.5rem;"><?php echo nl2br(escapeOutput($eval['staff_comments'] ?? 'No comments')); ?></p>
            <?php else: ?>
                <p style="color:#a0aec0;text-align:center;padding:1rem;">Part C has not been completed yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Part D -->
    <div class="view-section">
        <div class="view-section-header" style="background:#fefcbf;color:#744210;"><i class="fas fa-university"></i> Part D — Dean of Faculty <?php echo $eval['part_d_signed_at'] ? '<span style="margin-left:auto;font-size:0.75rem;color:#38a169;"><i class="fas fa-check-circle"></i> Signed ' . formatDate($eval['part_d_signed_at']) . '</span>' : ''; ?></div>
        <div class="view-section-body">
            <?php if ($eval['part_d_signed_at']): ?>
                <p><strong>Dean:</strong> <?php echo escapeOutput($eval['part_d_signer_name']); ?></p>
                <p style="margin-top:0.5rem;"><strong>Recommendation:</strong> <?php echo ucfirst(str_replace('_', ' ', $eval['dean_recommendation'] ?? 'N/A')); ?></p>
                <?php if ($eval['dean_promotion_to']): ?><p><strong>Promote to:</strong> <?php echo escapeOutput($eval['dean_promotion_to']); ?></p><?php endif; ?>
                <?php if ($eval['dean_comments']): ?><p style="margin-top:0.5rem;"><?php echo nl2br(escapeOutput($eval['dean_comments'])); ?></p><?php endif; ?>
            <?php else: ?>
                <p style="color:#a0aec0;text-align:center;padding:1rem;">Part D has not been completed yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Part E -->
    <div class="view-section">
        <div class="view-section-header" style="background:#c6f6d5;color:#276749;"><i class="fas fa-user-shield"></i> Part E — HR Officer <?php echo $eval['part_e_signed_at'] ? '<span style="margin-left:auto;font-size:0.75rem;color:#38a169;"><i class="fas fa-check-circle"></i> Signed ' . formatDate($eval['part_e_signed_at']) . '</span>' : ''; ?></div>
        <div class="view-section-body">
            <?php if ($eval['part_e_signed_at']): ?>
                <div class="view-grid">
                    <div class="view-item"><label>Year 1 Score</label><p><?php echo $eval['hr_score_year1']; ?></p></div>
                    <div class="view-item"><label>Year 2 Score</label><p><?php echo $eval['hr_score_year2']; ?></p></div>
                    <div class="view-item"><label>Year 3 Score</label><p><?php echo $eval['hr_score_year3']; ?></p></div>
                </div>
                <?php if ($eval['hr_notes']): ?><p style="margin-top:1rem;"><?php echo nl2br(escapeOutput($eval['hr_notes'])); ?></p><?php endif; ?>
            <?php else: ?>
                <p style="color:#a0aec0;text-align:center;padding:1rem;">Part E has not been completed yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Part F -->
    <div class="view-section">
        <div class="view-section-header" style="background:#feebc8;color:#744210;"><i class="fas fa-gavel"></i> Part F — A&P Committee <?php echo $eval['part_f_signed_at'] ? '<span style="margin-left:auto;font-size:0.75rem;color:#38a169;"><i class="fas fa-check-circle"></i> Signed ' . formatDate($eval['part_f_signed_at']) . '</span>' : ''; ?></div>
        <div class="view-section-body">
            <?php if ($eval['part_f_signed_at']): ?>
                <p><strong>Decision:</strong> <?php echo ucfirst(str_replace('_', ' ', $eval['committee_decision'] ?? 'N/A')); ?></p>
                <?php if ($eval['committee_decision_details']): ?><p style="margin-top:0.5rem;"><?php echo nl2br(escapeOutput($eval['committee_decision_details'])); ?></p><?php endif; ?>
                <?php if ($eval['committee_effective_date']): ?><p><strong>Effective Date:</strong> <?php echo formatDate($eval['committee_effective_date']); ?></p><?php endif; ?>
                <p style="margin-top:0.5rem;"><strong>Signed by:</strong> <?php echo escapeOutput($eval['part_f_signer_name']); ?></p>
            <?php else: ?>
                <p style="color:#a0aec0;text-align:center;padding:1rem;">Part F has not been completed yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="margin-top:1rem;">
        <a href="list.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
