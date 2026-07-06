<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * SHORT LEAVE APPLICATION DIGITAL FORM - VIEW & REVIEW
 * =====================================================
 * 
 * Holistic digital form representing the requested layout.
 * Sections 1-4: Read Only
 * Section 5: Review options depending on user role
 * 
 * @version 2.1
 */

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/role_check.php';

requireLogin();

$leaveId = $_GET['id'] ?? 0;

if (!$leaveId) {
    setFlashMessage('error', 'No leave application specified.');
    redirectBack();
}

$stmt = $pdo->prepare("
    SELECT la.*, s.first_name, s.middle_name, s.last_name, s.staff_id, s.rank as position, 
           d.department_name, lt.leave_name
    FROM leave_applications la
    JOIN staff s ON la.staff_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    JOIN leave_types lt ON la.leave_type_id = lt.id
    WHERE la.id = ?
");
$stmt->execute([$leaveId]);
$leaveApp = $stmt->fetch();

if (!$leaveApp) {
    setFlashMessage('error', 'Leave application not found.');
    redirectBack();
}

$pageTitle = 'Digital Leave Form';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<style>
.digital-form-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
.form-header { text-align: center; border-bottom: 2px solid #2b6cb0; padding-bottom: 15px; margin-bottom: 30px; }
.form-header h2 { color: #1e3a5f; margin: 0; font-size: 1.5rem; text-transform: uppercase; font-weight: 700; }
.form-section { margin-bottom: 30px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.section-title { background: #ebf8ff; color: #2b6cb0; padding: 12px 20px; font-weight: 700; margin: 0; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; font-size: 1.1rem; }
.section-content { padding: 20px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
.form-row.full { grid-template-columns: 1fr; }
.form-row.thirds { grid-template-columns: 1fr 1fr 1fr; }
.label-field { font-weight: 600; color: #4a5568; margin-bottom: 4px; display: block; font-size: 0.9rem; }
.value-field { background: #f7fafc; padding: 10px 15px; border-radius: 6px; border: 1px solid #e2e8f0; color: #1a202c; min-height: 42px; }
.signature { font-family: 'Brush Script MT', 'Segoe Script', cursive; font-size: 1.3rem; color: #2b6cb0; }
.review-box { border: 2px dashed #cbd5e0; padding: 15px; border-radius: 8px; margin-bottom: 15px; background: #fafbfc; }
.review-box h4 { margin: 0 0 10px 0; color: #2d3748; }
.toggle-group { display: flex; gap: 15px; margin-top: 10px; }
.checkbox-item { display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer; }
.checkbox-item input[type="radio"], .checkbox-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
@media(max-width: 600px){ .form-row { grid-template-columns: 1fr; } .form-row.thirds { grid-template-columns: 1fr; } }
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
        <h2>Short Leave Application Digital Form</h2>
    </div>

    <!-- SECTION 1 -->
    <div class="form-section">
        <h3 class="section-title">SECTION 1: APPLICANT DETAILS</h3>
        <div class="section-content">
            <div class="form-row">
                <div>
                    <span class="label-field">Full Name (Short Answer)</span>
                    <div class="value-field"><?php echo escapeOutput($leaveApp['first_name'] . ' ' . $leaveApp['last_name']); ?></div>
                </div>
                <div>
                    <span class="label-field">Department/Unit (Short Answer)</span>
                    <div class="value-field"><?php echo escapeOutput($leaveApp['department_name'] ?? 'N/A'); ?></div>
                </div>
                <div>
                    <span class="label-field">Position (Short Answer)</span>
                    <div class="value-field"><?php echo escapeOutput($leaveApp['position'] ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2 -->
    <div class="form-section">
        <h3 class="section-title">SECTION 2: LEAVE DETAILS</h3>
        <div class="section-content">
            <div class="form-row thirds">
                <div>
                    <span class="label-field">Start Date (Date Picker)</span>
                    <div class="value-field"><?php echo formatDate($leaveApp['start_date']); ?></div>
                </div>
                <div>
                    <span class="label-field">End Date (Date Picker)</span>
                    <div class="value-field"><?php echo formatDate($leaveApp['end_date']); ?></div>
                </div>
                <div>
                    <span class="label-field">Duration (Auto-calculated)</span>
                    <div class="value-field"><?php echo $leaveApp['total_days']; ?> Working Days</div>
                </div>
            </div>
            <div class="form-row full">
                <div>
                    <span class="label-field">Reason for Leave (Paragraph)</span>
                    <div class="value-field" style="min-height: 80px;"><?php echo nl2br(escapeOutput($leaveApp['reason'])); ?></div>
                </div>
            </div>
            <div class="form-row full">
                <div>
                    <span class="label-field">Upload Supporting Documents (File Upload)</span>
                    <div class="value-field">
                        <?php if($leaveApp['supporting_doc']): ?>
                            <a href="<?php echo getBaseUrl(); ?><?php echo ltrim($leaveApp['supporting_doc'], './'); ?>" target="_blank"><i class="fas fa-file-download"></i> View Document</a>
                        <?php else: ?>
                            <span style="color:#a0aec0;">No documents attached</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3 -->
    <div class="form-section">
        <h3 class="section-title">SECTION 3: HANDOVER DETAILS</h3>
        <div class="section-content">
            <div class="form-row">
                <div>
                    <span class="label-field">Person Covering Duties (Short Answer)</span>
                    <div class="value-field"><?php echo escapeOutput($leaveApp['reliever_name']); ?></div>
                </div>
                <div>
                    <span class="label-field">Is Applicant HOD? (Yes/No)</span>
                    <div class="value-field"><?php echo escapeOutput($leaveApp['is_applicant_hod']); ?></div>
                </div>
            </div>
            
            <?php if($leaveApp['is_applicant_hod'] === 'Yes'): ?>
            <div class="form-row" style="background: #fdfaf0; padding: 15px; border-radius: 6px; border: 1px dashed #d69e2e;">
                <div>
                    <span class="label-field">Acting HOD Name (Short Answer)</span>
                    <div class="value-field"><?php echo escapeOutput($leaveApp['acting_hod_name'] ?? 'N/A'); ?></div>
                </div>
                <div>
                    <span class="label-field">Is Acting HOD Most Senior? (Yes/No)</span>
                    <div class="value-field"><?php echo escapeOutput($leaveApp['acting_hod_most_senior'] ?? 'N/A'); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 4 -->
    <div class="form-section">
        <h3 class="section-title">SECTION 4: DECLARATION</h3>
        <div class="section-content">
            <div class="form-row">
                <div>
                    <span class="label-field">Applicant Signature (Typed Name)</span>
                    <div class="value-field signature"><?php echo escapeOutput($leaveApp['applicant_signature']); ?></div>
                </div>
                <div>
                    <span class="label-field">Date (Auto)</span>
                    <div class="value-field"><?php echo formatDate($leaveApp['applied_at']); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 5 -->
    <div class="form-section" style="border-color: #e53e3e;">
        <h3 class="section-title" style="background: #fed7d7; color: #c53030; border-bottom-color: #fc8181;">SECTION 5: APPROVAL WORKFLOW (RESTRICTED)</h3>
        <div class="section-content">
            
            <form method="POST" action="review.php" id="approvalForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="leave_id" value="<?php echo $leaveId; ?>">
                
                <!-- HOD Review -->
                <div class="review-box" <?php echo ($leaveApp['status'] === 'pending' && (isHOD() || isAdmin() || isHR())) ? 'style="border-color:#3182ce; background:#ebf8ff;"' : ''; ?>>
                    <h4>HOD Review</h4>
                    <?php if ($leaveApp['status'] === 'pending' && (isHOD() || isAdmin() || isHR())): ?>
                        <input type="hidden" name="review_level" value="hod">
                        <div class="form-row full">
                            <span class="label-field">Comment (Paragraph)</span>
                            <textarea name="comment" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-row full">
                            <span class="label-field">Approve / Reject (Toggle)</span>
                            <div class="toggle-group">
                                <label class="checkbox-item"><input type="radio" name="action" value="approve" required> Approve</label>
                                <label class="checkbox-item"><input type="radio" name="action" value="reject" required> Reject</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top:10px;">Submit HOD Decision</button>
                    <?php else: ?>
                        <div class="value-field" style="margin-bottom:10px;">Comment: <?php echo escapeOutput($leaveApp['hod_comment'] ?? '-'); ?></div>
                        <div class="value-field">Status: <?php echo ucfirst($leaveApp['hod_status']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Registrar/Dean Review -->
                <div class="review-box" <?php echo ($leaveApp['status'] === 'hod_approved' && (isAdmin() || isHR())) ? 'style="border-color:#3182ce; background:#ebf8ff;"' : ''; ?>>
                    <h4>Registrar / Dean Review</h4>
                    <?php if ($leaveApp['status'] === 'hod_approved' && (isAdmin() || isHR())): ?>
                        <input type="hidden" name="review_level" value="dean">
                        <div class="form-row full">
                            <span class="label-field">Comment (Paragraph)</span>
                            <textarea name="comment" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-row full">
                            <span class="label-field">Approve / Reject (Check boxes)</span>
                            <div class="toggle-group">
                                <label class="checkbox-item"><input type="radio" name="action" value="approve" required> Approve</label>
                                <label class="checkbox-item"><input type="radio" name="action" value="reject" required> Reject</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top:10px;">Submit Dean Decision</button>
                    <?php else: ?>
                        <div class="value-field" style="margin-bottom:10px;">Comment: <?php echo escapeOutput($leaveApp['dean_comment'] ?? '-'); ?></div>
                        <div class="value-field">Status: <?php echo ucfirst($leaveApp['dean_status']); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Vice-Chancellor Decision -->
                <div class="review-box" <?php echo ($leaveApp['status'] === 'dean_approved' && isAdmin()) ? 'style="border-color:#3182ce; background:#ebf8ff;"' : ''; ?>>
                    <h4>Vice-Chancellor Decision</h4>
                    <?php if ($leaveApp['status'] === 'dean_approved' && isAdmin()): ?>
                        <input type="hidden" name="review_level" value="vc">
                        <div class="form-row full">
                            <span class="label-field">Final Approval (Check boxes)</span>
                            <div class="toggle-group">
                                <label class="checkbox-item"><input type="radio" name="action" value="approve" required> Approved</label>
                                <label class="checkbox-item"><input type="radio" name="action" value="reject" required> Not Approved</label>
                            </div>
                        </div>
                        <div class="form-row full">
                            <span class="label-field">VC Signature (Typed Name)</span>
                            <input type="text" name="vc_signature" class="form-control">
                        </div>
                        <div class="form-row full">
                            <span class="label-field">Date (Date Picker)</span>
                            <input type="date" name="vc_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top:10px;">Submit VC Decision</button>
                    <?php else: ?>
                        <div class="form-row">
                            <div>
                                <span class="label-field">Status</span>
                                <div class="value-field"><?php echo ucfirst($leaveApp['vc_status']); ?></div>
                            </div>
                            <div>
                                <span class="label-field">Date</span>
                                <div class="value-field"><?php echo $leaveApp['vc_reviewed_at'] ? date('Y-m-d', strtotime($leaveApp['vc_reviewed_at'])) : '-'; ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </form>
        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>
