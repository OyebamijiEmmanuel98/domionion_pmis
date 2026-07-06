<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * AUTHENTICATION HELPER FILE
 * =====================================================
 * 
 * This file contains authentication-related helper functions.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Include database connection
require_once __DIR__ . '/../config/db.php';

// Include session management
require_once __DIR__ . '/session.php';

/**
 * Authenticate user with username and password
 * 
 * @param string $username The username or email
 * @param string $password The plain text password
 * @return array|bool User data array on success, false on failure
 */
function authenticateUser($username, $password) {
    global $pdo;
    
    try {
        // Prepare statement to prevent SQL injection
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name, s.department_id 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN staff s ON u.staff_id = s.id
            WHERE (u.username = :username OR u.email = :email)
            AND u.status = 'active'
            LIMIT 1
        ");
        
        $stmt->execute([
            ':username' => $username,
            ':email' => $username
        ]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password using password_verify()
        if ($user && password_verify($password, $user['password_hash'])) {
            // Password is correct, return user data
            return $user;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Authentication Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has specific role
 * 
 * @param string|array $allowedRoles Single role or array of allowed roles
 * @return bool True if user has allowed role, false otherwise
 */
function hasRole($allowedRoles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $currentRole = getCurrentUserRole();
    
    if (is_array($allowedRoles)) {
        return in_array($currentRole, $allowedRoles);
    }
    
    return $currentRole === $allowedRoles;
}

/**
 * Require specific role to access page
 * Redirects to access denied page if role doesn't match
 * 
 * @param string|array $allowedRoles Single role or array of allowed roles
 */
function requireRole($allowedRoles) {
    requireLogin();
    
    if (!hasRole($allowedRoles)) {
        header("Location: /PMIS/access_denied.php");
        exit();
    }
}

/**
 * Check if current user is admin
 * 
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if current user is HR
 * 
 * @return bool True if HR, false otherwise
 */
function isHR() {
    return hasRole('hr');
}

/**
 * Check if current user is HOD
 * 
 * @return bool True if HOD, false otherwise
 */
function isHOD() {
    return hasRole('hod');
}

/**
 * Check if current user is staff
 * 
 * @return bool True if staff, false otherwise
 */
function isStaff() {
    return hasRole('staff');
}

/**
 * Hash password securely
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    // Use PASSWORD_DEFAULT algorithm (currently bcrypt)
    // Automatically handles salt generation
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * 
 * @param string $password Plain text password
 * @param string $hash Stored password hash
 * @return bool True if password matches, false otherwise
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 * Useful when upgrading password hashing algorithm
 * 
 * @param string $hash Stored password hash
 * @return bool True if rehash needed, false otherwise
 */
function passwordNeedsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_DEFAULT);
}

/**
 * Generate secure random token
 * Useful for password reset, API tokens, etc.
 * 
 * @param int $length Token length in bytes (default: 32)
 * @return string Hex-encoded token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Log user login attempt
 * 
 * @param int|null $userId User ID if known, null for failed attempts
 * @param bool $success Whether login was successful
 * @param string $username Username attempted
 */
function logLoginAttempt($userId, $success, $username) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_logs (user_id, ip_address, user_agent)
            VALUES (:user_id, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
        
    } catch (PDOException $e) {
        error_log("Login Log Error: " . $e->getMessage());
    }
}

/**
 * Update last login timestamp
 * 
 * @param int $userId User ID
 */
function updateLastLogin($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET last_login = NOW() 
            WHERE id = :user_id
        ");
        
        $stmt->execute([':user_id' => $userId]);
        
    } catch (PDOException $e) {
        error_log("Update Last Login Error: " . $e->getMessage());
    }
}

/**
 * Logout user and record logout time
 */
function logoutUser() {
    global $pdo;
    
    $userId = getCurrentUserId();
    
    // Update logout time in login_logs
    if ($userId) {
        try {
            $stmt = $pdo->prepare("
                UPDATE login_logs 
                SET logout_time = NOW() 
                WHERE user_id = :user_id 
                AND logout_time IS NULL 
                ORDER BY login_time DESC 
                LIMIT 1
            ");
            
            $stmt->execute([':user_id' => $userId]);
            
        } catch (PDOException $e) {
            error_log("Logout Log Error: " . $e->getMessage());
        }
    }
    
    // Clear session
    clearSession();
}

/**
 * Get user details by ID
 * 
 * @param int $userId User ID
 * @return array|bool User data or false if not found
 */
function getUserById($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name, s.staff_id as staff_code
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN staff s ON u.staff_id = s.id
            WHERE u.id = :user_id
            LIMIT 1
        ");
        
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Get User Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if username exists
 * 
 * @param string $username Username to check
 * @param int|null $excludeId User ID to exclude (for updates)
 * @return bool True if exists, false otherwise
 */
function usernameExists($username, $excludeId = null) {
    global $pdo;
    
    try {
        $sql = "SELECT id FROM users WHERE username = :username";
        $params = [':username' => $username];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
        
    } catch (PDOException $e) {
        error_log("Username Check Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if email exists
 * 
 * @param string $email Email to check
 * @param int|null $excludeId User ID to exclude (for updates)
 * @return bool True if exists, false otherwise
 */
function emailExists($email, $excludeId = null) {
    global $pdo;
    
    try {
        $sql = "SELECT id FROM users WHERE email = :email";
        $params = [':email' => $email];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
        
    } catch (PDOException $e) {
        error_log("Email Check Error: " . $e->getMessage());
        return false;
    }
}
