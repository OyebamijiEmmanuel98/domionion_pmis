<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * RESET USER PASSWORD
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

$pageTitle = 'Reset Password';
$breadcrumbs = ['Users' => 'modules/users/list.php', 'Reset Password' => null];

$errors = [];
$userId = $_GET['id'] ?? 0;

// Get user data
$user = getUserById($userId);
if (!$user) {
    setFlashMessage('error', 'User not found');
    header("Location: list.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        // If no errors, update password
        if (empty($errors)) {
            try {
                $passwordHash = hashPassword($newPassword);
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET password_hash = :password_hash
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    ':password_hash' => $passwordHash,
                    ':id' => $userId
                ]);
                
                // Log activity
                logActivity('RESET_PASSWORD', 'users', $userId, 'Password reset for user: ' . $user['username']);
                
                setFlashMessage('success', 'Password reset successfully for ' . $user['username']);
                header("Location: list.php");
                exit();
                
            } catch (PDOException $e) {
                error_log("Reset Password Error: " . $e->getMessage());
                $errors[] = 'Error resetting password. Please try again.';
            }
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Reset Password: <?php echo escapeOutput($user['username']); ?></h3>
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
        
        <div class="alert alert-info">
            <span class="alert-icon"><i class="fas fa-info-circle"></i></span>
            You are resetting the password for user <strong><?php echo escapeOutput($user['username']); ?></strong> 
            (<?php echo escapeOutput($user['email'] ?? 'No email'); ?>).
        </div>
        
        <form method="POST" action="" data-validate>
            <?php echo csrfField(); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                    <small class="form-hint">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Reset Password</button>
                <a href="list.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
