<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';

session_start();

// Redirect if not authenticated as client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    $_SESSION['error_messages'] = ['You must be logged in as a client.'];
    header('Location: ../signin.php');
    exit();
}

try {
    $pdo = Database::connectDB();
    
    // Get client ID
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT client_id FROM clients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        throw new Exception('Client profile not found');
    }

    // Fetch feedback with pagination
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Get total feedback count
    $total_stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM feedback f
        JOIN enquiries e ON f.enquiry_id = e.enquiry_id
        WHERE f.recipient_type = 'client' AND f.recipient_id = ?
    ");
    $total_stmt->execute([$client['client_id']]);
    $total = $total_stmt->fetchColumn();
    $total_pages = ceil($total / $limit);

    // Fetch feedback with related enquiries
    $feedback_stmt = $pdo->prepare("
        SELECT 
            f.feedback_id,
            f.subject AS feedback_subject,
            f.message AS feedback_message,
            f.created_at AS feedback_date,
            e.subject AS enquiry_subject,
            e.message AS enquiry_message,
            e.created_at AS enquiry_date
        FROM feedback f
        JOIN enquiries e ON f.enquiry_id = e.enquiry_id
        WHERE 
            f.recipient_type = 'client' 
            AND f.recipient_id = ?
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $feedback_stmt->execute([$client['client_id'], $limit, $offset]);
    $feedbacks = $feedback_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Feedback Error: " . $e->getMessage());
    $_SESSION['error_messages'] = ['Failed to load feedback.'];
    $feedbacks = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Calyda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <li ><a href="dashboard.php"><span class="icon"><i class="fas fa-home"></i></span>Dashboard</a></li>
                <li><a href="tasks.php"><span class="icon"><i class="fas fa-tasks"></i></span>My Tasks</a></li>
                <li><a href="enquiries.php"><span class="icon"><i class="fas fa-question-circle"></i></span>Enquiries</a></li>
                <li class="active"><a href="feedback.php"><span class="icon"><i class="fas fa-question-circle"></i></span>Feedback</a></li>
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
                <h1>Feedback History</h1>
                <div class="user-profile">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 2)) ?></div>
                    <div class="user-info">
                        <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                        <span>Client</span>
                    </div>
                </div>
            </header>

            <!-- Feedback List -->
            <section class="section-card">
                <h2>Received Feedback</h2>
                
                <?php if (empty($feedbacks)): ?>
                    <p>No feedback received yet.</p>
                <?php else: ?>
                    <div class="feedback-list">
                        <?php foreach ($feedbacks as $feedback): ?>
                            <div class="feedback-item">
                                <div class="feedback-header">
                                    <span class="date"><?= date('M d, Y H:i A', strtotime($feedback['feedback_date'])) ?></span>
                                </div>
                                <h3><?= htmlspecialchars($feedback['feedback_subject']) ?></h3>
                                <div class="feedback-content">
                                    <?= nl2br(htmlspecialchars($feedback['feedback_message'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
        // Theme switcher functionality
        const themeButtons = document.querySelectorAll('.theme-button');
        themeButtons.forEach(button => {
            button.addEventListener('click', () => {
                document.documentElement.setAttribute('data-theme', button.id.replace('theme', '').toLowerCase());
                themeButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
            });
        });
        // Include the same JavaScript from dashboard.php for theme and menu toggle functionality
        <?php include 'dashboard_scripts.php'; ?>
    </script>
</body>
</html>