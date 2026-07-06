<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * LOGIN PAGE
 * =====================================================
 * 
 * This page handles user authentication.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Include required files
require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

// Initialize variables
$errors = [];
$username = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get and sanitize input
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validation
        if (empty($username)) {
            $errors[] = 'Staff ID or email is required';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        }
        
        // If no validation errors, attempt authentication
        if (empty($errors)) {
            $user = authenticateUser($username, $password);
            
            if ($user) {
                // Set session
                setUserSession($user);
                
                // Update last login
                updateLastLogin($user['id']);
                
                // Log the login
                logActivity('LOGIN', null, null, 'User logged into the system');
                
                // Set success message
                setFlashMessage('success', 'Welcome back, ' . $user['username'] . '!');
                
                // Redirect to intended page or dashboard
                $redirect = $_GET['redirect'] ?? '';
                if (!empty($redirect)) {
                    redirect(urldecode($redirect));
                } else {
                    redirectToDashboard();
                }
            } else {
                $errors[] = 'Invalid Staff ID or password';
                
                // Log failed attempt
                error_log("Failed login attempt for Staff ID/Email: {$username} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
            }
        }
    }
}

// Check for timeout message
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $errors[] = 'Your session has expired due to inactivity. Please log in again.';
}

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $success = 'You have been successfully logged out.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PMIS | Dominion University</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Login page specific styles */
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: #1e3a5f;
            color: #fff;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
        }
        
        .login-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .login-subtitle {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2d3748;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        
        .btn-login {
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
        }
        
        .btn-login:hover {
            background: #152a45;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
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
        
        .login-footer {
            text-align: center;
            padding: 20px;
            background: #f7fafc;
            font-size: 13px;
            color: #718096;
        }
        
        .demo-credentials {
            margin-top: 20px;
            padding: 15px;
            background: #f7fafc;
            border-radius: 6px;
            font-size: 13px;
        }
        
        .demo-credentials h4 {
            margin-bottom: 10px;
            color: #2d3748;
        }
        
        .demo-credentials table {
            width: 100%;
            font-size: 12px;
        }
        
        .demo-credentials td {
            padding: 4px 0;
        }
        
        .demo-credentials td:first-child {
            font-weight: 500;
            color: #4a5568;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <!-- Login Header -->
                <div class="login-header">
                    <div class="login-logo"><img src="assets/images/logo.png" alt="Dominion University Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
                    <h1 class="login-title">Dominion University</h1>
                    <p class="login-subtitle">Personnel Management Information System</p>
                </div>
                
                <!-- Login Body -->
                <div class="login-body">
                    <!-- Display Errors -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <span><i class="fas fa-exclamation-triangle"></i></span>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <div><?php echo escapeOutput($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Display Success -->
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            <span><i class="fas fa-check-circle"></i></span>
                            <span><?php echo escapeOutput($success); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Login Form -->
                    <form method="POST" action="" data-validate>
                        <?php echo csrfField(); ?>
                        
                        <div class="form-group">
                            <label class="form-label" for="username">Staff ID or Email</label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-control" 
                                placeholder="Enter your Staff ID or email"
                                value="<?php echo escapeOutput($username); ?>"
                                required
                                autofocus
                            >
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="Enter your password"
                                required
                            >
                        </div>
                        
                        <button type="submit" class="btn-login">
                            Sign In
                        </button>
                    </form>
                    

                    <div style="text-align: center; margin-top: 20px; font-size: 14px; color: #4a5568;">
                        New staff? <a href="signup.php" style="color: #3182ce; font-weight: 600; text-decoration: none;">Create an account</a>
                    </div>
                </div>
                
                <!-- Login Footer -->
                <div class="login-footer">
                    <p>&copy; <?php echo date('Y'); ?> Dominion University, Ibadan</p>
                    <p>Final Year Project - PMIS</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
