<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $employee = $stmt->fetch();
        
        if ($employee && (password_verify($password, $employee['password']) || md5($password) === $employee['password'])) {
            $_SESSION['employee_id'] = $employee['id'];
            $_SESSION['employee_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
            $_SESSION['employee_email'] = $employee['email'];
            $_SESSION['employee_role'] = 'employee';
            
            // Generate employee auth token
            generate_employee_auth_token($employee['id']);
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } catch(PDOException $e) {
        $error = "Login failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login - HR Management System</title>
    <link rel="stylesheet" href="../assets/css/enhanced-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <div class="login-logo">
                <i class="fas fa-user-tie" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h1>Employee Portal</h1>
                <p>Sign in to your employee account</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" name="email" required 
                               placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="password" required 
                               placeholder="Enter password" id="password">
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            
            <div class="login-footer text-center mt-4">
                <p style="color: var(--text-muted); font-size: 0.875rem;">
                    <a href="../login.php" style="color: var(--primary-color);">Admin Login</a>
                </p>
            </div>
        </div>
    </div>
    
    <style>
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .input-group .form-control {
            padding-left: 40px;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
        }
    </style>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
