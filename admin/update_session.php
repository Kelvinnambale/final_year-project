<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No active session']);
    exit();
}

try {
    $pdo = Database::connectDB();
    
    // Update last activity
    $stmt = $pdo->prepare("
        UPDATE user_sessions 
        SET last_activity = NOW() 
        WHERE user_id = ? AND session_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], session_id()]);
    
    // Get updated stats
    $stats = Database::getStats();
    $activeUsers = Database::getActiveUsers();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_clients' => $stats['stats']['total_clients'],
            'total_employees' => $stats['stats']['total_employees'],
            'completed_tasks' => $stats['task_stats']['completed_tasks'],
            'pending_tasks' => $stats['task_stats']['pending_tasks'],
            'active_users' => $activeUsers
        ]
    ]);
} catch (Exception $e) {
    error_log("Session update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error updating session']);
}
?> 