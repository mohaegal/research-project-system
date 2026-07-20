<?php
// signup.php
// Registration Page for Student and Supervisor registration

session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Research Project System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .signup-wrapper {
            min-height: calc(100vh - 73px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .signup-card {
            width: 100%;
            max-width: 480px;
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
        .role-tabs {
            display: flex;
            border: 1px solid var(--border-glass);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .role-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-secondary);
            background: var(--bg-secondary);
            transition: all var(--transition-speed);
            border: none;
            font-family: var(--font-body);
        }
        .role-tab.active {
            color: var(--text-primary);
            background: var(--bg-glass);
            border-bottom: 2px solid var(--accent-primary);
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

    <!-- Signup Area -->
    <div class="signup-wrapper">
        <div class="glass-card signup-card">
            <div class="text-center mb-4">
                <i class="fa-solid fa-user-plus" style="font-size: 2.5rem; color: var(--accent-primary); margin-bottom: 12px;"></i>
                <h2>Create Account</h2>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 4px;">Register to access the Research Workspace</p>
            </div>

            <!-- Role Selector Tabs -->
            <div class="role-tabs">
                <button type="button" class="role-tab active" id="tab-student" onclick="selectRole('student')">
                    <i class="fa-solid fa-user-graduate"></i> Student
                </button>
                <button type="button" class="role-tab" id="tab-supervisor" onclick="selectRole('supervisor')">
                    <i class="fa-solid fa-signature"></i> Supervisor
                </button>
            </div>

            <!-- Alert Container -->
            <div id="alert-container"></div>

            <!-- Form -->
            <form id="signup-form" onsubmit="handleSignup(event)">
                <input type="hidden" id="signup-role" name="role" value="student">
                
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-address-card" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" id="fullname" name="fullname" class="form-control" style="padding-left: 40px;" placeholder="e.g. John Doe" required autocomplete="name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-user" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" id="username" name="username" class="form-control" style="padding-left: 40px;" placeholder="Choose a unique username" required autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <i class="fa-solid fa-key" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="password" id="password" name="password" class="form-control" style="padding-left: 40px; padding-right: 40px;" placeholder="At least 6 characters" required autocomplete="new-password">
                        <i class="fa-solid fa-eye-slash password-toggle" id="pwd-toggle" onclick="togglePasswordVisibility('password', 'pwd-toggle')"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="password-container">
                        <i class="fa-solid fa-lock" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="password" id="confirm-password" name="confirm_password" class="form-control" style="padding-left: 40px; padding-right: 40px;" placeholder="Confirm your password" required autocomplete="new-password">
                        <i class="fa-solid fa-eye-slash password-toggle" id="cpwd-toggle" onclick="togglePasswordVisibility('confirm-password', 'cpwd-toggle')"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-4" style="width: 100%;" id="submit-btn">
                    Sign Up <i class="fa-solid fa-user-plus"></i>
                </button>
            </form>

            <div class="mt-8 text-center" style="border-top: 1px solid var(--border-glass); padding-top: 16px;">
                <p style="font-size: 0.9rem; color: var(--text-secondary);">
                    Already have an account? <a href="login.php" style="font-weight: 600;">Sign In here</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Script logic -->
    <script>
        function selectRole(role) {
            document.getElementById('signup-role').value = role;
            document.getElementById('tab-student').classList.toggle('active', role === 'student');
            document.getElementById('tab-supervisor').classList.toggle('active', role === 'supervisor');
        }

        function togglePasswordVisibility(inputId, toggleId) {
            const pwdInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(toggleId);
            
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                toggleIcon.className = 'fa-solid fa-eye password-toggle';
            } else {
                pwdInput.type = 'password';
                toggleIcon.className = 'fa-solid fa-eye-slash password-toggle';
            }
        }

        async function handleSignup(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submit-btn');
            const fullnameInput = document.getElementById('fullname').value.trim();
            const usernameInput = document.getElementById('username').value.trim();
            const passwordInput = document.getElementById('password').value;
            const confirmPasswordInput = document.getElementById('confirm-password').value;
            const roleInput = document.getElementById('signup-role').value;
            const alertContainer = document.getElementById('alert-container');
            
            alertContainer.innerHTML = '';

            // Client-side validations
            if (passwordInput.length < 6) {
                alertContainer.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> Password must be at least 6 characters long.</div>`;
                return;
            }

            if (passwordInput !== confirmPasswordInput) {
                alertContainer.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> Passwords do not match.</div>`;
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating Account...';

            try {
                const response = await fetch('api.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        username: usernameInput, 
                        fullname: fullnameInput, 
                        password: passwordInput,
                        role: roleInput
                    })
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    alertContainer.innerHTML = `<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> ${result.message} Redirecting to login...</div>`;
                    setTimeout(() => {
                        window.location.href = `login.php?role=${roleInput}&registered=true`;
                    }, 1500);
                } else {
                    alertContainer.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> ${result.message}</div>`;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Sign Up <i class="fa-solid fa-user-plus"></i>';
                }
            } catch (err) {
                alertContainer.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> Connection error. Try again.</div>`;
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Sign Up <i class="fa-solid fa-user-plus"></i>';
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
