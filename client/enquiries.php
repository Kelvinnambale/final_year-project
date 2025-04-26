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
    
    // Get client ID (critical fix: use proper column names)
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT client_id FROM clients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        throw new Exception('Client profile not found for user ID: ' . $user_id);
    }

    // Pagination parameters
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Get total enquiries count
    $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM enquiries WHERE client_id = ?");
    $total_stmt->execute([$client['client_id']]);
    $total = $total_stmt->fetchColumn();
    $total_pages = ceil($total / $limit);

    // Fetch enquiries with proper ordering
    $enquiry_stmt = $pdo->prepare("
        SELECT * FROM enquiries 
        WHERE client_id = ? 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $enquiry_stmt->execute([$client['client_id'], $limit, $offset]);
    $enquiries = $enquiry_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Enquiries Error: " . $e->getMessage());
    $_SESSION['error_messages'] = ['Failed to load enquiries.'];
    $enquiries = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiries - Calyda</title>
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
                <li ><a href="dashboard.php"><span class="icon"><i class="fas fa-home"></i></span>Dashboard</a></li>
                <li><a href="tasks.php"><span class="icon"><i class="fas fa-tasks"></i></span>My Tasks</a></li>
                <li class="active"><a href="enquiries.php"><span class="icon"><i class="fas fa-question-circle"></i></span>Enquiries</a></li>
                <li ><a href="feedback.php"><span class="icon"><i class="fas fa-question-circle"></i></span>Feedback</a></li>
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
            <!-- Display error messages -->
<?php if (isset($_SESSION['error_messages'])): ?>
    <div class="alert alert-error">
        <ul>
            <?php 
            foreach ($_SESSION['error_messages'] as $error): 
                echo "<li>" . htmlspecialchars($error) . "</li>";
            endforeach; 
            unset($_SESSION['error_messages']);
            ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Display success message -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <?php 
        echo htmlspecialchars($_SESSION['success_message']); 
        unset($_SESSION['success_message']);
        ?>
    </div>
<?php endif; ?>
            <header class="header">
                <div class="header-left">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>My Enquiries</h1>
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
                    <h2>Submit New Enquiry</h2>
                </div>
                
                <form class="enquiry-form" action="submit_enquiry.php" method="post">
                    <div class="form-row">
                        <label for="enquiry_type" class="form-label">Enquiry Type</label>
                        <select id="enquiry_type" name="enquiry_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="general">General</option>
                            <option value="support">Technical Support</option>
                            <option value="billing">Billing Question</option>
                            <option value="feature">Feature Request</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-input" required>
                    </div>
                    
                    <div class="form-row">
                        <label for="message" class="form-label">Message</label>
                        <textarea id="message" name="message" class="form-textarea" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <button type="submit" class="form-submit">Submit Enquiry</button>
                    </div>
                </form>
            </section>
            <section class="section-card">
            <h2>All Enquiries</h2>
            
            <?php if (empty($enquiries)): ?>
                <p>No enquiries found. <a href="#submit-form">Submit one now</a>.</p>
            <?php else: ?>
                <div class="enquiry-list">
                    <?php foreach ($enquiries as $enquiry): ?>
                        <div class="enquiry-item">
                            <div class="enquiry-header">
                                <span class="type"><?= htmlspecialchars(ucfirst($enquiry['type'])) ?></span>
                                <span class="date"><?= date('M d, Y H:i A', strtotime($enquiry['created_at'])) ?></span>
                            </div>
                            <h3><?= htmlspecialchars($enquiry['subject']) ?></h3>
                            <div class="message"><?= nl2br(htmlspecialchars($enquiry['message'])) ?></div>
                            
                            <?php if (!empty($enquiry['response'])): ?>
                                <div class="response">
                                    <strong>Response:</strong>
                                    <?= nl2br(htmlspecialchars($enquiry['response'])) ?>
                                </div>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>">&laquo; Previous</a>
                        <?php endif ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>>
                                <?= $i ?>
                            </a>
                        <?php endfor ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
                        <?php endif ?>
                    </div>
                <?php endif ?>
            <?php endif ?>
        </section>
                    
        </main>
    </div>
    
    <script>
        // Include the same JavaScript from dashboard.php for theme and menu toggle functionality
        <?php include 'dashboard_scripts.php'; ?>
    </script>
</body>
</html>