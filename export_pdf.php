<?php
// export_pdf.php
// Generates a print-ready HTML report for browser PDF export (Ctrl+P → Save as PDF)

session_start();
require_once __DIR__ . '/database.php';

// Determine which student's data to export
$view_uid = null;
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'student') {
        $view_uid = intval($_SESSION['user_id']);
    } elseif ($_SESSION['role'] === 'supervisor' && isset($_GET['sid'])) {
        $view_uid = intval($_GET['sid']);
    }
}
if (!$view_uid) {
    die('Unauthorized. Please log in first.');
}

// Fetch all data
$meta = $pdo->prepare("SELECT * FROM project_metadata WHERE user_id = ?");
$meta->execute([$view_uid]);
$metadata = $meta->fetch();
if (!$metadata) die('No project data found.');

$chaps = $pdo->prepare("SELECT * FROM research_chapters WHERE user_id = ? ORDER BY id");
$chaps->execute([$view_uid]);
$chapters = $chaps->fetchAll();

$budget_stmt = $pdo->prepare("SELECT * FROM budget_items WHERE user_id = ? ORDER BY id");
$budget_stmt->execute([$view_uid]);
$budget_items = $budget_stmt->fetchAll();
$budget_total = 0;
foreach ($budget_items as $b) $budget_total += floatval($b['cost']);

$emp_count = $pdo->prepare("SELECT COUNT(*) FROM employee_surveys WHERE user_id = ?");
$emp_count->execute([$view_uid]);
$total_surveys = $emp_count->fetchColumn();

$own_count = $pdo->prepare("SELECT COUNT(*) FROM owner_interviews WHERE user_id = ?");
$own_count->execute([$view_uid]);
$total_interviews = $own_count->fetchColumn();

// Gender stats
$gender = $pdo->prepare("SELECT gender, COUNT(*) as count FROM employee_surveys WHERE user_id = ? GROUP BY gender");
$gender->execute([$view_uid]);
$gender_data = $gender->fetchAll();

// Age stats
$age = $pdo->prepare("SELECT age, COUNT(*) as count FROM employee_surveys WHERE user_id = ? GROUP BY age");
$age->execute([$view_uid]);
$age_data = $age->fetchAll();

// Education stats
$edu = $pdo->prepare("SELECT education, COUNT(*) as count FROM employee_surveys WHERE user_id = ? GROUP BY education");
$edu->execute([$view_uid]);
$edu_data = $edu->fetchAll();

// Business type stats
$biz = $pdo->prepare("SELECT business_type, COUNT(*) as count FROM employee_surveys WHERE user_id = ? GROUP BY business_type");
$biz->execute([$view_uid]);
$biz_data = $biz->fetchAll();

// Youth employment ratio
$youth = $pdo->prepare("SELECT SUM(youth_employees) as youth, SUM(total_employees) as total FROM owner_interviews WHERE user_id = ?");
$youth->execute([$view_uid]);
$yr = $youth->fetch();
$youth_pct = ($yr['total'] > 0) ? round(($yr['youth'] / $yr['total']) * 100, 1) : 0;

// Approval
$app = $pdo->prepare("SELECT * FROM supervisor_approval WHERE user_id = ?");
$app->execute([$view_uid]);
$approval = $app->fetch();

$export_type = isset($_GET['type']) ? $_GET['type'] : 'full';
$now = date('F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($metadata['title']); ?> — Research Report</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            font-size: 12pt;
            line-height: 1.7;
            color: #1a1a2e;
            background: #fff;
            padding: 0;
        }

        /* Print styles */
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            @page { margin: 20mm 15mm; size: A4; }
            .report-page { box-shadow: none; padding: 0; margin: 0; }
        }

        /* Screen styles */
        @media screen {
            body { background: #f0f0f5; padding: 20px; }
            .report-page {
                max-width: 210mm;
                margin: 0 auto 30px;
                background: #fff;
                box-shadow: 0 2px 20px rgba(0,0,0,0.1);
                border-radius: 4px;
                padding: 40px 50px;
            }
        }

        /* Toolbar */
        .export-toolbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
            box-shadow: 0 2px 12px rgba(0,0,0,0.3);
        }
        .export-toolbar h3 { font-size: 1rem; font-weight: 600; }
        .export-toolbar .toolbar-actions { display: flex; gap: 10px; }
        .toolbar-btn {
            padding: 8px 18px;
            border: none;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .toolbar-btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: #fff;
        }
        .toolbar-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        .toolbar-btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .toolbar-btn-secondary:hover { background: rgba(255,255,255,0.2); }

        @media screen {
            body { padding-top: 70px; }
        }

        /* Typography */
        .report-title {
            font-size: 16pt;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            color: #1a1a2e;
            margin-bottom: 6px;
            letter-spacing: 0.02em;
        }
        .report-subtitle {
            font-size: 11pt;
            text-align: center;
            color: #555;
            margin-bottom: 30px;
        }
        h2.section-title {
            font-size: 14pt;
            font-weight: 700;
            color: #1a1a2e;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 6px;
            margin: 30px 0 16px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        h3.subsection-title {
            font-size: 12pt;
            font-weight: 600;
            color: #333;
            margin: 20px 0 10px;
        }
        p, li { font-size: 11pt; line-height: 1.8; color: #333; }

        /* Cover page */
        .cover-page {
            text-align: center;
            padding: 60px 40px;
            border: 3px double #1a1a2e;
            min-height: 700px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .cover-institution {
            font-size: 16pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .cover-department {
            font-size: 12pt;
            color: #555;
            margin-bottom: 40px;
        }
        .cover-title {
            font-size: 18pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #1a1a2e;
            max-width: 500px;
            line-height: 1.4;
            margin-bottom: 40px;
        }
        .cover-meta {
            font-size: 11pt;
            color: #444;
            margin: 6px 0;
        }
        .cover-meta strong { color: #1a1a2e; }
        .cover-divider {
            width: 80px;
            height: 3px;
            background: #6366f1;
            margin: 20px auto;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 10pt;
        }
        .data-table th, .data-table td {
            padding: 10px 14px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #1a1a2e;
            font-size: 10pt;
        }
        .data-table tr:nth-child(even) { background: #fafafa; }
        .data-table tfoot td {
            font-weight: 700;
            background: #f0f0f5;
            border-top: 2px solid #1a1a2e;
        }

        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin: 16px 0;
        }
        .stat-box {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        .stat-box .stat-value {
            font-size: 22pt;
            font-weight: 700;
            color: #6366f1;
        }
        .stat-box .stat-label {
            font-size: 9pt;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 4px;
        }

        /* Chapter content */
        .chapter-content {
            white-space: pre-wrap;
            font-size: 11pt;
            line-height: 1.8;
            text-align: justify;
        }

        /* Signature area */
        .signature-area {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
        }
        .signature-block {
            border-top: 1px solid #333;
            padding-top: 8px;
            margin-top: 60px;
        }
        .signature-block p { font-size: 10pt; color: #555; margin: 2px 0; }

        /* Watermark for draft */
        .draft-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80pt;
            color: rgba(99, 102, 241, 0.06);
            font-weight: 900;
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body>

<!-- Toolbar (hidden in print) -->
<div class="export-toolbar no-print">
    <h3>📄 Research Report Export</h3>
    <div class="toolbar-actions">
        <button class="toolbar-btn toolbar-btn-secondary" onclick="window.history.back()">← Back to Dashboard</button>
        <button class="toolbar-btn toolbar-btn-primary" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</div>

<?php if ($approval && $approval['status'] !== 'Approved'): ?>
<div class="draft-watermark no-print">DRAFT</div>
<?php endif; ?>

<!-- ==================== COVER PAGE ==================== -->
<div class="report-page">
    <div class="cover-page">
        <div class="cover-institution"><?php echo htmlspecialchars($metadata['institution']); ?></div>
        <div class="cover-department"><?php echo htmlspecialchars($metadata['department']); ?></div>

        <div class="cover-divider"></div>

        <div class="cover-title"><?php echo htmlspecialchars($metadata['title']); ?></div>

        <div class="cover-divider"></div>

        <div class="cover-meta"><strong>A Research Project Submitted in Partial Fulfillment of the Requirements for the Award of</strong></div>
        <div class="cover-meta" style="font-size:13pt;font-weight:600;margin-bottom:30px;"><?php echo htmlspecialchars($metadata['degree_type']); ?></div>

        <div class="cover-meta"><strong>By:</strong> <?php echo htmlspecialchars($metadata['student_name']); ?></div>
        <div class="cover-meta"><strong>Reg No:</strong> <?php echo htmlspecialchars($metadata['reg_number']); ?></div>
        <div class="cover-meta"><strong>Study Area:</strong> <?php echo htmlspecialchars($metadata['town_community']); ?></div>
        <div class="cover-meta" style="margin-top:20px;"><strong>Submission Date:</strong> <?php echo htmlspecialchars($metadata['submission_date']); ?></div>
    </div>
</div>

<!-- ==================== DECLARATION ==================== -->
<div class="report-page page-break">
    <h2 class="section-title">Declaration</h2>

    <h3 class="subsection-title">Student Declaration</h3>
    <p>I declare that this research project is my original work and has not been presented for any academic award in any institution. All sources of information have been acknowledged through proper referencing.</p>

    <div class="signature-area">
        <div>
            <div class="signature-block">
                <p><strong><?php echo htmlspecialchars($metadata['student_name']); ?></strong></p>
                <p>Student — <?php echo htmlspecialchars($metadata['reg_number']); ?></p>
                <p>Date: <?php echo $now; ?></p>
            </div>
        </div>
        <div>
            <div class="signature-block">
                <?php if ($approval && $approval['status'] === 'Approved'): ?>
                <p><strong><?php echo htmlspecialchars($approval['supervisor_name']); ?></strong></p>
                <p>Supervisor<?php echo !empty($approval['qualification']) ? ' — ' . htmlspecialchars($approval['qualification']) : ''; ?></p>
                <p>Status: <strong style="color:green;">APPROVED</strong></p>
                <p>Date: <?php echo htmlspecialchars($approval['approval_date']); ?></p>
                <?php else: ?>
                <p><em>Awaiting supervisor approval</em></p>
                <p>Status: <strong style="color:orange;"><?php echo htmlspecialchars($approval['status'] ?? 'Pending'); ?></strong></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($approval && !empty($approval['feedback'])): ?>
    <div style="margin-top:30px;padding:16px;background:#f8f9fa;border-left:4px solid #6366f1;border-radius:0 8px 8px 0;">
        <h3 class="subsection-title" style="margin-top:0;">Supervisor Feedback</h3>
        <p><em>"<?php echo htmlspecialchars($approval['feedback']); ?>"</em></p>
    </div>
    <?php endif; ?>
</div>

<!-- ==================== TABLE OF CONTENTS ==================== -->
<div class="report-page page-break">
    <h2 class="section-title">Table of Contents</h2>
    <table class="data-table" style="border:none;">
        <?php foreach ($chapters as $i => $ch): ?>
        <tr style="border:none;border-bottom:1px dotted #ccc;">
            <td style="border:none;padding:8px 0;"><?php echo htmlspecialchars($ch['title']); ?></td>
            <td style="border:none;padding:8px 0;text-align:right;color:#888;width:60px;"><?php echo ($i + 3); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="border:none;border-bottom:1px dotted #ccc;">
            <td style="border:none;padding:8px 0;">Research Budget</td>
            <td style="border:none;padding:8px 0;text-align:right;color:#888;width:60px;"><?php echo count($chapters) + 3; ?></td>
        </tr>
        <tr style="border:none;border-bottom:1px dotted #ccc;">
            <td style="border:none;padding:8px 0;">Data Analysis &amp; Findings</td>
            <td style="border:none;padding:8px 0;text-align:right;color:#888;width:60px;"><?php echo count($chapters) + 4; ?></td>
        </tr>
    </table>
</div>

<!-- ==================== CHAPTERS ==================== -->
<?php foreach ($chapters as $ch): ?>
<div class="report-page page-break">
    <h2 class="section-title"><?php echo htmlspecialchars($ch['title']); ?></h2>
    <div class="chapter-content"><?php echo htmlspecialchars($ch['content']); ?></div>
</div>
<?php endforeach; ?>

<!-- ==================== BUDGET ==================== -->
<div class="report-page page-break">
    <h2 class="section-title">Research Budget</h2>
    <p style="margin-bottom:16px;">The following table outlines the estimated costs for conducting this research study.</p>

    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Item Description</th>
                <th>Quantity / Frequency</th>
                <th style="text-align:right;">Cost (KES)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($budget_items as $i => $b): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td><?php echo htmlspecialchars($b['item_name']); ?></td>
                <td><?php echo htmlspecialchars($b['quantity']); ?></td>
                <td style="text-align:right;"><?php echo number_format($b['cost']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>TOTAL ESTIMATED COST</strong></td>
                <td style="text-align:right;"><strong>KES <?php echo number_format($budget_total); ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- ==================== DATA ANALYSIS ==================== -->
<div class="report-page page-break">
    <h2 class="section-title">Data Analysis &amp; Findings</h2>

    <p style="margin-bottom:20px;">This section presents the analyzed data collected from <?php echo $total_surveys; ?> youth employee questionnaires and <?php echo $total_interviews; ?> business owner interviews conducted in <?php echo htmlspecialchars($metadata['town_community']); ?>.</p>

    <!-- Summary Stats -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-value"><?php echo $total_surveys; ?></div>
            <div class="stat-label">Youth Surveyed</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $total_interviews; ?></div>
            <div class="stat-label">Owners Interviewed</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $youth_pct; ?>%</div>
            <div class="stat-label">Youth Employment Ratio</div>
        </div>
    </div>

    <!-- Gender Distribution -->
    <h3 class="subsection-title">4.1 Gender Distribution of Young Employees</h3>
    <table class="data-table">
        <thead><tr><th>Gender</th><th style="text-align:center;">Frequency</th><th style="text-align:center;">Percentage (%)</th></tr></thead>
        <tbody>
            <?php foreach ($gender_data as $g): $pct = $total_surveys > 0 ? round(($g['count']/$total_surveys)*100, 1) : 0; ?>
            <tr>
                <td><?php echo htmlspecialchars($g['gender']); ?></td>
                <td style="text-align:center;"><?php echo $g['count']; ?></td>
                <td style="text-align:center;"><?php echo $pct; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td><strong>Total</strong></td><td style="text-align:center;"><strong><?php echo $total_surveys; ?></strong></td><td style="text-align:center;"><strong>100%</strong></td></tr></tfoot>
    </table>

    <!-- Age Distribution -->
    <h3 class="subsection-title">4.2 Age Profile of Respondents</h3>
    <table class="data-table">
        <thead><tr><th>Age Group</th><th style="text-align:center;">Frequency</th><th style="text-align:center;">Percentage (%)</th></tr></thead>
        <tbody>
            <?php foreach ($age_data as $a): $pct = $total_surveys > 0 ? round(($a['count']/$total_surveys)*100, 1) : 0; ?>
            <tr>
                <td><?php echo htmlspecialchars($a['age']); ?></td>
                <td style="text-align:center;"><?php echo $a['count']; ?></td>
                <td style="text-align:center;"><?php echo $pct; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td><strong>Total</strong></td><td style="text-align:center;"><strong><?php echo $total_surveys; ?></strong></td><td style="text-align:center;"><strong>100%</strong></td></tr></tfoot>
    </table>

    <!-- Education -->
    <h3 class="subsection-title">4.3 Education Level of Young Workers</h3>
    <table class="data-table">
        <thead><tr><th>Education Level</th><th style="text-align:center;">Frequency</th><th style="text-align:center;">Percentage (%)</th></tr></thead>
        <tbody>
            <?php foreach ($edu_data as $e): $pct = $total_surveys > 0 ? round(($e['count']/$total_surveys)*100, 1) : 0; ?>
            <tr>
                <td><?php echo htmlspecialchars($e['education']); ?></td>
                <td style="text-align:center;"><?php echo $e['count']; ?></td>
                <td style="text-align:center;"><?php echo $pct; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td><strong>Total</strong></td><td style="text-align:center;"><strong><?php echo $total_surveys; ?></strong></td><td style="text-align:center;"><strong>100%</strong></td></tr></tfoot>
    </table>

    <!-- Business Types -->
    <h3 class="subsection-title">4.4 Types of Businesses Providing Youth Employment</h3>
    <table class="data-table">
        <thead><tr><th>Business Type</th><th style="text-align:center;">Frequency</th><th style="text-align:center;">Percentage (%)</th></tr></thead>
        <tbody>
            <?php foreach ($biz_data as $b): $pct = $total_surveys > 0 ? round(($b['count']/$total_surveys)*100, 1) : 0; ?>
            <tr>
                <td><?php echo htmlspecialchars($b['business_type']); ?></td>
                <td style="text-align:center;"><?php echo $b['count']; ?></td>
                <td style="text-align:center;"><?php echo $pct; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td><strong>Total</strong></td><td style="text-align:center;"><strong><?php echo $total_surveys; ?></strong></td><td style="text-align:center;"><strong>100%</strong></td></tr></tfoot>
    </table>

    <!-- Youth Employment Ratio -->
    <h3 class="subsection-title">4.5 Youth Employment Ratio in Surveyed Businesses</h3>
    <p>From the <?php echo $total_interviews; ?> business owners interviewed, a total of <strong><?php echo intval($yr['total']); ?></strong> employees were reported, of which <strong><?php echo intval($yr['youth']); ?></strong> were young people. This represents a youth employment ratio of <strong><?php echo $youth_pct; ?>%</strong>, indicating that small businesses play a significant role in absorbing young workers into the labor market.</p>
</div>

<!-- ==================== FOOTER ==================== -->
<div class="report-page page-break">
    <h2 class="section-title">End of Report</h2>
    <div style="text-align:center;padding:60px 0;">
        <p style="font-size:10pt;color:#888;">This report was generated from the Research Project Management System</p>
        <p style="font-size:10pt;color:#888;">on <?php echo $now; ?></p>
        <div class="cover-divider" style="margin:20px auto;"></div>
        <p style="font-size:11pt;color:#555;margin-top:20px;">
            <strong><?php echo htmlspecialchars($metadata['institution']); ?></strong><br>
            <?php echo htmlspecialchars($metadata['department']); ?><br>
            <?php echo htmlspecialchars($metadata['town_community']); ?>
        </p>
    </div>
</div>

</body>
</html>
