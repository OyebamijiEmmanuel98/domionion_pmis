<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * CHANGE PASSWORD
 * =====================================================
 * 
 * @author Final Year Project
 * @version 1.0
 */

require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/role_check.php';

// Require login
requireLogin();

$pageTitle = 'Change Password';
$breadcrumbs = ['Change Password' => null];

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }
        
        // Verify current password
        if (empty($errors)) {
            $userId = getCurrentUserId();
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                $errors[] = 'Current password is incorrect';
            }
        }
        
        // Update password
        if (empty($errors)) {
            try {
                $newHash = hashPassword($newPassword);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$newHash, $userId]);
                
                // Log activity
                logActivity('PASSWORD_CHANGE', 'users', $userId, 'User changed password');
                
                $success = 'Password changed successfully';
                
            } catch (PDOException $e) {
                error_log("Change Password Error: " . $e->getMessage());
                $errors[] = 'Error changing password. Please try again.';
            }
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-lock"></i> Change Password</h3>
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
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> <?php echo escapeOutput($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" data-validate>
            <?php echo csrfField(); ?>
            
            <div class="form-group">
                <label class="form-label required">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label required">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
                <small class="form-hint">Minimum 6 characters</small>
            </div>
            
            <div class="form-group">
                <label class="form-label required">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Change Password</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
