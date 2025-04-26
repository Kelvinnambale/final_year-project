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
    $activities = Database::getActivities();
    
    // Format the activities for JSON response
    $formatted_activities = array_map(function($activity) {
        return [
            'type' => $activity['type'],
            'action' => $activity['action'],
            'time_ago' => Database::formatTimeAgo($activity['created_at'])
        ];
    }, $activities);
    
    echo json_encode(['activities' => $formatted_activities]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    error_log("Activities error: " . $e->getMessage());
}
?>
