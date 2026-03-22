<?php
/**
 * HAUccountant Database Configuration
 * Enhanced with better error handling and utility functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$db_config = [
    'host' => 'localhost',
    'name' => 'hauaccountant',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]
];

try {
    // Create PDO connection with charset
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], $db_config['options']);
    
    // Set timezone for MySQL session
    $pdo->exec("SET time_zone = '+08:00'"); // Philippine Time
    
} catch(PDOException $e) {
    // Log error securely
    error_log("Database Connection Failed: " . $e->getMessage());
    die("We're experiencing technical difficulties. Please try again later.");
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirect to login if not authenticated
 */
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

/**
 * Check if user has admin role
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect if not admin
 */
function redirectIfNotAdmin() {
    if (!isAdmin()) {
        $_SESSION['error'] = "You don't have permission to access that page.";
        header('Location: index.php');
        exit();
    }
}

/**
 * Enhanced activity logging with more details
 * @param PDO $pdo
 * @param int $user_id
 * @param string $action
 * @param string $affected_record
 * @param string $details
 */
function logActivity($pdo, $user_id, $action, $affected_record, $details) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id, action, affected_record, details, 
                ip_address, user_agent, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $action, $affected_record, $details, $ip, $user_agent]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Format currency in Philippine Peso
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Generate CSRF token for forms
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Get user by ID
 * @param PDO $pdo
 * @param int $user_id
 * @return array|false
 */
function getUserById($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id, business_name, owner_name, email, role, status, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Get dashboard statistics
 * @param PDO $pdo
 * @param int $user_id
 * @return array
 */
function getDashboardStats($pdo, $user_id) {
    $stats = [];
    
    // Today's sales
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
    $stmt->execute();
    $stats['today_sales'] = $stmt->fetch()['total'];
    
    // This week's sales
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE YEARWEEK(sale_date) = YEARWEEK(CURDATE())");
    $stmt->execute();
    $stats['week_sales'] = $stmt->fetch()['total'];
    
    // This month's expenses
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())");
    $stmt->execute();
    $stats['month_expenses'] = $stmt->fetch()['total'];
    
    // Low stock count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE stock_quantity < 10");
    $stmt->execute();
    $stats['low_stock_count'] = $stmt->fetch()['count'];
    
    // Net profit
    $stats['net_profit'] = $stats['week_sales'] - $stats['month_expenses'];
    
    // Best selling product
    $stmt = $pdo->prepare("
        SELECT p.product_name, SUM(s.quantity) as total_sold 
        FROM sales s 
        JOIN products p ON s.product_id = p.id 
        GROUP BY s.product_id 
        ORDER BY total_sold DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $stats['best_product'] = $stmt->fetch();
    
    return $stats;
}

/**
 * Time ago function
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

// ============================================
// NEW FUNCTIONS FOR CONTACT, HISTORY, AND ADMIN
// ============================================

/**
 * Save contact message
 */
function saveContactMessage($pdo, $user_id, $name, $email, $subject, $message) {
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (user_id, name, email, subject, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'unread', NOW())
    ");
    return $stmt->execute([$user_id, $name, $email, $subject, $message]);
}

/**
 * Get all contact messages for admin
 */
function getContactMessages($pdo, $status = null) {
    if ($status) {
        $stmt = $pdo->prepare("
            SELECT cm.*, u.owner_name as user_name
            FROM contact_messages cm
            LEFT JOIN users u ON cm.user_id = u.id
            WHERE cm.status = ?
            ORDER BY cm.created_at DESC
        ");
        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->prepare("
            SELECT cm.*, u.owner_name as user_name
            FROM contact_messages cm
            LEFT JOIN users u ON cm.user_id = u.id
            ORDER BY cm.created_at DESC
        ");
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

/**
 * Reply to contact message
 */
function replyContactMessage($pdo, $message_id, $reply, $admin_id) {
    $stmt = $pdo->prepare("
        UPDATE contact_messages 
        SET admin_reply = ?, replied_by = ?, replied_at = NOW(), status = 'replied'
        WHERE id = ?
    ");
    return $stmt->execute([$reply, $admin_id, $message_id]);
}

/**
 * Mark contact message as read
 */
function markContactMessageRead($pdo, $message_id) {
    $stmt = $pdo->prepare("
        UPDATE contact_messages SET status = 'read' WHERE id = ?
    ");
    return $stmt->execute([$message_id]);
}

/**
 * Get unread contact messages count
 */
function getUnreadContactCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'");
    return $stmt->fetchColumn();
}

/**
 * Get user transaction history
 */
function getUserTransactionHistory($pdo, $user_id = null) {
    if ($user_id) {
        // Get specific user's transactions
        $stmt = $pdo->prepare("
            SELECT 
                'sale' as type,
                s.id,
                s.total_amount as amount,
                s.created_at as date,
                p.product_name as item_name,
                s.quantity,
                s.receipt_no as reference
            FROM sales s
            JOIN products p ON s.product_id = p.id
            WHERE s.created_by = ?
            UNION ALL
            SELECT 
                'expense' as type,
                e.id,
                e.amount,
                e.created_at,
                e.category as item_name,
                NULL as quantity,
                e.reference_no as reference
            FROM expenses e
            WHERE e.created_by = ?
            ORDER BY date DESC
        ");
        $stmt->execute([$user_id, $user_id]);
    } else {
        // Get all transactions for admin
        $stmt = $pdo->prepare("
            SELECT 
                'sale' as type,
                s.id,
                s.total_amount as amount,
                s.created_at as date,
                p.product_name as item_name,
                s.quantity,
                s.receipt_no as reference,
                u.owner_name as user_name,
                u.id as user_id
            FROM sales s
            JOIN products p ON s.product_id = p.id
            LEFT JOIN users u ON s.created_by = u.id
            UNION ALL
            SELECT 
                'expense' as type,
                e.id,
                e.amount,
                e.created_at,
                e.category as item_name,
                NULL as quantity,
                e.reference_no as reference,
                u.owner_name as user_name,
                u.id as user_id
            FROM expenses e
            LEFT JOIN users u ON e.created_by = u.id
            ORDER BY date DESC
        ");
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

/**
 * Get user login history
 */
function getUserLoginHistory($pdo, $user_id = null) {
    if ($user_id) {
        $stmt = $pdo->prepare("
            SELECT * FROM login_history 
            WHERE user_id = ? 
            ORDER BY login_time DESC 
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT lh.*, u.owner_name, u.email
            FROM login_history lh
            JOIN users u ON lh.user_id = u.id
            ORDER BY lh.login_time DESC
            LIMIT 100
        ");
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

/**
 * Record user login
 */
function recordLoginHistory($pdo, $user_id, $status = 'success') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt = $pdo->prepare("
        INSERT INTO login_history (user_id, login_time, ip_address, user_agent, status)
        VALUES (?, NOW(), ?, ?, ?)
    ");
    return $stmt->execute([$user_id, $ip, $user_agent, $status]);
}

/**
 * Get user details for admin
 */
function getAllUsersDetails($pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM sales WHERE created_by = u.id) as total_sales,
            (SELECT SUM(total_amount) FROM sales WHERE created_by = u.id) as total_revenue,
            (SELECT COUNT(*) FROM expenses WHERE created_by = u.id) as total_expenses,
            (SELECT SUM(amount) FROM expenses WHERE created_by = u.id) as total_expense_amount,
            (SELECT COUNT(*) FROM login_history WHERE user_id = u.id) as login_count,
            (SELECT MAX(login_time) FROM login_history WHERE user_id = u.id) as last_login
        FROM users u
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}
/**
 * Get messages for a specific user
 */
function getUserMessages($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM contact_messages 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Reply to message (for staff and admin)
 */
function replyToMessage($pdo, $message_id, $reply, $user_id, $user_role) {
    $stmt = $pdo->prepare("
        UPDATE contact_messages 
        SET admin_reply = CONCAT(IFNULL(admin_reply, ''), '\n\n--- Reply from " . ($user_role === 'admin' ? 'Admin' : 'Staff') . " on ' , NOW() , ' ---\n', ?),
            replied_by = ?,
            replied_at = NOW(),
            status = 'replied'
        WHERE id = ?
    ");
    return $stmt->execute([$reply, $user_id, $message_id]);
}
?>