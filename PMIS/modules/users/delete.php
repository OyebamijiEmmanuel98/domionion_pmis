<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * DELETE USER
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

// Get user data
$user = getUserById($userId);
if (!$user) {
    setFlashMessage('error', 'User not found');
    header("Location: list.php");
    exit();
}

// Prevent deleting yourself
if ($userId == getCurrentUserId()) {
    setFlashMessage('error', 'You cannot delete your own account');
    header("Location: list.php");
    exit();
}

try {
    // Delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    
    // Log activity
    logActivity('DELETE', 'users', $userId, 'Deleted user: ' . $user['username']);
    
    setFlashMessage('success', 'User ' . $user['username'] . ' has been deleted');
    
} catch (PDOException $e) {
    error_log("Delete User Error: " . $e->getMessage());
    setFlashMessage('error', 'Error deleting user. The user may have related records.');
}

header("Location: list.php");
exit();
