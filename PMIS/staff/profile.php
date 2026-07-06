<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * STAFF PROFILE
 * =====================================================
 * 
 * @author Final Year Project
 * @version 1.0
 */

require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/role_check.php';

requireLogin();

$pageTitle = 'My Profile';
$breadcrumbs = ['Profile' => null];

$staffId = getCurrentStaffId();
$staffData = null;

if (!$staffId) {
    setFlashMessage('error', 'Your account is not linked to a staff record. Contact an administrator.');
    header("Location: dashboard.php");
    exit();
}

// Get staff details
try {
    $stmt = $pdo->prepare("
        SELECT s.*, d.department_name, d.department_code
        FROM staff s
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE s.id = ?
    ");
    $stmt->execute([$staffId]);
    $staffData = $stmt->fetch();

    if (!$staffData) {
        setFlashMessage('error', 'Staff record not found.');
        header("Location: dashboard.php");
        exit();
    }

    // Get leave summary
    $leaveStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'approved' THEN total_days ELSE 0 END) as total_days_taken
        FROM leave_applications
        WHERE staff_id = ?
    ");
    $leaveStmt->execute([$staffId]);
    $leaveStats = $leaveStmt->fetch();

    // Get recent assessments
    $assessStmt = $pdo->prepare("
        SELECT a.*, u.username as assessor_name
        FROM assessments a
        JOIN users u ON a.assessor_user_id = u.id
        WHERE a.staff_id = ?
        ORDER BY a.assessment_date DESC
        LIMIT 5
    ");
    $assessStmt->execute([$staffId]);
    $assessments = $assessStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Profile Error: " . $e->getMessage());
    setFlashMessage('error', 'Error loading profile data');
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Profile Header -->
<div class="card mb-3">
    <div class="card-body" style="display: flex; align-items: center; gap: 24px;">
        <div class="user-avatar" style="width: 80px; height: 80px; font-size: 28px;">
            <?php echo strtoupper(substr($staffData['first_name'], 0, 1) . substr($staffData['last_name'], 0, 1)); ?>
        </div>
        <div>
            <h2 style="margin-bottom: 4px;"><?php echo escapeOutput($staffData['first_name'] . ' ' . $staffData['last_name']); ?></h2>
            <p class="text-muted" style="margin-bottom: 4px;">
                <?php echo escapeOutput($staffData['rank']); ?> — <?php echo escapeOutput($staffData['department_name'] ?? 'No Department'); ?>
            </p>
            <span class="badge <?php echo getStatusBadgeClass($staffData['status']); ?>">
                <?php echo ucfirst($staffData['status']); ?>
            </span>
            <span class="badge <?php echo $staffData['staff_type'] == 'academic' ? 'badge-success' : 'badge-warning'; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $staffData['staff_type'])); ?>
            </span>
        </div>
    </div>
</div>

<!-- Leave Stats -->
<div class="dashboard-stats" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $leaveStats['total'] ?? 0; ?></div>
            <div class="stat-label">Total Leave Applied</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $leaveStats['pending'] ?? 0; ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $leaveStats['approved'] ?? 0; ?></div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger"><i class="fas fa-calendar-minus"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $leaveStats['total_days_taken'] ?? 0; ?></div>
            <div class="stat-label">Days Taken</div>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Personal Information -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user"></i> Personal Information</h3>
        </div>
        <div class="card-body">
            <table class="profile-table">
                <tr>
                    <th>Staff ID</th>
                    <td><?php echo escapeOutput($staffData['staff_id']); ?></td>
                </tr>
                <tr>
                    <th>First Name</th>
                    <td><?php echo escapeOutput($staffData['first_name']); ?></td>
                </tr>
                <tr>
                    <th>Last Name</th>
                    <td><?php echo escapeOutput($staffData['last_name']); ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo escapeOutput($staffData['email'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td><?php echo escapeOutput($staffData['phone'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Gender</th>
                    <td><?php echo ucfirst($staffData['gender'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Date of Birth</th>
                    <td><?php echo $staffData['date_of_birth'] ? formatDate($staffData['date_of_birth']) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td><?php echo escapeOutput($staffData['address'] ?? 'N/A'); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Employment Information -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-briefcase"></i> Employment Details</h3>
        </div>
        <div class="card-body">
            <table class="profile-table">
                <tr>
                    <th>Department</th>
                    <td><?php echo escapeOutput($staffData['department_name'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Department Code</th>
                    <td><?php echo escapeOutput($staffData['department_code'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Status/Rank</th>
                    <td><?php echo escapeOutput($staffData['rank']); ?></td>
                </tr>
                <tr>
                    <th>Staff Type</th>
                    <td>
                        <span class="badge <?php echo $staffData['staff_type'] == 'academic' ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $staffData['staff_type'])); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Date Employed</th>
                    <td><?php echo (isset($staffData['date_recruited']) && $staffData['date_recruited']) ? formatDate($staffData['date_recruited']) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <th>Qualification</th>
                    <td><?php echo escapeOutput($staffData['qualification'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge <?php echo getStatusBadgeClass($staffData['status']); ?>">
                            <?php echo ucfirst($staffData['status']); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- Recent Assessments -->
<?php if (!empty($assessments)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Recent Assessments</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Assessor</th>
                        <th>Report</th>
                        <th>Recommendation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assessments as $a): ?>
                        <tr>
                            <td><?php echo formatDate($a['assessment_date']); ?></td>
                            <td><?php echo escapeOutput($a['assessor_name']); ?></td>
                            <td><?php echo escapeOutput(truncateText($a['report'], 60)); ?></td>
                            <td><?php echo escapeOutput(truncateText($a['recommendation'] ?? 'N/A', 40)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    margin-bottom: 24px;
}

.profile-table {
    width: 100%;
    border-collapse: collapse;
}

.profile-table tr {
    border-bottom: 1px solid var(--border-color);
}

.profile-table tr:last-child {
    border-bottom: none;
}

.profile-table th {
    text-align: left;
    padding: 12px 16px;
    color: var(--text-muted);
    font-weight: 500;
    width: 40%;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-table td {
    padding: 12px 16px;
    color: var(--text-color);
    font-weight: 500;
}

@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
