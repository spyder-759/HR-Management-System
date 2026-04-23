<?php
require_once '../../includes/config.php';
require_login();

// Prevent caching after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$conn = getConnection();

// Handle AJAX request for leave balance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['get_balance'])) {
    $employee_id = sanitize_input($_POST['employee_id']);
    $leave_type_id = sanitize_input($_POST['leave_type_id']);
    $current_year = date('Y');
    
    $stmt = $conn->prepare("SELECT total_allocated, total_used FROM leave_balance 
                            WHERE employee_id = ? AND leave_type_id = ? AND year = ?");
    $stmt->execute([$employee_id, $leave_type_id, $current_year]);
    $balance = $stmt->fetch();
    
    if ($balance) {
        $available = $balance['total_allocated'] - $balance['total_used'];
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'available' => $available]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false]);
    }
    exit();
}

// Handle leave operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_leave'])) {
        $employee_id = sanitize_input($_POST['employee_id']);
        $leave_type_id = sanitize_input($_POST['leave_type_id']);
        $start_date = sanitize_input($_POST['start_date']);
        $end_date = sanitize_input($_POST['end_date']);
        $reason = sanitize_input($_POST['reason']);
        
        // Calculate total days
        $total_days = calculate_days($start_date, $end_date);
        
        // Check leave balance
        $current_year = date('Y');
        $stmt = $conn->prepare("SELECT total_allocated, total_used FROM leave_balance 
                                WHERE employee_id = ? AND leave_type_id = ? AND year = ?");
        $stmt->execute([$employee_id, $leave_type_id, $current_year]);
        $balance = $stmt->fetch();
        
        if ($balance && $balance['total_used'] + $total_days > $balance['total_allocated']) {
            $_SESSION['error'] = "Insufficient leave balance!";
            header('Location: leaves.php');
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO leaves (employee_id, leave_type_id, start_date, end_date, total_days, reason) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$employee_id, $leave_type_id, $start_date, $end_date, $total_days, $reason]);
        
        $_SESSION['success'] = "Leave request submitted successfully!";
        header('Location: leaves.php');
        exit();
    }
    
    if (isset($_POST['update_leave_status'])) {
        $id = sanitize_input($_POST['id']);
        $status = sanitize_input($_POST['status']);
        $admin_id = $_SESSION['admin_id'];
        
        $stmt = $conn->prepare("UPDATE leaves SET status=?, approved_by=?, approved_date=NOW() WHERE id=?");
        $stmt->execute([$status, $admin_id, $id]);
        
        $_SESSION['success'] = "Leave request " . $status . " successfully!";
        header('Location: leaves.php');
        exit();
    }
    
    if (isset($_POST['delete_leave'])) {
        $id = sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM leaves WHERE id=?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Leave request deleted successfully!";
        header('Location: leaves.php');
        exit();
    }
}

// Get leaves with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ? OR lt.name LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

if ($status_filter) {
    $where_conditions[] = "l.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$total_query = "SELECT COUNT(*) as total FROM leaves l 
                JOIN employees e ON l.employee_id = e.id 
                JOIN leave_types lt ON l.leave_type_id = lt.id 
                $where_clause";
$stmt = $conn->prepare($total_query);
$stmt->execute($params);
$total_leaves = $stmt->fetch()['total'];

$pagination = get_pagination($total_leaves, $per_page, $page);

$query = "SELECT l.*, e.first_name, e.last_name, e.employee_id, lt.name as leave_type, 
          a.full_name as approved_by_name 
          FROM leaves l 
          JOIN employees e ON l.employee_id = e.id 
          JOIN leave_types lt ON l.leave_type_id = lt.id 
          LEFT JOIN admins a ON l.approved_by = a.id 
          $where_clause 
          ORDER BY l.created_at DESC 
          LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$leaves = $stmt->fetchAll();

// Get employees for dropdown
$stmt = $conn->prepare("SELECT id, first_name, last_name, employee_id FROM employees WHERE status = 'active' ORDER BY first_name, last_name");
$stmt->execute();
$employees = $stmt->fetchAll();

// Get leave types for dropdown
$stmt = $conn->prepare("SELECT * FROM leave_types ORDER BY name");
$stmt->execute();
$leave_types = $stmt->fetchAll();

// Get leave statistics
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM leaves GROUP BY status");
$stmt->execute();
$leave_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - HR Management System</title>
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
                    <li><a href="leaves.php" class="active"><i class="fas fa-calendar-alt"></i> Leave Management</a></li>
                    <li><a href="attendance.php"><i class="fas fa-clock"></i> Attendance</a></li>
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
                    <h1>Leave Management</h1>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-primary" onclick="showAddLeaveModal()">
                        <i class="fas fa-plus"></i> Leave Request
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
                
                <!-- Statistics Cards -->
                <div class="stats-grid" style="display: flex; gap: 5rem;">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_leaves); ?></div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($leave_stats['pending'] ?? 0); ?></div>
                        <div class="stat-label">Pending Approval</div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($leave_stats['approved'] ?? 0); ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($leave_stats['rejected'] ?? 0); ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>
                
                <!-- Search and Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-3">
                            <input type="text" class="form-control" name="search" placeholder="Search leaves..." value="<?php echo htmlspecialchars($search); ?>">
                            
                            <select class="form-control form-select" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            
                            <?php if ($search || $status_filter): ?>
                                <a href="leaves.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Leaves Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Leave Requests (<?php echo $total_leaves; ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($leaves)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-alt" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <h4>No leave requests found</h4>
                                <p style="color: var(--text-muted);">Start by adding your first leave request.</p>
                                <button class="btn btn-primary" onclick="showAddLeaveModal()">
                                    <i class="fas fa-plus"></i> Leave Request
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Leave Type</th>
                                            <th>Duration</th>
                                            <th>Total Days</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Applied Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leaves as $leave): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <div style="font-weight: 500;">
                                                            <?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?>
                                                        </div>
                                                        <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                            <?php echo htmlspecialchars($leave['employee_id']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo htmlspecialchars($leave['leave_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div><?php echo date('M j, Y', strtotime($leave['start_date'])); ?></div>
                                                        <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                            to <?php echo date('M j, Y', strtotime($leave['end_date'])); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span style="font-weight: 600;"><?php echo $leave['total_days']; ?></span> day(s)
                                                </td>
                                                <td>
                                                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                                         title="<?php echo htmlspecialchars($leave['reason']); ?>">
                                                        <?php echo htmlspecialchars($leave['reason'] ?: 'N/A'); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $leave['status'] == 'approved' ? 'success' : 
                                                            ($leave['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($leave['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($leave['created_at'])); ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <?php if ($leave['status'] == 'pending'): ?>
                                                            <button class="btn btn-sm btn-success" onclick="updateLeaveStatus(<?php echo $leave['id']; ?>, 'approved')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="updateLeaveStatus(<?php echo $leave['id']; ?>, 'rejected')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-sm btn-outline" onclick="deleteLeave(<?php echo $leave['id']; ?>)">
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
                                        <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <?php if ($i == $pagination['current_page']): ?>
                                            <span class="active"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                        <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
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
    
    <!-- Add Leave Modal -->
    <div class="modal" id="addLeaveModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Leave Request</h3>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" id="leaveForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Employee *</label>
                        <select class="form-control form-select" name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Leave Type *</label>
                        <select class="form-control form-select" name="leave_type_id" required>
                            <option value="">Select Leave Type</option>
                            <?php foreach ($leave_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>">
                                    <?php echo htmlspecialchars($type['name']); ?>
                                    <?php if ($type['max_days_per_year'] > 0): ?>
                                        (Max: <?php echo $type['max_days_per_year']; ?> days/year)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="date" class="form-control" name="start_date" required id="startDate">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">End Date *</label>
                        <input type="date" class="form-control" name="end_date" required id="endDate">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Total Days</label>
                        <input type="text" class="form-control" id="totalDays" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" name="reason" rows="4" placeholder="Please provide a reason for the leave request..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" name="add_leave" class="btn btn-primary">Submit Request</button>
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
        
        function showAddLeaveModal() {
            document.getElementById('addLeaveModal').classList.add('show');
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('startDate').min = today;
            document.getElementById('endDate').min = today;
        }
        
        function closeAddModal() {
            document.getElementById('addLeaveModal').classList.remove('show');
            document.getElementById('leaveForm').reset();
            document.getElementById('totalDays').value = '';
        }
        
        function updateLeaveStatus(id, status) {
            if (confirm(`Are you sure you want to ${status} this leave request?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="update_leave_status" value="1">
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="status" value="${status}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteLeave(id) {
            if (confirm('Are you sure you want to delete this leave request?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_leave" value="1"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Calculate total days when dates change
        function calculateDays() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (end >= start) {
                    const diffTime = Math.abs(end - start);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    document.getElementById('totalDays').value = diffDays + ' day(s)';
                } else {
                    document.getElementById('totalDays').value = 'Invalid date range';
                }
            }
        }
        
        document.getElementById('startDate').addEventListener('change', calculateDays);
        document.getElementById('endDate').addEventListener('change', calculateDays);
        
        // Set minimum end date when start date changes
        document.getElementById('startDate').addEventListener('change', function() {
            document.getElementById('endDate').min = this.value;
            if (document.getElementById('endDate').value < this.value) {
                document.getElementById('endDate').value = this.value;
            }
            calculateDays();
        });
    </script>
</body>
</html>
