<?php
// dashboard.php
// Unified Student + Supervisor Workspace — Multi-user Research Project Management System (v2)

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$fullname = $_SESSION['fullname'];
$is_supervisor = ($role === 'supervisor');
$is_student    = ($role === 'student');
$is_admin      = ($role === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Workspace Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-glass);
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .char-counter { font-size: 0.85rem; color: var(--text-muted); }
        .action-card {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .comment-item {
            border-left: 3px solid var(--accent-primary);
            padding: 12px;
            margin-bottom: 12px;
            background: var(--bg-glass);
            border-radius: 0 8px 8px 0;
        }
        .comment-text { font-size: 0.9rem; color: var(--text-secondary); font-style: italic; }
        .comment-meta { font-size: 0.8rem; color: var(--text-muted); margin-top: 6px; }
        .editor-textarea:disabled {
            background: rgba(255,255,255,0.01);
            color: var(--text-secondary);
            cursor: not-allowed;
            border-color: rgba(255,255,255,0.05);
        }

        /* ---- Supervisor Student Table ---- */
        .progress-bar-wrap {
            background: var(--bg-secondary);
            border-radius: 4px;
            height: 8px;
            width: 100%;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
            transition: width 0.4s ease;
        }

        /* ---- Student Project Modal (Supervisor) ---- */
        .student-modal-wrap {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
        }
        .student-modal-wrap.active { display: flex; }
        .student-modal-box {
            background: var(--bg-primary);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            width: 100%;
            max-width: 960px;
            margin: auto;
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .student-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-glass);
            background: var(--bg-glass);
        }
        .student-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all var(--transition-speed);
        }
        .student-modal-close:hover { background: var(--bg-secondary); color: var(--text-primary); }

        /* Inner modal tabs */
        .modal-inner-tabs {
            display: flex;
            gap: 2px;
            padding: 12px 24px 0;
            border-bottom: 1px solid var(--border-glass);
            background: var(--bg-secondary);
        }
        .modal-inner-tab {
            padding: 10px 18px;
            border: none;
            background: none;
            color: var(--text-secondary);
            font-family: var(--font-body);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all var(--transition-speed);
            border-radius: 6px 6px 0 0;
        }
        .modal-inner-tab.active {
            color: var(--accent-primary);
            border-bottom-color: var(--accent-primary);
            background: var(--bg-glass);
        }
        .modal-inner-tab:hover:not(.active) { color: var(--text-primary); background: var(--bg-glass); }
        .modal-tab-panel { display: none; padding: 24px; }
        .modal-tab-panel.active { display: block; }

        /* Supervisor overview activity feed */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-glass);
            font-size: 0.9rem;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        /* ---- Chat UI Styles ---- */
        .chat-bubble {
            max-width: 70%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 0.92rem;
            line-height: 1.5;
            position: relative;
            animation: fadeIn var(--transition-speed) ease;
        }
        .chat-bubble.sent {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: #fff;
            align-self: flex-end;
            border-bottom-right-radius: 2px;
        }
        .chat-bubble.received {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            color: var(--text-primary);
            align-self: flex-start;
            border-bottom-left-radius: 2px;
        }
        .chat-time {
            font-size: 0.72rem;
            margin-top: 4px;
            display: block;
            opacity: 0.75;
            text-align: right;
        }

        /* ---- Gantt Timeline Styles ---- */
        .gantt-row {
            display: grid;
            grid-template-columns: 140px 1fr;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border-glass);
            padding: 12px 0;
        }
        .gantt-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .gantt-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .gantt-bar-wrap {
            height: 24px;
            background: var(--bg-secondary);
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            border: 1px solid var(--border-glass);
        }
        .gantt-bar-fill {
            height: 100%;
            border-radius: 12px;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 12px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #fff;
            min-width: 25%;
        }
        .gantt-bar-fill.status-completed {
            background: linear-gradient(90deg, #10b981, #059669);
            width: 100%;
        }
        .gantt-bar-fill.status-inprogress {
            background: linear-gradient(90deg, #a855f7, #6366f1);
            width: 60%;
        }
        .gantt-bar-fill.status-pending {
            background: linear-gradient(90deg, #f59e0b, #d97706);
            width: 25%;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
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
            <div style="display:flex;align-items:center;gap:16px;">
                <div class="user-profile-badge">
                    <div class="user-avatar"><?php echo strtoupper(substr($fullname,0,1)); ?></div>
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($role); ?> Workspace</div>
                    </div>
                </div>
                
                <!-- Notification Bell -->
                <div class="notif-bell-container" onclick="toggleNotifications(event)">
                    <i class="fa-solid fa-bell notif-bell"></i>
                    <span class="notif-badge" id="notif-badge-count">0</span>
                    <div class="notif-dropdown" id="notif-dropdown" onclick="event.stopPropagation()">
                        <div class="notif-header">
                            <h4>Notifications</h4>
                            <a onclick="markNotificationsRead()">Mark all as read</a>
                        </div>
                        <div class="notif-body" id="notif-list">
                            <div style="padding:16px;text-align:center;color:var(--text-muted);font-size:0.85rem;">No new notifications</div>
                        </div>
                    </div>
                </div>

                <button class="theme-toggle-btn" id="theme-toggle" title="Toggle Theme">
                    <i class="fa-solid fa-moon" id="theme-icon"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Workspace -->
    <div class="dashboard-container">

        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar">
            <div class="sidebar-heading">Navigation</div>

            <?php if ($is_student): ?>
                <div class="sidebar-link active" id="nav-overview" onclick="showPanel('overview')">
                    <i class="fa-solid fa-house-laptop"></i> Overview
                </div>
                <div class="sidebar-link" id="nav-info" onclick="showPanel('info')">
                    <i class="fa-solid fa-circle-info"></i> Project Details
                </div>
                <div class="sidebar-link" id="nav-chapters" onclick="showPanel('chapters')">
                    <i class="fa-solid fa-book-open-reader"></i> Chapter Drafting
                </div>
                <div class="sidebar-link" id="nav-budget" onclick="showPanel('budget')">
                    <i class="fa-solid fa-scale-balanced"></i> Research Budget
                </div>
                <div class="sidebar-link" id="nav-analytics" onclick="showPanel('analytics')">
                    <i class="fa-solid fa-chart-pie"></i> Data Analytics
                </div>
                <div class="sidebar-link" id="nav-approval" onclick="showPanel('approval')">
                    <i class="fa-solid fa-file-signature"></i> Supervisor Approval
                </div>
                <div class="sidebar-link" id="nav-milestones" onclick="showPanel('milestones')">
                    <i class="fa-solid fa-clock-rotate-left"></i> Milestones Timeline
                </div>
                <div class="sidebar-link" id="nav-chat" onclick="showPanel('chat')">
                    <i class="fa-solid fa-comments"></i> Direct Chat <span class="badge badge-danger" id="chat-unread-badge" style="display:none;margin-left:auto;padding:2px 6px;font-size:0.75rem;">0</span>
                </div>
                <a href="export_pdf.php" target="_blank" class="sidebar-link" style="color:var(--accent-secondary);">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF Report
                </a>
            <?php elseif ($is_supervisor): ?>
                <div class="sidebar-link active" id="nav-overview" onclick="showPanel('overview')">
                    <i class="fa-solid fa-gauge-high"></i> Overview
                </div>
                <div class="sidebar-link" id="nav-students" onclick="showPanel('students')">
                    <i class="fa-solid fa-users-viewfinder"></i> Students Management
                </div>
            <?php elseif ($is_admin): ?>
                <div class="sidebar-link active" id="nav-overview" onclick="showPanel('overview')">
                    <i class="fa-solid fa-gauge-high"></i> Overview
                </div>
                <div class="sidebar-link" id="nav-supervisors" onclick="showPanel('supervisors')">
                    <i class="fa-solid fa-user-shield"></i> Supervisor Accounts
                </div>
            <?php endif; ?>

            <div class="sidebar-heading" style="margin-top:auto;">System</div>
            <div class="sidebar-link" id="nav-profile" onclick="showPanel('profile')">
                <i class="fa-solid fa-user-gear"></i> My Profile
            </div>
            <a href="index.php" class="sidebar-link" style="color:var(--text-secondary);">
                <i class="fa-solid fa-home"></i> View Website
            </a>
            <button type="button" onclick="handleLogout()" class="sidebar-link" style="border:none;background:none;width:100%;color:var(--accent-danger);text-align:left;display:flex;align-items:center;">
                <i class="fa-solid fa-sign-out-alt"></i> Logout
            </button>
        </aside>

        <!-- ===== MAIN CONTENT ===== -->
        <main class="dashboard-content">

            <!-- ============================================================ -->
            <!-- OVERVIEW PANEL (content differs per role)                     -->
            <!-- ============================================================ -->
            <div class="dashboard-panel active" id="panel-overview">
                <div class="dashboard-header">
                    <div>
                        <h2><?php echo $is_admin ? 'Admin Dashboard' : ($is_supervisor ? 'Supervisor Dashboard' : 'Dashboard Overview'); ?></h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">Welcome back, <?php echo htmlspecialchars($fullname); ?>.</p>
                    </div>
                    <span class="badge badge-success"><?php echo ucfirst($role); ?> Mode</span>
                </div>

                <?php if ($is_student): ?>
                <!-- ---- Student Overview ---- -->
                <div class="glass-card mb-8">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                        <div>
                            <h3 style="color:var(--text-primary);"><i class="fa-solid fa-flag-checkered"></i> Project Timeline</h3>
                            <p style="color:var(--text-secondary);font-size:0.95rem;margin-top:4px;">Your research journey progress</p>
                        </div>
                        <div style="background:var(--bg-primary);padding:8px 16px;border-radius:20px;border:1px solid var(--border-glass);">
                            <span style="font-weight:700;color:var(--accent-primary);" id="overview-progress-text">0%</span>
                        </div>
                    </div>
                    
                    <!-- Visual Timeline -->
                    <div class="timeline-container" id="student-timeline">
                        <div class="timeline-step active"><div class="timeline-icon"><i class="fa-solid fa-file-signature"></i></div><div class="timeline-label">Registered</div></div>
                        <div class="timeline-step"><div class="timeline-icon"><i class="fa-solid fa-pen-nib"></i></div><div class="timeline-label">Chapters</div></div>
                        <div class="timeline-step"><div class="timeline-icon"><i class="fa-solid fa-chart-pie"></i></div><div class="timeline-label">Data Collection</div></div>
                        <div class="timeline-step"><div class="timeline-icon"><i class="fa-solid fa-comments"></i></div><div class="timeline-label">Supervisor Review</div></div>
                        <div class="timeline-step"><div class="timeline-icon"><i class="fa-solid fa-check-double"></i></div><div class="timeline-label">Final Approval</div></div>
                    </div>
                </div>
                <div class="grid-4 mb-8">
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-primary);background:rgba(99,102,241,0.08);"><i class="fa-solid fa-user-check"></i></div>
                        <div class="metric-info"><h3>Youth Surveyed</h3><p id="stat-employees">0</p></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-secondary);background:rgba(168,85,247,0.08);"><i class="fa-solid fa-handshake"></i></div>
                        <div class="metric-info"><h3>Owners Interviewed</h3><p id="stat-owners">0</p></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-success);background:rgba(16,185,129,0.08);"><i class="fa-solid fa-wallet"></i></div>
                        <div class="metric-info"><h3>Total Budget</h3><p id="stat-budget">KES 0</p></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-warning);background:rgba(245,158,11,0.08);"><i class="fa-solid fa-signature"></i></div>
                        <div class="metric-info"><h3>Approval</h3><p id="stat-approval">Pending</p></div>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="glass-card">
                        <h3 class="mb-4"><i class="fa-solid fa-bullhorn"></i> Data Collection Status</h3>
                        <p style="color:var(--text-secondary);font-size:0.95rem;margin-bottom:20px;">
                            The online surveys are currently active. Respondents can submit their answers directly via the public portals. All new submissions are synchronized here in real-time.
                        </p>
                        <div class="flex gap-2">
                            <a href="survey_employee.php?sid=<?php echo $user_id; ?>" target="_blank" class="btn btn-secondary" style="font-size:0.85rem;"><i class="fa-solid fa-external-link"></i> Employee Form</a>
                            <a href="survey_owner.php?sid=<?php echo $user_id; ?>"    target="_blank" class="btn btn-secondary" style="font-size:0.85rem;"><i class="fa-solid fa-external-link"></i> Owner Form</a>
                        </div>
                    </div>
                    <div class="glass-card">
                        <h3 class="mb-4"><i class="fa-solid fa-gears"></i> Developer &amp; Admin Tools</h3>
                        <p style="color:var(--text-secondary);font-size:0.95rem;margin-bottom:20px;">
                            Populate the database with synthetic survey entries to check charts, or completely wipe collected responses for a fresh academic run.
                        </p>
                        <div class="flex gap-2 flex-wrap" style="flex-wrap:wrap;gap:8px;">
                            <button onclick="triggerSeedData()" class="btn btn-primary" style="font-size:0.85rem;"><i class="fa-solid fa-database"></i> Re-Seed Mock Data</button>
                            <button onclick="triggerClearData()" class="btn btn-danger"  style="font-size:0.85rem;"><i class="fa-solid fa-trash-can"></i> Clear Surveys</button>
                        </div>
                    </div>
                </div>

                <?php elseif ($is_supervisor): ?>
                <!-- ---- Supervisor Overview ---- -->
                <div class="grid-4 mb-8" id="sup-stats-grid">
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-primary);background:rgba(99,102,241,0.08);"><i class="fa-solid fa-user-graduate"></i></div>
                        <div class="metric-info"><h3>Total Students</h3><p id="sup-stat-students">—</p></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-secondary);background:rgba(168,85,247,0.08);"><i class="fa-solid fa-chalkboard-user"></i></div>
                        <div class="metric-info"><h3>Total Supervisors</h3><p id="sup-stat-supervisors">—</p></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-success);background:rgba(16,185,129,0.08);"><i class="fa-solid fa-folder-open"></i></div>
                        <div class="metric-info"><h3>Total Projects</h3><p id="sup-stat-projects">—</p></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-warning);background:rgba(245,158,11,0.08);"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="metric-info"><h3>Total Surveys</h3><p id="sup-stat-surveys">—</p></div>
                    </div>
                </div>
                <div class="grid-2 mb-8">
                    <div class="metric-card">
                        <div class="metric-icon" style="color:#06b6d4;background:rgba(6,182,212,0.08);"><i class="fa-solid fa-comments"></i></div>
                        <div class="metric-info"><h3>Total Interviews</h3><p id="sup-stat-interviews">—</p></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon" style="color:#f43f5e;background:rgba(244,63,94,0.08);"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div class="metric-info"><h3>Awaiting Approval</h3><p id="sup-stat-awaiting">—</p></div>
                    </div>
                </div>
                <div class="grid-2">
                    <!-- Recent Activity -->
                    <div class="glass-card">
                        <h3 class="mb-4"><i class="fa-solid fa-bolt"></i> Recent Activity</h3>
                        <div id="sup-activity-feed"><p style="color:var(--text-muted);">Loading...</p></div>
                    </div>
                    <!-- Students Awaiting Approval -->
                    <div class="glass-card">
                        <h3 class="mb-4"><i class="fa-solid fa-hourglass-half"></i> Awaiting Your Approval</h3>
                        <div id="sup-awaiting-list"><p style="color:var(--text-muted);">Loading...</p></div>
                    </div>
                </div>

                <?php elseif ($is_admin): ?>
                <!-- ---- Admin Overview ---- -->
                <div class="grid-4 mb-8">
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-primary);background:rgba(99,102,241,0.08);"><i class="fa-solid fa-user-graduate"></i></div>
                        <div class="metric-info"><h3>Total Students</h3><p id="admin-stat-students">—</p></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-secondary);background:rgba(168,85,247,0.08);"><i class="fa-solid fa-chalkboard-user"></i></div>
                        <div class="metric-info"><h3>Supervisors</h3><p id="admin-stat-supervisors">—</p></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-success);background:rgba(16,185,129,0.08);"><i class="fa-solid fa-user-shield"></i></div>
                        <div class="metric-info"><h3>Admins</h3><p id="admin-stat-admins">—</p></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon" style="color:var(--accent-warning);background:rgba(245,158,11,0.08);"><i class="fa-solid fa-chart-pie"></i></div>
                        <div class="metric-info"><h3>Surveys Collected</h3><p id="admin-stat-surveys">—</p></div>
                    </div>
                </div>
                <div class="glass-card">
                    <h3 class="mb-4"><i class="fa-solid fa-circle-info"></i> System Administration Portal</h3>
                    <p style="color:var(--text-secondary);font-size:0.95rem;line-height:1.6;">
                        Welcome to the Research System Admin Dashboard. From this interface, you can manage the list of active supervisors, create new supervisor profiles, modify supervisor credentials, and activate/deactivate accounts to control system access.
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($is_student): ?>
            <!-- ============================================================ -->
            <!-- STUDENT PANELS (unchanged layout, data filtered by user_id)  -->
            <!-- ============================================================ -->

            <!-- 2. PROJECT DETAILS -->
            <div class="dashboard-panel" id="panel-info">
                <div class="dashboard-header">
                    <div>
                        <h2>Project Metadata &amp; Details</h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">Manage institution letterhead, student info, and timeline configurations.</p>
                    </div>
                </div>
                <div class="glass-card" style="max-width:720px;">
                    <div id="metadata-alert-container"></div>
                    <form id="metadata-form" onsubmit="saveMetadata(event)">
                        <div class="form-group">
                            <label for="meta-title">Project Research Title</label>
                            <input type="text" id="meta-title" class="form-control" required>
                        </div>
                        <div class="grid-2">
                            <div class="form-group"><label for="meta-student">Student Full Name</label><input type="text" id="meta-student" class="form-control" required></div>
                            <div class="form-group"><label for="meta-reg">Registration Number</label><input type="text" id="meta-reg" class="form-control" required></div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group"><label for="meta-institution">Name of Institution</label><input type="text" id="meta-institution" class="form-control" required></div>
                            <div class="form-group"><label for="meta-department">Department / Faculty</label><input type="text" id="meta-department" class="form-control" required></div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group"><label for="meta-degree">Award/Degree Program</label><input type="text" id="meta-degree" class="form-control" required></div>
                            <div class="form-group"><label for="meta-community">Town / Study Area</label><input type="text" id="meta-community" class="form-control" required></div>
                        </div>
                        <div class="form-group"><label for="meta-date">Submission Date/Timeline</label><input type="text" id="meta-date" class="form-control" required></div>
                        <button type="submit" class="btn btn-primary" id="meta-save-btn">Save Details <i class="fa-solid fa-save"></i></button>
                        <a href="generate_letter.php" target="_blank" class="btn btn-secondary">View Authorization Letter <i class="fa-solid fa-file-pdf"></i></a>
                    </form>
                </div>
            </div>

            <!-- 3. CHAPTER DRAFTING -->
            <div class="dashboard-panel" id="panel-chapters">
                <div class="dashboard-header">
                    <div>
                        <h2>Chapter Drafting Workspace</h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">View, evaluate, and edit the academic thesis text segments.</p>
                    </div>
                </div>
                <div id="chapter-alert-container"></div>
                <div class="glass-card">
                    <div class="editor-layout">
                        <div class="editor-sidebar" id="chapter-buttons-container"></div>
                        <div class="editor-body">
                            <h3 id="current-chapter-title">Select a Chapter</h3>
                            <textarea id="chapter-editor" class="form-control editor-textarea" placeholder="Draft your content here..." oninput="updateWordCount()"></textarea>
                            <div class="editor-status">
                                <span class="char-counter" id="chapter-word-count">Words: 0</span>
                                <button onclick="saveCurrentChapter()" class="btn btn-primary" id="chapter-save-btn">Save Chapter <i class="fa-solid fa-cloud-arrow-up"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. RESEARCH BUDGET -->
            <div class="dashboard-panel" id="panel-budget">
                <div class="dashboard-header">
                    <div>
                        <h2>Research Cost &amp; Budget Estimates</h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">Interactive management of questionnaire photocopy costs, transport, stationery, and binding.</p>
                    </div>
                    <button class="btn btn-primary" onclick="openBudgetModal()"><i class="fa-solid fa-circle-plus"></i> Add Item</button>
                </div>
                <div id="budget-alert-container"></div>
                <div class="table-wrapper">
                    <table class="custom-table" id="budget-table">
                        <thead><tr><th>Item Description</th><th>Quantity / Frequency</th><th>Cost (KES)</th><th class="text-center" style="width:100px;">Actions</th></tr></thead>
                        <tbody id="budget-table-body"></tbody>
                        <tfoot>
                            <tr style="font-weight:700;background:var(--bg-glass);">
                                <td colspan="2">TOTAL BUDGET COST</td>
                                <td id="budget-total-cell" style="color:var(--accent-success);font-size:1.1rem;">KES 0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- 5. DATA ANALYTICS -->
            <div class="dashboard-panel" id="panel-analytics">
                <div class="dashboard-header">
                    <div>
                        <h2>Survey Data &amp; Analytics</h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">Real-time analysis plots displaying gender, age, education distributions, and business scales.</p>
                    </div>
                </div>
                <div class="grid-3 mb-8">
                    <div class="glass-card text-center" style="padding:16px;">
                        <span style="color:var(--text-muted);font-size:0.8rem;text-transform:uppercase;">Youth Employed Ratio</span>
                        <h3 style="font-size:2.2rem;color:var(--accent-secondary);margin:6px 0;" id="analytics-youth-ratio">0%</h3>
                        <p style="font-size:0.8rem;color:var(--text-secondary);">Of total workers in surveyed owner firms are young people</p>
                    </div>
                    <div class="glass-card text-center" style="padding:16px;">
                        <span style="color:var(--text-muted);font-size:0.8rem;text-transform:uppercase;">Hiring Motivations</span>
                        <p style="font-size:0.9rem;margin-top:10px;line-height:1.4;" id="analytics-motivation-stat">"Young people are energetic, hard-working, and fast learners."</p>
                    </div>
                    <div class="glass-card text-center" style="padding:16px;">
                        <span style="color:var(--text-muted);font-size:0.8rem;text-transform:uppercase;">Primary Jobs Sector</span>
                        <h3 style="font-size:1.8rem;color:var(--accent-primary);margin:6px 0;"><i class="fa-solid fa-shop"></i> Retail Shops</h3>
                        <p style="font-size:0.8rem;color:var(--text-secondary);">Leading source of youth employment in your study area</p>
                    </div>
                </div>
                <div class="grid-2 mb-8">
                    <div class="glass-card"><h4 class="mb-4">Gender Distribution (Young Employees)</h4><div style="max-height:250px;display:flex;justify-content:center;"><canvas id="chart-gender"></canvas></div></div>
                    <div class="glass-card"><h4 class="mb-4">Age Profile of Respondents</h4><div style="max-height:250px;display:flex;justify-content:center;"><canvas id="chart-age"></canvas></div></div>
                </div>
                <div class="grid-2 mb-8">
                    <div class="glass-card"><h4 class="mb-4">Education Level of Young Workers</h4><div style="max-height:250px;display:flex;justify-content:center;"><canvas id="chart-education"></canvas></div></div>
                    <div class="glass-card"><h4 class="mb-4">Business Types Providing Jobs</h4><div style="max-height:250px;display:flex;justify-content:center;"><canvas id="chart-biztype"></canvas></div></div>
                </div>
                <div class="grid-2 mb-8">
                    <div class="glass-card"><h4 class="mb-4">Average Monthly Income Bracket</h4><div style="max-height:250px;display:flex;justify-content:center;"><canvas id="chart-income"></canvas></div></div>
                    <div class="glass-card"><h4 class="mb-4">Key Metrics Summary</h4><div style="max-height:250px;display:flex;justify-content:center;"><canvas id="chart-skills-challenges"></canvas></div></div>
                </div>
                <div class="grid-2">
                    <div class="glass-card"><h4 class="mb-4"><i class="fa-solid fa-comments"></i> Youth Employee Comments</h4><div style="max-height:300px;overflow-y:auto;" id="comments-employee-container"></div></div>
                    <div class="glass-card"><h4 class="mb-4"><i class="fa-solid fa-comments-dollar"></i> Business Owner Comments</h4><div style="max-height:300px;overflow-y:auto;" id="comments-owner-container"></div></div>
                </div>
            </div>

            <!-- 6. SUPERVISOR APPROVAL (student read-only view) -->
            <div class="dashboard-panel" id="panel-approval">
                <div class="dashboard-header">
                    <div>
                        <h2>Supervisor Review &amp; Sign-Off</h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">Thesis declaration statement and supervisor evaluation status.</p>
                    </div>
                </div>
                <div id="approval-alert-container"></div>
                <div class="grid-2">
                    <!-- Student declaration -->
                    <div class="glass-card">
                        <h3 class="mb-2"><i class="fa-solid fa-user-graduate"></i> Student Declaration</h3>
                        <p style="font-size:0.95rem;color:var(--text-secondary);line-height:1.7;margin-bottom:20px;">
                            "I declare that this research project is my original work and has not been presented for any academic award in any institution. All sources of information have been acknowledged through proper referencing."
                        </p>
                        <div style="border-top:1px solid var(--border-glass);padding-top:16px;">
                            <p style="font-size:0.9rem;"><strong>Student Name:</strong> <span id="decl-student-name">...</span></p>
                            <p style="font-size:0.9rem;margin-top:6px;"><strong>Status:</strong> Submitted and Sealed &#10003;</p>
                        </div>
                    </div>
                    <!-- Approval status -->
                    <div class="glass-card">
                        <h3 class="mb-2"><i class="fa-solid fa-signature"></i> Supervisor Approval Portal</h3>
                        <p style="font-size:0.9rem;color:var(--text-secondary);margin-bottom:20px;">
                            The supervisor checks chapter descriptions, analytical data, and budget values prior to marking this proposal as Approved for examination.
                        </p>
                        <div id="approval-form-readonly">
                            <div class="form-group"><label>Supervisor Name</label><input type="text" id="app-name" class="form-control" disabled></div>
                            <div class="form-group"><label>Academic Qualification</label><input type="text" id="app-qualification" class="form-control" disabled></div>
                            <div class="form-group"><label>Approval Status</label><input type="text" id="app-status-display" class="form-control" disabled></div>
                            <div class="form-group"><label>Feedback</label><textarea id="app-feedback" class="form-control" rows="3" disabled></textarea></div>
                            <div class="form-group">
                                <label>Supervisor Signature</label>
                                <div class="signature-pad-container">
                                    <canvas id="signature-pad"></canvas>
                                </div>
                            </div>
                            <div id="approval-status-message" class="alert alert-warning" style="margin-top:16px;font-size:0.85rem;">
                                <i class="fa-solid fa-clock"></i> Awaiting supervisor evaluation.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. MILESTONES TIMELINE -->
            <div class="dashboard-panel" id="panel-milestones">
                <div class="dashboard-header">
                    <div>
                        <h2>Milestones &amp; Gantt Tracker</h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">Manage and visualize your research project timeline milestones.</p>
                    </div>
                    <button class="btn btn-primary" onclick="openMilestoneModal()"><i class="fa-solid fa-circle-plus"></i> Add Milestone</button>
                </div>
                <div id="milestones-alert-container"></div>
                <div class="grid-2">
                    <!-- Milestones List & CRUD -->
                    <div class="glass-card">
                        <h3 class="mb-4"><i class="fa-solid fa-list-check"></i> Milestones List</h3>
                        <div class="table-wrapper">
                            <table class="custom-table" id="milestones-table">
                                <thead>
                                    <tr>
                                        <th>Milestone</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th class="text-center" style="width:120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="milestones-table-body">
                                    <tr><td colspan="4" class="text-center" style="color:var(--text-muted);">Loading milestones...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Gantt Timeline Representation -->
                    <div class="glass-card">
                        <h3 class="mb-4"><i class="fa-solid fa-chart-gantt"></i> Visual Gantt Timeline</h3>
                        <div class="gantt-chart-container" id="gantt-chart-container" style="display:flex;flex-direction:column;gap:16px;padding:10px 0;">
                            <!-- Dynamically loaded Gantt steps -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- 8. DIRECT CHAT -->
            <div class="dashboard-panel" id="panel-chat">
                <div class="dashboard-header">
                    <div>
                        <h2>Direct Supervisor Chat</h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">Send direct messages and coordinate research progress with your assigned supervisor.</p>
                    </div>
                </div>
                <div class="chat-wrapper glass-card" style="display:grid;grid-template-rows:1fr auto;height:550px;padding:0;overflow:hidden;border:1px solid var(--border-glass);">
                    <!-- Messages Log -->
                    <div class="chat-messages-container" id="chat-messages-container" style="padding:24px;overflow-y:auto;background:rgba(0,0,0,0.15);display:flex;flex-direction:column;gap:12px;height:470px;">
                        <div style="color:var(--text-muted);text-align:center;padding-top:20px;">Loading chat messages...</div>
                    </div>
                    <!-- Input Area -->
                    <form id="chat-input-form" onsubmit="sendChatMessage(event)" style="display:grid;grid-template-columns:1fr auto;gap:12px;padding:16px;background:var(--bg-secondary);border-top:1px solid var(--border-glass);margin-bottom:0;">
                        <input type="text" id="chat-message-input" class="form-control" placeholder="Type your message here..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary" style="padding:10px 24px;"><i class="fa-solid fa-paper-plane"></i> Send</button>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <!-- ============================================================ -->
            <!-- SUPERVISOR PANELS                                             -->
            <!-- ============================================================ -->

            <!-- STUDENTS MANAGEMENT PANEL -->
            <div class="dashboard-panel" id="panel-students">
                <div class="dashboard-header">
                    <div>
                        <h2>Students Management</h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">View all registered students, their progress, and open their full research project.</p>
                    </div>
                    <span id="students-count-badge" class="badge badge-success">Loading...</span>
                </div>
                <div id="students-alert-container"></div>
                
                <!-- Search and Filter Row -->
                <div class="filter-row">
                    <input type="text" id="student-search-input" placeholder="Search by name or reg number..." onkeyup="filterStudents()">
                    <select id="student-status-filter" onchange="filterStudents()">
                        <option value="All">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Reviewed">Reviewed</option>
                        <option value="Approved">Approved</option>
                    </select>
                </div>

                <div class="table-wrapper">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Reg Number</th>
                                <th>Research Title</th>
                                <th>Surveys</th>
                                <th>Interviews</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="students-table-body">
                            <tr><td colspan="9" class="text-center" style="color:var(--text-muted);">Loading students...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php endif; ?>

            <?php if ($is_admin): ?>
            <!-- ============================================================ -->
            <!-- ADMIN SUPERVISORS MANAGEMENT PANEL                           -->
            <!-- ============================================================ -->
            <div class="dashboard-panel" id="panel-supervisors">
                <div class="dashboard-header">
                    <div>
                        <h2>Supervisor Accounts</h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">Create, edit, activate/deactivate, and delete supervisor profiles.</p>
                    </div>
                    <button class="btn btn-primary" onclick="openAddSupervisorModal()"><i class="fa-solid fa-user-plus"></i> Add Supervisor</button>
                </div>
                <div id="supervisors-alert-container"></div>
                <div class="table-wrapper">
                    <table class="custom-table" id="supervisors-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Bio</th>
                                <th>Status</th>
                                <th class="text-center" style="width:240px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="supervisors-table-body">
                            <tr><td colspan="7" class="text-center" style="color:var(--text-muted);">Loading supervisors...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================ -->
            <!-- PROFILE PANEL (available to all users)                        -->
            <!-- ============================================================ -->
            <div class="dashboard-panel" id="panel-profile">
                <div class="dashboard-header">
                    <div>
                        <h2>My Profile</h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">Manage your contact information and short bio.</p>
                    </div>
                </div>
                <div class="glass-card" style="max-width:600px;">
                    <div id="profile-alert-container"></div>
                    <form id="profile-form" onsubmit="saveProfile(event)">
                        <div class="form-group">
                            <label for="profile-email">Email Address</label>
                            <input type="email" id="profile-email" class="form-control" placeholder="your@email.com">
                        </div>
                        <div class="form-group">
                            <label for="profile-phone">Phone Number</label>
                            <input type="text" id="profile-phone" class="form-control" placeholder="+254...">
                        </div>
                        <div class="form-group">
                            <label for="profile-bio">Short Bio</label>
                            <textarea id="profile-bio" class="form-control" rows="4" placeholder="Tell us a little about yourself..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" id="profile-save-btn">Update Profile <i class="fa-solid fa-save"></i></button>
                    </form>
                </div>
            </div>

        </main>
    </div><!-- /dashboard-container -->

    <!-- ============================================================ -->
    <!-- BUDGET MODAL (student only)                                  -->
    <!-- ============================================================ -->
    <?php if ($is_student): ?>
    <div class="modal-overlay" id="budget-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="budget-modal-title">Add Budget Item</h3>
                <button class="modal-close" onclick="closeBudgetModal()">&times;</button>
            </div>
            <form id="budget-form" onsubmit="saveBudgetItem(event)">
                <input type="hidden" id="budget-id">
                <div class="form-group"><label for="budget-name">Item Description</label><input type="text" id="budget-name" class="form-control" placeholder="e.g. Printing questionnaires" required></div>
                <div class="form-group"><label for="budget-quantity">Quantity / Frequency</label><input type="text" id="budget-quantity" class="form-control" placeholder="e.g. 80 copies, 10 trips" required></div>
                <div class="form-group"><label for="budget-cost">Estimated Cost (KES)</label><input type="number" id="budget-cost" class="form-control" min="0" placeholder="e.g. 1500" required></div>
                <div class="flex justify-between mt-8">
                    <button type="button" class="btn btn-secondary" onclick="closeBudgetModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="budget-modal-save-btn">Save Item</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- ADMIN SUPERVISOR MODALS (admin only)                         -->
    <!-- ============================================================ -->
    <?php if ($is_admin): ?>
    <!-- Add Supervisor Modal -->
    <div class="modal-overlay" id="add-supervisor-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Supervisor Account</h3>
                <button class="modal-close" onclick="closeAddSupervisorModal()">&times;</button>
            </div>
            <form id="add-supervisor-form" onsubmit="submitCreateSupervisor(event)">
                <div class="form-group">
                    <label for="add-sup-fullname">Full Name</label>
                    <input type="text" id="add-sup-fullname" class="form-control" placeholder="e.g. Dr. Jane Smith" required>
                </div>
                <div class="form-group">
                    <label for="add-sup-username">Username</label>
                    <input type="text" id="add-sup-username" class="form-control" placeholder="Unique username" required>
                </div>
                <div class="form-group">
                    <label for="add-sup-password">Password</label>
                    <input type="password" id="add-sup-password" class="form-control" placeholder="At least 6 characters" required>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="add-sup-email">Email Address</label>
                        <input type="email" id="add-sup-email" class="form-control" placeholder="name@univ.edu">
                    </div>
                    <div class="form-group">
                        <label for="add-sup-phone">Phone Number</label>
                        <input type="text" id="add-sup-phone" class="form-control" placeholder="+254...">
                    </div>
                </div>
                <div class="form-group">
                    <label for="add-sup-bio">Short Biography</label>
                    <textarea id="add-sup-bio" class="form-control" rows="3" placeholder="Credentials and faculty departments..."></textarea>
                </div>
                <div class="flex justify-between mt-8">
                    <button type="button" class="btn btn-secondary" onclick="closeAddSupervisorModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="add-sup-save-btn">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Supervisor Modal -->
    <div class="modal-overlay" id="edit-supervisor-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Supervisor Account</h3>
                <button class="modal-close" onclick="closeEditSupervisorModal()">&times;</button>
            </div>
            <form id="edit-supervisor-form" onsubmit="submitUpdateSupervisor(event)">
                <input type="hidden" id="edit-sup-id">
                <div class="form-group">
                    <label for="edit-sup-fullname">Full Name</label>
                    <input type="text" id="edit-sup-fullname" class="form-control" placeholder="e.g. Dr. Jane Smith" required>
                </div>
                <div class="form-group">
                    <label for="edit-sup-username">Username</label>
                    <input type="text" id="edit-sup-username" class="form-control" placeholder="Unique username" required>
                </div>
                <div class="form-group">
                    <label for="edit-sup-password">Password (leave blank to keep unchanged)</label>
                    <input type="password" id="edit-sup-password" class="form-control" placeholder="Enter new password">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="edit-sup-email">Email Address</label>
                        <input type="email" id="edit-sup-email" class="form-control" placeholder="name@univ.edu">
                    </div>
                    <div class="form-group">
                        <label for="edit-sup-phone">Phone Number</label>
                        <input type="text" id="edit-sup-phone" class="form-control" placeholder="+254...">
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit-sup-bio">Short Biography</label>
                    <textarea id="edit-sup-bio" class="form-control" rows="3" placeholder="Credentials and faculty departments..."></textarea>
                </div>
                <div class="flex justify-between mt-8">
                    <button type="button" class="btn btn-secondary" onclick="closeEditSupervisorModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit-sup-save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- MILESTONE MODAL (student only)                               -->
    <!-- ============================================================ -->
    <?php if ($is_student): ?>
    <div class="modal-overlay" id="milestone-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="milestone-modal-title">Add Timeline Milestone</h3>
                <button class="modal-close" onclick="closeMilestoneModal()">&times;</button>
            </div>
            <form id="milestone-form" onsubmit="saveMilestoneItem(event)">
                <input type="hidden" id="milestone-id">
                <div class="form-group">
                    <label for="milestone-title">Milestone Title</label>
                    <input type="text" id="milestone-title" class="form-control" placeholder="e.g. Chapter 1 draft" required>
                </div>
                <div class="form-group">
                    <label for="milestone-date">Target Due Date</label>
                    <input type="date" id="milestone-date" class="form-control" required>
                </div>
                <div class="form-group" id="milestone-status-group" style="display:none;">
                    <label for="milestone-status">Status</label>
                    <select id="milestone-status" class="form-control">
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div class="flex justify-between mt-8">
                    <button type="button" class="btn btn-secondary" onclick="closeMilestoneModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="milestone-modal-save-btn">Save Milestone</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- STUDENT PROJECT VIEWER MODAL (supervisor only)               -->
    <!-- ============================================================ -->
    <?php if ($is_supervisor): ?>
    <div class="student-modal-wrap" id="student-project-modal">
        <div class="student-modal-box">
            <div class="student-modal-header">
                <div>
                    <h2 id="modal-student-fullname" style="margin:0;font-size:1.3rem;">Student Project</h2>
                    <p id="modal-student-meta" style="margin:4px 0 0;color:var(--text-secondary);font-size:0.9rem;"></p>
                </div>
                <button class="student-modal-close" onclick="closeStudentModal()">&times;</button>
                <a id="modal-export-pdf-btn" href="#" target="_blank" class="toolbar-btn" style="margin-right:8px;padding:6px 14px;font-size:0.8rem;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </a>
            </div>
            <!-- Inner Tabs -->
            <div class="modal-inner-tabs">
                <button class="modal-inner-tab active" id="modal-tab-btn-project"    onclick="switchModalTab('project')"><i class="fa-solid fa-circle-info"></i> Details</button>
                <button class="modal-inner-tab"         id="modal-tab-btn-chapters"   onclick="switchModalTab('chapters')"><i class="fa-solid fa-book-open-reader"></i> Chapters</button>
                <button class="modal-inner-tab"         id="modal-tab-btn-analytics"  onclick="switchModalTab('analytics')"><i class="fa-solid fa-chart-pie"></i> Analytics</button>
                <button class="modal-inner-tab"         id="modal-tab-btn-milestones" onclick="switchModalTab('milestones')"><i class="fa-solid fa-clock-rotate-left"></i> Milestones</button>
                <button class="modal-inner-tab"         id="modal-tab-btn-chat"       onclick="switchModalTab('chat')"><i class="fa-solid fa-comments"></i> Chat <span class="badge badge-danger" id="modal-chat-unread" style="display:none;padding:2px 5px;font-size:0.7rem;margin-left:4px;">0</span></button>
                <button class="modal-inner-tab"         id="modal-tab-btn-approval"   onclick="switchModalTab('approval')"><i class="fa-solid fa-signature"></i> Approval</button>
            </div>

            <!-- Project Details Tab -->
            <div class="modal-tab-panel active" id="modal-tab-project">
                <div id="modal-metadata-content">
                    <div class="form-group"><label>Research Title</label><input type="text" id="modal-meta-title" class="form-control" disabled></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group"><label>Student Name</label><input type="text" id="modal-meta-student" class="form-control" disabled></div>
                        <div class="form-group"><label>Reg Number</label><input type="text" id="modal-meta-reg" class="form-control" disabled></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group"><label>Institution</label><input type="text" id="modal-meta-institution" class="form-control" disabled></div>
                        <div class="form-group"><label>Department</label><input type="text" id="modal-meta-department" class="form-control" disabled></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group"><label>Degree</label><input type="text" id="modal-meta-degree" class="form-control" disabled></div>
                        <div class="form-group"><label>Study Area</label><input type="text" id="modal-meta-community" class="form-control" disabled></div>
                    </div>
                    <div class="form-group"><label>Submission Date</label><input type="text" id="modal-meta-date" class="form-control" disabled></div>
                </div>
            </div>

            <!-- Chapters Tab -->
            <div class="modal-tab-panel" id="modal-tab-chapters">
                <div style="display:grid;grid-template-columns:200px 1fr;gap:20px;">
                    <div id="modal-chapter-list" style="display:flex;flex-direction:column;gap:6px;"></div>
                    <div>
                        <h4 id="modal-chapter-title" style="margin-bottom:12px;">Select a chapter</h4>
                        <textarea id="modal-chapter-content" class="form-control editor-textarea" rows="12" disabled placeholder="Chapter content will appear here..."></textarea>
                        <div style="margin-top:16px;border-top:1px solid var(--border-glass);padding-top:16px;">
                            <h4 class="mb-4"><i class="fa-solid fa-comment-dots"></i> Leave a Review Comment</h4>
                            <div id="modal-reviews-list" style="max-height:150px;overflow-y:auto;margin-bottom:12px;"></div>
                            <textarea id="modal-review-comment" class="form-control" rows="3" placeholder="Enter your review comment or feedback..."></textarea>
                            <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
                                <select id="modal-review-status" class="form-control" style="max-width:200px;">
                                    <option value="Reviewed">Reviewed</option>
                                    <option value="Needs Revision">Needs Revision</option>
                                    <option value="Approved">Approved</option>
                                </select>
                                <button onclick="submitChapterReview()" class="btn btn-primary" id="review-submit-btn">
                                    <i class="fa-solid fa-paper-plane"></i> Submit Review
                                </button>
                            </div>
                            <div id="modal-review-alert" style="margin-top:8px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div class="modal-tab-panel" id="modal-tab-analytics">
                <div class="grid-3 mb-8">
                    <div class="glass-card text-center" style="padding:16px;">
                        <span style="color:var(--text-muted);font-size:0.8rem;text-transform:uppercase;">Youth Employed Ratio</span>
                        <h3 style="font-size:2.2rem;color:var(--accent-secondary);margin:6px 0;" id="modal-youth-ratio">0%</h3>
                    </div>
                    <div class="glass-card text-center" style="padding:16px;">
                        <span style="color:var(--text-muted);font-size:0.8rem;text-transform:uppercase;">Youth Surveys</span>
                        <h3 style="font-size:2.2rem;color:var(--accent-primary);margin:6px 0;" id="modal-total-surveys">0</h3>
                    </div>
                    <div class="glass-card text-center" style="padding:16px;">
                        <span style="color:var(--text-muted);font-size:0.8rem;text-transform:uppercase;">Owner Interviews</span>
                        <h3 style="font-size:2.2rem;color:var(--accent-success);margin:6px 0;" id="modal-total-interviews">0</h3>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="glass-card"><h4 class="mb-4">Gender Distribution</h4><div style="max-height:200px;display:flex;justify-content:center;"><canvas id="modal-chart-gender"></canvas></div></div>
                    <div class="glass-card"><h4 class="mb-4">Age Profile</h4><div style="max-height:200px;display:flex;justify-content:center;"><canvas id="modal-chart-age"></canvas></div></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
                    <div class="glass-card"><h4 class="mb-4">Education Levels</h4><div style="max-height:200px;display:flex;justify-content:center;"><canvas id="modal-chart-edu"></canvas></div></div>
                    <div class="glass-card"><h4 class="mb-4">Business Types</h4><div style="max-height:200px;display:flex;justify-content:center;"><canvas id="modal-chart-biz"></canvas></div></div>
                </div>
            </div>

            <!-- Approval Tab -->
            <div class="modal-tab-panel" id="modal-tab-approval">
                <div id="modal-approval-alert"></div>
                <input type="hidden" id="modal-student-uid">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div>
                        <h4 class="mb-4"><i class="fa-solid fa-user-graduate"></i> Student Declaration</h4>
                        <div class="glass-card" style="padding:16px;">
                            <p style="font-size:0.9rem;color:var(--text-secondary);line-height:1.7;">"I declare that this research project is my original work and has not been presented for any academic award in any institution."</p>
                            <p style="margin-top:10px;font-size:0.9rem;"><strong>Student:</strong> <span id="modal-decl-name">—</span></p>
                        </div>
                        <div class="glass-card" style="padding:16px;margin-top:12px;">
                            <h5 style="margin-bottom:10px;color:var(--text-secondary);">Current Approval Status</h5>
                            <p id="modal-current-status" style="font-size:1.1rem;font-weight:700;">Pending</p>
                            <p id="modal-current-feedback" style="font-size:0.85rem;color:var(--text-secondary);margin-top:6px;"></p>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-4"><i class="fa-solid fa-signature"></i> Submit Approval Decision</h4>
                        <form id="modal-approval-form" onsubmit="saveStudentApproval(event)">
                            <div class="form-group"><label>Your Name (Supervisor)</label><input type="text" id="modal-app-name" class="form-control" value="<?php echo htmlspecialchars($fullname); ?>" required></div>
                            <div class="form-group"><label>Academic Qualification</label><input type="text" id="modal-app-qual" class="form-control" placeholder="e.g. PhD in Business Studies" required></div>
                            <div class="form-group">
                                <label>Decision</label>
                                <select id="modal-app-status" class="form-control" required>
                                    <option value="Pending">Pending Evaluation</option>
                                    <option value="Approved">Approved for Examination</option>
                                    <option value="Rejected">Needs Revision</option>
                                </select>
                            </div>
                            <div class="form-group"><label>Feedback / Comments</label><textarea id="modal-app-feedback" class="form-control" rows="4" placeholder="Enter your evaluation feedback..." required></textarea></div>
                            <button type="submit" class="btn btn-primary" style="width:100%;" id="modal-approval-btn">
                                Submit Evaluation <i class="fa-solid fa-check-double"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Milestones Tab -->
            <div class="modal-tab-panel" id="modal-tab-milestones">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="glass-card">
                        <h4 class="mb-4"><i class="fa-solid fa-list-check"></i> Student Milestones</h4>
                        <div class="table-wrapper">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Milestone</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-milestones-table-body">
                                    <tr><td colspan="3" class="text-center" style="color:var(--text-muted);">Loading milestones...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="glass-card">
                        <h4 class="mb-4"><i class="fa-solid fa-chart-gantt"></i> Visual Gantt Timeline</h4>
                        <div class="gantt-chart-container" id="modal-gantt-chart-container" style="display:flex;flex-direction:column;gap:16px;padding:10px 0;">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Tab -->
            <div class="modal-tab-panel" id="modal-tab-chat">
                <div class="chat-wrapper glass-card" style="display:grid;grid-template-rows:1fr auto;height:450px;padding:0;overflow:hidden;border:1px solid var(--border-glass);">
                    <div class="chat-messages-container" id="modal-chat-messages-container" style="padding:20px;overflow-y:auto;background:rgba(0,0,0,0.15);display:flex;flex-direction:column;gap:12px;height:380px;">
                        <div style="color:var(--text-muted);text-align:center;padding-top:20px;">Loading chat messages...</div>
                    </div>
                    <form id="modal-chat-input-form" onsubmit="sendModalChatMessage(event)" style="display:grid;grid-template-columns:1fr auto;gap:12px;padding:12px;background:var(--bg-secondary);border-top:1px solid var(--border-glass);margin-bottom:0;">
                        <input type="text" id="modal-chat-message-input" class="form-control" placeholder="Type a message to the student..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary" style="padding:8px 20px;"><i class="fa-solid fa-paper-plane"></i> Send</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- JAVASCRIPT                                                   -->
    <!-- ============================================================ -->
    <script>
    // --------------- Global State ---------------
    const userRole   = '<?php echo $role; ?>';
    let   chapters   = [];
    let   activeChapterId = '';
    let   chartsInstance  = {};
    let   modalChartsInstance = {};
    let   currentStudentId    = null;
    let   modalActiveChapterId = '';
    let   modalChapters = [];

    // --------------- Panel Navigation ---------------
    function showPanel(panelId) {
        document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
        const navEl = document.getElementById('nav-' + panelId);
        if (navEl) navEl.classList.add('active');

        document.querySelectorAll('.dashboard-panel').forEach(p => p.classList.remove('active'));
        const panel = document.getElementById('panel-' + panelId);
        if (panel) panel.classList.add('active');

        // Load panel data
        if (panelId === 'profile') {
            loadProfile();
        } else if (userRole === 'admin') {
            if (panelId === 'overview')    loadAdminOverview();
            if (panelId === 'supervisors') loadAdminSupervisors();
        } else if (userRole === 'supervisor') {
            if (panelId === 'overview')  loadSupervisorOverview();
            if (panelId === 'students')  loadStudentsPanel();
        } else {
            if (panelId === 'overview')  loadOverviewStats();
            if (panelId === 'info')      loadMetadata();
            if (panelId === 'chapters')  loadChapters();
            if (panelId === 'budget')    loadBudget();
            if (panelId === 'analytics') loadAnalytics();
            if (panelId === 'approval')  loadApproval();
        }
    }

    // --------------- Initialization ---------------
    window.onload = () => {
        showPanel('overview');
        fetchNotifications();
        setInterval(fetchNotifications, 15000); // Poll every 15s
    };

    // ================================================================
    // STUDENT FUNCTIONS
    // ================================================================

    async function loadOverviewStats() {
        try {
            const res    = await fetch('api.php?action=get_analytics');
            const result = await res.json();
            
            const metaRes    = await fetch('api.php?action=get_metadata');
            const metaResult = await metaRes.json();
            
            const appRes    = await fetch('api.php?action=get_approval');
            const appResult = await appRes.json();
            
            let employeesCount = 0;
            let ownersCount = 0;
            let budgetTotal = 0;
            let title = '';
            let status = 'Pending';
            
            if (result.status === 'success') {
                employeesCount = result.data.totals.employees;
                ownersCount = result.data.totals.owners;
                budgetTotal = result.data.totals.budget;
                
                document.getElementById('stat-employees').innerText = employeesCount;
                document.getElementById('stat-owners').innerText    = ownersCount;
                document.getElementById('stat-budget').innerText    = 'KES ' + Number(budgetTotal).toLocaleString();
            }
            
            if (metaResult.status === 'success' && metaResult.data) {
                title = metaResult.data.title || '';
            }
            
            if (appResult.status === 'success' && appResult.data) {
                status = appResult.data.status || 'Pending';
                const badge  = document.getElementById('stat-approval');
                badge.innerText = status;
                badge.style.color = (status === 'Approved') ? 'var(--accent-success)' : 'var(--accent-warning)';
            }
            
            // Calculate progress percentage matching the supervisor's dashboard logic:
            // - Registered & Title set: 20%
            // - Employee Surveys started: 25%
            // - Owner Interviews started: 25%
            // - Supervisor Approved: 30%
            let progressVal = 0;
            if (title && title.trim() !== '') progressVal += 20;
            if (employeesCount > 0) progressVal += 25;
            if (ownersCount > 0) progressVal += 25;
            if (status === 'Approved') progressVal += 30;
            
            // Update the UI progress bar text and timeline states
            const progressText = document.getElementById('overview-progress-text');
            if (progressText) progressText.innerText = progressVal + '%';
            
            updateTimeline(progressVal, status);
        } catch (err) { console.error('Overview stats error', err); }
    }

    async function loadMetadata() {
        try {
            const res    = await fetch('api.php?action=get_metadata');
            const result = await res.json();
            if (result.status === 'success' && result.data) {
                document.getElementById('meta-title').value       = result.data.title       || '';
                document.getElementById('meta-student').value     = result.data.student_name|| '';
                document.getElementById('meta-reg').value         = result.data.reg_number  || '';
                document.getElementById('meta-institution').value = result.data.institution || '';
                document.getElementById('meta-department').value  = result.data.department  || '';
                document.getElementById('meta-degree').value      = result.data.degree_type || '';
                document.getElementById('meta-community').value   = result.data.town_community || '';
                document.getElementById('meta-date').value        = result.data.submission_date || '';
                const dn = document.getElementById('decl-student-name');
                if (dn) dn.innerText = result.data.student_name || '';
            }
        } catch(e) { console.error(e); }
    }

    async function saveMetadata(e) {
        e.preventDefault();
        const btn = document.getElementById('meta-save-btn');
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        const payload = {
            title: document.getElementById('meta-title').value,
            student_name: document.getElementById('meta-student').value,
            reg_number: document.getElementById('meta-reg').value,
            institution: document.getElementById('meta-institution').value,
            department: document.getElementById('meta-department').value,
            degree_type: document.getElementById('meta-degree').value,
            town_community: document.getElementById('meta-community').value,
            submission_date: document.getElementById('meta-date').value
        };
        try {
            const res    = await fetch('api.php?action=update_metadata', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const result = await res.json();
            const c      = document.getElementById('metadata-alert-container');
            c.innerHTML  = result.status === 'success'
                ? `<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> ${result.message}</div>`
                : `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> ${result.message}</div>`;
        } catch(e) { console.error(e); }
        finally { btn.disabled = false; btn.innerHTML = 'Save Details <i class="fa-solid fa-save"></i>'; }
    }

    async function loadChapters() {
        try {
            const res    = await fetch('api.php?action=get_chapters');
            const result = await res.json();
            if (result.status === 'success') {
                chapters = result.data;
                renderChapterButtons();
                if (!activeChapterId && chapters.length > 0) selectChapter(chapters[0].chapter_id);
            }
        } catch(e) { console.error(e); }
    }

    function renderChapterButtons() {
        const c = document.getElementById('chapter-buttons-container');
        c.innerHTML = '';
        chapters.forEach(ch => {
            const btn = document.createElement('button');
            btn.className = `editor-sidebar-btn ${activeChapterId === ch.chapter_id ? 'active' : ''}`;
            btn.innerHTML = `<i class="fa-solid fa-file-lines"></i> ${ch.title.split(':')[0]}`;
            btn.onclick   = () => selectChapter(ch.chapter_id);
            c.appendChild(btn);
        });
    }

    function selectChapter(id) {
        activeChapterId = id;
        renderChapterButtons();
        const ch = chapters.find(c => c.chapter_id === id);
        if (ch) {
            document.getElementById('current-chapter-title').innerText = ch.title;
            document.getElementById('chapter-editor').value = ch.content;
            updateWordCount();
        }
    }

    function updateWordCount() {
        const txt   = document.getElementById('chapter-editor').value;
        const words = txt.trim().split(/\s+/).filter(w => w.length > 0).length;
        document.getElementById('chapter-word-count').innerText = `Words: ${words}`;
    }

    async function saveCurrentChapter() {
        const btn = document.getElementById('chapter-save-btn');
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        try {
            const res    = await fetch('api.php?action=update_chapter', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ chapter_id: activeChapterId, content: document.getElementById('chapter-editor').value }) });
            const result = await res.json();
            const c      = document.getElementById('chapter-alert-container');
            if (result.status === 'success') {
                const ch = chapters.find(c => c.chapter_id === activeChapterId);
                if (ch) ch.content = document.getElementById('chapter-editor').value;
                c.innerHTML = `<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Chapter saved successfully.</div>`;
                setTimeout(() => c.innerHTML = '', 3000);
            } else {
                c.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> ${result.message}</div>`;
            }
        } catch(e) { console.error(e); }
        finally { btn.disabled = false; btn.innerHTML = 'Save Chapter <i class="fa-solid fa-cloud-arrow-up"></i>'; }
    }

    async function loadBudget() {
        try {
            const res    = await fetch('api.php?action=get_budget');
            const result = await res.json();
            if (result.status === 'success') {
                const tbody = document.getElementById('budget-table-body');
                tbody.innerHTML = '';
                result.data.items.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${escapeHtml(item.item_name)}</td>
                        <td>${escapeHtml(item.quantity)}</td>
                        <td>KES ${Number(item.cost).toLocaleString()}</td>
                        <td class="text-center">
                            <button class="btn btn-secondary" style="padding:4px 8px;font-size:0.8rem;" onclick="openBudgetModal(${item.id},'${escapeHtml(item.item_name)}','${escapeHtml(item.quantity)}',${item.cost})"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-danger"    style="padding:4px 8px;font-size:0.8rem;" onclick="deleteBudgetItem(${item.id})"><i class="fa-solid fa-trash"></i></button>
                        </td>`;
                    tbody.appendChild(tr);
                });
                document.getElementById('budget-total-cell').innerText = 'KES ' + Number(result.data.total).toLocaleString();
            }
        } catch(e) { console.error(e); }
    }

    function escapeHtml(text) {
        return String(text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    function openBudgetModal(id='',name='',qty='',cost='') {
        document.getElementById('budget-id').value       = id;
        document.getElementById('budget-name').value     = name;
        document.getElementById('budget-quantity').value = qty;
        document.getElementById('budget-cost').value     = cost;
        document.getElementById('budget-modal-title').innerText = id ? 'Edit Budget Item' : 'Add Budget Item';
        document.getElementById('budget-modal').classList.add('active');
    }
    function closeBudgetModal() { document.getElementById('budget-modal').classList.remove('active'); }

    async function saveBudgetItem(e) {
        e.preventDefault();
        const id   = document.getElementById('budget-id').value;
        const name = document.getElementById('budget-name').value;
        const qty  = document.getElementById('budget-quantity').value;
        const cost = parseFloat(document.getElementById('budget-cost').value);
        const action  = id ? 'update_budget' : 'add_budget';
        const payload = id ? {id,item_name:name,quantity:qty,cost} : {item_name:name,quantity:qty,cost};
        const btn = document.getElementById('budget-modal-save-btn');
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        try {
            const res    = await fetch(`api.php?action=${action}`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const result = await res.json();
            if (result.status === 'success') { closeBudgetModal(); loadBudget(); }
            else alert(result.message);
        } catch(e) { console.error(e); }
        finally { btn.disabled = false; btn.innerHTML = 'Save Item'; }
    }

    async function deleteBudgetItem(id) {
        if (!confirm('Remove this budget estimate?')) return;
        try {
            const res    = await fetch('api.php?action=delete_budget', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
            const result = await res.json();
            if (result.status === 'success') loadBudget();
            else alert(result.message);
        } catch(e) { console.error(e); }
    }

    async function loadAnalytics() {
        try {
            const res    = await fetch('api.php?action=get_analytics');
            const result = await res.json();
            if (result.status === 'success') {
                const data = result.data;
                document.getElementById('analytics-youth-ratio').innerText = data.owner_charts.youth_percentage + '%';
                renderChartGender(data.employee_charts.gender, 'chart-gender', chartsInstance);
                renderChartAge(data.employee_charts.age, 'chart-age', chartsInstance);
                renderChartEducation(data.employee_charts.education, 'chart-education', chartsInstance);
                renderChartBizType(data.employee_charts.business_type, 'chart-biztype', chartsInstance);
                renderChartIncome(data.employee_charts.income, 'chart-income', chartsInstance);
                renderChartSkillsChallenges(data.employee_charts.skills, data.employee_charts.challenges, 'chart-skills-challenges', chartsInstance);
                renderComments(data.comments);
            }
        } catch(e) { console.error(e); }
    }

    async function loadApproval() {
        try {
            const metaRes = await fetch('api.php?action=get_metadata');
            const meta    = await metaRes.json();
            if (meta.status === 'success' && meta.data) {
                const dn = document.getElementById('decl-student-name');
                if (dn) dn.innerText = meta.data.student_name || '';
            }
            const res    = await fetch('api.php?action=get_approval');
            const result = await res.json();
            if (result.status === 'success' && result.data) {
                const d = result.data;
                document.getElementById('app-name').value         = d.supervisor_name || '';
                document.getElementById('app-qualification').value= d.qualification   || '';
                document.getElementById('app-status-display').value= d.status         || 'Pending';
                document.getElementById('app-feedback').value     = d.feedback        || '';
                const msg = document.getElementById('approval-status-message');
                if (d.status === 'Approved') {
                    msg.className   = 'alert alert-success';
                    msg.innerHTML   = `<i class="fa-solid fa-circle-check"></i> Project approved by ${escapeHtml(d.supervisor_name)} on ${d.approval_date}. Feedback: "${escapeHtml(d.feedback)}"`;
                } else if (d.status === 'Rejected') {
                    msg.className   = 'alert alert-danger';
                    msg.innerHTML   = `<i class="fa-solid fa-triangle-exclamation"></i> Revision required. Supervisor feedback: "${escapeHtml(d.feedback)}"`;
                } else {
                    msg.className   = 'alert alert-warning';
                    msg.innerHTML   = `<i class="fa-solid fa-clock"></i> Project evaluation is pending supervisor review.`;
                }
                // Draw signature if approved
                initSignaturePad(d);
            }
        } catch(e) { console.error(e); }
    }

    let sigCanvas, sigCtx;
    function initSignaturePad(approval) {
        sigCanvas = document.getElementById('signature-pad');
        if (!sigCanvas) return;
        sigCtx    = sigCanvas.getContext('2d');
        sigCanvas.width  = sigCanvas.parentElement.clientWidth;
        sigCanvas.height = 150;
        sigCtx.strokeStyle = '#1e3b8b'; sigCtx.lineWidth = 2.5; sigCtx.lineCap = 'round';
        if (approval && approval.status === 'Approved' && approval.supervisor_name) {
            sigCtx.font      = "italic 32px 'Brush Script MT', cursive, sans-serif";
            sigCtx.fillStyle = '#1e3b8b';
            sigCtx.fillText(approval.supervisor_name, 40, 80);
        }
    }

    async function triggerSeedData() {
        if (!confirm('Re-seed your account with 51 youth surveys and 30 owner interviews? Current data will be replaced.')) return;
        try {
            const res    = await fetch('api.php?action=seed_data', { method:'POST' });
            const result = await res.json();
            if (result.status === 'success') { alert(result.message); window.location.reload(); }
        } catch(e) { console.error(e); }
    }

    async function triggerClearData() {
        if (!confirm('Delete all your survey and interview responses? This cannot be undone!')) return;
        try {
            const res    = await fetch('api.php?action=clear_data', { method:'POST' });
            const result = await res.json();
            if (result.status === 'success') { alert(result.message); window.location.reload(); }
        } catch(e) { console.error(e); }
    }

    // ================================================================
    // CHART RENDER HELPERS (shared by student panel & modal)
    // ================================================================
    function destroyChart(name, store) { if (store[name]) { store[name].destroy(); delete store[name]; } }

    function renderChartGender(raw, canvasId, store) {
        destroyChart(canvasId, store);
        const ctx = document.getElementById(canvasId).getContext('2d');
        store[canvasId] = new Chart(ctx, { type:'pie', data: { labels: raw.map(r=>r.gender), datasets:[{data:raw.map(r=>r.count),backgroundColor:['#6366f1','#a855f7','#10b981']}]}, options:{responsive:true,maintainAspectRatio:false}});
    }
    function renderChartAge(raw, canvasId, store) {
        destroyChart(canvasId, store);
        const ctx = document.getElementById(canvasId).getContext('2d');
        store[canvasId] = new Chart(ctx, { type:'bar', data:{labels:raw.map(r=>r.age),datasets:[{label:'Respondents',data:raw.map(r=>r.count),backgroundColor:'#6366f1'}]}, options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true}}}});
    }
    function renderChartEducation(raw, canvasId, store) {
        destroyChart(canvasId, store);
        const ctx = document.getElementById(canvasId).getContext('2d');
        store[canvasId] = new Chart(ctx, { type:'doughnut', data:{labels:raw.map(r=>r.education),datasets:[{data:raw.map(r=>r.count),backgroundColor:['#f59e0b','#10b981','#6366f1','#a855f7','#ef4444']}]}, options:{responsive:true,maintainAspectRatio:false}});
    }
    function renderChartBizType(raw, canvasId, store) {
        destroyChart(canvasId, store);
        const ctx = document.getElementById(canvasId).getContext('2d');
        store[canvasId] = new Chart(ctx, { type:'bar', data:{labels:raw.map(r=>r.business_type),datasets:[{label:'Jobs distribution',data:raw.map(r=>r.count),backgroundColor:'#a855f7'}]}, options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,scales:{x:{beginAtZero:true}}}});
    }
    function renderChartIncome(raw, canvasId, store) {
        destroyChart(canvasId, store);
        const ctx = document.getElementById(canvasId).getContext('2d');
        store[canvasId] = new Chart(ctx, { type:'bar', data:{labels:raw.map(r=>r.income),datasets:[{label:'Employees',data:raw.map(r=>r.count),backgroundColor:['#10b981','#6366f1','#a855f7','#f59e0b']}]}, options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true}}}});
    }
    function renderChartSkillsChallenges(skills, challenges, canvasId, store) {
        destroyChart(canvasId, store);
        const sY = skills?.find(s=>s.skills_improved==='Yes')?.count||0;
        const sN = skills?.find(s=>s.skills_improved==='No')?.count||0;
        const cY = challenges?.find(c=>c.challenges==='Yes')?.count||0;
        const cN = challenges?.find(c=>c.challenges==='No')?.count||0;
        const ctx = document.getElementById(canvasId).getContext('2d');
        store[canvasId] = new Chart(ctx, { type:'bar', data:{labels:['Skills Improved','Challenges Faced'],datasets:[{label:'Yes',data:[sY,cY],backgroundColor:'#10b981'},{label:'No',data:[sN,cN],backgroundColor:'#ef4444'}]}, options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true}}}});
    }

    function renderComments(comments) {
        const empC = document.getElementById('comments-employee-container');
        const ownC = document.getElementById('comments-owner-container');
        if (!empC || !ownC) return;
        empC.innerHTML = ''; ownC.innerHTML = '';
        comments.employee.forEach(c => {
            if (c.challenges_details) {
                empC.innerHTML += `<div class="comment-item"><p class="comment-text">"${escapeHtml(c.challenges_details)}"</p><div class="comment-meta">- Work challenge stated by young employee</div></div>`;
            }
        });
        comments.owner.forEach(c => {
            if (c.challenges) {
                ownC.innerHTML += `<div class="comment-item" style="border-color:var(--accent-secondary)"><p class="comment-text">"${escapeHtml(c.challenges)}"</p><div class="comment-meta">- Operation bottleneck stated by owner</div></div>`;
            }
        });
    }

    // ================================================================
    // SUPERVISOR FUNCTIONS
    // ================================================================

    async function loadSupervisorOverview() {
        try {
            const res    = await fetch('api.php?action=get_supervisor_dashboard');
            const result = await res.json();
            if (result.status !== 'success') return;
            const d = result.data;

            document.getElementById('sup-stat-students').innerText    = d.totals.students;
            document.getElementById('sup-stat-supervisors').innerText  = d.totals.supervisors;
            document.getElementById('sup-stat-projects').innerText     = d.totals.projects;
            document.getElementById('sup-stat-surveys').innerText      = d.totals.surveys;
            document.getElementById('sup-stat-interviews').innerText   = d.totals.interviews;
            document.getElementById('sup-stat-awaiting').innerText     = d.awaiting_approval.length;

            // Activity feed
            const feed = document.getElementById('sup-activity-feed');
            if (d.recent_activity.length === 0) {
                feed.innerHTML = '<p style="color:var(--text-muted);">No recent activity.</p>';
            } else {
                feed.innerHTML = d.recent_activity.map(a => {
                    const icon  = a.type === 'survey' ? '#6366f1' : '#10b981';
                    const label = a.type === 'survey' ? 'completed a survey' : 'submitted an interview';
                    const date  = new Date(a.submitted_at).toLocaleDateString();
                    return `<div class="activity-item">
                        <div class="activity-icon" style="background:${icon}22;color:${icon};">
                            <i class="fa-solid fa-${a.type==='survey'?'clipboard-check':'comments'}"></i>
                        </div>
                        <div><strong>${escapeHtml(a.respondent_name)}</strong> ${label} for <strong>${escapeHtml(a.student_name)}</strong><br><span style="color:var(--text-muted);font-size:0.8rem;">${date}</span></div>
                    </div>`;
                }).join('');
            }

            // Awaiting approval
            const alist = document.getElementById('sup-awaiting-list');
            if (d.awaiting_approval.length === 0) {
                alist.innerHTML = '<p style="color:var(--text-muted);">No students awaiting approval.</p>';
            } else {
                alist.innerHTML = d.awaiting_approval.map(s =>
                    `<div class="activity-item">
                        <div class="activity-icon" style="background:rgba(245,158,11,0.1);color:var(--accent-warning);"><i class="fa-solid fa-clock"></i></div>
                        <div style="flex:1;">
                            <strong>${escapeHtml(s.fullname)}</strong><br>
                            <span style="color:var(--text-muted);font-size:0.8rem;">${escapeHtml(s.reg_number||'')}</span>
                        </div>
                        <button class="btn btn-secondary" style="font-size:0.8rem;padding:4px 10px;" onclick="openStudentModal(${s.id},'approval')">
                            <i class="fa-solid fa-pen-to-square"></i> Review
                        </button>
                    </div>`
                ).join('');
            }
        } catch(e) { console.error(e); }
    }

    async function loadStudentsPanel() {
        try {
            const res    = await fetch('api.php?action=get_supervisor_dashboard');
            const result = await res.json();
            if (result.status !== 'success') return;
            const list = result.data.students_list;

            const badge = document.getElementById('students-count-badge');
            if (badge) badge.innerText = `${list.length} Student${list.length!==1?'s':''}`;

            const tbody = document.getElementById('students-table-body');
            tbody.innerHTML = '';
            list.forEach((s,i) => {
                const statusColor = s.approval_status === 'Approved' ? 'var(--accent-success)' :
                                    s.approval_status === 'Rejected' ? 'var(--accent-danger)'  : 'var(--accent-warning)';
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${i+1}</td>
                    <td><strong>${escapeHtml(s.fullname)}</strong><br><span style="color:var(--text-muted);font-size:0.8rem;">@${escapeHtml(s.username)}</span></td>
                    <td>${escapeHtml(s.reg_number||'—')}</td>
                    <td style="max-width:200px;"><span title="${escapeHtml(s.title||'')}" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml((s.title||'').substring(0,60))}${(s.title||'').length>60?'…':''}</span></td>
                    <td class="text-center">${s.surveys}</td>
                    <td class="text-center">${s.interviews}</td>
                    <td style="min-width:120px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="progress-bar-wrap" style="flex:1;"><div class="progress-bar-fill" style="width:${s.progress}%;"></div></div>
                            <span style="font-size:0.8rem;color:var(--text-muted);">${s.progress}%</span>
                        </div>
                    </td>
                    <td><span style="color:${statusColor};font-weight:600;">${s.approval_status}</span></td>
                    <td class="text-center">
                        <button class="btn btn-primary" style="font-size:0.8rem;padding:6px 12px;" onclick="openStudentModal(${s.id},'project')">
                            <i class="fa-solid fa-eye"></i> View Project
                        </button>
                    </td>`;
                tbody.appendChild(tr);
            });
        } catch(e) { console.error(e); }
    }

    // ---- Student Project Modal ----
    async function openStudentModal(studentId, tab = 'project') {
        currentStudentId = studentId;
        document.getElementById('modal-student-uid').value = studentId;

        // Reset tabs
        document.querySelectorAll('.modal-inner-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.modal-tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('modal-tab-btn-' + tab).classList.add('active');
        document.getElementById('modal-tab-' + tab).classList.add('active');

        document.getElementById('student-project-modal').classList.add('active');
        document.getElementById('modal-export-pdf-btn').href = `export_pdf.php?sid=${studentId}`;

        // Load data for the selected tab
        if (tab === 'project')    loadModalMetadata(studentId);
        if (tab === 'chapters')   loadModalChapters(studentId);
        if (tab === 'analytics')  loadModalAnalytics(studentId);
        if (tab === 'milestones') loadModalMilestones(studentId);
        if (tab === 'chat')       loadModalChat(studentId);
        if (tab === 'approval')   loadModalApproval(studentId);
    }

    function closeStudentModal() {
        document.getElementById('student-project-modal').classList.remove('active');
        // Destroy modal charts
        Object.keys(modalChartsInstance).forEach(k => { modalChartsInstance[k].destroy(); delete modalChartsInstance[k]; });
        // Stop modal chat polling
        if (modalChatInterval) { clearInterval(modalChatInterval); modalChatInterval = null; }
    }

    function switchModalTab(tab) {
        document.querySelectorAll('.modal-inner-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.modal-tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('modal-tab-btn-' + tab).classList.add('active');
        document.getElementById('modal-tab-' + tab).classList.add('active');

        if (tab === 'project')    loadModalMetadata(currentStudentId);
        if (tab === 'chapters')   loadModalChapters(currentStudentId);
        if (tab === 'analytics')  loadModalAnalytics(currentStudentId);
        if (tab === 'milestones') loadModalMilestones(currentStudentId);
        if (tab === 'chat')       loadModalChat(currentStudentId);
        if (tab === 'approval')   loadModalApproval(currentStudentId);
    }

    async function loadModalMetadata(sid) {
        try {
            const res = await fetch(`api.php?action=get_metadata&student_id=${sid}`);
            const r   = await res.json();
            if (r.status === 'success' && r.data) {
                const d = r.data;
                document.getElementById('modal-student-fullname').innerText = d.student_name || 'Student';
                document.getElementById('modal-student-meta').innerText     = (d.reg_number||'') + ' • ' + (d.institution||'');
                document.getElementById('modal-meta-title').value       = d.title            || '';
                document.getElementById('modal-meta-student').value     = d.student_name     || '';
                document.getElementById('modal-meta-reg').value         = d.reg_number       || '';
                document.getElementById('modal-meta-institution').value = d.institution      || '';
                document.getElementById('modal-meta-department').value  = d.department       || '';
                document.getElementById('modal-meta-degree').value      = d.degree_type      || '';
                document.getElementById('modal-meta-community').value   = d.town_community   || '';
                document.getElementById('modal-meta-date').value        = d.submission_date  || '';
                document.getElementById('modal-decl-name').innerText    = d.student_name     || '';
            }
        } catch(e) { console.error(e); }
    }

    async function loadModalChapters(sid) {
        try {
            const res = await fetch(`api.php?action=get_chapters&student_id=${sid}`);
            const r   = await res.json();
            if (r.status === 'success') {
                modalChapters = r.data;
                const list    = document.getElementById('modal-chapter-list');
                list.innerHTML = '';
                modalChapters.forEach(ch => {
                    const btn = document.createElement('button');
                    btn.className = 'editor-sidebar-btn';
                    btn.innerHTML = `<i class="fa-solid fa-file-lines"></i> ${ch.title.split(':')[0]}`;
                    btn.onclick   = () => selectModalChapter(ch.chapter_id, sid);
                    list.appendChild(btn);
                });
                if (modalChapters.length) selectModalChapter(modalChapters[0].chapter_id, sid);
            }
        } catch(e) { console.error(e); }
    }

    function selectModalChapter(chapterId, sid) {
        modalActiveChapterId = chapterId;
        document.querySelectorAll('#modal-chapter-list .editor-sidebar-btn').forEach((btn, i) => {
            btn.classList.toggle('active', modalChapters[i]?.chapter_id === chapterId);
        });
        const ch = modalChapters.find(c => c.chapter_id === chapterId);
        if (ch) {
            document.getElementById('modal-chapter-title').innerText       = ch.title;
            document.getElementById('modal-chapter-content').value         = ch.content;
            document.getElementById('modal-review-comment').value          = '';
            document.getElementById('modal-review-alert').innerHTML        = '';
            loadModalChapterReviews(sid, chapterId);
        }
    }

    async function loadModalChapterReviews(sid, chapterId) {
        try {
            const res = await fetch(`api.php?action=get_chapter_reviews&student_id=${sid}&chapter_id=${chapterId}`);
            const r   = await res.json();
            const div = document.getElementById('modal-reviews-list');
            if (r.status === 'success' && r.data.length) {
                div.innerHTML = r.data.map(rev => `
                    <div class="comment-item" style="border-color:var(--accent-secondary);">
                        <p class="comment-text">${escapeHtml(rev.comment)}</p>
                        <div class="comment-meta">— ${escapeHtml(rev.supervisor_name)} | ${rev.status} | ${new Date(rev.created_at).toLocaleDateString()}</div>
                    </div>`).join('');
            } else {
                div.innerHTML = '<p style="color:var(--text-muted);font-size:0.85rem;">No reviews yet for this chapter.</p>';
            }
        } catch(e) { console.error(e); }
    }

    async function submitChapterReview() {
        const comment = document.getElementById('modal-review-comment').value.trim();
        const status  = document.getElementById('modal-review-status').value;
        const alert   = document.getElementById('modal-review-alert');
        if (!comment) { alert.innerHTML = '<div class="alert alert-danger">Please enter a comment.</div>'; return; }
        const btn = document.getElementById('review-submit-btn');
        btn.disabled = true;
        try {
            const res = await fetch('api.php?action=submit_chapter_review', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ student_user_id: currentStudentId, chapter_id: modalActiveChapterId, comment, status })
            });
            const r = await res.json();
            if (r.status === 'success') {
                alert.innerHTML = '<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Review submitted.</div>';
                document.getElementById('modal-review-comment').value = '';
                loadModalChapterReviews(currentStudentId, modalActiveChapterId);
                setTimeout(() => alert.innerHTML = '', 3000);
            } else {
                alert.innerHTML = `<div class="alert alert-danger">${r.message}</div>`;
            }
        } catch(e) { console.error(e); }
        finally { btn.disabled = false; }
    }

    async function loadModalAnalytics(sid) {
        try {
            const res = await fetch(`api.php?action=get_analytics&student_id=${sid}`);
            const r   = await res.json();
            if (r.status === 'success') {
                const d = r.data;
                document.getElementById('modal-youth-ratio').innerText      = d.owner_charts.youth_percentage + '%';
                document.getElementById('modal-total-surveys').innerText    = d.totals.employees;
                document.getElementById('modal-total-interviews').innerText = d.totals.owners;
                // Destroy old modal charts first
                ['modal-chart-gender','modal-chart-age','modal-chart-edu','modal-chart-biz'].forEach(id => {
                    if (modalChartsInstance[id]) { modalChartsInstance[id].destroy(); delete modalChartsInstance[id]; }
                });
                renderChartGender(d.employee_charts.gender,       'modal-chart-gender', modalChartsInstance);
                renderChartAge(d.employee_charts.age,             'modal-chart-age',    modalChartsInstance);
                renderChartEducation(d.employee_charts.education, 'modal-chart-edu',    modalChartsInstance);
                renderChartBizType(d.employee_charts.business_type,'modal-chart-biz',   modalChartsInstance);
            }
        } catch(e) { console.error(e); }
    }

    async function loadModalApproval(sid) {
        try {
            // Load metadata for student name
            await loadModalMetadata(sid);
            const res = await fetch(`api.php?action=get_approval&student_id=${sid}`);
            const r   = await res.json();
            if (r.status === 'success' && r.data) {
                const d = r.data;
                const statusColor = d.status==='Approved' ? 'var(--accent-success)' : d.status==='Rejected' ? 'var(--accent-danger)' : 'var(--accent-warning)';
                document.getElementById('modal-current-status').innerText   = d.status   || 'Pending';
                document.getElementById('modal-current-status').style.color = statusColor;
                document.getElementById('modal-current-feedback').innerText = d.feedback ? `Feedback: "${d.feedback}"` : '';
                // Pre-fill supervisor's qualification if previously set
                if (d.qualification) document.getElementById('modal-app-qual').value = d.qualification;
                if (d.status && d.status !== 'Pending') document.getElementById('modal-app-status').value = d.status;
            }
        } catch(e) { console.error(e); }
    }

    async function saveStudentApproval(e) {
        e.preventDefault();
        const btn = document.getElementById('modal-approval-btn');
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
        const payload = {
            student_user_id : parseInt(document.getElementById('modal-student-uid').value),
            supervisor_name : document.getElementById('modal-app-name').value,
            qualification   : document.getElementById('modal-app-qual').value,
            status          : document.getElementById('modal-app-status').value,
            feedback        : document.getElementById('modal-app-feedback').value,
        };
        try {
            const res = await fetch('api.php?action=submit_approval', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const r   = await res.json();
            const alertDiv = document.getElementById('modal-approval-alert');
            if (r.status === 'success') {
                alertDiv.innerHTML = `<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> ${r.message}</div>`;
                loadModalApproval(currentStudentId);
                loadStudentsPanel(); // refresh table
            } else {
                alertDiv.innerHTML = `<div class="alert alert-danger">${r.message}</div>`;
            }
        } catch(e) { console.error(e); }
        finally { btn.disabled = false; btn.innerHTML = 'Submit Evaluation <i class="fa-solid fa-check-double"></i>'; }
    }

    // ================================================================
    // COMMON FUNCTIONS
    // ================================================================
    async function handleLogout() {
        try {
            await fetch('api.php?action=logout', { method:'POST' });
            window.location.href = 'login.php';
        } catch(e) { window.location.href = 'login.php'; }
    }

    // Initial load handled by window.onload

    // Theme Switcher
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon      = document.getElementById('theme-icon');
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

    // ================================================================
    // NEW FEATURES JAVASCRIPT
    // ================================================================

    // --- Notifications ---
    function toggleNotifications(e) {
        document.getElementById('notif-dropdown').classList.toggle('active');
        e.stopPropagation();
    }
    window.addEventListener('click', () => {
        document.getElementById('notif-dropdown').classList.remove('active');
    });

    async function fetchNotifications() {
        try {
            const res = await fetch('api.php?action=get_notifications');
            const r = await res.json();
            if (r.status === 'success') {
                const list = document.getElementById('notif-list');
                const badge = document.getElementById('notif-badge-count');
                let unreadCount = 0;
                list.innerHTML = '';
                if (r.data.length === 0) {
                    list.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-muted);font-size:0.85rem;">No new notifications</div>';
                } else {
                    r.data.forEach(n => {
                        if (n.is_read == 0) unreadCount++;
                        let icon = 'fa-info-circle';
                        if (n.type === 'survey' || n.type === 'interview') icon = 'fa-file-lines';
                        if (n.type === 'review') icon = 'fa-comment-dots';
                        if (n.type === 'approval') icon = 'fa-check-double';
                        list.innerHTML += `
                            <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}">
                                <div class="notif-icon"><i class="fa-solid ${icon}"></i></div>
                                <div>
                                    <div style="margin-bottom:4px;">${n.message}</div>
                                    <div style="font-size:0.7rem;color:var(--text-muted);">${n.created_at}</div>
                                </div>
                            </div>
                        `;
                    });
                }
                if (unreadCount > 0) {
                    badge.textContent = unreadCount;
                    badge.classList.add('active');
                } else {
                    badge.classList.remove('active');
                }
            }
        } catch(e) { console.error(e); }
    }

    async function markNotificationsRead() {
        try {
            await fetch('api.php?action=mark_notifications_read', { method:'POST' });
            fetchNotifications();
            document.getElementById('notif-dropdown').classList.remove('active');
        } catch(e) { console.error(e); }
    }

    // --- Profile ---
    async function loadProfile() {
        try {
            const res = await fetch('api.php?action=get_profile');
            const r = await res.json();
            if (r.status === 'success') {
                document.getElementById('profile-email').value = r.data.email || '';
                document.getElementById('profile-phone').value = r.data.phone || '';
                document.getElementById('profile-bio').value = r.data.bio || '';
            }
        } catch(e) { console.error(e); }
    }

    async function saveProfile(e) {
        e.preventDefault();
        const btn = document.getElementById('profile-save-btn');
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        const payload = {
            email: document.getElementById('profile-email').value,
            phone: document.getElementById('profile-phone').value,
            bio: document.getElementById('profile-bio').value
        };
        try {
            const res = await fetch('api.php?action=update_profile', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const r = await res.json();
            const alertDiv = document.getElementById('profile-alert-container');
            if (r.status === 'success') {
                alertDiv.innerHTML = `<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> ${r.message}</div>`;
            } else {
                alertDiv.innerHTML = `<div class="alert alert-danger">${r.message}</div>`;
            }
        } catch(e) { console.error(e); }
        finally { btn.disabled = false; btn.innerHTML = 'Update Profile <i class="fa-solid fa-save"></i>'; }
    }

    // --- Search & Filter (Supervisor) ---
    function filterStudents() {
        const query = document.getElementById('student-search-input').value.toLowerCase();
        const statusFilter = document.getElementById('student-status-filter').value;
        const rows = document.querySelectorAll('#students-table-body tr.student-row');
        
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const status = row.getAttribute('data-status') || '';
            const matchesQuery = text.includes(query);
            const matchesStatus = (statusFilter === 'All') || (status === statusFilter);
            
            if (matchesQuery && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // --- Timeline Stepper Update ---
    function updateTimeline(progressVal, approvalStatus) {
        // Find timeline container. If it's a student, it's 'student-timeline'.
        // If supervisor viewing modal, it's 'modal-timeline' (Wait, we haven't added it to modal yet. Let's just update the student one for now, or both if they exist).
        const tl = document.getElementById('student-timeline') || document.getElementById('modal-timeline');
        if (!tl) return;
        
        const steps = tl.querySelectorAll('.timeline-step');
        if (steps.length < 5) return;
        
        // Reset all
        steps.forEach(s => { s.classList.remove('active', 'completed'); });
        
        // Step 1: Registered (Always completed)
        steps[0].classList.add('completed');
        
        // Step 2: Chapters (Completed if chapters drafted, roughly > 0%)
        if (progressVal > 10) steps[1].classList.add('completed');
        else steps[1].classList.add('active');
        
        // Step 3: Data Collection (Completed if progress > 50%)
        if (progressVal > 50) steps[2].classList.add('completed');
        else if (progressVal > 10) steps[2].classList.add('active');
        
        // Step 4: Supervisor Review (Completed if Reviewed or Approved)
        if (approvalStatus === 'Reviewed' || approvalStatus === 'Approved') steps[3].classList.add('completed');
        else if (progressVal > 50) steps[3].classList.add('active');
        
        // Step 5: Final Approval (Completed if Approved)
        if (approvalStatus === 'Approved') steps[4].classList.add('completed');
        else if (approvalStatus === 'Reviewed') steps[4].classList.add('active');
    }

    // ================================================================
    // ADMIN FUNCTIONS
    // ================================================================
    async function loadAdminOverview() {
        try {
            const res = await fetch('api.php?action=get_admin_dashboard');
            const result = await res.json();
            if (result.status !== 'success') return;
            const d = result.data;
            document.getElementById('admin-stat-students').innerText = d.totals.students;
            document.getElementById('admin-stat-supervisors').innerText = d.totals.supervisors;
            document.getElementById('admin-stat-admins').innerText = d.totals.admins;
            document.getElementById('admin-stat-surveys').innerText = d.totals.surveys;
        } catch(e) { console.error(e); }
    }

    async function loadAdminSupervisors() {
        try {
            const res = await fetch('api.php?action=get_admin_dashboard');
            const result = await res.json();
            if (result.status !== 'success') return;
            const list = result.data.supervisors_list;
            const tbody = document.getElementById('supervisors-table-body');
            tbody.innerHTML = '';
            
            if (list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="color:var(--text-muted);">No supervisor accounts registered.</td></tr>';
                return;
            }

            list.forEach(s => {
                const tr = document.createElement('tr');
                const isDeactivated = s.status === 'deactivated';
                const statusBadge = isDeactivated 
                    ? '<span class="badge badge-danger">Deactivated</span>' 
                    : '<span class="badge badge-success">Active</span>';
                
                const toggleBtn = isDeactivated
                    ? `<button class="btn btn-success" style="font-size:0.8rem;padding:4px 8px;" onclick="toggleSupervisorStatus(${s.id}, 'active')"><i class="fa-solid fa-circle-check"></i> Activate</button>`
                    : `<button class="btn btn-warning" style="font-size:0.8rem;padding:4px 8px;background:var(--accent-warning);color:#fff;" onclick="toggleSupervisorStatus(${s.id}, 'deactivated')"><i class="fa-solid fa-circle-minus"></i> Deactivate</button>`;

                const escapedName = escapeHtml(s.fullname);
                const escapedUser = escapeHtml(s.username);
                const escapedEmail = escapeHtml(s.email || '');
                const escapedPhone = escapeHtml(s.phone || '');
                const escapedBio = escapeHtml(s.bio || '');

                tr.innerHTML = `
                    <td><strong>${escapedName}</strong></td>
                    <td>@${escapedUser}</td>
                    <td>${escapedEmail || '—'}</td>
                    <td>${escapedPhone || '—'}</td>
                    <td style="max-width:200px;"><span title="${escapedBio}" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapedBio || '—'}</span></td>
                    <td>${statusBadge}</td>
                    <td class="text-center">
                        <div style="display:flex;gap:4px;justify-content:center;">
                            <button class="btn btn-secondary" style="font-size:0.8rem;padding:4px 8px;" onclick="openEditSupervisorModal(${s.id}, '${escapedUser.replace(/'/g, "\\'")}', '${escapedName.replace(/'/g, "\\'")}', '${escapedEmail.replace(/'/g, "\\'")}', '${escapedPhone.replace(/'/g, "\\'")}', '${escapedBio.replace(/'/g, "\\'")}')"><i class="fa-solid fa-edit"></i> Edit</button>
                            ${toggleBtn}
                            <button class="btn btn-danger" style="font-size:0.8rem;padding:4px 8px;" onclick="deleteSupervisor(${s.id})"><i class="fa-solid fa-trash"></i> Delete</button>
                        </div>
                    </td>`;
                tbody.appendChild(tr);
            });
        } catch(e) { console.error(e); }
    }

    // Modal Add
    function openAddSupervisorModal() {
        document.getElementById('add-supervisor-form').reset();
        document.getElementById('add-supervisor-modal').classList.add('active');
    }
    function closeAddSupervisorModal() {
        document.getElementById('add-supervisor-modal').classList.remove('active');
    }
    async function submitCreateSupervisor(e) {
        e.preventDefault();
        const btn = document.getElementById('add-sup-save-btn');
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        const payload = {
            username: document.getElementById('add-sup-username').value.trim(),
            fullname: document.getElementById('add-sup-fullname').value.trim(),
            password: document.getElementById('add-sup-password').value,
            email: document.getElementById('add-sup-email').value.trim(),
            phone: document.getElementById('add-sup-phone').value.trim(),
            bio: document.getElementById('add-sup-bio').value.trim()
        };
        try {
            const res = await fetch('api.php?action=admin_create_supervisor', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const r = await res.json();
            const alertDiv = document.getElementById('supervisors-alert-container');
            if (r.status === 'success') {
                alertDiv.innerHTML = `<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> ${r.message}</div>`;
                closeAddSupervisorModal();
                loadAdminSupervisors();
            } else {
                alertDiv.innerHTML = `<div class="alert alert-danger">${r.message}</div>`;
            }
        } catch(e) { console.error(e); }
        finally { btn.disabled = false; btn.innerHTML = 'Create Account'; }
    }

    // Modal Edit
    function openEditSupervisorModal(id, username, fullname, email, phone, bio) {
        document.getElementById('edit-sup-id').value = id;
        document.getElementById('edit-sup-username').value = username;
        document.getElementById('edit-sup-fullname').value = fullname;
        document.getElementById('edit-sup-password').value = '';
        document.getElementById('edit-sup-email').value = email;
        document.getElementById('edit-sup-phone').value = phone;
        document.getElementById('edit-sup-bio').value = bio;
        document.getElementById('edit-supervisor-modal').classList.add('active');
    }
    function closeEditSupervisorModal() {
        document.getElementById('edit-supervisor-modal').classList.remove('active');
    }
    async function submitUpdateSupervisor(e) {
        e.preventDefault();
        const btn = document.getElementById('edit-sup-save-btn');
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        const payload = {
            id: document.getElementById('edit-sup-id').value,
            username: document.getElementById('edit-sup-username').value.trim(),
            fullname: document.getElementById('edit-sup-fullname').value.trim(),
            password: document.getElementById('edit-sup-password').value,
            email: document.getElementById('edit-sup-email').value.trim(),
            phone: document.getElementById('edit-sup-phone').value.trim(),
            bio: document.getElementById('edit-sup-bio').value.trim()
        };
        try {
            const res = await fetch('api.php?action=admin_update_supervisor', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const r = await res.json();
            const alertDiv = document.getElementById('supervisors-alert-container');
            if (r.status === 'success') {
                alertDiv.innerHTML = `<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> ${r.message}</div>`;
                closeEditSupervisorModal();
                loadAdminSupervisors();
            } else {
                alertDiv.innerHTML = `<div class="alert alert-danger">${r.message}</div>`;
            }
        } catch(e) { console.error(e); }
        finally { btn.disabled = false; btn.innerHTML = 'Save Changes'; }
    }

    // Toggle status
    async function toggleSupervisorStatus(id, newStatus) {
        if (!confirm(`Are you sure you want to ${newStatus === 'deactivated' ? 'deactivate' : 'activate'} this supervisor account?`)) return;
        try {
            const res = await fetch('api.php?action=admin_toggle_supervisor_status', { 
                method:'POST', 
                headers:{'Content-Type':'application/json'}, 
                body: JSON.stringify({ id: id, status: newStatus }) 
            });
            const r = await res.json();
            const alertDiv = document.getElementById('supervisors-alert-container');
            if (r.status === 'success') {
                alertDiv.innerHTML = `<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> ${r.message}</div>`;
                loadAdminSupervisors();
            } else {
                alertDiv.innerHTML = `<div class="alert alert-danger">${r.message}</div>`;
            }
        } catch(e) { console.error(e); }
    }

    // Delete
    async function deleteSupervisor(id) {
        if (!confirm('Are you sure you want to permanently delete this supervisor account? All students assigned to them will have their supervisor unassigned.')) return;
        try {
            const res = await fetch('api.php?action=admin_delete_supervisor', { 
                method:'POST', 
                headers:{'Content-Type':'application/json'}, 
                body: JSON.stringify({ id: id }) 
            });
            const r = await res.json();
            const alertDiv = document.getElementById('supervisors-alert-container');
            if (r.status === 'success') {
                alertDiv.innerHTML = `<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> ${r.message}</div>`;
                loadAdminSupervisors();
            } else {
                alertDiv.innerHTML = `<div class="alert alert-danger">${r.message}</div>`;
            }
        } catch(e) { console.error(e); }
    }

    // --- Logout ---
    async function handleLogout() {
        try {
            await fetch('api.php?action=logout', { method: 'POST' });
        } catch(e) { console.error(e); }
        window.location.href = 'login.php';
    }

    </script>
</body>
</html>
