<?php
// Ensure the session is started in the main page
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-chart-line" style="color: var(--primary-color);"></i>
            <h2>Calyda Admin</h2>
        </div>
    </div>
    <ul class="nav-links">
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <span class="icon"><i class="fas fa-home"></i></span>Dashboard
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'client.php' ? 'active' : ''; ?>">
            <a href="client.php">
                <span class="icon"><i class="fas fa-users"></i></span>Clients
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'employee.php' ? 'active' : ''; ?>">
            <a href="employee.php">
                <span class="icon"><i class="fas fa-user-tie"></i></span>Employees
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'task.php' ? 'active' : ''; ?>">
            <a href="task.php">
                <span class="icon"><i class="fas fa-tasks"></i></span>Tasks
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'enquiry.php' ? 'active' : ''; ?>">
            <a href="enquiry.php">
                <span class="icon"><i class="fas fa-envelope"></i></span>Enquiries
            </a>
        </li>
    </ul>
    <div class="theme-selector">
                <span class="theme-caption">Theme</span>
                    <button id="themeAuto" class="theme-button active" aria-label="Auto theme">
                        <i class="fas fa-circle-half-stroke"></i>
                    </button>
                    <button id="themeLight" class="theme-button" aria-label="Light theme">
                        <i class="fas fa-sun"></i>
                    </button>
                    <button id="themeDark" class="theme-button" aria-label="Dark theme">
                        <i class="fas fa-moon"></i>
                    </button>
            </div>
        <form action="logout.php" method="POST" style="margin-top: 2rem;">
            <button type="submit" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
</nav>

<script>
    // Theme switcher
    const themeButtons = document.querySelectorAll('.theme-button');
    const body = document.body;
    
    // Check for saved theme preference or use system preference
    const savedTheme = localStorage.getItem('theme') || 'auto';
    setTheme(savedTheme);
    
    // Set active button based on current theme
    themeButtons.forEach(button => {
        if (button.id === `theme${savedTheme.charAt(0).toUpperCase() + savedTheme.slice(1)}`) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });
    
    // Add click event to theme buttons
    themeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const theme = button.id.replace('theme', '').toLowerCase();
            setTheme(theme);
            
            // Update active button
            themeButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            // Save theme preference
            localStorage.setItem('theme', theme);
        });
    });
    
    function setTheme(theme) {
        if (theme === 'auto') {
            // Check system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            body.classList.toggle('dark-theme', prefersDark);
            
            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (localStorage.getItem('theme') === 'auto') {
                    body.classList.toggle('dark-theme', e.matches);
                }
            });
        } else if (theme === 'dark') {
            body.classList.add('dark-theme');
        } else {
            body.classList.remove('dark-theme');
        }
    }

    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.sidebar');

    mobileMenuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
</script>