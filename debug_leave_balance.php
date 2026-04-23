<?php
require_once 'includes/config.php';

echo "<h2>Leave Balance Debug</h2>";

// Get current employee session info
if (isset($_SESSION['employee_id'])) {
    $employee_id = $_SESSION['employee_id'];
    echo "<p><strong>Employee ID in session:</strong> $employee_id</p>";
    
    $conn = getConnection();
    
    // Check employee details
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if ($employee) {
        echo "<p><strong>Employee Name:</strong> " . htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) . "</p>";
        echo "<p><strong>Employee Email:</strong> " . htmlspecialchars($employee['email']) . "</p>";
    } else {
        echo "<p style='color: red;'>Employee not found in database!</p>";
    }
    
    // Check leave balance for this employee
    $current_year = date('Y');
    echo "<p><strong>Current Year:</strong> $current_year</p>";
    
    $stmt = $conn->prepare("SELECT lb.*, lt.name as leave_type_name 
                            FROM leave_balance lb 
                            JOIN leave_types lt ON lb.leave_type_id = lt.id 
                            WHERE lb.employee_id = ? AND lb.year = ?");
    $stmt->execute([$employee_id, $current_year]);
    $balances = $stmt->fetchAll();
    
    echo "<h3>Leave Balance Records:</h3>";
    if (empty($balances)) {
        echo "<p style='color: red;'>No leave balance records found for this employee!</p>";
        
        // Try to initialize
        echo "<p>Attempting to initialize leave balance...</p>";
        initialize_employee_leave_balance($employee_id);
        
        // Check again
        $stmt->execute([$employee_id, $current_year]);
        $balances = $stmt->fetchAll();
        
        if (!empty($balances)) {
            echo "<p style='color: green;'>Leave balance initialized successfully!</p>";
        } else {
            echo "<p style='color: red;'>Failed to initialize leave balance!</p>";
        }
    }
    
    foreach ($balances as $balance) {
        $remaining = $balance['total_allocated'] - $balance['total_used'];
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
        echo "<strong>" . htmlspecialchars($balance['leave_type_name']) . "</strong><br>";
        echo "Allocated: " . $balance['total_allocated'] . " days<br>";
        echo "Used: " . $balance['total_used'] . " days<br>";
        echo "Remaining: <span style='color: " . ($remaining > 0 ? 'green' : 'red') . ";'>" . $remaining . " days</span>";
        echo "</div>";
    }
    
} else {
    echo "<p style='color: red;'>No employee session found! Please log in first.</p>";
}

echo "<br><br>";
echo "<a href='employee/dashboard.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;'>← Employee Dashboard</a>";
?>
