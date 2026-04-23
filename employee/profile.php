<?php
require_once '../includes/config.php';

// Check if employee is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getConnection();
$employee_id = $_SESSION['employee_id'];

// Get employee information
$stmt = $conn->prepare("SELECT e.*, d.name as department_name FROM employees e 
                        LEFT JOIN departments d ON e.department_id = d.id 
                        WHERE e.id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Employee Portal</title>
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
                    <li><a href="leave.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></li>
                    <li><a href="tasks.php"><i class="fas fa-tasks"></i> Tasks</a></li>
                    <li><a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
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
                    <h1>My Profile</h1>
                </div>
            </header>
            
            <!-- Content -->
            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <div>
                                <div style="text-align: center; margin-bottom: 2rem;">
                                    <div style="width: 120px; height: 120px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; margin: 0 auto;">
                                        <?php echo strtoupper(substr($employee['first_name'], 0, 2)); ?>
                                    </div>
                                    <h3 style="margin: 1rem 0 0.5rem 0;"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h3>
                                    <p style="color: var(--text-muted); margin: 0;"><?php echo htmlspecialchars($employee['role']); ?></p>
                                </div>
                                
                                <div style="display: grid; gap: 1rem;">
                                    <div>
                                        <label style="color: var(--text-muted); font-size: 0.875rem;">Employee ID</label>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($employee['employee_id']); ?></div>
                                    </div>
                                    <div>
                                        <label style="color: var(--text-muted); font-size: 0.875rem;">Email</label>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($employee['email']); ?></div>
                                    </div>
                                    <div>
                                        <label style="color: var(--text-muted); font-size: 0.875rem;">Phone</label>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($employee['phone'] ?? 'Not provided'); ?></div>
                                    </div>
                                    <div>
                                        <label style="color: var(--text-muted); font-size: 0.875rem;">Department</label>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($employee['department_name'] ?? 'Not assigned'); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Employment Details</h4>
                                <div style="display: grid; gap: 1rem;">
                                    <div>
                                        <label style="color: var(--text-muted); font-size: 0.875rem;">Role/Position</label>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($employee['role']); ?></div>
                                    </div>
                                    <div>
                                        <label style="color: var(--text-muted); font-size: 0.875rem;">Joining Date</label>
                                        <div style="font-weight: 600;"><?php echo date('M j, Y', strtotime($employee['joining_date'])); ?></div>
                                    </div>
                                    <div>
                                        <label style="color: var(--text-muted); font-size: 0.875rem;">Status</label>
                                        <div>
                                            <span class="badge badge-<?php echo $employee['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($employee['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <label style="color: var(--text-muted); font-size: 0.875rem;">Salary</label>
                                        <div style="font-weight: 600;">
                                            <?php 
                                            if ($employee['salary']) {
                                                echo '$' . number_format($employee['salary'], 2);
                                            } else {
                                                echo 'Not specified';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <a href="attendance.php" class="action-card">
                                <i class="fas fa-clock" style="color: var(--primary-color);"></i>
                                <div>Mark Attendance</div>
                                <small>Check in/out for today</small>
                            </a>
                            <a href="leave.php" class="action-card">
                                <i class="fas fa-calendar-alt" style="color: var(--success-color);"></i>
                                <div>Apply Leave</div>
                                <small>Request time off</small>
                            </a>
                            <a href="tasks.php" class="action-card">
                                <i class="fas fa-tasks" style="color: var(--warning-color);"></i>
                                <div>View Tasks</div>
                                <small>Check assignments</small>
                            </a>
                            <a href="#" onclick="window.print()" class="action-card">
                                <i class="fas fa-print" style="color: var(--info-color);"></i>
                                <div>Print Profile</div>
                                <small>Download PDF</small>
                            </a>
                        </div>
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
    
    <style>
        .action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }
        
        .action-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .action-card div {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .action-card small {
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        
        @media print {
            .sidebar, .header {
                display: none;
            }
            .main-content {
                margin: 0;
                padding: 1rem;
            }
        }
    </style>
</body>
</html>
