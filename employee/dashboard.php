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

// Get employee information
$stmt = $conn->prepare("SELECT e.*, d.name as department_name FROM employees e 
                        LEFT JOIN departments d ON e.department_id = d.id 
                        WHERE e.id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

// Get today's attendance
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = CURDATE()");
$stmt->execute([$employee_id]);
$today_attendance = $stmt->fetch();

// Get leave balance
$stmt = $conn->prepare("SELECT lt.name, lb.total_allocated, lb.total_used, 
                        (lb.total_allocated - lb.total_used) as remaining_days
                        FROM leave_balance lb 
                        JOIN leave_types lt ON lb.leave_type_id = lt.id 
                        WHERE lb.employee_id = ? AND YEAR(lb.year) = YEAR(CURDATE())");
$stmt->execute([$employee_id]);
$leave_balances = $stmt->fetchAll();

// Get recent leave requests
$stmt = $conn->prepare("SELECT l.*, lt.name as leave_type FROM leaves l 
                        JOIN leave_types lt ON l.leave_type_id = lt.id 
                        WHERE l.employee_id = ? 
                        ORDER BY l.created_at DESC LIMIT 5");
$stmt->execute([$employee_id]);
$recent_leaves = $stmt->fetchAll();

// Get assigned tasks
$stmt = $conn->prepare("SELECT * FROM tasks 
                        WHERE assigned_to = ? AND status IN ('pending', 'in_progress') 
                        ORDER BY priority DESC, due_date ASC LIMIT 5");
$stmt->execute([$employee_id]);
$tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - HR Management System</title>
    <link rel="stylesheet" href="../assets/css/enhanced-style.css">
    <link rel="stylesheet" href="../assets/css/enhanced-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                    <li>
                        <a href="dashboard.php" class="active">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="attendance.php">
                            <i class="fas fa-clock"></i>
                            <span>Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="leave.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Leave Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="tasks.php">
                            <i class="fas fa-tasks"></i>
                            <span>Tasks</span>
                        </a>
                    </li>
                    <li>
                        <a href="profile.php">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
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
                    <h1 style="font-size: 1.5rem; font-weight: 600;">Welcome, <?php echo htmlspecialchars($employee['first_name']); ?>!</h1>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($employee['first_name'], 0, 2)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($_SESSION['employee_name']); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars($employee['department_name'] ?: 'Not assigned'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="content">
                <!-- Enhanced Quick Stats -->
                <div class="dashboard-stats-grid">
                    <div class="stat-card-enhanced primary animate-slide-in-up">
                        <div class="stat-icon-enhanced">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-value-enhanced">
                            <?php 
                            $present_days = 0;
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance 
                                                    WHERE employee_id = ? AND MONTH(date) = MONTH(CURDATE()) 
                                                    AND YEAR(date) = YEAR(CURDATE()) AND status = 'present'");
                            $stmt->execute([$employee_id]);
                            $present_days = $stmt->fetch()['count'];
                            echo $present_days;
                            ?>
                        </div>
                        <div class="stat-label-enhanced">Days Present This Month</div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i>
                            <span>Excellent attendance</span>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced success animate-slide-in-up animate-delay-1">
                        <div class="stat-icon-enhanced">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-value-enhanced">
                            <?php 
                            $total_leave = 0;
                            foreach ($leave_balances as $balance) {
                                $total_leave += $balance['remaining_days'];
                            }
                            echo $total_leave;
                            ?>
                        </div>
                        <div class="stat-label-enhanced">Leave Days Available</div>
                        <div class="stat-change">
                            <i class="fas fa-shield-alt"></i>
                            <span>Good balance</span>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced warning animate-slide-in-up animate-delay-2">
                        <div class="stat-icon-enhanced">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-value-enhanced"><?php echo count($tasks); ?></div>
                        <div class="stat-label-enhanced">Pending Tasks</div>
                        <div class="stat-change negative">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Action needed</span>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced info animate-slide-in-up animate-delay-3">
                        <div class="stat-icon-enhanced">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value-enhanced">
                            <?php 
                            if ($today_attendance) {
                                echo $today_attendance['check_in'] ? substr($today_attendance['check_in'], 0, 5) : 'Not checked in';
                            } else {
                                echo 'Not marked';
                            }
                            ?>
                        </div>
                        <div class="stat-label-enhanced">Today's Check-in</div>
                        <div class="stat-change">
                            <i class="fas fa-info-circle"></i>
                            <span>Track time</span>
                        </div>
                    </div>
                </div>
                
                <!-- Enhanced Today's Attendance -->
                <div class="dashboard-card animate-slide-in-up animate-delay-4">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">
                            <i class="fas fa-clock"></i>
                            Today's Attendance
                        </h3>
                        <?php if (!$today_attendance): ?>
                            <button class="btn-enhanced btn-enhanced-primary" onclick="markAttendance()">
                                <i class="fas fa-clock"></i> Mark Attendance
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="dashboard-card-body">
                        <?php if ($today_attendance): ?>
                            <div class="attendance-status">
                                <div class="attendance-item">
                                    <div class="attendance-label">Status</div>
                                    <div class="attendance-value">
                                        <span class="attendance-badge badge-enhanced badge-enhanced-<?php echo $today_attendance['status'] == 'present' ? 'success' : ($today_attendance['status'] == 'late' ? 'warning' : 'danger'); ?>">
                                            <i class="fas fa-<?php echo $today_attendance['status'] == 'present' ? 'check-circle' : ($today_attendance['status'] == 'late' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                                            <?php echo ucfirst($today_attendance['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="attendance-item">
                                    <div class="attendance-label">Check In</div>
                                    <div class="attendance-value">
                                        <i class="fas fa-sign-in-alt" style="color: var(--success-color); margin-right: 0.5rem;"></i>
                                        <?php echo $today_attendance['check_in'] ?: 'Not checked in'; ?>
                                    </div>
                                </div>
                                <div class="attendance-item">
                                    <div class="attendance-label">Check Out</div>
                                    <div class="attendance-value">
                                        <i class="fas fa-sign-out-alt" style="color: var(--danger-color); margin-right: 0.5rem;"></i>
                                        <?php echo $today_attendance['check_out'] ?: 'Not checked out'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="empty-state-title">No Attendance Marked</div>
                                <div class="empty-state-description">
                                    Your attendance for today has not been marked yet. Click the button above to mark your attendance.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Enhanced Leave Balance -->
                <div class="dashboard-card animate-slide-in-up animate-delay-5">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">
                            <i class="fas fa-calendar-alt"></i>
                            Leave Balance
                        </h3>
                        <a href="leave.php" class="btn-enhanced btn-enhanced-outline btn-enhanced-sm">
                            <i class="fas fa-plus"></i> Apply Leave
                        </a>
                    </div>
                    <div class="dashboard-card-body">
                        <?php if (empty($leave_balances)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                                <div class="empty-state-title">No Leave Data</div>
                                <div class="empty-state-description">
                                    No leave balance data available. Contact HR for assistance.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="leave-balance-grid">
                                <?php foreach ($leave_balances as $balance): ?>
                                    <div class="leave-balance-card">
                                        <div class="leave-type-name">
                                            <i class="fas fa-<?php echo $balance['name'] == 'Annual Leave' ? 'umbrella-beach' : ($balance['name'] == 'Sick Leave' ? 'heart' : 'home'); ?>" style="margin-right: 0.5rem; color: var(--primary-color);"></i>
                                            <?php echo htmlspecialchars($balance['name']); ?>
                                        </div>
                                        <div class="leave-stats">
                                            <div class="leave-stat-item">
                                                <span class="leave-stat-label">Allocated</span>
                                                <span class="leave-stat-value"><?php echo $balance['total_allocated']; ?></span>
                                            </div>
                                            <div class="leave-stat-item">
                                                <span class="leave-stat-label">Used</span>
                                                <span class="leave-stat-value"><?php echo $balance['total_used']; ?></span>
                                            </div>
                                            <div class="leave-stat-item">
                                                <span class="leave-stat-label">Remaining</span>
                                                <span class="leave-stat-value leave-remaining <?php echo $balance['remaining_days'] <= 2 ? ($balance['remaining_days'] <= 0 ? 'critical' : 'low') : ''; ?>">
                                                    <?php echo $balance['remaining_days']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if ($balance['remaining_days'] > 0): ?>
                                            <div style="margin-top: 1rem;">
                                                <div style="background: var(--light-color); border-radius: 9999px; height: 8px; overflow: hidden;">
                                                    <div style="background: var(--gradient-success); height: 100%; width: <?php echo ($balance['remaining_days'] / $balance['total_allocated']) * 100; ?>%; transition: width 0.3s ease;"></div>
                                                </div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem; text-align: center;">
                                                    <?php echo round(($balance['remaining_days'] / $balance['total_allocated']) * 100); ?>% available
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Enhanced Recent Leave Requests -->
                <div class="dashboard-card animate-slide-in-up animate-delay-6">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">
                            <i class="fas fa-history"></i>
                            Recent Leave Requests
                        </h3>
                        <a href="leave.php" class="btn-enhanced btn-enhanced-outline btn-enhanced-sm">
                            <i class="fas fa-eye"></i> View All
                        </a>
                    </div>
                    <div class="dashboard-card-body">
                        <?php if (empty($recent_leaves)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="empty-state-title">No Leave Requests</div>
                                <div class="empty-state-description">
                                    You haven't submitted any leave requests yet. Apply for leave when needed.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="leave-requests-list">
                                <?php foreach ($recent_leaves as $leave): ?>
                                    <div class="leave-request-item">
                                        <div class="leave-request-header">
                                            <div class="leave-request-type">
                                                <i class="fas fa-<?php echo $leave['leave_type'] == 'Annual Leave' ? 'umbrella-beach' : ($leave['leave_type'] == 'Sick Leave' ? 'heart' : 'home'); ?>" style="margin-right: 0.5rem; color: var(--primary-color);"></i>
                                                <?php echo htmlspecialchars($leave['leave_type']); ?>
                                            </div>
                                            <span class="badge-enhanced badge-enhanced-<?php echo $leave['status'] == 'approved' ? 'success' : ($leave['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                <i class="fas fa-<?php echo $leave['status'] == 'approved' ? 'check' : ($leave['status'] == 'rejected' ? 'times' : 'clock'); ?>"></i>
                                                <?php echo ucfirst($leave['status']); ?>
                                            </span>
                                        </div>
                                        <div class="leave-request-dates">
                                            <i class="fas fa-calendar-day" style="margin-right: 0.5rem;"></i>
                                            <?php echo date('M j, Y', strtotime($leave['start_date'])); ?> - 
                                            <?php echo date('M j, Y', strtotime($leave['end_date'])); ?>
                                        </div>
                                        <div class="leave-request-days">
                                            <i class="fas fa-clock" style="margin-right: 0.5rem;"></i>
                                            <?php echo $leave['total_days']; ?> day<?php echo $leave['total_days'] > 1 ? 's' : ''; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Enhanced Pending Tasks -->
                <div class="dashboard-card animate-slide-in-up animate-delay-7">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">
                            <i class="fas fa-tasks"></i>
                            Pending Tasks
                        </h3>
                        <a href="tasks.php" class="btn-enhanced btn-enhanced-outline btn-enhanced-sm">
                            <i class="fas fa-eye"></i> View All
                        </a>
                    </div>
                    <div class="dashboard-card-body">
                        <?php if (empty($tasks)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="empty-state-title">All Caught Up!</div>
                                <div class="empty-state-description">
                                    You have no pending tasks. Great job staying on top of your work!
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="tasks-list">
                                <?php foreach ($tasks as $task): ?>
                                    <div class="task-item <?php echo $task['priority']; ?>-priority">
                                        <div class="task-header">
                                            <div class="task-title">
                                                <i class="fas fa-clipboard-check" style="margin-right: 0.5rem; color: var(--primary-color);"></i>
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </div>
                                            <span class="badge-enhanced badge-enhanced-<?php echo $task['priority'] == 'high' ? 'danger' : ($task['priority'] == 'medium' ? 'warning' : 'info'); ?>">
                                                <i class="fas fa-<?php echo $task['priority'] == 'high' ? 'exclamation-triangle' : ($task['priority'] == 'medium' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                                                <?php echo ucfirst($task['priority']); ?>
                                            </span>
                                        </div>
                                        <div class="task-description">
                                            <?php echo htmlspecialchars(substr($task['description'], 0, 120)) . (strlen($task['description']) > 120 ? '...' : ''); ?>
                                        </div>
                                        <div class="task-meta">
                                            <div class="task-due-date">
                                                <i class="fas fa-calendar-alt"></i>
                                                Due: <?php echo date('M j, Y', strtotime($task['due_date'])); ?>
                                            </div>
                                            <?php
                                            $days_until_due = (strtotime($task['due_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                                            if ($days_until_due <= 3 && $days_until_due >= 0): ?>
                                                <span style="color: var(--danger-color); font-weight: 500;">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo $days_until_due == 0 ? 'Due today!' : ($days_until_due == 1 ? 'Due tomorrow' : $days_until_due . ' days left'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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
        
        function markAttendance() {
            const currentTime = new Date().toTimeString().slice(0, 5);
            if (confirm('Mark your attendance for today at ' + currentTime + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'attendance.php';
                form.innerHTML = '<input type="hidden" name="mark_attendance" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Enhanced Dashboard Interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to stat cards
            const statCards = document.querySelectorAll('.stat-card-enhanced');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Animate progress bars on scroll
            const progressBars = document.querySelectorAll('[style*="width: 0%"]');
            const observerOptions = {
                threshold: 0.5,
                rootMargin: '0px'
            };

            const progressObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const progressBar = entry.target;
                        const targetWidth = progressBar.getAttribute('data-width') || progressBar.style.width;
                        progressBar.style.width = '0%';
                        setTimeout(() => {
                            progressBar.style.width = targetWidth;
                        }, 100);
                    }
                });
            }, observerOptions);

            progressBars.forEach(bar => {
                const currentWidth = bar.style.width;
                bar.setAttribute('data-width', currentWidth);
                progressObserver.observe(bar);
            });

            // Add click feedback to cards
            const dashboardCards = document.querySelectorAll('.dashboard-card');
            dashboardCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    if (!e.target.closest('button') && !e.target.closest('a')) {
                        this.style.transform = 'scale(0.98)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }
                });
            });

            // Enhanced button interactions
            const buttons = document.querySelectorAll('.btn-enhanced');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.style.transform = 'translateX(2px)';
                    }
                });
                
                button.addEventListener('mouseleave', function() {
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.style.transform = 'translateX(0)';
                    }
                });
            });

            // Task item interactions
            const taskItems = document.querySelectorAll('.task-item');
            taskItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    const priorityBar = this.querySelector('::before');
                    if (priorityBar) {
                        this.style.setProperty('--priority-width', '4px');
                    }
                });
            });

            // Leave balance card hover effects
            const leaveCards = document.querySelectorAll('.leave-balance-card');
            leaveCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    const progressBar = this.querySelector('[style*="background: var(--gradient-success)"]');
                    if (progressBar) {
                        progressBar.style.filter = 'brightness(1.1)';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    const progressBar = this.querySelector('[style*="background: var(--gradient-success)"]');
                    if (progressBar) {
                        progressBar.style.filter = 'brightness(1)';
                    }
                });
            });

            // Smooth scroll for lists
            const scrollableLists = document.querySelectorAll('.leave-requests-list, .tasks-list');
            scrollableLists.forEach(list => {
                let isDown = false;
                let startY;
                let scrollTop;

                list.addEventListener('mousedown', (e) => {
                    isDown = true;
                    startY = e.pageY - list.offsetTop;
                    scrollTop = list.scrollTop;
                    list.style.cursor = 'grabbing';
                });

                list.addEventListener('mouseleave', () => {
                    isDown = false;
                    list.style.cursor = 'grab';
                });

                list.addEventListener('mouseup', () => {
                    isDown = false;
                    list.style.cursor = 'grab';
                });

                list.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    e.preventDefault();
                    const y = e.pageY - list.offsetTop;
                    const walk = (y - startY) * 2;
                    list.scrollTop = scrollTop - walk;
                });
            });

            // Add ripple effect to buttons
            function createRipple(event) {
                const button = event.currentTarget;
                const ripple = document.createElement('span');
                const rect = button.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = event.clientX - rect.left - size / 2;
                const y = event.clientY - rect.top - size / 2;

                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');

                button.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            }

            // Apply ripple effect to enhanced buttons
            document.querySelectorAll('.btn-enhanced').forEach(button => {
                button.addEventListener('click', createRipple);
            });

            // Live time update for check-in display
            function updateLiveTime() {
                const checkInElement = document.querySelector('.stat-value-enhanced');
                if (checkInElement && checkInElement.textContent.includes('Not marked')) {
                    const currentTime = new Date().toTimeString().slice(0, 5);
                    // Optional: Update if you want to show current time
                }
            }

            // Update time every minute
            setInterval(updateLiveTime, 60000);

            // Add notification badge animation
            const badges = document.querySelectorAll('.badge-enhanced');
            badges.forEach(badge => {
                badge.addEventListener('animationend', function() {
                    this.style.animation = '';
                });
            });

            // Initialize tooltips (if you want to add them later)
            function initializeTooltips() {
                const tooltipElements = document.querySelectorAll('[data-tooltip]');
                tooltipElements.forEach(element => {
                    element.addEventListener('mouseenter', showTooltip);
                    element.addEventListener('mouseleave', hideTooltip);
                });
            }

            function showTooltip(e) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = e.target.getAttribute('data-tooltip');
                document.body.appendChild(tooltip);

                const rect = e.target.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            }

            function hideTooltip() {
                const tooltip = document.querySelector('.tooltip');
                if (tooltip) {
                    tooltip.remove();
                }
            }

            // Add keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                    }
                }
            });

            // Performance optimization: Throttle scroll events
            let scrollTimeout;
            window.addEventListener('scroll', function() {
                if (scrollTimeout) {
                    window.cancelAnimationFrame(scrollTimeout);
                }
                scrollTimeout = window.requestAnimationFrame(function() {
                    // Handle scroll-based animations
                    handleScrollAnimations();
                });
            });

            function handleScrollAnimations() {
                const scrolled = window.pageYOffset;
                const parallaxElements = document.querySelectorAll('.stat-card-enhanced');
                
                parallaxElements.forEach((element, index) => {
                    const speed = 0.5 + (index * 0.1);
                    const yPos = -(scrolled * speed);
                    element.style.transform = `translateY(${yPos * 0.1}px)`;
                });
            }

            console.log('Enhanced Dashboard initialized successfully!');
        });

        // Add CSS for ripple effect
        const style = document.createElement('style');
        style.textContent = `
            .ripple {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.5);
                transform: scale(0);
                animation: ripple-animation 0.6s ease-out;
                pointer-events: none;
            }

            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }

            .tooltip {
                position: absolute;
                background: var(--dark-color);
                color: white;
                padding: 0.5rem 0.75rem;
                border-radius: 0.375rem;
                font-size: 0.75rem;
                z-index: 1000;
                pointer-events: none;
                opacity: 0;
                animation: tooltip-fade-in 0.2s ease forwards;
            }

            @keyframes tooltip-fade-in {
                to {
                    opacity: 1;
                }
            }

            .btn-enhanced {
                position: relative;
                overflow: hidden;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
