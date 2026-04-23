<?php
require_once '../../includes/config.php';
require_login();

$conn = getConnection();

// Handle performance review operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_review'])) {
        $employee_id = sanitize_input($_POST['employee_id']);
        $review_period = sanitize_input($_POST['review_period']);
        $review_date = sanitize_input($_POST['review_date']);
        $technical_skills = sanitize_input($_POST['technical_skills']);
        $communication_skills = sanitize_input($_POST['communication_skills']);
        $teamwork = sanitize_input($_POST['teamwork']);
        $punctuality = sanitize_input($_POST['punctuality']);
        $strengths = sanitize_input($_POST['strengths']);
        $areas_for_improvement = sanitize_input($_POST['areas_for_improvement']);
        $goals = sanitize_input($_POST['goals']);
        $comments = sanitize_input($_POST['comments']);
        
        // Calculate overall rating
        $overall_rating = ($technical_skills + $communication_skills + $teamwork + $punctuality) / 4;
        
        $reviewer_id = $_SESSION['admin_id'];
        
        $stmt = $conn->prepare("INSERT INTO performance_reviews (employee_id, review_period, review_date, reviewer_id, technical_skills, communication_skills, teamwork, punctuality, overall_rating, strengths, areas_for_improvement, goals, comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$employee_id, $review_period, $review_date, $reviewer_id, $technical_skills, $communication_skills, $teamwork, $punctuality, $overall_rating, $strengths, $areas_for_improvement, $goals, $comments]);
        
        $_SESSION['success'] = "Performance review added successfully!";
        header('Location: performance.php');
        exit();
    }
    
    if (isset($_POST['update_review'])) {
        $id = sanitize_input($_POST['id']);
        $employee_id = sanitize_input($_POST['employee_id']);
        $review_period = sanitize_input($_POST['review_period']);
        $review_date = sanitize_input($_POST['review_date']);
        $technical_skills = sanitize_input($_POST['technical_skills']);
        $communication_skills = sanitize_input($_POST['communication_skills']);
        $teamwork = sanitize_input($_POST['teamwork']);
        $punctuality = sanitize_input($_POST['punctuality']);
        $strengths = sanitize_input($_POST['strengths']);
        $areas_for_improvement = sanitize_input($_POST['areas_for_improvement']);
        $goals = sanitize_input($_POST['goals']);
        $comments = sanitize_input($_POST['comments']);
        
        // Calculate overall rating
        $overall_rating = ($technical_skills + $communication_skills + $teamwork + $punctuality) / 4;
        
        $stmt = $conn->prepare("UPDATE performance_reviews SET employee_id=?, review_period=?, review_date=?, technical_skills=?, communication_skills=?, teamwork=?, punctuality=?, overall_rating=?, strengths=?, areas_for_improvement=?, goals=?, comments=? WHERE id=?");
        $stmt->execute([$employee_id, $review_period, $review_date, $technical_skills, $communication_skills, $teamwork, $punctuality, $overall_rating, $strengths, $areas_for_improvement, $goals, $comments, $id]);
        
        $_SESSION['success'] = "Performance review updated successfully!";
        header('Location: performance.php');
        exit();
    }
    
    if (isset($_POST['delete_review'])) {
        $id = sanitize_input($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM performance_reviews WHERE id=?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Performance review deleted successfully!";
        header('Location: performance.php');
        exit();
    }
}

// Get performance reviews with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$department_filter = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ? OR pr.review_period LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

if ($department_filter) {
    $where_conditions[] = "e.department_id = ?";
    $params[] = $department_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$total_query = "SELECT COUNT(*) as total FROM performance_reviews pr 
                JOIN employees e ON pr.employee_id = e.id 
                $where_clause";
$stmt = $conn->prepare($total_query);
$stmt->execute($params);
$total_reviews = $stmt->fetch()['total'];

$pagination = get_pagination($total_reviews, $per_page, $page);

$query = "SELECT pr.*, e.first_name, e.last_name, e.employee_id, d.name as department_name, 
          a.full_name as reviewer_name 
          FROM performance_reviews pr 
          JOIN employees e ON pr.employee_id = e.id 
          LEFT JOIN departments d ON e.department_id = d.id 
          LEFT JOIN admins a ON pr.reviewer_id = a.id 
          $where_clause 
          ORDER BY pr.review_date DESC 
          LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Get employees for dropdown
$stmt = $conn->prepare("SELECT e.id, e.first_name, e.last_name, e.employee_id, d.name as department_name 
                        FROM employees e 
                        LEFT JOIN departments d ON e.department_id = d.id 
                        WHERE e.status = 'active' 
                        ORDER BY d.name, e.first_name, e.last_name");
$stmt->execute();
$employees = $stmt->fetchAll();

// Get departments for filter
$stmt = $conn->prepare("SELECT * FROM departments ORDER BY name");
$stmt->execute();
$departments = $stmt->fetchAll();

// Get performance statistics
$stmt = $conn->prepare("SELECT AVG(overall_rating) as avg_rating, 
                        COUNT(*) as total_reviews,
                        MIN(overall_rating) as min_rating,
                        MAX(overall_rating) as max_rating
                        FROM performance_reviews");
$stmt->execute();
$performance_stats = $stmt->fetch();

// Get review for editing
$edit_review = null;
if (isset($_GET['edit'])) {
    $id = sanitize_input($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM performance_reviews WHERE id = ?");
    $stmt->execute([$id]);
    $edit_review = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Reviews - HR Management System</title>
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
                    <li><a href="../leaves/leaves.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></li>
                    <li><a href="../leaves/attendance.php"><i class="fas fa-clock"></i> Attendance</a></li>
                    <li><a href="performance.php" class="active"><i class="fas fa-chart-line"></i> Performance</a></li>
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
                    <h1>Performance Reviews</h1>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-primary" onclick="showAddReviewModal()">
                        <i class="fas fa-plus"></i> Add Review
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
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_reviews); ?></div>
                        <div class="stat-label">Total Reviews</div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($performance_stats['avg_rating'], 1); ?></div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($performance_stats['max_rating'], 1); ?></div>
                        <div class="stat-label">Highest Rating</div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($performance_stats['min_rating'], 1); ?></div>
                        <div class="stat-label">Lowest Rating</div>
                    </div>
                </div>
                
                <!-- Search and Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-3">
                            <input type="text" class="form-control" name="search" placeholder="Search reviews..." value="<?php echo htmlspecialchars($search); ?>">
                            
                            <select class="form-control form-select" name="department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            
                            <?php if ($search || $department_filter): ?>
                                <a href="performance.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Reviews Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Performance Reviews (<?php echo $total_reviews; ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-chart-line" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <h4>No performance reviews found</h4>
                                <p style="color: var(--text-muted);">Start by adding your first performance review.</p>
                                <button class="btn btn-primary" onclick="showAddReviewModal()">
                                    <i class="fas fa-plus"></i> Add Review
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Review Period</th>
                                            <th>Review Date</th>
                                            <th>Overall Rating</th>
                                            <th>Reviewer</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reviews as $review): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <div style="font-weight: 500;">
                                                            <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                                        </div>
                                                        <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                            <?php echo htmlspecialchars($review['employee_id']); ?>
                                                        </div>
                                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                            <?php echo htmlspecialchars($review['department_name'] ?: 'Not assigned'); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($review['review_period']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($review['review_date'])); ?></td>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                        <div style="flex: 1; background: var(--border-color); border-radius: 0.25rem; height: 8px; overflow: hidden;">
                                                            <div style="background: <?php echo $review['overall_rating'] >= 4 ? 'var(--success-color)' : ($review['overall_rating'] >= 3 ? 'var(--warning-color)' : 'var(--danger-color)'); ?>; height: 100%; width: <?php echo ($review['overall_rating'] / 5) * 100; ?>%;"></div>
                                                        </div>
                                                        <span style="font-weight: 600; min-width: 40px;"><?php echo number_format($review['overall_rating'], 1); ?>/5</span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($review['reviewer_name'] ?: 'Unknown'); ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-outline" onclick="viewReview(<?php echo $review['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline" onclick="editReview(<?php echo $review['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteReview(<?php echo $review['id']; ?>)">
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
                                        <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . urlencode($department_filter) : ''; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <?php if ($i == $pagination['current_page']): ?>
                                            <span class="active"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . urlencode($department_filter) : ''; ?>"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                        <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . urlencode($department_filter) : ''; ?>">
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
    
    <!-- Add/Edit Review Modal -->
    <div class="modal" id="reviewModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle"><?php echo $edit_review ? 'Edit Performance Review' : 'Add Performance Review'; ?></h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="reviewForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="reviewId" value="<?php echo $edit_review['id'] ?? ''; ?>">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Employee *</label>
                            <select class="form-control form-select" name="employee_id" required id="employeeSelect">
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>" 
                                            data-department="<?php echo htmlspecialchars($employee['department_name']); ?>"
                                            <?php echo (isset($edit_review['employee_id']) && $edit_review['employee_id'] == $employee['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Review Period *</label>
                            <input type="text" class="form-control" name="review_period" required 
                                   value="<?php echo htmlspecialchars($edit_review['review_period'] ?? ''); ?>"
                                   placeholder="e.g., Q1 2024, Annual 2024">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Review Date *</label>
                        <input type="date" class="form-control" name="review_date" required 
                               value="<?php echo htmlspecialchars($edit_review['review_date'] ?? date('Y-m-d')); ?>">
                    </div>
                    
                    <!-- Rating Fields -->
                    <h4 style="margin: 2rem 0 1rem 0; color: var(--dark-color);">Performance Ratings (1-5)</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Technical Skills</label>
                            <select class="form-control form-select" name="technical_skills" required id="technicalSkills">
                                <option value="1" <?php echo (isset($edit_review['technical_skills']) && $edit_review['technical_skills'] == 1) ? 'selected' : ''; ?>>1 - Poor</option>
                                <option value="2" <?php echo (isset($edit_review['technical_skills']) && $edit_review['technical_skills'] == 2) ? 'selected' : ''; ?>>2 - Below Average</option>
                                <option value="3" <?php echo (isset($edit_review['technical_skills']) && $edit_review['technical_skills'] == 3) ? 'selected' : ''; ?>>3 - Average</option>
                                <option value="4" <?php echo (isset($edit_review['technical_skills']) && $edit_review['technical_skills'] == 4) ? 'selected' : ''; ?>>4 - Good</option>
                                <option value="5" <?php echo (isset($edit_review['technical_skills']) && $edit_review['technical_skills'] == 5) ? 'selected' : ''; ?>>5 - Excellent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Communication Skills</label>
                            <select class="form-control form-select" name="communication_skills" required id="communicationSkills">
                                <option value="1" <?php echo (isset($edit_review['communication_skills']) && $edit_review['communication_skills'] == 1) ? 'selected' : ''; ?>>1 - Poor</option>
                                <option value="2" <?php echo (isset($edit_review['communication_skills']) && $edit_review['communication_skills'] == 2) ? 'selected' : ''; ?>>2 - Below Average</option>
                                <option value="3" <?php echo (isset($edit_review['communication_skills']) && $edit_review['communication_skills'] == 3) ? 'selected' : ''; ?>>3 - Average</option>
                                <option value="4" <?php echo (isset($edit_review['communication_skills']) && $edit_review['communication_skills'] == 4) ? 'selected' : ''; ?>>4 - Good</option>
                                <option value="5" <?php echo (isset($edit_review['communication_skills']) && $edit_review['communication_skills'] == 5) ? 'selected' : ''; ?>>5 - Excellent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Teamwork</label>
                            <select class="form-control form-select" name="teamwork" required id="teamwork">
                                <option value="1" <?php echo (isset($edit_review['teamwork']) && $edit_review['teamwork'] == 1) ? 'selected' : ''; ?>>1 - Poor</option>
                                <option value="2" <?php echo (isset($edit_review['teamwork']) && $edit_review['teamwork'] == 2) ? 'selected' : ''; ?>>2 - Below Average</option>
                                <option value="3" <?php echo (isset($edit_review['teamwork']) && $edit_review['teamwork'] == 3) ? 'selected' : ''; ?>>3 - Average</option>
                                <option value="4" <?php echo (isset($edit_review['teamwork']) && $edit_review['teamwork'] == 4) ? 'selected' : ''; ?>>4 - Good</option>
                                <option value="5" <?php echo (isset($edit_review['teamwork']) && $edit_review['teamwork'] == 5) ? 'selected' : ''; ?>>5 - Excellent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Punctuality</label>
                            <select class="form-control form-select" name="punctuality" required id="punctuality">
                                <option value="1" <?php echo (isset($edit_review['punctuality']) && $edit_review['punctuality'] == 1) ? 'selected' : ''; ?>>1 - Poor</option>
                                <option value="2" <?php echo (isset($edit_review['punctuality']) && $edit_review['punctuality'] == 2) ? 'selected' : ''; ?>>2 - Below Average</option>
                                <option value="3" <?php echo (isset($edit_review['punctuality']) && $edit_review['punctuality'] == 3) ? 'selected' : ''; ?>>3 - Average</option>
                                <option value="4" <?php echo (isset($edit_review['punctuality']) && $edit_review['punctuality'] == 4) ? 'selected' : ''; ?>>4 - Good</option>
                                <option value="5" <?php echo (isset($edit_review['punctuality']) && $edit_review['punctuality'] == 5) ? 'selected' : ''; ?>>5 - Excellent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Overall Rating</label>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="flex: 1; background: var(--border-color); border-radius: 0.25rem; height: 20px; overflow: hidden;">
                                <div id="overallRatingBar" style="background: linear-gradient(90deg, var(--danger-color) 0%, var(--warning-color) 50%, var(--success-color) 100%); height: 100%; width: 60%; transition: width 0.3s ease;"></div>
                            </div>
                            <span id="overallRatingValue" style="font-weight: 600; font-size: 1.25rem; min-width: 60px;">3.0/5</span>
                        </div>
                    </div>
                    
                    <!-- Text Fields -->
                    <h4 style="margin: 2rem 0 1rem 0; color: var(--dark-color);">Feedback & Comments</h4>
                    
                    <div class="form-group">
                        <label class="form-label">Strengths</label>
                        <textarea class="form-control" name="strengths" rows="3" placeholder="Describe the employee's strengths..."><?php echo htmlspecialchars($edit_review['strengths'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Areas for Improvement</label>
                        <textarea class="form-control" name="areas_for_improvement" rows="3" placeholder="Describe areas where the employee can improve..."><?php echo htmlspecialchars($edit_review['areas_for_improvement'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Goals</label>
                        <textarea class="form-control" name="goals" rows="3" placeholder="Set goals for the next review period..."><?php echo htmlspecialchars($edit_review['goals'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Additional Comments</label>
                        <textarea class="form-control" name="comments" rows="3" placeholder="Any additional comments..."><?php echo htmlspecialchars($edit_review['comments'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="<?php echo $edit_review ? 'update_review' : 'add_review'; ?>" class="btn btn-primary">
                        <?php echo $edit_review ? 'Update Review' : 'Add Review'; ?>
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
        
        function showAddReviewModal() {
            document.getElementById('modalTitle').textContent = 'Add Performance Review';
            document.getElementById('reviewForm').reset();
            document.getElementById('reviewId').value = '';
            document.getElementById('reviewForm').querySelector('button[type="submit"]').name = 'add_review';
            document.getElementById('reviewForm').querySelector('button[type="submit"]').textContent = 'Add Review';
            updateOverallRating();
            document.getElementById('reviewModal').classList.add('show');
        }
        
        function editReview(id) {
            window.location.href = '?edit=' + id;
        }
        
        function viewReview(id) {
            // Implement view functionality or redirect to edit
            window.location.href = '?edit=' + id;
        }
        
        function deleteReview(id) {
            if (confirm('Are you sure you want to delete this performance review?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_review" value="1"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal() {
            document.getElementById('reviewModal').classList.remove('show');
            window.location.href = 'performance.php';
        }
        
        function updateOverallRating() {
            const technical = parseFloat(document.getElementById('technicalSkills').value) || 0;
            const communication = parseFloat(document.getElementById('communicationSkills').value) || 0;
            const teamwork = parseFloat(document.getElementById('teamwork').value) || 0;
            const punctuality = parseFloat(document.getElementById('punctuality').value) || 0;
            
            const overall = (technical + communication + teamwork + punctuality) / 4;
            const percentage = (overall / 5) * 100;
            
            document.getElementById('overallRatingBar').style.width = percentage + '%';
            document.getElementById('overallRatingValue').textContent = overall.toFixed(1) + '/5';
        }
        
        // Update overall rating when any rating changes
        document.getElementById('technicalSkills').addEventListener('change', updateOverallRating);
        document.getElementById('communicationSkills').addEventListener('change', updateOverallRating);
        document.getElementById('teamwork').addEventListener('change', updateOverallRating);
        document.getElementById('punctuality').addEventListener('change', updateOverallRating);
        
        // Show edit modal if editing
        <?php if ($edit_review): ?>
            document.getElementById('reviewModal').classList.add('show');
            updateOverallRating();
        <?php endif; ?>
    </script>
</body>
</html>
