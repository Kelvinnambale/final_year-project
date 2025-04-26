<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "calyd_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);


// Check connection and display error if fails
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Debug queries with error handling
try {
    $employees_query = "SELECT employee_id, first_name, second_name, surname FROM employees ORDER BY first_name";
    $employees = $conn->query($employees_query);
    if (!$employees) {
        throw new Exception("Error fetching employees: " . $conn->error);
    }

    $clients_query = "SELECT client_id, first_name, surname FROM clients ORDER BY first_name";
    $clients = $conn->query($clients_query);
    if (!$clients) {
        throw new Exception("Error fetching clients: " . $conn->error);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

// Handle DELETE request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_task'])) {
    try {
        $task_id = (int)$_POST['task_id'];
        
        $delete_sql = "DELETE FROM tasks WHERE task_id = ?";
        $stmt = $conn->prepare($delete_sql);
        
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $task_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF'] . "?delete_success=1");
        exit();
    } catch (Exception $e) {
        $error = "Error deleting task: " . $e->getMessage();
    }
}

// Handle EDIT request - Fetch task for editing
if (isset($_GET['edit_task'])) {
    try {
        $task_id = (int)$_GET['edit_task'];
        $edit_query = "SELECT * FROM tasks WHERE id = ?";
        $stmt = $conn->prepare($edit_query);
        
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $task_to_edit = $result->fetch_assoc();
        $stmt->close();
        
        if (!$task_to_edit) {
            throw new Exception("Task not found");
        }
    } catch (Exception $e) {
        $error = "Error fetching task: " . $e->getMessage();
    }
}

// Handle form submission for new task or update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['assign_task']) || isset($_POST['update_task'])) {
        try {
            // Validate required fields
            $required_fields = ['employee', 'client', 'task_type', 'due_date', 'priority'];
            $errors = [];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
                }
            }
            
            if (empty($errors)) {
                // Sanitize and prepare data
                $employee_id = mysqli_real_escape_string($conn, $_POST['employee']);
                $client_id = mysqli_real_escape_string($conn, $_POST['client']);
                $task_type = mysqli_real_escape_string($conn, $_POST['task_type']);
                $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
                $priority = mysqli_real_escape_string($conn, $_POST['priority']);
                $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
                $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'Pending';
                
                if (isset($_POST['update_task'])) {
                    // Update existing task
                    $task_id = (int)$_POST['task_id'];
                    
                    $sql = "UPDATE tasks SET 
                            employee_id = ?, 
                            client_id = ?, 
                            task_type = ?, 
                            due_date = ?, 
                            priority = ?, 
                            notes = ?, 
                            status = ? 
                            WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt === false) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("iisssssi", $employee_id, $client_id, $task_type, $due_date, $priority, $notes, $status, $task_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                    
                    $stmt->close();
                    header("Location: " . $_SERVER['PHP_SELF'] . "?update_success=1");
                    exit();
                } else {
                    // Insert new task
                    $sql = "INSERT INTO tasks (employee_id, client_id, task_type, due_date, priority, notes, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt === false) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("iisssss", $employee_id, $client_id, $task_type, $due_date, $priority, $notes, $status);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                    
                    $stmt->close();
                    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                    exit();
                }
            } else {
                $error = implode("<br>", $errors);
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
            error_log($error);
        }
    }
}

// Fetch tasks with proper JOIN and error handling
try {
    $tasks_query = "SELECT t.*, 
                          CONCAT(e.first_name, ' ', e.surname) as employee_name,
                          CONCAT(c.first_name, ' ', c.surname) as client_name
                   FROM tasks t
                   LEFT JOIN employees e ON t.employee_id = e.employee_id
                   LEFT JOIN clients c ON t.client_id = c.client_id
                   ORDER BY t.due_date ASC";
    $tasks = $conn->query($tasks_query);
    if (!$tasks) {
        throw new Exception("Error fetching tasks: " . $conn->error);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            padding: 20px;
            line-height: 1.6;
        }

        .content-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Form styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        select, input[type="date"], textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }

        /* Table styles */
        .table-container {
            overflow-x: auto;
            margin-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        /* Priority and status badges */
        .priority, .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }

        .priority.high { background-color: #dc3545; color: white; }
        .priority.medium { background-color: #ffc107; }
        .priority.low { background-color: #28a745; color: white; }
        .status.pending { background-color: #ffc107; }
        .status.completed { background-color: #28a745; color: white; }
        .status.in-progress { background-color: #17a2b8; color: white; }

        /* Buttons */
        .btn-primary, .btn-secondary {
            padding: 10px 20px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }

        .btn-primary {
            background-color: #0d6efd;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-right: 5px;
        }
        
        .btn-icon.delete {
            color: #dc3545;
        }
        
        .btn-icon.edit {
            color: #0d6efd;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
        }
        .error {
            border-color: #dc3545!important;
        }
    </style>
</head>
<body>
    <div class="content-section">
        <h1>Task Management</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">Task assigned successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['update_success'])): ?>
            <div class="success-message">Task updated successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['delete_success'])): ?>
            <div class="success-message">Task deleted successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="taskForm">
            <h2><?php echo isset($task_to_edit) ? 'Edit Task' : 'Assign New Task'; ?></h2>
            
            <?php if (isset($task_to_edit)): ?>
                <input type="hidden" name="task_id" value="<?php echo $task_to_edit['task_id']; ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Employee</label>
                    <select name="employee" required>
                        <option value="">Select Employee</option>
                        <?php 
                        if ($employees && $employees->num_rows > 0) {
                            $employees->data_seek(0);
                            while ($employee = $employees->fetch_assoc()): 
                                $selected = isset($task_to_edit) && $task_to_edit['employee_id'] == $employee['id'] ? 'selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($employee['employee_id']); ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['surname']); ?>
                                </option>
                            <?php endwhile;
                        } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Client</label>
                    <select name="client" required>
                        <option value="">Select Client</option>
                        <?php 
                        if ($clients && $clients->num_rows > 0) {
                            $clients->data_seek(0);
                            while ($client = $clients->fetch_assoc()): 
                                $selected = isset($task_to_edit) && $task_to_edit['client_id'] == $client['id'] ? 'selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($client['client_id']); ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['surname']); ?>
                                </option>
                            <?php endwhile;
                        } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Task Type</label>
                    <select name="task_type" required>
                        <option value="">Select Task Type</option>
                        <?php 
                            $task_types = ['tax_filing' => 'Tax Filing', 'audit' => 'Audit', 'accounting' => 'Accounting'];
                            foreach ($task_types as $value => $label):
                                $selected = isset($task_to_edit) && $task_to_edit['task_type'] == $value ? 'selected' : '';
                        ?>
                            <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" required min="<?php echo date('Y-m-d'); ?>" 
                           value="<?php echo isset($task_to_edit) ? date('Y-m-d', strtotime($task_to_edit['due_date'])) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" required>
                        <option value="">Select Priority</option>
                        <?php 
                            $priorities = ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];
                            foreach ($priorities as $value => $label):
                                $selected = isset($task_to_edit) && $task_to_edit['priority'] == $value ? 'selected' : '';
                        ?>
                            <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (isset($task_to_edit)): ?>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <?php 
                            $statuses = ['Pending' => 'Pending', 'In Progress' => 'In Progress', 'Completed' => 'Completed'];
                            foreach ($statuses as $value => $label):
                                $selected = $task_to_edit['status'] == $value ? 'selected' : '';
                        ?>
                            <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="3"><?php echo isset($task_to_edit) ? htmlspecialchars($task_to_edit['notes']) : ''; ?></textarea>
            </div>

            <div class="form-actions">
                <?php if (isset($task_to_edit)): ?>
                    <button type="submit" name="update_task" class="btn-primary">Update Task</button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>'">Cancel</button>
                <?php else: ?>
                    <button type="submit" name="assign_task" class="btn-primary">Assign Task</button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='dashboard.php'">Cancel</button>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Client</th>
                        <th>Task Type</th>
                        <th>Due Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($tasks && $tasks->num_rows > 0) {
                        while ($task = $tasks->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['task_id']); ?></td>
                                <td><?php echo htmlspecialchars($task['employee_name']); ?></td>
                                <td><?php echo htmlspecialchars($task['client_name']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $task['task_type']))); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($task['due_date'])); ?></td>
                                <td><span class="priority <?php echo htmlspecialchars($task['priority']); ?>"><?php echo ucfirst($task['priority']); ?></span></td>
                                <td><span class="status <?php echo strtolower(str_replace(' ', '-', htmlspecialchars($task['status']))); ?>"><?php echo htmlspecialchars($task['status']); ?></span></td>
                                <td>
                                    <a href="?edit_task=<?php echo $task['task_id']; ?>" class="btn-icon edit"><i class="fas fa-edit"></i></a>
                                    <button class="btn-icon delete" onclick="deleteTask(<?php echo $task['task_id']; ?>)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endwhile;
                    } else {
                        echo "<tr><td colspan='8'>No tasks found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Hidden form for delete operations -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="task_id" id="delete_task_id">
        <input type="hidden" name="delete_task" value="1">
    </form>

    <script>
    document.getElementById('taskForm').addEventListener('submit', function(e) {
        const required = ['employee', 'client', 'task_type', 'due_date', 'priority'];
        const errors = [];
        
        required.forEach(field => {
            const element = this.elements[field];
            if (!element.value.trim()) {
                errors.push(`${field.replace('_', ' ')} is required`);
                element.classList.add('error');
            } else {
                element.classList.remove('error');
            }
        });
        
        if (errors.length > 0) {
            e.preventDefault();
            alert(errors.join('\n'));
        }
    });

    function deleteTask(taskId) {
        if (confirm('Are you sure you want to delete this task?')) {
            const deleteForm = document.getElementById('deleteForm');
            document.getElementById('delete_task_id').value = taskId;
            deleteForm.submit();
        }
    }
    </script>
</body>
</html>