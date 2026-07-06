<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * DATABASE CONFIGURATION FILE
 * =====================================================
 * 
 * This file contains the database connection settings.
 * Modify these settings according to your local environment.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Database configuration constants
// Change these values according to your hosting setup

// =====================================================
// INFINITYFREE HOSTING - Replace with YOUR values from:
// InfinityFree Control Panel > MySQL Databases
// =====================================================
define('DB_HOST', 'sql103.infinityfree.com');                  // XAMPP local MySQL
define('DB_USER', 'if0_42349024');                       // Default XAMPP user
define('DB_PASS', 'OYEBAMIJI001');                           // Default XAMPP password (empty)
define('DB_NAME', 'if0_42349024_pmis_dominion');               // Local database name

// Create database connection using PDO (PHP Data Objects)
// PDO is preferred over mysqli for better security and flexibility

try {
    // Create PDO instance with error handling
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Fetch associative arrays
            PDO::ATTR_EMULATE_PREPARES => false,                // Use real prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
} catch (PDOException $e) {
    // Log error and display user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    die("<div style='padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px;'>
        <h3>Database Connection Failed</h3>
        <p>Unable to connect to the database. Please check:</p>
        <ul>
            <li>MySQL server is running</li>
            <li>Database 'pmis_dominion' exists</li>
            <li>Username and password are correct</li>
        </ul>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
    </div>");
}

/**
 * Helper function to get PDO connection
 * Use this function when you need to pass the connection variable
 * 
 * @return PDO The database connection object
 */
function getDBConnection() {
    global $pdo;
    return $pdo;
}

/**
 * Helper function to close database connection
 * Good practice to call this when done with database operations
 */
function closeDBConnection() {
    global $pdo;
    $pdo = null;
}

/**
 * Helper function to check if database is connected
 * 
 * @return bool True if connected, false otherwise
 */
function isDBConnected() {
    global $pdo;
    try {
        $pdo->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
