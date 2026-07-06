<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * TOGGLE USER STATUS (ACTIVATE/DEACTIVATE)
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

$userId = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

// Validate action
if (!in_array($action, ['activate', 'deactivate'])) {
    setFlashMessage('error', 'Invalid action');
    header("Location: list.php");
    exit();
}

// Get user data
$user = getUserById($userId);
if (!$user) {
    setFlashMessage('error', 'User not found');
    header("Location: list.php");
    exit();
}

// Prevent deactivating yourself
if ($userId == getCurrentUserId() && $action === 'deactivate') {
    setFlashMessage('error', 'You cannot deactivate your own account');
    header("Location: list.php");
    exit();
}

// Toggle status
$newStatus = ($action === 'activate') ? 'active' : 'inactive';

try {
    $stmt = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $newStatus,
        ':id' => $userId
    ]);
    
    // Log activity
    $actionText = ($action === 'activate') ? 'ACTIVATE_USER' : 'DEACTIVATE_USER';
    logActivity($actionText, 'users', $userId, 'User ' . $user['username'] . ' ' . $newStatus);
    
    setFlashMessage('success', 'User ' . $user['username'] . ' has been ' . $newStatus);
    
} catch (PDOException $e) {
    error_log("Toggle User Error: " . $e->getMessage());
    setFlashMessage('error', 'Error updating user status');
}

header("Location: list.php");
exit();
