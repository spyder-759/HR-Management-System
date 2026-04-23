-- HR Management System Database Schema
-- Created for MySQL 8.0+

CREATE DATABASE IF NOT EXISTS hr_management_system;
USE hr_management_system;

-- Admins table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Jobs table
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    department_id INT,
    description TEXT,
    requirements TEXT,
    salary_range VARCHAR(100),
    location VARCHAR(100),
    job_type ENUM('full-time', 'part-time', 'contract', 'internship') DEFAULT 'full-time',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- Candidates table
CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    job_id INT,
    resume_path VARCHAR(255),
    status ENUM('applied', 'shortlisted', 'interviewed', 'selected', 'rejected') DEFAULT 'applied',
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
);

-- Employees table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    department_id INT,
    role VARCHAR(100) NOT NULL,
    salary DECIMAL(10,2),
    joining_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
    candidate_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE SET NULL
);

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
    check_in TIME,
    check_out TIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_date (employee_id, date)
);

-- Leave types table
CREATE TABLE leave_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    max_days_per_year INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Leaves table
CREATE TABLE leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- Performance reviews table
CREATE TABLE performance_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    review_period VARCHAR(50) NOT NULL,
    review_date DATE NOT NULL,
    reviewer_id INT NOT NULL,
    technical_skills INT DEFAULT 0,
    communication_skills INT DEFAULT 0,
    teamwork INT DEFAULT 0,
    punctuality INT DEFAULT 0,
    overall_rating DECIMAL(3,2) DEFAULT 0.00,
    strengths TEXT,
    areas_for_improvement TEXT,
    goals TEXT,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES admins(id) ON DELETE SET NULL
);

-- Leave balance table
CREATE TABLE leave_balance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    total_allocated INT DEFAULT 0,
    used INT DEFAULT 0,
    remaining INT GENERATED ALWAYS AS (total_allocated - used) STORED,
    year YEAR NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_employee_leave_type_year (employee_id, leave_type_id, year)
);

-- Insert default admin user (password: admin123)
INSERT INTO admins (username, email, password, full_name) VALUES 
('admin', 'admin@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');

-- Insert default departments
INSERT INTO departments (name, description) VALUES 
('Human Resources', 'Manages employee relations, recruitment, and company policies'),
('Information Technology', 'Handles all technical infrastructure and software development'),
('Sales & Marketing', 'Responsible for business development and customer acquisition'),
('Finance', 'Manages financial planning, accounting, and budgeting'),
('Operations', 'Oversees daily business operations and logistics');

-- Insert default leave types
INSERT INTO leave_types (name, description, max_days_per_year) VALUES 
('Sick Leave', 'Leave taken due to illness or medical appointments', 12),
('Annual Leave', 'Paid vacation leave for personal time', 21),
('Maternity Leave', 'Leave for pregnancy and childbirth', 90),
('Paternity Leave', 'Leave for new fathers', 7),
('Emergency Leave', 'Leave for unexpected personal emergencies', 5);

-- Insert sample jobs
INSERT INTO jobs (title, department_id, description, requirements, salary_range, location, job_type) VALUES 
('Senior Software Developer', 2, 'Develop and maintain web applications using modern technologies', '5+ years experience in PHP, JavaScript, MySQL', '$80,000 - $120,000', 'Remote', 'full-time'),
('HR Manager', 1, 'Manage human resources operations and employee relations', '7+ years in HR management, MBA preferred', '$70,000 - $95,000', 'Office', 'full-time'),
('Sales Executive', 3, 'Drive sales and acquire new clients', '3+ years sales experience, excellent communication', '$45,000 - $65,000', 'Hybrid', 'full-time'),
('Financial Analyst', 4, 'Analyze financial data and prepare reports', '4+ years in finance, CPA preferred', '$60,000 - $85,000', 'Office', 'full-time');

-- Create indexes for better performance
CREATE INDEX idx_candidates_status ON candidates(status);
CREATE INDEX idx_candidates_job_id ON candidates(job_id);
CREATE INDEX idx_employees_department ON employees(department_id);
CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_attendance_date ON attendance(date);
CREATE INDEX idx_leaves_status ON leaves(status);
CREATE INDEX idx_leaves_employee ON leaves(employee_id);
CREATE INDEX idx_performance_employee ON performance_reviews(employee_id);

-- ========================================
-- DEMO DATA FOR TESTING
-- ========================================

-- Insert additional admin users for testing
INSERT INTO admins (username, email, password, full_name) VALUES 
('john_admin', 'john.doe@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe'),
('sarah_admin', 'sarah.smith@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Smith'),
('mike_admin', 'mike.wilson@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Wilson');

-- Insert sample candidates
INSERT INTO candidates (first_name, last_name, email, phone, job_id, status, applied_date, notes) VALUES 
('Alice', 'Johnson', 'alice.johnson@email.com', '555-0101', 1, 'selected', '2024-01-15', 'Excellent technical skills, good fit for Senior Developer role'),
('Bob', 'Smith', 'bob.smith@email.com', '555-0102', 2, 'interviewed', '2024-01-20', 'Strong HR background, good communication skills'),
('Carol', 'Williams', 'carol.williams@email.com', '555-0103', 3, 'shortlisted', '2024-01-25', 'Impressive sales record, confident presenter'),
('David', 'Brown', 'david.brown@email.com', '555-0104', 4, 'applied', '2024-02-01', 'Solid financial analysis experience'),
('Emma', 'Davis', 'emma.davis@email.com', '555-0105', 1, 'rejected', '2024-02-05', 'Lack of required experience'),
('Frank', 'Miller', 'frank.miller@email.com', '555-0106', 2, 'applied', '2024-02-10', 'Recent HR graduate, eager to learn');

-- Insert sample employees
INSERT INTO employees (employee_id, first_name, last_name, email, phone, department_id, role, salary, joining_date, status, candidate_id) VALUES 
('EMP001', 'Alice', 'Johnson', 'alice.johnson@hrms.com', '555-0101', 2, 'Senior Software Developer', 95000.00, '2024-02-01', 'active', 1),
('EMP002', 'Robert', 'Smith', 'robert.smith@hrms.com', '555-0107', 1, 'HR Manager', 75000.00, '2024-01-15', 'active', NULL),
('EMP003', 'Carol', 'Williams', 'carol.williams@hrms.com', '555-0103', 3, 'Sales Executive', 55000.00, '2024-02-15', 'active', 3),
('EMP004', 'David', 'Brown', 'david.brown@hrms.com', '555-0104', 4, 'Financial Analyst', 70000.00, '2024-03-01', 'active', 4),
('EMP005', 'Emma', 'Davis', 'emma.davis@hrms.com', '555-0105', 2, 'Junior Developer', 65000.00, '2024-03-15', 'active', NULL),
('EMP006', 'Frank', 'Miller', 'frank.miller@hrms.com', '555-0106', 1, 'HR Coordinator', 45000.00, '2024-02-20', 'active', NULL),
('EMP007', 'Grace', 'Wilson', 'grace.wilson@hrms.com', '555-0108', 3, 'Marketing Manager', 68000.00, '2024-01-10', 'active', NULL),
('EMP008', 'Henry', 'Moore', 'henry.moore@hrms.com', '555-0109', 5, 'Operations Manager', 72000.00, '2023-12-01', 'active', NULL),
('EMP009', 'Isabella', 'Taylor', 'isabella.taylor@hrms.com', '555-0110', 2, 'UI/UX Designer', 58000.00, '2024-02-10', 'active', NULL),
('EMP010', 'James', 'Anderson', 'james.anderson@hrms.com', '555-0111', 4, 'Accountant', 52000.00, '2024-03-10', 'inactive', NULL);

-- Insert sample attendance records for the last 30 days
INSERT INTO attendance (employee_id, date, status, check_in, check_out, notes) VALUES 
-- Employee 1 - Alice Johnson
(1, '2024-03-01', 'present', '09:00:00', '17:30:00', NULL),
(1, '2024-03-02', 'present', '08:45:00', '17:15:00', NULL),
(1, '2024-03-03', 'late', '09:15:00', '17:45:00', 'Traffic delay'),
(1, '2024-03-04', 'present', '08:50:00', '17:20:00', NULL),
(1, '2024-03-05', 'absent', NULL, NULL, 'Sick leave'),

-- Employee 2 - Robert Smith
(2, '2024-03-01', 'present', '08:30:00', '17:00:00', NULL),
(2, '2024-03-02', 'present', '08:45:00', '17:30:00', NULL),
(2, '2024-03-03', 'present', '09:00:00', '18:00:00', 'Working late'),
(2, '2024-03-04', 'half_day', '09:00:00', '13:00:00', 'Personal appointment'),
(2, '2024-03-05', 'present', '08:30:00', '17:00:00', NULL),

-- Employee 3 - Carol Williams
(3, '2024-03-01', 'present', '09:00:00', '18:00:00', 'Client meeting'),
(3, '2024-03-02', 'present', '08:45:00', '17:15:00', NULL),
(3, '2024-03-03', 'present', '09:30:00', '18:30:00', 'Late start, worked late'),
(3, '2024-03-04', 'present', '08:30:00', '17:00:00', NULL),
(3, '2024-03-05', 'present', '09:00:00', '17:30:00', NULL),

-- Employee 4 - David Brown
(4, '2024-03-01', 'present', '08:15:00', '16:45:00', NULL),
(4, '2024-03-02', 'present', '08:30:00', '17:00:00', NULL),
(4, '2024-03-03', 'present', '08:45:00', '17:15:00', NULL),
(4, '2024-03-04', 'present', '08:30:00', '17:00:00', NULL),
(4, '2024-03-05', 'late', '09:30:00', '18:00:00', 'Train delay'),

-- Employee 5 - Emma Davis
(5, '2024-03-01', 'present', '09:15:00', '17:45:00', NULL),
(5, '2024-03-02', 'present', '08:45:00', '17:15:00', NULL),
(5, '2024-03-03', 'absent', NULL, NULL, 'Emergency leave'),
(5, '2024-03-04', 'present', '09:00:00', '17:30:00', NULL),
(5, '2024-03-05', 'present', '08:30:00', '17:00:00', NULL);

-- Insert sample leave applications
INSERT INTO leaves (employee_id, leave_type_id, start_date, end_date, total_days, reason, status, approved_by, approved_date) VALUES 
(1, 2, '2024-03-10', '2024-03-12', 3, 'Family vacation', 'approved', 1, '2024-03-01 10:00:00'),
(2, 1, '2024-03-05', '2024-03-05', 1, 'Doctor appointment', 'approved', 1, '2024-03-01 11:30:00'),
(3, 5, '2024-03-15', '2024-03-15', 1, 'Personal emergency', 'pending', NULL, NULL),
(4, 2, '2024-04-01', '2024-04-05', 5, 'Annual vacation', 'approved', 2, '2024-03-02 09:00:00'),
(5, 1, '2024-03-03', '2024-03-03', 1, 'Medical emergency', 'approved', 1, '2024-03-01 14:00:00'),
(6, 3, '2024-06-01', '2024-08-30', 90, 'Maternity leave', 'approved', 2, '2024-03-01 15:00:00'),
(7, 2, '2024-05-20', '2024-05-24', 5, 'Summer vacation', 'pending', NULL, NULL),
(8, 4, '2024-03-20', '2024-03-20', 1, 'Childbirth', 'approved', 1, '2024-03-02 10:30:00');

-- Insert leave balance for current year (2024)
INSERT INTO leave_balance (employee_id, leave_type_id, total_allocated, used, year) VALUES 
-- Employee 1 - Alice Johnson
(1, 1, 12, 1, 2024),  -- Sick Leave: 12 allocated, 1 used
(1, 2, 21, 3, 2024),  -- Annual Leave: 21 allocated, 3 used
(1, 5, 5, 0, 2024),   -- Emergency Leave: 5 allocated, 0 used

-- Employee 2 - Robert Smith
(2, 1, 12, 1, 2024),
(2, 2, 21, 0, 2024),
(2, 5, 5, 0, 2024),

-- Employee 3 - Carol Williams
(3, 1, 12, 0, 2024),
(3, 2, 21, 0, 2024),
(3, 5, 5, 1, 2024),

-- Employee 4 - David Brown
(4, 1, 12, 0, 2024),
(4, 2, 21, 5, 2024),
(4, 5, 5, 0, 2024),

-- Employee 5 - Emma Davis
(5, 1, 12, 1, 2024),
(5, 2, 21, 0, 2024),
(5, 5, 5, 0, 2024),

-- Employee 6 - Frank Miller
(6, 1, 12, 0, 2024),
(6, 2, 21, 0, 2024),
(6, 3, 90, 90, 2024), -- Maternity Leave: 90 allocated, 90 used
(6, 5, 5, 0, 2024),

-- Employee 7 - Grace Wilson
(7, 1, 12, 0, 2024),
(7, 2, 21, 0, 2024),
(7, 5, 5, 0, 2024),

-- Employee 8 - Henry Moore
(8, 1, 12, 0, 2024),
(8, 2, 21, 0, 2024),
(8, 4, 7, 1, 2024),  -- Paternity Leave: 7 allocated, 1 used
(8, 5, 5, 0, 2024);

-- Insert performance reviews
INSERT INTO performance_reviews (employee_id, review_period, review_date, reviewer_id, technical_skills, communication_skills, teamwork, punctuality, overall_rating, strengths, areas_for_improvement, goals, comments) VALUES 
(1, 'Q1 2024', '2024-03-15', 1, 9, 8, 9, 8, 8.50, 'Excellent technical skills, quick learner', 'Time management', 'Lead a development project', 'Outstanding performer'),
(2, 'Q1 2024', '2024-03-10', 1, 7, 9, 8, 9, 8.25, 'Great communication skills, team player', 'Technical knowledge of HR systems', 'Complete HR certification', 'Strong leadership potential'),
(3, 'Q1 2024', '2024-03-12', 2, 8, 9, 8, 7, 8.00, 'Excellent sales skills, client relationships', 'Documentation', 'Exceed sales targets by 15%', 'Top performer in sales team'),
(4, 'Q1 2024', '2024-03-08', 1, 8, 7, 8, 9, 8.00, 'Detail-oriented, reliable', 'Presentation skills', 'Advanced Excel certification', 'Consistently accurate work'),
(5, 'Q1 2024', '2024-03-14', 1, 7, 6, 7, 8, 7.00, 'Good coding skills, eager to learn', 'Experience with complex projects', 'Mentorship from senior developer', 'Shows promise, needs more experience'),
(6, 'Q1 2024', '2024-03-11', 2, 6, 8, 7, 9, 7.50, 'Good interpersonal skills', 'HR policy knowledge', 'Complete HR management course', 'Dedicated employee'),
(7, 'Q1 2024', '2024-03-13', 1, 8, 9, 8, 8, 8.25, 'Creative marketing ideas, good strategist', 'Budget management', 'Launch successful marketing campaign', 'Innovative thinker'),
(8, 'Q1 2024', '2024-03-09', 1, 7, 8, 9, 9, 8.25, 'Excellent organizational skills', 'Technology adoption', 'Implement new operations software', 'Reliable and efficient');

-- Insert additional sample jobs for more variety
INSERT INTO jobs (title, department_id, description, requirements, salary_range, location, job_type, status) VALUES 
('Marketing Specialist', 3, 'Create and execute marketing campaigns', '3+ years marketing experience, social media expertise', '$50,000 - $70,000', 'Hybrid', 'full-time', 'active'),
('DevOps Engineer', 2, 'Manage CI/CD pipelines and cloud infrastructure', '4+ years DevOps experience, AWS certified', '$85,000 - $115,000', 'Remote', 'full-time', 'active'),
('Junior Accountant', 4, 'Assist with financial reporting and bookkeeping', '1-2 years accounting experience, Bachelor degree', '$40,000 - $55,000', 'Office', 'full-time', 'active'),
('Operations Coordinator', 5, 'Support daily operations and logistics', '2+ years operations experience', '$45,000 - $60,000', 'Office', 'full-time', 'active'),
('HR Intern', 1, 'Assist HR team with recruitment and admin tasks', 'Currently pursuing HR degree', '$25,000 - $35,000', 'Office', 'internship', 'active'),
('Senior Financial Analyst', 4, 'Lead financial analysis and forecasting', '7+ years finance experience, MBA preferred', '$80,000 - $110,000', 'Office', 'full-time', 'inactive');

-- Additional candidates for new jobs
INSERT INTO candidates (first_name, last_name, email, phone, job_id, status, applied_date, notes) VALUES 
('Jack', 'Thompson', 'jack.thompson@email.com', '555-0112', 5, 'shortlisted', '2024-03-05', 'Strong marketing background'),
('Karen', 'White', 'karen.white@email.com', '555-0113', 6, 'interviewed', '2024-03-08', 'Excellent DevOps skills'),
('Laura', 'Harris', 'laura.harris@email.com', '555-0114', 7, 'applied', '2024-03-10', 'Recent accounting graduate'),
('Mark', 'Clark', 'mark.clark@email.com', '555-0115', 8, 'shortlisted', '2024-03-12', 'Operations experience'),
('Nancy', 'Lewis', 'nancy.lewis@email.com', '555-0116', 9, 'applied', '2024-03-14', 'HR student, eager to learn');

-- More attendance records for variety
INSERT INTO attendance (employee_id, date, status, check_in, check_out, notes) VALUES 
(6, '2024-03-01', 'present', '09:00:00', '17:30:00', NULL),
(6, '2024-03-02', 'present', '08:45:00', '17:15:00', NULL),
(6, '2024-03-03', 'present', '09:00:00', '17:30:00', NULL),
(6, '2024-03-04', 'half_day', '09:00:00', '13:00:00', 'Medical appointment'),
(6, '2024-03-05', 'present', '08:30:00', '17:00:00', NULL),

(7, '2024-03-01', 'present', '08:30:00', '17:00:00', NULL),
(7, '2024-03-02', 'late', '09:30:00', '18:00:00', 'Traffic'),
(7, '2024-03-03', 'present', '08:45:00', '17:15:00', NULL),
(7, '2024-03-04', 'present', '09:00:00', '17:30:00', NULL),
(7, '2024-03-05', 'present', '08:30:00', '17:00:00', NULL),

(8, '2024-03-01', 'present', '08:00:00', '16:30:00', NULL),
(8, '2024-03-02', 'present', '08:15:00', '16:45:00', NULL),
(8, '2024-03-03', 'present', '08:00:00', '16:30:00', NULL),
(8, '2024-03-04', 'present', '08:15:00', '16:45:00', NULL),
(8, '2024-03-05', 'present', '08:00:00', '16:30:00', NULL);

-- ========================================
-- LOGIN CREDENTIALS FOR TESTING
-- ========================================
/*
ADMIN USERS:
Username: admin | Password: admin123
Username: john_admin | Password: admin123  
Username: sarah_admin | Password: admin123
Username: mike_admin | Password: admin123

EMPLOYEES (can be used for testing if employee login is implemented):
Employee ID: EMP001 | Email: alice.johnson@hrms.com
Employee ID: EMP002 | Email: robert.smith@hrms.com
Employee ID: EMP003 | Email: carol.williams@hrms.com
etc.

DATABASE: hr_management_system
*/
