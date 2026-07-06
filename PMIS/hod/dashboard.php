<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * HOD DASHBOARD
 * =====================================================
 * 
 * This is the main dashboard for Heads of Departments.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Include required files
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/role_check.php';

// Require HOD or higher access
requireHOD();

// Page title
$pageTitle = 'HOD Dashboard';

// Get HOD's department ID
$departmentId = getCurrentUserDepartmentId();
$departmentName = 'Your Department';

// Get dashboard statistics
try {
    // Get department name
    if ($departmentId) {
        $stmt = $pdo->prepare("SELECT department_name FROM departments WHERE id = ?");
        $stmt->execute([$departmentId]);
        $dept = $stmt->fetch();
        if ($dept) {
            $departmentName = $dept['department_name'];
        }
    }
    
    // Staff in department
    if ($departmentId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM staff WHERE department_id = ?");
        $stmt->execute([$departmentId]);
        $deptStaff = $stmt->fetch()['total'];
    } else {
        $deptStaff = 0;
    }
    
    // Pending leave approvals for department
    if ($departmentId) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM leave_applications la 
            JOIN staff s ON la.staff_id = s.id 
            WHERE s.department_id = ? AND la.status = 'pending'
        ");
        $stmt->execute([$departmentId]);
        $pendingApprovals = $stmt->fetch()['total'];
    } else {
        $pendingApprovals = 0;
    }
    
    // Approved leave count
    if ($departmentId) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM leave_applications la 
            JOIN staff s ON la.staff_id = s.id 
            WHERE s.department_id = ? AND la.status = 'approved'
        ");
        $stmt->execute([$departmentId]);
        $approvedLeave = $stmt->fetch()['total'];
    } else {
        $approvedLeave = 0;
    }
    
    // Rejected leave count
    if ($departmentId) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM leave_applications la 
            JOIN staff s ON la.staff_id = s.id 
            WHERE s.department_id = ? AND la.status = 'rejected'
        ");
        $stmt->execute([$departmentId]);
        $rejectedLeave = $stmt->fetch()['total'];
    } else {
        $rejectedLeave = 0;
    }
    
    // Department staff list
    if ($departmentId) {
        $stmt = $pdo->prepare("
            SELECT s.*, d.department_name 
            FROM staff s 
            LEFT JOIN departments d ON s.department_id = d.id 
            WHERE s.department_id = ? 
            ORDER BY s.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$departmentId]);
        $staffList = $stmt->fetchAll();
    } else {
        $staffList = [];
    }
    
    // Pending leave requests for review
    if ($departmentId) {
        $stmt = $pdo->prepare("
            SELECT la.*, s.first_name, s.last_name, s.staff_id, lt.leave_name 
            FROM leave_applications la 
            JOIN staff s ON la.staff_id = s.id 
            JOIN leave_types lt ON la.leave_type_id = lt.id 
            WHERE s.department_id = ? AND la.status = 'pending'
            ORDER BY la.applied_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$departmentId]);
        $pendingLeaveRequests = $stmt->fetchAll();
    } else {
        $pendingLeaveRequests = [];
    }
    
} catch (PDOException $e) {
    error_log("HOD Dashboard Error: " . $e->getMessage());
    setFlashMessage('error', 'Error loading dashboard data');
}

// Include header
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Department Info -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-building"></i> <?php echo escapeOutput($departmentName); ?></h3>
    </div>
</div>

<style>
.clickable-stat {
    cursor: pointer;
    transition: all 0.3s ease;
}
.clickable-stat:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 20px rgba(0,0,0,0.1);
}
</style>

<!-- Dashboard Stats -->
<div class="dashboard-stats">
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/staff/department.php'">
        <div class="stat-icon primary"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($deptStaff ?? 0); ?></div>
            <div class="stat-label">Department Staff</div>
        </div>
    </div>
    
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/leave/review.php'">
        <div class="stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($pendingApprovals ?? 0); ?></div>
            <div class="stat-label">Pending Approvals</div>
        </div>
    </div>
    
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/leave/review.php'">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($approvedLeave ?? 0); ?></div>
            <div class="stat-label">Approved Leave</div>
        </div>
    </div>
    
    <div class="stat-card clickable-stat" onclick="window.location.href='../modules/leave/review.php'">
        <div class="stat-icon danger"><i class="fas fa-times-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($rejectedLeave ?? 0); ?></div>
            <div class="stat-label">Rejected Leave</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 flex-wrap">
            <a href="../modules/staff/department.php" class="btn btn-primary"><i class="fas fa-users"></i> View Department Staff</a>
            <a href="../modules/leave/review.php" class="btn btn-warning"><i class="fas fa-check-circle"></i> Review Leave Requests</a>
            <a href="../modules/reports/department.php" class="btn btn-info"><i class="fas fa-file-alt"></i> Department Report</a>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Department Staff -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Department Staff</h3>
            <a href="../modules/staff/department.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php if (!empty($staffList)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Staff ID</th>
                                <th>Name</th>
                                <th>Status/Rank</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffList as $staff): ?>
                                <tr>
                                    <td><?php echo escapeOutput($staff['staff_id']); ?></td>
                                    <td><?php echo escapeOutput($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                                    <td><?php echo escapeOutput($staff['rank']); ?></td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($staff['status']); ?>">
                                            <?php echo ucfirst($staff['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No staff in department</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pending Leave Requests -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pending Leave Requests</h3>
            <a href="../modules/leave/review.php" class="btn btn-sm btn-outline">Review All</a>
        </div>
        <div class="card-body">
            <?php if (!empty($pendingLeaveRequests)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Leave Type</th>
                                <th>Days</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingLeaveRequests as $leave): ?>
                                <tr>
                                    <td><?php echo escapeOutput($leave['first_name'] . ' ' . $leave['last_name']); ?></td>
                                    <td><?php echo escapeOutput($leave['leave_name']); ?></td>
                                    <td><?php echo $leave['total_days']; ?></td>
                                    <td>
                                        <a href="../modules/leave/review.php?id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No pending leave requests</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Include footer
require_once '../includes/footer.php';
