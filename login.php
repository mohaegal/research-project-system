<?php
// login.php
// Login Page for Student/Researcher and Supervisor authentication

session_start();
// Pass current session info to JS so the page can show a switch-user banner
$already_logged_in = isset($_SESSION['user_id']);
$current_fullname  = $already_logged_in ? htmlspecialchars($_SESSION['fullname']) : '';
$current_role      = $already_logged_in ? htmlspecialchars($_SESSION['role'])     : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Research Project System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-wrapper {
            min-height: calc(100vh - 73px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            padding: 36px;
        }
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            transition: color var(--transition-speed);
        }
        .password-toggle:hover {
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="navbar-brand">
                <i class="fa-solid fa-graduation-cap"></i> ResearchPortal
            </a>
            <ul class="nav-links">
                <li class="nav-item"><a href="index.php">Home</a></li>
                <li class="nav-item"><a href="survey_employee.php">Employee Survey</a></li>
                <li class="nav-item"><a href="survey_owner.php">Owner Interview</a></li>
                <li>
                    <button class="theme-toggle-btn" id="theme-toggle" title="Toggle Theme">
                        <i class="fa-solid fa-moon" id="theme-icon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Login Area -->
    <div class="login-wrapper">
        <div class="glass-card login-card">
            <div class="text-center mb-4">
                <i class="fa-solid fa-user-lock" style="font-size: 2.5rem; color: var(--accent-primary); margin-bottom: 12px;"></i>
                <h2>Workspace Login</h2>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 4px;">Sign in to access your dashboard</p>
            </div>

            <!-- Alert Container -->
            <div id="alert-container"></div>

            <!-- Form -->
            <form id="login-form" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-user" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" id="username" name="username" class="form-control" style="padding-left: 40px;" placeholder="Enter your username" required autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <i class="fa-solid fa-key" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="password" id="password" name="password" class="form-control" style="padding-left: 40px; padding-right: 40px;" placeholder="Enter your password" required autocomplete="current-password">
                        <i class="fa-solid fa-eye-slash password-toggle" id="password-toggle-btn" onclick="togglePasswordVisibility()"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-4" style="width: 100%;" id="submit-btn">
                    Sign In <i class="fa-solid fa-sign-in-alt"></i>
                </button>
            </form>

            <div class="mt-8 text-center" style="border-top: 1px solid var(--border-glass); padding-top: 16px;">
                <p style="font-size: 0.95rem; color: var(--text-secondary); margin-bottom: 12px;">
                    Don't have an account? <a href="signup.php" id="signup-link" style="font-weight: 600;">Sign Up here</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Script logic -->
    <script>
        // Check if just registered to display alert
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('registered') === 'true') {
            document.getElementById('alert-container').innerHTML = 
                `<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Account created successfully! Please sign in below.</div>`;
        }

        // Show switch-user banner if someone is already logged in
        const alreadyLoggedIn = <?php echo $already_logged_in ? 'true' : 'false'; ?>;
        const currentFullname = <?php echo json_encode($current_fullname); ?>;
        const currentRole     = <?php echo json_encode($current_role); ?>;
        if (alreadyLoggedIn) {
            document.getElementById('alert-container').innerHTML =
                `<div class="alert alert-info" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                    <span><i class="fa-solid fa-circle-info"></i> Currently logged in as <strong>${currentFullname}</strong> (${currentRole}). Sign in below to switch accounts, or <a href="dashboard.php" style="font-weight:600;">go to dashboard</a>.</span>
                </div>`;
        }

        function togglePasswordVisibility() {
            const pwdInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle-btn');
            
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                toggleIcon.className = 'fa-solid fa-eye password-toggle';
            } else {
                pwdInput.type = 'password';
                toggleIcon.className = 'fa-solid fa-eye-slash password-toggle';
            }
        }

        async function handleLogin(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submit-btn');
            const usernameInput = document.getElementById('username').value.trim();
            const passwordInput = document.getElementById('password').value;
            const alertContainer = document.getElementById('alert-container');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Authenticating...';
            alertContainer.innerHTML = '';

            try {
                // Always logout the current session first so switching users works correctly
                await fetch('api.php?action=logout', { method: 'POST' }).catch(() => {});

                const response = await fetch('api.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: usernameInput, password: passwordInput })
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    alertContainer.innerHTML = `<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> ${result.message} Redirecting...</div>`;
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 800);
                } else {
                    alertContainer.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> ${result.message}</div>`;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Sign In <i class="fa-solid fa-sign-in-alt"></i>';
                }
            } catch (err) {
                alertContainer.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> Connection error. Try again.</div>`;
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Sign In <i class="fa-solid fa-sign-in-alt"></i>';
            }
        }

        // Theme Switcher
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');

        if (localStorage.getItem('theme') === 'light') {
            document.body.classList.add('light-theme');
            themeIcon.className = 'fa-solid fa-sun';
        }

        themeToggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('light-theme');
            if (document.body.classList.contains('light-theme')) {
                themeIcon.className = 'fa-solid fa-sun';
                localStorage.setItem('theme', 'light');
            } else {
                themeIcon.className = 'fa-solid fa-moon';
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>
