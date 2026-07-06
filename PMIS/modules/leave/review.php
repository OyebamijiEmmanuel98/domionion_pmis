<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * REVIEW LEAVE APPLICATIONS
 * =====================================================
 * 
 * Multi-level approval: HOD → Dean/Registrar → VC
 * Role-based access control determines which level
 * 
 * @author Final Year Project
 * @version 2.0
 */

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/role_check.php';

// Require HOD or higher access
requireHOD();

$pageTitle = 'Review Leave Applications';
$breadcrumbs = ['Leave' => null, 'Review' => null];

$departmentId = getCurrentUserDepartmentId();
$errors = [];

// Process approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $leaveId = $_POST['leave_id'] ?? 0;
        $action = $_POST['action']; // 'approve' or 'reject'
        $comment = sanitizeInput($_POST['comment'] ?? '');
        $reviewLevel = $_POST['review_level'] ?? 'hod'; // hod, dean, vc
        
        // Verify the leave application
        $verifyStmt = $pdo->prepare("
            SELECT la.*, s.department_id 
            FROM leave_applications la
            JOIN staff s ON la.staff_id = s.id
            WHERE la.id = ?
        ");
        $verifyStmt->execute([$leaveId]);
        $leaveApp = $verifyStmt->fetch();
        
        if (!$leaveApp) {
            $errors[] = 'Leave application not found';
        } else {
            $canProcess = false;
            $updateFields = [];
            $newOverallStatus = '';
            
            if ($reviewLevel === 'hod') {
                // HOD approval
                if (!isAdmin() && !isHR() && $leaveApp['department_id'] != $departmentId) {
                    $errors[] = 'You can only review leave from your department';
                } elseif ($leaveApp['status'] !== 'pending') {
                    $errors[] = 'This application has already been processed at HOD level';
                } else {
                    $canProcess = true;
                    $newHodStatus = ($action === 'approve') ? 'approved' : 'rejected';
                    $newOverallStatus = ($action === 'approve') ? 'hod_approved' : 'hod_rejected';
                    $updateFields = [
                        'hod_status' => $newHodStatus,
                        'hod_comment' => !empty($comment) ? $comment : null,
                        'status' => $newOverallStatus,
                        'reviewed_at' => date('Y-m-d H:i:s'),
                        'reviewed_by' => getCurrentUserId()
                    ];
                }
            } elseif ($reviewLevel === 'dean') {
                // Dean/Registrar approval
                if (!isAdmin() && !isHR()) {
                    $errors[] = 'Only Admin/HR can process Dean-level reviews';
                } elseif ($leaveApp['status'] !== 'hod_approved') {
                    $errors[] = 'This application must be HOD-approved first';
                } else {
                    $canProcess = true;
                    $newDeanStatus = ($action === 'approve') ? 'approved' : 'rejected';
                    $newOverallStatus = ($action === 'approve') ? 'dean_approved' : 'dean_rejected';
                    $updateFields = [
                        'dean_status' => $newDeanStatus,
                        'dean_comment' => !empty($comment) ? $comment : null,
                        'dean_reviewed_by' => getCurrentUserId(),
                        'dean_reviewed_at' => date('Y-m-d H:i:s'),
                        'status' => $newOverallStatus
                    ];
                }
            } elseif ($reviewLevel === 'vc') {
                // VC final approval
                if (!isAdmin()) {
                    $errors[] = 'Only Admin can process VC-level reviews';
                } elseif ($leaveApp['status'] !== 'dean_approved') {
                    $errors[] = 'This application must be Dean-approved first';
                } else {
                    $canProcess = true;
                    $newVcStatus = ($action === 'approve') ? 'approved' : 'rejected';
                    $newOverallStatus = ($action === 'approve') ? 'approved' : 'rejected';
                    $vcSig = sanitizeInput($_POST['vc_signature'] ?? '');
                    $updateFields = [
                        'vc_status' => $newVcStatus,
                        'vc_signature' => !empty($vcSig) ? $vcSig : null,
                        'vc_reviewed_at' => date('Y-m-d H:i:s'),
                        'status' => $newOverallStatus
                    ];
                }
            }
            
            if ($canProcess && empty($errors)) {
                try {
                    $setClauses = [];
                    $params = [];
                    foreach ($updateFields as $field => $value) {
                        $setClauses[] = "$field = ?";
                        $params[] = $value;
                    }
                    $params[] = $leaveId;
                    
                    $sql = "UPDATE leave_applications SET " . implode(', ', $setClauses) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $actionText = ($action === 'approve') ? 'APPROVE_LEAVE' : 'REJECT_LEAVE';
                    logActivity($actionText, 'leave_applications', $leaveId, "Leave $newOverallStatus at $reviewLevel level");
                    
                    setFlashMessage('success', 'Leave application ' . $action . 'd successfully at ' . strtoupper($reviewLevel) . ' level');
                    header("Location: review.php");
                    exit();
                    
                } catch (PDOException $e) {
                    error_log("Review Leave Error: " . $e->getMessage());
                    $errors[] = 'Error processing leave application';
                }
            }
        }
    }
}

// ---- FETCH APPLICATIONS FOR CURRENT USER'S ROLE ----

// Pending HOD Review
if (isAdmin() || isHR()) {
    $hodPendingSql = "
        SELECT la.*, lt.leave_name, s.first_name, s.last_name, s.staff_id, d.department_name,
               la.reliever_name, la.is_applicant_hod
        FROM leave_applications la
        JOIN leave_types lt ON la.leave_type_id = lt.id
        JOIN staff s ON la.staff_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE la.status = 'pending'
        ORDER BY la.applied_at DESC
    ";
    $hodPendingStmt = $pdo->query($hodPendingSql);
} else {
    $hodPendingSql = "
        SELECT la.*, lt.leave_name, s.first_name, s.last_name, s.staff_id, d.department_name,
               la.reliever_name, la.is_applicant_hod
        FROM leave_applications la
        JOIN leave_types lt ON la.leave_type_id = lt.id
        JOIN staff s ON la.staff_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE la.status = 'pending' AND s.department_id = :dept_id
        ORDER BY la.applied_at DESC
    ";
    $hodPendingStmt = $pdo->prepare($hodPendingSql);
    $hodPendingStmt->execute([':dept_id' => $departmentId]);
}
$hodPending = $hodPendingStmt->fetchAll();

// Pending Dean Review (only visible to Admin/HR)
$deanPending = [];
if (isAdmin() || isHR()) {
    $deanPendingSql = "
        SELECT la.*, lt.leave_name, s.first_name, s.last_name, s.staff_id, d.department_name,
               la.reliever_name, la.is_applicant_hod
        FROM leave_applications la
        JOIN leave_types lt ON la.leave_type_id = lt.id
        JOIN staff s ON la.staff_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE la.status = 'hod_approved'
        ORDER BY la.applied_at DESC
    ";
    $deanPending = $pdo->query($deanPendingSql)->fetchAll();
}

// Pending VC Review (only visible to Admin)
$vcPending = [];
if (isAdmin()) {
    $vcPendingSql = "
        SELECT la.*, lt.leave_name, s.first_name, s.last_name, s.staff_id, d.department_name,
               la.reliever_name, la.is_applicant_hod
        FROM leave_applications la
        JOIN leave_types lt ON la.leave_type_id = lt.id
        JOIN staff s ON la.staff_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE la.status = 'dean_approved'
        ORDER BY la.applied_at DESC
    ";
    $vcPending = $pdo->query($vcPendingSql)->fetchAll();
}

// Recently Processed
if (isAdmin() || isHR()) {
    $processedSql = "
        SELECT la.*, lt.leave_name, s.first_name, s.last_name, s.staff_id, d.department_name, u.username as reviewed_by_name
        FROM leave_applications la
        JOIN leave_types lt ON la.leave_type_id = lt.id
        JOIN staff s ON la.staff_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        LEFT JOIN users u ON la.reviewed_by = u.id
        WHERE la.status NOT IN ('pending')
        ORDER BY la.reviewed_at DESC
        LIMIT 15
    ";
    $processedStmt = $pdo->query($processedSql);
} else {
    $processedSql = "
        SELECT la.*, lt.leave_name, s.first_name, s.last_name, s.staff_id, d.department_name, u.username as reviewed_by_name
        FROM leave_applications la
        JOIN leave_types lt ON la.leave_type_id = lt.id
        JOIN staff s ON la.staff_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        LEFT JOIN users u ON la.reviewed_by = u.id
        WHERE la.status NOT IN ('pending') AND s.department_id = :dept_id
        ORDER BY la.reviewed_at DESC
        LIMIT 15
    ";
    $processedStmt = $pdo->prepare($processedSql);
    $processedStmt->execute([':dept_id' => $departmentId]);
}
$processedLeave = $processedStmt->fetchAll();

// Helper for status display
function getLeaveStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending HOD</span>',
        'hod_approved' => '<span class="badge badge-info" style="background:#ebf4ff;color:#2b6cb0;">HOD Approved</span>',
        'hod_rejected' => '<span class="badge badge-danger">HOD Rejected</span>',
        'dean_approved' => '<span class="badge badge-info" style="background:#e9d8fd;color:#553c9a;">Dean Approved</span>',
        'dean_rejected' => '<span class="badge badge-danger">Dean Rejected</span>',
        'approved' => '<span class="badge badge-success">Fully Approved</span>',
        'rejected' => '<span class="badge badge-danger">Rejected</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-default">' . ucfirst($status) . '</span>';
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <div><?php echo escapeOutput($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- HOD Level: Pending Applications -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-hourglass-half" style="color:#ed8936;margin-right:8px;"></i> Pending HOD Review</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($hodPending)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Department</th>
                            <th>Leave Type</th>
                            <th>Duration</th>
                            <th>Days</th>
                            <th>Reliever</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hodPending as $leave): ?>
                            <tr>
                                <td><?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?></td>
                                <td><?php echo escapeOutput($leave['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($leave['leave_name']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td><?php echo escapeOutput($leave['reliever_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($leave['applied_at']); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> View Form</a>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="openReviewModal(<?php echo $leave['id']; ?>, 'approve', '<?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?>', 'hod')"><i class="fas fa-check"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="openReviewModal(<?php echo $leave['id']; ?>, 'reject', '<?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?>', 'hod')"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No pending HOD-level leave applications</p>
        <?php endif; ?>
    </div>
</div>

<?php if (isAdmin() || isHR()): ?>
<!-- Dean Level: Pending Applications -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-graduate" style="color:#805ad5;margin-right:8px;"></i> Pending Registrar/Dean Review</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($deanPending)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Department</th>
                            <th>Leave Type</th>
                            <th>Duration</th>
                            <th>Days</th>
                            <th>HOD Comment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deanPending as $leave): ?>
                            <tr>
                                <td><?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?></td>
                                <td><?php echo escapeOutput($leave['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($leave['leave_name']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td><?php echo escapeOutput($leave['hod_comment'] ?? '-'); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> View Form</a>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="openReviewModal(<?php echo $leave['id']; ?>, 'approve', '<?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?>', 'dean')"><i class="fas fa-check"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="openReviewModal(<?php echo $leave['id']; ?>, 'reject', '<?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?>', 'dean')"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No pending Dean/Registrar-level applications</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<!-- VC Level: Pending Applications -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-crown" style="color:#d69e2e;margin-right:8px;"></i> Pending Vice-Chancellor Decision</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($vcPending)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Department</th>
                            <th>Leave Type</th>
                            <th>Duration</th>
                            <th>Days</th>
                            <th>Dean Comment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vcPending as $leave): ?>
                            <tr>
                                <td><?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?></td>
                                <td><?php echo escapeOutput($leave['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($leave['leave_name']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td><?php echo escapeOutput($leave['dean_comment'] ?? '-'); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> View Form</a>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="openReviewModal(<?php echo $leave['id']; ?>, 'approve', '<?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?>', 'vc')"><i class="fas fa-check"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="openReviewModal(<?php echo $leave['id']; ?>, 'reject', '<?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?>', 'vc')"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No pending VC-level applications</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recently Processed -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recently Processed</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($processedLeave)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Leave Type</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>HOD Comment</th>
                            <th>Processed By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processedLeave as $leave): ?>
                            <tr>
                                <td><?php echo escapeOutput($leave['last_name'] . ', ' . $leave['first_name']); ?></td>
                                <td><?php echo escapeOutput($leave['leave_name']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo getLeaveStatusBadge($leave['status']); ?></td>
                                <td><?php echo escapeOutput($leave['hod_comment'] ?? 'N/A'); ?></td>
                                <td><?php echo escapeOutput($leave['reviewed_by_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDateTime($leave['reviewed_at']); ?></td>
                                <td><a href="view.php?id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No processed applications</p>
        <?php endif; ?>
    </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                              background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
        <h3 id="modalTitle" style="margin-bottom: 1rem; color: #1e3a5f;">Review Leave Application</h3>
        <p id="modalStaff" style="color: #718096; margin-bottom: 1.5rem;"></p>
        
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <input type="hidden" name="leave_id" id="modalLeaveId">
            <input type="hidden" name="action" id="modalAction">
            <input type="hidden" name="review_level" id="modalLevel">
            
            <div class="form-group">
                <label class="form-label">Comment (Optional)</label>
                <textarea name="comment" class="form-control" rows="3" placeholder="Add a comment..."></textarea>
            </div>
            
            <div class="form-group" id="vcSignatureField" style="display:none; margin-top: 15px;">
                <label class="form-label">VC Signature (Typed Name)</label>
                <input type="text" name="vc_signature" class="form-control" placeholder="Type name as signature">
            </div>
            
            <div class="form-group" style="margin-top: 20px; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Submit</button>
                <button type="button" class="btn btn-outline" onclick="closeReviewModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(leaveId, action, staffName, level) {
    document.getElementById('modalLeaveId').value = leaveId;
    document.getElementById('modalAction').value = action;
    document.getElementById('modalLevel').value = level;
    document.getElementById('modalStaff').textContent = 'Staff: ' + staffName;
    
    var levelNames = { hod: 'HOD', dean: 'Dean/Registrar', vc: 'Vice-Chancellor' };
    var title = (action === 'approve' ? 'Approve' : 'Reject') + ' Leave — ' + levelNames[level] + ' Level';
    document.getElementById('modalTitle').textContent = title;
    
    var btn = document.getElementById('modalSubmitBtn');
    btn.innerHTML = action === 'approve' ? '<i class="fas fa-check"></i> Approve' : '<i class="fas fa-times"></i> Reject';
    btn.className = action === 'approve' ? 'btn btn-success' : 'btn btn-danger';
    
    document.getElementById('vcSignatureField').style.display = (level === 'vc') ? 'block' : 'none';
    document.getElementById('reviewModal').style.display = 'flex';
}

function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

document.getElementById('reviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeReviewModal();
});
</script>

<?php require_once '../../includes/footer.php'; ?>
