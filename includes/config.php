<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hr_management_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
function getConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Start session
session_start();

// Security functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_employee_id() {
    $prefix = 'EMP';
    $year = date('Y');
    $random = sprintf('%04d', rand(1, 9999));
    return $prefix . $year . $random;
}

// Check if admin is logged in
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']) && isset($_SESSION['auth_token']) && validate_auth_token($_SESSION['auth_token']);
}

// Generate and store auth token
function generate_auth_token($user_id) {
    $token = bin2hex(random_bytes(32));
    $expires = time() + 3600; // 1 hour expiry
    
    // Store token in session
    $_SESSION['auth_token'] = $token;
    $_SESSION['token_expires'] = $expires;
    
    return $token;
}

// Validate auth token
function validate_auth_token($token) {
    if (!isset($_SESSION['auth_token']) || !isset($_SESSION['token_expires'])) {
        return false;
    }
    
    if ($token !== $_SESSION['auth_token']) {
        return false;
    }
    
    if (time() > $_SESSION['token_expires']) {
        return false;
    }
    
    return true;
}

// Clear auth token
function clear_auth_token() {
    unset($_SESSION['auth_token']);
    unset($_SESSION['token_expires']);
}

// Refresh auth token (extend session)
function refresh_auth_token() {
    if (isset($_SESSION['admin_id']) && isset($_SESSION['auth_token'])) {
        // Extend token expiry by another hour
        $_SESSION['token_expires'] = time() + 3600;
    }
}

// Validate and refresh token on page load
function validate_session() {
    if (isset($_SESSION['admin_id'])) {
        if (!isset($_SESSION['auth_token']) || !validate_auth_token($_SESSION['auth_token'])) {
            clear_auth_token();
            session_destroy();
            header('Location: login.php');
            exit();
        } else {
            refresh_auth_token();
        }
    }
}

// Redirect if not logged in
function require_login() {
    validate_session();
    if (!is_admin_logged_in()) {
        clear_auth_token();
        header('Location: login.php');
        exit();
    }
}

// Check if employee is logged in
function is_employee_logged_in() {
    return isset($_SESSION['employee_id']) && !empty($_SESSION['employee_id']) && isset($_SESSION['employee_auth_token']) && validate_employee_auth_token($_SESSION['employee_auth_token']);
}

// Generate and store employee auth token
function generate_employee_auth_token($user_id) {
    $token = bin2hex(random_bytes(32));
    $expires = time() + 3600; // 1 hour expiry
    
    // Store token in session
    $_SESSION['employee_auth_token'] = $token;
    $_SESSION['employee_token_expires'] = $expires;
    
    return $token;
}

// Validate employee auth token
function validate_employee_auth_token($token) {
    if (!isset($_SESSION['employee_auth_token']) || !isset($_SESSION['employee_token_expires'])) {
        return false;
    }
    
    if ($token !== $_SESSION['employee_auth_token']) {
        return false;
    }
    
    if (time() > $_SESSION['employee_token_expires']) {
        return false;
    }
    
    return true;
}

// Clear employee auth token
function clear_employee_auth_token() {
    unset($_SESSION['employee_auth_token']);
    unset($_SESSION['employee_token_expires']);
}

// Refresh employee auth token (extend session)
function refresh_employee_auth_token() {
    if (isset($_SESSION['employee_id']) && isset($_SESSION['employee_auth_token'])) {
        // Extend token expiry by another hour
        $_SESSION['employee_token_expires'] = time() + 3600;
    }
}

// Validate and refresh employee token on page load
function validate_employee_session() {
    if (isset($_SESSION['employee_id'])) {
        if (!isset($_SESSION['employee_auth_token']) || !validate_employee_auth_token($_SESSION['employee_auth_token'])) {
            clear_employee_auth_token();
            session_destroy();
            header('Location: employee/login.php');
            exit();
        } else {
            refresh_employee_auth_token();
        }
    }
}

// Redirect if employee not logged in
function require_employee_login() {
    validate_employee_session();
    if (!is_employee_logged_in()) {
        clear_employee_auth_token();
        header('Location: employee/login.php');
        exit();
    }
}

// Format date
function format_date($date, $format = 'Y-m-d') {
    $date_obj = new DateTime($date);
    return $date_obj->format($format);
}

// Calculate days between dates
function calculate_days($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    return $interval->days + 1; // Include both dates
}

// Upload file
function upload_file($file, $allowed_types = ['pdf', 'doc', 'docx'], $max_size = 5242880) {
    $target_dir = "../uploads/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_name = time() . '_' . rand(1000, 9999) . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    // Validate file
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'];
    }
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Only ' . implode(', ', $allowed_types) . ' files are allowed.'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'file_path' => $target_file];
    } else {
        return ['success' => false, 'message' => 'Error uploading file.'];
    }
}

// Get dashboard statistics
function get_dashboard_stats() {
    $conn = getConnection();
    
    $stats = [];
    
    // Total employees
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
    $stmt->execute();
    $stats['total_employees'] = $stmt->fetch()['total'];
    
    // Total candidates
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM candidates");
    $stmt->execute();
    $stats['total_candidates'] = $stmt->fetch()['total'];
    
    // Pending leaves
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leaves WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending_leaves'] = $stmt->fetch()['total'];
    
    // Today's attendance
    $stmt = $conn->prepare("SELECT 
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
        COUNT(*) as total
        FROM attendance WHERE date = CURDATE()");
    $stmt->execute();
    $attendance = $stmt->fetch();
    $stats['today_attendance'] = [
        'present' => $attendance['present'] ?? 0,
        'absent' => $attendance['absent'] ?? 0,
        'total' => $attendance['total'] ?? 0
    ];
    
    // Recent candidates
    $stmt = $conn->prepare("SELECT c.*, j.title as job_title FROM candidates c 
        LEFT JOIN jobs j ON c.job_id = j.id 
        ORDER BY c.applied_date DESC LIMIT 5");
    $stmt->execute();
    $stats['recent_candidates'] = $stmt->fetchAll();
    
    // Recent leaves
    $stmt = $conn->prepare("SELECT l.*, e.first_name, e.last_name, lt.name as leave_type 
        FROM leaves l 
        JOIN employees e ON l.employee_id = e.id 
        JOIN leave_types lt ON l.leave_type_id = lt.id 
        ORDER BY l.created_at DESC LIMIT 5");
    $stmt->execute();
    $stats['recent_leaves'] = $stmt->fetchAll();
    
    // Task statistics
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'completed' AND DATE(completed_at) = CURDATE() THEN 1 ELSE 0 END) as completed_today
        FROM tasks");
    $stmt->execute();
    $task_stats = $stmt->fetch();
    $stats['total_tasks'] = $task_stats['total_tasks'];
    $stats['pending_tasks'] = $task_stats['pending_tasks'];
    $stats['in_progress_tasks'] = $task_stats['in_progress_tasks'];
    $stats['completed_tasks'] = $task_stats['completed_tasks'];
    $stats['completed_today'] = $task_stats['completed_today'];
    
    // Recent tasks
    $stmt = $conn->prepare("SELECT t.*, e.first_name, e.last_name, a.username as admin_name 
        FROM tasks t 
        JOIN employees e ON t.assigned_to = e.id 
        LEFT JOIN admins a ON t.assigned_by = a.id 
        ORDER BY t.created_at DESC LIMIT 5");
    $stmt->execute();
    $stats['recent_tasks'] = $stmt->fetchAll();
    
    return $stats;
}

// Pagination helper
function get_pagination($total_records, $per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_records / $per_page);
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'total_records' => $total_records,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset
    ];
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize leave balance for employees
function initialize_employee_leave_balance($employee_id, $year = null) {
    if ($year === null) {
        $year = date('Y');
    }
    
    $conn = getConnection();
    
    // Get all leave types
    $stmt = $conn->prepare("SELECT * FROM leave_types");
    $stmt->execute();
    $leave_types = $stmt->fetchAll();
    
    foreach ($leave_types as $type) {
        // Check if balance already exists for this employee and leave type
        $stmt = $conn->prepare("SELECT id FROM leave_balance 
                                WHERE employee_id = ? AND leave_type_id = ? AND year = ?");
        $stmt->execute([$employee_id, $type['id'], $year]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            // Insert new leave balance record
            $stmt = $conn->prepare("INSERT INTO leave_balance 
                                    (employee_id, leave_type_id, total_allocated, total_used, year) 
                                    VALUES (?, ?, ?, 0, ?)");
            $stmt->execute([$employee_id, $type['id'], $type['max_days_per_year'], $year]);
        }
    }
}

// Initialize leave balance for all employees
function initialize_all_employees_leave_balance($year = null) {
    if ($year === null) {
        $year = date('Y');
    }
    
    $conn = getConnection();
    
    // Get all active employees
    $stmt = $conn->prepare("SELECT id FROM employees WHERE status = 'active'");
    $stmt->execute();
    $employees = $stmt->fetchAll();
    
    foreach ($employees as $employee) {
        initialize_employee_leave_balance($employee['id'], $year);
    }
}

?>
