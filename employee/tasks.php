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

// Handle task completion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['complete_task'])) {
        $task_id = sanitize_input($_POST['task_id']);
        
        $stmt = $conn->prepare("UPDATE tasks SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$task_id, $employee_id]);
        
        $_SESSION['success'] = "Task marked as completed!";
        header('Location: tasks.php');
        exit();
    }
    
    if (isset($_POST['update_task_status'])) {
        $task_id = sanitize_input($_POST['task_id']);
        $status = sanitize_input($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$status, $task_id, $employee_id]);
        
        $_SESSION['success'] = "Task status updated!";
        header('Location: tasks.php');
        exit();
    }
}

// Get tasks
$stmt = $conn->prepare("SELECT t.*, a.username as admin_name FROM tasks t LEFT JOIN admins a ON t.assigned_by = a.id WHERE t.assigned_to = ? ORDER BY t.priority DESC, t.due_date ASC, t.created_at DESC");
$stmt->execute([$employee_id]);
$tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - Employee Portal</title>
    <link rel="stylesheet" href="../assets/css/enhanced-style.css">
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
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="attendance.php"><i class="fas fa-clock"></i> Attendance</a></li>
                    <li><a href="leave.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></li>
                    <li><a href="tasks.php" class="active"><i class="fas fa-tasks"></i> Tasks</a></li>
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
                    <h1>My Tasks</h1>
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
                
                <!-- Task Statistics -->
                <div class="stats-grid mb-4" style="display: flex; gap: 5rem;">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-value">
                            <?php 
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = ? AND status IN ('pending', 'in_progress')");
                            $stmt->execute([$employee_id]);
                            echo $stmt->fetch()['count'];
                            ?>
                        </div>
                        <div class="stat-label">Active Tasks</div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value">
                            <?php 
                            $pending_count = 0;
                            foreach ($tasks as $task) {
                                if ($task['status'] == 'pending') $pending_count++;
                            }
                            echo $pending_count;
                            ?>
                        </div>
                        <div class="stat-label">Pending Tasks</div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-value">
                            <?php 
                            $overdue_count = 0;
                            foreach ($tasks as $task) {
                                if ($task['status'] != 'completed' && $task['due_date'] && $task['due_date'] < date('Y-m-d')) $overdue_count++;
                            }
                            echo $overdue_count;
                            ?>
                        </div>
                        <div class="stat-label">Overdue Tasks</div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value">
                            <?php 
                            $completed_count = 0;
                            foreach ($tasks as $task) {
                                if ($task['status'] == 'completed') $completed_count++;
                            }
                            echo $completed_count;
                            ?>
                        </div>
                        <div class="stat-label">Completed Tasks</div>
                    </div>
                </div>
                
                <!-- Tasks List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Task List</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tasks)): ?>
                            <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">
                                <i class="fas fa-tasks" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                                No tasks assigned to you yet.
                            </p>
                        <?php else: ?>
                            <div style="display: grid; gap: 1rem;">
                                <?php foreach ($tasks as $task): ?>
                                    <div class="task-card" style="border-left: 4px solid <?php echo $task['priority'] == 'high' ? 'var(--danger-color)' : ($task['priority'] == 'medium' ? 'var(--warning-color)' : 'var(--info-color)'); ?>;">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                            <div>
                                                <h4 style="margin: 0 0 0.5rem 0; color: var(--dark-color);">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </h4>
                                                <p style="margin: 0 0 1rem 0; color: var(--text-muted); line-height: 1.5;">
                                                    <?php echo htmlspecialchars($task['description']); ?>
                                                </p>
                                            </div>
                                            <div style="text-align: right;">
                                                <?php if ($task['status'] == 'completed'): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> Completed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-<?php echo $task['priority'] == 'high' ? 'danger' : ($task['priority'] == 'medium' ? 'warning' : 'info'); ?>">
                                                        <?php echo ucfirst($task['priority']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.875rem;">
                                            <div>
                                                <i class="fas fa-calendar"></i>
                                                <strong>Due Date:</strong> 
                                                <?php 
                                                $due_date = new DateTime($task['due_date']);
                                                $today = new DateTime();
                                                $is_overdue = $due_date < $today;
                                                echo date('M j, Y', strtotime($task['due_date'])) . ' ';
                                                if ($is_overdue && $task['status'] != 'completed') {
                                                    echo '<span style="color: var(--danger-color); font-weight: 600;">(Overdue)</span>';
                                                }
                                                ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-user"></i>
                                                <strong>Assigned by:</strong> 
                                                <?php echo htmlspecialchars($task['admin_name'] ?: 'Admin'); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($task['status'] != 'completed'): ?>
                                            <div style="margin-top: 1rem; text-align: right;">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                    <button type="submit" name="complete_task" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i> Mark Complete
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <div style="margin-top: 1rem; text-align: right;">
                                                <small style="color: var(--success-color);">
                                                    <i class="fas fa-check-circle"></i> 
                                                    Completed on <?php echo $task['completed_at'] ? date('M j, Y', strtotime($task['completed_at'])) : 'Recently'; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
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
    </script>
    
    <style>
        .task-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
    </style>
</body>
</html>
