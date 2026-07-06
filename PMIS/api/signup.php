<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * STAFF SIGN UP / REGISTRATION PAGE
 * =====================================================
 * 
 * Public registration page for new staff members.
 * Creates both a staff record and a user account.
 * 
 * @author Final Year Project
 * @version 1.0
 */

require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

$errors = [];
$success = false;
$formData = [];

// Get departments for dropdown
$deptStmt = $pdo->query("SELECT id, department_name FROM departments ORDER BY department_name");
$departments = $deptStmt->fetchAll();

// Get the staff role ID
$staffRoleStmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = 'staff'");
$staffRoleStmt->execute();
$staffRole = $staffRoleStmt->fetch();
$staffRoleId = $staffRole ? $staffRole['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get and sanitize input
        $formData = [
            // Account info
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            // Personal info
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'middle_name' => sanitizeInput($_POST['middle_name'] ?? ''),
            'gender' => $_POST['gender'] ?? '',
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'marital_status' => $_POST['marital_status'] ?? '',
            'height' => sanitizeInput($_POST['height'] ?? ''),
            // Contact info
            'address' => sanitizeInput($_POST['address'] ?? ''),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'next_of_kin' => sanitizeInput($_POST['next_of_kin'] ?? ''),
            // Employment info
            'staff_type' => $_POST['staff_type'] ?? '',
            'post' => sanitizeInput($_POST['post'] ?? ''),
            'department_id' => $_POST['department_id'] ?? '',
            'rank' => sanitizeInput($_POST['post'] ?? ''),
            'qualification' => sanitizeInput($_POST['qualification'] ?? ''),
            'employment_condition' => $_POST['employment_condition'] ?? 'Permanent',
            'date_recruited' => $_POST['date_recruited'] ?? '',
            'basic_salary' => $_POST['basic_salary'] ?? '',
        ];

        // === VALIDATION ===
        
        // Account validation
        if (empty($formData['email'])) {
            $errors[] = 'Email is required';
        } elseif (!isValidEmail($formData['email'])) {
            $errors[] = 'Invalid email address';
        } elseif (emailExists($formData['email'])) {
            $errors[] = 'Email already registered';
        }
        
        if (empty($formData['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($formData['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if ($formData['password'] !== $formData['confirm_password']) {
            $errors[] = 'Passwords do not match';
        }
        
        // Personal info validation
        if (empty($formData['first_name'])) {
            $errors[] = 'First name is required';
        }
        
        if (empty($formData['last_name'])) {
            $errors[] = 'Last name is required';
        }
        
        if (empty($formData['gender'])) {
            $errors[] = 'Gender is required';
        }
        
        // Employment validation
        if (empty($formData['staff_type'])) {
            $errors[] = 'Staff type is required';
        }
        
        if (empty($formData['department_id']) && $formData['staff_type'] !== 'non_academic') {
            $errors[] = 'Department is required';
        }
        
        if (empty($formData['post'])) {
            $errors[] = 'Post is required';
        }
        
        if (!empty($formData['basic_salary']) && !is_numeric($formData['basic_salary'])) {
            $errors[] = 'Basic salary must be numeric';
        }
        
        // If no errors, create records
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Generate a staff ID
                $prefix = strtoupper(substr($formData['staff_type'] == 'academic' ? 'AC' : 'NA', 0, 2));
                $countStmt = $pdo->query("SELECT COUNT(*) as total FROM staff");
                $staffCount = $countStmt->fetch()['total'] + 1;
                $generatedStaffId = 'DU' . str_pad($staffCount, 3, '0', STR_PAD_LEFT);
                
                // Check if generated staff ID exists, increment if needed
                while (recordExists($pdo, 'staff', 'staff_id', $generatedStaffId)) {
                    $staffCount++;
                    $generatedStaffId = 'DU' . str_pad($staffCount, 3, '0', STR_PAD_LEFT);
                }
                
                // 1. Insert staff record
                $staffStmt = $pdo->prepare("
                    INSERT INTO staff (
                        staff_id, first_name, last_name, middle_name, gender, date_of_birth,
                        marital_status, address, phone, email, next_of_kin, height,
                        qualification, department_id, rank, post, employment_condition,
                        date_recruited, basic_salary, staff_type, status
                    ) VALUES (
                        :staff_id, :first_name, :last_name, :middle_name, :gender, :date_of_birth,
                        :marital_status, :address, :phone, :email, :next_of_kin, :height,
                        :qualification, :department_id, :rank, :post, :employment_condition,
                        :date_recruited, :basic_salary, :staff_type, 'active'
                    )
                ");
                
                $staffStmt->execute([
                    ':staff_id' => $generatedStaffId,
                    ':first_name' => $formData['first_name'],
                    ':last_name' => $formData['last_name'],
                    ':middle_name' => !empty($formData['middle_name']) ? $formData['middle_name'] : null,
                    ':gender' => $formData['gender'],
                    ':date_of_birth' => !empty($formData['date_of_birth']) ? $formData['date_of_birth'] : null,
                    ':marital_status' => !empty($formData['marital_status']) ? $formData['marital_status'] : null,
                    ':address' => !empty($formData['address']) ? $formData['address'] : null,
                    ':phone' => !empty($formData['phone']) ? $formData['phone'] : null,
                    ':email' => $formData['email'],
                    ':next_of_kin' => !empty($formData['next_of_kin']) ? $formData['next_of_kin'] : null,
                    ':height' => !empty($formData['height']) ? $formData['height'] : null,
                    ':qualification' => !empty($formData['qualification']) ? $formData['qualification'] : null,
                    ':department_id' => !empty($formData['department_id']) ? $formData['department_id'] : null,
                    ':rank' => $formData['rank'],
                    ':post' => !empty($formData['post']) ? $formData['post'] : null,
                    ':employment_condition' => $formData['employment_condition'],
                    ':date_recruited' => !empty($formData['date_recruited']) ? $formData['date_recruited'] : date('Y-m-d'),
                    ':basic_salary' => !empty($formData['basic_salary']) ? $formData['basic_salary'] : null,
                    ':staff_type' => $formData['staff_type'],
                ]);
                
                $newStaffDbId = $pdo->lastInsertId();
                
                // 2. Create user account linked to staff
                $passwordHash = hashPassword($formData['password']);
                
                $userStmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, role_id, staff_id, status)
                    VALUES (:username, :email, :password_hash, :role_id, :staff_id, 'active')
                ");
                
                $userStmt->execute([
                    ':username' => $generatedStaffId,
                    ':email' => $formData['email'],
                    ':password_hash' => $passwordHash,
                    ':role_id' => $staffRoleId,
                    ':staff_id' => $newStaffDbId,
                ]);
                
                $pdo->commit();
                
                $success = true;
                $formData = []; // Clear form
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Sign Up Error: " . $e->getMessage());
                $errors[] = 'An error occurred during registration. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - PMIS | Dominion University</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .signup-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            padding: 30px 20px;
        }
        
        .signup-container {
            width: 100%;
            max-width: 780px;
        }
        
        .signup-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .signup-header {
            background: #1e3a5f;
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        
        .signup-logo {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
        }
        
        .signup-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .signup-subtitle {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .signup-body {
            padding: 35px 30px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e3a5f;
            margin: 25px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-title:first-child {
            margin-top: 0;
        }
        
        .section-title i {
            color: #3182ce;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 0;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 13px;
            color: #2d3748;
        }
        
        .form-label .required-star {
            color: #e53e3e;
            margin-left: 2px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        
        select.form-control {
            appearance: auto;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 60px;
        }
        
        .form-hint {
            display: block;
            font-size: 12px;
            color: #718096;
            margin-top: 4px;
        }
        
        .btn-signup {
            width: 100%;
            padding: 14px;
            background: #1e3a5f;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        
        .btn-signup:hover {
            background: #152a45;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .alert-danger {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .alert ul {
            margin: 5px 0 0 15px;
            padding: 0;
        }
        
        .alert ul li {
            margin-bottom: 3px;
        }
        
        .signup-footer {
            text-align: center;
            padding: 20px;
            background: #f7fafc;
            font-size: 13px;
            color: #718096;
        }
        
        .signup-footer a {
            color: #3182ce;
            text-decoration: none;
            font-weight: 500;
        }
        
        .signup-footer a:hover {
            text-decoration: underline;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #4a5568;
        }
        
        .login-link a {
            color: #3182ce;
            font-weight: 600;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .success-box {
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #c6f6d5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: #22543d;
        }
        
        .success-box h2 {
            color: #22543d;
            margin-bottom: 10px;
        }
        
        .success-box p {
            color: #4a5568;
            margin-bottom: 20px;
        }
        
        .btn-login-link {
            display: inline-block;
            padding: 12px 30px;
            background: #1e3a5f;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        
        .btn-login-link:hover {
            background: #152a45;
        }
        
        @media (max-width: 640px) {
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
            }
            .signup-body {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="signup-page">
        <div class="signup-container">
            <div class="signup-card">
                <!-- Header -->
                <div class="signup-header">
                    <div class="signup-logo"><img src="assets/images/logo.png" alt="Dominion University Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
                    <h1 class="signup-title">Staff Registration</h1>
                    <p class="signup-subtitle">Dominion University — Personnel Management Information System</p>
                </div>
                
                <!-- Body -->
                <div class="signup-body">
                    <?php if ($success): ?>
                        <!-- Success State -->
                        <div class="success-box">
                            <div class="success-icon"><i class="fas fa-check"></i></div>
                            <h2>Registration Successful!</h2>
                            <p>Your staff account has been created. You can now log in with your Staff ID and password.</p>
                            <a href="login.php" class="btn-login-link"><i class="fas fa-sign-in-alt"></i> Go to Login</a>
                        </div>
                    <?php else: ?>
                        <!-- Errors -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <span><i class="fas fa-exclamation-triangle"></i></span>
                                <div>
                                    <strong>Please fix the following:</strong>
                                    <ul>
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo escapeOutput($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <?php echo csrfField(); ?>
                            
                            <!-- SECTION 1: Account Information -->
                            <div class="section-title"><i class="fas fa-user-lock"></i> Account Information</div>
                            
                            <div class="form-row">
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label class="form-label">Email Address <span class="required-star">*</span></label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo escapeOutput($formData['email'] ?? ''); ?>" 
                                           placeholder="your.email@dominion.edu.ng" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Password <span class="required-star">*</span></label>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Minimum 6 characters" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Confirm Password <span class="required-star">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" 
                                           placeholder="Re-enter password" required>
                                </div>
                            </div>
                            
                            <!-- SECTION 2: Personal Information -->
                            <div class="section-title"><i class="fas fa-id-card"></i> Personal Information</div>
                            
                            <div class="form-row-3">
                                <div class="form-group">
                                    <label class="form-label">First Name <span class="required-star">*</span></label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo escapeOutput($formData['first_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control" 
                                           value="<?php echo escapeOutput($formData['middle_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Last Name <span class="required-star">*</span></label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo escapeOutput($formData['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row-3">
                                <div class="form-group">
                                    <label class="form-label">Gender <span class="required-star">*</span></label>
                                    <select name="gender" class="form-control" required>
                                        <option value="">-- Select --</option>
                                        <option value="Male" <?php echo (($formData['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (($formData['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (($formData['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
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
                                        <option value="Single" <?php echo (($formData['marital_status'] ?? '') == 'Single') ? 'selected' : ''; ?>>Single</option>
                                        <option value="Married" <?php echo (($formData['marital_status'] ?? '') == 'Married') ? 'selected' : ''; ?>>Married</option>
                                        <option value="Divorced" <?php echo (($formData['marital_status'] ?? '') == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                                        <option value="Widowed" <?php echo (($formData['marital_status'] ?? '') == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Height</label>
                                    <input type="text" name="height" class="form-control" 
                                           value="<?php echo escapeOutput($formData['height'] ?? ''); ?>" 
                                           placeholder="e.g., 5'8&quot;">
                                </div>
                            </div>
                            
                            <!-- SECTION 3: Contact Information -->
                            <div class="section-title"><i class="fas fa-phone-alt"></i> Contact Information</div>
                            
                            <div class="form-group">
                                <label class="form-label">Residential Address</label>
                                <textarea name="address" class="form-control" rows="2" 
                                          placeholder="Enter your full address"><?php echo escapeOutput($formData['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo escapeOutput($formData['phone'] ?? ''); ?>" 
                                           placeholder="08012345678">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Next of Kin</label>
                                    <input type="text" name="next_of_kin" class="form-control" 
                                           value="<?php echo escapeOutput($formData['next_of_kin'] ?? ''); ?>" 
                                           placeholder="Full name of next of kin">
                                </div>
                            </div>
                            
                            <!-- SECTION 4: Employment Information -->
                            <div class="section-title"><i class="fas fa-briefcase"></i> Employment Information</div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Staff Type <span class="required-star">*</span></label>
                                    <select name="staff_type" id="staff_type" class="form-control" required onchange="togglePostField()">
                                        <option value="">-- Select Type --</option>
                                        <option value="academic" <?php echo (($formData['staff_type'] ?? '') == 'academic') ? 'selected' : ''; ?>>Academic</option>
                                        <option value="non_academic" <?php echo (($formData['staff_type'] ?? '') == 'non_academic') ? 'selected' : ''; ?>>Non-Academic</option>
                                    </select>
                                </div>
                                
                                <div class="form-group" id="deptFieldContainer">
                                    <label class="form-label">Department <span class="required-star" id="deptRequiredStar">*</span></label>
                                    <select name="department_id" id="department_id" class="form-control" required>
                                        <option value="">-- Select Department --</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" 
                                                    <?php echo (($formData['department_id'] ?? '') == $dept['id']) ? 'selected' : ''; ?>>
                                                <?php echo escapeOutput($dept['department_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row" id="postFieldRow" style="display: <?php echo !empty($formData['staff_type']) ? 'grid' : 'none'; ?>;">
                                <div class="form-group">
                                    <label class="form-label" id="postLabel">Post <span class="required-star">*</span></label>
                                    <div id="rankInputContainer">
                                        <input type="text" id="post_field" name="post" class="form-control" 
                                               value="<?php echo escapeOutput($formData['post'] ?? ''); ?>" 
                                               placeholder="Select staff type first" required>
                                    </div>
                                    <div id="rankSelectContainer" style="display:none;">
                                        <select id="post_select" name="post_disabled" class="form-control">
                                            <option value="">-- Select Position --</option>
                                            <?php
                                            $positions = ['Registrar', 'Assistant Registrar', 'Registry staff', 'Admission Officer', 'Student Affairs Officer', 'Bursar', 'Accountant', 'Chief Accountant', 'Accounts Officer', 'Audit Officer / Internal Auditor', 'Cashier', 'Vice chancellor', 'ICT Officer', 'Librarian', 'Assistant Librarian', 'Library Officer', 'Nurse', 'Hall warden', 'Security'];
                                            foreach($positions as $pos) {
                                                $selected = (($formData['post'] ?? '') == $pos) ? 'selected' : '';
                                                echo "<option value=\"$pos\" $selected>$pos</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <span class="form-hint" id="postHint">Specify your post/designation</span>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Qualification</label>
                                    <input type="text" name="qualification" class="form-control" 
                                           value="<?php echo escapeOutput($formData['qualification'] ?? ''); ?>" 
                                           placeholder="e.g., Ph.D Computer Science">
                                </div>
                            </div>
                            
                            <div class="form-row-3">
                                <div class="form-group">
                                    <label class="form-label">Employment Condition</label>
                                    <select name="employment_condition" class="form-control">
                                        <option value="Permanent" <?php echo (($formData['employment_condition'] ?? '') == 'Permanent') ? 'selected' : ''; ?>>Permanent</option>
                                        <option value="Contract" <?php echo (($formData['employment_condition'] ?? '') == 'Contract') ? 'selected' : ''; ?>>Contract</option>
                                        <option value="Temporary" <?php echo (($formData['employment_condition'] ?? '') == 'Temporary') ? 'selected' : ''; ?>>Temporary</option>
                                        <option value="Part-time" <?php echo (($formData['employment_condition'] ?? '') == 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Date of Recruitment</label>
                                    <input type="date" name="date_recruited" class="form-control" 
                                           value="<?php echo escapeOutput($formData['date_recruited'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Basic Salary (₦)</label>
                                    <input type="number" name="basic_salary" class="form-control" step="0.01" 
                                           value="<?php echo escapeOutput($formData['basic_salary'] ?? ''); ?>"
                                           placeholder="0.00">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-signup">
                                <i class="fas fa-user-plus"></i> Create My Account
                            </button>
                            
                            <script>
                            function togglePostField() {
                                var staffType = document.getElementById('staff_type').value;
                                var postRow = document.getElementById('postFieldRow');
                                var postField = document.getElementById('post_field');
                                var postHint = document.getElementById('postHint');
                                
                                var deptContainer = document.getElementById('deptFieldContainer');
                                var deptField = document.getElementById('department_id');
                                var deptStar = document.getElementById('deptRequiredStar');
                                
                                var rankInputContainer = document.getElementById('rankInputContainer');
                                var rankSelectContainer = document.getElementById('rankSelectContainer');
                                var rankSelect = document.getElementById('post_select');
                                
                                if (staffType === 'academic') {
                                    postRow.style.display = 'grid';
                                    postField.placeholder = 'e.g. Lecturer, Faculty Officer, Lab Technologist';
                                    postHint.textContent = 'Specify your academic post/designation';
                                    
                                    deptContainer.style.opacity = '1';
                                    deptField.disabled = false;
                                    deptField.required = true;
                                    if(deptStar) deptStar.style.display = 'inline';
                                    
                                    rankInputContainer.style.display = 'block';
                                    postField.name = 'post';
                                    postField.required = true;
                                    
                                    rankSelectContainer.style.display = 'none';
                                    rankSelect.name = 'post_disabled';
                                    rankSelect.required = false;
                                    
                                } else if (staffType === 'non_academic') {
                                    postRow.style.display = 'grid';
                                    postHint.textContent = 'Specify your non-academic post/designation';
                                    
                                    deptContainer.style.opacity = '0.5';
                                    deptField.disabled = true;
                                    deptField.required = false;
                                    if(deptStar) deptStar.style.display = 'none';
                                    
                                    rankInputContainer.style.display = 'none';
                                    postField.name = 'post_disabled';
                                    postField.required = false;
                                    
                                    rankSelectContainer.style.display = 'block';
                                    rankSelect.name = 'post';
                                    rankSelect.required = true;
                                    
                                } else {
                                    postRow.style.display = 'none';
                                    postField.value = '';
                                    
                                    deptContainer.style.opacity = '1';
                                    deptField.disabled = false;
                                    deptField.required = true;
                                    if(deptStar) deptStar.style.display = 'inline';
                                    
                                    rankInputContainer.style.display = 'block';
                                    postField.name = 'post';
                                    postField.required = true;
                                    
                                    rankSelectContainer.style.display = 'none';
                                    rankSelect.name = 'post_disabled';
                                    rankSelect.required = false;
                                }
                            }
                            // Run on page load if staff_type is already selected
                            document.addEventListener('DOMContentLoaded', function() { togglePostField(); });
                            </script>
                            
                            <div class="login-link">
                                Already have an account? <a href="login.php">Sign in here</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                
                <!-- Footer -->
                <div class="signup-footer">
                    <p>&copy; <?php echo date('Y'); ?> Dominion University, Ibadan</p>
                    <p>Final Year Project - PMIS</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
