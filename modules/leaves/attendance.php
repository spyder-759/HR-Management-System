<?php
require_once '../../includes/config.php';
require_login();

$conn = getConnection();

// Handle attendance operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_attendance'])) {
        $date = sanitize_input($_POST['date']);
        $attendances = $_POST['attendance'];
        
        foreach ($attendances as $employee_id => $status) {
            $check_in = isset($_POST['check_in'][$employee_id]) ? sanitize_input($_POST['check_in'][$employee_id]) : null;
            $check_out = isset($_POST['check_out'][$employee_id]) ? sanitize_input($_POST['check_out'][$employee_id]) : null;
            $notes = isset($_POST['notes'][$employee_id]) ? sanitize_input($_POST['notes'][$employee_id]) : null;
            
            // Check if attendance already exists for this employee and date
            $stmt = $conn->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
            $stmt->execute([$employee_id, $date]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing record
                $stmt = $conn->prepare("UPDATE attendance SET status=?, check_in=?, check_out=?, notes=? WHERE employee_id=? AND date=?");
                $stmt->execute([$status, $check_in, $check_out, $notes, $employee_id, $date]);
            } else {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, status, check_in, check_out, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$employee_id, $date, $status, $check_in, $check_out, $notes]);
            }
        }
        
        $_SESSION['success'] = "Attendance marked successfully!";
        header('Location: attendance.php');
        exit();
    }
    
    if (isset($_POST['bulk_mark'])) {
        $date = sanitize_input($_POST['date']);
        $status = sanitize_input($_POST['status']);
        
        // Get all active employees
        $stmt = $conn->prepare("SELECT id FROM employees WHERE status = 'active'");
        $stmt->execute();
        $employees = $stmt->fetchAll();
        
        foreach ($employees as $employee) {
            // Check if attendance already exists
            $stmt = $conn->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
            $stmt->execute([$employee['id'], $date]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, ?)");
                $stmt->execute([$employee['id'], $date, $status]);
            }
        }
        
        $_SESSION['success'] = "Bulk attendance marked successfully!";
        header('Location: attendance.php');
        exit();
    }
}

// Get date parameter
$date = isset($_GET['date']) ? sanitize_input($_GET['date']) : date('Y-m-d');

// Get employees for attendance marking
$stmt = $conn->prepare("SELECT e.*, d.name as department_name FROM employees e 
                        LEFT JOIN departments d ON e.department_id = d.id 
                        WHERE e.status = 'active' 
                        ORDER BY d.name, e.first_name, e.last_name");
$stmt->execute();
$employees = $stmt->fetchAll();

// Get existing attendance for the selected date
$attendance_data = [];
if ($employees) {
    $employee_ids = array_column($employees, 'id');
    $placeholders = str_repeat('?,', count($employee_ids) - 1) . '?';
    
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id IN ($placeholders) AND date = ?");
    $stmt->execute(array_merge($employee_ids, [$date]));
    $attendance_records = $stmt->fetchAll();
    
    foreach ($attendance_records as $record) {
        $attendance_data[$record['employee_id']] = $record;
    }
}

// Get attendance statistics for the selected date
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE date = ? GROUP BY status");
$stmt->execute([$date]);
$attendance_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get monthly attendance summary
$month_start = date('Y-m-01', strtotime($date));
$month_end = date('Y-m-t', strtotime($date));

$stmt = $conn->prepare("SELECT e.id, e.first_name, e.last_name, e.employee_id,
                        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_days,
                        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_days,
                        COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_days,
                        COUNT(*) as total_days
                        FROM employees e
                        LEFT JOIN attendance a ON e.id = a.employee_id AND a.date BETWEEN ? AND ?
                        WHERE e.status = 'active'
                        GROUP BY e.id
                        ORDER BY present_days DESC");
$stmt->execute([$month_start, $month_end]);
$monthly_summary = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - HR Management System</title>
    <link rel="stylesheet" href="../../assets/css/enhanced-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-users-cog"></i>
                    <span>HR System</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="sidebar-menu">
                    <li><a href="../../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="../recruitment/jobs.php"><i class="fas fa-briefcase"></i> Recruitment</a></li>
                    <li><a href="../employees/employees.php"><i class="fas fa-users"></i> Employees</a></li>
                    <li><a href="leaves.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></li>
                    <li><a href="attendance.php" class="active"><i class="fas fa-clock"></i> Attendance</a></li>
                    <li><a href="../performance/performance.php"><i class="fas fa-chart-line"></i> Performance</a></li>
                    <li><a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
                    <h1>Attendance Management</h1>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-primary" onclick="showBulkModal()">
                        <i class="fas fa-users-check"></i> Bulk Mark
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
                
                <!-- Date Selection -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-3 align-items-center">
                            <label class="form-label" style="margin: 0;">Select Date:</label>
                            <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date); ?>" style="max-width: 200px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Load Attendance
                            </button>
                            <button type="button" class="btn btn-outline" onclick="setToday()">
                                <i class="fas fa-calendar-day"></i> Today
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-grid" style="display: flex; gap: 5rem;">
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($attendance_stats['present'] ?? 0); ?></div>
                        <div class="stat-label">Present</div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($attendance_stats['absent'] ?? 0); ?></div>
                        <div class="stat-label">Absent</div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($attendance_stats['late'] ?? 0); ?></div>
                        <div class="stat-label">Late</div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format(count($employees)); ?></div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                </div>
                
                <!-- Attendance Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Mark Attendance - <?php echo date('F j, Y', strtotime($date)); ?></h3>
                        <button type="submit" form="attendanceForm" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Attendance
                        </button>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="attendanceForm">
                            <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                            
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Employee ID</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employees as $employee): ?>
                                            <?php 
                                            $attendance = isset($attendance_data[$employee['id']]) ? $attendance_data[$employee['id']] : null;
                                            $current_status = $attendance ? $attendance['status'] : 'present';
                                            ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 500;">
                                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span style="color: var(--primary-color); font-weight: 600;">
                                                        <?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($employee['department_name'] ?? 'Not assigned'); ?></td>
                                                <td>
                                                    <select class="form-control form-select" name="attendance[<?php echo $employee['id']; ?>]" style="min-width: 120px;">
                                                        <option value="present" <?php echo $current_status == 'present' ? 'selected' : ''; ?>>Present</option>
                                                        <option value="absent" <?php echo $current_status == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                        <option value="late" <?php echo $current_status == 'late' ? 'selected' : ''; ?>>Late</option>
                                                        <option value="half_day" <?php echo $current_status == 'half_day' ? 'selected' : ''; ?>>Half Day</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="time" class="form-control" name="check_in[<?php echo $employee['id']; ?>]" 
                                                           value="<?php echo $attendance ? substr($attendance['check_in'], 0, 5) : ''; ?>">
                                                </td>
                                                <td>
                                                    <input type="time" class="form-control" name="check_out[<?php echo $employee['id']; ?>]" 
                                                           value="<?php echo $attendance ? substr($attendance['check_out'], 0, 5) : ''; ?>">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="notes[<?php echo $employee['id']; ?>]" 
                                                           placeholder="Add notes..." 
                                                           value="<?php echo htmlspecialchars($attendance['notes'] ?? ''); ?>">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Monthly Summary -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Monthly Summary - <?php echo date('F Y', strtotime($date)); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Employee ID</th>
                                        <th>Present Days</th>
                                        <th>Absent Days</th>
                                        <th>Late Days</th>
                                        <th>Total Days</th>
                                        <th>Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthly_summary as $summary): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 500;">
                                                    <?php echo htmlspecialchars($summary['first_name'] ?? '') . ' ' . htmlspecialchars($summary['last_name'] ?? ''); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($summary['employee_id'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge badge-success"><?php echo $summary['present_days']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-danger"><?php echo $summary['absent_days']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning"><?php echo $summary['late_days']; ?></span>
                                            </td>
                                            <td><?php echo $summary['total_days']; ?></td>
                                            <td>
                                                <?php 
                                                $attendance_percentage = $summary['total_days'] > 0 ? 
                                                    round(($summary['present_days'] / $summary['total_days']) * 100, 1) : 0;
                                                ?>
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <div style="flex: 1; background: var(--border-color); border-radius: 0.25rem; height: 8px; overflow: hidden;">
                                                        <div style="background: <?php echo $attendance_percentage >= 90 ? 'var(--success-color)' : ($attendance_percentage >= 75 ? 'var(--warning-color)' : 'var(--danger-color)'); ?>; height: 100%; width: <?php echo $attendance_percentage; ?>%;"></div>
                                                    </div>
                                                    <span style="font-weight: 600; min-width: 45px;"><?php echo $attendance_percentage; ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Bulk Mark Modal -->
    <div class="modal" id="bulkModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Bulk Mark Attendance</h3>
                <button class="modal-close" onclick="closeBulkModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" value="<?php echo date('F j, Y', strtotime($date)); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mark all employees as:</label>
                        <select class="form-control form-select" name="status" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This will mark attendance for all active employees who don't already have attendance recorded for this date. Existing records will not be overwritten.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeBulkModal()">Cancel</button>
                    <button type="submit" name="bulk_mark" class="btn btn-primary">Mark All</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        }
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../../logout.php';
            }
        }
        
        function setToday() {
            const today = new Date().toISOString().split('T')[0];
            window.location.href = '?date=' + today;
        }
        
        function showBulkModal() {
            document.getElementById('bulkModal').classList.add('show');
        }
        
        function closeBulkModal() {
            document.getElementById('bulkModal').classList.remove('show');
        }
        
        // Auto-save functionality (optional)
        let autoSaveTimer;
        function startAutoSave() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                if (confirm('Do you want to save the current attendance data?')) {
                    document.getElementById('attendanceForm').submit();
                }
            }, 300000); // 5 minutes
        }
        
        // Start auto-save when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startAutoSave();
            
            // Restart auto-save on any form change
            document.getElementById('attendanceForm').addEventListener('change', startAutoSave);
        });
    </script>
</body>
</html>
