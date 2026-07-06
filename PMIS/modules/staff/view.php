<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * VIEW STAFF
 * =====================================================
 * 
 * @author Final Year Project
 * @version 1.0
 */

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/role_check.php';

// Require login
requireLogin();

$pageTitle = 'View Staff';
$breadcrumbs = ['Staff' => 'modules/staff/list.php', 'View' => null];

$staffId = $_GET['id'] ?? 0;

// Get staff data with department
$stmt = $pdo->prepare("
    SELECT s.*, d.department_name, d.department_code
    FROM staff s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt->execute([$staffId]);
$staff = $stmt->fetch();

if (!$staff) {
    setFlashMessage('error', 'Staff not found');
    header("Location: list.php");
    exit();
}

// Check permission
if (!canViewDepartmentStaff($staff['department_id']) && getCurrentStaffId() != $staffId) {
    header("Location: ../../access_denied.php");
    exit();
}

// Get leave history
$leaveStmt = $pdo->prepare("
    SELECT la.*, lt.leave_name
    FROM leave_applications la
    JOIN leave_types lt ON la.leave_type_id = lt.id
    WHERE la.staff_id = ?
    ORDER BY la.applied_at DESC
");
$leaveStmt->execute([$staffId]);
$leaveHistory = $leaveStmt->fetchAll();

// Get assessments
$assessStmt = $pdo->prepare("
    SELECT a.*, u.username as assessor_name
    FROM assessments a
    JOIN users u ON a.assessor_user_id = u.id
    WHERE a.staff_id = ?
    ORDER BY a.assessment_date DESC
");
$assessStmt->execute([$staffId]);
$assessments = $assessStmt->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Staff Profile</h3>
        <div>
            <?php if (canEditStaff($staffId)): ?>
                <a href="edit.php?id=<?php echo $staffId; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
            <?php endif; ?>
            <a href="list.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 30px; margin-bottom: 30px;">
            <!-- Photo -->
            <div style="text-align: center;">
                <?php if ($staff['passport_photo']): ?>
                    <img src="<?php echo escapeOutput($staff['passport_photo']); ?>" 
                         alt="Staff Photo" style="width: 150px; height: 150px; border-radius: 8px; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 150px; height: 150px; border-radius: 8px; background: #e2e8f0; 
                                display: flex; align-items: center; justify-content: center; font-size: 48px;">
                        <?php echo getInitials($staff['first_name'] . ' ' . $staff['last_name']); ?>
                    </div>
                <?php endif; ?>
                <div style="margin-top: 10px;">
                    <span class="badge <?php echo getStatusBadgeClass($staff['status']); ?>">
                        <?php echo ucfirst($staff['status']); ?>
                    </span>
                </div>
            </div>
            
            <!-- Basic Info -->
            <div style="flex: 1;">
                <h2 style="margin-bottom: 5px;"><?php echo escapeOutput($staff['last_name'] . ', ' . $staff['first_name'] . ' ' . $staff['middle_name']); ?></h2>
                <p style="color: #718096; margin-bottom: 15px;">
                    <?php echo escapeOutput($staff['staff_id']); ?> | 
                    <?php echo ucfirst(str_replace('_', ' ', $staff['staff_type'])); ?> Staff
                </p>
                
                <div class="form-row" style="margin-top: 20px;">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <p><?php echo escapeOutput($staff['department_name'] ?? 'N/A'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status/Rank</label>
                        <p><?php echo escapeOutput($staff['rank']); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Employment Condition</label>
                        <p><?php echo $staff['employment_condition']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e2e8f0;">
        
        <h4 style="margin-bottom: 20px; color: var(--primary-color);">Personal Information</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Gender</label>
                <p><?php echo $staff['gender']; ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Date of Birth</label>
                <p><?php echo $staff['date_of_birth'] ? formatDate($staff['date_of_birth']) : 'N/A'; ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Marital Status</label>
                <p><?php echo $staff['marital_status'] ?? 'N/A'; ?></p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Address</label>
            <p><?php echo nl2br(escapeOutput($staff['address'] ?? 'Not provided')); ?></p>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Phone</label>
                <p><?php echo escapeOutput($staff['phone'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <p><?php echo escapeOutput($staff['email'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Next of Kin</label>
                <p><?php echo escapeOutput($staff['next_of_kin'] ?? 'N/A'); ?></p>
            </div>
        </div>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e2e8f0;">
        
        <h4 style="margin-bottom: 20px; color: var(--primary-color);">Employment Details</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Qualification</label>
                <p><?php echo escapeOutput($staff['qualification'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Date Recruited</label>
                <p><?php echo $staff['date_recruited'] ? formatDate($staff['date_recruited']) : 'N/A'; ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Basic Salary</label>
                <p><?php echo $staff['basic_salary'] ? formatCurrency($staff['basic_salary']) : 'N/A'; ?></p>
            </div>
        </div>
        
        <?php if ($staff['reason']): ?>
            <div class="form-group">
                <label class="form-label">Remarks</label>
                <p><?php echo nl2br(escapeOutput($staff['reason'])); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Leave History -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Leave History</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($leaveHistory)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Duration</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaveHistory as $leave): ?>
                            <tr>
                                <td><?php echo escapeOutput($leave['leave_name']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td><?php echo escapeOutput(truncateText($leave['reason'], 30)); ?></td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($leave['status']); ?>">
                                        <?php echo ucfirst($leave['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No leave history</p>
        <?php endif; ?>
    </div>
</div>

<!-- Assessments -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Performance Assessments</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($assessments)): ?>
            <?php foreach ($assessments as $assessment): ?>
                <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <strong>Assessment Date: <?php echo formatDate($assessment['assessment_date']); ?></strong>
                        <span class="text-muted">By: <?php echo escapeOutput($assessment['assessor_name']); ?></span>
                    </div>
                    <p style="color: #4a5568;"><?php echo nl2br(escapeOutput($assessment['report'])); ?></p>
                    <?php if ($assessment['recommendation']): ?>
                        <div style="background: #f7fafc; padding: 15px; border-radius: 4px; margin-top: 10px;">
                            <strong>Recommendation:</strong><br>
                            <?php echo nl2br(escapeOutput($assessment['recommendation'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center text-muted">No assessments yet</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
