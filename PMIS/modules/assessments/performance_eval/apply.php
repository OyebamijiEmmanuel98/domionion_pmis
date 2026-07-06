<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * PERFORMANCE EVALUATION WIZARD - PART A
 * =====================================================
 * 
 * Digitalized Form exactly like the Leave module.
 * Dynamically switches between Academic and Non-Academic.
 * 
 * @version 2.0
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
        // If they have no staff profile, they must pick someone
        $proxyMode = true;
    }
}

if ($proxyMode && !$staffId) {
        $stmt = $pdo->query("SELECT id, first_name, last_name, staff_id as scode FROM staff ORDER BY first_name");
        $allStaff = $stmt->fetchAll();
        
        require_once '../../../includes/header.php';
        require_once '../../../includes/sidebar.php';
        ?>
        <div class="card" style="max-width:500px; margin: 50px auto;">
            <div class="card-header"><h3 class="card-title">Select Staff Member</h3></div>
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

// Fetch staff details
$stmt = $pdo->prepare("
    SELECT s.*, d.department_name 
    FROM staff s 
    LEFT JOIN departments d ON s.department_id = d.id 
    WHERE s.id = ?
");
$stmt->execute([$staffId]);
$staff = $stmt->fetch();

if (!$staff) {
    setFlashMessage('error', 'Selected staff member not found.');
    header("Location: list.php");
    exit;
}

$isAcademic = ($staff['staff_type'] === 'academic');
$pageTitle = $isAcademic ? 'Academic Staff Appraisal' : 'Non-Academic Staff Appraisal';

$errors = [];
$formData = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $periodFrom = $_POST['period_from'] ?? '';
    $periodTo = $_POST['period_to'] ?? '';

    if (empty($periodFrom) || empty($periodTo)) {
        $errors[] = "Assessment period is required.";
    }

    if (empty($errors)) {
        try {
            $jsonData = json_encode($formData);
            $appraisalType = $isAcademic ? 'academic' : 'non_academic';

            $stmt = $pdo->prepare("
                INSERT INTO performance_appraisals 
                (staff_id, appraisal_type, period_from, period_to, status, form_data) 
                VALUES (?, ?, ?, ?, 'pending_hod', ?)
            ");
            $stmt->execute([
                $staffId, 
                $appraisalType, 
                $periodFrom, 
                $periodTo, 
                $jsonData
            ]);

            setFlashMessage('success', 'Your Part A Appraisal form has been submitted successfully to your HOD.');
            header("Location: list.php");
            exit;
        } catch (PDOException $e) {
            error_log("Appraisal Submit Error: " . $e->getMessage());
            $errors[] = "An error occurred while saving your form. Please try again.";
        }
    }
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<style>
.wizard-container {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    padding: 30px;
    margin-bottom: 30px;
}
.wizard-header {
    text-align: center;
    margin-bottom: 30px;
}
.wizard-header h2 {
    color: #1e3a5f;
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
}
.wizard-header p {
    color: #718096;
    margin-top: 5px;
}
.wizard-progress {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    position: relative;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}
.wizard-progress::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 4px;
    background: #e2e8f0;
    transform: translateY(-50%);
    z-index: 1;
}
.progress-bar-fill {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    height: 4px;
    background: #2b6cb0;
    transform: translateY(-50%);
    z-index: 2;
    transition: width 0.3s ease;
}
.wizard-step {
    position: relative;
    z-index: 3;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    padding: 0 10px;
    transition: all 0.3s ease;
}
.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #fff;
    border: 3px solid #cbd5e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #a0aec0;
    transition: all 0.3s ease;
}
.wizard-step.active .step-circle {
    border-color: #2b6cb0;
    background: #2b6cb0;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(43, 108, 176, 0.2);
}
.wizard-step.completed .step-circle {
    border-color: #48bb78;
    background: #48bb78;
    color: #fff;
}
.step-label {
    font-size: 0.85rem;
    color: #718096;
    font-weight: 600;
}
.wizard-step.active .step-label { color: #2b6cb0; }
.wizard-step.completed .step-label { color: #48bb78; }
.wizard-section {
    display: none;
    animation: fadeIn 0.4s ease;
}
.wizard-section.active { display: block; }
.section-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fcfdfe;
    margin-bottom: 25px;
    overflow: hidden;
}
.section-header {
    background: #ebf8ff;
    border-bottom: 1px solid #bee3f8;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-header h4 {
    margin: 0;
    color: #2c5282;
    font-size: 1.1rem;
    font-weight: 600;
}
.section-body { padding: 25px; }
.wizard-nav {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
.auto-filled { background-color: #f7fafc; cursor: not-allowed; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="wizard-container">
    <div class="wizard-header">
        <h2><?php echo $pageTitle; ?></h2>
        <p>PART A - To be completed by individual member of staff</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin:0; padding-left:20px;">
                <?php foreach ($errors as $error) echo "<li>" . escapeOutput($error) . "</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="wizard-progress">
        <div class="progress-bar-fill" id="progressFill" style="width: 0%;"></div>
        <div class="wizard-step active" id="stepIndicator-0">
            <div class="step-circle">1</div><div class="step-label">Personal Details</div>
        </div>
        <div class="wizard-step" id="stepIndicator-1">
            <div class="step-circle">2</div><div class="step-label"><?php echo $isAcademic ? 'Teaching & Supervision' : 'Experience / Duties'; ?></div>
        </div>
        <div class="wizard-step" id="stepIndicator-2">
            <div class="step-circle">3</div><div class="step-label"><?php echo $isAcademic ? 'Research & Pubs' : 'Other Activities'; ?></div>
        </div>
        <div class="wizard-step" id="stepIndicator-3">
            <div class="step-circle">4</div><div class="step-label">Declaration</div>
        </div>
    </div>

    <form method="POST" action="apply.php" id="appraisalForm">
        <?php echo csrfField(); ?>
        <?php if ($proxyMode): ?><input type="hidden" name="staff_id" value="<?php echo $staffId; ?>"><?php endif; ?>

        <!-- STEP 1: Personal Details -->
        <div class="wizard-section active" id="step-0">
            <div class="section-card">
                <div class="section-header">
                    <h4><i class="fas fa-user"></i> SECTION 1: Personal & Employment Details</h4>
                </div>
                <div class="section-body">
                    <div class="form-grid-2" style="margin-bottom: 15px;">
                        <div class="form-group">
                            <label class="form-label">Period of Report (From)</label>
                            <input type="date" name="period_from" class="form-control" value="<?php echo date('Y-01-01'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Period of Report (To)</label>
                            <input type="date" name="period_to" class="form-control" value="<?php echo date('Y-12-31'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Surname & Other Names</label>
                            <input type="text" class="form-control auto-filled" value="<?php echo escapeOutput($staff['last_name'] . ' ' . $staff['first_name'] . ' ' . $staff['middle_name']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Title</label>
                            <select name="title" class="form-control">
                                <option value="Mr.">Mr.</option><option value="Mrs.">Mrs.</option><option value="Miss">Miss</option>
                                <option value="Ms.">Ms.</option><option value="Dr.">Dr.</option><option value="Prof.">Prof.</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Marital Status</label>
                            <select name="marital_status" class="form-control">
                                <option value="Single">Single</option><option value="Married">Married</option>
                                <option value="Divorced">Divorced</option><option value="Widowed">Widowed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">College/Department/Unit</label>
                            <input type="text" class="form-control auto-filled" value="<?php echo escapeOutput($staff['department_name'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of First Appointment</label>
                            <input type="date" name="date_first_appointment" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Grade/Status on First Appointment</label>
                            <input type="text" name="grade_first_appointment" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Current Grade/Status</label>
                            <input type="text" name="current_grade" class="form-control" value="<?php echo escapeOutput($staff['rank']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Date promoted to current grade</label>
                            <input type="date" name="date_promoted" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Has appointment been confirmed?</label>
                            <select name="is_confirmed" class="form-control">
                                <option value="No">No</option><option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of confirmation</label>
                            <input type="date" name="date_confirmation" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Acting Appointment held (if any)</label>
                            <input type="text" name="acting_appointment" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Present Salary (per annum)</label>
                            <input type="text" name="salary" class="form-control" placeholder="₦">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Grade Level/Step</label>
                            <input type="text" name="grade_level" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2 -->
        <div class="wizard-section" id="step-1">
            <?php if ($isAcademic): ?>
                <div class="section-card">
                    <div class="section-header"><h4><i class="fas fa-chalkboard-teacher"></i> SECTION 2: Teaching & Student Supervision</h4></div>
                    <div class="section-body">
                        <div class="form-group">
                            <label class="form-label">Courses Taught in Current Academic Session (Provide Course Codes, Titles, Units)</label>
                            <textarea name="courses_taught" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Supervision of Students (Industrial Experience)</label>
                            <textarea name="industrial_supervision" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Supervision of Final Year Undergraduate Projects</label>
                            <textarea name="ug_supervision" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Graduate Students Supervision</label>
                            <textarea name="pg_supervision" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="section-card">
                    <div class="section-header"><h4><i class="fas fa-briefcase"></i> SECTION 2: Qualifications & Experience</h4></div>
                    <div class="section-body">
                        <div class="form-group">
                            <label class="form-label">Courses or Conferences attended during the period</label>
                            <textarea name="courses_attended" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Academic & Professional Qualifications (Institution, Class, Date)</label>
                            <textarea name="qualifications" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Job description prior to the period of Report</label>
                            <textarea name="job_desc_prior" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Main official duties performed during the period of Report</label>
                            <textarea name="duties_performed" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Important ad hoc duties performed (Not of continuous nature)</label>
                            <textarea name="adhoc_duties" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- STEP 3 -->
        <div class="wizard-section" id="step-2">
            <?php if ($isAcademic): ?>
                <div class="section-card">
                    <div class="section-header"><h4><i class="fas fa-book"></i> SECTION 3: Research & Publications</h4></div>
                    <div class="section-body">
                        <div class="form-group">
                            <label class="form-label">Research in Progress</label>
                            <textarea name="research_progress" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Research completed but not yet published / Thesis</label>
                            <textarea name="research_completed" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Publications (Books, Journal Articles, Conferences, etc.)</label>
                            <textarea name="publications" class="form-control" rows="6" placeholder="List your authorized books, articles, etc..."></textarea>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="section-card">
                    <div class="section-header"><h4><i class="fas fa-network-wired"></i> SECTION 3: Other Activities & Publications</h4></div>
                    <div class="section-body">
                        <div class="form-group">
                            <label class="form-label">Other activities within the University (Contribution to the University)</label>
                            <textarea name="activities_within" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Other activities outside normal University work (Contribution to society)</label>
                            <textarea name="activities_outside" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Publications (in journals, creative writing etc.)</label>
                            <textarea name="publications" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- STEP 4 -->
        <div class="wizard-section" id="step-3">
            <div class="section-card">
                <div class="section-header"><h4><i class="fas fa-pen-nib"></i> SECTION 4: Declaration</h4></div>
                <div class="section-body">
                    <?php if ($isAcademic): ?>
                        <div class="form-group">
                            <label class="form-label">Extra-Curricular Activities (Within and Outside University)</label>
                            <textarea name="extra_curricular" class="form-control" rows="3"></textarea>
                        </div>
                    <?php endif; ?>
                    
                    <p style="color:#4a5568; margin-top:20px; font-weight:500;">
                        I certify that the information provided in this appraisal form is true and correct to the best of my knowledge.
                    </p>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Applicant Signature (Typed Name)</label>
                            <input type="text" name="applicant_signature" class="form-control" placeholder="Type your full name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <input type="text" class="form-control auto-filled" value="<?php echo date('F d, Y'); ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding: 1.25rem; background: #ebf8ff; border-radius: 8px; border: 1px solid #bee3f8;">
                <h4 style="color: #2b6cb0; margin-bottom: 0.75rem; font-size: 0.95rem;">
                    <i class="fas fa-info-circle"></i> Submission Workflow
                </h4>
                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; font-size: 0.85rem; color: #4a5568;">
                    <span style="padding: 4px 12px; background: #fefcbf; border-radius: 12px; font-weight: 600;">1. Submitted by You</span>
                    <i class="fas fa-chevron-right" style="color: #cbd5e0;"></i>
                    <span style="padding: 4px 12px; background: #fefcbf; border-radius: 12px; font-weight: 600;">2. HOD Review</span>
                    <i class="fas fa-chevron-right" style="color: #cbd5e0;"></i>
                    <span style="padding: 4px 12px; background: #fefcbf; border-radius: 12px; font-weight: 600;">3. Dean / HR Review</span>
                    <i class="fas fa-chevron-right" style="color: #cbd5e0;"></i>
                    <span style="padding: 4px 12px; background: #fefcbf; border-radius: 12px; font-weight: 600;">4. A&P Committee</span>
                </div>
            </div>
            
            <div class="wizard-nav">
                <button type="button" class="btn btn-secondary" onclick="prevStep()">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> Submit Appraisal Form
                </button>
            </div>
        </div>
        
        <!-- Navigation Buttons for earlier steps -->
        <div class="wizard-nav" id="normalNav">
            <button type="button" class="btn btn-secondary" onclick="prevStep()" id="prevBtn" style="visibility:hidden;">
                <i class="fas fa-arrow-left"></i> Previous
            </button>
            <button type="button" class="btn btn-primary" onclick="nextStep()" id="nextBtn">
                Next <i class="fas fa-arrow-right"></i>
            </button>
        </div>

    </form>
</div>

<script>
    let currentStep = 0;
    const totalSteps = 4;
    
    function updateNav() {
        if (currentStep === 0) {
            document.getElementById('prevBtn').style.visibility = 'hidden';
            document.getElementById('normalNav').style.display = 'flex';
        } else if (currentStep === totalSteps - 1) {
            document.getElementById('normalNav').style.display = 'none';
        } else {
            document.getElementById('prevBtn').style.visibility = 'visible';
            document.getElementById('normalNav').style.display = 'flex';
        }
    }
    
    function goToStep(step) {
        if (step < 0 || step >= totalSteps) return;
        
        document.querySelectorAll('.wizard-section').forEach(s => s.classList.remove('active'));
        document.getElementById('step-' + step).classList.add('active');
        
        document.querySelectorAll('.wizard-step').forEach((s, i) => {
            s.classList.remove('active', 'completed');
            if (i < step) s.classList.add('completed');
            if (i === step) s.classList.add('active');
        });
        
        const fillWidth = (step / (totalSteps - 1)) * 100;
        document.getElementById('progressFill').style.width = fillWidth + '%';
        
        currentStep = step;
        updateNav();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    function nextStep() {
        if (currentStep < totalSteps - 1) goToStep(currentStep + 1);
    }
    
    function prevStep() {
        if (currentStep > 0) goToStep(currentStep - 1);
    }
    
    updateNav();
</script>

<?php require_once '../../../includes/footer.php'; ?>
