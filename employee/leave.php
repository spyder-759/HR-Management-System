<?php
require_once '../includes/config.php';

// Check if employee is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit();
}

require_employee_login();

// Prevent caching after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$conn = getConnection();
$employee_id = $_SESSION['employee_id'];

// Initialize leave balance for this employee if not exists
initialize_employee_leave_balance($employee_id);

// Handle leave application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_leave'])) {
    $leave_type_id = sanitize_input($_POST['leave_type_id']);
    $start_date = sanitize_input($_POST['start_date']);
    $end_date = sanitize_input($_POST['end_date']);
    $reason = sanitize_input($_POST['reason']);
    
    try {
        // Calculate total days
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $total_days = $start->diff($end)->days + 1;
        
        // Check leave balance
        $current_year = date('Y');
        $stmt = $conn->prepare("SELECT total_allocated, total_used FROM leave_balance 
                                WHERE employee_id = ? AND leave_type_id = ? AND year = ?");
        $stmt->execute([$employee_id, $leave_type_id, $current_year]);
        $balance = $stmt->fetch();
        
        if ($balance && ($balance['total_allocated'] - $balance['total_used']) >= $total_days) {
            // Apply for leave
            $stmt = $conn->prepare("INSERT INTO leaves (employee_id, leave_type_id, start_date, end_date, total_days, reason, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$employee_id, $leave_type_id, $start_date, $end_date, $total_days, $reason]);
            
            $_SESSION['success'] = "Leave application submitted successfully!";
        } else {
            $_SESSION['error'] = "Insufficient leave balance. Available: " . ($balance ? ($balance['total_allocated'] - $balance['total_used']) : 0) . " days";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Failed to submit leave application. Please try again.";
    }
    
    header('Location: leave.php');
    exit();
}

// Get leave types
$stmt = $conn->prepare("SELECT * FROM leave_types ORDER BY name");
$stmt->execute();
$leave_types = $stmt->fetchAll();

// Get leave balance
$current_year = date('Y');
$stmt = $conn->prepare("SELECT lt.name, lb.total_allocated, lb.total_used, 
                        (lb.total_allocated - lb.total_used) as remaining_days
                        FROM leave_balance lb 
                        JOIN leave_types lt ON lb.leave_type_id = lt.id 
                        WHERE lb.employee_id = ? AND lb.year = ?");
$stmt->execute([$employee_id, $current_year]);
$leave_balances = $stmt->fetchAll();

// Get leave requests
$stmt = $conn->prepare("SELECT l.*, lt.name as leave_type FROM leaves l 
                        JOIN leave_types lt ON l.leave_type_id = lt.id 
                        WHERE l.employee_id = ? 
                        ORDER BY l.created_at DESC");
$stmt->execute([$employee_id]);
$leave_requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - Employee Portal</title>
    <link rel="stylesheet" href="../assets/css/enhanced-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-user-tie"></i>
                    <span>Employee Portal</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="sidebar-menu">
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="attendance.php"><i class="fas fa-clock"></i> Attendance</a></li>
                    <li><a href="leave.php" class="active"><i class="fas fa-calendar-alt"></i> Leave Management</a></li>
                    <li><a href="tasks.php"><i class="fas fa-tasks"></i> Tasks</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="btn btn-outline" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Leave Management</h1>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-primary" onclick="showLeaveModal()">
                        <i class="fas fa-plus"></i> Apply Leave
                    </button>
                </div>
            </header>
            
            <!-- Content -->
            <div class="content">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Leave Balance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Leave Balance</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($leave_balances)): ?>
                            <p style="text-align: center; color: var(--text-muted);">No leave balance data available</p>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                <?php foreach ($leave_balances as $balance): ?>
                                    <div style="padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem; text-align: center;">
                                        <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem; color: var(--primary-color);">
                                            <?php echo htmlspecialchars($balance['name']); ?>
                                        </div>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <div>
                                                <small style="color: var(--text-muted);">Allocated</small>
                                                <div style="font-size: 1.2rem; font-weight: 700;"><?php echo $balance['total_allocated']; ?></div>
                                            </div>
                                            <div>
                                                <small style="color: var(--text-muted);">Remaining</small>
                                                <div style="font-size: 1.2rem; font-weight: 700; color: <?php echo $balance['remaining_days'] > 0 ? 'var(--success-color)' : 'var(--danger-color)'; ?>;">
                                                    <?php echo $balance['remaining_days']; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="margin-top: 0.5rem;">
                                            <small style="color: var(--text-muted);">Used: <?php echo $balance['total_used']; ?> days</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Leave Requests -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Leave History</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($leave_requests)): ?>
                            <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No leave requests found</p>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Leave Type</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Days</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Applied On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leave_requests as $leave): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($leave['start_date'])); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($leave['end_date'])); ?></td>
                                                <td><?php echo $leave['total_days']; ?></td>
                                                <td><?php echo htmlspecialchars(substr($leave['reason'], 0, 50)) . (strlen($leave['reason']) > 50 ? '...' : ''); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $leave['status'] == 'approved' ? 'success' : ($leave['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($leave['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($leave['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Leave Application Modal -->
    <div class="modal" id="leaveModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Apply for Leave</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="leaveForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Leave Type *</label>
                        <select class="form-control form-select" name="leave_type_id" required>
                            <option value="">Select Leave Type</option>
                            <?php foreach ($leave_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>">
                                    <?php echo htmlspecialchars($type['name']); ?> 
                                    (Available: <?php 
                                        $available = 0;
                                        foreach ($leave_balances as $balance) {
                                            if ($balance['name'] == $type['name']) {
                                                $available = $balance['remaining_days'];
                                                break;
                                            }
                                        }
                                        echo $available;
                                    ?> days)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Start Date *</label>
                            <input type="date" class="form-control" name="start_date" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date *</label>
                            <input type="date" class="form-control" name="end_date" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Reason *</label>
                        <textarea class="form-control" name="reason" rows="4" required 
                                  placeholder="Please provide reason for leave..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="apply_leave" class="btn btn-primary">Apply Leave</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        }
        
        function showLeaveModal() {
            document.getElementById('leaveModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('leaveModal').classList.remove('show');
        }
        
        // Calculate days when dates change
        document.getElementById('leaveForm').addEventListener('change', function(e) {
            if (e.target.name === 'start_date' || e.target.name === 'end_date') {
                const startDate = new Date(document.querySelector('input[name="start_date"]').value);
                const endDate = new Date(document.querySelector('input[name="end_date"]').value);
                
                if (startDate && endDate && endDate >= startDate) {
                    const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
                    // You could display this to the user if needed
                }
            }
        });
    </script>
</body>
</html>
