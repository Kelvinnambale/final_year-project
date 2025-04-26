<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "calyd_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input data
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number (Kenyan format)
function validatePhone($phone) {
    return preg_match('/^(?:\+254|0)[1-9]\d{8}$/', $phone);
}

// Function to validate National ID
function validateNationalID($id) {
    return preg_match('/^\d{8}$/', $id);
}

// Initialize error array
$errors = array();
$success_message = "";

// Handle Excel Export
if (isset($_POST['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="client_list.xls"');
    header('Cache-Control: max-age=0');
    
    echo "ID\tFirst Name\tSecond Name\tSurname\tCompany Name\tNational ID\tEmail\tPhone Number\tCounty\tSub-County\tUsername\tUser Type\n";
    
    $result = $conn->query("SELECT c.*, u.user_type FROM clients c 
                            LEFT JOIN users u ON c.user_id = u.id 
                            ORDER BY c.client_id DESC");
    while ($row = $result->fetch_assoc()) {
        echo $row['client_id'] . "\t" . $row['first_name'] . "\t" . $row['second_name'] . "\t" . 
             $row['surname'] . "\t" . $row['company_name'] . "\t" . $row['national_id'] . "\t" . 
             $row['email'] . "\t" . $row['phone'] . "\t" . $row['county'] . "\t" . 
             $row['sub_county'] . "\t" . $row['username'] . "\t" . $row['user_type'] . "\n";
    }
    exit();
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add']) || isset($_POST['update'])) {
        $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
        $first_name = sanitize($_POST['firstName']);
        $second_name = sanitize($_POST['secondName']);
        $surname = sanitize($_POST['surname']);
        $company_name = sanitize($_POST['companyName']); // Get Company Name
        $national_id = sanitize($_POST['nationalId']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $county = sanitize($_POST['county']);
        $sub_county = sanitize($_POST['subCounty']);
        $username = sanitize($_POST['username']);
        $password = isset($_POST['password']) && !empty($_POST['password']) ? 
                    password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
        $user_type = sanitize($_POST['userType']);
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        // Validation
        if (empty($first_name)) $errors[] = "First name is required";
        if (empty($second_name)) $errors[] = "Second name is required";
        if (empty($surname)) $errors[] = "Surname is required";
        if (empty($company_name)) $errors[] = "Company name is required"; // Validate Company Name
        if (!validateNationalID($national_id)) $errors[] = "Invalid National ID format";
        if (!validateEmail($email)) $errors[] = "Invalid email format";
        if (!validatePhone($phone)) $errors[] = "Invalid phone number format";
        if (empty($county)) $errors[] = "County is required";
        if (empty($sub_county)) $errors[] = "Sub-County is required";
        if (empty($username)) $errors[] = "Username is required";
        if (!$client_id && empty($_POST['password'])) {
            $errors[] = "Password is required for new clients";
        }
        if (empty($user_type)) $errors[] = "User type is required";
        
        
        // Check for duplicates
        if (!$client_id) {
            // Check email and national ID
            $stmt = $conn->prepare("SELECT client_id FROM clients 
                WHERE email = ? OR national_id = ?");
            $stmt->bind_param("ss", $email, $national_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Email or National ID already exists";
            }
            $stmt->close();
            // Check username
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Username already exists";
            }
            $stmt->close();
        }

        if (empty($errors)) {
            $conn->begin_transaction();
            try {
                if (isset($_POST['add'])) {
                    // Insert user data
                    $stmt = $conn->prepare("INSERT INTO users 
                        (username, password, user_type) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $password, $user_type);
                    $stmt->execute();
                    $user_id = $conn->insert_id;
                    $stmt->close();

                    // Insert client data
                    $stmt = $conn->prepare("INSERT INTO clients 
                        (first_name, second_name, surname, company_name, national_id, email, 
                        phone, county, sub_county, user_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssssssi", $first_name, $second_name, 
                        $surname, $company_name, $national_id, $email, $phone, $county, 
                        $sub_county, $user_id);
                    $stmt->execute();
                    $stmt->close();
                } elseif (isset($_POST['update']) && $client_id) {
                    // Update user data
                    if (!empty($password)) {
                        $stmt = $conn->prepare("UPDATE users SET 
                            username=?, password=?, user_type=? WHERE id=?");
                        $stmt->bind_param("sssi", $username, $password, 
                            $user_type, $user_id);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET 
                            username=?, user_type=? WHERE id=?");
                        $stmt->bind_param("ssi", $username, $user_type, 
                            $user_id);
                    }
                    $stmt->execute();
                    $stmt->close();

                    // Update client data
                    $stmt = $conn->prepare("UPDATE clients SET 
                        first_name=?, second_name=?, surname=?, company_name=?,
                        national_id=?, email=?, phone=?, county=?, 
                        sub_county=? WHERE client_id=?");
                    $stmt->bind_param("sssssssssi", $first_name, $second_name, 
                        $surname, $company_name, $national_id, $email, $phone, $county, 
                        $sub_county, $client_id);
                    $stmt->execute();
                    $stmt->close();
                }

                $conn->commit();
                $success_message = isset($_POST['add']) ? "Client added successfully!" : "Client updated successfully!";
                header("Location: client.php?success=" . urlencode($success_message));
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
    // Handle Delete
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $user_id = (int)$_POST['user_id'];
        
        $conn->begin_transaction();
        try {
            // First delete the client
            $stmt = $conn->prepare("DELETE FROM clients WHERE client_id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Then delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            $success_message = "Client deleted successfully!";
            header("Location: client.php?success=" . urlencode($success_message));
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error deleting client: " . $e->getMessage();
        }
    }
}
// Fetch client for editing
$edit_client = null;
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT c.*, u.username, u.user_type, u.id as user_id 
        FROM clients c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.client_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $edit_client = $row;
        $edit_user = [
            'id' => $row['user_id'],
            'username' => $row['username'],
            'user_type' => $row['user_type']
        ];
    }
    $stmt->close();
}

// Fetch all clients
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
if (!empty($search)) {
    $search_term = "%$search%";
    $stmt = $conn->prepare("SELECT c.*, u.username, u.user_type, u.id as user_id 
        FROM clients c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.first_name LIKE ? OR c.second_name LIKE ? OR 
              c.surname LIKE ? OR  c.company_name LIKE ? OR c.email LIKE ? OR 
              c.phone LIKE ? OR c.national_id LIKE ?
        ORDER BY c.client_id DESC");
    $stmt->bind_param("sssssss", $search_term, $search_term, $search_term, $search_term,
        $search_term, $search_term, $search_term);
    $stmt->execute();
    $clients = $stmt->get_result();
} else {
    $clients = $conn->query("SELECT c.*, u.username, u.user_type, u.id as user_id 
        FROM clients c 
        JOIN users u ON c.user_id = u.id 
        ORDER BY c.client_id DESC");
}

// Check if users table exists
$check_users_table = $conn->query("SHOW TABLES LIKE 'users'");
if ($check_users_table->num_rows == 0) {
    // Create users table
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        user_type ENUM('admin', 'client', 'employee') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Check if clients table has user_id column
$check_column = $conn->query("SHOW COLUMNS FROM clients LIKE 'user_id'");
if ($check_column->num_rows == 0) {
    // Add user_id column and remove username/password
    $conn->query("ALTER TABLE clients ADD COLUMN user_id INT(11) AFTER sub_county");
    $conn->query("ALTER TABLE clients ADD FOREIGN KEY (user_id) REFERENCES users(id)");
    
    // Migrate existing data if needed
    $existing_clients = $conn->query("SELECT * FROM clients");
    while ($client = $existing_clients->fetch_assoc()) {
        if (!empty($client['username'])) {
            $password = !empty($client['password']) ? $client['password'] : password_hash('temporary', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, user_type) VALUES (?, ?, 'client')");
            $stmt->bind_param("ss", $client['username'], $password);
            $stmt->execute();
            $user_id = $conn->insert_id;
            
            $conn->query("UPDATE clients SET user_id = $user_id WHERE client_id = {$client['client_id']}");
        }
    }
    
    // Remove old columns after migration
    // $conn->query("ALTER TABLE clients DROP COLUMN username, DROP COLUMN password"); // Removed to preserve data
}

// Add company_name column if it doesn't exist
$check_company_name_column = $conn->query("SHOW COLUMNS FROM clients LIKE 'company_name'");
if ($check_company_name_column->num_rows == 0) {
    $conn->query("ALTER TABLE clients ADD COLUMN company_name VARCHAR(255) NOT NULL AFTER surname");
}

// Check if form should be initially shown
$showForm = (!empty($edit_client) || !empty($errors) || 
            (isset($_POST['add']) || isset($_POST['update'])));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
/* Reset and base styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Roboto', Arial, sans-serif;
    background-color: #f5f7fa;
    color: #333;
    line-height: 1.6;
}

.container {
    max-width: 1300px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

/* Header */
h1 {
    font-size: 2.2rem;
    color: #2c3e50;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #eaeaea;
    padding-bottom: 0.8rem;
}

h2 {
    font-size: 1.6rem;
    color: #34495e;
    margin: 1.5rem 0 1rem;
}

/* Form container with fixed positioning */
.form-container {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1000;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    padding: 1.5rem;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    transition: all 0.3s ease;
    display: none;
}

/* Overlay when form is visible */
.form-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
    display: none;
}

/* Table responsive styles */
.table-responsive {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    padding: 1.5rem;
    margin-bottom: 2rem;
    position: relative;
    z-index: 1;
}

/* Grid layout */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.2rem;
}

.form-group {
    margin-bottom: 1.2rem;
}

/* Form elements */
label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #4a5568;
}

input[type="text"],
input[type="email"],
input[type="password"],
input[type="tel"],
select {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 0.95rem;
    transition: border-color 0.2s ease;
}

input:focus,
select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.button-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

/* Search form improvements */
.button-group form {
    display: flex;
    gap: 8px;
    align-items: stretch;
    height: 100%;
}

/* Consistent input styling */
.button-group input[type="text"] {
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    height: 42px;
    line-height: 1.5;
    min-width: 200px;
}

/* Consistent button styling */
.button-group .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    padding: 0 1.2rem;
}

/* Search button specific styling */
.button-group .btn-primary i {
    margin: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .button-group {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .button-group > div {
        width: 100%;
    }

    .button-group form {
        flex-grow: 1;
        width: 100%;
    }

    .button-group input[type="text"] {
        flex-grow: 1;
    }
}
/* Enhanced button styles */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.25rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    gap: 0.5rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.btn:active {
    transform: translateY(1px);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn i {
    font-size: 1rem;
}

/* Primary button style */
.btn-primary {
    background-color: #3498db;
    color: white;
}

.btn-primary:hover {
    background-color: #2980b9;
}

/* Success button style */
.btn-success {
    background-color: #2ecc71;
    color: white;
}

.btn-success:hover {
    background-color: #27ae60;
}

/* Danger button style */
.btn-danger {
    background-color: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background-color: #c0392b;
}

/* Button group layout */
.header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.action-group {
    display: flex;
    gap: 12px;
    align-items: center;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .header-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .action-group {
        width: 100%;
        justify-content: space-between;
    }
}

/* Search input styling */
.search-form {
    display: flex;
    gap: 8px;
    align-items: stretch;
}

.search-input {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    min-width: 250px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

/* Table styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    font-size: 0.95rem;
}

th, td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #edf2f7;
}

th {
    background-color: #f8fafc;
    font-weight: 600;
    color: #4a5568;
}

tr:hover {
    background-color: #f8f9fa;
}

/* Notifications */
.error-list, .success-message {
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 6px;
    list-style-position: inside;
}

.error-list {
    color: #721c24;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
}

.success-message {
    color: #155724;
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
}

/* Section divider */
.section-divider {
    border-top: 1px solid #eaeaea;
    margin: 2rem 0 1.5rem;
    padding-top: 1.5rem;
}

.section-title {
    margin-bottom: 1rem;
    color: #4299e1;
    font-weight: bold;
    font-size: 1.6rem;
    color: #34495e;
}

/* Action buttons in table */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.action-buttons .btn {
    margin: 0;
    padding: 0.5rem;
    display: inline-flex;
    justify-content: center;
    align-items: center;
}

/* Add styles for the form toggle */
.toggle-btn {
    margin-bottom: 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    h1 {
        font-size: 1.8rem;
    }
    
    .btn {
        padding: 0.6rem 1rem;
    }
}

/* Fix href issues */
a.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

a.btn i {
    margin: 0;
}

/* Fix spacing */
.btn + .btn {
    margin-left: 0.5rem;
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Client Management</h1>
        
        <!-- Button Group -->
        <div class="header-actions">
    <div class="action-group">
        <form method="POST" action="">
            <button type="submit" name="export_excel" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Download Excel
            </button>
        </form>
        <button id="toggleFormBtn" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Client
        </button>
    </div>
    
    <div class="action-group">
        <form method="GET" action="" class="search-form">
            <input type="text" name="search" placeholder="Search clients..." 
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                   class="search-input">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
            </button>
        </form>
        <a href="dashboard.php" class="btn btn-primary">Back</a>
    </div>
</div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        
        <!-- Form Overlay -->
        <div class="form-overlay"></div>
        
        <!-- Client Form -->
        <div id="clientForm" class="form-container">
            <h2><?php echo $edit_client ? 'Edit Client' : 'Add New Client'; ?></h2>
            <form method="POST" action="">
                <?php if ($edit_client): ?>
                    <input type="hidden" name="client_id" value="<?php echo $edit_client['client_id']; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstName">First Name:</label>
                        <input type="text" id="firstName" name="firstName" 
                               value="<?php echo $edit_client ? htmlspecialchars($edit_client['first_name']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="secondName">Second Name:</label>
                        <input type="text" id="secondName" name="secondName" 
                               value="<?php echo $edit_client ? htmlspecialchars($edit_client['second_name']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="surname">Surname:</label>
                        <input type="text" id="surname" name="surname" 
                               value="<?php echo $edit_client ? htmlspecialchars($edit_client['surname']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="companyName">Company Name:</label>
                        <input type="text" id="companyName" name="companyName" 
                            value="<?php echo $edit_client ? htmlspecialchars($edit_client['company_name']) : ''; ?>" 
                            required>
                    </div>


                    <div class="form-group">
                        <label for="nationalId">National ID:</label>
                        <input type="text" id="nationalId" name="nationalId" 
                               value="<?php echo $edit_client ? htmlspecialchars($edit_client['national_id']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo $edit_client ? htmlspecialchars($edit_client['email']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo $edit_client ? htmlspecialchars($edit_client['phone']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="county">County:</label>
                        <input type="text" id="county" name="county" 
                               value="<?php echo $edit_client ? htmlspecialchars($edit_client['county']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="subCounty">Sub-County:</label>
                        <input type="text" id="subCounty" name="subCounty" 
                               value="<?php echo $edit_client ? htmlspecialchars($edit_client['sub_county']) : ''; ?>" 
                               required>
                    </div>
                </div>

                <div class="section-divider">
                    <div class="section-title">User Authentication Details</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" 
                                   value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password:<?php echo $edit_client ? ' (Leave blank to keep current)' : ''; ?></label>
                            <input type="password" id="password" name="password" 
                                   <?php echo $edit_client ? '' : 'required'; ?>>
                        </div>

                        <div class="form-group">
                            <label for="userType">User Type:</label>
                            <select id="userType" name="userType" required>
                                <option value="">-- Select User Type --</option>
                                <option value="admin" <?php echo ($edit_user && $edit_user['user_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="client" <?php echo ($edit_user && $edit_user['user_type'] == 'client') ? 'selected' : ''; ?>>Client</option>
                                <option value="employee" <?php echo ($edit_user && $edit_user['user_type'] == 'employee') ? 'selected' : ''; ?>>Employee</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="button-group" style="margin-top: 1.5rem;">
                    <?php if ($edit_client): ?>
                        <button type="submit" name="update" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Client
                        </button>
                    <?php else: ?>
                        <button type="submit" name="add" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Add Client
                        </button>
                    <?php endif; ?>
                    <button type="button" id="cancelForm" class="btn btn-danger">
                        <i class="fas fa-times-circle"></i> Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Clients Table -->
        <div class="table-responsive">
            <h2><?php echo isset($_GET['search']) ? 'Search Results' : 'Client List'; ?></h2>
            
            <?php if ($clients->num_rows == 0): ?>
        <p>No clients found. Total clients: <?php echo $clients->num_rows; ?></p>
                <p>No clients found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Client_id</th>
                            <th>Name</th>
                            <th>National ID</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th>Username</th>
                            <th>User Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $clients->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['client_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['second_name'] . ' ' . $row['surname']); ?></td>
                                <td><?php echo htmlspecialchars($row['national_id']); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($row['email']); ?></div>
                                    <div><?php echo htmlspecialchars($row['phone']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($row['county'] . ', ' . $row['sub_county']); ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_type']); ?></td>
                                <td class="action-buttons">
                                    <a href="?edit=<?php echo $row['client_id']; ?>" class="btn btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this client?');" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-danger" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Form visibility toggle
        document.addEventListener('DOMContentLoaded', function() {
            const formContainer = document.getElementById('clientForm');
            const formOverlay = document.querySelector('.form-overlay');
            const toggleFormBtn = document.getElementById('toggleFormBtn');
            const cancelFormBtn = document.getElementById('cancelForm');
            
            // Check if form should be shown initially (for edit or errors)
            const showFormInitially = <?php echo $showForm ? 'true' : 'false'; ?>;
            
            if (showFormInitially) {
                formContainer.style.display = 'block';
                formOverlay.style.display = 'block';
            }
            
            toggleFormBtn.addEventListener('click', function() {
                formContainer.style.display = 'block';
                formOverlay.style.display = 'block';
            });
            
            cancelFormBtn.addEventListener('click', function() {
                formContainer.style.display = 'none';
                formOverlay.style.display = 'none';
            });
            
            formOverlay.addEventListener('click', function() {
                formContainer.style.display = 'none';
                formOverlay.style.display = 'none';
            });
        });
    </script>
</body>
</html>