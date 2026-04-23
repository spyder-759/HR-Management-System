<?php
require_once '../../includes/config.php';
require_login();

$conn = getConnection();

// Handle candidate operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_candidate'])) {
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $job_id = sanitize_input($_POST['job_id']);
        
        // Handle resume upload
        $resume_path = null;
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
            $upload_result = upload_file($_FILES['resume'], ['pdf', 'doc', 'docx']);
            if (!$upload_result['success']) {
                $_SESSION['error'] = $upload_result['message'];
                header('Location: candidates.php');
                exit();
            }
            $resume_path = $upload_result['file_path'];
        }
        
        $stmt = $conn->prepare("INSERT INTO candidates (first_name, last_name, email, phone, job_id, resume_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $email, $phone, $job_id, $resume_path]);
        
        $_SESSION['success'] = "Candidate added successfully!";
        header('Location: candidates.php');
        exit();
    }
    
    if (isset($_POST['update_status'])) {
        $id = sanitize_input($_POST['id']);
        $status = sanitize_input($_POST['status']);
        $notes = sanitize_input($_POST['notes']);
        
        $stmt = $conn->prepare("UPDATE candidates SET status=?, notes=? WHERE id=?");
        $stmt->execute([$status, $notes, $id]);
        
        $_SESSION['success'] = "Candidate status updated successfully!";
        header('Location: candidates.php');
        exit();
    }
    
    if (isset($_POST['delete_candidate'])) {
        $id = sanitize_input($_POST['id']);
        
        // Get resume path to delete file
        $stmt = $conn->prepare("SELECT resume_path FROM candidates WHERE id=?");
        $stmt->execute([$id]);
        $candidate = $stmt->fetch();
        
        if ($candidate && $candidate['resume_path'] && file_exists($candidate['resume_path'])) {
            unlink($candidate['resume_path']);
        }
        
        $stmt = $conn->prepare("DELETE FROM candidates WHERE id=?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Candidate deleted successfully!";
        header('Location: candidates.php');
        exit();
    }
}

// Get candidates with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if ($status_filter) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$total_query = "SELECT COUNT(*) as total FROM candidates c $where_clause";
$stmt = $conn->prepare($total_query);
$stmt->execute($params);
$total_candidates = $stmt->fetch()['total'];

$pagination = get_pagination($total_candidates, $per_page, $page);

$query = "SELECT c.*, j.title as job_title FROM candidates c 
          LEFT JOIN jobs j ON c.job_id = j.id 
          $where_clause 
          ORDER BY c.applied_date DESC 
          LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$candidates = $stmt->fetchAll();

// Get jobs for dropdown
$stmt = $conn->prepare("SELECT id, title FROM jobs WHERE status = 'active' ORDER BY title");
$stmt->execute();
$jobs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates - HR Management System</title>
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
                    <li><a href="jobs.php"><i class="fas fa-briefcase"></i> Job Postings</a></li>
                    <li><a href="candidates.php" class="active"><i class="fas fa-user-tie"></i> Candidates</a></li>
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
                    <h1>Candidates</h1>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-primary" onclick="showAddCandidateModal()">
                        <i class="fas fa-plus"></i> Add Candidate
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
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Search and Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-3">
                            <input type="text" class="form-control" name="search" placeholder="Search candidates..." value="<?php echo htmlspecialchars($search); ?>">
                            
                            <select class="form-control form-select" name="status">
                                <option value="">All Status</option>
                                <option value="applied" <?php echo $status_filter == 'applied' ? 'selected' : ''; ?>>Applied</option>
                                <option value="shortlisted" <?php echo $status_filter == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                <option value="interviewed" <?php echo $status_filter == 'interviewed' ? 'selected' : ''; ?>>Interviewed</option>
                                <option value="selected" <?php echo $status_filter == 'selected' ? 'selected' : ''; ?>>Selected</option>
                                <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            
                            <?php if ($search || $status_filter): ?>
                                <a href="candidates.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Candidates Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Candidates (<?php echo $total_candidates; ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($candidates)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-user-tie" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <h4>No candidates found</h4>
                                <p style="color: var(--text-muted);">Start by adding your first candidate.</p>
                                <button class="btn btn-primary" onclick="showAddCandidateModal()">
                                    <i class="fas fa-plus"></i> Add Candidate
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Applied For</th>
                                            <th>Status</th>
                                            <th>Applied Date</th>
                                            <th>Resume</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($candidates as $candidate): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 500;">
                                                        <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($candidate['email']); ?></td>
                                                <td><?php echo htmlspecialchars($candidate['phone'] ?: 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($candidate['job_title'] ?: 'Not specified'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $candidate['status'] == 'selected' ? 'success' : 
                                                            ($candidate['status'] == 'rejected' ? 'danger' : 
                                                            ($candidate['status'] == 'interviewed' ? 'info' : 
                                                            ($candidate['status'] == 'shortlisted' ? 'warning' : 'secondary'))); ?>">
                                                        <?php echo ucfirst($candidate['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($candidate['applied_date'])); ?></td>
                                                <td>
                                                    <?php if ($candidate['resume_path']): ?>
                                                        <a href="<?php echo htmlspecialchars($candidate['resume_path']); ?>" target="_blank" class="btn btn-sm btn-outline">
                                                            <i class="fas fa-file-pdf"></i> View
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="color: var(--text-muted);">No resume</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-outline" onclick="updateStatus(<?php echo $candidate['id']; ?>, '<?php echo $candidate['status']; ?>', '<?php echo htmlspecialchars($candidate['notes']); ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteCandidate(<?php echo $candidate['id']; ?>)">
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
    
    <!-- Add Candidate Modal -->
    <div class="modal" id="addCandidateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Candidate</h3>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Applied For</label>
                        <select class="form-control form-select" name="job_id">
                            <option value="">Select Job</option>
                            <?php foreach ($jobs as $job): ?>
                                <option value="<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Resume (PDF, DOC, DOCX - Max 5MB)</label>
                        <input type="file" class="form-control" name="resume" accept=".pdf,.doc,.docx">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" name="add_candidate" class="btn btn-primary">Add Candidate</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal" id="statusModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Update Candidate Status</h3>
                <button class="modal-close" onclick="closeStatusModal()">&times;</button>
            </div>
            <form method="POST" id="statusForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="statusId">
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control form-select" name="status" id="statusSelect">
                            <option value="applied">Applied</option>
                            <option value="shortlisted">Shortlisted</option>
                            <option value="interviewed">Interviewed</option>
                            <option value="selected">Selected</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="statusNotes" rows="4" placeholder="Add any notes about this candidate..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeStatusModal()">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
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
        
        function showAddCandidateModal() {
            document.getElementById('addCandidateModal').classList.add('show');
        }
        
        function closeAddModal() {
            document.getElementById('addCandidateModal').classList.remove('show');
        }
        
        function updateStatus(id, currentStatus, notes) {
            document.getElementById('statusId').value = id;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusNotes').value = notes;
            document.getElementById('statusModal').classList.add('show');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('show');
        }
        
        function deleteCandidate(id) {
            if (confirm('Are you sure you want to delete this candidate?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_candidate" value="1"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
