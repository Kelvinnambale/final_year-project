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

// Function to validate ID Number
function validateIDNumber($id) {
    return preg_match('/^\d{8}$/', $id);
}

// Function to validate KRA PIN
function validateKRAPin($pin) {
    return preg_match('/^[A-Z]\d{9}[A-Z]$/', $pin);
}

// Initialize error array
$errors = array();
$success_message = "";

// Handle Excel Export
if (isset($_POST['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="employee_list.xls"');
    header('Cache-Control: max-age=0');

    echo "Employee ID\tName\tID Number\tEmail\tPhone Number\tKRA PIN\tDepartment\tPosition\tUsername\tUser Type\n";

    $result = $conn->query("SELECT e.*, u.username, u.user_type FROM employees e
                           LEFT JOIN users u ON e.user_id = u.id
                           ORDER BY e.employee_id DESC");
    while ($row = $result->fetch_assoc()) {
        echo $row['employee_id'] . "\t" . $row['first_name'] . " " . $row['second_name'] . " " . $row['surname'] . "\t" .
             $row['id_number'] . "\t" . $row['email'] . "\t" . $row['phone'] . "\t" .
             $row['kra_pin'] . "\t" . $row['department'] . "\t" . $row['position'] . "\t" .
             $row['username'] . "\t" . $row['user_type'] . "\n";
    }
    exit();
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add']) || isset($_POST['update'])) {
        $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;  // Capture employee_id if set
        $first_name = sanitize($_POST['firstName']);
        $second_name = sanitize($_POST['secondName']);
        $surname = sanitize($_POST['surname']);
        $id_number = sanitize($_POST['idNumber']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $kra_pin = sanitize($_POST['kraPin']);
        $department = sanitize($_POST['department']);
        $position = sanitize($_POST['position']);
        $username = sanitize($_POST['username']);
        $password = isset($_POST['password']) && !empty($_POST['password']) ?
                    password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
        $user_type = sanitize($_POST['userType']);
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

        // Validation
        if (empty($first_name)) $errors[] = "First name is required";
        if (empty($second_name)) $errors[] = "Second name is required";
        if (empty($surname)) $errors[] = "Surname is required";
        if (!validateIDNumber($id_number)) $errors[] = "Invalid ID number format";
        if (!validateEmail($email)) $errors[] = "Invalid email format";
        if (!validatePhone($phone)) $errors[] = "Invalid phone number format";
        if (!validateKRAPin($kra_pin)) $errors[] = "Invalid KRA PIN format";
        if (empty($department)) $errors[] = "Department is required";
        if (empty($position)) $errors[] = "Position is required";
        if (empty($username)) $errors[] = "Username is required";
        if (!$employee_id && empty($_POST['password'])) $errors[] = "Password is required for new employees"; // Check $employee_id
        if (empty($user_type)) $errors[] = "User type is required";

        // Check for duplicates
        if (!$employee_id) { // Check employee_id
            // Check email and ID number
            $stmt = $conn->prepare("SELECT employee_id FROM employees WHERE email = ? OR id_number = ?");
            $stmt->bind_param("ss", $email, $id_number);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Email or ID number already exists";
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
                    // First insert user data
                    $stmt = $conn->prepare("INSERT INTO users (username, password, user_type) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $password, $user_type);
                    $stmt->execute();
                    $user_id = $conn->insert_id;
                    $stmt->close();

                    // Then insert employee data with user_id reference
                    $stmt = $conn->prepare("INSERT INTO employees (first_name, second_name, surname, id_number, email,
                                          phone, kra_pin, department, position, user_id)
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssssssi", $first_name, $second_name, $surname, $id_number,
                                    $email, $phone, $kra_pin, $department, $position, $user_id);
                    $stmt->execute();
                    $stmt->close();

                    $success_message = "Employee added successfully!";
                } elseif (isset($_POST['update']) && $employee_id) { // Check and use $employee_id here!
                    // Update user data
                    if (!empty($password)) {
                        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, user_type=? WHERE id=?");
                        $stmt->bind_param("sssi", $username, $password, $user_type, $user_id);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET username=?, user_type=? WHERE id=?");
                        $stmt->bind_param("ssi", $username, $user_type, $user_id);
                    }
                    $stmt->execute();
                    $stmt->close();

                    // Update employee data
                    $stmt = $conn->prepare("UPDATE employees SET first_name=?, second_name=?, surname=?,
                                          id_number=?, email=?, phone=?, kra_pin=?, department=?, position=? WHERE employee_id=?");
                    $stmt->bind_param("sssssssssi", $first_name, $second_name, $surname, $id_number,
                                    $email, $phone, $kra_pin, $department, $position, $employee_id); //use employee_id
                    $stmt->execute();
                    $stmt->close();

                    $success_message = "Employee updated successfully!";
                }

                $conn->commit();
                header("Location: employee.php?success=" . urlencode($success_message));
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }

    // Handle Delete
   if (isset($_POST['delete'])) {
       $employee_id = (int)$_POST['employee_id']; // Get employee_id from the form
       $user_id = (int)$_POST['user_id'];

       $conn->begin_transaction();
       try {
           // First delete the employee
           $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id=?");  // Changed to employee_id
           $stmt->bind_param("i", $employee_id); // Bind the employee_id
           $stmt->execute();
           $stmt->close();

           // Then delete the user
           $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
           $stmt->bind_param("i", $user_id);
           $stmt->execute();
           $stmt->close();

           $conn->commit();
           $success_message = "Employee deleted successfully!";
           header("Location: employee.php?success=" . urlencode($success_message));
           exit();
       } catch (Exception $e) {
           $conn->rollback();
           $errors[] = "Error deleting employee: " . $e->getMessage();
       }
   }
}

// Fetch employee for editing
$edit_employee = null;
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT e.*, u.username, u.user_type, u.id as user_id
                           FROM employees e
                           JOIN users u ON e.user_id = u.id
                           WHERE e.employee_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $edit_employee = $row;
        $edit_user = [
            'id' => $row['user_id'],
            'username' => $row['username'],
            'user_type' => $row['user_type']
        ];
    }
    $stmt->close();
}

// Fetch all employees
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
if (!empty($search)) {
    $search_term = "%$search%";
    $stmt = $conn->prepare("SELECT e.*, u.username, u.user_type, u.id as user_id
                          FROM employees e
                          JOIN users u ON e.user_id = u.id
                          WHERE e.first_name LIKE ? OR e.second_name LIKE ? OR
                                e.surname LIKE ? OR e.email LIKE ? OR
                                e.phone LIKE ? OR e.id_number LIKE ? OR
                                e.department LIKE ? OR e.position LIKE ?
                          ORDER BY e.employee_id DESC"); // Corrected ORDER BY
    $stmt->bind_param("ssssssss", $search_term, $search_term, $search_term,
                      $search_term, $search_term, $search_term, $search_term, $search_term);
    $stmt->execute();
    $employees = $stmt->get_result();
} else {
    $employees = $conn->query("SELECT e.*, u.username, u.user_type, u.id as user_id
                             FROM employees e
                             JOIN users u ON e.user_id = u.id
                             ORDER BY e.employee_id DESC"); // Corrected ORDER BY
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

// Check if employees table exists
$check_employees_table = $conn->query("SHOW TABLES LIKE 'employees'");
if ($check_employees_table->num_rows == 0) {
    // Create employees table
    $conn->query("CREATE TABLE IF NOT EXISTS employees (
        employee_id INT(11) AUTO_INCREMENT PRIMARY KEY, // Changed to employee_id
        first_name VARCHAR(50) NOT NULL,
        second_name VARCHAR(50) NOT NULL,
        surname VARCHAR(50) NOT NULL,
        id_number VARCHAR(20) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        kra_pin VARCHAR(20) NOT NULL,
        department VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        user_id INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
}

// Check if form should be initially shown
$showForm = (!empty($edit_employee) || !empty($errors) ||
            (isset($_POST['add']) || isset($_POST['update'])));

// Fetch all departments for dropdown
$departments = $conn->query("SELECT DISTINCT department FROM employees ORDER BY department ASC");
$dept_options = [];
while ($row = $departments->fetch_assoc()) {
    if (!empty($row['department'])) {
        $dept_options[] = $row['department'];
    }
}

// Fetch all positions for dropdown
$positions = $conn->query("SELECT DISTINCT position FROM employees ORDER BY position ASC");
$pos_options = [];
while ($row = $positions->fetch_assoc()) {
    if (!empty($row['position'])) {
        $pos_options[] = $row['position'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
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

/* Warning box styling */
.warning-box {
    background-color: #fff3cd;
    color: #856404;
    padding: 0.75rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    border-left: 4px solid #ffc107;
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Employee Management</h1>
        
        <!-- Button Group -->
        <div class="header-actions">
            <div class="action-group">
                <form method="POST" action="">
                    <button type="submit" name="export_excel" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Download Excel
                    </button>
                </form>
                <button id="toggleFormBtn" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Employee
                </button>
            </div>
            
            <div class="action-group">
                <form method="GET" action="" class="search-form">
                    <input type="text" name="search" placeholder="Search employees..." 
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
        
        <!-- Employee Form -->
        <div id="employeeForm" class="form-container">
            <h2><?php echo $edit_employee ? 'Edit Employee' : 'Add New Employee'; ?></h2>
            <form method="POST" action="">
                <?php if ($edit_employee): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_employee['employee_id']; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstName">First Name:</label>
                        <input type="text" id="firstName" name="firstName" 
                               value="<?php echo $edit_employee ? htmlspecialchars($edit_employee['first_name']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="secondName">Second Name:</label>
                        <input type="text" id="secondName" name="secondName" 
                               value="<?php echo $edit_employee ? htmlspecialchars($edit_employee['second_name']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="surname">Surname:</label>
                        <input type="text" id="surname" name="surname" 
                               value="<?php echo $edit_employee ? htmlspecialchars($edit_employee['surname']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="idNumber">ID Number:</label>
                        <input type="text" id="idNumber" name="idNumber" 
                               value="<?php echo $edit_employee ? htmlspecialchars($edit_employee['id_number']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo $edit_employee ? htmlspecialchars($edit_employee['email']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo $edit_employee ? htmlspecialchars($edit_employee['phone']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="kraPin">KRA PIN:</label>
                        <input type="text" id="kraPin" name="kraPin" 
                               value="<?php echo $edit_employee ? htmlspecialchars($edit_employee['kra_pin']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="department">Department:</label>
                        <select id="department" name="department" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($dept_options as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" 
                                    <?php echo ($edit_employee && $edit_employee['department'] == $dept) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="other">Add New Department</option>
                        </select>
                        <div id="otherDeptField" style="display:none; margin-top:10px;">
                            <input type="text" id="otherDepartment" placeholder="Enter new department">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="position">Position:</label>
                        <select id="position" name="position" required>
                            <option value="">-- Select Position --</option>
                            <?php foreach ($pos_options as $pos): ?>
                                <option value="<?php echo htmlspecialchars($pos); ?>" 
                                    <?php echo ($edit_employee && $edit_employee['position'] == $pos) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pos); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="other">Add New Position</option>
                        </select>
                        <div id="otherPosField" style="display:none; margin-top:10px;">
                            <input type="text" id="otherPosition" placeholder="Enter new position">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password<?php echo $edit_employee ? ' (leave blank to keep current)' : ''; ?>:</label>
                        <input type="password" id="password" name="password" 
                               <?php echo $edit_employee ? '' : 'required'; ?>>
                    </div>

                    <div class="form-group">
                        <label for="userType">User Type:</label>
                        <select id="userType" name="userType" required>
                            <option value="">-- Select User Type --</option>
                            <option value="admin" <?php echo ($edit_user && $edit_user['user_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="employee" <?php echo ($edit_user && $edit_user['user_type'] == 'employee') ? 'selected' : ''; ?>>Employee</option>
                            <option value="client" <?php echo ($edit_user && $edit_user['user_type'] == 'client') ? 'selected' : ''; ?>>Client</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="<?php echo $edit_employee ? 'update' : 'add'; ?>" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo $edit_employee ? 'Update Employee' : 'Add Employee'; ?>
                    </button>
                    <button type="button" id="cancelFormBtn" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Employees Table -->
        <div class="table-responsive">
            <table id="employeesTable">
                <thead>
                    <tr>
                        <th>Employee Id</th>
                        <th>Full Name</th>
                        <th>ID Number</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>User Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($employees && $employees->num_rows > 0): ?>
                        <?php while ($row = $employees->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['employee_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['second_name'] . ' ' . $row['surname']); ?></td>
                                <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><?php echo htmlspecialchars($row['position']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_type']); ?></td>
                                <td class="action-buttons">
                                    <a href="?edit=<?php echo $row['employee_id']; ?>" class="btn btn-primary edit-btn">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                        <input type="hidden" name="id" value="<?php echo $row['employee_id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-danger">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align:center;">No employees found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Handle form toggle
        const toggleFormBtn = document.getElementById('toggleFormBtn');
        const cancelFormBtn = document.getElementById('cancelFormBtn');
        const employeeForm = document.getElementById('employeeForm');
        const formOverlay = document.querySelector('.form-overlay');
        
        // Function to show form
        function showForm() {
            employeeForm.style.display = 'block';
            formOverlay.style.display = 'block';
            setTimeout(() => {
                employeeForm.style.opacity = '1';
                formOverlay.style.opacity = '1';
            }, 10);
        }
        
        // Function to hide form
        function hideForm() {
            employeeForm.style.opacity = '0';
            formOverlay.style.opacity = '0';
            setTimeout(() => {
                employeeForm.style.display = 'none';
                formOverlay.style.display = 'none';
            }, 300);
        }
        
        // Event listeners
        toggleFormBtn.addEventListener('click', showForm);
        cancelFormBtn.addEventListener('click', hideForm);
        formOverlay.addEventListener('click', hideForm);
        
        // Show form if editing or errors
        <?php if ($showForm): ?>
            showForm();
        <?php endif; ?>
        
        // Handle other department input
        const departmentSelect = document.getElementById('department');
        const otherDeptField = document.getElementById('otherDeptField');
        const otherDepartment = document.getElementById('otherDepartment');
        
        departmentSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                otherDeptField.style.display = 'block';
                otherDepartment.required = true;
                this.name = ''; // Remove the name attribute from select
                
                // Add event listener to update the select when typing
                otherDepartment.addEventListener('input', function() {
                    // Create new option with current value
                    const newOption = document.createElement('option');
                    newOption.value = this.value;
                    newOption.textContent = this.value;
                    newOption.selected = true;
                    
                    // Remove "other" option if exists
                    if (departmentSelect.querySelector('option[value="' + this.value + '"]')) {
                        departmentSelect.querySelector('option[value="' + this.value + '"]').remove();
                    }
                    
                    // Add new option before "other" option
                    departmentSelect.insertBefore(newOption, departmentSelect.lastElementChild);
                    
                    // Update the name attribute
                    departmentSelect.name = 'department';
                });
            } else {
                otherDeptField.style.display = 'none';
                otherDepartment.required = false;
                this.name = 'department'; // Restore the name attribute
            }
        });
        
        // Handle other position input
        const positionSelect = document.getElementById('position');
        const otherPosField = document.getElementById('otherPosField');
        const otherPosition = document.getElementById('otherPosition');
        
        positionSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                otherPosField.style.display = 'block';
                otherPosition.required = true;
                this.name = ''; // Remove the name attribute from select
                
                // Add event listener to update the select when typing
                otherPosition.addEventListener('input', function() {
                    // Create new option with current value
                    const newOption = document.createElement('option');
                    newOption.value = this.value;
                    newOption.textContent = this.value;
                    newOption.selected = true;
                    
                    // Remove option if exists
                    if (positionSelect.querySelector('option[value="' + this.value + '"]')) {
                        positionSelect.querySelector('option[value="' + this.value + '"]').remove();
                    }
                    
                    // Add new option before "other" option
                    positionSelect.insertBefore(newOption, positionSelect.lastElementChild);
                    
                    // Update the name attribute
                    positionSelect.name = 'position';
                });
            } else {
                otherPosField.style.display = 'none';
                otherPosition.required = false;
                this.name = 'position'; // Restore the name attribute
            }
        });
        
        // Handle edit buttons
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                // Get the URL
                const url = this.getAttribute('href');
                // Navigate to URL
                window.location.href = url;
            });
        });
    </script>
</body>
</html>
