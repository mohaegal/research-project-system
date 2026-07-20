<?php
// index.php
// Public Landing Page for the Youth Employment Research System

require_once __DIR__ . '/database.php';

// Fetch metadata
$meta_stmt = $pdo->query("SELECT * FROM project_metadata WHERE id = 1");
$metadata = $meta_stmt->fetch();

// Fetch counts for dashboard stats
$cnt_emp = $pdo->query("SELECT COUNT(*) FROM employee_surveys")->fetchColumn();
$cnt_own = $pdo->query("SELECT COUNT(*) FROM owner_interviews")->fetchColumn();
$total_budget = $pdo->query("SELECT SUM(cost) FROM budget_items")->fetchColumn() ?? 0;

$app_status = $pdo->query("SELECT status FROM supervisor_approval WHERE id = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Youth Employment Research System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="navbar-brand">
                <i class="fa-solid fa-graduation-cap"></i> ResearchPortal
            </a>
            <ul class="nav-links">
                <li class="nav-item active"><a href="index.php">Home</a></li>
                <li class="nav-item"><a href="survey_employee.php">Employee Survey</a></li>
                <li class="nav-item"><a href="survey_owner.php">Owner Interview</a></li>
                <li class="nav-item"><a href="dashboard.php" class="btn btn-secondary no-print" style="padding: 6px 12px; margin-left: 10px;">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a></li>
                <li>
                    <button class="theme-toggle-btn" id="theme-toggle" title="Toggle Theme">
                        <i class="fa-solid fa-moon" id="theme-icon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Portal Logins -->
    <section class="container mt-8">
        <div class="glass-card" style="background: rgba(var(--accent-primary-rgb), 0.02); border-color: rgba(99, 102, 241, 0.15);">
            <div class="portal-grid-2" style="text-align: center;">
                <div class="portal-col-left">
                    <i class="fa-solid fa-user-gear" style="font-size: 1.75rem; color: var(--accent-primary); margin-bottom: 12px;"></i>
                    <h4 class="mb-2">Student Workspace</h4>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">Manage metadata, write proposal text, add budget rows, and view live charts.</p>
                    <div style="display: flex; justify-content: center;">
                        <a href="login.php" class="btn btn-primary" style="font-size: 0.85rem; padding: 8px 24px; box-shadow: none;">
                            Sign In <i class="fa-solid fa-sign-in-alt"></i>
                        </a>
                    </div>
                </div>
                
                <div class="portal-col-right">
                    <i class="fa-solid fa-user-shield" style="font-size: 1.75rem; color: var(--accent-secondary); margin-bottom: 12px;"></i>
                    <h4 class="mb-2">Supervisor Portal</h4>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">Review proposal text, inspect budget estimates, verify data logs, and approve project.</p>
                    <div style="display: flex; justify-content: center;">
                        <a href="login.php" class="btn btn-primary" style="font-size: 0.85rem; padding: 8px 24px; background: linear-gradient(135deg, var(--accent-secondary), var(--accent-primary)); box-shadow: none;">
                            Sign In <i class="fa-solid fa-signature"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Hero Section -->
    <header class="hero container">
        <span class="badge badge-success mb-4" style="font-size: 0.85rem; padding: 6px 14px;">
            <i class="fa-solid fa-book-open"></i> Academic Research Project
        </span>
        <h1><?php echo htmlspecialchars($metadata['title'] ?? 'THE ROLE OF SMALL BUSINESSES IN YOUTH EMPLOYMENT'); ?></h1>
        <p>Investigating the capacity of retail shops, salons, restaurants, and repair services in creating job opportunities for the youth in local communities.</p>
        
        <!-- Metadata Info Cards -->
        <div class="glass-card grid-3 mt-8" style="text-align: left; padding: 20px; gap: 16px;">
            <div>
                <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Researcher</span>
                <p style="font-weight: 600; font-size: 1.05rem; color: var(--text-primary);">
                    <?php echo htmlspecialchars($metadata['student_name']); ?>
                </p>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Reg: <?php echo htmlspecialchars($metadata['reg_number']); ?></span>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Institution</span>
                <p style="font-weight: 600; font-size: 1.05rem; color: var(--text-primary);">
                    <?php echo htmlspecialchars($metadata['institution']); ?>
                </p>
                <span style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo htmlspecialchars($metadata['department']); ?></span>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Study Location & Date</span>
                <p style="font-weight: 600; font-size: 1.05rem; color: var(--text-primary);">
                    <?php echo htmlspecialchars($metadata['town_community']); ?>
                </p>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Timeline: <?php echo htmlspecialchars($metadata['submission_date']); ?></span>
            </div>
        </div>
    </header>

    <!-- Real-time metrics -->
    <section class="container mb-8">
        <div class="grid-4">
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(99, 102, 241, 0.1); color: var(--accent-primary);">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="metric-info">
                    <h3>Youth Employees</h3>
                    <p><?php echo $cnt_emp; ?> <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: normal;">surveyed</span></p>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(168, 85, 247, 0.1); color: var(--accent-secondary);">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div class="metric-info">
                    <h3>Business Owners</h3>
                    <p><?php echo $cnt_own; ?> <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: normal;">interviewed</span></p>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-success);">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div class="metric-info">
                    <h3>Research Budget</h3>
                    <p>KES <?php echo number_format($total_budget); ?></p>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--accent-warning);">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <div class="metric-info">
                    <h3>Approval Status</h3>
                    <p>
                        <?php if ($app_status === 'Approved'): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-check-double"></i> Approved</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Pending</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Portals / Public questionnaires links -->
    <main class="container mb-8">
        <h2 class="mb-4" style="text-align: center; font-size: 1.75rem;">Research Data Collection Portals</h2>
        <div class="grid-2">
            <!-- Employee Survey Portal -->
            <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 2rem; color: var(--accent-primary); margin-bottom: 16px;">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <h3 class="mb-2" style="font-size: 1.25rem;">Questionnaire for Young Employees</h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 24px;">
                        If you are a young person (under 35 years) working in a retail shop, salon, restaurant, repair shop, or any small local enterprise, please share your work conditions, skills acquired, and challenges faced.
                    </p>
                </div>
                <a href="survey_employee.php" class="btn btn-primary" style="width: 100%;">
                    Fill Employee Questionnaire <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Owner Interview Portal -->
            <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 2rem; color: var(--accent-secondary); margin-bottom: 16px;">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <h3 class="mb-2" style="font-size: 1.25rem;">Interview Guide for Business Owners</h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 24px;">
                        If you are an entrepreneur operating a small business in this locality, we appreciate your feedback regarding youth hiring motivation, operational bottlenecks, training, and policy support needed.
                    </p>
                </div>
                <a href="survey_owner.php" class="btn btn-primary" style="width: 100%; background: linear-gradient(135deg, var(--accent-secondary), var(--accent-primary));">
                    Open Owner Interview Guide <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Authorization Letter Gateway -->
        <div class="glass-card mt-8" style="background: rgba(var(--accent-primary-rgb), 0.02); border-color: rgba(99, 102, 241, 0.15); max-width: 600px; margin-left: auto; margin-right: auto;">
            <div style="text-align: center; padding: 10px;">
                <i class="fa-solid fa-file-pdf" style="font-size: 1.75rem; color: var(--accent-success); margin-bottom: 12px;"></i>
                <h4 class="mb-2">Authorization Letter</h4>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">Generate and print the academic letterhead authorization letter for field survey research.</p>
                <a href="generate_letter.php" target="_blank" class="btn btn-secondary" style="font-size: 0.85rem; padding: 8px 16px;">
                    Generate Letter <i class="fa-solid fa-print"></i>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer style="border-top: 1px solid var(--border-glass); padding: 24px 0; text-align: center; font-size: 0.9rem; color: var(--text-secondary);">
        <div class="container">
            <p>&copy; 2026 Youth Employment Research System. All rights reserved.</p>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Powered by PHP, SQLite, Vanilla CSS & JS</p>
        </div>
    </footer>

    <!-- Theme and Global Logic -->
    <script>
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');

        // Check user preferences
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
