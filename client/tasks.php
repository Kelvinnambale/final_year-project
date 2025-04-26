<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';

session_start();

// Redirect if not authenticated as a client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: ../signin.php');
    exit();
}

try {
    $pdo = Database::connectDB();
    
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

} catch (Exception $e) {
    error_log("Tasks Page Error: " . $e->getMessage());
    $tasks = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - Calyda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'dashboard_styles.php'; ?>
    <link rel="stylesheet" href="client/style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-chart-line" style="color: var(--primary-color);"></i>
                    <h2>Calyda Client</h2>
                </div>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php"><span class="icon"><i class="fas fa-home"></i></span>Dashboard</a></li>
                <li class="active"><a href="tasks.php"><span class="icon"><i class="fas fa-tasks"></i></span>My Tasks</a></li>
                <li><a href="enquiries.php"><span class="icon"><i class="fas fa-question-circle"></i></span>Enquiries</a></li>
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
                    <h1>My Tasks</h1>
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

            <section class="section-card">
                <div class="section-header">
                    <h2>Task List</h2>
                </div>
                
                <div class="task-list">
                    <?php if (empty($tasks)): ?>
                        <p>No tasks have been assigned to you yet.</p>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): 
                            $statusClass = '';
                            $progressClass = '';
                            $progressWidth = '0%';
                            
                            switch ($task['status']) {
                                case 'Completed':
                                    $statusClass = 'status-completed';
                                    $progressClass = 'progress-completed';
                                    $progressWidth = '100%';
                                    break;
                                case 'In Progress':
                                    $statusClass = 'status-in-progress';
                                    $progressClass = 'progress-in-progress';
                                    $progressWidth = '50%';
                                    break;
                                default:
                                    $statusClass = 'status-pending';
                                    $progressClass = 'progress-pending';
                                    $progressWidth = '10%';
                            }
                        ?>
                        <div class="task-item">
                            <div class="task-type">
                                <strong>Task Type:</strong> 
                                <span><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $task['task_type']))); ?></span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar <?php echo $progressClass; ?>" style="width: <?php echo $progressWidth; ?>"></div>
                            </div>
                            <div class="task-meta">
                                <div class="task-assigned">
                                    <i class="fas fa-user"></i>
                                    <span><?php 
                                        echo $task['employee_first_name'] 
                                            ? htmlspecialchars($task['employee_first_name'] . ' ' . $task['employee_surname']) 
                                            : 'Unassigned'; 
                                    ?></span>
                                </div>
                                <div class="task-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Due: <?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'No due date'; ?></span>
                                </div>
                            </div>
                            <div class="task-details">
                                <div class="task-priority">
                                    <strong>Priority:</strong> 
                                    <span class="<?php 
                                        echo $task['priority'] === 'high' ? 'text-danger' : 
                                             ($task['priority'] === 'medium' ? 'text-warning' : 'text-muted');
                                    ?>">
                                        <?php echo htmlspecialchars(ucfirst($task['priority'])); ?>
                                    </span>
                                </div>
                                <?php if (!empty($task['notes'])): ?>
                                <div class="task-notes">
                                    <strong>Notes:</strong>
                                    <p><?php echo htmlspecialchars($task['notes']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        // Include the same JavaScript from dashboard.php for theme and menu toggle functionality
        <?php include 'dashboard_scripts.php'; ?>
    </script>
</body>
</html>