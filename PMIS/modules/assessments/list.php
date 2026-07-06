<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * ASSESSMENTS LIST
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

// Require HR or Admin access
requireHR();

$pageTitle = 'Staff Assessments';
$breadcrumbs = ['Assessments' => null];

// Get all assessments
try {
    $stmt = $pdo->query("
        SELECT a.*, s.first_name, s.last_name, s.staff_id, u.username as assessor_name
        FROM assessments a
        JOIN staff s ON a.staff_id = s.id
        JOIN users u ON a.assessor_user_id = u.id
        ORDER BY a.assessment_date DESC
    ");
    $assessments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Assessments List Error: " . $e->getMessage());
    setFlashMessage('error', 'Error loading assessments');
    $assessments = [];
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Assessments</h3>
        <a href="add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Assessment</a>
    </div>
    <div class="card-body">
        <?php if (!empty($assessments)): ?>
            <div class="table-container">
                <table class="data-table" id="assessmentsTable">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Assessment Date</th>
                            <th>Report</th>
                            <th>Assessor</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assessments as $assessment): ?>
                            <tr>
                                <td><?php echo escapeOutput($assessment['last_name'] . ', ' . $assessment['first_name'] . ' (' . $assessment['staff_id'] . ')'); ?></td>
                                <td><?php echo formatDate($assessment['assessment_date']); ?></td>
                                <td><?php echo escapeOutput(truncateText($assessment['report'], 50)); ?></td>
                                <td><?php echo escapeOutput($assessment['assessor_name']); ?></td>
                                <td class="actions">
                                    <a href="view.php?id=<?php echo $assessment['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No assessments found</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
