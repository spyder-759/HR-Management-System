<?php
require_once '../../includes/config.php';
require_login();

// Prevent caching after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$conn = getConnection();

// Handle employee operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_employee'])) {
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $department_id = sanitize_input($_POST['department_id']);
        $role = sanitize_input($_POST['role']);
        $salary = sanitize_input($_POST['salary']);
        $joining_date = sanitize_input($_POST['joining_date']);
        $candidate_id = sanitize_input($_POST['candidate_id']) ?: null;
        
        // Generate unique employee ID
        $employee_id = generate_employee_id();
        
        // Ensure employee_id is unique
        do {
            $employee_id = generate_employee_id();
            $stmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
            $stmt->execute([$employee_id]);
            $exists = $stmt->fetch();
        } while ($exists);
        
        // Generate default password for employee (emp123) and hash it
        $default_password = 'emp123';
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO employees (employee_id, first_name, last_name, email, password, phone, department_id, role, salary, joining_date, candidate_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$employee_id, $first_name, $last_name, $email, $hashed_password, $phone, $department_id, $role, $salary, $joining_date, $candidate_id]);
        
        // Get the newly inserted employee ID
        $new_employee_id = $conn->lastInsertId();
        
        // Initialize leave balance for the new employee
        initialize_employee_leave_balance($new_employee_id);
        
        $_SESSION['success'] = "Employee added successfully with ID: $employee_id. Default password: emp123. Leave balance has been initialized.";
        header('Location: employees.php');
        exit();
    }
    
    if (isset($_POST['update_employee'])) {
        $id = sanitize_input($_POST['id']);
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $department_id = sanitize_input($_POST['department_id']);
        $role = sanitize_input($_POST['role']);
        $salary = sanitize_input($_POST['salary']);
        $status = sanitize_input($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE employees SET first_name=?, last_name=?, email=?, phone=?, department_id=?, role=?, salary=?, status=? WHERE id=?");
        $stmt->execute([$first_name, $last_name, $email, $phone, $department_id, $role, $salary, $status, $id]);
        
        $_SESSION['success'] = "Employee updated successfully!";
        header('Location: employees.php');
        exit();
    }
    
    if (isset($_POST['delete_employee'])) {
        $id = sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM employees WHERE id=?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Employee deleted successfully!";
        header('Location: employees.php');
        exit();
    }
}

// Get employees with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$department_filter = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ? OR e.employee_id LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

if ($department_filter) {
    $where_conditions[] = "e.department_id = ?";
    $params[] = $department_filter;
}

if ($status_filter) {
    $where_conditions[] = "e.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$total_query = "SELECT COUNT(*) as total FROM employees e $where_clause";
$stmt = $conn->prepare($total_query);
$stmt->execute($params);
$total_employees = $stmt->fetch()['total'];

$pagination = get_pagination($total_employees, $per_page, $page);

$query = "SELECT e.*, d.name as department_name FROM employees e 
          LEFT JOIN departments d ON e.department_id = d.id 
          $where_clause 
          ORDER BY e.joining_date DESC 
          LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Get departments for dropdown
$stmt = $conn->prepare("SELECT * FROM departments ORDER BY name");
$stmt->execute();
$departments = $stmt->fetchAll();

// Get selected candidates for onboarding
$stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM candidates WHERE status = 'selected' ORDER BY applied_date DESC");
$stmt->execute();
$selected_candidates = $stmt->fetchAll();

// Get employee for editing
$edit_employee = null;
if (isset($_GET['edit'])) {
    $id = sanitize_input($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $edit_employee = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - HR Management System</title>
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
                    <i class="fas fa-users-cog"></i>
                    <span>HR System</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="sidebar-menu">
                    <li><a href="../../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="../recruitment/jobs.php"><i class="fas fa-briefcase"></i> Recruitment</a></li>
                    <li><a href="employees.php" class="active"><i class="fas fa-users"></i> Employees</a></li>
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
                    <h1>Employees</h1>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-primary" onclick="showAddEmployeeModal()">
                        <i class="fas fa-user-plus"></i> Add Employee
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
                        <form method="GET" class="d-flex gap-3">
                            <input type="text" class="form-control" name="search" placeholder="Search employees..." value="<?php echo htmlspecialchars($search); ?>">
                            
                            <select class="form-control form-select" name="department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select class="form-control form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="terminated" <?php echo $status_filter == 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                            </select>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            
                            <?php if ($search || $department_filter || $status_filter): ?>
                                <a href="employees.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Employees Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Employees (<?php echo $total_employees; ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($employees)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <h4>No employees found</h4>
                                <p style="color: var(--text-muted);">Start by adding your first employee.</p>
                                <button class="btn btn-primary" onclick="showAddEmployeeModal()">
                                    <i class="fas fa-user-plus"></i> Add Employee
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Department</th>
                                            <th>Role</th>
                                            <th>Salary</th>
                                            <th>Status</th>
                                            <th>Joining Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employees as $employee): ?>
                                            <tr>
                                                <td>
                                                    <span style="font-weight: 600; color: var(--primary-color);">
                                                        <?php echo htmlspecialchars($employee['employee_id']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 500;">
                                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['phone'] ?: 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($employee['department_name'] ?: 'Not assigned'); ?></td>
                                                <td><?php echo htmlspecialchars($employee['role']); ?></td>
                                                <td><?php echo $employee['salary'] ? '$' . number_format($employee['salary'], 2) : 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $employee['status'] == 'active' ? 'success' : 
                                                            ($employee['status'] == 'inactive' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($employee['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($employee['joining_date'])); ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-outline" onclick="editEmployee(<?php echo $employee['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteEmployee(<?php echo $employee['id']; ?>)">
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
                                        <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . urlencode($department_filter) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <?php if ($i == $pagination['current_page']): ?>
                                            <span class="active"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . urlencode($department_filter) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                        <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . urlencode($department_filter) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
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
    
    <!-- Add/Edit Employee Modal -->
    <div class="modal" id="employeeModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle"><?php echo $edit_employee ? 'Edit Employee' : 'Add New Employee'; ?></h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="employeeForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="employeeId" value="<?php echo $edit_employee['id'] ?? ''; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-control" name="first_name" required 
                               value="<?php echo htmlspecialchars($edit_employee['first_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-control" name="last_name" required 
                               value="<?php echo htmlspecialchars($edit_employee['last_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required 
                               value="<?php echo htmlspecialchars($edit_employee['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" 
                               value="<?php echo htmlspecialchars($edit_employee['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Department *</label>
                        <select class="form-control form-select" name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo (isset($edit_employee['department_id']) && $edit_employee['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <input type="text" class="form-control" name="role" required 
                               value="<?php echo htmlspecialchars($edit_employee['role'] ?? ''); ?>"
                               placeholder="e.g., Software Developer, HR Manager">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Salary</label>
                        <input type="number" class="form-control" name="salary" step="0.01" 
                               value="<?php echo htmlspecialchars($edit_employee['salary'] ?? ''); ?>"
                               placeholder="Annual salary">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Joining Date *</label>
                        <input type="date" class="form-control" name="joining_date" required 
                               value="<?php echo htmlspecialchars($edit_employee['joining_date'] ?? date('Y-m-d')); ?>">
                    </div>
                    
                    <?php if (!$edit_employee && !empty($selected_candidates)): ?>
                        <div class="form-group">
                            <label class="form-label">Onboard from Selected Candidates</label>
                            <select class="form-control form-select" name="candidate_id" id="candidateSelect">
                                <option value="">-- Select Candidate (Optional) --</option>
                                <?php foreach ($selected_candidates as $candidate): ?>
                                    <option value="<?php echo $candidate['id']; ?>">
                                        <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name'] . ' - ' . $candidate['email']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($edit_employee): ?>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control form-select" name="status">
                                <option value="active" <?php echo $edit_employee['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $edit_employee['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="terminated" <?php echo $edit_employee['status'] == 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="<?php echo $edit_employee ? 'update_employee' : 'add_employee'; ?>" class="btn btn-primary">
                        <?php echo $edit_employee ? 'Update Employee' : 'Add Employee'; ?>
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
        
        function showAddEmployeeModal() {
            document.getElementById('modalTitle').textContent = 'Add New Employee';
            document.getElementById('employeeForm').reset();
            document.getElementById('employeeId').value = '';
            document.getElementById('employeeForm').querySelector('button[type="submit"]').name = 'add_employee';
            document.getElementById('employeeForm').querySelector('button[type="submit"]').textContent = 'Add Employee';
            document.getElementById('employeeModal').classList.add('show');
        }
        
        function editEmployee(id) {
            window.location.href = '?edit=' + id;
        }
        
        function deleteEmployee(id) {
            if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_employee" value="1"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal() {
            document.getElementById('employeeModal').classList.remove('show');
            window.location.href = 'employees.php';
        }
        
        // Auto-fill form when candidate is selected
        document.getElementById('candidateSelect')?.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const text = selectedOption.text;
            
            if (this.value) {
                // Parse candidate info from option text
                const parts = text.split(' - ');
                const nameParts = parts[0].split(' ');
                const firstName = nameParts[0];
                const lastName = nameParts.slice(1).join(' ');
                const email = parts[1];
                
                // Fill form fields
                document.querySelector('input[name="first_name"]').value = firstName;
                document.querySelector('input[name="last_name"]').value = lastName;
                document.querySelector('input[name="email"]').value = email;
            }
        });
        
        // Show edit modal if editing
        <?php if ($edit_employee): ?>
            document.getElementById('employeeModal').classList.add('show');
        <?php endif; ?>
    </script>
</body>
</html>
