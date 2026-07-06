<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * ADD STAFF
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

$pageTitle = 'Add Staff';
$breadcrumbs = ['Staff' => 'modules/staff/list.php', 'Add Staff' => null];

$errors = [];
$formData = [];

// Get departments for dropdown
$deptStmt = $pdo->query("SELECT id, department_name FROM departments ORDER BY department_name");
$departments = $deptStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get and sanitize input
        $formData = [
            'staff_id' => sanitizeInput($_POST['staff_id'] ?? ''),
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'middle_name' => sanitizeInput($_POST['middle_name'] ?? ''),
            'gender' => $_POST['gender'] ?? '',
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'marital_status' => $_POST['marital_status'] ?? '',
            'address' => sanitizeInput($_POST['address'] ?? ''),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'next_of_kin' => sanitizeInput($_POST['next_of_kin'] ?? ''),
            'height' => sanitizeInput($_POST['height'] ?? ''),
            'qualification' => sanitizeInput($_POST['qualification'] ?? ''),
            'department_id' => $_POST['department_id'] ?? '',
            'rank' => sanitizeInput($_POST['rank'] ?? ''),
            'employment_condition' => $_POST['employment_condition'] ?? 'Permanent',
            'date_recruited' => $_POST['date_recruited'] ?? '',
            'reason' => sanitizeInput($_POST['reason'] ?? ''),
            'basic_salary' => $_POST['basic_salary'] ?? '',
            'staff_type' => $_POST['staff_type'] ?? '',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        // Validation
        if (empty($formData['staff_id'])) {
            $errors[] = 'Staff ID is required';
        } elseif (recordExists($pdo, 'staff', 'staff_id', $formData['staff_id'])) {
            $errors[] = 'Staff ID already exists';
        }
        
        if (empty($formData['first_name'])) {
            $errors[] = 'First name is required';
        }
        
        if (empty($formData['last_name'])) {
            $errors[] = 'Last name is required';
        }
        
        if (empty($formData['gender'])) {
            $errors[] = 'Gender is required';
        }
        
        if (empty($formData['department_id']) && $formData['staff_type'] !== 'non_academic') {
            $errors[] = 'Department is required';
        }
        
        if (empty($formData['rank'])) {
            $errors[] = 'Status/Rank is required';
        }
        
        if (empty($formData['staff_type'])) {
            $errors[] = 'Staff type is required';
        }
        
        if (!empty($formData['email']) && !isValidEmail($formData['email'])) {
            $errors[] = 'Invalid email address';
        }
        
        if (!empty($formData['basic_salary']) && !is_numeric($formData['basic_salary'])) {
            $errors[] = 'Basic salary must be numeric';
        }
        
        // If no errors, insert
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO staff (
                        staff_id, first_name, last_name, middle_name, gender, date_of_birth,
                        marital_status, address, phone, email, next_of_kin, height,
                        qualification, department_id, rank, employment_condition,
                        date_recruited, reason, basic_salary, staff_type, status
                    ) VALUES (
                        :staff_id, :first_name, :last_name, :middle_name, :gender, :date_of_birth,
                        :marital_status, :address, :phone, :email, :next_of_kin, :height,
                        :qualification, :department_id, :rank, :employment_condition,
                        :date_recruited, :reason, :basic_salary, :staff_type, :status
                    )
                ");
                
                $stmt->execute([
                    ':staff_id' => $formData['staff_id'],
                    ':first_name' => $formData['first_name'],
                    ':last_name' => $formData['last_name'],
                    ':middle_name' => !empty($formData['middle_name']) ? $formData['middle_name'] : null,
                    ':gender' => $formData['gender'],
                    ':date_of_birth' => !empty($formData['date_of_birth']) ? $formData['date_of_birth'] : null,
                    ':marital_status' => !empty($formData['marital_status']) ? $formData['marital_status'] : null,
                    ':address' => !empty($formData['address']) ? $formData['address'] : null,
                    ':phone' => !empty($formData['phone']) ? $formData['phone'] : null,
                    ':email' => !empty($formData['email']) ? $formData['email'] : null,
                    ':next_of_kin' => !empty($formData['next_of_kin']) ? $formData['next_of_kin'] : null,
                    ':height' => !empty($formData['height']) ? $formData['height'] : null,
                    ':qualification' => !empty($formData['qualification']) ? $formData['qualification'] : null,
                    ':department_id' => !empty($formData['department_id']) ? $formData['department_id'] : null,
                    ':rank' => $formData['rank'],
                    ':employment_condition' => $formData['employment_condition'],
                    ':date_recruited' => !empty($formData['date_recruited']) ? $formData['date_recruited'] : null,
                    ':reason' => !empty($formData['reason']) ? $formData['reason'] : null,
                    ':basic_salary' => !empty($formData['basic_salary']) ? $formData['basic_salary'] : null,
                    ':staff_type' => $formData['staff_type'],
                    ':status' => $formData['status']
                ]);
                
                $newStaffId = $pdo->lastInsertId();
                
                // Log activity
                logActivity('CREATE', 'staff', $newStaffId, 'Registered new staff: ' . $formData['staff_id']);
                
                setFlashMessage('success', 'Staff registered successfully');
                header("Location: list.php");
                exit();
                
            } catch (PDOException $e) {
                error_log("Add Staff Error: " . $e->getMessage());
                $errors[] = 'Error registering staff. Please try again.';
            }
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Register New Staff</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escapeOutput($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" data-validate>
            <?php echo csrfField(); ?>
            
            <h4 style="margin-bottom: 20px; color: var(--primary-color);">Basic Information</h4>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Staff ID</label>
                    <input type="text" name="staff_id" class="form-control" 
                           value="<?php echo escapeOutput($formData['staff_id'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Staff Type</label>
                    <select id="staff_type" name="staff_type" class="form-control" required onchange="togglePostField()">
                        <option value="">-- Select Type --</option>
                        <option value="academic" <?php echo (isset($formData['staff_type']) && $formData['staff_type'] == 'academic') ? 'selected' : ''; ?>>Academic</option>
                        <option value="non_academic" <?php echo (isset($formData['staff_type']) && $formData['staff_type'] == 'non_academic') ? 'selected' : ''; ?>>Non-Academic</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">First Name</label>
                    <input type="text" name="first_name" class="form-control" 
                           value="<?php echo escapeOutput($formData['first_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" 
                           value="<?php echo escapeOutput($formData['middle_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Last Name</label>
                    <input type="text" name="last_name" class="form-control" 
                           value="<?php echo escapeOutput($formData['last_name'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Gender</label>
                    <select name="gender" class="form-control" required>
                        <option value="">-- Select Gender --</option>
                        <option value="Male" <?php echo (isset($formData['gender']) && $formData['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($formData['gender']) && $formData['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($formData['gender']) && $formData['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" 
                           value="<?php echo escapeOutput($formData['date_of_birth'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Marital Status</label>
                    <select name="marital_status" class="form-control">
                        <option value="">-- Select --</option>
                        <option value="Single" <?php echo (isset($formData['marital_status']) && $formData['marital_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo (isset($formData['marital_status']) && $formData['marital_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                        <option value="Divorced" <?php echo (isset($formData['marital_status']) && $formData['marital_status'] == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                        <option value="Widowed" <?php echo (isset($formData['marital_status']) && $formData['marital_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                    </select>
                </div>
            </div>
            
            <h4 style="margin: 30px 0 20px; color: var(--primary-color);">Contact Information</h4>
            
            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2"><?php echo escapeOutput($formData['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" data-phone
                           value="<?php echo escapeOutput($formData['phone'] ?? ''); ?>"
                           placeholder="08012345678">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo escapeOutput($formData['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Next of Kin</label>
                    <input type="text" name="next_of_kin" class="form-control" 
                           value="<?php echo escapeOutput($formData['next_of_kin'] ?? ''); ?>">
                </div>
            </div>
            
            <h4 style="margin: 30px 0 20px; color: var(--primary-color);">Employment Information</h4>
            
            <div class="form-row">
                <div class="form-group" id="deptFieldContainer">
                    <label class="form-label required">Department</label>
                    <select name="department_id" id="department_id" class="form-control" required>
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                    <?php echo (isset($formData['department_id']) && $formData['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo escapeOutput($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Post</label>
                    <div id="rankInputContainer">
                        <input type="text" id="rank_input" name="rank" class="form-control" 
                               value="<?php echo escapeOutput($formData['rank'] ?? ''); ?>"
                               placeholder="e.g., Lecturer I, Senior Admin Officer" required>
                    </div>
                    <div id="rankSelectContainer" style="display:none;">
                        <select id="rank_select" name="rank_disabled" class="form-control">
                            <option value="">-- Select Position --</option>
                            <?php
                            $positions = ['Registrar', 'Assistant Registrar', 'Registry staff', 'Admission Officer', 'Student Affairs Officer', 'Bursar', 'Accountant', 'Chief Accountant', 'Accounts Officer', 'Audit Officer / Internal Auditor', 'Cashier', 'Vice chancellor', 'ICT Officer', 'Librarian', 'Assistant Librarian', 'Library Officer', 'Nurse', 'Hall warden', 'Security'];
                            foreach($positions as $pos) {
                                $selected = (($formData['rank'] ?? '') == $pos) ? 'selected' : '';
                                echo "<option value=\"$pos\" $selected>$pos</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Employment Condition</label>
                    <select name="employment_condition" class="form-control">
                        <option value="Permanent" <?php echo (isset($formData['employment_condition']) && $formData['employment_condition'] == 'Permanent') ? 'selected' : ''; ?>>Permanent</option>
                        <option value="Contract" <?php echo (isset($formData['employment_condition']) && $formData['employment_condition'] == 'Contract') ? 'selected' : ''; ?>>Contract</option>
                        <option value="Temporary" <?php echo (isset($formData['employment_condition']) && $formData['employment_condition'] == 'Temporary') ? 'selected' : ''; ?>>Temporary</option>
                        <option value="Part-time" <?php echo (isset($formData['employment_condition']) && $formData['employment_condition'] == 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Date Recruited</label>
                    <input type="date" name="date_recruited" class="form-control" 
                           value="<?php echo escapeOutput($formData['date_recruited'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Basic Salary (₦)</label>
                    <input type="number" name="basic_salary" class="form-control" step="0.01"
                           value="<?php echo escapeOutput($formData['basic_salary'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Qualification</label>
                <input type="text" name="qualification" class="form-control" 
                       value="<?php echo escapeOutput($formData['qualification'] ?? ''); ?>"
                       placeholder="e.g., Ph.D Computer Science, B.Sc Mathematics">
            </div>
            
            <div class="form-group">
                <label class="form-label">Remarks/Reason</label>
                <textarea name="reason" class="form-control" rows="2"><?php echo escapeOutput($formData['reason'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?php echo (isset($formData['status']) && $formData['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (isset($formData['status']) && $formData['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Register Staff</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
            
            <script>
            function togglePostField() {
                var staffType = document.getElementById('staff_type').value;
                var deptContainer = document.getElementById('deptFieldContainer');
                var deptField = document.getElementById('department_id');
                
                var rankInputContainer = document.getElementById('rankInputContainer');
                var rankInput = document.getElementById('rank_input');
                var rankSelectContainer = document.getElementById('rankSelectContainer');
                var rankSelect = document.getElementById('rank_select');
                
                if (staffType === 'academic' || staffType === '') {
                    deptContainer.style.opacity = '1';
                    deptField.disabled = false;
                    deptField.required = true;
                    
                    rankInputContainer.style.display = 'block';
                    rankInput.name = 'rank';
                    rankInput.required = true;
                    
                    rankSelectContainer.style.display = 'none';
                    rankSelect.name = 'rank_disabled';
                    rankSelect.required = false;
                    
                } else if (staffType === 'non_academic') {
                    deptContainer.style.opacity = '0.5';
                    deptField.disabled = true;
                    deptField.required = false;
                    deptField.value = '';
                    
                    rankInputContainer.style.display = 'none';
                    rankInput.name = 'rank_disabled';
                    rankInput.required = false;
                    
                    rankSelectContainer.style.display = 'block';
                    rankSelect.name = 'rank';
                    rankSelect.required = true;
                }
            }
            document.addEventListener('DOMContentLoaded', function() { togglePostField(); });
            </script>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
