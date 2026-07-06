<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * ADD USER
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

// Require Admin access
requireAdmin();

$pageTitle = 'Add User';
$breadcrumbs = ['Users' => 'modules/users/list.php', 'Add User' => null];

$errors = [];
$formData = [
    'username' => '',
    'email' => '',
    'role_id' => '',
    'staff_id' => '',
    'password' => '',
    'confirm_password' => ''
];

// Get roles
$rolesStmt = $pdo->query("SELECT id, role_name FROM roles ORDER BY id");
$roles = $rolesStmt->fetchAll();

// Get staff for linking
$staffStmt = $pdo->query("
    SELECT s.id, s.staff_id, CONCAT(s.first_name, ' ', s.last_name) as name, d.department_name
    FROM staff s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.status = 'active'
    AND s.id NOT IN (SELECT staff_id FROM users WHERE staff_id IS NOT NULL)
    ORDER BY s.first_name, s.last_name
");
$staffList = $staffStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get input
        $formData['username'] = sanitizeInput($_POST['username'] ?? '');
        $formData['email'] = sanitizeInput($_POST['email'] ?? '');
        $formData['role_id'] = $_POST['role_id'] ?? '';
        $formData['staff_id'] = $_POST['staff_id'] ?? '';
        $formData['password'] = $_POST['password'] ?? '';
        $formData['confirm_password'] = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($formData['username'])) {
            $errors[] = 'Username is required';
        } elseif (usernameExists($formData['username'])) {
            $errors[] = 'Username already exists';
        }
        
        if (!empty($formData['email']) && !isValidEmail($formData['email'])) {
            $errors[] = 'Invalid email address';
        } elseif (!empty($formData['email']) && emailExists($formData['email'])) {
            $errors[] = 'Email already exists';
        }
        
        if (empty($formData['role_id'])) {
            $errors[] = 'Role is required';
        }
        
        if (empty($formData['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($formData['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if ($formData['password'] !== $formData['confirm_password']) {
            $errors[] = 'Passwords do not match';
        }
        
        // If no errors, insert
        if (empty($errors)) {
            try {
                $passwordHash = hashPassword($formData['password']);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, role_id, staff_id, status)
                    VALUES (:username, :email, :password_hash, :role_id, :staff_id, 'active')
                ");
                
                $stmt->execute([
                    ':username' => $formData['username'],
                    ':email' => !empty($formData['email']) ? $formData['email'] : null,
                    ':password_hash' => $passwordHash,
                    ':role_id' => $formData['role_id'],
                    ':staff_id' => !empty($formData['staff_id']) ? $formData['staff_id'] : null
                ]);
                
                $newUserId = $pdo->lastInsertId();
                
                // Log activity
                logActivity('CREATE', 'users', $newUserId, 'Created new user: ' . $formData['username']);
                
                setFlashMessage('success', 'User created successfully');
                header("Location: list.php");
                exit();
                
            } catch (PDOException $e) {
                error_log("Add User Error: " . $e->getMessage());
                $errors[] = 'Error creating user. Please try again.';
            }
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Create New User Account</h3>
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
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Username</label>
                    <input type="text" name="username" class="form-control" 
                           value="<?php echo escapeOutput($formData['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo escapeOutput($formData['email']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Role</label>
                    <select name="role_id" class="form-control" required>
                        <option value="">-- Select Role --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>" 
                                    <?php echo ($formData['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                <?php echo getRoleDisplayName($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Link to Staff (Optional)</label>
                    <select name="staff_id" class="form-control">
                        <option value="">-- Not Linked --</option>
                        <?php foreach ($staffList as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>" 
                                    <?php echo ($formData['staff_id'] == $staff['id']) ? 'selected' : ''; ?>>
                                <?php echo escapeOutput($staff['name'] . ' (' . $staff['staff_id'] . ') - ' . $staff['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-hint">Link this user account to a staff record</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Password</label>
                    <input type="password" name="password" class="form-control" required>
                    <small class="form-hint">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create User</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
