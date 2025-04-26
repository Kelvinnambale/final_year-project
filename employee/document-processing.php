<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';  // Database connection
session_start();

// Check if user is an authenticated employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../signin.php");
    exit();
}

// Function to analyze the M-Pesa statement and categorize transactions
function analyzeMpesaStatement($filepath) {
    $transactions = [];
    $file = fopen($filepath, 'r');

    if ($file) {
        // Read the header row (if it exists)
        $header = fgetcsv($file);  // Adjust if your file doesn't have a header

        while (($row = fgetcsv($file)) !== false) {  // CSV format assumed
            // Assuming the columns are in a specific order; adjust as needed
            // Example columns: Transaction Date, Description, Amount, Balance
            if (count($row) < 3) {
                error_log("Skipping malformed row: " . implode(",", $row));
                continue; // Skip malformed rows
            }

            $transactionDate = $row[0];
            $description = $row[1];
            $amount = floatval($row[2]); // Convert to a number

            // Determine the category based on the description
            $category = categorizeTransaction($description);

            $transactions[] = [
                'date' => $transactionDate,
                'description' => $description,
                'amount' => $amount,
                'category' => $category,
            ];
        }
        fclose($file);
    } else {
        throw new Exception("Unable to open file.");
    }
    return $transactions;
}

// Function to categorize a transaction based on its description
function categorizeTransaction($description) {
    $description = strtolower($description); // For case-insensitive matching

    // Define your categorization rules here.  This is the *most* important part.
    if (strpos($description, 'salary') !== false) {
        return 'Salary';
    } elseif (strpos($description, 'rent') !== false) {
        return 'Rent';
    } elseif (strpos($description, 'kplc') !== false || strpos($description, 'electricity') !== false) {
        return 'Utilities';
    } elseif (strpos($description, 'transfer') !== false || strpos($description, 'sent to') !== false) {
        return 'Transfer';
    } elseif (strpos($description, 'payment received') !== false) {
        return 'Payment Received';
    } else {
        return 'Uncategorized';  // Default category
    }
}

// Handle file upload
$upload_success = false;
$transactions = [];  // Initialize transactions array
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['mpesa_statement'])) {
    try {
        $file = $_FILES['mpesa_statement'];

        // Validate file upload (VERY IMPORTANT!)
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error: " . $file['error']);
        }

        // Check file size
        if ($file['size'] > 2000000) { // 2MB limit
            throw new Exception("File is too large (max 2MB).");
        }

        // Check file type (more robust check)
        $file_type = mime_content_type($file['tmp_name']);
        if ($file_type !== 'text/csv' && $file_type !== 'text/plain') {
            throw new Exception("Invalid file type. Only CSV or TXT files are allowed.");
        }

        // Sanitize the filename
        $filename = basename($file['name']);
        $new_filename = uniqid() . "_" . $filename; // Unique filename
        $destination = 'uploads/' . $new_filename;  // Store outside webroot if possible

        // Create the 'uploads' directory if it doesn't exist
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);  // Create recursively
        }

        // Move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Analyze the statement
            $transactions = analyzeMpesaStatement($destination);
            $upload_success = true;
        } else {
            throw new Exception("Failed to move uploaded file.");
        }
    } catch (Exception $e) {
        $upload_error = $e->getMessage();
        error_log("Document Processing Error: " . $upload_error);
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Statement Analyzer - Calyda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'dashboard_styles.php'; ?>
    <link rel="stylesheet" href="employee/style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar (as in your other files) -->
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
                <li  class="active"><a href="document-processing.php"><span class="icon"><i class="fas fa-file-alt"></i></span>Document Processing</a></li>
                <li><a href="clients.php"><span class="icon"><i class="fas fa-users"></i></span>Clients</a></li>
                <li><a href="enquiries.php"><span class="icon"><i class="fas fa-question-circle"></i></span>Enquiries</a></li>
                <li><a href="settings.php"><span class="icon"><i class="fas fa-cog"></i></span>Settings</a></li>
                <li><a href="../logout.php"><span class="icon"><i class="fas fa-sign-out-alt"></i></span>Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header>
                <h1>M-Pesa Statement Analyzer</h1>
            </header>

            <section class="upload-form">
                <h2>Upload M-Pesa Statement (CSV or TXT)</h2>
                <?php if ($upload_error): ?>
                    
                        <?= htmlspecialchars($upload_error) ?>
                    
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="mpesa_statement" accept=".csv, .txt" required>
                    <button type="submit">Analyze Statement</button>
                </form>
            </section>

            <?php if ($upload_success && !empty($transactions)): ?>
                <section class="transaction-results">
                    <h2>Transaction Analysis</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= htmlspecialchars($transaction['date']) ?></td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                    <td><?= htmlspecialchars(number_format($transaction['amount'], 2)) ?></td>
                                    <td><?= htmlspecialchars($transaction['category']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
