<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';

session_start();

// Check if user is logged in and is an employee
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
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT employee_id FROM employees 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Employee profile not found.');
    }

    $employee_id = $employee['employee_id'];

    // Fetch employee details
    $stmt = $pdo->prepare("
        SELECT first_name, second_name, surname, id_number, email, phone, kra_pin, department FROM employees 
        WHERE employee_id = ?
    ");
    $stmt->execute([$employee_id]);
    $employeeDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employeeDetails) {
        throw new Exception('Employee details not found.');
    }

    // Assign employee details to variables
    $first_name = $employeeDetails['first_name'] ?? 'N/A';
    $second_name = $employeeDetails['second_name'] ?? 'N/A';
    $surname = $employeeDetails['surname'] ?? 'N/A';
    $id_number = $employeeDetails['id_number'] ?? 'N/A';
    $email = $employeeDetails['email'] ?? 'N/A';
    $phone = $employeeDetails['phone'] ?? 'N/A';
    $kra_pin = $employeeDetails['kra_pin'] ?? 'N/A';
    $department = $employeeDetails['department'] ?? 'N/A';

    // Fetch tasks for this employee
$tasks_stmt = $pdo->prepare("
SELECT * FROM tasks 
WHERE employee_id = ?
");
$tasks_stmt->execute([$employee_id]);
$tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// If no tasks are found, initialize an empty array to prevent errors
if (!$tasks) {
$tasks = [];
}

} catch(Exception $e) {
    error_log("Employee Dashboard Error: " . $e->getMessage());
    $employeeDetails = null;
    $error_message = "An error occurred: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Calyda</title>
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
                <li class="active"><a href="dashboard.php"><span class="icon"><i class="fas fa-home"></i></span>Dashboard</a></li>
                <li><a href="tasks.php"><span class="icon"><i class="fas fa-tasks"></i></span>Tasks</a></li>
                <li><a href="clients.php"><span class="icon"><i class="fas fa-users"></i></span>Clients</a></li>
                <li><a href="enquiries.php"><span class="icon"><i class="fas fa-question-circle"></i></span>Enquiries</a></li>
                <li><a href="feedback.php"><span class="icon"><i class="fas fa-question-circle"></i></span>Feedback</a></li>
                <li><a href="profile.php"><span class="icon"><i class="fas fa-user"></i></span>Profile</a></li>
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
                    <h1>Employee Dashboard</h1>
                </div>
                <div class="user-controls">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo htmlspecialchars(strtoupper(substr($_SESSION['username'], 0, 2))); ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <span class="user-role">Employee</span>
                        </div>
                    </div>
                </div>
            </header>

            <?php if (isset($employeeDetails)): ?>
                <section class="employee-info">
                    <div class="section-header">
                        <h2>My Information</h2>
                        <a href="profile.php" class="view-all">Edit Profile</a>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">First Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($first_name); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Second Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($second_name); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Surname</span>
                            <span class="info-value"><?php echo htmlspecialchars($surname); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ID Number</span>
                            <span class="info-value"><?php echo htmlspecialchars($id_number); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($email); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <span class="info-value"><?php echo htmlspecialchars($phone); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">KRA PIN</span>
                            <span class="info-value"><?php echo htmlspecialchars($kra_pin); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Department</span>
                            <span class="info-value"><?php echo htmlspecialchars($department); ?></span>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <section class="employee-info">
                    <div class="section-header">
                        <h2>My Information</h2>
                    </div>
                    <div class="info-grid">
                        <p>No employee information available.</p>
                        <?php if (isset($error_message)): ?>
                            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
            <section class="section-card">
                <div class="section-header">
                    <h2>Tasks Overview</h2>
                </div>
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon completed-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-info">
                            <h3>Completed Tasks</h3>
                            <p class="stat-number"><?php 
                                echo count(array_filter($tasks, function($task) { 
                                    return $task['status'] === 'Completed'; 
                                })); 
                            ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon progress-icon">
                                <i class="fas fa-spinner"></i>
                            </div>
                        </div>
                        <div class="stat-info">
                            <h3>In Progress</h3>
                            <p class="stat-number"><?php 
                                echo count(array_filter($tasks, function($task) { 
                                    return $task['status'] === 'In Progress'; 
                                })); 
                            ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon pending-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                        </div>
                        <div class="stat-info">
                            <h3>Pending Tasks</h3>
                            <p class="stat-number"><?php 
                                echo count(array_filter($tasks, function($task) { 
                                    return $task['status'] === 'Pending'; 
                                })); 
                            ?></p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Theme management
        const themeAutoBtn = document.getElementById('themeAuto');
        const themeLightBtn = document.getElementById('themeLight');
        const themeDarkBtn = document.getElementById('themeDark');
        const htmlEl = document.documentElement;
        
        // Check for saved theme preference or use OS preference
        const savedTheme = localStorage.getItem('theme');
        
        function setTheme(theme) {
            if (theme === 'dark') {
                document.body.classList.add('dark-theme');
                themeDarkBtn.classList.add('active');
                themeLightBtn.classList.remove('active');
                themeAutoBtn.classList.remove('active');
                localStorage.setItem('theme', 'dark');
            } else if (theme === 'light') {
                document.body.classList.remove('dark-theme');
                themeLightBtn.classList.add('active');
                themeDarkBtn.classList.remove('active');
                themeAutoBtn.classList.remove('active');
                localStorage.setItem('theme', 'light');
            } else {
                // Auto - use OS preference
                themeAutoBtn.classList.add('active');
                themeLightBtn.classList.remove('active');
                themeDarkBtn.classList.remove('active');
                localStorage.removeItem('theme');
                
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.body.classList.add('dark-theme');
                } else {
                    document.body.classList.remove('dark-theme');
                }
            }
        }
        
        // Set initial theme
        if (savedTheme) {
            setTheme(savedTheme);
        } else {
            setTheme('auto');
        }
        
        // Theme button listeners
        themeAutoBtn.addEventListener('click', () => setTheme('auto'));
        themeLightBtn.addEventListener('click', () => setTheme('light'));
        themeDarkBtn.addEventListener('click', () => setTheme('dark'));
        
        // Listen for OS theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (!localStorage.getItem('theme')) {
                    if (e.matches) {
                        document.body.classList.add('dark-theme');
                    } else {
                        document.body.classList.remove('dark-theme');
                    }
                }
            });
        }
        
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.querySelector('.sidebar');
        
        mobileMenuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1024 && 
                !sidebar.contains(e.target) && 
                !mobileMenuToggle.contains(e.target) && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>
