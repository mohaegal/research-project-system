<?php
// database.php
// SQLite connection - Multi-user Research Project Management System (v2)

$db_file = __DIR__ . '/research.db';

// --- Schema Migration Detection ---
// If the DB exists but is using the old schema (no bio on users), delete and recreate.
if (file_exists($db_file) && filesize($db_file) > 0) {
    try {
        $check_pdo = new PDO("sqlite:$db_file");
        $check_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pragma_rows = $check_pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
        $existing_cols = array_column($pragma_rows, 'name');
        unset($check_pdo);
        if (!in_array('bio', $existing_cols) || !in_array('status', $existing_cols)) {
            @unlink($db_file); // Remove old-schema DB
        }
    } catch (Exception $e) {
        if (file_exists($db_file)) { @unlink($db_file); }
    }
}

$db_needs_init = !file_exists($db_file) || filesize($db_file) === 0;

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA journal_mode=WAL");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($db_needs_init) {
    init_database($pdo);
}

// ---------------------------------------------------------------------------
// PUBLIC HELPER: called from api.php after registering a new student
// ---------------------------------------------------------------------------
function create_student_scaffold($pdo, $user_id, $fullname) {
    // Select a random supervisor to assign to this student
    $sup_id = $pdo->query("SELECT id FROM users WHERE role='supervisor' ORDER BY RANDOM() LIMIT 1")->fetchColumn();
    $pdo->prepare("UPDATE users SET supervisor_id = ? WHERE id = ?")->execute([$sup_id, $user_id]);

    $pdo->prepare("INSERT OR IGNORE INTO project_metadata
        (user_id, title, student_name, reg_number, institution, department, degree_type, town_community, submission_date)
        VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([$user_id, 'Enter Your Research Title Here', $fullname,
            'REG/DIP/0000/' . date('Y'), 'Kenya Institute of Management',
            'Department of Business Studies', 'Diploma in Business Management',
            'Your Study Community', date('F Y')]);

    _seed_chapters_for_user($pdo, $user_id);
    _seed_budget_for_user($pdo, $user_id);
    _seed_milestones_for_user($pdo, $user_id);

    $pdo->prepare("INSERT OR IGNORE INTO supervisor_approval
        (user_id, supervisor_name, qualification, status, approval_date, feedback) VALUES (?,?,?,?,?,?)")
        ->execute([$user_id, '', '', 'Pending', '', '']);
}

// ---------------------------------------------------------------------------
// DATABASE INITIALIZER
// ---------------------------------------------------------------------------
function init_database($pdo) {

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        username      TEXT    UNIQUE NOT NULL,
        password_hash TEXT    NOT NULL,
        role          TEXT    NOT NULL,
        fullname      TEXT    NOT NULL,
        email         TEXT    DEFAULT '',
        phone         TEXT    DEFAULT '',
        bio           TEXT    DEFAULT '',
        supervisor_id INTEGER DEFAULT NULL,
        status        TEXT    DEFAULT 'active',
        created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Project Metadata (one row per student)
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_metadata (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id         INTEGER NOT NULL UNIQUE,
        title           TEXT    NOT NULL,
        student_name    TEXT    NOT NULL,
        reg_number      TEXT    NOT NULL,
        institution     TEXT    NOT NULL,
        department      TEXT    NOT NULL,
        degree_type     TEXT    NOT NULL,
        town_community  TEXT    NOT NULL,
        submission_date TEXT    NOT NULL
    )");

    // 3. Research Chapters (one set per student)
    $pdo->exec("CREATE TABLE IF NOT EXISTS research_chapters (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id    INTEGER NOT NULL,
        chapter_id TEXT    NOT NULL,
        title      TEXT    NOT NULL,
        content    TEXT    NOT NULL,
        UNIQUE(user_id, chapter_id)
    )");

    // 4. Budget Items
    $pdo->exec("CREATE TABLE IF NOT EXISTS budget_items (
        id        INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id   INTEGER NOT NULL,
        item_name TEXT    NOT NULL,
        quantity  TEXT    NOT NULL,
        cost      REAL    NOT NULL
    )");

    // 5. Employee Surveys
    $pdo->exec("CREATE TABLE IF NOT EXISTS employee_surveys (
        id                  INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id             INTEGER NOT NULL DEFAULT 1,
        fullname            TEXT    NOT NULL,
        gender              TEXT    NOT NULL,
        age                 TEXT    NOT NULL,
        education           TEXT    NOT NULL,
        job_title           TEXT    NOT NULL,
        business_type       TEXT    NOT NULL,
        duration            TEXT    NOT NULL,
        income              TEXT    NOT NULL,
        skills_improved     TEXT    NOT NULL,
        skills_details      TEXT,
        challenges          TEXT    NOT NULL,
        challenges_details  TEXT,
        recommend           TEXT    NOT NULL,
        recommend_reason    TEXT,
        improvements        TEXT,
        submitted_at        DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 6. Owner Interviews
    $pdo->exec("CREATE TABLE IF NOT EXISTS owner_interviews (
        id                 INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id            INTEGER NOT NULL DEFAULT 1,
        owner_name         TEXT    NOT NULL,
        business_name      TEXT    NOT NULL,
        business_type      TEXT    NOT NULL,
        operation_duration TEXT    NOT NULL,
        total_employees    INTEGER NOT NULL,
        youth_employees    INTEGER NOT NULL,
        motivation         TEXT    NOT NULL,
        roles              TEXT    NOT NULL,
        challenges         TEXT    NOT NULL,
        support_needed     TEXT    NOT NULL,
        training_programs  TEXT    NOT NULL,
        advice             TEXT    NOT NULL,
        submitted_at       DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 7. Supervisor Approval (one row per student)
    $pdo->exec("CREATE TABLE IF NOT EXISTS supervisor_approval (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id         INTEGER NOT NULL UNIQUE,
        supervisor_name TEXT    NOT NULL DEFAULT '',
        qualification   TEXT    NOT NULL DEFAULT '',
        status          TEXT    NOT NULL DEFAULT 'Pending',
        approval_date   TEXT    DEFAULT '',
        feedback        TEXT    DEFAULT ''
    )");

    // 8. Chapter Reviews (supervisor comments on student chapters)
    $pdo->exec("CREATE TABLE IF NOT EXISTS chapter_reviews (
        id                  INTEGER PRIMARY KEY AUTOINCREMENT,
        student_user_id     INTEGER NOT NULL,
        chapter_id          TEXT    NOT NULL,
        supervisor_user_id  INTEGER NOT NULL,
        comment             TEXT    NOT NULL,
        status              TEXT    NOT NULL DEFAULT 'Reviewed',
        created_at          DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 9. Notifications
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id    INTEGER NOT NULL,
        message    TEXT    NOT NULL,
        type       TEXT    NOT NULL DEFAULT 'info',
        is_read    INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 10. Direct Messages
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        sender_id   INTEGER NOT NULL,
        receiver_id INTEGER NOT NULL,
        message     TEXT NOT NULL,
        is_read     INTEGER DEFAULT 0,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 11. Gantt Milestones
    $pdo->exec("CREATE TABLE IF NOT EXISTS milestones (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id     INTEGER NOT NULL,
        title       TEXT NOT NULL,
        due_date    TEXT NOT NULL,
        status      TEXT NOT NULL DEFAULT 'Pending',
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // --- Seed ---
    _seed_users($pdo);
    _seed_all_student_data($pdo);
}

// ---------------------------------------------------------------------------
// SEED FUNCTIONS
// ---------------------------------------------------------------------------
function _seed_users($pdo) {
    $users = [
        // Demo credentials
        ['student',    password_hash('Pass_student_2026', PASSWORD_DEFAULT), 'student',    'John Doe'],
        ['supervisor', password_hash('Pass_super_2026',   PASSWORD_DEFAULT), 'supervisor', 'Dr. Jane Smith'],
        ['admin',      password_hash('Pass_admin_2026',   PASSWORD_DEFAULT), 'admin',      'System Administrator'],
        // Additional students
        ['alice',      password_hash('Pass_alice_2026',   PASSWORD_DEFAULT), 'student',    'Alice Kamau'],
        ['bob',        password_hash('Pass_bob_2026',     PASSWORD_DEFAULT), 'student',    'Bob Mwangi'],
        ['carol',      password_hash('Pass_carol_2026',   PASSWORD_DEFAULT), 'student',    'Carol Otieno'],
        ['david',      password_hash('Pass_david_2026',   PASSWORD_DEFAULT), 'student',    'David Kariuki'],
        // Additional supervisors
        ['prof_james', password_hash('Pass_james_2026',   PASSWORD_DEFAULT), 'supervisor', 'Prof. James Ochieng'],
        ['dr_mary',    password_hash('Pass_mary_2026',    PASSWORD_DEFAULT), 'supervisor', 'Dr. Mary Njoroge'],
    ];
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, password_hash, role, fullname) VALUES (?,?,?,?)");
    foreach ($users as $u) { $stmt->execute($u); }

    // Map seeded students to their respective supervisors
    $sup_smith   = $pdo->query("SELECT id FROM users WHERE username='supervisor'")->fetchColumn();
    $sup_james   = $pdo->query("SELECT id FROM users WHERE username='prof_james'")->fetchColumn();
    $sup_mary    = $pdo->query("SELECT id FROM users WHERE username='dr_mary'")->fetchColumn();

    $pdo->prepare("UPDATE users SET supervisor_id = ? WHERE username = ?")->execute([$sup_smith, 'student']);
    $pdo->prepare("UPDATE users SET supervisor_id = ? WHERE username = ?")->execute([$sup_smith, 'alice']);
    $pdo->prepare("UPDATE users SET supervisor_id = ? WHERE username = ?")->execute([$sup_james, 'bob']);
    $pdo->prepare("UPDATE users SET supervisor_id = ? WHERE username = ?")->execute([$sup_james, 'david']);
    $pdo->prepare("UPDATE users SET supervisor_id = ? WHERE username = ?")->execute([$sup_mary, 'carol']);
}

function _get_default_chapters() {
    return [
        'abstract' => [
            'Abstract',
            "Youth unemployment is a persistent challenge affecting many communities. Small businesses play a crucial role in absorbing young people into the labor market. This study investigated the role of small businesses in youth employment, focusing on the types of businesses that employ youth, how they contribute to reducing unemployment, the challenges they face in hiring young people, and possible strategies to enhance their capacity to create jobs.\n\nThe study adopted a descriptive survey research design. The target population included small business owners and young employees in Mombasa Community. A sample of 30 business owners and 50 young employees was selected using simple random sampling. Data was collected through questionnaires and interviews, and analyzed using tables, percentages, and charts.\n\nThe findings indicate that small businesses such as retail shops, salons, restaurants, and repair services are major sources of employment for young people. However, challenges such as limited capital, high taxation, and lack of business training limit their capacity to hire more youth. The study recommends that the government and relevant stakeholders provide affordable loans, business training, and favorable policies to support small businesses in creating more employment opportunities for young people.\n\nKeywords: Youth Unemployment, Small Businesses, Employment, Job Creation, Kenya."
        ],
        'introduction' => [
            'Chapter One: Introduction',
            "1.1 Background of the Study\nYouth unemployment is a serious challenge affecting many communities. Young people often complete school or college but cannot find jobs, which can lead to financial struggles, dependency on family, and social problems.\nSmall businesses are a vital part of the economy because they provide goods and services, generate income, and create employment opportunities. They are easier to start than large companies and can help reduce youth unemployment. However, small businesses face challenges like limited capital, high operating costs, and lack of government support, which can limit the number of young people they employ.\nThis study seeks to understand the role of small businesses in providing jobs to youth and explore ways to support them for more effective employment creation.\n\n1.2 Problem Statement\nYouth unemployment remains a major problem in many communities. While small businesses exist in most local areas and provide employment, their role in helping young people secure jobs is not fully understood. Challenges such as lack of capital and resources may prevent small businesses from employing more youth. This study seeks to examine the contribution of small businesses to youth employment and suggest ways to support them in creating more job opportunities.\n\n1.3 Objectives of the Study\nGeneral Objective:\nTo investigate the role of small businesses in youth employment.\n\nSpecific Objectives:\n- To identify small businesses that provide jobs for young people.\n- To examine how small businesses help reduce youth unemployment.\n- To find out the challenges small businesses face in employing young people.\n- To suggest ways of supporting small businesses to create more jobs for youth.\n\n1.4 Research Questions\n- What types of small businesses provide jobs for young people?\n- How do small businesses help reduce youth unemployment?\n- What challenges do small businesses face in employing young people?\n- What strategies can support small businesses to create more job opportunities for youth?\n\n1.5 Significance of the Study\nThis study is important because it:\n- Helps policymakers understand the role of small businesses in reducing youth unemployment.\n- Provides insights to small business owners on challenges and improvements.\n- Guides young people in exploring job opportunities within small businesses.\n- Contributes to community development by suggesting ways to increase youth employment.\n\n1.6 Scope of the Study\nThe study focuses on small businesses in Mombasa Community. It will examine the types of small businesses, employment opportunities they provide for young people, and challenges they face in hiring youth. The main respondents will be small business owners and young employees."
        ],
        'literature_review' => [
            'Chapter Two: Literature Review',
            "2.1 Introduction\nThis chapter reviews existing literature related to youth unemployment and the role of small businesses in job creation. It discusses concepts, previous studies, challenges faced by small businesses, and possible solutions to enhance youth employment.\n\n2.2 Concept of Youth Unemployment\nYouth unemployment refers to a situation where young people who are willing and able to work cannot find suitable employment. According to the Kenya National Bureau of Statistics, youth unemployment remains a significant challenge affecting economic growth and social stability.\nUnemployment among young people leads to financial dependency, poverty, frustration, and sometimes involvement in crime or other social problems. High youth unemployment slows down national development because young people represent an important part of the workforce.\n\n2.3 Overview of Small Businesses\nSmall businesses are enterprises that operate on a small scale with limited capital, employees, and market share. According to the World Bank, small and medium enterprises (SMEs) play a major role in economic development by contributing to job creation and income generation.\nSmall businesses include retail shops, salons, restaurants, small manufacturing units, transport services, and repair shops. They are usually easy to start and operate within local communities.\n\n2.4 Role of Small Businesses in Youth Employment\nSmall businesses contribute to youth employment in several ways:\n- Direct Employment: They hire young people as sales attendants, technicians, assistants, and cashiers.\n- Skill Development: Young employees gain practical experience and develop work skills.\n- Entrepreneurship Opportunities: Small businesses encourage young people to start their own enterprises.\n- Income Generation: Employment provides income that improves the living standards of young people.\nStudies show that small businesses are more flexible in hiring youth compared to large corporations that often require higher qualifications and experience.\n\n2.5 Challenges Facing Small Businesses\nDespite their importance, small businesses face several challenges that limit their ability to employ more young people:\n- Limited access to capital and loans\n- High taxation and licensing fees\n- Lack of training and business management skills\n- High competition from larger companies\n- Economic instability and inflation\nThese challenges reduce business growth and limit job opportunities for youth.\n\n2.6 Strategies to Enhance Youth Employment through Small Businesses\nTo strengthen the role of small businesses in youth employment, the following strategies can be considered:\n- Providing affordable loans and financial support\n- Offering business training and mentorship programs\n- Reducing taxation and simplifying business registration\n- Encouraging youth entrepreneurship programs\n- Strengthening government policies that support SMEs\nSuch measures can help small businesses grow and create more employment opportunities for young people.\n\n2.7 Summary of the Literature Review\nThe literature shows that youth unemployment is a serious economic and social issue. Small businesses play a crucial role in reducing unemployment by providing job opportunities and promoting entrepreneurship. However, financial and operational challenges limit their potential. Therefore, proper support and policies are necessary to enhance their contribution to youth employment."
        ],
        'methodology' => [
            'Chapter Three: Research Methodology',
            "3.1 Introduction\nThis chapter describes the methods that will be used to carry out the study. It explains the research design, target population, sampling techniques, data collection methods, and data analysis procedures used in the study of the role of small businesses in youth employment.\n\n3.2 Research Design\nThe study will use a descriptive survey research design. This design is appropriate because it allows the researcher to collect information from respondents about their opinions, experiences, and practices regarding youth employment in small businesses.\n\n3.3 Target Population\nThe target population of this study will include:\n- Small business owners\n- Young employees working in small businesses\nThese respondents are chosen because they have direct knowledge and experience related to employment in small businesses.\n\n3.4 Sample Size and Sampling Technique\nSample Size:\nCategory | Number of Respondents\nSmall Business Owners | 30\nYoung Employees | 50\nTotal | 80\n\nSampling Technique:\nThe study will use simple random sampling, where respondents are selected randomly to ensure fairness and avoid bias.\n\n3.5 Data Collection Methods\nThe study will use the following methods to collect data:\n- Questionnaires: Structured questionnaires will be given to young employees. They will include both open-ended and closed-ended questions.\n- Interviews: Interviews will be conducted with small business owners. This will help gather detailed information about their experiences and challenges.\n\n3.6 Data Collection Procedure\nThe researcher will visit selected small businesses within the study area. Questionnaires will be distributed to young employees, and interviews will be conducted with business owners. Respondents will be given enough time to answer the questions, and the collected data will be recorded for analysis.\n\n3.7 Data Analysis\nThe collected data will be analyzed using simple statistical methods such as:\n- Tables\n- Percentages\n- Charts (bar graphs and pie charts)\nThis will help in presenting the findings clearly and making conclusions.\n\n3.8 Ethical Considerations\nThe study will observe the following ethical considerations:\n- Participation will be voluntary.\n- Respondents will be informed about the purpose of the study.\n- Confidentiality and privacy of respondents will be maintained.\n- No information will be used for purposes other than the study.\n\n3.9 Summary\nThis chapter has explained the research methods that will be used in the study. It has outlined the research design, population, sampling methods, data collection techniques, and data analysis procedures."
        ],
        'references' => [
            'References',
            "International Labour Organization. (2022). World employment and social outlook: Trends 2022. ILO Publications.\n\nKenya National Bureau of Statistics. (2021). Economic survey 2021. Government Printer.\n\nNjeru, J. M. (2020). Small and medium enterprises and youth employment in Kenya. Journal of Business and Economic Development, 5(3), 112-120.\n\nOkafor, C. (2019). Entrepreneurship and youth unemployment in Africa. African Journal of Economic and Management Studies, 10(2), 45-59.\n\nUnited Nations. (2023). The sustainable development goals report 2023. United Nations Publications.\n\nWorld Bank. (2022). Small and medium enterprises (SMEs) finance. Retrieved from https://www.worldbank.org/en/topic/smefinance"
        ],
    ];
}

function _seed_chapters_for_user($pdo, $user_id) {
    $chapters = _get_default_chapters();
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO research_chapters (user_id, chapter_id, title, content) VALUES (?,?,?,?)");
    foreach ($chapters as $id => [$title, $content]) {
        $stmt->execute([$user_id, $id, $title, $content]);
    }
}

function _seed_budget_for_user($pdo, $user_id) {
    $items = [
        ['Printing and photocopying questionnaires', '80 copies', 800],
        ['Transport to study area', '10 trips', 3000],
        ['Stationery (pens, notebooks)', '1 set', 500],
        ['Internet and communication', '1 month', 1000],
        ['Report printing and binding', '3 copies', 1500],
        ['Miscellaneous expenses', '-', 700],
    ];
    $stmt = $pdo->prepare("INSERT INTO budget_items (user_id, item_name, quantity, cost) VALUES (?,?,?,?)");
    foreach ($items as $item) { $stmt->execute([$user_id, $item[0], $item[1], $item[2]]); }
}

function _seed_milestones_for_user($pdo, $user_id) {
    $milestones = [
        ['Proposal Registration', date('Y-m-d', strtotime('-5 days')), 'Completed'],
        ['Literature Review Draft', date('Y-m-d', strtotime('+7 days')), 'In Progress'],
        ['Field Data Collection', date('Y-m-d', strtotime('+20 days')), 'Pending'],
        ['Data Analysis & Interpretation', date('Y-m-d', strtotime('+35 days')), 'Pending'],
        ['Final Thesis Submission', date('Y-m-d', strtotime('+50 days')), 'Pending'],
    ];
    $stmt = $pdo->prepare("INSERT INTO milestones (user_id, title, due_date, status) VALUES (?,?,?,?)");
    foreach ($milestones as $m) {
        $stmt->execute([$user_id, $m[0], $m[1], $m[2]]);
    }
}

function _seed_all_student_data($pdo) {
    $configs = [
        'student' => [
            'title'      => 'THE ROLE OF SMALL BUSINESSES IN YOUTH EMPLOYMENT',
            'reg'        => 'CS/DIP/45102/2024',
            'institution'=> 'Kenya Institute of Management',
            'department' => 'Department of Business Studies',
            'degree'     => 'Diploma in Business Management',
            'community'  => 'Mombasa Community',
            'date'       => 'April 2026',
            'surveys'    => 51,
            'interviews' => 30,
            'approval'   => ['Dr. Jane Smith', 'PhD in Entrepreneurship & Business Development', 'Approved',
                             'Excellent research methodology and comprehensive data collection. Approved for examination.'],
        ],
        'alice' => [
            'title'      => 'IMPACT OF MICROFINANCE ON YOUTH ENTREPRENEURSHIP IN NAIROBI',
            'reg'        => 'CS/DIP/45203/2024',
            'institution'=> 'Kenya Institute of Management',
            'department' => 'Department of Finance',
            'degree'     => 'Diploma in Finance',
            'community'  => 'Nairobi CBD',
            'date'       => 'May 2026',
            'surveys'    => 53,
            'interviews' => 27,
            'approval'   => ['Dr. Jane Smith', 'PhD in Entrepreneurship', 'Pending', ''],
        ],
        'bob' => [
            'title'      => 'DIGITAL MARKETING STRATEGIES FOR SMALL ENTERPRISES IN MOMBASA',
            'reg'        => 'CS/DIP/45301/2024',
            'institution'=> 'Kenya Institute of Management',
            'department' => 'Department of Marketing',
            'degree'     => 'Diploma in Business Management',
            'community'  => 'Mombasa Town',
            'date'       => 'May 2026',
            'surveys'    => 49,
            'interviews' => 31,
            'approval'   => ['Prof. James Ochieng', 'PhD in Business Studies', 'Pending', ''],
        ],
        'carol' => [
            'title'      => 'CHALLENGES FACING WOMEN IN BUSINESS IN COASTAL KENYA',
            'reg'        => 'CS/DIP/45404/2024',
            'institution'=> 'Kenya Institute of Management',
            'department' => 'Department of Gender Studies',
            'degree'     => 'Diploma in Business Management',
            'community'  => 'Kilifi Community',
            'date'       => 'June 2026',
            'surveys'    => 46,
            'interviews' => 28,
            'approval'   => ['Dr. Mary Njoroge', 'PhD in Development Studies', 'Pending', ''],
        ],
        'david' => [
            'title'      => 'EFFECTS OF TAXATION ON SMALL BUSINESS GROWTH IN KENYA',
            'reg'        => 'CS/DIP/45505/2024',
            'institution'=> 'Kenya Institute of Management',
            'department' => 'Department of Business Studies',
            'degree'     => 'Diploma in Business Management',
            'community'  => 'Kisumu Town',
            'date'       => 'June 2026',
            'surveys'    => 55,
            'interviews' => 25,
            'approval'   => ['Prof. James Ochieng', 'PhD in Business Studies', 'Pending', ''],
        ],
    ];

    $students = $pdo->query("SELECT id, username, fullname FROM users WHERE role='student'")->fetchAll();

    $meta_stmt = $pdo->prepare("INSERT OR IGNORE INTO project_metadata
        (user_id, title, student_name, reg_number, institution, department, degree_type, town_community, submission_date)
        VALUES (?,?,?,?,?,?,?,?,?)");
    $appr_stmt = $pdo->prepare("INSERT OR IGNORE INTO supervisor_approval
        (user_id, supervisor_name, qualification, status, approval_date, feedback) VALUES (?,?,?,?,?,?)");

    foreach ($students as $student) {
        $uid   = $student['id'];
        $uname = $student['username'];
        $cfg   = $configs[$uname] ?? null;
        if (!$cfg) continue;

        $meta_stmt->execute([$uid, $cfg['title'], $student['fullname'], $cfg['reg'],
            $cfg['institution'], $cfg['department'], $cfg['degree'], $cfg['community'], $cfg['date']]);

        _seed_chapters_for_user($pdo, $uid);
        _seed_budget_for_user($pdo, $uid);
        _seed_milestones_for_user($pdo, $uid);
        seed_employee_surveys($pdo, $uid, $cfg['surveys']);
        seed_owner_interviews($pdo, $uid, $cfg['interviews']);

        $appr_date = ($cfg['approval'][2] === 'Approved') ? date('Y-m-d H:i:s', strtotime('-7 days')) : '';
        $appr_stmt->execute([$uid, $cfg['approval'][0], $cfg['approval'][1],
            $cfg['approval'][2], $appr_date, $cfg['approval'][3]]);
    }
}

// ---------------------------------------------------------------------------
// EMPLOYEE SURVEY SEEDER
// ---------------------------------------------------------------------------
function seed_employee_surveys($pdo, $user_id, $count = 50) {
    $male_first   = ['John','Joseph','Peter','David','James','Michael','Daniel','Paul','Kevin','Evans','Brian','Emmanuel','Samuel','Moses','Robert'];
    $female_first = ['Mary','Grace','Jane','Ann','Alice','Florence','Rose','Ruth','Beatrice','Emily','Mercy','Faith','Sharon','Caroline','Cynthia','Diana'];
    $last_names   = ['Kamau','Mwangi','Njoroge','Maina','Kariuki','Otieno','Ochieng','Onyango','Odhiambo','Omwamba','Wambui','Njeri','Nduta','Atieno','Adhiambo','Wanjiku','Awuor','Juma','Kibet','Kiprop','Kipkurui','Lagat','Mutua','Musyoka','Mwanza','Kilonzo'];

    $genders        = ['Male','Female','Prefer not to say'];  $gender_w  = [55,42,3];
    $ages           = ['18-20','21-24','25-29','30-35'];      $age_w     = [15,45,30,10];
    $educations     = ['Primary','Secondary','Certificate','Diploma','Degree']; $edu_w = [5,40,25,25,5];
    $businesses     = ['Retail shop','Salon/Barbershop','Restaurant/Food kiosk','Repair shop','Transport']; $biz_w = [40,25,20,10,5];
    $durations      = ['Less than 6 months','6 months-1 year','1-2 years','Over 2 years']; $dur_w = [25,35,25,15];
    $incomes        = ['Below KES 5,000','KES 5,000-10,000','KES 10,001-20,000','Above KES 20,000']; $inc_w = [20,50,25,5];
    $skills_opts    = ['Yes','No']; $skills_w = [85,15];
    $challenges_opts= ['Yes','No']; $chal_w   = [70,30];
    $rec_opts       = ['Yes','No']; $rec_w    = [80,20];

    $skills_pool = ['Customer service, sales techniques, and book keeping.',
        'Hair styling, salon management, and customer care.',
        'Food preparation, cooking skills, and hygienic operations.',
        'Electronic repairs, soldering, troubleshooting, and diagnosing hardware faults.',
        'Motorcycle riding safety, routing, and vehicle basic maintenance.',
        'Communication skills, patience, and direct negotiation with clients.',
        'Time management, stock control, and cash handling.'];

    $chal_pool = ['Long working hours with relatively low payment.',
        'Delay in monthly salary payment due to slow business days.',
        'Harassment from local municipal council licensing officials.',
        'Lack of formal contracts and job insecurity.',
        'High physical fatigue due to standing or manual work for long periods.',
        'Conflicts with challenging customers or employers.'];

    $rec_reasons = [
        'Yes' => ['It provides vital starting income and practical experience.',
            'You learn valuable entrepreneurship skills to start your own business.',
            'It keeps young people active and away from drug abuse or crimes.',
            'Easy to enter and offers highly flexible working environments.'],
        'No'  => ['The wages are too small to sustain standard cost of living.',
            'There is no clear career growth or long-term pension benefits.',
            'Usually characterized by extreme job insecurity.']
    ];

    $improvements_pool = [
        'Government should offer subventions or reduce taxes on youth-employing shops.',
        'Employers should provide regular training and better working terms.',
        'Establish cheap microfinance loans for employees to buy tools.',
        'Provide standardized minimum wages and standard shifts.',
        'Offer business mentorship to help employees transition to business owners.'];

    $job_pool = [
        'Retail shop'             => ['Sales Attendant','Cashier','Shop Attendant','Stock Controller'],
        'Salon/Barbershop'        => ['Hairstylist','Barber','Beautician','Nail Artist'],
        'Restaurant/Food kiosk'   => ['Chef','Line Cook','Waiter','Waitress','Dishwasher'],
        'Repair shop'             => ['Electronics Technician','Mechanic Apprentice','Repair Assistant'],
        'Transport'               => ['Boda Boda Rider','Delivery Courier','Assistant Loader'],
    ];

    $stmt = $pdo->prepare("INSERT INTO employee_surveys
        (user_id, fullname, gender, age, education, job_title, business_type, duration, income,
         skills_improved, skills_details, challenges, challenges_details, recommend, recommend_reason, improvements, submitted_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    for ($i = 0; $i < $count; $i++) {
        $gender   = get_weighted_value($genders, $gender_w);
        $first    = ($gender === 'Male') ? $male_first[array_rand($male_first)] : $female_first[array_rand($female_first)];
        $fullname = $first . ' ' . $last_names[array_rand($last_names)];
        $age      = get_weighted_value($ages, $age_w);
        $edu      = get_weighted_value($educations, $edu_w);
        $biz      = get_weighted_value($businesses, $biz_w);
        $dur      = get_weighted_value($durations, $dur_w);
        $inc      = get_weighted_value($incomes, $inc_w);
        $job      = $job_pool[$biz][array_rand($job_pool[$biz])];
        $skills   = get_weighted_value($skills_opts, $skills_w);
        $s_detail = ($skills === 'Yes') ? $skills_pool[array_rand($skills_pool)] : '';
        $chal     = get_weighted_value($challenges_opts, $chal_w);
        $c_detail = ($chal === 'Yes') ? $chal_pool[array_rand($chal_pool)] : '';
        $rec      = get_weighted_value($rec_opts, $rec_w);
        $r_reason = $rec_reasons[$rec][array_rand($rec_reasons[$rec])];
        $imp      = $improvements_pool[array_rand($improvements_pool)];
        $date     = date('Y-m-d H:i:s', strtotime('-' . rand(0, 180) . ' days'));

        $stmt->execute([$user_id, $fullname, $gender, $age, $edu, $job, $biz, $dur, $inc,
            $skills, $s_detail, $chal, $c_detail, $rec, $r_reason, $imp, $date]);
    }
}

// ---------------------------------------------------------------------------
// OWNER INTERVIEW SEEDER
// ---------------------------------------------------------------------------
function seed_owner_interviews($pdo, $user_id, $count = 30) {
    $male_first   = ['John','Joseph','Peter','David','James','Michael','Daniel','Paul','Kevin','Evans','Brian','Emmanuel','Samuel','Moses','Robert'];
    $female_first = ['Mary','Grace','Jane','Ann','Alice','Florence','Rose','Ruth','Beatrice','Emily','Mercy','Faith','Sharon','Caroline','Cynthia','Diana'];
    $last_names   = ['Kamau','Mwangi','Njoroge','Maina','Kariuki','Otieno','Ochieng','Onyango','Odhiambo','Omwamba','Wambui','Njeri','Nduta','Atieno','Adhiambo','Wanjiku','Awuor','Juma','Kibet','Kiprop','Kipkurui','Lagat','Mutua','Musyoka','Mwanza','Kilonzo'];

    $businesses = ['Retail shop','Salon/Barbershop','Restaurant/Food kiosk','Repair shop','Transport']; $biz_w = [35,25,25,10,5];
    $durations  = ['Less than 6 months','6 months-1 year','1-2 years','Over 2 years'];                 $dur_w = [5,20,40,35];

    $motivation_pool = [
        'Young people are energetic, hard-working, and fast learners.',
        'They are willing to work for affordable rates which aligns with our budget.',
        'To support the community by providing opportunities to unemployed local youth.',
        'Young employees adapt quickly to technology (like M-Pesa transactions and social media marketing).'];

    $roles_pool = ['Sales attendants, cashiers, stock takers.','Hairdresser, barber, beauty assistant.',
        'Chefs, kitchen helpers, waiters/waitresses.','Technicians, apprentices, repair assistants.',
        'Riders, dispatchers, loader helpers.'];

    $challenges_pool = ['High employee turnover - young people often leave quickly for other options.',
        'Lack of discipline, reporting late, or absenteeism without prior warnings.',
        'Insufficient technical experience, requiring expensive training time.',
        'Lack of capital to pay competitive wages or expand and hire more workers.',
        'High taxes, business licenses, and frequent economic downturns.'];

    $support_pool = ['Access to cheap business loans with low interest rates.',
        'Tax holidays or reductions for small businesses that employ youths.',
        'Free government-sponsored business management and technical training programs.',
        'Relaxing micro-enterprise licensing processes and reducing municipal fees.'];

    $training_pool = ['Yes, we provide informal on-the-job training on customer handling and sales daily.',
        'Yes, we pay for external brief seminars when new hair/beauty trends emerge.',
        'Yes, a senior technician guides the apprentice through diagnostics.',
        'No formal training, but we provide mentorship on discipline and saving habits.'];

    $advice_pool = ['Be patient, work hard, and focus on acquiring practical skills rather than just quick money.',
        'Always maintain high honesty, discipline, and keep time, as small businesses rely on trust.',
        'Treat the job as a learning platform to launch your own enterprise later.',
        'Be ready to perform diverse roles, which gives you valuable all-round business exposure.'];

    $biz_names = [
        'Retail shop'           => [' Boutique',' Groceries',' Retailers',' Wholesale Shop'],
        'Salon/Barbershop'      => [' Beauty Salon',' Barbers',' Cuts',' Executive Parlour'],
        'Restaurant/Food kiosk' => [' Fish Kiosk',' Express Diner',' Swahili Dishes',' Cafe & Grill'],
        'Repair shop'           => [' Tech Clinic',' Auto Repairs',' Electronics Fix',' Solutions'],
        'Transport'             => [' Riders',' Express Couriers',' Boda Hub',' Logistics'],
    ];

    $stmt = $pdo->prepare("INSERT INTO owner_interviews
        (user_id, owner_name, business_name, business_type, operation_duration, total_employees, youth_employees,
         motivation, roles, challenges, support_needed, training_programs, advice, submitted_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    for ($i = 0; $i < $count; $i++) {
        $biz    = get_weighted_value($businesses, $biz_w);
        $dur    = get_weighted_value($durations, $dur_w);
        $total  = rand(2, 8);
        $youth  = rand(1, $total);
        $gender = rand(0, 1) ? 'Male' : 'Female';
        $first  = ($gender === 'Male') ? $male_first[array_rand($male_first)] : $female_first[array_rand($female_first)];
        $owner  = $first . ' ' . $last_names[array_rand($last_names)];
        $bname  = $last_names[array_rand($last_names)] . $biz_names[$biz][array_rand($biz_names[$biz])];
        $date   = date('Y-m-d H:i:s', strtotime('-' . rand(0, 180) . ' days'));

        $stmt->execute([$user_id, $owner, $bname, $biz, $dur, $total, $youth,
            $motivation_pool[array_rand($motivation_pool)],
            $roles_pool[array_rand($roles_pool)],
            $challenges_pool[array_rand($challenges_pool)],
            $support_pool[array_rand($support_pool)],
            $training_pool[array_rand($training_pool)],
            $advice_pool[array_rand($advice_pool)],
            $date]);
    }
}

// ---------------------------------------------------------------------------
// UTILITY
// ---------------------------------------------------------------------------
function get_weighted_value($values, $weights) {
    $total  = array_sum($weights);
    $rand   = rand(1, $total);
    $cur    = 0;
    foreach ($weights as $i => $w) {
        $cur += $w;
        if ($rand <= $cur) return $values[$i];
    }
    return $values[array_rand($values)];
}
?>
