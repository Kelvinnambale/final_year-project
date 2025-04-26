<?php
class LogoutHandler {
    private $pdo;

    public function __construct() {
        // Ensure session is started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Require config for database connection
        require_once '../config.php';
    }

    /**
     * Handle logout process
     */
    public function processLogout() {
        // Validate request method
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->showLogoutConfirmation();
            exit();
        }

        try {
            // Establish database connection
            $pdo = Database::connectDB();
            if (!$pdo) {
                throw new Exception('Database connection failed');
            }

            // Remove user session from database
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $session_id = session_id();

                $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_id = ?");
                $stmt->execute([$user_id, $session_id]);

                error_log("Logout successful for User ID: " . $user_id);
            }

            // Clear and destroy session
            $this->destroySession();

            // Redirect to login
            header("Location: ../signin.php");
            exit();

        } catch (Exception $e) {
            error_log("Logout Error: " . $e->getMessage());
            die("Error during logout: " . $e->getMessage());
        }
    }

    /**
     * Show logout confirmation page
     */
    private function showLogoutConfirmation() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Logout Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding-top: 50px; }
                form { background: #f4f4f4; width: 300px; margin: 0 auto; padding: 20px; border-radius: 5px; }
            </style>
        </head>
        <body>
            <form action="logout.php" method="POST">
                <h2>Logout Confirmation</h2>
                <p>Are you sure you want to logout?</p>
                <button type="submit">Confirm Logout</button>
            </form>
        </body>
        </html>
        <?php
    }

    /**
     * Completely destroy the session
     */
    private function destroySession() {
        // Unset all session variables
        $_SESSION = array();

        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();
    }
}

// Execute logout process
$logoutHandler = new LogoutHandler();
$logoutHandler->processLogout();
?>
