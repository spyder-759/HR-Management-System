<?php
require_once 'includes/config.php';

// Clear auth token and destroy session
clear_auth_token();
session_destroy();
session_unset();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging out...</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card text-center">
            <div class="spinner" style="margin: 0 auto 2rem;"></div>
            <h2>Logging out...</h2>
            <p style="color: var(--text-muted);">Please wait while we redirect you.</p>
        </div>
    </div>
    
    <script>
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 1500);
    </script>
</body>
</html>
