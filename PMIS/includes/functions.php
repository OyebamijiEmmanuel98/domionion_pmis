<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * COMMON FUNCTIONS FILE
 * =====================================================
 * 
 * This file contains reusable helper functions used across the system.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Include database connection
require_once __DIR__ . '/../config/db.php';

/**
 * =====================================================
 * SECURITY FUNCTIONS
 * =====================================================
 */

/**
 * Sanitize user input to prevent XSS attacks
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Clean output for display (prevent XSS)
 * 
 * @param string $data Data to display
 * @return string Escaped data
 */
function escapeOutput($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token field for forms
 * 
 * @return string HTML input field with CSRF token
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * =====================================================
 * VALIDATION FUNCTIONS
 * =====================================================
 */

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Nigerian format)
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function isValidPhone($phone) {
    // Nigerian phone format: 08012345678 or +2348012345678
    $pattern = '/^(\+?234|0)[7-9][0-1][0-9]{8}$/';
    return preg_match($pattern, $phone) === 1;
}

/**
 * Validate date format
 * 
 * @param string $date Date string
 * @param string $format Expected format (default: Y-m-d)
 * @return bool True if valid, false otherwise
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate that end date is after start date
 * 
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return bool True if valid, false otherwise
 */
function isValidDateRange($startDate, $endDate) {
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    return $start !== false && $end !== false && $end >= $start;
}

/**
 * Check if string is empty
 * 
 * @param string $str String to check
 * @return bool True if empty, false otherwise
 */
function isEmpty($str) {
    return trim($str) === '';
}

/**
 * Validate numeric value
 * 
 * @param mixed $value Value to check
 * @return bool True if numeric, false otherwise
 */
function isNumeric($value) {
    return is_numeric($value);
}

/**
 * =====================================================
 * FORMATTING FUNCTIONS
 * =====================================================
 */

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Output format
 * @return string Formatted date
 */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) return 'N/A';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : 'Invalid Date';
}

/**
 * Format datetime for display
 * 
 * @param string $datetime Datetime string
 * @param string $format Output format
 * @return string Formatted datetime
 */
function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    if (empty($datetime)) return 'N/A';
    $timestamp = strtotime($datetime);
    return $timestamp ? date($format, $timestamp) : 'Invalid Date';
}

/**
 * Format currency (Naira)
 * 
 * @param float $amount Amount to format
 * @return string Formatted currency
 */
function formatCurrency($amount) {
    return '₦' . number_format($amount, 2);
}

/**
 * Format number with commas
 * 
 * @param int|float $number Number to format
 * @return string Formatted number
 */
function formatNumber($number) {
    return number_format($number);
}

/**
 * Format phone number for display
 * 
 * @param string $phone Phone number
 * @return string Formatted phone
 */
function formatPhone($phone) {
    if (strlen($phone) === 11) {
        return substr($phone, 0, 4) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
    }
    return $phone;
}

/**
 * =====================================================
 * UTILITY FUNCTIONS
 * =====================================================
 */

/**
 * Calculate age from date of birth
 * 
 * @param string $dob Date of birth
 * @return int Age in years
 */
function calculateAge($dob) {
    $birthDate = new DateTime($dob);
    $today = new DateTime();
    $diff = $today->diff($birthDate);
    return $diff->y;
}

/**
 * Calculate days between two dates
 * 
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return int Number of days
 */
function calculateDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $diff = $start->diff($end);
    return $diff->days + 1; // Include both start and end dates
}

/**
 * Generate unique ID
 * 
 * @param string $prefix ID prefix
 * @return string Unique ID
 */
function generateUniqueId($prefix = 'ID') {
    return $prefix . date('Y') . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Truncate text to specified length
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to add if truncated
 * @return string Truncated text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get initials from name
 * 
 * @param string $name Full name
 * @return string Initials
 */
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials;
}

/**
 * =====================================================
 * SESSION MESSAGE FUNCTIONS
 * =====================================================
 */

/**
 * Set flash message
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash messages
 * 
 * @return array Array of flash messages
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Display flash messages as HTML
 * 
 * @return string HTML of flash messages
 */
function displayFlashMessages() {
    $messages = getFlashMessages();
    $html = '';
    
    foreach ($messages as $msg) {
        $class = '';
        $icon = '';
        
        switch ($msg['type']) {
            case 'success':
                $class = 'alert-success';
                $icon = '<i class="fas fa-check-circle"></i>';
                break;
            case 'error':
                $class = 'alert-danger';
                $icon = '<i class="fas fa-times-circle"></i>';
                break;
            case 'warning':
                $class = 'alert-warning';
                $icon = '<i class="fas fa-exclamation-triangle"></i>';
                break;
            case 'info':
            default:
                $class = 'alert-info';
                $icon = '<i class="fas fa-info-circle"></i>';
        }
        
        $html .= "<div class='alert {$class}'><span class='alert-icon'>{$icon}</span> " . 
                 escapeOutput($msg['message']) . "</div>";
    }
    
    return $html;
}

/**
 * Check if there are flash messages
 * 
 * @return bool True if messages exist
 */
function hasFlashMessages() {
    return !empty($_SESSION['flash_messages']);
}

/**
 * =====================================================
* PAGINATION FUNCTIONS
 * =====================================================
 */

/**
 * Get paginated results
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name
 * @param string $where WHERE clause
 * @param array $params Parameters for prepared statement
 * @param int $page Current page number
 * @param int $perPage Items per page
 * @return array Pagination data
 */
function getPaginatedResults($pdo, $table, $where = '', $params = [], $page = 1, $perPage = 20) {
    // Count total records
    $countSql = "SELECT COUNT(*) as total FROM {$table}";
    if (!empty($where)) {
        $countSql .= " WHERE {$where}";
    }
    
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Calculate pagination
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => $offset
    ];
}

/**
 * Generate pagination HTML
 * 
 * @param int $currentPage Current page
 * @param int $totalPages Total pages
 * @param string $baseUrl Base URL for links
 * @return string Pagination HTML
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="page-link">&laquo; Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . '?page=1" class="page-link">1</a>';
        if ($start > 2) {
            $html .= '<span class="page-ellipsis">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? 'active' : '';
        $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="page-link ' . $active . '">' . $i . '</a>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="page-ellipsis">...</span>';
        }
        $html .= '<a href="' . $baseUrl . '?page=' . $totalPages . '" class="page-link">' . $totalPages . '</a>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="page-link">Next &raquo;</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * =====================================================
 * ACTIVITY LOGGING FUNCTIONS
 * =====================================================
 */

/**
 * Log system activity
 * 
 * @param string $action Action performed
 * @param string $tableName Table affected (optional)
 * @param int $recordId Record ID affected (optional)
 * @param string $description Description of action
 */
function logActivity($action, $tableName = null, $recordId = null, $description = '') {
    global $pdo;
    
    $userId = getCurrentUserId();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address)
            VALUES (:user_id, :action, :table_name, :record_id, :description, :ip_address)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':table_name' => $tableName,
            ':record_id' => $recordId,
            ':description' => $description,
            ':ip_address' => $ipAddress
        ]);
        
    } catch (PDOException $e) {
        error_log("Activity Log Error: " . $e->getMessage());
    }
}

/**
 * =====================================================
 * FILE UPLOAD FUNCTIONS
 * =====================================================
 */

/**
 * Handle file upload with validation
 * 
 * @param array $file $_FILES array element
 * @param string $destination Destination directory
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @return array Upload result with status and message/path
 */
function uploadFile($file, $destination, $allowedTypes = [], $maxSize = 2097152) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        return ['status' => false, 'message' => $errors[$file['error']] ?? 'Unknown upload error'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['status' => false, 'message' => 'File size exceeds limit of ' . formatBytes($maxSize)];
    }
    
    // Check file type
    if (!empty($allowedTypes)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['status' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
        }
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $destination . '/' . $filename;
    
    // Create directory if not exists
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['status' => true, 'path' => $filepath, 'filename' => $filename];
    }
    
    return ['status' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Format bytes to human readable
 * 
 * @param int $bytes Bytes to format
 * @return string Formatted size
 */
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }
    
    return round($bytes, 2) . ' ' . $units[$unitIndex];
}

/**
 * =====================================================
 * DATABASE HELPER FUNCTIONS
 * =====================================================
 */

/**
 * Get single record by ID
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name
 * @param int $id Record ID
 * @return array|bool Record data or false
 */
function getRecordById($pdo, $table, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get Record Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all records from table
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name
 * @param string $orderBy Order by clause
 * @return array Records array
 */
function getAllRecords($pdo, $table, $orderBy = 'id DESC') {
    try {
        $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY {$orderBy}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get All Records Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if record exists
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name
 * @param string $field Field to check
 * @param mixed $value Value to check
 * @return bool True if exists
 */
function recordExists($pdo, $table, $field, $value) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE {$field} = :value LIMIT 1");
        $stmt->execute([':value' => $value]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Record Exists Error: " . $e->getMessage());
        return false;
    }
}

/**
 * =====================================================
 * REDIRECT FUNCTIONS
 * =====================================================
 */

/**
 * Redirect to URL
 * 
 * @param string $url URL to redirect to
 * @param int $delay Delay in seconds (0 for immediate)
 */
function redirect($url, $delay = 0) {
    if ($delay > 0) {
        header("Refresh: {$delay}; URL={$url}");
    } else {
        header("Location: {$url}");
    }
    exit();
}

/**
 * Redirect back to previous page
 */
function redirectBack() {
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    redirect($referer);
}

/**
 * =====================================================
 * DEBUG FUNCTIONS
 * =====================================================
 */

/**
 * Debug print variable
 * 
 * @param mixed $var Variable to debug
 * @param bool $die Stop execution after print
 */
function dd($var, $die = true) {
    echo '<pre style="background: #f4f4f4; padding: 15px; border: 1px solid #ddd; overflow: auto;">';
    var_dump($var);
    echo '</pre>';
    if ($die) exit();
}

/**
 * Log debug message
 * 
 * @param string $message Message to log
 */
function debugLog($message) {
    error_log("[PMIS DEBUG] " . date('Y-m-d H:i:s') . " - " . $message);
}
