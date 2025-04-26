<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';

session_start();

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    $_SESSION['error_messages'] = ['You must be logged in as a client to submit an enquiry.'];
    header('Location: ../signin.php');
    exit();
}

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_messages'] = ['Invalid request method.'];
    header('Location: enquiries.php');
    exit();
}

// Validate input
$enquiry_type = filter_input(INPUT_POST, 'enquiry_type', FILTER_SANITIZE_STRING);
$subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

// Validate form fields
$errors = [];

if (empty($enquiry_type)) {
    $errors[] = 'Enquiry type is required.';
}

if (empty($subject)) {
    $errors[] = 'Subject is required.';
}

if (empty($message)) {
    $errors[] = 'Message is required.';
}

// If there are validation errors, redirect back with error messages
if (!empty($errors)) {
    $_SESSION['error_messages'] = $errors;
    header('Location: enquiries.php');
    exit();
}

try {
    // Connect to the database
    $pdo = Database::connectDB();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get client ID
    $stmt = $pdo->prepare("SELECT client_id FROM clients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $client = $stmt->fetch();
    
    if (!$client) {
        throw new Exception('Client not found');
    }
    
    // Prepare and execute the insert statement
    $insert_stmt = $pdo->prepare("
        INSERT INTO enquiries (
            client_id, 
            type, 
            subject, 
            message, 
            created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    
    $result = $insert_stmt->execute([
        $client['client_id'], 
        $enquiry_type, 
        $subject, 
        $message
    ]);
    
    if ($result) {
        // Set success message
        $_SESSION['success_message'] = 'Your enquiry has been submitted successfully.';
    } else {
        throw new Exception('Failed to submit enquiry');
    }
    
    // Redirect back to enquiries page
    header('Location: enquiries.php');
    exit();
    
} catch(Exception $e) {
    // Log the error
    error_log("Enquiry Submission Error: " . $e->getMessage());
    
    // Set error message
    $_SESSION['error_messages'] = ['An error occurred while submitting your enquiry. Please try again.'];
    
    // Redirect back to enquiries page
    header('Location: enquiries.php');
    exit();
}