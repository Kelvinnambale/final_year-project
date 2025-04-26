<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';

// Start session for authentication and messages
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Debugging function
function debugLog($message) {
    error_log($message);
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    try {
        $enquiry_id = filter_var($_POST['enquiry_id'], FILTER_SANITIZE_NUMBER_INT);
        $subject = trim(htmlspecialchars($_POST['subject']));
        $message = trim(htmlspecialchars($_POST['message']));

        if (empty($enquiry_id) || empty($subject) || empty($message)) {
            throw new Exception("All fields are required.");
        }

        // Fetch the original enquiry with improved error handling
        $pdo = Database::connectDB();
        $stmt = $pdo->prepare("
            SELECT e.client_id, e.employee_id, e.submitted_by 
            FROM enquiries e
            WHERE e.enquiry_id = ?
        ");
        $stmt->execute([$enquiry_id]);
        $enquiry = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enquiry) {
            throw new Exception("Enquiry not found.");
        }

        // Determine recipient type and ID based on who submitted the enquiry
        if ($enquiry['submitted_by'] === 'client') {
            $recipient_type = 'client';
            $recipient_id = $enquiry['client_id'];
        } else {
            $recipient_type = 'employee';
            $recipient_id = $enquiry['employee_id'];
        }

        // Additional validation for recipient
        if (empty($recipient_id)) {
            debugLog("Invalid recipient: Type: $recipient_type, ID: " . ($recipient_id ?? 'null'));
            throw new Exception("Invalid recipient information. Please check enquiry details.");
        }

        // Insert reply into feedback table
        $insert_stmt = $pdo->prepare("
            INSERT INTO feedback (
                enquiry_id, 
                subject, 
                message, 
                created_at, 
                is_admin_reply, 
                recipient_type, 
                recipient_id,
                employee_id
            ) VALUES (?, ?, ?, NOW(), 1, ?, ?, 0)
        ");

        $result = $insert_stmt->execute([
            $enquiry_id, 
            $subject, 
            $message, 
            $recipient_type, 
            $recipient_id
        ]);

        if (!$result) {
            throw new Exception("Failed to insert feedback. Database error.");
        }

        // Update enquiry status
        $update_stmt = $pdo->prepare("
            UPDATE enquiries 
            SET status = 'responded' 
            WHERE enquiry_id = ?
        ");
        $update_result = $update_stmt->execute([$enquiry_id]);

        if (!$update_result) {
            throw new Exception("Failed to update enquiry status.");
        }

        $_SESSION['success_message'] = "Reply sent successfully!";
        header('Location: enquiry.php');
        exit();

    } catch (Exception $e) {
        error_log("Reply Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to send reply: " . $e->getMessage();
        header('Location: enquiry.php');
        exit();
    }
}

// Fetch enquiries with enhanced error handling
try {
    $db = Database::connectDB();
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Modified query to show both client and employee enquiries
    $stmt = $db->prepare("
        SELECT 
            e.*,
            CASE 
                WHEN e.submitted_by = 'client' THEN c.first_name
                WHEN e.submitted_by = 'employee' THEN emp.first_name
            END as submitter_name
        FROM enquiries e
        LEFT JOIN clients c ON e.client_id = c.client_id
        LEFT JOIN employees emp ON e.employee_id = emp.employee_id
        ORDER BY e.created_at DESC
    ");
    $stmt->execute();
    $enquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    debugLog("Enquiries Fetched: " . count($enquiries));
} catch(Exception $e) {
    error_log("Enquiries Fetch Error: " . $e->getMessage());
    $enquiries = [];
    $_SESSION['error_message'] = "Failed to fetch enquiries. Error: " . $e->getMessage();
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiries - Calyda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'dashboard_styles.php'; ?>
    <link rel="stylesheet" href="admin/style.css">
    <style>
        /* Alert Styles */
.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 0.5rem;
    font-weight: 500;
}

.alert-danger {
    background-color: var(--error-color, #dc3545);
    color: white;
}

.alert-danger ul {
    list-style-type: disc;
    padding-left: 1.5rem;
}

.alert-success {
    background-color: var(--success-color, #28a745);
    color: white;
}
/*MOdal styles*/
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    overflow: auto;
}

.modal-content {
    background-color: #1e2a38;
    margin: 5% auto;
    padding: 2rem;
    border-radius: 8px;
    width: 190%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    color: #fff;
    border: 1px solid #37475f;
    text-align: center; /* Center all text content by default */
}

.modal-body{
    text-align:left;
}
.modal-content h2 {
    color: #fff;
    text-align: center;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    border-bottom: 1px solid #37475f;
    padding-bottom: 0.75rem;
}

.form-group {
    margin-bottom: 1.25rem;
    text-align: center; /* Center the form group content */
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #cfd9e6;
    text-align: left; /* Center the labels */
}

.form-group input[type="text"],
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #37475f;
    border-radius: 4px;
    background-color: #0f172a;
    color: #fff;
    font-size: 1rem;
    transition: border-color 0.2s;
    text-align: left; /* Keep input text left-aligned for better readability */
}

.form-group input[type="text"]:focus,
.form-group textarea:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
}

.form-actions {
    display: flex;
    justify-content: center; /* Center the buttons */
    gap: 1rem;
    margin-top: 1.5rem;
}

.btn {
    padding: 0.6rem 1.25rem;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-size: 0.95rem;
}

.btn-primary {
    background-color: #3b82f6;
    color: #ffffff;
}

.btn-primary:hover {
    background-color: #2563eb;
}

button[type="button"] {
    background-color: #2563eb;
    color: #ffffff;
    border: 1px solid #475569;
}

button[type="button"]:hover {
    background-color: #3b82f6;
    color: white;
    
}
    </style>
    </head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header class="header">
                <h1>Enquiries</h1>
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

            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo htmlspecialchars($_SESSION['error_message']); 
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']); 
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <section class="recent-activity">
                <h2>Enquiry List</h2>
                <table class="enquiries-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Submitted By</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($enquiries)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No enquiries found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($enquiries as $enquiry): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($enquiry['enquiry_id']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($enquiry['submitted_by'])); ?></td>
                                    <td>
                                        <span class="enquiry-type type-<?php echo htmlspecialchars($enquiry['type']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($enquiry['type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($enquiry['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($enquiry['message']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($enquiry['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($enquiry['status'] ?? 'pending'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($enquiry['status'] ?? 'Pending')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" onclick="viewEnquiryDetails(
                                            '<?php echo htmlspecialchars($enquiry['enquiry_id']); ?>',
                                            '<?php echo htmlspecialchars($enquiry['submitted_by']); ?>',
                                            '<?php echo htmlspecialchars($enquiry['type']); ?>',
                                            '<?php echo htmlspecialchars($enquiry['subject']); ?>',
                                            '<?php echo htmlspecialchars($enquiry['message']); ?>'
                                        )">
                                            View
                                        </button>
                                        <button class="action-btn reply-btn" onclick="prepareReply(
                                            '<?php echo htmlspecialchars($enquiry['enquiry_id']); ?>',
                                            '<?php echo htmlspecialchars($enquiry['subject']); ?>'
                                        )">
                                            Reply
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Modal for Enquiry Details -->
    <div id="enquiryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Enquiry Details</h2>
                <button class="close-modal" onclick="document.getElementById('enquiryModal').style.display='none'">
                &times;
                </button>
            </div>
        <div id="enquiryDetails" class="modal-body">
            <!-- Details will be dynamically inserted here -->
    </div>
    </div>
</div>

    <!-- Modal for Reply -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <h2>Reply to Enquiry</h2>
            <form id="replyForm" method="POST" action="">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" id="replyEnquiryId" name="enquiry_id" value="">
                
                <div class="form-group">
                    <label for="replySubject">Subject:</label>
                    <input type="text" id="replySubject" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label for="replyMessage">Message:</label>
                    <textarea id="replyMessage" name="message" rows="5" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('replyModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function viewEnquiryDetails(id, submittedBy, type, subject, message) {
        const modal = document.getElementById('enquiryModal');
        const details = document.getElementById('enquiryDetails');
        
        details.innerHTML = `
            <p><span class="detail-label">ID:</span> <span class="detail-value">${id}</span></p>
            <p><span class="detail-label">Submitted By:</span> <span class="detail-value">${submittedBy}</span></p>
            <p><span class="detail-label">Type:</span> <span class="detail-value">${type}</span></p>
            <p><span class="detail-label">Subject:</span> <span class="detail-value">${subject}</span></p>
            <p><span class="detail-label">Message:</span> <span class="detail-value">${message}</span></p>
        `;
        
        modal.style.display = 'flex';
    }

    function prepareReply(enquiryId, subject) {
        const modal = document.getElementById('replyModal');
        const enquiryIdInput = document.getElementById('replyEnquiryId');
        const subjectInput = document.getElementById('replySubject');
        
        // Prepopulate the subject with "Re: " prefix
        subjectInput.value = "Re: " + subject;
        enquiryIdInput.value = enquiryId;
        
        modal.style.display = 'flex';
    }
    </script>

</body>
</html>
