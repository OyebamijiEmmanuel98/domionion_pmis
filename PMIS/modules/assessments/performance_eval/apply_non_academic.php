<?php
/**
 * DOMINION UNIVERSITY, IBADAN
 * PERFORMANCE EVALUATION FOR NON-ACADEMIC STAFF
 */
require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

requireLogin();
$staffId = getCurrentStaffId();
$proxyMode = false;

if (isAdmin() || isHR()) {
    $reqStaffId = $_GET['staff_id'] ?? ($_POST['staff_id'] ?? null);
    if ($reqStaffId) {
        $staffId = $reqStaffId;
        $proxyMode = true;
    } elseif (!$staffId) {
        $proxyMode = true;
    }
}

if ($proxyMode && !$staffId) {
    // Fetch non-academic staff for this form
    $stmt = $pdo->query("SELECT id, first_name, last_name, staff_id as scode FROM staff WHERE staff_type != 'academic' ORDER BY first_name");
    $allStaff = $stmt->fetchAll();
    require_once '../../../includes/header.php';
    require_once '../../../includes/sidebar.php';
    ?>
    <div class="card" style="max-width:500px; margin: 50px auto;">
        <div class="card-header"><h3 class="card-title">Select Non-Academic Staff</h3></div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Staff Member</label>
                    <select name="staff_id" class="form-control" required>
                        <option value="">-- Select Staff --</option>
                        <?php foreach($allStaff as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo escapeOutput($s['first_name'].' '.$s['last_name'].' ('.$s['scode'].')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Continue to Appraisal Form</button>
            </form>
        </div>
    </div>
    <?php
    require_once '../../../includes/footer.php';
    exit;
} elseif (!$staffId) {
    setFlashMessage('error', 'Only staff members or Administrators can initiate an appraisal.');
    redirectBack();
}

$stmt = $pdo->prepare("SELECT s.*, d.department_name FROM staff s LEFT JOIN departments d ON s.department_id = d.id WHERE s.id = ?");
$stmt->execute([$staffId]);
$staff = $stmt->fetch();

if (!$staff || $staff['staff_type'] === 'academic') {
    setFlashMessage('error', 'Selected staff member is invalid or not non-academic.');
    header("Location: list.php");
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $periodFrom = $_POST['period_from'] ?? '';
    $periodTo = $_POST['period_to'] ?? '';
    if (empty($periodFrom) || empty($periodTo)) {
        $errors[] = "Assessment period is required.";
    }
    if (empty($errors)) {
        try {
            // Include everything
            $formData = $_POST;
            $jsonData = json_encode($formData);
            $stmt = $pdo->prepare("INSERT INTO performance_appraisals (staff_id, appraisal_type, period_from, period_to, status, form_data) VALUES (?, 'non_academic', ?, ?, 'pending_hod', ?)");
            $stmt->execute([$staffId, $periodFrom, $periodTo, $jsonData]);
            setFlashMessage('success', 'Your Appraisal form has been submitted successfully to your HOD.');
            header("Location: list.php");
            exit;
        } catch (Exception $e) {
            $errors[] = "Error saving form.";
        }
    }
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>
<style>
.wizard-container { background: #fff; border-radius: 12px; padding: 30px; margin-bottom: 30px; }
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
.section-card { border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 25px; background: #fcfdfe; }
.section-header { background: #ebf8ff; padding: 15px 20px; font-weight: bold; color: #2c5282; border-bottom: 1px solid #bee3f8;}
.section-body { padding: 25px; }
.auto-filled { background-color: #f7fafc; cursor: not-allowed; border: 1px solid #e2e8f0; }
</style>
<div class="wizard-container">
    <div style="text-align:center; margin-bottom: 30px;">
        <h2 style="color:#1e3a5f;font-weight:700;">DOMINION UNIVERSITY, IBADAN</h2>
        <h3 style="color:#4a5568;">ANNUAL PERFORMANCE EVALUATION REPORT</h3>
        <p><strong>Target:</strong> ADMINISTRATIVE, TECHNICAL AND PROFESSIONAL STAFF</p>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin:0; padding-left:20px;">
                <?php foreach ($errors as $error) echo "<li>" . escapeOutput($error) . "</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <?php echo csrfField(); ?>
        <?php if ($proxyMode): ?><input type="hidden" name="staff_id" value="<?php echo $staffId; ?>"><?php endif; ?>
        
        <div class="section-card">
            <div class="section-header">PERIOD OF REPORT</div>
            <div class="section-body form-grid-2">
                <div class="form-group"><label>From (Date)</label><input type="date" name="period_from" class="form-control" required value="<?php echo date('Y-01-01'); ?>"></div>
                <div class="form-group"><label>To (Date)</label><input type="date" name="period_to" class="form-control" required value="<?php echo date('Y-12-31'); ?>"></div>
            </div>
            <div style="padding: 0 25px 25px 25px; color: #718096; font-size: 0.9rem;">
                <strong>NOTE:</strong> This report is designed to provide an up-to-date appraisal of the employee’s competence, efficiency and official conduct for: Transfer, Promotion, Training, Staff development, Objective supervision assessment.
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">SECTION 1: PERSONAL INFORMATION (PART A)</div>
            <div class="section-body">
                <div class="form-grid-3">
                    <div class="form-group"><label>Surname & Other Names</label><input type="text" class="form-control auto-filled" value="<?php echo escapeOutput($staff['last_name'].' '.$staff['first_name'].' '.$staff['middle_name']); ?>" readonly></div>
                    <div class="form-group"><label>Title</label>
                        <select name="title" class="form-control"><option>Dr.</option><option>Mr.</option><option>Mrs.</option><option>Miss</option><option>Ms.</option><option>Others</option></select>
                    </div>
                    <div class="form-group"><label>Date of Birth</label><input type="date" name="dob" class="form-control"></div>
                </div>
                <div class="form-grid-3">
                    <div class="form-group"><label>Age</label><input type="number" name="age" class="form-control"></div>
                    <div class="form-group"><label>Marital Status</label><select name="marital_status" class="form-control"><option>Single</option><option>Married</option><option>Divorced</option><option>Widowed</option></select></div>
                    <div class="form-group"><label>College/Department/Unit</label><input type="text" class="form-control auto-filled" value="<?php echo escapeOutput($staff['department_name'] ?? ''); ?>" readonly></div>
                </div>
                <div class="form-grid-3">
                    <div class="form-group"><label>Date of First Appointment</label><input type="date" name="date_first_appointment" class="form-control"></div>
                    <div class="form-group"><label>Grade/Status on First Appt.</label><input type="text" name="grade_first_appointment" class="form-control"></div>
                    <div class="form-group"><label>Current Grade/Status</label><input type="text" name="current_grade" class="form-control" value="<?php echo escapeOutput($staff['rank']); ?>"></div>
                </div>
                <div class="form-grid-3">
                    <div class="form-group"><label>Date Prom. Current Grade</label><input type="date" name="date_promoted" class="form-control"></div>
                    <div class="form-group"><label>Confirmed?</label><select name="is_confirmed" class="form-control"><option>No</option><option>Yes</option></select></div>
                    <div class="form-group"><label>Date of Confirmation</label><input type="date" name="date_confirmation" class="form-control"></div>
                </div>
                <div class="form-grid-3">
                    <div class="form-group"><label>Acting Appointment Held</label><input type="text" name="acting_appointment" class="form-control"></div>
                    <div class="form-group"><label>Present Salary per annum</label><input type="number" name="salary" class="form-control"></div>
                    <div class="form-group"><label>Grade Level/Step</label><input type="text" name="grade_level" class="form-control"></div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">SECTION 2: PROFESSIONAL DEVELOPMENT</div>
            <div class="section-body">
                <label>Courses or Conferences Attended:</label>
                <input type="text" name="course_1" class="form-control" placeholder="Entry 1" style="margin-bottom:10px;">
                <input type="text" name="course_2" class="form-control" placeholder="Entry 2" style="margin-bottom:10px;">
                <input type="text" name="course_3" class="form-control" placeholder="Entry 3" style="margin-bottom:10px;">
                <input type="text" name="course_4" class="form-control" placeholder="Entry 4">
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">SECTION 3: QUALIFICATIONS</div>
            <div class="section-body">
                <label>Academic Qualifications (University/Degree, Class, Institution, Date):</label>
                <div id="academicQuals">
                    <div class="form-grid-3" style="margin-bottom:10px;">
                        <input type="text" name="acad_degree[]" class="form-control" placeholder="University/Degree">
                        <input type="text" name="acad_class[]" class="form-control" placeholder="Class / Institution">
                        <input type="text" name="acad_date[]" class="form-control" placeholder="Date of Award">
                    </div>
                </div>
                
                <label style="margin-top:20px;">Professional Qualifications (Qualification, Awarding Body, Date):</label>
                <div id="profQuals">
                    <div class="form-grid-3" style="margin-bottom:10px;">
                        <input type="text" name="prof_qual[]" class="form-control" placeholder="Qualification">
                        <input type="text" name="prof_body[]" class="form-control" placeholder="Awarding Body">
                        <input type="text" name="prof_date[]" class="form-control" placeholder="Date of Award">
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">SECTION 4: EXPERIENCE</div>
            <div class="section-body">
                <div class="form-group"><label>13(a). Previous Job Description (prior to report period)</label><textarea name="job_desc_prior" class="form-control" rows="3"></textarea></div>
                <div class="form-group"><label>13(b). Main Duties During Report Period (in order of importance)</label><textarea name="duties_performed" class="form-control" rows="3"></textarea></div>
                <div class="form-group"><label>13(c). Ad Hoc Duties (non-continuous)</label><textarea name="adhoc_duties" class="form-control" rows="2"></textarea></div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">SECTION 5: CONTRIBUTIONS</div>
            <div class="section-body">
                <div class="form-group"><label>14. Contribution within University</label><textarea name="activities_within" class="form-control" rows="2"></textarea></div>
                <div class="form-group"><label>15. Contribution to Society (Outside University Work)</label><textarea name="activities_outside" class="form-control" rows="2"></textarea></div>
                <div class="form-group"><label>16. Publications (Journals, Creative Writing etc.)</label><textarea name="publications" class="form-control" rows="2"></textarea></div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">SECTION 6: DECLARATION</div>
            <div class="section-body">
                <p>I certify that the information provided is true to the best of my knowledge.</p>
                <div class="form-grid-2">
                    <div class="form-group"><label>Employee Signature (Typed Name)</label><input type="text" name="applicant_signature" class="form-control" required></div>
                    <div class="form-group"><label>Date</label><input type="date" name="applicant_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly></div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success" style="width:100%; padding: 15px; font-size:1.1rem;"><i class="fas fa-paper-plane"></i> Submit Non-Academic Appraisal</button>
    </form>
</div>
<?php require_once '../../../includes/footer.php'; ?>
