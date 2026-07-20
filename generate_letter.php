<?php
// generate_letter.php
// Appendix IV: Research Authorization Letter Generator

session_start();
require_once __DIR__ . '/database.php';

// Determine which student's data to show
// If logged in as student, use their own user_id.
// If logged in as supervisor viewing a student, use ?sid=X.
// Default fallback: user_id = 1 (demo student).
$view_uid = 1;
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'student') {
        $view_uid = intval($_SESSION['user_id']);
    } elseif ($_SESSION['role'] === 'supervisor' && isset($_GET['sid'])) {
        $view_uid = intval($_GET['sid']);
    }
} elseif (isset($_GET['sid'])) {
    $view_uid = intval($_GET['sid']);
}

// Fetch metadata for the resolved student
$meta_stmt = $pdo->prepare("SELECT * FROM project_metadata WHERE user_id = ?");
$meta_stmt->execute([$view_uid]);
$metadata = $meta_stmt->fetch();

// Fallback to demo student if not found
if (!$metadata) {
    $meta_stmt = $pdo->prepare("SELECT * FROM project_metadata LIMIT 1");
    $meta_stmt->execute();
    $metadata = $meta_stmt->fetch();
}

// Fetch supervisor approval for this student
$sup_stmt = $pdo->prepare("SELECT * FROM supervisor_approval WHERE user_id = ?");
$sup_stmt->execute([$metadata['user_id'] ?? $view_uid]);
$supervisor = $sup_stmt->fetch();
if (!$supervisor) {
    $supervisor = ['status' => 'Pending', 'supervisor_name' => ''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Authorization Letter - <?php echo htmlspecialchars($metadata['student_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f3f4f6;
            color: #111827;
            padding-top: 60px; /* Space for the print navbar */
        }
        
        .print-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #1e1b4b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 999;
        }

        .print-title {
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .print-actions {
            display: flex;
            gap: 12px;
        }

        @media print {
            body {
                padding-top: 0;
                background-color: #ffffff;
            }
            .print-bar {
                display: none !important;
            }
            .letter-container {
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Print Navbar -->
    <div class="print-bar no-print">
        <div class="print-title">
            <i class="fa-solid fa-file-invoice"></i> Research Authorization Letter
        </div>
        <div class="print-actions">
            <button class="btn btn-primary" onclick="window.print()" style="padding: 8px 16px; background: var(--accent-success); box-shadow: none;">
                <i class="fa-solid fa-print"></i> Print Letter
            </button>
            <a href="index.php" class="btn btn-secondary" style="padding: 8px 16px; color: white; border-color: rgba(255,255,255,0.2);">
                <i class="fa-solid fa-circle-xmark"></i> Close
            </a>
        </div>
    </div>

    <!-- Letter Body -->
    <div class="letter-container">
        <!-- Institution Letterhead -->
        <div class="letter-header">
            <div class="letter-logo"><?php echo htmlspecialchars($metadata['institution']); ?></div>
            <div class="letter-subheader">
                <?php echo htmlspecialchars($metadata['department']); ?> | Email: research@<?php 
                    $domain = strtolower(str_replace(' ', '', $metadata['institution']));
                    echo htmlspecialchars($domain);
                ?>.ac.ke
            </div>
            <div style="font-size: 10pt; color: #4b5563; margin-top: 4px;">
                P.O. Box 90120 - 80100, Mombasa, Kenya. Tel: +254 (041) 211-8000
            </div>
        </div>

        <div class="letter-date">
            <strong>Date:</strong> <?php echo date('F d, Y'); ?>
        </div>

        <div class="letter-recipient">
            <strong>TO WHOM IT MAY CONCERN</strong>
        </div>

        <div class="letter-title">
            SUBJECT: RESEARCH AUTHORIZATION FOR <?php echo htmlspecialchars(strtoupper($metadata['student_name'])); ?> (REG NO: <?php echo htmlspecialchars($metadata['reg_number']); ?>)
        </div>

        <div class="letter-body">
            <p>
                This is to certify that the above-named is a registered student at the <strong><?php echo htmlspecialchars($metadata['institution']); ?></strong>, pursuing a <strong><?php echo htmlspecialchars($metadata['degree_type']); ?></strong> in the <strong><?php echo htmlspecialchars($metadata['department']); ?></strong>.
            </p>
            <p>
                As part of the academic requirements for the completion of the program, the student is conducting an original research study titled: <strong>"<?php echo htmlspecialchars($metadata['title']); ?>"</strong>.
            </p>
            <p>
                To achieve the objectives of this research, the student is authorized and required to collect data from small business owners and young employees within <strong><?php echo htmlspecialchars($metadata['town_community']); ?></strong>. The study employs simple random sampling and relies on brief questionnaires and structured interviews.
            </p>
            <p>
                We kindly request that you extend your maximum cooperation, access, and assistance to the student during the data collection process. Please note that all information collected will be treated with the utmost confidentiality, and will be used strictly for academic research purposes.
            </p>
            <p>
                Your support and cooperation in this matter are highly valued and appreciated by the institution.
            </p>
        </div>

        <p>Yours faithfully,</p>
        
        <!-- Signatures block -->
        <div class="letter-signatures">
            <div class="letter-signature-block">
                <br>
                <div style="font-style: italic; color: #4b5563; min-height: 40px; display: flex; align-items: center; justify-content: center;">
                    <!-- Visual signed status -->
                    <?php if ($supervisor['status'] === 'Approved'): ?>
                        <span style="font-family: 'Brush Script MT', cursive, sans-serif; font-size: 18pt; color: #1e3a8a;">
                            <?php echo htmlspecialchars($supervisor['supervisor_name']); ?>
                        </span>
                    <?php else: ?>
                        <span style="color: #9ca3af;">(Unsigned - Pending Approval)</span>
                    <?php endif; ?>
                </div>
                <div style="font-weight: bold; margin-top: 8px;">
                    <?php echo htmlspecialchars($supervisor['supervisor_name']); ?>
                </div>
                <div style="font-size: 10pt; color: #4b5563;">
                    Supervising Lecturer / Dean of Studies<br>
                    <?php echo htmlspecialchars($metadata['institution']); ?>
                </div>
            </div>
            
            <div class="letter-signature-block" style="border-top: none;">
                <!-- Official Seal Placeholder -->
                <div style="border: 2px dashed #9ca3af; border-radius: 50%; width: 100px; height: 100px; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 8pt; font-weight: bold; text-transform: uppercase; text-align: center;">
                    OFFICIAL<br>INSTITUTION<br>SEAL
                </div>
            </div>
        </div>
    </div>
</body>
</html>
