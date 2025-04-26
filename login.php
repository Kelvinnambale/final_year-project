<?php
// login.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    // User type is no longer submitted in the form
    
    try {
        $pdo = Database::connectDB();
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        // Modified query - we don't filter by user_type anymore
        $stmt = $pdo->prepare("SELECT id, username, password, user_type FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['user_type'];
            
            $_SESSION['success'] = "Login successful! Redirecting...";
            $redirectURL = getRedirectURL($user['user_type']);
            
            // Save user session
            saveUserSession($pdo, $user['id'], session_id());
            
            // Redirect to dashboard
            header("Location: {$redirectURL}");
            exit();
        } else {
            $_SESSION['error'] = "Incorrect username or password";
            header("Location: signin.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Database error occurred: " . $e->getMessage();
        header("Location: signin.php");
        exit();
    }
}

function getRedirectURL($usertype) {
    switch ($usertype) {
        case 'admin': return 'admin/dashboard.php';
        case 'client': return 'client/dashboard.php';
        case 'employee': return 'employee/dashboard.php';
        default: return 'signin.php';
    }
}

function saveUserSession($pdo, $userId, $sessionId) {
    try {
        // First, remove any existing sessions for this user
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Then insert the new session
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_id, last_activity) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$userId, $sessionId]);
        
        // Update the last activity
        $stmt = $pdo->prepare("
            UPDATE user_sessions 
            SET last_activity = NOW() 
            WHERE user_id = ? AND session_id = ?
        ");
        $stmt->execute([$userId, $sessionId]);
        
    } catch (Exception $e) {
        error_log("Error saving user session: " . $e->getMessage());
    }
}
?>