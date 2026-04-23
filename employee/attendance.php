<?php
require_once '../includes/config.php';

// Check if employee is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getConnection();
$employee_id = $_SESSION['employee_id'];

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $date = date('Y-m-d');
    $check_in = date('H:i:s');
    
    try {
        // Check if attendance already marked for today
        $stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->execute([$employee_id, $date]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            // Mark check-in
            $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, status, check_in) VALUES (?, ?, 'present', ?)");
            $stmt->execute([$employee_id, $date, $check_in]);
            $_SESSION['success'] = "Attendance marked successfully!";
        } else {
            $_SESSION['error'] = "Attendance already marked for today.";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Failed to mark attendance. Please try again.";
    }
    
    header('Location: attendance.php');
    exit();
}

// Handle check-out
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    $date = date('Y-m-d');
    $check_out = date('H:i:s');
    
    try {
        $stmt = $conn->prepare("UPDATE attendance SET check_out = ? WHERE employee_id = ? AND date = ?");
        $stmt->execute([$check_out, $employee_id, $date]);
        $_SESSION['success'] = "Check-out marked successfully!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Failed to mark check-out. Please try again.";
    }
    
    header('Location: attendance.php');
    exit();
}

// Get current month attendance
$month = isset($_GET['month']) ? sanitize_input($_GET['month']) : date('Y-m');
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND DATE_FORMAT(date, '%Y-%m') = ? ORDER BY date DESC");
$stmt->execute([$employee_id, $month]);
$attendance_records = $stmt->fetchAll();

// Get today's attendance
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = CURDATE()");
$stmt->execute([$employee_id]);
$today_attendance = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Employee Portal</title>
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
                    <li><a href="attendance.php" class="active"><i class="fas fa-clock"></i> Attendance</a></li>
                    <li><a href="leave.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></li>
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
                    <h1>Attendance Management</h1>
                </div>
                
                <div class="header-right">
                    <input type="month" class="form-control" value="<?php echo htmlspecialchars($month); ?>" 
                           onchange="window.location.href='attendance.php?month=' + this.value" 
                           style="max-width: 200px;">
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
                
                <!-- Today's Attendance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Today's Attendance</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($today_attendance): ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <div>
                                    <strong>Status:</strong>
                                    <span class="badge badge-<?php echo $today_attendance['status'] == 'present' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($today_attendance['status']); ?>
                                    </span>
                                </div>
                                <div>
                                    <strong>Check In:</strong> <?php echo $today_attendance['check_in'] ?: 'Not checked in'; ?>
                                </div>
                                <div>
                                    <strong>Check Out:</strong> 
                                    <?php 
                                    if ($today_attendance['check_out']) {
                                        echo $today_attendance['check_out'];
                                    } else {
                                        echo '<form method="POST" style="display: inline;"><button type="submit" name="checkout" class="btn btn-sm btn-warning"><i class="fas fa-sign-out-alt"></i> Check Out</button></form>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 2rem;">
                                <form method="POST">
                                    <button type="submit" name="mark_attendance" class="btn btn-primary btn-lg">
                                        <i class="fas fa-clock"></i> Mark Attendance for Today
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Attendance Records -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Attendance Records</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($attendance_records)): ?>
                            <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No attendance records found</p>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance_records as $record): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $record['status'] == 'present' ? 'success' : ($record['status'] == 'late' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $record['check_in'] ?: '-'; ?></td>
                                                <td><?php echo $record['check_out'] ?: '-'; ?></td>
                                                <td><?php echo htmlspecialchars($record['notes'] ?? '-'); ?></td>
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
    
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        }
    </script>
</body>
</html>
