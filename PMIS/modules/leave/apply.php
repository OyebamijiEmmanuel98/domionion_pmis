<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * SHORT LEAVE APPLICATION - DIGITAL FORM
 * =====================================================
 * 
 * Multi-step wizard with:
 * - Progress bar
 * - Conditional logic
 * - Auto-calculated duration
 * - File upload support
 * - Multi-level approval workflow
 * 
 * @author Final Year Project
 * @version 2.0
 */

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/role_check.php';

// Require login
requireLogin();

$pageTitle = 'Short Leave Application';
$breadcrumbs = ['Leave' => 'modules/leave/history.php', 'Apply' => null];

$errors = [];
$formData = [
    'leave_type_id' => '',
    'start_date' => '',
    'end_date' => '',
    'total_days' => '',
    'reason' => '',
    'reliever_name' => '',
    'is_applicant_hod' => 'No',
    'acting_hod_name' => '',
    'acting_hod_most_senior' => '',
    'applicant_signature' => ''
];

// Get leave types
$leaveTypesStmt = $pdo->query("SELECT * FROM leave_types ORDER BY leave_name");
$leaveTypes = $leaveTypesStmt->fetchAll();

// Get current staff info
$staffId = getCurrentStaffId();

if (!$staffId) {
    setFlashMessage('error', 'Your account is not linked to a staff record');
    header("Location: ../../dashboard.php");
    exit();
}

// Get staff details for auto-fill
$staffStmt = $pdo->prepare("
    SELECT s.*, d.department_name 
    FROM staff s 
    LEFT JOIN departments d ON s.department_id = d.id 
    WHERE s.id = ?
");
$staffStmt->execute([$staffId]);
$staffInfo = $staffStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get input
        $formData['leave_type_id'] = $_POST['leave_type_id'] ?? '';
        $formData['start_date'] = $_POST['start_date'] ?? '';
        $formData['end_date'] = $_POST['end_date'] ?? '';
        $formData['total_days'] = $_POST['total_days'] ?? '';
        $formData['reason'] = sanitizeInput($_POST['reason'] ?? '');
        $formData['reliever_name'] = sanitizeInput($_POST['reliever_name'] ?? '');
        $formData['is_applicant_hod'] = $_POST['is_applicant_hod'] ?? 'No';
        $formData['acting_hod_name'] = sanitizeInput($_POST['acting_hod_name'] ?? '');
        $formData['acting_hod_most_senior'] = $_POST['acting_hod_most_senior'] ?? '';
        $formData['applicant_signature'] = sanitizeInput($_POST['applicant_signature'] ?? '');
        
        // Validation
        if (empty($formData['leave_type_id'])) {
            $errors[] = 'Leave type is required';
        }
        
        if (empty($formData['start_date'])) {
            $errors[] = 'Start date is required';
        }
        
        if (empty($formData['end_date'])) {
            $errors[] = 'End date is required';
        }
        
        if (empty($formData['reason'])) {
            $errors[] = 'Reason is required';
        }
        
        if (empty($formData['reliever_name'])) {
            $errors[] = 'Person covering duties is required';
        }
        
        if (empty($formData['applicant_signature'])) {
            $errors[] = 'Applicant signature (typed name) is required';
        }
        
        // Validate date range
        if (!empty($formData['start_date']) && !empty($formData['end_date'])) {
            if (!isValidDateRange($formData['start_date'], $formData['end_date'])) {
                $errors[] = 'End date must be after start date';
            }
        }
        
        // Handle file upload
        $docPath = null;
        if (isset($_FILES['supporting_doc']) && $_FILES['supporting_doc']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadDir = '../../assets/uploads/leave_docs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $result = uploadFile($_FILES['supporting_doc'], $uploadDir, $allowedTypes, 5242880);
            if ($result['status']) {
                $docPath = $result['path'];
            } else {
                $errors[] = 'Document upload failed: ' . $result['message'];
            }
        }
        
        // If no errors, insert
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO leave_applications 
                    (staff_id, leave_type_id, start_date, end_date, total_days, reason, 
                     reliever_name, is_applicant_hod, acting_hod_name, acting_hod_most_senior,
                     applicant_signature, supporting_doc, status, hod_status, dean_status, vc_status, applied_at)
                    VALUES (:staff_id, :leave_type_id, :start_date, :end_date, :total_days, :reason,
                            :reliever_name, :is_applicant_hod, :acting_hod_name, :acting_hod_most_senior,
                            :applicant_signature, :supporting_doc, 'pending', 'pending', 'pending', 'pending', NOW())
                ");
                
                $stmt->execute([
                    ':staff_id' => $staffId,
                    ':leave_type_id' => $formData['leave_type_id'],
                    ':start_date' => $formData['start_date'],
                    ':end_date' => $formData['end_date'],
                    ':total_days' => $formData['total_days'],
                    ':reason' => $formData['reason'],
                    ':reliever_name' => $formData['reliever_name'],
                    ':is_applicant_hod' => $formData['is_applicant_hod'],
                    ':acting_hod_name' => !empty($formData['acting_hod_name']) ? $formData['acting_hod_name'] : null,
                    ':acting_hod_most_senior' => !empty($formData['acting_hod_most_senior']) ? $formData['acting_hod_most_senior'] : null,
                    ':applicant_signature' => $formData['applicant_signature'],
                    ':supporting_doc' => $docPath
                ]);
                
                $leaveId = $pdo->lastInsertId();
                
                // Log activity
                logActivity('APPLY_LEAVE', 'leave_applications', $leaveId, 'Applied for short leave');
                
                setFlashMessage('success', 'Leave application submitted successfully! It is now pending HOD review.');
                header("Location: history.php");
                exit();
                
            } catch (PDOException $e) {
                error_log("Apply Leave Error: " . $e->getMessage());
                $errors[] = 'Error submitting leave application. Please try again.';
            }
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<style>
    /* Leave Application Wizard Styles */
    .leave-wizard {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .wizard-progress {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        position: relative;
        padding: 0 1rem;
    }
    
    .wizard-progress::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 40px;
        right: 40px;
        height: 3px;
        background: #e2e8f0;
        z-index: 0;
    }
    
    .wizard-progress .progress-fill {
        position: absolute;
        top: 20px;
        left: 40px;
        height: 3px;
        background: linear-gradient(90deg, #1e3a5f, #3182ce);
        z-index: 1;
        transition: width 0.5s ease;
    }
    
    .wizard-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        z-index: 2;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #fff;
        border: 3px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.875rem;
        transition: all 0.3s ease;
        color: #a0aec0;
    }
    
    .wizard-step.active .step-circle {
        background: #1e3a5f;
        border-color: #1e3a5f;
        color: #fff;
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
    }
    
    .wizard-step.completed .step-circle {
        background: #38a169;
        border-color: #38a169;
        color: #fff;
    }
    
    .step-title {
        font-size: 0.75rem;
        font-weight: 600;
        color: #a0aec0;
        text-align: center;
        max-width: 100px;
    }
    
    .wizard-step.active .step-title {
        color: #1e3a5f;
    }
    
    .wizard-step.completed .step-title {
        color: #38a169;
    }
    
    .wizard-section {
        display: none;
        animation: fadeInUp 0.4s ease;
    }
    
    .wizard-section.active {
        display: block;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .section-card {
        background: #fff;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        border: 1px solid #e2e8f0;
    }
    
    .section-card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e3a5f;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #edf2f7;
    }
    
    .section-card-title i {
        width: 32px;
        height: 32px;
        background: rgba(30, 58, 95, 0.08);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #3182ce;
    }
    
    .form-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }
    
    .form-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.25rem;
    }
    
    .full-width {
        grid-column: 1 / -1;
    }
    
    .auto-filled {
        background: #f7fafc !important;
        color: #718096 !important;
        border-color: #e2e8f0 !important;
    }
    
    .auto-badge {
        display: inline-block;
        font-size: 0.7rem;
        background: #ebf4ff;
        color: #3182ce;
        padding: 2px 8px;
        border-radius: 10px;
        margin-left: 0.5rem;
        font-weight: 600;
    }
    
    .conditional-field {
        display: none;
        padding: 1.25rem;
        background: #fafbfc;
        border-radius: 8px;
        border: 1px dashed #cbd5e0;
        margin-top: 1rem;
        animation: fadeInUp 0.3s ease;
    }
    
    .conditional-field.visible {
        display: block;
    }
    
    .duration-display {
        background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
        color: #fff;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        text-align: center;
        margin-top: 0.5rem;
    }
    
    .duration-display .days-count {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .duration-display .days-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }
    
    .upload-area {
        border: 2px dashed #cbd5e0;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #fafbfc;
    }
    
    .upload-area:hover {
        border-color: #3182ce;
        background: #ebf8ff;
    }
    
    .upload-area i {
        font-size: 2rem;
        color: #a0aec0;
        margin-bottom: 0.75rem;
    }
    
    .upload-area p {
        color: #718096;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }
    
    .upload-area small {
        color: #a0aec0;
        font-size: 0.75rem;
    }
    
    .signature-preview {
        font-family: 'Brush Script MT', 'Segoe Script', cursive;
        font-size: 1.5rem;
        color: #1e3a5f;
        padding: 1rem;
        border-bottom: 2px solid #1e3a5f;
        margin-top: 0.5rem;
        min-height: 50px;
    }
    
    .wizard-nav {
        display: flex;
        justify-content: space-between;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e2e8f0;
    }
    
    .btn-wizard {
        padding: 0.75rem 2rem;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .btn-wizard-prev {
        background: #edf2f7;
        color: #4a5568;
    }
    
    .btn-wizard-prev:hover {
        background: #e2e8f0;
    }
    
    .btn-wizard-next {
        background: #1e3a5f;
        color: #fff;
    }
    
    .btn-wizard-next:hover {
        background: #152a45;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
    }
    
    .btn-wizard-submit {
        background: linear-gradient(135deg, #38a169, #2f855a);
        color: #fff;
    }
    
    .btn-wizard-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(56, 161, 105, 0.3);
    }
    
    .declaration-text {
        font-size: 0.9rem;
        color: #4a5568;
        line-height: 1.7;
        padding: 1rem;
        background: #fafbfc;
        border-radius: 8px;
        border-left: 4px solid #1e3a5f;
    }
    
    @media (max-width: 640px) {
        .form-grid-2, .form-grid-3 {
            grid-template-columns: 1fr;
        }
        .wizard-progress {
            padding: 0;
        }
        .step-title {
            display: none;
        }
    }
</style>

<div class="leave-wizard">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</strong>
            <ul style="margin: 8px 0 0 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo escapeOutput($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Progress Bar -->
    <div class="wizard-progress">
        <div class="progress-fill" id="progressFill" style="width: 0%;"></div>
        <div class="wizard-step active" onclick="goToStep(0)">
            <div class="step-circle">1</div>
            <span class="step-title">Applicant Details</span>
        </div>
        <div class="wizard-step" onclick="goToStep(1)">
            <div class="step-circle">2</div>
            <span class="step-title">Leave Details</span>
        </div>
        <div class="wizard-step" onclick="goToStep(2)">
            <div class="step-circle">3</div>
            <span class="step-title">Handover</span>
        </div>
        <div class="wizard-step" onclick="goToStep(3)">
            <div class="step-circle">4</div>
            <span class="step-title">Declaration</span>
        </div>
    </div>
    
    <form method="POST" action="" enctype="multipart/form-data" id="leaveForm">
        <?php echo csrfField(); ?>
        
        <!-- SECTION 1: APPLICANT DETAILS -->
        <div class="wizard-section active" id="step-0">
            <div class="section-card">
                <h3 class="section-card-title">
                    <i class="fas fa-user"></i>
                    SECTION 1: APPLICANT DETAILS
                </h3>
                
                <div class="form-grid-2">
                    <div class="form-group full-width">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control auto-filled" 
                               value="<?php echo escapeOutput($staffInfo['first_name'] . ' ' . ($staffInfo['middle_name'] ? $staffInfo['middle_name'] . ' ' : '') . $staffInfo['last_name']); ?>" 
                               readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Department/Unit</label>
                        <input type="text" class="form-control auto-filled" 
                               value="<?php echo escapeOutput($staffInfo['department_name'] ?? 'N/A'); ?>" 
                               readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <input type="text" class="form-control auto-filled" 
                               value="<?php echo escapeOutput($staffInfo['rank'] ?? 'N/A'); ?>" 
                               readonly>
                    </div>
                </div>
            </div>
            
            <div class="wizard-nav">
                <div></div>
                <button type="button" class="btn-wizard btn-wizard-next" onclick="nextStep()">
                    Next: Leave Details <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
        
        <!-- SECTION 2: LEAVE DETAILS -->
        <div class="wizard-section" id="step-1">
            <div class="section-card">
                <h3 class="section-card-title">
                    <i class="fas fa-calendar-alt"></i>
                    SECTION 2: LEAVE DETAILS
                </h3>
                
                <div class="form-grid-2">
                    <div class="form-group full-width">
                        <label class="form-label required">Leave Type</label>
                        <select name="leave_type_id" class="form-control" id="leaveType">
                            <option value="">-- Select Leave Type --</option>
                            <?php foreach ($leaveTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>" 
                                        data-max="<?php echo $type['max_days']; ?>"
                                        <?php echo ($formData['leave_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                    <?php echo escapeOutput($type['leave_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Start Date (Date Picker)</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" 
                               value="<?php echo escapeOutput($formData['start_date']); ?>"
                               onchange="calculateDuration()">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">End Date (Date Picker)</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" 
                               value="<?php echo escapeOutput($formData['end_date']); ?>"
                               onchange="calculateDuration()">
                    </div>
                </div>
                
                <div class="form-grid-2" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Duration (Auto-calculated)</label>
                        <input type="hidden" name="total_days" id="total_days" 
                               value="<?php echo escapeOutput($formData['total_days']); ?>">
                        <div class="duration-display" id="durationDisplay" style="<?php echo empty($formData['total_days']) ? 'display:none' : ''; ?>">
                            <div class="days-count" id="daysCount"><?php echo escapeOutput($formData['total_days'] ?: '0'); ?></div>
                            <div class="days-label">Working Days</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 1.25rem;">
                    <label class="form-label required">Reason for Leave (Paragraph)</label>
                    <textarea name="reason" class="form-control" rows="4"
                              placeholder="Please provide a detailed reason for your leave request..."><?php echo escapeOutput($formData['reason']); ?></textarea>
                </div>
                
                <div class="form-group" style="margin-top: 1.25rem;">
                    <label class="form-label">Upload Supporting Documents (File Upload)</label>
                    <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p id="uploadText">Click to upload or drag and drop</p>
                        <small>PDF, Word, JPEG, PNG (Max 5MB)</small>
                    </div>
                    <input type="file" name="supporting_doc" id="fileInput" 
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                           style="display: none;" onchange="showFileName(this)">
                </div>
            </div>
            
            <div class="wizard-nav">
                <button type="button" class="btn-wizard btn-wizard-prev" onclick="prevStep()">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button type="button" class="btn-wizard btn-wizard-next" onclick="nextStep()">
                    Next: Handover <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
        
        <!-- SECTION 3: HANDOVER DETAILS -->
        <div class="wizard-section" id="step-2">
            <div class="section-card">
                <h3 class="section-card-title">
                    <i class="fas fa-exchange-alt"></i>
                    SECTION 3: HANDOVER DETAILS
                </h3>
                
                <div class="form-group">
                    <label class="form-label required">Person Covering Duties (Short Answer)</label>
                    <input type="text" name="reliever_name" class="form-control" 
                           value="<?php echo escapeOutput($formData['reliever_name']); ?>"
                           placeholder="Enter full name of the person covering your duties">
                </div>
                
                <div class="form-group" style="margin-top: 1.25rem;">
                    <label class="form-label required">Is Applicant HOD? (Yes/No)</label>
                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;" class="hod-option">
                            <input type="radio" name="is_applicant_hod" value="Yes" 
                                   onchange="toggleHodFields()" 
                                   <?php echo ($formData['is_applicant_hod'] == 'Yes') ? 'checked' : ''; ?>>
                            <span style="font-weight: 500;">Yes</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;" class="hod-option">
                            <input type="radio" name="is_applicant_hod" value="No" 
                                   onchange="toggleHodFields()"
                                   <?php echo ($formData['is_applicant_hod'] != 'Yes') ? 'checked' : ''; ?>>
                            <span style="font-weight: 500;">No</span>
                        </label>
                    </div>
                </div>
                
                <!-- Conditional: Acting HOD fields -->
                <div class="conditional-field <?php echo ($formData['is_applicant_hod'] == 'Yes') ? 'visible' : ''; ?>" id="hodFields">
                    <h4 style="color: #1e3a5f; margin-bottom: 1rem; font-size: 0.95rem;">
                        <i class="fas fa-info-circle"></i> Acting HOD Information
                    </h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Acting HOD Name (Short Answer)</label>
                            <input type="text" name="acting_hod_name" class="form-control" 
                                   value="<?php echo escapeOutput($formData['acting_hod_name']); ?>"
                                   placeholder="Enter name of Acting HOD">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Is Acting HOD Most Senior? (Yes/No)</label>
                            <select name="acting_hod_most_senior" class="form-control">
                                <option value="">-- Select --</option>
                                <option value="Yes" <?php echo ($formData['acting_hod_most_senior'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                <option value="No" <?php echo ($formData['acting_hod_most_senior'] == 'No') ? 'selected' : ''; ?>>No</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="wizard-nav">
                <button type="button" class="btn-wizard btn-wizard-prev" onclick="prevStep()">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button type="button" class="btn-wizard btn-wizard-next" onclick="nextStep()">
                    Next: Declaration <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
        
        <!-- SECTION 4: DECLARATION -->
        <div class="wizard-section" id="step-3">
            <div class="section-card">
                <h3 class="section-card-title">
                    <i class="fas fa-file-signature"></i>
                    SECTION 4: DECLARATION
                </h3>
                
                <div class="declaration-text">
                    <p>I, <strong><?php echo escapeOutput($staffInfo['first_name'] . ' ' . $staffInfo['last_name']); ?></strong>, 
                    hereby declare that the information provided in this leave application is true and accurate to the best of my knowledge. 
                    I undertake to ensure proper handover of my duties and responsibilities before proceeding on leave.</p>
                </div>
                
                <div class="form-grid-2" style="margin-top: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label required">Applicant Signature (Typed Name)</label>
                        <input type="text" name="applicant_signature" class="form-control" 
                               value="<?php echo escapeOutput($formData['applicant_signature']); ?>"
                               placeholder="Type your full name as signature"
                               id="signatureInput" oninput="updateSignaturePreview(this.value)">
                        <div class="signature-preview" id="signaturePreview">
                            <?php echo escapeOutput($formData['applicant_signature'] ?: ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date (Auto)</label>
                        <input type="text" class="form-control auto-filled" 
                               value="<?php echo date('F d, Y'); ?>" readonly>
                    </div>
                </div>
            </div>    
                <!-- Approval Workflow Info -->
                <div style="margin-top: 2rem; padding: 1.25rem; background: #ebf8ff; border-radius: 8px; border: 1px solid #bee3f8;">
                    <h4 style="color: #2b6cb0; margin-bottom: 0.75rem; font-size: 0.95rem;">
                        <i class="fas fa-info-circle"></i> Approval Workflow
                    </h4>
                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; font-size: 0.85rem; color: #4a5568;">
                        <span style="padding: 4px 12px; background: #fefcbf; border-radius: 12px; font-weight: 600;">1. HOD Review</span>
                        <i class="fas fa-chevron-right" style="color: #cbd5e0;"></i>
                        <span style="padding: 4px 12px; background: #fefcbf; border-radius: 12px; font-weight: 600;">2. Registrar/Dean Review</span>
                        <i class="fas fa-chevron-right" style="color: #cbd5e0;"></i>
                        <span style="padding: 4px 12px; background: #fefcbf; border-radius: 12px; font-weight: 600;">3. Vice-Chancellor Decision</span>
                    </div>
                    <p style="margin-top: 0.75rem; font-size: 0.8rem; color: #718096;">
                        Your application will go through the above approval chain after submission.
                    </p>
                </div>
            </div>
            
            <div class="wizard-nav">
                <button type="button" class="btn-wizard btn-wizard-prev" onclick="prevStep()">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button type="submit" class="btn-wizard btn-wizard-submit">
                    <i class="fas fa-paper-plane"></i> Submit Leave Application
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    let currentStep = 0;
    const totalSteps = 4;
    
    function goToStep(step) {
        if (step < 0 || step >= totalSteps) return;
        
        // Hide all sections
        document.querySelectorAll('.wizard-section').forEach(s => s.classList.remove('active'));
        document.getElementById('step-' + step).classList.add('active');
        
        // Update step indicators
        document.querySelectorAll('.wizard-step').forEach((s, i) => {
            s.classList.remove('active', 'completed');
            if (i < step) s.classList.add('completed');
            if (i === step) s.classList.add('active');
        });
        
        // Update progress bar
        const fillWidth = (step / (totalSteps - 1)) * 100;
        document.getElementById('progressFill').style.width = fillWidth + '%';
        
        currentStep = step;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    function nextStep() {
        if (currentStep < totalSteps - 1) {
            goToStep(currentStep + 1);
        }
    }
    
    function prevStep() {
        if (currentStep > 0) {
            goToStep(currentStep - 1);
        }
    }
    
    function calculateDuration() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const durationDisplay = document.getElementById('durationDisplay');
        const daysCount = document.getElementById('daysCount');
        const totalDaysField = document.getElementById('total_days');
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            if (start <= end) {
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                totalDaysField.value = diffDays;
                daysCount.textContent = diffDays;
                durationDisplay.style.display = 'block';
            } else {
                totalDaysField.value = '';
                durationDisplay.style.display = 'none';
            }
        }
    }
    
    function toggleHodFields() {
        const isHod = document.querySelector('input[name="is_applicant_hod"]:checked').value;
        const hodFields = document.getElementById('hodFields');
        
        // Style selected radio
        document.querySelectorAll('.hod-option').forEach(opt => {
            opt.style.borderColor = '#e2e8f0';
            opt.style.background = '#fff';
        });
        document.querySelector('input[name="is_applicant_hod"]:checked').closest('.hod-option').style.borderColor = '#3182ce';
        document.querySelector('input[name="is_applicant_hod"]:checked').closest('.hod-option').style.background = '#ebf8ff';
        
        if (isHod === 'Yes') {
            hodFields.classList.add('visible');
        } else {
            hodFields.classList.remove('visible');
        }
    }
    
    function showFileName(input) {
        const uploadText = document.getElementById('uploadText');
        if (input.files.length > 0) {
            uploadText.innerHTML = '<i class="fas fa-file-check" style="color: #38a169;"></i> ' + input.files[0].name;
        }
    }
    
    function updateSignaturePreview(value) {
        document.getElementById('signaturePreview').textContent = value;
    }
    
    // Initialize radio button styling on load
    document.addEventListener('DOMContentLoaded', function() {
        const checked = document.querySelector('input[name="is_applicant_hod"]:checked');
        if (checked) {
            toggleHodFields();
        }
        calculateDuration();
    });
</script>

<?php require_once '../../includes/footer.php'; ?>
