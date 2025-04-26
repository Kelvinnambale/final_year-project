<?php
// config.php

class Database {
    private static $pdo = null;

    public static function connectDB() {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    "mysql:host=localhost;dbname=calyd_db",
                    "root",
                    "",
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                return self::$pdo;
            } catch(PDOException $e) {
                error_log("Connection Error: " . $e->getMessage());
                return null;
            }
        }
        self::updateUserActivity();
        return self::$pdo;
    }

    public static function getStats() {
        try {
            $pdo = self::connectDB();
            
            // Get total clients
            $clientStmt = $pdo->query("SELECT COUNT(*) FROM clients");
            $totalClients = $clientStmt->fetchColumn();
            
            // Get total employees
            $employeeStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'employee'");
            $totalEmployees = $employeeStmt->fetchColumn();
            
            // Get task stats
            $taskStmt = $pdo->query("SELECT 
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status != 'completed' THEN 1 ELSE 0 END) as pending_tasks
                FROM tasks");
            $taskStats = $taskStmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'stats' => [
                    'total_clients' => $totalClients,
                    'total_employees' => $totalEmployees
                ],
                'task_stats' => [
                    'completed_tasks' => $taskStats['completed_tasks'] ?? 0,
                    'pending_tasks' => $taskStats['pending_tasks'] ?? 0
                ]
            ];
        } catch (PDOException $e) {
            error_log("Error getting stats: " . $e->getMessage());
            return [
                'stats' => ['total_clients' => 0, 'total_employees' => 0],
                'task_stats' => ['completed_tasks' => 0, 'pending_tasks' => 0]
            ];
        }
    }

    public static function getActiveUsers() {
        try {
            // First cleanup expired sessions
            self::cleanupExpiredSessions();
            
            $pdo = self::connectDB();
            $timeout = date('Y-m-d H:i:s', strtotime('-30 minutes'));
            
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT user_id) as active_users 
                FROM user_sessions 
                WHERE last_activity > ?
            ");
            
            $stmt->execute([$timeout]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['active_users'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting active users: " . $e->getMessage());
            return 0;
        }
    }

    public static function getActivities() {
        try {
            $pdo = self::connectDB();
            if (!$pdo) {
                throw new Exception('Database connection failed');
            }

            // Check if required tables exist
            $tables = ['clients', 'employees', 'tasks'];
            foreach ($tables as $table) {
                $result = $pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($result->rowCount() === 0) {
                    throw new Exception("Table '{$table}' does not exist");
                }
            }

            // Fetch activities with error handling for each query
            $activities = [];

            // Clients
            try {
                $clientQuery = "
                    SELECT
                        'client' as type,
                        CONCAT(first_name, ' ', surname) as name,
                        'New client registered' as action,
                        created_at
                    FROM clients
                    ORDER BY created_at DESC
                    LIMIT 5
                ";
                $clientActivities = $pdo->query($clientQuery)->fetchAll();
                $activities = array_merge($activities, $clientActivities);
            } catch(Exception $e) {
                error_log("Client Activities Error: " . $e->getMessage());
            }

                        // Employees
                        try {
                            $employeeQuery = "
                                SELECT
                                    'employee' as type,
                                    CONCAT(first_name, ' ', surname) as name,
                                    'New employee added' as action,
                                    created_at
                                FROM employees
                                ORDER BY created_at DESC
                                LIMIT 5
                            ";
                            $employeeActivities = $pdo->query($employeeQuery)->fetchAll();
                            $activities = array_merge($activities, $employeeActivities);
                        } catch(Exception $e) {
                            error_log("Employee Activities Error: " . $e->getMessage()); // Corrected line
                        }
            

            // Tasks
            try {
                $taskQuery = "
                    SELECT
                        'task' as type,
                        title as name,
                        CONCAT('Task ', status) as action,
                        created_at
                    FROM tasks
                    ORDER BY created_at DESC
                    LIMIT 5
                ";
                $taskActivities = $pdo->query($taskQuery)->fetchAll();
                $activities = array_merge($activities, $taskActivities);
            } catch(Exception $e) {
                error_log("Task Activities Error: " . $e->getMessage());
            }

            // Sort by created_at in descending order and limit to 10
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return array_slice($activities, 0, 10);
        } catch(Exception $e) {
            error_log("Activities Error: " . $e->getMessage());
            return [];
        }
    }

    // Helper function to format time ago
    public static function formatTimeAgo($timestamp) {
        $time = is_string($timestamp) ? strtotime($timestamp) : $timestamp;
        $diff = time() - $time;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }

    // Function to update user activity timestamp
    private static function updateUserActivity() {
        if (session_status() == PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            try {
                $userId = $_SESSION['user_id'];
                $sessionId = session_id();
                $pdo = self::$pdo;  // Use the existing PDO instance

                $stmt = $pdo->prepare("
                    UPDATE user_sessions
                    SET last_activity = NOW()
                    WHERE user_id = ? AND session_id = ?
                ");
                $stmt->execute([$userId, $sessionId]);
            } catch (Exception $e) {
                error_log("Error updating user activity: " . $e->getMessage());
            }
        }
    }

    public static function cleanupExpiredSessions() {
        try {
            $pdo = self::connectDB();
            
            // Remove sessions older than 30 minutes
            $timeout = date('Y-m-d H:i:s', strtotime('-30 minutes'));
            $stmt = $pdo->prepare("
                DELETE FROM user_sessions 
                WHERE last_activity < ?
            ");
            $stmt->execute([$timeout]);
            
        } catch (Exception $e) {
            error_log("Error cleaning up sessions: " . $e->getMessage());
        }
    }
}

// Helper function for formatting time ago in PHP
function formatTimeAgo($datetime) {
    $time = is_string($datetime) ? strtotime($datetime) : $datetime;
    $diff = time() - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
?>
