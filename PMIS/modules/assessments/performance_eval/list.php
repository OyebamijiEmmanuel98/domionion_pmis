<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * PERFORMANCE EVALUATION - LIST
 * =====================================================
 */

require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

requireLogin();

$pageTitle = 'Performance Evaluations';
$breadcrumbs = ['Assessments' => null, 'Performance Evaluations' => null];

$evaluations = [];

try {
    if (isAdmin() || isHR()) {
        $stmt = $pdo->query("
            SELECT pe.*, s.first_name, s.last_name, s.staff_id AS staff_code, 
                   d.department_name, s.staff_type
            FROM performance_appraisals pe
            JOIN staff s ON pe.staff_id = s.id
            LEFT JOIN departments d ON s.department_id = d.id
            ORDER BY pe.created_at DESC
        ");
        $evaluations = $stmt->fetchAll();
    } elseif (isHOD()) {
        $deptId = getCurrentUserDepartmentId();
        $stmt = $pdo->prepare("
            SELECT pe.*, s.first_name, s.last_name, s.staff_id AS staff_code, 
                   d.department_name, s.staff_type
            FROM performance_appraisals pe
            JOIN staff s ON pe.staff_id = s.id
            LEFT JOIN departments d ON s.department_id = d.id
            WHERE s.department_id = ?
            ORDER BY pe.created_at DESC
        ");
        $stmt->execute([$deptId]);
        $evaluations = $stmt->fetchAll();
    } else {
        $staffId = getCurrentStaffId();
        if ($staffId) {
            $stmt = $pdo->prepare("
                SELECT pe.*, s.first_name, s.last_name, s.staff_id AS staff_code, 
                       d.department_name, s.staff_type
                FROM performance_appraisals pe
                JOIN staff s ON pe.staff_id = s.id
                LEFT JOIN departments d ON s.department_id = d.id
                WHERE pe.staff_id = ?
                ORDER BY pe.created_at DESC
            ");
            $stmt->execute([$staffId]);
            $evaluations = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    error_log("Performance Eval List Error: " . $e->getMessage());
    setFlashMessage('error', 'Error loading evaluations');
}

function getEvalStatusLabel($status) {
    $labels = [
        'draft' => 'Draft',
        'pending_hod' => 'Awaiting Part B (HOD)',
        'pending_staff_review' => 'Awaiting Staff Review (Part C)',
        'pending_dean' => 'Awaiting Part D (Dean)',
        'pending_hr' => 'Awaiting Part E (HR)',
        'pending_committee' => 'Awaiting Part F (A&P)',
        'completed' => 'Completed',
        'rejected' => 'Rejected'
    ];
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function getEvalStatusBadgeClass($status) {
    if ($status === 'completed') return 'badge-success';
    if (strpos($status, 'pending') !== false) return 'badge-warning';
    return 'badge-default';
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h3 class="card-title" style="margin:0;">Annual Performance Evaluations</h3>
        <div>
            <a href="apply.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Start Academic Appraisal</a>
            <a href="apply_non_academic.php" class="btn btn-info btn-sm"><i class="fas fa-plus"></i> Start Non-Academic Appraisal</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($evaluations)): ?>
            <div class="table-container">
                <table class="data-table" id="evalTable">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Form Type</th>
                            <th>Department</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluations as $eval): ?>
                            <tr>
                                <td><?php echo escapeOutput($eval['last_name'] . ', ' . $eval['first_name'] . ' (' . $eval['staff_code'] . ')'); ?></td>
                                <td><span class="badge badge-info"><?php echo $eval['appraisal_type'] === 'academic' ? 'Academic' : 'Non-Academic'; ?></span></td>
                                <td><?php echo escapeOutput($eval['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($eval['period_from']); ?> – <?php echo formatDate($eval['period_to']); ?></td>
                                <td>
                                    <span class="badge <?php echo getEvalStatusBadgeClass($eval['status']); ?>">
                                        <?php echo getEvalStatusLabel($eval['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($eval['created_at']); ?></td>
                                <td class="actions">
                                    <?php $viewPage = ($eval['appraisal_type'] === 'non_academic') ? 'view_non_academic.php' : 'view.php'; ?>
                                    <a href="<?php echo $viewPage; ?>?id=<?php echo $eval['id']; ?>" class="btn btn-sm btn-info" title="View / Review Form"><i class="fas fa-eye"></i> View & Process</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No performance evaluations found</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
