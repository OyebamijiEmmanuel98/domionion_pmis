<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * ACCESS DENIED PAGE
 * =====================================================
 * 
 * This page is shown when users try to access unauthorized areas.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Include required files
require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Page title
$pageTitle = 'Access Denied';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - PMIS | Dominion University</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .access-denied-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f7fafc;
            padding: 20px;
        }
        
        .access-denied-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .access-icon {
            width: 100px;
            height: 100px;
            background: #fed7d7;
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
        }
        
        .access-title {
            font-size: 28px;
            font-weight: 700;
            color: #e53e3e;
            margin-bottom: 15px;
        }
        
        .access-message {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .access-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #1e3a5f;
            color: #fff;
            border: none;
        }
        
        .btn-primary:hover {
            background: #152a45;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #e2e8f0;
            color: #4a5568;
        }
        
        .btn-outline:hover {
            background: #f7fafc;
        }
    </style>
</head>
<body>
    <div class="access-denied-page">
        <div class="access-denied-card">
            <div class="access-icon"><i class="fas fa-ban"></i></div>
            <h1 class="access-title">Access Denied</h1>
            <p class="access-message">
                Sorry, you do not have permission to access this page. 
                Please contact your system administrator if you believe this is an error.
            </p>
            <div class="access-actions">
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>
