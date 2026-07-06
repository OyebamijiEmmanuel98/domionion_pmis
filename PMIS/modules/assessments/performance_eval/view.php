<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * PERFORMANCE EVALUATION - DIGITAL FORM VIEW / REVIEW
 * =====================================================
 */

require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

requireLogin();

$id = $_GET['id'] ?? ($_POST['appraisal_id'] ?? 0);
if (!$id) {
    setFlashMessage('error', 'No evaluation specified.');
    redirectBack();
}

// Fetch evaluation
$stmt = $pdo->prepare("
    SELECT pe.*, s.first_name, s.middle_name, s.last_name, s.staff_id as staff_code, d.department_name, s.rank
    FROM performance_appraisals pe
    JOIN staff s ON pe.staff_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE pe.id = ?
");
$stmt->execute([$id]);
$eval = $stmt->fetch();

if (!$eval) {
    setFlashMessage('error', 'Evaluation not found.');
    redirectBack();
}

$formData = json_decode($eval['form_data'], true) ?: [];
$isAcademic = ($eval['appraisal_type'] === 'academic');
$pageTitle = $isAcademic ? 'Academic Staff Appraisal Form' : 'Non-Academic Staff Appraisal Form';

// Workflow states
$canHODReview = (isHOD() || isAdmin() || isHR()) && $eval['status'] === 'pending_hod';
$canStaffReview = (getCurrentStaffId() == $eval['staff_id']) && $eval['status'] === 'pending_staff_review';
$canDeanReview = (isAdmin() || isHR()) && $eval['status'] === 'pending_dean'; // Using Admin for Dean role placeholders
$canHRReview = (isHR() || isAdmin()) && $eval['status'] === 'pending_hr';
$canCommitteeReview = (isAdmin() || isHR()) && $eval['status'] === 'pending_committee';

// Process POST updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionPart = $_POST['action_part'] ?? '';
    
    // Merge new post data into existing formData
    $newFormData = array_merge($formData, $_POST);
    // Ignore control fields
    unset($newFormData['appraisal_id'], $newFormData['action_part'], $newFormData['csrf_token']);
    
    $newStatus = $eval['status'];
    
    if ($actionPart === 'part_b' && $canHODReview) {
        $newStatus = 'pending_staff_review';
        setFlashMessage('success', 'Part B (HOD Assessment) submitted successfully.');
    } elseif ($actionPart === 'part_c' && $canStaffReview) {
        $newStatus = 'pending_dean'; // Academic goes to Dean, NonAcademic goes to HR possibly, standardizing to Dean for both as generic workflow
        setFlashMessage('success', 'Part C (Staff Comments) submitted successfully.');
    } elseif ($actionPart === 'part_d' && $canDeanReview) {
        $newStatus = 'pending_hr';
        setFlashMessage('success', 'Part D (Dean Review) submitted successfully.');
    } elseif ($actionPart === 'part_e' && $canHRReview) {
        $newStatus = 'pending_committee';
        setFlashMessage('success', 'Part E (HR Assessment) submitted successfully.');
    } elseif ($actionPart === 'part_f' && $canCommitteeReview) {
        $newStatus = 'completed';
        setFlashMessage('success', 'Part F (Committee Decision) submitted and finalized.');
    }

    $stmt = $pdo->prepare("UPDATE performance_appraisals SET form_data = ?, status = ? WHERE id = ?");
    $stmt->execute([json_encode($newFormData), $newStatus, $id]);
    
    header("Location: view.php?id=$id");
    exit;
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';

// Helper function safely outputs data
function v($key, $def = '-') {
    global $formData;
    return nl2br(escapeOutput($formData[$key] ?? $def));
}
?>

<style>
.digital-form-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
.form-header { text-align: center; border-bottom: 3px double #2b6cb0; padding-bottom: 20px; margin-bottom: 30px; }
.form-header h2 { color: #1e3a5f; margin: 0; font-size: 1.5rem; text-transform: uppercase; font-weight: 700; line-height: 1.4; }
.form-header h3 { color: #4a5568; margin: 5px 0 0 0; font-size: 1.1rem; }
.form-section { margin-bottom: 30px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.section-title { background: #f7fafc; color: #2d3748; padding: 12px 20px; font-weight: bold; margin: 0; border-bottom: 1px solid #e2e8f0; }
.section-content { padding: 25px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
.form-row.full { grid-template-columns: 1fr; }
.form-row.thirds { grid-template-columns: 1fr 1fr 1fr; }
.label-field { font-weight: 600; color: #4a5568; margin-bottom: 4px; display: block; font-size: 0.9rem; }
.value-field { background: #fdfdfe; padding: 10px 15px; border-radius: 4px; border: 1px dashed #cbd5e0; color: #1a202c; min-height: 42px; }
.signature { font-family: 'Brush Script MT', 'Segoe Script', cursive; font-size: 1.4rem; color: #2b6cb0; }
.action-box { border: 2px solid #3182ce; padding: 20px; border-radius: 8px; margin-top: 20px; background: #ebf8ff; }
.action-box h4 { margin-top: 0; color: #2c5282; border-bottom: 1px solid #bee3f8; padding-bottom: 10px; margin-bottom: 20px; }
.scoring-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
.scoring-table th, .scoring-table td { border: 1px solid #cbd5e0; padding: 10px; text-align: left; }
.scoring-table th { background: #edf2f7; font-weight: 600; }
@media print {
    body * { visibility: hidden; }
    .digital-form-container, .digital-form-container * { visibility: visible; }
    .digital-form-container { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; padding: 0; }
    .sidebar, .top-nav, .btn { display: none !important; }
}
</style>

<div class="digital-form-container">
    <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;" class="no-print">
        <button onclick="window.print()" class="btn btn-outline" style="background:#fff;"><i class="fas fa-print"></i> Generate PDF / Print</button>
    </div>

    <div class="form-header">
        <h2>DOMINION UNIVERSITY, IBADAN</h2>
        <h3>ANNUAL PERFORMANCE EVALUATION REPORT</h3>
        <p style="margin-top: 10px; font-weight: 500;"><?php echo $isAcademic ? 'FOR ACADEMIC STAFF' : 'FOR ADMINISTRATIVE, TECHNICAL AND PROFESSIONAL STAFF'; ?></p>
        <p>Period: <?php echo formatDate($eval['period_from']); ?> to <?php echo formatDate($eval['period_to']); ?></p>
        <div style="margin-top: 10px;">
            <span class="badge <?php echo getEvalStatusBadgeClass($eval['status']); ?>">
                STATUS: <?php echo strtoupper(getEvalStatusLabel($eval['status'])); ?>
            </span>
        </div>
    </div>

    <!-- PART A: INDIVIDUAL MEMBER OF STAFF -->
    <div class="form-section">
        <h3 class="section-title">PART A (Completed by Staff) - Personal Details</h3>
        <div class="section-content">
            <div class="form-row thirds">
                <div><span class="label-field">Name</span><div class="value-field"><?php echo escapeOutput($eval['last_name'].' '.$eval['first_name'].' '.$eval['middle_name']); ?></div></div>
                <div><span class="label-field">Title</span><div class="value-field"><?php echo v('title'); ?></div></div>
                <div><span class="label-field">Date of Birth</span><div class="value-field"><?php echo v('dob'); ?></div></div>
            </div>
            <div class="form-row thirds">
                <div><span class="label-field">Age</span><div class="value-field"><?php echo v('age'); ?></div></div>
                <div><span class="label-field">Marital Status</span><div class="value-field"><?php echo v('marital_status'); ?></div></div>
                <div><span class="label-field">College/Department/Unit</span><div class="value-field"><?php echo escapeOutput($eval['department_name'] ?? '-'); ?></div></div>
            </div>
            <div class="form-row">
                <div><span class="label-field">Date of First Appointment</span><div class="value-field"><?php echo v('date_first_appointment'); ?></div></div>
                <div><span class="label-field">Grade on First Appt.</span><div class="value-field"><?php echo v('grade_first_appointment'); ?></div></div>
            </div>
            <div class="form-row thirds">
                <div><span class="label-field">Current Grade</span><div class="value-field"><?php echo escapeOutput($eval['rank']); ?></div></div>
                <div><span class="label-field">Date Promoted</span><div class="value-field"><?php echo v('date_promoted'); ?></div></div>
                <div><span class="label-field">Appointment Confirmed?</span><div class="value-field"><?php echo v('is_confirmed'); ?> (<?php echo v('date_confirmation'); ?>)</div></div>
            </div>
            <div class="form-row thirds">
                <div><span class="label-field">Acting Appointment</span><div class="value-field"><?php echo v('acting_appointment'); ?></div></div>
                <div><span class="label-field">Present Salary (p.a.)</span><div class="value-field"><?php echo v('salary'); ?></div></div>
                <div><span class="label-field">Grade Level/Step</span><div class="value-field"><?php echo v('grade_level'); ?></div></div>
            </div>
        </div>
    </div>

    <!-- PART A: Activities -->
    <div class="form-section">
        <h3 class="section-title">PART A - Activities & Experience</h3>
        <div class="section-content">
            <?php if ($isAcademic): ?>
                <div class="form-row full"><div><span class="label-field">Courses Taught</span><div class="value-field"><?php echo v('courses_taught'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Supervision of Students</span><div class="value-field"><?php echo v('industrial_supervision'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Final Year Undergrad Projects</span><div class="value-field"><?php echo v('ug_supervision'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Graduate Supervision</span><div class="value-field"><?php echo v('pg_supervision'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Research in Progress</span><div class="value-field"><?php echo v('research_progress'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Research Completed / Thesis</span><div class="value-field"><?php echo v('research_completed'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Publications</span><div class="value-field"><?php echo v('publications'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Extra-Curricular Activities</span><div class="value-field"><?php echo v('extra_curricular'); ?></div></div></div>
            <?php else: ?>
                <div class="form-row full"><div><span class="label-field">Courses/Conferences Attended</span><div class="value-field"><?php echo v('courses_attended'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Academic & Professional Qualifications</span><div class="value-field"><?php echo v('qualifications'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Job Description prior to period</span><div class="value-field"><?php echo v('job_desc_prior'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Main Official Duties performed</span><div class="value-field"><?php echo v('duties_performed'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Important Ad Hoc Duties</span><div class="value-field"><?php echo v('adhoc_duties'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Contribution to University</span><div class="value-field"><?php echo v('activities_within'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Contribution to Society</span><div class="value-field"><?php echo v('activities_outside'); ?></div></div></div>
                <div class="form-row full"><div><span class="label-field">Publications (Creative writing/etc.)</span><div class="value-field"><?php echo v('publications'); ?></div></div></div>
            <?php endif; ?>
            
            <div class="form-row" style="margin-top: 20px;">
                <div><span class="label-field">Applicant Signature</span><div class="value-field signature"><?php echo v('applicant_signature'); ?></div></div>
            </div>
        </div>
    </div>

    <!-- PART B: HOD Assessment (Will show box if completed) -->
    <?php if ($eval['status'] !== 'pending_hod' && !empty($formData['hod_remarks'])): ?>
    <div class="form-section">
        <h3 class="section-title">PART B (Head of Department) - Assessment</h3>
        <div class="section-content">
            <?php if ($isAcademic): ?>
                <table class="scoring-table">
                    <tr><th>Criteria</th><th>Max Score</th><th>Actual Score</th></tr>
                    <tr><td>Qualifications</td><td>10</td><td><?php echo v('score_qual', '0'); ?></td></tr>
                    <tr><td>Research & Publication</td><td>50</td><td><?php echo v('score_research', '0'); ?></td></tr>
                    <tr><td>Teaching Duty</td><td>20</td><td><?php echo v('score_teaching', '0'); ?></td></tr>
                    <tr><td>Student Assessment</td><td>5</td><td><?php echo v('score_student', '0'); ?></td></tr>
                    <tr><td>Contribution (University)</td><td>10</td><td><?php echo v('score_univ', '0'); ?></td></tr>
                    <tr><td>Contribution (Outside)</td><td>5</td><td><?php echo v('score_out', '0'); ?></td></tr>
                    <tr><th>Total</th><th>100</th><th><?php echo v('score_total', '0'); ?></th></tr>
                </table>
            <?php else: ?>
                <div class="form-row">
                    <div><span class="label-field">Sick Leave (With Cert) Days</span><div class="value-field"><?php echo v('sick_with_cert'); ?></div></div>
                    <div><span class="label-field">Sick Leave (Without Cert) Days</span><div class="value-field"><?php echo v('sick_without_cert'); ?></div></div>
                </div>
                <div class="form-row full"><div><span class="label-field">Meritorious Work</span><div class="value-field"><?php echo v('meritorious_work'); ?></div></div></div>
            <?php endif; ?>
            <div class="form-row full"><div><span class="label-field">General Remarks & Training Needs</span><div class="value-field"><?php echo v('hod_remarks'); ?></div></div></div>
            <div class="form-row"><div><span class="label-field">HOD Signature</span><div class="value-field signature"><?php echo v('hod_signature'); ?></div></div></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- WORKFLOW ACTION BOXES -->

    <?php if ($canHODReview): ?>
    <div class="action-box">
        <form method="POST" action="">
            <?php echo csrfField(); ?><input type="hidden" name="action_part" value="part_b">
            <h4><i class="fas fa-edit"></i> PART B - Head of Department Assessment</h4>
            
            <?php if ($isAcademic): ?>
                <p>Quantitative Evaluation (Scores out of Max):</p>
                <div class="form-grid-3">
                    <div class="form-group"><label>Qualifications (10)</label><input type="number" name="score_qual" class="form-control" max="10"></div>
                    <div class="form-group"><label>Research (50)</label><input type="number" name="score_research" class="form-control" max="50"></div>
                    <div class="form-group"><label>Teaching (20)</label><input type="number" name="score_teaching" class="form-control" max="20"></div>
                    <div class="form-group"><label>Student Assess (5)</label><input type="number" name="score_student" class="form-control" max="5"></div>
                    <div class="form-group"><label>Contrib Univ (10)</label><input type="number" name="score_univ" class="form-control" max="10"></div>
                    <div class="form-group"><label>Contrib Outside (5)</label><input type="number" name="score_out" class="form-control" max="5"></div>
                </div>
            <?php else: ?>
                <div class="form-grid-2">
                    <div class="form-group"><label>Sick Leave (With Cert) Days</label><input type="number" name="sick_with_cert" class="form-control"></div>
                    <div class="form-group"><label>Sick Leave (Without Cert) Days</label><input type="number" name="sick_without_cert" class="form-control"></div>
                </div>
                <div class="form-group"><label>Meritorious Work Performed</label><textarea name="meritorious_work" class="form-control"></textarea></div>
            <?php endif; ?>
            
            <div class="form-group" style="margin-top:10px;"><label>General Remarks & Training Needs</label><textarea name="hod_remarks" class="form-control" rows="3" required></textarea></div>
            <div class="form-group"><label>HOD Signature (Typed Name)</label><input type="text" name="hod_signature" class="form-control" required></div>
            <button type="submit" class="btn btn-primary" style="margin-top:15px;"><i class="fas fa-paper-plane"></i> Submit Assessment</button>
        </form>
    </div>
    <?php endif; ?>
    
    <?php if ($canStaffReview): ?>
    <div class="action-box">
        <form method="POST" action="">
            <?php echo csrfField(); ?><input type="hidden" name="action_part" value="part_c">
            <h4><i class="fas fa-edit"></i> PART C - Staff Comments (Acknowledge Review)</h4>
            <p>I certify that I have read the contents of this report and that my HOD has discussed them with me.</p>
            <div class="form-group"><label>Staff Comments / Remarks</label><textarea name="staff_remarks" class="form-control" rows="3"></textarea></div>
            <div class="form-group"><label>Signature (Typed Name)</label><input type="text" name="staff_ack_signature" class="form-control" required></div>
            <button type="submit" class="btn btn-primary" style="margin-top:15px;"><i class="fas fa-check-circle"></i> Acknowledge & Forward</button>
        </form>
    </div>
    <?php elseif (!empty($formData['staff_remarks'])): ?>
    <div class="form-section">
        <h3 class="section-title">PART C (Staff Comments)</h3>
        <div class="section-content">
            <div class="form-row full"><div><span class="label-field">Comments</span><div class="value-field"><?php echo v('staff_remarks'); ?></div></div></div>
            <div class="form-row"><div><span class="label-field">Signature</span><div class="value-field signature"><?php echo v('staff_ack_signature'); ?></div></div></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canDeanReview): ?>
    <div class="action-box">
        <form method="POST" action="">
            <?php echo csrfField(); ?><input type="hidden" name="action_part" value="part_d">
            <h4><i class="fas fa-edit"></i> PART D - Dean of Faculty / Final HOD Recommendation</h4>
            <div class="form-group"><label>Recommendation for Confirmation / Promotion</label><select name="dean_recommendation" class="form-control"><option>Confirmed to retiring age</option><option>Normal Promotion</option><option>Accelerated Promotion</option><option>Not recommended for promotion</option><option>Grant Annual Increment</option></select></div>
            <div class="form-group"><label>Justifications / Reasons</label><textarea name="dean_justification" class="form-control" rows="3" required></textarea></div>
            <div class="form-group"><label>Signature (Typed Name)</label><input type="text" name="dean_signature" class="form-control" required></div>
            <button type="submit" class="btn btn-primary" style="margin-top:15px;"><i class="fas fa-save"></i> Submit Dean Decision</button>
        </form>
    </div>
    <?php elseif (!empty($formData['dean_justification'])): ?>
    <div class="form-section">
        <h3 class="section-title">PART D (Dean / Committee Chairman)</h3>
        <div class="section-content">
            <div class="form-row"><div><span class="label-field">Recommendation</span><div class="value-field"><?php echo v('dean_recommendation'); ?></div></div></div>
            <div class="form-row full"><div><span class="label-field">Justifications</span><div class="value-field"><?php echo v('dean_justification'); ?></div></div></div>
            <div class="form-row"><div><span class="label-field">Signature</span><div class="value-field signature"><?php echo v('dean_signature'); ?></div></div></div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($canHRReview): ?>
    <div class="action-box">
        <form method="POST" action="">
            <?php echo csrfField(); ?><input type="hidden" name="action_part" value="part_e">
            <h4><i class="fas fa-edit"></i> PART E - HR Officer</h4>
            <div class="form-group"><label>Scores for history / Notes</label><textarea name="hr_notes" class="form-control" rows="2" required></textarea></div>
            <div class="form-group"><label>HR Signature (Typed Name)</label><input type="text" name="hr_signature" class="form-control" required></div>
            <button type="submit" class="btn btn-primary" style="margin-top:15px;"><i class="fas fa-save"></i> Submit HR Notes</button>
        </form>
    </div>
    <?php elseif (!empty($formData['hr_notes'])): ?>
    <div class="form-section">
        <h3 class="section-title">PART E (HR Officer)</h3>
        <div class="section-content">
            <div class="form-row full"><div><span class="label-field">HR Notes/Scores</span><div class="value-field"><?php echo v('hr_notes'); ?></div></div></div>
            <div class="form-row"><div><span class="label-field">Signature</span><div class="value-field signature"><?php echo v('hr_signature'); ?></div></div></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canCommitteeReview): ?>
    <div class="action-box">
        <form method="POST" action="">
            <?php echo csrfField(); ?><input type="hidden" name="action_part" value="part_f">
            <h4><i class="fas fa-edit"></i> PART F - Appointments & Promotion Committee</h4>
            <div class="form-group"><label>Final Committee Recommendation</label><textarea name="committee_recom" class="form-control" rows="3" required></textarea></div>
            <div class="form-group"><label>Committee Chair Signature (Typed Name)</label><input type="text" name="committee_signature" class="form-control" required></div>
            <button type="submit" class="btn btn-success" style="margin-top:15px;"><i class="fas fa-check"></i> Finalize Appraisal Process</button>
        </form>
    </div>
    <?php elseif ($eval['status'] === 'completed'): ?>
    <div class="form-section" style="border-color: #48bb78;">
        <h3 class="section-title" style="background: #f0fff4; color: #276749;">PART F (A&P Committee) - FINAL DECISION</h3>
        <div class="section-content">
            <div class="form-row full"><div><span class="label-field">Final Recommendation / Promotion Decision</span><div class="value-field"><?php echo v('committee_recom'); ?></div></div></div>
            <div class="form-row"><div><span class="label-field">Signature</span><div class="value-field signature"><?php echo v('committee_signature'); ?></div></div></div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once '../../../includes/footer.php'; ?>
