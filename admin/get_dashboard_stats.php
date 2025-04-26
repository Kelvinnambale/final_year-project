<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get statistics
    $result = Database::getStats();
    $stats = $result['stats'];
    $task_stats = $result['task_stats'];
    
    // Get enquiries count
    $pdo = Database::connectDB();
    $stmt = $pdo->query("SELECT COUNT(*) FROM enquiries");
    $enquiries_count = $stmt->fetchColumn();
    
    // Get active users
    $active_users = Database::getActiveUsers();
    
    echo json_encode([
        'total_enquiries' => $enquiries_count,
        'total_clients' => $stats['total_clients'],
        'total_employees' => $stats['total_employees'],
        'completed_tasks' => $task_stats['completed_tasks'],
        'pending_tasks' => $task_stats['pending_tasks'],
        'active_users' => $active_users
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    error_log("Dashboard stats error: " . $e->getMessage());
}
?> 