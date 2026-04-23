<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']);
    
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];
            
            // Generate auth token
            generate_auth_token($admin['id']);
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Invalid username or password";
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
    <title>Login - HR Management System</title>
    <link rel="stylesheet" href="assets/css/enhanced-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <div class="login-logo">
                <i class="fas fa-users-cog" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h1>HR Management</h1>
                <p>Sign in to your account</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username or Email</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" name="username" required 
                               placeholder="Enter username or email" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
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
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            
            <div class="login-footer text-center mt-4">
                <p style="color: var(--text-muted); font-size: 0.875rem;">
                     <a href="index.php">Home Page</a>
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
        
        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .checkbox-label input[type="checkbox"] {
            margin-right: 8px;
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
        
        // Auto-hide success messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>
