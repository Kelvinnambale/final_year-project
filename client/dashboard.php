<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';

session_start();

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: ../signin.php');
    exit();
}

$tasks = [];  // Initialize $tasks to an empty array here!
$task_stats = ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0];
$enquiries = [];

try {
    $pdo = Database::connectDB();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Get client information
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT client_id FROM clients 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        throw new Exception('Client profile not found.');
    }

    $client_id = $client['client_id'];

    // Fetch client details
    $stmt = $pdo->prepare("
        SELECT first_name, surname, email, phone, company_name FROM clients 
        WHERE client_id = ?
    ");
    $stmt->execute([$client_id]);
    $clientDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($clientDetails) {
        $first_name = $clientDetails['first_name'] ?? 'N/A';
        $surname = $clientDetails['surname'] ?? 'N/A';
        $email = $clientDetails['email'] ?? 'N/A';
        $phone = $clientDetails['phone'] ?? 'N/A';
        $company_name = $clientDetails['company_name'] ?? 'N/A';
    } else {
        $first_name = $surname = $email = $phone = $company_name = 'N/A';
    }

    // Pagination setup
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10; // Number of tasks per page
    $offset = ($page - 1) * $limit;

    // Get total number of tasks for the client
    $total_stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM tasks 
        WHERE client_id = ?
    ");
    $total_stmt->execute([$client_id]);
    $total_rows = $total_stmt->fetch()['total'];
    $total_pages = ceil($total_rows / $limit);

    // Fetch tasks with pagination - MODIFIED to join with employees table
    $task_stmt = $pdo->prepare("
        SELECT t.*, e.first_name AS employee_first_name, e.surname AS employee_surname
        FROM tasks t
        LEFT JOIN employees e ON t.employee_id = e.employee_id
        WHERE t.client_id = ? 
        ORDER BY t.due_date ASC, t.priority DESC 
        LIMIT ? OFFSET ?
    ");
    $task_stmt->execute([$client_id, $limit, $offset]);
    $tasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Task statistics calculation
    $task_stats['total'] = count($tasks);
    foreach ($tasks as $task) {
        if ($task['status'] == 'Completed') {
            $task_stats['completed']++;
        } elseif ($task['status'] == 'In Progress') {
            $task_stats['in_progress']++;
        } elseif ($task['status'] == 'Pending') {
            $task_stats['pending']++;
        }
    }

} catch(Exception $e) {
    error_log("Client Dashboard Error: " . $e->getMessage());
    $client = null;
    $tasks = []; // Initialize $tasks to an empty array inside catch block as well for extra safety.
    $task_stats = ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0];
    $enquiries = [];
}
?>


<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Calyda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'dashboard_styles.php'; ?>
    <link rel="stylesheet" href="client/style.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-chart-line" style="color: var(--primary-color);"></i>
                    <h2>Calyda Client</h2>
                </div>
                
            </div>
            <ul class="nav-links">
                <li class="active"><a href="dashboard.php"><span class="icon"><i class="fas fa-home"></i></span>Dashboard</a></li>
                <li><a href="tasks.php"><span class="icon"><i class="fas fa-tasks"></i></span>My Tasks</a></li>
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
                    <h1>Client Dashboard</h1>
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

            <?php if (isset($first_name) && isset($surname) && isset($email) && isset($phone) && isset($company_name)): ?>
    <section class="client-info">
        <div class="section-header">
            <h2>My Information</h2>
            <a href="profile.php" class="view-all">Edit Profile</a>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Name</span>
                <span class="info-value"><?php echo htmlspecialchars("$first_name $surname"); ?></span>
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
                <span class="info-label">Company</span>
                <span class="info-value"><?php echo htmlspecialchars($company_name); ?></span>
            </div>
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