<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';

session_start();

// Redirect if not authenticated as employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_messages'] = ['You must be logged in as an employee.'];
    header('Location: ../signin.php');
    exit();
}

try {
    $pdo = Database::connectDB();

    // Get employee ID
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Employee profile not found');
    }

    // Fetch clients with pagination
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Get total clients count
    $total_stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM clients c
        JOIN tasks t ON c.client_id = t.client_id
        WHERE t.employee_id = ?
    ");
    $total_stmt->execute([$employee['employee_id']]);
    $total = $total_stmt->fetchColumn();
    $total_pages = ceil($total / $limit);

    // Fetch clients assigned to the employee, including email and national_id
    $clients_stmt = $pdo->prepare("
        SELECT DISTINCT
            c.client_id,
            c.first_name,
            c.surname,
            c.company_name,
            c.phone,
            c.email,
            c.national_id
        FROM clients c
        JOIN tasks t ON c.client_id = t.client_id
        WHERE t.employee_id = ?
        ORDER BY c.surname, c.first_name
        LIMIT ? OFFSET ?
    ");
    $clients_stmt->execute([$employee['employee_id'], $limit, $offset]);
    $clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Clients Error: " . $e->getMessage());
    $_SESSION['error_messages'] = ['Failed to load clients.'];
    $clients = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - Calyda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'dashboard_styles.php'; ?>
    <link rel="stylesheet" href="employee/style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
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
                <li class="active"><a href="clients.php"><span class="icon"><i class="fas fa-users"></i></span>Clients</a></li>
                <li><a href="enquiries.php"><span class="icon"><i class="fas fa-question-circle"></i></span>Enquiries</a></li>
                <li><a href="feedback.php"><span class="icon"><i class="fas fa-comment"></i></span>Feedback</a></li>
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


        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>Assigned Clients</h1>
                <div class="user-profile">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 2)) ?></div>
                    <div class="user-info">
                        <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                        <span>Employee</span>
                    </div>
                </div>
            </header>

            <!-- Clients List -->
            <section class="section-card">
                <h2>Your Clients</h2>

                <?php if (empty($clients)): ?>
                    <p>No clients assigned yet.</p>
                <?php else: ?>
                    <div class="clients-list">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Company</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>National ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($client['first_name'] . ' ' . $client['surname']) ?></td>
                                        <td><?= htmlspecialchars($client['company_name']) ?></td>
                                        <td><?= htmlspecialchars($client['phone']) ?></td>
                                        <td><?= htmlspecialchars($client['email']) ?></td>
                                        <td><?= htmlspecialchars($client['national_id']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>">&laquo; Previous</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>>
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
       //Include the same JavaScript from dashboard.php for theme and menu toggle functionality
        <?php include 'dashboard_scripts.php'; ?>
    </script>
</body>
</html>
