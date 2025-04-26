<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';

session_start();

// Check if user is logged in and is a employee
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../signin.php');
    exit();
}

try {
    $pdo = Database::connectDB();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get employee information
    $employee_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT * FROM employees 
        WHERE user_id = ?
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Handle profile update
    $update_success = false;
    $update_error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate and sanitize input
        $first_name = trim($_POST['first_name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['company_name'] ?? '');
        
        // Basic validation
        if (empty($first_name) || empty($surname) || empty($email)) {
            $update_error = 'First name, surname, and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $update_error = 'Invalid email format.';
        } else {
            try {
                // Update employee information
                $update_stmt = $pdo->prepare("
                    UPDATE employees 
                    SET first_name = ?, 
                        surname = ?, 
                        email = ?, 
                        phone = ?, 
                        department = ?
                    WHERE user_id = ?
                ");
                $update_stmt->execute([
                    $first_name, 
                    $surname, 
                    $email, 
                    $phone, 
                    $department, 
                    $employee_id
                ]);
                
                // Refresh client data
                $stmt = $pdo->prepare("
                    SELECT * FROM employees 
                    WHERE user_id = ?
                ");
                $stmt->execute([$client_id]);
                $employee = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $update_success = true;
                
                // Update session username if email changes
                $_SESSION['username'] = $first_name . ' ' . $surname;
            } catch (PDOException $e) {
                $update_error = 'Error updating profile: ' . $e->getMessage();
            }
        }
    }
    
} catch(Exception $e) {
    error_log("Profile Page Error: " . $e->getMessage());
    $client = null;
    $update_error = 'An error occurred while retrieving your profile.';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Calyda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'dashboard_styles.php'; ?>
    <link rel="stylesheet" href="employee/style.css">
        
</head>
<body>
    <div class="container">
    <nav class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-chart-line" style="color: var(--primary-color);"></i>
                    <h2>Calyda Employee</h2>
                </div>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php"><span class="icon"><i class="fas fa-home"></i></span>Dashboard</a></li>
                <li><a href="tasks.php"><span class="icon"><i class="fas fa-tasks"></i></span>Tasks</a></li>
                <li><a href="clients.php"><span class="icon"><i class="fas fa-users"></i></span>Clients</a></li>
                <li><a href="enquiries.php"><span class="icon"><i class="fas fa-question-circle"></i></span>Enquiries</a></li>
                <li><a href="feedback.php"><span class="icon"><i class="fas fa-question-circle"></i></span>Feedback</a></li>
                <li class="active"><a href="profile.php"><span class="icon"><i class="fas fa-user"></i></span>Profile</a></li>
            </ul>
            <div class="theme-selector">
                <span class="theme-caption">Theme</span>
                    <button id="themeAuto" class="theme-button active" aria-label="Auto theme">
                        <i class="fas fa-circle-half-stroke"></i>
                    </button>
                    <button id="themeLight" class="theme-button" aria-label="Light theme">
                        <i class="fas fa-sun"></i>
                    </button>
                    <button id="themeDark" class="theme-button" aria-label="Dark theme">
                        <i class="fas fa-moon"></i>
                    </button>
            </div>
            <form action="logout.php" method="POST" style="margin-top: 2rem;">
                <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </nav>
        
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>My Profile</h1>
                </div>
                <div class="user-controls">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo htmlspecialchars(strtoupper(substr($_SESSION['username'], 0, 2))); ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <span class="user-role">Client</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="profile-container section-card">
                <?php if ($update_success): ?>
                    <div class="success-message">
                        Profile updated successfully!
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($update_error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($update_error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($employee): ?>
               
                <form class="profile-form" method="POST" action="">
                    <div class="form-row">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" id="first_name" name="first_name" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($employee['first_name']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-row">
                        <label for="surname" class="form-label">Surname</label>
                        <input type="text" id="surname" name="surname" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($employee['surname']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-row">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($employee['email']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-row">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" id="phone" name="phone" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($employee['phone']); ?>">
                    </div>
                    
                    <div class="form-row">
                        <label for="company_name" class="form-label">Department</label>
                        <input type="text" id="department" name="department" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($employee['department']); ?>">
                    </div>
                    
                    <div class="form-row">
                        <button type="submit" class="form-submit">Update Profile</button>
                    </div>
                </form>
                <?php else: ?>
                    <p>Unable to retrieve profile information. Please try again later.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Theme management and mobile menu toggle script from dashboard.php
        <?php 
        // Extract the script from dashboard.php
        $dashboardContent = file_get_contents('dashboard.php');
        preg_match('/<script>(.*?)<\/script>/s', $dashboardContent, $matches);
        echo $matches[1]; 
        ?>
    </script>
</body>
</html>