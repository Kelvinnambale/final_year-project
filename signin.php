<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Calyda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
    /* Light Theme Variables */
    --primary-color: #4285f4; /* Adjusted to match the blue in the image */
    --primary-color-hover: #2563eb;
    --secondary-color: #1e40af;
    --background-color: #0f172a; /* Adjusted to dark background */
    --card-background: #1a2234; /* Darker card background */
    --text-color: #ffffff;
    --text-muted: #94a3b8;
    --border-color: #334155;
    --danger-color: #ef4444;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --input-background: #131d33;
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.2);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.2), 0 2px 4px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.25), 0 4px 6px -2px rgba(0, 0, 0, 0.15);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background-color: var(--background-color);
    color: var(--text-color);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    margin: 0;
}

.nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 0.75rem 2rem;
    background-color: #1a1f2e; /* Adjusted to match the nav color */
    color: var(--text-color);
    box-shadow: var(--shadow-sm);
    border-bottom: 1px solid var(--border-color);
    z-index: 100;
}

.nav-brand {
    font-size: 1.5rem;
    font-weight: 600;
    color: #4285f4; /* Adjusted to match the image */
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.theme-toggle {
    background: none;
    border: none;
    color: var(--text-muted);
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.3s ease, background-color 0.3s ease;
}

.theme-toggle:hover {
    color: var(--primary-color);
    background-color: rgba(59, 130, 246, 0.1);
}

.home-btn {
    background-color: var(--primary-color);
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 9999px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.home-btn:hover {
    background-color: var(--primary-color-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.main-content {
    display: flex;
    justify-content: center;
    align-items: center;
    flex: 1;
    padding: 0; /* Removed padding to match image */
}

/* Split screen layout - matches image */
.split-screen {
    display: flex;
    flex-direction: row;
    min-height: calc(100vh - 60px - 30px); /* Adjusted for navbar and footer height */
    width: 100%;
}
.alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: rgba(52, 211, 153, 0.1);
            color: var(--success-color);
            border-left-color: var(--success-color);
        }

        .alert-success::before {
            content: "✓";
            font-weight: bold;
            font-size: 1.1rem;
        }

        .alert-error {
            background-color: rgba(248, 113, 113, 0.1);
            color: var(--danger-color);
            border-left-color: var(--danger-color);
        }

        .alert-error::before {
            content: "⚠";
            font-weight: bold;
            font-size: 1.1rem;
        }
/* Hero section (left side) */
.hero {
    flex: 1;
    background: var(--background-color); /* Adjusted to match image */
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.hero-content {
    max-width: 650px;
    padding: 2rem;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.9;
    line-height: 1.6;
}

.hero-features {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-top: 2rem;
}

.feature {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.feature i {
    font-size: 1.5rem;
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
}

.feature span {
    font-size: 1.1rem;
    font-weight: 500;
}

/* Login side (right side) */
.login-side {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    background-color: var(--background-color);
}

.login-container {
    width: 100%;
    max-width: 420px;
    background: var(--card-background);
    padding: 2.5rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
}

.login-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 0.5rem;
    text-align: center;
}

.login-subtitle {
    color: var(--text-muted);
    font-size: 1rem;
    margin-bottom: 2rem;
    text-align: center;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-color);
    font-weight: 500;
}

.form-input, select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    font-size: 1rem;
    background-color: var(--input-background);
    color: var(--text-color);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-input:focus, select:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
}

.password-wrapper {
    position: relative;
    width: 100%;
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: var(--text-muted);
    transition: color 0.3s;
    background: none;
    border: none;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toggle-password:hover {
    color: var(--primary-color);
}

.signin-button {
    width: 100%;
    padding: 0.85rem;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-top: 0.5rem;
}

.signin-button:hover {
    background-color: var(--primary-color-hover);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.footer {
    background-color: #1a1f2e; /* Adjusted to match the footer color */
    padding: 0.5rem;
    text-align: center;
    color: var(--text-muted);
    border-top: 1px solid var(--border-color);
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .split-screen {
        flex-direction: column;
    }
    
    .hero, .login-side {
        flex: none;
        width: 100%;
    }
    
    .hero {
        padding: 3rem 1.5rem;
    }
    
    .hero-content {
        text-align: center;
        padding: 0;
    }
    
    .hero-features {
        align-items: center;
    }
    
    .login-side {
        padding-top: 2rem;
        padding-bottom: 3rem;
    }
}
</style>
</head>
<body>
<nav class="nav">
    <div class="nav-brand">
        <span>Calyda</span>
    </div>
    <div class="nav-right">
        <button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme">
            <i class="fas fa-moon"></i>
        </button>
        
    </div>
</nav>

<!-- New split-screen layout -->
<div class="split-screen">
    <!-- Hero section (left side) -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Online Accounting and Task Management System</h1>
            <p class="hero-subtitle">
                Comprehensive solution for managing clients, employees, tasks, and 
                operations all in one place.
            </p>
            <div class="hero-features">
                <div class="feature">
                    <i class="fas fa-user-friends"></i>
                    <span>Admin, Client, Employee Portal Management</span>
                </div>
                
                <div class="feature">
                    <i class="fas fa-question-circle"></i>
                    <span>Enquiry Management</span>
                </div>
                <div class="feature">
                    <i class="fas fa-comment"></i>
                    <span>Feedback Management</span>
                </div>
                <div class="feature">
                    <i class="fas fa-tasks"></i>
                    <span>Task Tracking</span>
                </div>
                
            </div>
        </div>
    </section>
    
    <!-- Login form (right side) -->
    <div class="login-side">
        <div class="login-container">
            <h1 class="login-title">Login Panel</h1>
            <p class="login-subtitle">Enter your credentials to access your account</p>

            <?php if (isset($_SESSION['success'])): ?>
                <div class='alert alert-success'>
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = "<?php echo isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'signin.php'; ?>";
                    }, 1000);
                </script>
                <?php 
                    unset($_SESSION['success']); 
                    unset($_SESSION['redirect_url']); 
                ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class='alert alert-error'>
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-input" required placeholder="Enter your Email">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-input" required placeholder="Enter your Password">
                        <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="signin-button">Sign in</button>
            </form>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2025 Calyda Management System. All rights reserved.</p>
</footer>

    <script>
    // Theme toggle functionality
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = themeToggle.querySelector('i');
    
    // Check for saved theme preference or use device preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
    } else {
        // Use device preference as default
        const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const defaultTheme = prefersDarkScheme ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', defaultTheme);
        updateThemeIcon(defaultTheme);
    }
    
    // Toggle theme function
    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);
    });
    
    // Update theme icon based on current theme
    function updateThemeIcon(theme) {
        if (theme === 'dark') {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        } else {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }
    }
    
    // Password toggle functionality
    function togglePassword() {
        const passwordField = document.getElementById("password");
        const eyeIcon = document.querySelector(".toggle-password i");
        
        if (passwordField.type === "password") {
            passwordField.type = "text";
            eyeIcon.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            passwordField.type = "password";
            eyeIcon.classList.replace("fa-eye-slash", "fa-eye");
        }
    }
    </script>
</body>
</html>