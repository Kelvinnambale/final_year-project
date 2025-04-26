<?php

require_once '../config.php';

session_start();

// Redirect to signin if user is not authenticated
if (!isset($_SESSION['username'])) {
    header('Location: ../signin.php');
    exit();
}

try {
    // Connect to the database
    $pdo = Database::connectDB();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Retrieve CSRF token from URL on successful login
    if (isset($_GET['csrf_token'])) {
        $_SESSION['csrf_token'] = $_GET['csrf_token'];
    }

    // Get CSRF token from session
    $csrfToken = $_SESSION['csrf_token'] ?? '';

    // Fetch statistics and enquiries
    $result = Database::getStats();
    $stats = $result['stats'];
    $task_stats = $result['task_stats'];

    // Fetch enquiries
    $stmt = $pdo->prepare("SELECT * FROM enquiries ORDER BY created_at DESC");
    $stmt->execute();
    $enquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active users count
    $activeUserCount = Database::getActiveUsers();

    // Add this after the try block where you get $activeUserCount
    error_log("Debug - Session Info: " . print_r($_SESSION, true));
    error_log("Debug - Active Users Count: " . $activeUserCount);

} catch (Exception $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
    $stats = ['total_clients' => 0, 'total_employees' => 0];
    $task_stats = ['completed_tasks' => 0, 'pending_tasks' => 0];
    $activities = [];
    $activeUserCount = 0;
}

try {
    // Fetch recent activities
    $activities = Database::getActivities();
} catch (Exception $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
    $activities = []; // Initialize with an empty array if an error occurs
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Calyda</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Stylesheet -->
    <?php include 'dashboard_styles.php'; ?>
    <link rel="stylesheet" href="admin/style.css">
</head>
<body class="light">
<div class="container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-chart-line" style="color: var(--primary-color);"></i>
                <h2>Calyda Admin</h2>
            </div>
        </div>
        <ul class="nav-links">
            <li class="active"><a href="dashboard.php"><span class="icon"><i class="fas fa-home"></i></span>Dashboard</a></li>
            <li><a href="client.php"><span class="icon"><i class="fas fa-users"></i></span>Clients</a></li>
            <li><a href="employee.php"><span class="icon"><i class="fas fa-user-tie"></i></span>Employees</a></li>
            <li><a href="task.php"><span class="icon"><i class="fas fa-tasks"></i></span>Tasks</a></li>
            <li><a href="enquiry.php"><span class="icon"><i class="fas fa-envelope"></i></span>Enquiries</a></li>
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
    </aside>
    <main class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>Dashboard</h1>
            </div>
            <div class="user-controls">
                <div class="user-profile">
                    <div class="user-avatar">
                        AD
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <section class="stats-container">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-info">
                        <h3>Total Enquiries</h3>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo count($enquiries); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-info">
                        <h3>Total Clients</h3>
                    </div>
                    <div class="stat-icon client-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo htmlspecialchars($stats['total_clients']); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-info">
                        <h3>Total Employees</h3>
                    </div>
                    <div class="stat-icon employee-icon">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo htmlspecialchars($stats['total_employees']); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-info">
                        <h3>Completed Tasks</h3>
                    </div>
                    <div class="stat-icon completed-icon">
                        <i class="fa-solid fa-check"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo htmlspecialchars($task_stats['completed_tasks']); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-info">
                        <h3>Pending Tasks</h3>
                    </div>
                    <div class="stat-icon task-icon">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo htmlspecialchars($task_stats['pending_tasks']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-info">
                        <h3>Active Users</h3>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $activeUserCount; ?></div>
            </div>
        </section>

        <section class="section-card">
            <div class="section-header">
                <h2>Recent Activities</h2>
            </div>
            <div class="activity-list">
                <?php foreach ($enquiries as $enquiry): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div class="activity-info">
                            <h3 class="activity-title"><?php echo htmlspecialchars($enquiry['subject']); ?></h3>
                            <span class="activity-time"><?php echo htmlspecialchars(Database::formatTimeAgo($enquiry['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php if ($activity['type'] === 'client'): ?>
                                <i class="fa-solid fa-user-plus"></i>
                            <?php elseif ($activity['type'] === 'employee'): ?>
                                <i class="fa-solid fa-user-tie"></i>
                            <?php elseif ($activity['type'] === 'task'): ?>
                                <i class="fa-solid fa-list-check"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-question"></i>
                            <?php endif; ?>
                        </div>
                        <div class="activity-info">
                            <h3 class="activity-title"><?php echo htmlspecialchars($activity['action']); ?></h3>
                            <span class="activity-time"><?php echo htmlspecialchars(Database::formatTimeAgo($activity['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>
<script>
    // Theme switcher
    const themeButtons = document.querySelectorAll('.theme-button');
    const body = document.body;

    // Check for saved theme preference or use system preference
    const savedTheme = localStorage.getItem('theme') || 'auto';
    setTheme(savedTheme);

    // Set active button based on current theme
    themeButtons.forEach(button => {
        if (button.id === `theme${savedTheme.charAt(0).toUpperCase() + savedTheme.slice(1)}`) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });

    // Add click event to theme buttons
    themeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const theme = button.id.replace('theme', '').toLowerCase();
            setTheme(theme);

            // Update active button
            themeButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // Save theme preference
            localStorage.setItem('theme', theme);
        });
    });

    function setTheme(theme) {
        if (theme === 'auto') {
            // Check system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            body.classList.toggle('dark-theme', prefersDark);

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (localStorage.getItem('theme') === 'auto') {
                    body.classList.toggle('dark-theme', e.matches);
                }
            });
        } else if (theme === 'dark') {
            body.classList.add('dark-theme');
        } else {
            body.classList.remove('dark-theme');
        }
    }

    // Function to update dashboard stats
    function updateDashboardStats() {
        fetch('../update_session.php', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update statistics
                document.querySelector('.stat-card:nth-child(2) .stat-number').textContent = data.stats.total_clients;
                document.querySelector('.stat-card:nth-child(3) .stat-number').textContent = data.stats.total_employees;
                document.querySelector('.stat-card:nth-child(4) .stat-number').textContent = data.stats.completed_tasks;
                document.querySelector('.stat-card:nth-child(5) .stat-number').textContent = data.stats.pending_tasks;
                document.querySelector('.stat-card:nth-child(6) .stat-number').textContent = data.stats.active_users;
            } else {
                // Session expired, redirect to login
                window.location.href = '../signin.php';
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Update stats every 30 seconds
    setInterval(updateDashboardStats, 30000);

    // Update immediately when page loads
    document.addEventListener('DOMContentLoaded', updateDashboardStats);

    // Add confirmation to logout
    document.querySelector('.logout-btn').addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to logout?')) {
            this.closest('form').submit();
        }
    });
</script>
</body>
</html>