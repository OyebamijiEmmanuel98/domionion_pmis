<?php
/**
 * DOMINION UNIVERSITY, IBADAN
 * PERFORMANCE EVALUATION FOR NON-ACADEMIC STAFF (VIEW/REVIEW)
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

$stmt = $pdo->prepare("SELECT pe.*, s.first_name, s.middle_name, s.last_name, s.staff_id as staff_code, d.department_name, s.rank FROM performance_appraisals pe JOIN staff s ON pe.staff_id = s.id LEFT JOIN departments d ON s.department_id = d.id WHERE pe.id = ?");
$stmt->execute([$id]);
$eval = $stmt->fetch();

if (!$eval) {
    setFlashMessage('error', 'Evaluation not found.');
    redirectBack();
}

$formData = json_decode($eval['form_data'], true) ?: [];

$canHODReview = (isHOD() || isAdmin() || isHR()) && $eval['status'] === 'pending_hod';
$canStaffReview = (getCurrentStaffId() == $eval['staff_id']) && $eval['status'] === 'pending_staff_review';
$canHRReview = (isHR() || isAdmin()) && $eval['status'] === 'pending_hr';
$canFinalReview = (isAdmin() || isHR() || isHOD()) && $eval['status'] === 'pending_committee';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionPart = $_POST['action_part'] ?? '';
    $newFormData = array_merge($formData, $_POST);
    unset($newFormData['appraisal_id'], $newFormData['action_part'], $newFormData['csrf_token']);
    $newStatus = $eval['status'];
    
    if ($actionPart === 'part_b' && $canHODReview) {
        $newStatus = 'pending_staff_review';
        setFlashMessage('success', 'Part B (Supervisor Assessment) submitted successfully.');
    } elseif ($actionPart === 'part_c' && $canStaffReview) {
        $newStatus = 'pending_hr'; // goes to HR next
        setFlashMessage('success', 'Part C (Staff Comments) submitted successfully.');
    } elseif ($actionPart === 'part_d' && $canHRReview) {
        $newStatus = 'pending_committee';
        setFlashMessage('success', 'Part D (HR Review & Final Assessment) submitted successfully.');
    } elseif ($actionPart === 'part_e' && $canFinalReview) {
        $newStatus = 'completed';
        setFlashMessage('success', 'Part E (Decision Section) finalized successfully.');
    }

    $stmt = $pdo->prepare("UPDATE performance_appraisals SET form_data = ?, status = ? WHERE id = ?");
    $stmt->execute([json_encode($newFormData), $newStatus, $id]);
    header("Location: view_non_academic.php?id=$id");
    exit;
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';

function v($key, $def = '-') {
    global $formData;
    if (is_array($formData[$key] ?? null)) return escapeOutput(implode(', ', $formData[$key]));
    return nl2br(escapeOutput($formData[$key] ?? $def));
}

function r($key) {
    global $formData;
    $val = $formData[$key] ?? '';
    $map = ['5'=>'Outstanding (5)', '4'=>'Very Good (4)', '3'=>'Good (3)', '2'=>'Fair (2)', '1'=>'Unsatisfactory (1)'];
    return $map[$val] ?? '-';
}
?>
<style>
.digital-form-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
.form-header { text-align: center; border-bottom: 3px double #2b6cb0; padding-bottom: 20px; margin-bottom: 30px; }
.form-header h2 { color: #1e3a5f; margin: 0; font-size: 1.5rem; font-weight: 700; }
.form-section { margin-bottom: 30px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.section-title { background: #f7fafc; color: #2d3748; padding: 12px 20px; font-weight: bold; margin: 0; border-bottom: 1px solid #e2e8f0; }
.section-content { padding: 25px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
.form-row.full { grid-template-columns: 1fr; }
.label-field { font-weight: 600; color: #4a5568; margin-bottom: 4px; display: block; font-size: 0.9rem; }
.value-field { background: #fdfdfe; padding: 10px 15px; border-radius: 4px; border: 1px dashed #cbd5e0; color: #1a202c; min-height: 42px; }
.action-box { border: 2px solid #3182ce; padding: 20px; border-radius: 8px; margin-top: 20px; background: #ebf8ff; }
.action-box h4 { margin-top: 0; color: #2c5282; border-bottom: 1px solid #bee3f8; padding-bottom: 10px; margin-bottom: 20px; }
@media print { body * { visibility: hidden; } .digital-form-container, .digital-form-container * { visibility: visible; } .digital-form-container { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; padding: 0; } .sidebar, .top-nav, .btn, .no-print { display: none !important; } }
</style>

<div class="digital-form-container">
    <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;" class="no-print">
        <button onclick="window.print()" class="btn btn-outline" style="background:#fff;"><i class="fas fa-print"></i> Generate PDF / Print</button>
    </div>

    <div class="form-header">
        <h2>DOMINION UNIVERSITY, IBADAN</h2>
        <h3>ANNUAL PERFORMANCE EVALUATION REPORT</h3>
        <p style="margin-top: 10px; font-weight: 500;">TARGET: ADMINISTRATIVE, TECHNICAL AND PROFESSIONAL STAFF</p>
        <p>Period: <?php echo v('period_from'); ?> to <?php echo v('period_to'); ?></p>
        <div style="margin-top: 10px;"><span class="badge badge-info">STATUS: <?php echo strtoupper($eval['status']); ?></span></div>
    </div>

    <!-- PART A -->
    <div class="form-section">
        <h3 class="section-title">SECTION 1: PERSONAL INFORMATION (PART A)</h3>
        <div class="section-content">
            <div class="form-row"><div><span class="label-field">Name</span><div class="value-field"><?php echo escapeOutput($eval['last_name'].' '.$eval['first_name'].' '.$eval['middle_name']); ?></div></div><div><span class="label-field">Title</span><div class="value-field"><?php echo v('title'); ?></div></div></div>
            <div class="form-row"><div><span class="label-field">Date of Birth</span><div class="value-field"><?php echo v('dob'); ?></div></div><div><span class="label-field">Age / Marital</span><div class="value-field"><?php echo v('age'); ?> / <?php echo v('marital_status'); ?></div></div></div>
            <div class="form-row"><div><span class="label-field">College/Dept.</span><div class="value-field"><?php echo escapeOutput($eval['department_name'] ?? '-'); ?></div></div><div><span class="label-field">Salary & Grade Step</span><div class="value-field"><?php echo v('salary'); ?> (GL: <?php echo v('grade_level'); ?>)</div></div></div>
            <div class="form-row full">
                <div><span class="label-field">Qualifications (Acad & Prof)</span><div class="value-field">See form data - Acad degrees: <?php echo v('acad_degree'); ?><br>Prof: <?php echo v('prof_qual'); ?></div></div>
            </div>
            <div class="form-row full">
                <div><span class="label-field">Main Duties (Report Period)</span><div class="value-field"><?php echo v('duties_performed'); ?></div></div>
            </div>
            <div class="form-row full">
                <div><span class="label-field">Staff Signature & Date</span><div class="value-field"><?php echo v('applicant_signature'); ?> (<?php echo v('applicant_date'); ?>)</div></div>
            </div>
        </div>
    </div>

    <!-- Supervisor Section (Part B) -->
    <?php if ($eval['status'] !== 'pending_hod' && !empty($formData['supervisor_name'])): ?>
    <div class="form-section">
        <h3 class="section-title">SECTION 7 & 8: SUPERVISOR ASSESSMENT (PART B)</h3>
        <div class="section-content">
            <div class="form-row"><div><span class="label-field">Sick Leave (w/ Cert)</span><div class="value-field"><?php echo v('sick_cert'); ?> days</div></div><div><span class="label-field">Sick Leave (w/o Cert)</span><div class="value-field"><?php echo v('sick_nocert'); ?> days</div></div></div>
            <div class="form-row full"><div><span class="label-field">Sanctions</span><div class="value-field"><?php echo v('sanctions_yesno'); ?>: <?php echo v('sanctions_details'); ?></div></div></div>
            <div class="form-row full"><div><span class="label-field">Main Work Performed</span><div class="value-field"><?php echo v('merit_work'); ?></div></div></div>
            <div class="form-row full"><div><span class="label-field">Quality of Work</span><div class="value-field"><?php echo r('rate_quality'); ?></div></div></div>
            <div class="form-row full"><div><span class="label-field">Punctuality</span><div class="value-field"><?php echo r('rate_punctual'); ?></div></div></div>
            <div class="form-row full"><div><span class="label-field">Overall Performance</span><div class="value-field"><?php echo v('overall_perf'); ?></div></div></div>
            <div class="form-row full"><div><span class="label-field">Supervisor</span><div class="value-field"><?php echo v('supervisor_name'); ?> | <?php echo v('supervisor_sig'); ?> | <?php echo v('supervisor_date'); ?></div></div></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Staff Comments (Part B - Section B) -->
    <?php if ($eval['status'] !== 'pending_staff_review' && $eval['status'] !== 'pending_hod' && !empty($formData['staff_comments'])): ?>
    <div class="form-section">
        <h3 class="section-title">SECTION 9: STAFF COMMENTS (PART B - SECTION B)</h3>
        <div class="section-content">
            <div class="form-row full"><div><span class="label-field">Comments</span><div class="value-field"><?php echo v('staff_comments'); ?></div></div></div>
            <div class="form-row full"><div><span class="label-field">Signature / Date</span><div class="value-field"><?php echo v('staff_comm_sig'); ?> | <?php echo v('staff_comm_date'); ?></div></div></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Boxes -->
    <?php if ($canHODReview): ?>
    <div class="action-box no-print">
        <form method="POST">
            <?php echo csrfField(); ?><input type="hidden" name="action_part" value="part_b">
            <h4>SECTION 7: SUPERVISOR ASSESSMENT (PART B - SECTION A)</h4>
            <div class="form-group"><label>Sick Leave (w/ Cert) days</label><input type="number" name="sick_cert" class="form-control" value="0"></div>
            <div class="form-group"><label>Sick Leave (w/o Cert) days</label><input type="number" name="sick_nocert" class="form-control" value="0"></div>
            <div class="form-group"><label>Any sanctions?</label><select name="sanctions_yesno" class="form-control"><option>No</option><option>Yes</option></select></div>
            <div class="form-group"><label>Sanctions Details</label><textarea name="sanctions_details" class="form-control"></textarea></div>
            <div class="form-group"><label>Main Work Performed</label><textarea name="merit_work" class="form-control" required></textarea></div>
            <div class="form-group"><label>Recommended Training</label><textarea name="rec_training" class="form-control"></textarea></div>
            
            <h4 style="margin-top:20px;">SECTION 8: PERFORMANCE RATING (5=Outstanding... 1=Unsatisfactory)</h4>
            <div class="form-row">
                <div class="form-group"><label>Quality of Work</label><select name="rate_quality" class="form-control"><option value="5">A (5)</option><option value="4">B (4)</option><option value="3">C (3)</option><option value="2">D (2)</option><option value="1">E (1)</option></select></div>
                <div class="form-group"><label>Punctuality/Regularity</label><select name="rate_punctual" class="form-control"><option value="5">A (5)</option><option value="4">B (4)</option><option value="3">C (3)</option><option value="2">D (2)</option><option value="1">E (1)</option></select></div>
            </div>
            <div class="form-group"><label>Overall Performance</label><select name="overall_perf" class="form-control"><option>Outstanding (4.5-5.0)</option><option>Very Good (3.5-4.49)</option><option>Good (2.5-3.49)</option><option>Fair</option><option>Unsatisfactory</option></select></div>
            
            <div class="form-group"><label>Supervisor Name</label><input type="text" name="supervisor_name" class="form-control" required></div>
            <div class="form-group"><label>Signature (Typed)</label><input type="text" name="supervisor_sig" class="form-control" required></div>
            <div class="form-group"><label>Date</label><input type="date" name="supervisor_date" class="form-control" required></div>
            <button type="submit" class="btn btn-primary" style="margin-top:10px;">Submit Supervisor Assessment</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($canStaffReview): ?>
    <div class="action-box no-print">
        <form method="POST">
            <?php echo csrfField(); ?><input type="hidden" name="action_part" value="part_c">
            <h4>SECTION 9: STAFF COMMENTS (PART B - SECTION B)</h4>
            <p>"I certify that I have read the contents of this report and it has been discussed with me."</p>
            <div class="form-group"><label>Comments</label><textarea name="staff_comments" class="form-control" required></textarea></div>
            <div class="form-group"><label>Signature (Typed)</label><input type="text" name="staff_comm_sig" class="form-control" required></div>
            <div class="form-group"><label>Date</label><input type="date" name="staff_comm_date" class="form-control" required></div>
            <button type="submit" class="btn btn-primary">Acknowledge</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($canHRReview): ?>
    <div class="action-box no-print">
        <form method="POST">
            <?php echo csrfField(); ?><input type="hidden" name="action_part" value="part_d">
            <h4>SECTION 10 & 11: HR REVIEW / FINAL ASSESSMENT</h4>
            <div class="form-row">
                <div class="form-group"><label>Avg Score Yr 1</label><input type="number" name="avg_yr1" class="form-control"></div>
                <div class="form-group"><label>Avg Score Yr 2</label><input type="number" name="avg_yr2" class="form-control"></div>
                <div class="form-group"><label>Avg Score Yr 3</label><input type="number" name="avg_yr3" class="form-control"></div>
            </div>
            <div class="form-group"><label>Areas Requiring Improvement (Needs)</label><textarea name="training_needs" class="form-control"></textarea></div>
            <div class="form-group"><label>HR Officer Name & Sig</label><input type="text" name="hr_officer_sig" class="form-control" required></div>
            <button type="submit" class="btn btn-primary">Submit HR Review</button>
        </form>
    </div>
    <?php endif; ?>
    
    <?php if ($canFinalReview): ?>
    <div class="action-box no-print">
        <form method="POST">
            <?php echo csrfField(); ?><input type="hidden" name="action_part" value="part_e">
            <h4>SECTION 12: DECISION SECTION</h4>
            <div class="form-group"><label>Annual Increment</label><select name="dec_increment" class="form-control"><option>Grant Increment</option><option>Do Not Grant</option><option>Delay Increment</option></select></div>
            <div class="form-group"><label>Confirmation</label><select name="dec_confirm" class="form-control"><option>Confirm Appointment</option><option>Extend Appointment (6 mo)</option><option>Terminate</option></select></div>
            <div class="form-group"><label>Promotion</label><select name="dec_promo" class="form-control"><option>Recommend Normal Promotion</option><option>Recommend Accelerated Promotion</option><option>Not Recommended</option></select></div>
            <div class="form-group"><label>Head of Dept / Chair Signature</label><input type="text" name="final_auth_sig" class="form-control" required></div>
            <button type="submit" class="btn btn-success">Finalize Appraisal</button>
        </form>
    </div>
    <?php endif; ?>

</div>
<?php require_once '../../../includes/footer.php'; ?>
