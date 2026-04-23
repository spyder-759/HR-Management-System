<?php
require_once '../../includes/config.php';
require_login();

// Prevent caching after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$conn = getConnection();

// Handle task operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_task'])) {
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $assigned_to = sanitize_input($_POST['assigned_to']);
        $priority = sanitize_input($_POST['priority']);
        $due_date = sanitize_input($_POST['due_date']) ?: null;
        
        $stmt = $conn->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, priority, due_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $assigned_to, $_SESSION['admin_id'], $priority, $due_date]);
        
        $_SESSION['success'] = "Task assigned successfully!";
        header('Location: tasks.php');
        exit();
    }
    
    if (isset($_POST['update_task'])) {
        $id = sanitize_input($_POST['id']);
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $assigned_to = sanitize_input($_POST['assigned_to']);
        $priority = sanitize_input($_POST['priority']);
        $due_date = sanitize_input($_POST['due_date']) ?: null;
        $status = sanitize_input($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE tasks SET title=?, description=?, assigned_to=?, priority=?, due_date=?, status=? WHERE id=?");
        $stmt->execute([$title, $description, $assigned_to, $priority, $due_date, $status, $id]);
        
        $_SESSION['success'] = "Task updated successfully!";
        header('Location: tasks.php');
        exit();
    }
    
    if (isset($_POST['delete_task'])) {
        $id = sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id=?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Task deleted successfully!";
        header('Location: tasks.php');
        exit();
    }
}

// Get tasks with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$priority_filter = isset($_GET['priority']) ? sanitize_input($_GET['priority']) : '';
$employee_filter = isset($_GET['employee']) ? sanitize_input($_GET['employee']) : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(t.title LIKE ? OR t.description LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%"]);
}

if ($status_filter) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter) {
    $where_conditions[] = "t.priority = ?";
    $params[] = $priority_filter;
}

if ($employee_filter) {
    $where_conditions[] = "t.assigned_to = ?";
    $params[] = $employee_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$total_query = "SELECT COUNT(*) as total FROM tasks t $where_clause";
$stmt = $conn->prepare($total_query);
$stmt->execute($params);
$total_tasks = $stmt->fetch()['total'];

$pagination = get_pagination($total_tasks, $per_page, $page);

$query = "SELECT t.*, e.first_name, e.last_name, e.employee_id, a.username as admin_name 
          FROM tasks t 
          LEFT JOIN employees e ON t.assigned_to = e.id 
          LEFT JOIN admins a ON t.assigned_by = a.id 
          $where_clause 
          ORDER BY t.created_at DESC 
          LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Get employees for dropdown
$stmt = $conn->prepare("SELECT id, first_name, last_name, employee_id FROM employees WHERE status = 'active' ORDER BY first_name, last_name");
$stmt->execute();
$employees = $stmt->fetchAll();

// Get task for editing
$edit_task = null;
if (isset($_GET['edit'])) {
    $id = sanitize_input($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $edit_task = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - HR Management System</title>
    <link rel="stylesheet" href="../../assets/css/enhanced-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-tasks"></i>
                    <span>HR System</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="sidebar-menu">
                    <li><a href="../../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="../recruitment/jobs.php"><i class="fas fa-briefcase"></i> Recruitment</a></li>
                    <li><a href="../employees/employees.php"><i class="fas fa-users"></i> Employees</a></li>
                    <li><a href="tasks.php" class="active"><i class="fas fa-tasks"></i> Task Management</a></li>
                    <li><a href="../leaves/leaves.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></li>
                    <li><a href="../leaves/attendance.php"><i class="fas fa-clock"></i> Attendance</a></li>
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
                    <h1>Task Management</h1>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-primary" onclick="showAddTaskModal()">
                        <i class="fas fa-plus"></i> Assign Task
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
                
                <!-- Search and Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-3 flex-wrap">
                            <input type="text" class="form-control" name="search" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search); ?>">
                            
                            <select class="form-control form-select" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            
                            <select class="form-control form-select" name="priority">
                                <option value="">All Priority</option>
                                <option value="low" <?php echo $priority_filter == 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo $priority_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo $priority_filter == 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?php echo $priority_filter == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                            
                            <select class="form-control form-select" name="employee">
                                <option value="">All Employees</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo $employee_filter == $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            
                            <?php if ($search || $status_filter || $priority_filter || $employee_filter): ?>
                                <a href="tasks.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Tasks Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tasks (<?php echo $total_tasks; ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tasks)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-tasks" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <h4>No tasks found</h4>
                                <p style="color: var(--text-muted);">Start by assigning your first task.</p>
                                <button class="btn btn-primary" onclick="showAddTaskModal()">
                                    <i class="fas fa-plus"></i> Assign Task
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Assigned To</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Due Date</th>
                                            <th>Assigned By</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tasks as $task): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 500;">
                                                        <?php echo htmlspecialchars($task['title']); ?>
                                                    </div>
                                                    <?php if ($task['description']): ?>
                                                        <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                            <?php echo htmlspecialchars(substr($task['description'], 0, 100)) . (strlen($task['description']) > 100 ? '...' : ''); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($task['first_name']): ?>
                                                        <div>
                                                            <?php echo htmlspecialchars($task['first_name'] . ' ' . $task['last_name']); ?>
                                                        </div>
                                                        <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                            <?php echo htmlspecialchars($task['employee_id']); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span style="color: var(--danger-color);">Employee not found</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $task['priority'] == 'urgent' ? 'danger' : 
                                                            ($task['priority'] == 'high' ? 'warning' : 
                                                            ($task['priority'] == 'medium' ? 'info' : 'secondary')); ?>">
                                                        <?php echo ucfirst($task['priority']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $task['status'] == 'completed' ? 'success' : 
                                                            ($task['status'] == 'in_progress' ? 'info' : 
                                                            ($task['status'] == 'cancelled' ? 'danger' : 'warning')); ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'No due date'; ?>
                                                    <?php 
                                                        if ($task['due_date'] && $task['status'] != 'completed') {
                                                            $due_date = new DateTime($task['due_date']);
                                                            $today = new DateTime();
                                                            $days_left = $today->diff($due_date)->days;
                                                            if ($due_date < $today) {
                                                                echo '<br><span style="color: var(--danger-color); font-size: 0.75rem;">Overdue</span>';
                                                            } elseif ($days_left <= 2) {
                                                                echo '<br><span style="color: var(--warning-color); font-size: 0.75rem;">Due soon</span>';
                                                            }
                                                        }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($task['admin_name']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($task['created_at'])); ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-outline" onclick="editTask(<?php echo $task['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteTask(<?php echo $task['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($pagination['total_pages'] > 1): ?>
                                <div class="pagination">
                                    <?php if ($pagination['current_page'] > 1): ?>
                                        <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $priority_filter ? '&priority=' . urlencode($priority_filter) : ''; ?><?php echo $employee_filter ? '&employee=' . urlencode($employee_filter) : ''; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <?php if ($i == $pagination['current_page']): ?>
                                            <span class="active"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $priority_filter ? '&priority=' . urlencode($priority_filter) : ''; ?><?php echo $employee_filter ? '&employee=' . urlencode($employee_filter) : ''; ?>"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                        <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $priority_filter ? '&priority=' . urlencode($priority_filter) : ''; ?><?php echo $employee_filter ? '&employee=' . urlencode($employee_filter) : ''; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Task Modal -->
    <div class="modal" id="taskModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle"><?php echo $edit_task ? 'Edit Task' : 'Assign New Task'; ?></h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="taskForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="taskId" value="<?php echo $edit_task['id'] ?? ''; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Task Title *</label>
                        <input type="text" class="form-control" name="title" required 
                               value="<?php echo htmlspecialchars($edit_task['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($edit_task['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Assign To *</label>
                        <select class="form-control form-select" name="assigned_to" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" <?php echo (isset($edit_task['assigned_to']) && $edit_task['assigned_to'] == $emp['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Priority *</label>
                        <select class="form-control form-select" name="priority" required>
                            <option value="low" <?php echo (isset($edit_task['priority']) && $edit_task['priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo (isset($edit_task['priority']) && $edit_task['priority'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo (isset($edit_task['priority']) && $edit_task['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                            <option value="urgent" <?php echo (isset($edit_task['priority']) && $edit_task['priority'] == 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="due_date" 
                               value="<?php echo htmlspecialchars($edit_task['due_date'] ?? ''); ?>">
                    </div>
                    
                    <?php if ($edit_task): ?>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control form-select" name="status">
                                <option value="pending" <?php echo $edit_task['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $edit_task['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $edit_task['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $edit_task['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="<?php echo $edit_task ? 'update_task' : 'add_task'; ?>" class="btn btn-primary">
                        <?php echo $edit_task ? 'Update Task' : 'Assign Task'; ?>
                    </button>
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
        
        function showAddTaskModal() {
            document.getElementById('modalTitle').textContent = 'Assign New Task';
            document.getElementById('taskForm').reset();
            document.getElementById('taskId').value = '';
            document.getElementById('taskForm').querySelector('button[type="submit"]').name = 'add_task';
            document.getElementById('taskForm').querySelector('button[type="submit"]').textContent = 'Assign Task';
            document.getElementById('taskModal').classList.add('show');
        }
        
        function editTask(id) {
            window.location.href = '?edit=' + id;
        }
        
        function deleteTask(id) {
            if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_task" value="1"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal() {
            document.getElementById('taskModal').classList.remove('show');
            window.location.href = 'tasks.php';
        }
        
        // Show edit modal if editing
        <?php if ($edit_task): ?>
            document.getElementById('taskModal').classList.add('show');
        <?php endif; ?>
    </script>
</body>
</html>
