<?php
require_once '../../includes/config.php';
require_login();

$conn = getConnection();

// Handle job operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_job'])) {
        $title = sanitize_input($_POST['title']);
        $department_id = sanitize_input($_POST['department_id']);
        $description = sanitize_input($_POST['description']);
        $requirements = sanitize_input($_POST['requirements']);
        $salary_range = sanitize_input($_POST['salary_range']);
        $location = sanitize_input($_POST['location']);
        $job_type = sanitize_input($_POST['job_type']);
        
        $stmt = $conn->prepare("INSERT INTO jobs (title, department_id, description, requirements, salary_range, location, job_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $department_id, $description, $requirements, $salary_range, $location, $job_type]);
        
        $_SESSION['success'] = "Job posted successfully!";
        header('Location: jobs.php');
        exit();
    }
    
    if (isset($_POST['update_job'])) {
        $id = sanitize_input($_POST['id']);
        $title = sanitize_input($_POST['title']);
        $department_id = sanitize_input($_POST['department_id']);
        $description = sanitize_input($_POST['description']);
        $requirements = sanitize_input($_POST['requirements']);
        $salary_range = sanitize_input($_POST['salary_range']);
        $location = sanitize_input($_POST['location']);
        $job_type = sanitize_input($_POST['job_type']);
        $status = sanitize_input($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE jobs SET title=?, department_id=?, description=?, requirements=?, salary_range=?, location=?, job_type=?, status=? WHERE id=?");
        $stmt->execute([$title, $department_id, $description, $requirements, $salary_range, $location, $job_type, $status, $id]);
        
        $_SESSION['success'] = "Job updated successfully!";
        header('Location: jobs.php');
        exit();
    }
    
    if (isset($_POST['delete_job'])) {
        $id = sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM jobs WHERE id=?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Job deleted successfully!";
        header('Location: jobs.php');
        exit();
    }
}

// Get jobs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$where = $search ? "WHERE j.title LIKE ? OR d.name LIKE ?" : "";
$params = $search ? ["%$search%", "%$search%"] : [];

$total_query = "SELECT COUNT(*) as total FROM jobs j LEFT JOIN departments d ON j.department_id = d.id $where";
$stmt = $conn->prepare($total_query);
$stmt->execute($params);
$total_jobs = $stmt->fetch()['total'];

$pagination = get_pagination($total_jobs, $per_page, $page);

$query = "SELECT j.*, d.name as department_name FROM jobs j 
          LEFT JOIN departments d ON j.department_id = d.id 
          $where 
          ORDER BY j.created_at DESC 
          LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Get departments for dropdown
$stmt = $conn->prepare("SELECT * FROM departments ORDER BY name");
$stmt->execute();
$departments = $stmt->fetchAll();

// Get job for editing
$edit_job = null;
if (isset($_GET['edit'])) {
    $id = sanitize_input($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$id]);
    $edit_job = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings - HR Management System</title>
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
                    <li><a href="jobs.php" class="active"><i class="fas fa-briefcase"></i> Recruitment</a></li>
                    <li><a href="../employees/employees.php"><i class="fas fa-users"></i> Employees</a></li>
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
                    <h1>Job Postings</h1>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-primary" onclick="showAddJobModal()">
                        <i class="fas fa-plus"></i> Post New Job
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
                
                <!-- Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-3">
                            <input type="text" class="form-control" name="search" placeholder="Search jobs..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <?php if ($search): ?>
                                <a href="jobs.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Jobs Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Job Postings (<?php echo $total_jobs; ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($jobs)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-briefcase" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <h4>No jobs found</h4>
                                <p style="color: var(--text-muted);">Start by posting your first job opening.</p>
                                <button class="btn btn-primary" onclick="showAddJobModal()">
                                    <i class="fas fa-plus"></i> Post New Job
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Job Title</th>
                                            <th>Department</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Salary Range</th>
                                            <th>Status</th>
                                            <th>Posted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jobs as $job): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($job['title']); ?></div>
                                                        <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                            <?php echo substr(htmlspecialchars($job['description']), 0, 100) . '...'; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($job['department_name'] ?: 'Not specified'); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo htmlspecialchars($job['job_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                                <td><?php echo htmlspecialchars($job['salary_range']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $job['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($job['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-outline" onclick="editJob(<?php echo $job['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteJob(<?php echo $job['id']; ?>)">
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
                                        <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <?php if ($i == $pagination['current_page']): ?>
                                            <span class="active"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                        <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
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
    
    <!-- Add/Edit Job Modal -->
    <div class="modal" id="jobModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle"><?php echo $edit_job ? 'Edit Job' : 'Post New Job'; ?></h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="jobForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="jobId" value="<?php echo $edit_job['id'] ?? ''; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Job Title *</label>
                        <input type="text" class="form-control" name="title" required 
                               value="<?php echo htmlspecialchars($edit_job['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Department *</label>
                        <select class="form-control form-select" name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo (isset($edit_job['department_id']) && $edit_job['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Job Type *</label>
                        <select class="form-control form-select" name="job_type" required>
                            <option value="full-time" <?php echo (isset($edit_job['job_type']) && $edit_job['job_type'] == 'full-time') ? 'selected' : ''; ?>>Full Time</option>
                            <option value="part-time" <?php echo (isset($edit_job['job_type']) && $edit_job['job_type'] == 'part-time') ? 'selected' : ''; ?>>Part Time</option>
                            <option value="contract" <?php echo (isset($edit_job['job_type']) && $edit_job['job_type'] == 'contract') ? 'selected' : ''; ?>>Contract</option>
                            <option value="internship" <?php echo (isset($edit_job['job_type']) && $edit_job['job_type'] == 'internship') ? 'selected' : ''; ?>>Internship</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" 
                               value="<?php echo htmlspecialchars($edit_job['location'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Salary Range</label>
                        <input type="text" class="form-control" name="salary_range" 
                               value="<?php echo htmlspecialchars($edit_job['salary_range'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($edit_job['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Requirements</label>
                        <textarea class="form-control" name="requirements" rows="4"><?php echo htmlspecialchars($edit_job['requirements'] ?? ''); ?></textarea>
                    </div>
                    
                    <?php if ($edit_job): ?>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control form-select" name="status">
                                <option value="active" <?php echo $edit_job['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $edit_job['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="<?php echo $edit_job ? 'update_job' : 'add_job'; ?>" class="btn btn-primary">
                        <?php echo $edit_job ? 'Update Job' : 'Post Job'; ?>
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
        
        function showAddJobModal() {
            document.getElementById('modalTitle').textContent = 'Post New Job';
            document.getElementById('jobForm').reset();
            document.getElementById('jobId').value = '';
            document.getElementById('jobForm').querySelector('button[type="submit"]').name = 'add_job';
            document.getElementById('jobForm').querySelector('button[type="submit"]').textContent = 'Post Job';
            
            // Remove status field for new jobs
            const statusField = document.querySelector('select[name="status"]');
            if (statusField) {
                statusField.closest('.form-group').remove();
            }
            
            document.getElementById('jobModal').classList.add('show');
        }
        
        function editJob(id) {
            window.location.href = '?edit=' + id;
        }
        
        function deleteJob(id) {
            if (confirm('Are you sure you want to delete this job posting?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_job" value="1"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal() {
            document.getElementById('jobModal').classList.remove('show');
        }
        
        // Show edit modal if editing
        <?php if ($edit_job): ?>
            document.getElementById('jobModal').classList.add('show');
        <?php endif; ?>
    </script>
</body>
</html>
