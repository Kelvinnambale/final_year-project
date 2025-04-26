<?php
session_start();
require_once '../config.php';

if (isset($_SESSION['user_id'])) {
    try {
        $pdo = Database::connectDB();
        $stmt = $pdo->prepare("
            UPDATE user_sessions 
            SET last_activity = NOW() 
            WHERE user_id = ? AND session_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], session_id()]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Session update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?> 