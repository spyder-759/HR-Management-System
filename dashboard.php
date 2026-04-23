<?php
require_once 'includes/config.php';
require_login();

// Prevent caching after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HR Management System</title>
    <link rel="stylesheet" href="assets/css/enhanced-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li>
                        <a href="dashboard.php" class="active">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="modules/recruitment/jobs.php">
                            <i class="fas fa-briefcase"></i>
                            <span>Recruitment</span>
                        </a>
                    </li>
                    <li>
                        <a href="modules/employees/employees.php">
                            <i class="fas fa-users"></i>
                            <span>Employees</span>
                        </a>
                    </li>
                    <li>
                        <a href="modules/tasks/tasks.php">
                            <i class="fas fa-tasks"></i>
                            <span>Task Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="modules/leaves/leaves.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Leave Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="modules/leaves/attendance.php">
                            <i class="fas fa-clock"></i>
                            <span>Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="modules/performance/performance.php">
                            <i class="fas fa-chart-line"></i>
                            <span>Performance</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="logout()">
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
                    <h1 style="font-size: 1.5rem; font-weight: 600;">Dashboard</h1>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 2)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Administrator</div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="content">
                <?php
                $stats = get_dashboard_stats();
                ?>
                
                <!-- Stats Cards -->
                <div class="stats-grid" style="display: flex; gap: 5rem;">
                    <div class="stat-card primary slide-in-up">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_employees'] ?? 0); ?></div>
                        <div class="stat-label">Total Employees</div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +12% from last month
                        </div>
                    </div>
                    
                    <div class="stat-card success slide-in-up" style="animation-delay: 0.1s;">
                        <div class="stat-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_candidates'] ?? 0); ?></div>
                        <div class="stat-label">Total Candidates</div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +8% from last month
                        </div>
                    </div>
                    
                    <div class="stat-card warning slide-in-up" style="animation-delay: 0.2s;">
                        <div class="stat-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['pending_tasks'] ?? 0); ?></div>
                        <div class="stat-label">Pending Tasks</div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-down"></i> -5% from yesterday
                        </div>
                    </div>
                    
                    <div class="stat-card info slide-in-up" style="animation-delay: 0.3s;">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['completed_today'] ?? 0); ?></div>
                        <div class="stat-label">Completed Today</div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +15% productivity
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="card slide-in-up" style="animation-delay: 0.4s;">
                        <div class="card-header">
                            <h3 class="card-title">Employee Growth</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="employeeGrowthChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card slide-in-up" style="animation-delay: 0.5s;">
                        <div class="card-header">
                            <h3 class="card-title">Leave Statistics</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="leaveStatsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                    <div class="card slide-in-up" style="animation-delay: 0.6s;">
                        <div class="card-header">
                            <h3 class="card-title">Recent Candidates</h3>
                            <a href="modules/recruitment/candidates.php" class="btn btn-sm btn-outline">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($stats['recent_candidates'])): ?>
                                <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No candidates yet</p>
                            <?php else: ?>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <?php foreach ($stats['recent_candidates'] as $candidate): ?>
                                        <div style="padding: 1rem 0; border-bottom: 1px solid var(--border-color);">
                                            <div style="display: flex; justify-content: between; align-items: center;">
                                                <div>
                                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></div>
                                                    <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                        <?php echo htmlspecialchars($candidate['job_title'] ?: 'No position'); ?>
                                                    </div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                        <?php echo date('M j, Y', strtotime($candidate['applied_date'])); ?>
                                                    </div>
                                                </div>
                                                <span class="badge badge-<?php echo $candidate['status'] == 'selected' ? 'success' : ($candidate['status'] == 'rejected' ? 'danger' : 'info'); ?>">
                                                    <?php echo ucfirst($candidate['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card slide-in-up" style="animation-delay: 0.7s;">
                        <div class="card-header">
                            <h3 class="card-title">Recent Tasks</h3>
                            <a href="modules/tasks/tasks.php" class="btn btn-sm btn-outline">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($stats['recent_tasks'])): ?>
                                <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No tasks assigned yet</p>
                            <?php else: ?>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <?php foreach ($stats['recent_tasks'] as $task): ?>
                                        <div style="padding: 1rem 0; border-bottom: 1px solid var(--border-color);">
                                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                                <div style="flex: 1;">
                                                    <div style="font-weight: 500; margin-bottom: 0.25rem;">
                                                        <?php echo htmlspecialchars($task['title']); ?>
                                                    </div>
                                                    <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">
                                                        <?php echo htmlspecialchars($task['first_name'] . ' ' . $task['last_name']); ?>
                                                    </div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                        Assigned <?php echo date('M j, Y', strtotime($task['created_at'])); ?>
                                                    </div>
                                                </div>
                                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
                                                    <span class="badge badge-<?php 
                                                        echo $task['status'] == 'completed' ? 'success' : 
                                                            ($task['status'] == 'in_progress' ? 'info' : 'warning'); ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                                    </span>
                                                    <span class="badge badge-<?php 
                                                        echo $task['priority'] == 'urgent' ? 'danger' : 
                                                            ($task['priority'] == 'high' ? 'warning' : 'secondary'); ?>" style="font-size: 0.75rem;">
                                                        <?php echo ucfirst($task['priority']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card slide-in-up" style="animation-delay: 0.8s;">
                        <div class="card-header">
                            <h3 class="card-title">Recent Leave Requests</h3>
                            <a href="modules/leaves/leaves.php" class="btn btn-sm btn-outline">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($stats['recent_leaves'])): ?>
                                <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No leave requests yet</p>
                            <?php else: ?>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <?php foreach ($stats['recent_leaves'] as $leave): ?>
                                        <div style="padding: 1rem 0; border-bottom: 1px solid var(--border-color);">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <div>
                                                    <div style="font-weight: 500;">
                                                        <?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?>
                                                    </div>
                                                    <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                        <?php echo htmlspecialchars($leave['leave_type']); ?> - <?php echo $leave['total_days']; ?> days
                                                    </div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                        <?php echo date('M j, Y', strtotime($leave['start_date'])); ?> - <?php echo date('M j, Y', strtotime($leave['end_date'])); ?>
                                                    </div>
                                                </div>
                                                <span class="badge badge-<?php echo $leave['status'] == 'approved' ? 'success' : ($leave['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($leave['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        }
        
        // Logout
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
        
        // Employee Growth Chart
        const employeeGrowthCtx = document.getElementById('employeeGrowthChart').getContext('2d');
        new Chart(employeeGrowthCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Employees',
                    data: [12, 19, 23, 25, 32, 38],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Leave Statistics Chart
        const leaveStatsCtx = document.getElementById('leaveStatsChart').getContext('2d');
        new Chart(leaveStatsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: [
                        '#10b981',
                        '#f59e0b',
                        '#ef4444'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
