<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * ACADEMIC STAFF APPRAISAL - LIST VIEW
 * =====================================================
 */

require_once '../../../config/db.php';
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/role_check.php';

// Only Admin, HR, HOD can see this
requireHOD();

$pageTitle = 'Academic Staff Appraisals';
$breadcrumbs = ['Assessments' => null, 'Academic Appraisals' => null];

// Get evaluations based on role
if (isAdmin() || isHR()) {
    $sql = "
        SELECT ae.*, s.first_name, s.last_name, s.staff_id, d.department_name
        FROM academic_evaluations ae
        JOIN staff s ON ae.staff_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        ORDER BY ae.created_at DESC
    ";
    $stmt = $pdo->query($sql);
} else {
    // HOD sees only their department
    $deptId = getCurrentUserDepartmentId();
    $sql = "
        SELECT ae.*, s.first_name, s.last_name, s.staff_id, d.department_name
        FROM academic_evaluations ae
        JOIN staff s ON ae.staff_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE s.department_id = ?
        ORDER BY ae.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$deptId]);
}
$evaluations = $stmt->fetchAll();

// Status display helper
function getAcademicEvalStatus($status) {
    $labels = [
        'part_a_pending' => ['Part A: Staff Input', 'badge-warning'],
        'part_b_pending' => ['Part B: HOD Assessment', 'badge-info'],
        'part_c_pending' => ['Part C: Staff Response', 'badge-warning'],
        'part_d_pending' => ['Part D: Dean Review', 'badge-info'],
        'part_e_pending' => ['Part E: HR Review', 'badge-info'],
        'part_f_pending' => ['Part F: A&P Committee', 'badge-info'],
        'completed' => ['Completed', 'badge-success']
    ];
    $info = $labels[$status] ?? ['Unknown', 'badge-default'];
    return '<span class="badge ' . $info[1] . '">' . $info[0] . '</span>';
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-graduation-cap" style="margin-right:8px;color:#3182ce;"></i> Academic Staff Appraisals</h3>
        <?php if (isAdmin() || isHR() || isHOD()): ?>
            <a href="initiate.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Initiate New Appraisal</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!empty($evaluations)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Staff Name</th>
                            <th>Department</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluations as $eval): ?>
                            <tr>
                                <td><?php echo escapeOutput($eval['staff_id']); ?></td>
                                <td><?php echo escapeOutput($eval['last_name'] . ', ' . $eval['first_name']); ?></td>
                                <td><?php echo escapeOutput($eval['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($eval['period_from']); ?> – <?php echo formatDate($eval['period_to']); ?></td>
                                <td><?php echo getAcademicEvalStatus($eval['status']); ?></td>
                                <td><?php echo formatDate($eval['created_at']); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $eval['id']; ?>" class="btn btn-sm btn-outline" title="View"><i class="fas fa-eye"></i></a>
                                    <?php
                                    // Show appropriate action button based on status
                                    $actionPage = '';
                                    switch($eval['status']) {
                                        case 'part_a_pending': $actionPage = 'part_a.php'; break;
                                        case 'part_b_pending': $actionPage = 'part_b.php'; break;
                                        case 'part_c_pending': $actionPage = 'part_c.php'; break;
                                        case 'part_d_pending': $actionPage = 'part_d.php'; break;
                                        case 'part_e_pending': $actionPage = 'part_e.php'; break;
                                        case 'part_f_pending': $actionPage = 'part_f.php'; break;
                                    }
                                    if ($actionPage): ?>
                                        <a href="<?php echo $actionPage; ?>?id=<?php echo $eval['id']; ?>" class="btn btn-sm btn-primary" title="Continue"><i class="fas fa-edit"></i> Continue</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center" style="padding: 40px;">
                <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #cbd5e0; margin-bottom: 1rem;"></i>
                <p style="color: #718096; margin-bottom: 20px;">No academic appraisals found.</p>
                <?php if (isAdmin() || isHR() || isHOD()): ?>
                    <a href="initiate.php" class="btn btn-primary">Initiate New Appraisal</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
