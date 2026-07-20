<?php
// api.php
// Backend JSON API gateway – Multi-user Research Project Management System (v2)

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/database.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$action         = isset($_GET['action']) ? $_GET['action'] : '';

// ---------------------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------------------
function json_respond($status, $data = [], $message = '') {
    echo json_encode(['status' => $status, 'data' => $data, 'message' => $message]);
    exit;
}

function require_auth($role = null) {
    if (!isset($_SESSION['user_id'])) {
        json_respond('error', [], 'Authentication required.');
    }
    if ($role && $_SESSION['role'] !== $role) {
        json_respond('error', [], 'Unauthorized access.');
    }
}

// Resolve the target student user_id for a request.
// Students can only access their own data.
// Supervisors can pass ?student_id=X to view any student's data.
function resolve_target_uid() {
    $session_uid = $_SESSION['user_id'] ?? null;
    if (!$session_uid) json_respond('error', [], 'Authentication required.');

    $role = $_SESSION['role'];

    if ($role === 'supervisor') {
        $sid = intval($_GET['student_id'] ?? 0);
        if ($sid > 0) return $sid;
        json_respond('error', [], 'student_id is required for supervisor requests.');
    }

    return $session_uid; // student always uses their own id
}

// Helper to create a notification
function notify($pdo, $user_id, $message, $type = 'info') {
    if (!$user_id) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?,?,?)");
        $stmt->execute([$user_id, $message, $type]);
    } catch (PDOException $e) {}
}

// ============================================================================
// GET ACTIONS
// ============================================================================
if ($request_method === 'GET') {
    switch ($action) {

        // ------------------------------------------------------------------
        case 'get_session':
            if (isset($_SESSION['user_id'])) {
                json_respond('success', [
                    'logged_in' => true,
                    'user_id'   => $_SESSION['user_id'],
                    'username'  => $_SESSION['username'],
                    'role'      => $_SESSION['role'],
                    'fullname'  => $_SESSION['fullname'],
                ]);
            } else {
                json_respond('success', ['logged_in' => false]);
            }
            break;

        // ------------------------------------------------------------------
        case 'get_profile':
            require_auth();
            try {
                $uid = $_SESSION['user_id'];
                $stmt = $pdo->prepare("SELECT email, phone, bio FROM users WHERE id = ?");
                $stmt->execute([$uid]);
                $profile = $stmt->fetch();
                json_respond('success', $profile ?: ['email'=>'','phone'=>'','bio'=>'']);
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_notifications':
            require_auth();
            try {
                $uid = $_SESSION['user_id'];
                $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
                $stmt->execute([$uid]);
                json_respond('success', $stmt->fetchAll());
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_metadata':
            require_auth();
            try {
                $uid  = resolve_target_uid();
                $stmt = $pdo->prepare("SELECT * FROM project_metadata WHERE user_id = ?");
                $stmt->execute([$uid]);
                $meta = $stmt->fetch();
                json_respond('success', $meta ?: (object)[]);
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_chapters':
            require_auth();
            try {
                $uid  = resolve_target_uid();
                $stmt = $pdo->prepare("SELECT * FROM research_chapters WHERE user_id = ? ORDER BY id");
                $stmt->execute([$uid]);
                json_respond('success', $stmt->fetchAll());
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_budget':
            require_auth();
            try {
                $uid  = resolve_target_uid();
                $stmt = $pdo->prepare("SELECT * FROM budget_items WHERE user_id = ?");
                $stmt->execute([$uid]);
                $items = $stmt->fetchAll();

                $tot = $pdo->prepare("SELECT SUM(cost) as total FROM budget_items WHERE user_id = ?");
                $tot->execute([$uid]);
                $total = $tot->fetch()['total'] ?? 0;

                json_respond('success', ['items' => $items, 'total' => floatval($total)]);
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_approval':
            require_auth();
            try {
                $uid  = resolve_target_uid();
                $stmt = $pdo->prepare("SELECT * FROM supervisor_approval WHERE user_id = ?");
                $stmt->execute([$uid]);
                json_respond('success', $stmt->fetch() ?: (object)[]);
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_responses':
            require_auth();
            try {
                $uid = resolve_target_uid();
                $emp = $pdo->prepare("SELECT * FROM employee_surveys WHERE user_id = ? ORDER BY id DESC");
                $emp->execute([$uid]);
                $own = $pdo->prepare("SELECT * FROM owner_interviews WHERE user_id = ? ORDER BY id DESC");
                $own->execute([$uid]);
                json_respond('success', ['employees' => $emp->fetchAll(), 'owners' => $own->fetchAll()]);
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_analytics':
            require_auth();
            try {
                $uid = resolve_target_uid();

                $cnt_emp = $pdo->prepare("SELECT COUNT(*) FROM employee_surveys WHERE user_id=?");
                $cnt_emp->execute([$uid]); $cnt_emp = $cnt_emp->fetchColumn();

                $cnt_own = $pdo->prepare("SELECT COUNT(*) FROM owner_interviews WHERE user_id=?");
                $cnt_own->execute([$uid]); $cnt_own = $cnt_own->fetchColumn();

                $tot_bud = $pdo->prepare("SELECT SUM(cost) FROM budget_items WHERE user_id=?");
                $tot_bud->execute([$uid]); $tot_bud = $tot_bud->fetchColumn() ?? 0;

                $q = function($sql, $p) use ($pdo) {
                    $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll();
                };

                $gender_data  = $q("SELECT gender, COUNT(*) as count FROM employee_surveys WHERE user_id=? GROUP BY gender",            [$uid]);
                $age_data     = $q("SELECT age, COUNT(*) as count FROM employee_surveys WHERE user_id=? GROUP BY age",                  [$uid]);
                $edu_data     = $q("SELECT education, COUNT(*) as count FROM employee_surveys WHERE user_id=? GROUP BY education",       [$uid]);
                $emp_biz_data = $q("SELECT business_type, COUNT(*) as count FROM employee_surveys WHERE user_id=? GROUP BY business_type", [$uid]);
                $income_data  = $q("SELECT income, COUNT(*) as count FROM employee_surveys WHERE user_id=? GROUP BY income",            [$uid]);
                $skills_data  = $q("SELECT skills_improved, COUNT(*) as count FROM employee_surveys WHERE user_id=? GROUP BY skills_improved", [$uid]);
                $chal_data    = $q("SELECT challenges, COUNT(*) as count FROM employee_surveys WHERE user_id=? GROUP BY challenges",    [$uid]);
                $rec_data     = $q("SELECT recommend, COUNT(*) as count FROM employee_surveys WHERE user_id=? GROUP BY recommend",      [$uid]);
                $own_biz_data = $q("SELECT business_type, COUNT(*) as count FROM owner_interviews WHERE user_id=? GROUP BY business_type", [$uid]);
                $dur_data     = $q("SELECT operation_duration, COUNT(*) as count FROM owner_interviews WHERE user_id=? GROUP BY operation_duration", [$uid]);

                $youth_stmt = $pdo->prepare("SELECT SUM(youth_employees) as youth, SUM(total_employees) as total FROM owner_interviews WHERE user_id=?");
                $youth_stmt->execute([$uid]);
                $yr = $youth_stmt->fetch();
                $youth_sum  = intval($yr['youth']  ?? 0);
                $total_sum  = intval($yr['total']  ?? 0);
                $youth_ratio = $total_sum > 0 ? round(($youth_sum / $total_sum) * 100, 1) : 0;

                $emp_comments = $pdo->prepare("SELECT skills_details, challenges_details, improvements FROM employee_surveys WHERE user_id=? AND (skills_details!='' OR challenges_details!='') LIMIT 10");
                $emp_comments->execute([$uid]);
                $own_comments = $pdo->prepare("SELECT challenges, support_needed, advice FROM owner_interviews WHERE user_id=? LIMIT 10");
                $own_comments->execute([$uid]);

                json_respond('success', [
                    'totals' => ['employees' => intval($cnt_emp), 'owners' => intval($cnt_own), 'budget' => floatval($tot_bud)],
                    'employee_charts' => [
                        'gender'        => $gender_data,
                        'age'           => $age_data,
                        'education'     => $edu_data,
                        'business_type' => $emp_biz_data,
                        'income'        => $income_data,
                        'skills'        => $skills_data,
                        'challenges'    => $chal_data,
                        'recommend'     => $rec_data,
                    ],
                    'owner_charts' => [
                        'business_type'  => $own_biz_data,
                        'duration'       => $dur_data,
                        'youth_percentage'=> $youth_ratio,
                        'youth_sum'      => $youth_sum,
                        'total_sum'      => $total_sum,
                    ],
                    'comments' => [
                        'employee' => $emp_comments->fetchAll(),
                        'owner'    => $own_comments->fetchAll(),
                    ],
                ]);
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_supervisor_dashboard':
            require_auth('supervisor');
            try {
                $supervisor_id = $_SESSION['user_id'];

                $total_students_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='student' AND supervisor_id = ?");
                $total_students_stmt->execute([$supervisor_id]);
                $total_students = $total_students_stmt->fetchColumn();

                $total_supervisors = $pdo->query("SELECT COUNT(*) FROM users WHERE role='supervisor'")->fetchColumn();

                $total_projects_stmt = $pdo->prepare("SELECT COUNT(*) FROM project_metadata pm JOIN users u ON u.id = pm.user_id WHERE u.supervisor_id = ?");
                $total_projects_stmt->execute([$supervisor_id]);
                $total_projects = $total_projects_stmt->fetchColumn();

                $total_surveys_stmt = $pdo->prepare("SELECT COUNT(*) FROM employee_surveys es JOIN users u ON u.id = es.user_id WHERE u.supervisor_id = ?");
                $total_surveys_stmt->execute([$supervisor_id]);
                $total_surveys = $total_surveys_stmt->fetchColumn();

                $total_interviews_stmt = $pdo->prepare("SELECT COUNT(*) FROM owner_interviews oi JOIN users u ON u.id = oi.user_id WHERE u.supervisor_id = ?");
                $total_interviews_stmt->execute([$supervisor_id]);
                $total_interviews = $total_interviews_stmt->fetchColumn();

                // Students list with stats (filtered by supervisor_id)
                $list_stmt = $pdo->prepare("
                    SELECT u.id, u.fullname, u.username, u.created_at,
                           pm.reg_number, pm.title,
                           (SELECT COUNT(*) FROM employee_surveys WHERE user_id = u.id) as surveys,
                           (SELECT COUNT(*) FROM owner_interviews  WHERE user_id = u.id) as interviews,
                           COALESCE(sa.status,'Pending') as approval_status,
                           COALESCE(sa.approval_date,'') as approval_date
                    FROM users u
                    LEFT JOIN project_metadata pm ON pm.user_id = u.id
                    LEFT JOIN supervisor_approval sa ON sa.user_id = u.id
                    WHERE u.role = 'student' AND u.supervisor_id = ?
                    ORDER BY u.id
                ");
                $list_stmt->execute([$supervisor_id]);
                $students_list = $list_stmt->fetchAll();

                // Calculate progress for each student
                foreach ($students_list as &$s) {
                    $prog = 0;
                    if (!empty($s['title']))     $prog += 20;
                    if ($s['surveys'] > 0)       $prog += 25;
                    if ($s['interviews'] > 0)    $prog += 25;
                    if ($s['approval_status'] === 'Approved') $prog += 30;
                    $s['progress'] = $prog;
                }
                unset($s);

                // Awaiting approval
                $awaiting = array_values(array_filter($students_list, fn($s) => $s['approval_status'] !== 'Approved'));

                // Recent activity (last 8 submissions from students assigned to this supervisor, showing respondent name)
                $recent_stmt = $pdo->prepare("
                    SELECT es.fullname as respondent_name, u.fullname as student_name, 'survey' as type, es.submitted_at
                    FROM employee_surveys es 
                    JOIN users u ON u.id = es.user_id
                    WHERE u.supervisor_id = ?
                    UNION ALL
                    SELECT oi.owner_name as respondent_name, u.fullname as student_name, 'interview' as type, oi.submitted_at
                    FROM owner_interviews oi 
                    JOIN users u ON u.id = oi.user_id
                    WHERE u.supervisor_id = ?
                    ORDER BY submitted_at DESC LIMIT 8
                ");
                $recent_stmt->execute([$supervisor_id, $supervisor_id]);
                $recent = $recent_stmt->fetchAll();

                json_respond('success', [
                    'totals' => [
                        'students'    => intval($total_students),
                        'supervisors' => intval($total_supervisors),
                        'projects'    => intval($total_projects),
                        'surveys'     => intval($total_surveys),
                        'interviews'  => intval($total_interviews),
                    ],
                    'students_list' => $students_list,
                    'awaiting_approval' => array_slice($awaiting, 0, 5),
                    'recent_activity'   => $recent,
                ]);
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_chapter_reviews':
            require_auth();
            try {
                $chapter_id     = trim($_GET['chapter_id']     ?? '');
                $student_uid    = intval($_GET['student_id']   ?? 0);
                if (!$student_uid || !$chapter_id) json_respond('error', [], 'chapter_id and student_id required.');
                $stmt = $pdo->prepare("
                    SELECT cr.*, u.fullname as supervisor_name
                    FROM chapter_reviews cr
                    JOIN users u ON u.id = cr.supervisor_user_id
                    WHERE cr.student_user_id = ? AND cr.chapter_id = ?
                    ORDER BY cr.created_at DESC
                ");
                $stmt->execute([$student_uid, $chapter_id]);
                json_respond('success', $stmt->fetchAll());
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_chat_contacts':
            require_auth();
            try {
                $uid = $_SESSION['user_id'];
                $role = $_SESSION['role'];
                if ($role === 'supervisor') {
                    $stmt = $pdo->prepare("SELECT id, fullname, username, role, (SELECT COUNT(*) FROM messages WHERE sender_id = users.id AND receiver_id = ? AND is_read = 0) as unread_count FROM users WHERE role='student' AND supervisor_id = ? ORDER BY fullname");
                    $stmt->execute([$uid, $uid]);
                } else {
                    $stmt = $pdo->prepare("SELECT id, fullname, username, role, (SELECT COUNT(*) FROM messages WHERE sender_id = users.id AND receiver_id = ? AND is_read = 0) as unread_count FROM users WHERE id = (SELECT supervisor_id FROM users WHERE id = ?)");
                    $stmt->execute([$uid, $uid]);
                }
                json_respond('success', $stmt->fetchAll());
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_messages':
            require_auth();
            $uid = $_SESSION['user_id'];
            $recipient_id = intval($_GET['recipient_id'] ?? 0);
            if ($recipient_id <= 0) json_respond('error', [], 'recipient_id is required.');
            try {
                $stmt = $pdo->prepare("
                    SELECT * FROM messages 
                    WHERE (sender_id = ? AND receiver_id = ?) 
                       OR (sender_id = ? AND receiver_id = ?)
                    ORDER BY created_at ASC
                ");
                $stmt->execute([$uid, $recipient_id, $recipient_id, $uid]);
                $messages = $stmt->fetchAll();

                $up = $pdo->prepare("UPDATE messages SET is_read=1 WHERE sender_id = ? AND receiver_id = ? AND is_read=0");
                $up->execute([$recipient_id, $uid]);

                json_respond('success', $messages);
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_milestones':
            require_auth();
            try {
                $uid = resolve_target_uid();
                $stmt = $pdo->prepare("SELECT * FROM milestones WHERE user_id = ? ORDER BY due_date ASC");
                $stmt->execute([$uid]);
                json_respond('success', $stmt->fetchAll());
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'get_admin_dashboard':
            require_auth('admin');
            try {
                $students = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
                $supervisors = $pdo->query("SELECT COUNT(*) FROM users WHERE role='supervisor'")->fetchColumn();
                $admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
                
                $esurveys = $pdo->query("SELECT COUNT(*) FROM employee_surveys")->fetchColumn();
                $ointerviews = $pdo->query("SELECT COUNT(*) FROM owner_interviews")->fetchColumn();
                $surveys = $esurveys + $ointerviews;

                $stmt = $pdo->query("SELECT id, username, fullname, email, phone, bio, status, created_at FROM users WHERE role='supervisor' ORDER BY id DESC");
                $supervisors_list = $stmt->fetchAll();

                json_respond('success', [
                    'totals' => [
                        'students' => intval($students),
                        'supervisors' => intval($supervisors),
                        'admins' => intval($admins),
                        'surveys' => intval($surveys)
                    ],
                    'supervisors_list' => $supervisors_list
                ]);
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        default:
            json_respond('error', [], 'Invalid GET action.');
    }
}

// ============================================================================
// POST ACTIONS
// ============================================================================
if ($request_method === 'POST') {
    $raw_input   = file_get_contents('php://input');
    $input       = json_decode($raw_input, true) ?? $_POST;
    $post_action = isset($_GET['action']) ? $_GET['action'] : ($input['action'] ?? '');

    switch ($post_action) {

        // ------------------------------------------------------------------
        case 'update_profile':
            require_auth();
            $uid   = intval($_SESSION['user_id']);
            $email = trim($input['email'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $bio   = trim($input['bio']   ?? '');
            try {
                $pdo->prepare("UPDATE users SET email=?, phone=?, bio=? WHERE id=?")
                    ->execute([$email, $phone, $bio, $uid]);
                json_respond('success', [], 'Profile updated successfully.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'mark_notifications_read':
            require_auth();
            $uid = intval($_SESSION['user_id']);
            try {
                $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
                json_respond('success', [], 'Notifications marked as read.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'login':
            $username = trim($input['username'] ?? '');
            $password = trim($input['password'] ?? '');
            if (empty($username) || empty($password)) {
                json_respond('error', [], 'Username and password are required.');
            }
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Check if deactivated
                    if (isset($user['status']) && $user['status'] === 'deactivated') {
                        json_respond('error', [], 'Your account has been deactivated. Please contact the administrator.');
                    }
                    $_SESSION['user_id']  = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role']     = $user['role'];
                    $_SESSION['fullname'] = $user['fullname'];
                    json_respond('success', [
                        'username' => $user['username'],
                        'role'     => $user['role'],
                        'fullname' => $user['fullname'],
                    ], 'Login successful.');
                } else {
                    json_respond('error', [], 'Invalid username or password.');
                }
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'register':
            $username = trim($input['username'] ?? '');
            $fullname = trim($input['fullname'] ?? '');
            $password = trim($input['password'] ?? '');
            $role     = trim($input['role']     ?? '');

            if (empty($username) || empty($fullname) || empty($password) || empty($role)) {
                json_respond('error', [], 'All fields are required.');
            }
            if ($role !== 'student' && $role !== 'supervisor') {
                json_respond('error', [], 'Invalid registration role.');
            }
            try {
                $dup = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $dup->execute([$username]);
                if ($dup->fetchColumn() > 0) {
                    json_respond('error', [], 'Username is already taken.');
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins  = $pdo->prepare("INSERT INTO users (username, password_hash, role, fullname) VALUES (?,?,?,?)");
                $ins->execute([$username, $hash, $role, $fullname]);
                $new_id = intval($pdo->lastInsertId());

                // Auto-scaffold project for students
                if ($role === 'student') {
                    create_student_scaffold($pdo, $new_id, $fullname);
                }

                json_respond('success', [], 'Registration successful. You can now log in.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'logout':
            session_unset();
            session_destroy();
            json_respond('success', [], 'Logged out successfully.');
            break;

        // ------------------------------------------------------------------
        case 'submit_employee':
            // Tag with session user_id (logged-in student) or public default (1)
            $survey_uid     = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;
            $gender         = trim($input['gender']             ?? '');
            $age            = trim($input['age']               ?? '');
            $education      = trim($input['education']         ?? '');
            $business_type  = trim($input['business_type']     ?? '');
            $duration       = trim($input['duration']          ?? '');
            $income         = trim($input['income']            ?? '');
            $skills_improved= trim($input['skills_improved']   ?? '');
            $skills_details = trim($input['skills_details']    ?? '');
            $challenges     = trim($input['challenges']        ?? '');
            $chal_details   = trim($input['challenges_details']?? '');
            $recommend      = trim($input['recommend']         ?? '');
            $rec_reason     = trim($input['recommend_reason']  ?? '');
            $improvements   = trim($input['improvements']      ?? '');
            $fullname_resp  = trim($input['fullname']          ?? 'Anonymous');

            if (empty($gender)||empty($age)||empty($education)||empty($business_type)||
                empty($duration)||empty($income)||empty($skills_improved)||empty($challenges)||empty($recommend)) {
                json_respond('error', [], 'Please complete all required fields.');
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO employee_surveys
                    (user_id, fullname, gender, age, education, business_type, duration, income,
                     skills_improved, skills_details, challenges, challenges_details, recommend, recommend_reason, improvements)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$survey_uid, $fullname_resp, $gender, $age, $education, $business_type,
                    $duration, $income, $skills_improved, $skills_details, $challenges, $chal_details,
                    $recommend, $rec_reason, $improvements]);
                
                $sup_id = $pdo->query("SELECT supervisor_id FROM users WHERE id=$survey_uid")->fetchColumn();
                notify($pdo, $sup_id, "{$fullname_resp} completed a survey for one of your students.", "survey");

                json_respond('success', [], 'Thank you! Your survey response has been submitted.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'submit_owner':
            $survey_uid        = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;
            $business_type     = trim($input['business_type']      ?? '');
            $operation_duration= trim($input['operation_duration'] ?? '');
            $total_employees   = intval($input['total_employees']  ?? 0);
            $youth_employees   = intval($input['youth_employees']  ?? 0);
            $motivation        = trim($input['motivation']         ?? '');
            $roles             = trim($input['roles']              ?? '');
            $challenges        = trim($input['challenges']         ?? '');
            $support_needed    = trim($input['support_needed']     ?? '');
            $training_programs = trim($input['training_programs']  ?? '');
            $advice            = trim($input['advice']             ?? '');
            $owner_name        = trim($input['owner_name']         ?? 'Anonymous');
            $business_name     = trim($input['business_name']      ?? 'N/A');

            if (empty($business_type)||empty($operation_duration)||$total_employees<=0||
                empty($motivation)||empty($roles)||empty($challenges)||empty($support_needed)||
                empty($training_programs)||empty($advice)) {
                json_respond('error', [], 'Please complete all required interview fields.');
            }
            if ($youth_employees < 0 || $youth_employees > $total_employees) {
                json_respond('error', [], 'Youth employees count must be between 0 and total employees.');
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO owner_interviews
                    (user_id, owner_name, business_name, business_type, operation_duration, total_employees, youth_employees,
                     motivation, roles, challenges, support_needed, training_programs, advice)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$survey_uid, $owner_name, $business_name, $business_type, $operation_duration,
                    $total_employees, $youth_employees, $motivation, $roles, $challenges,
                    $support_needed, $training_programs, $advice]);
                
                $sup_id = $pdo->query("SELECT supervisor_id FROM users WHERE id=$survey_uid")->fetchColumn();
                notify($pdo, $sup_id, "{$owner_name} submitted an interview for one of your students.", "interview");

                json_respond('success', [], 'Thank you! Your interview response has been recorded.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'update_metadata':
            require_auth('student');
            $uid            = intval($_SESSION['user_id']);
            $title          = trim($input['title']           ?? '');
            $student_name   = trim($input['student_name']    ?? '');
            $reg_number     = trim($input['reg_number']      ?? '');
            $institution    = trim($input['institution']     ?? '');
            $department     = trim($input['department']      ?? '');
            $degree_type    = trim($input['degree_type']     ?? '');
            $town_community = trim($input['town_community']  ?? '');
            $submission_date= trim($input['submission_date'] ?? '');

            if (empty($title)||empty($student_name)||empty($reg_number)||empty($institution)||
                empty($department)||empty($degree_type)||empty($town_community)||empty($submission_date)) {
                json_respond('error', [], 'All metadata fields are required.');
            }
            try {
                $stmt = $pdo->prepare("UPDATE project_metadata SET
                    title=?, student_name=?, reg_number=?, institution=?, department=?, degree_type=?, town_community=?, submission_date=?
                    WHERE user_id=?");
                $stmt->execute([$title,$student_name,$reg_number,$institution,$department,$degree_type,$town_community,$submission_date,$uid]);
                // Also sync fullname in users table
                $pdo->prepare("UPDATE users SET fullname=? WHERE id=?")->execute([$student_name, $uid]);
                json_respond('success', [], 'Research metadata updated successfully.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'update_chapter':
            require_auth('student');
            $uid        = intval($_SESSION['user_id']);
            $chapter_id = trim($input['chapter_id'] ?? '');
            $content    = $input['content'] ?? '';
            if (empty($chapter_id)) json_respond('error', [], 'Chapter ID is required.');
            try {
                $stmt = $pdo->prepare("UPDATE research_chapters SET content=? WHERE user_id=? AND chapter_id=?");
                $stmt->execute([$content, $uid, $chapter_id]);
                json_respond('success', [], 'Chapter saved successfully.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'add_budget':
            require_auth('student');
            $uid       = intval($_SESSION['user_id']);
            $item_name = trim($input['item_name'] ?? '');
            $quantity  = trim($input['quantity']  ?? '');
            $cost      = floatval($input['cost']  ?? 0);
            if (empty($item_name)||empty($quantity)||$cost < 0) json_respond('error', [], 'Valid item name, quantity, and cost are required.');
            try {
                $pdo->prepare("INSERT INTO budget_items (user_id, item_name, quantity, cost) VALUES (?,?,?,?)")
                    ->execute([$uid, $item_name, $quantity, $cost]);
                json_respond('success', [], 'Budget item added successfully.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'update_budget':
            require_auth('student');
            $uid       = intval($_SESSION['user_id']);
            $id        = intval($input['id']       ?? 0);
            $item_name = trim($input['item_name']  ?? '');
            $quantity  = trim($input['quantity']   ?? '');
            $cost      = floatval($input['cost']   ?? 0);
            if ($id<=0||empty($item_name)||empty($quantity)||$cost<0) json_respond('error', [], 'Invalid input parameters.');
            try {
                $pdo->prepare("UPDATE budget_items SET item_name=?, quantity=?, cost=? WHERE id=? AND user_id=?")
                    ->execute([$item_name, $quantity, $cost, $id, $uid]);
                json_respond('success', [], 'Budget item updated successfully.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'delete_budget':
            require_auth('student');
            $uid = intval($_SESSION['user_id']);
            $id  = intval($input['id'] ?? 0);
            if ($id<=0) json_respond('error', [], 'Invalid ID.');
            try {
                $pdo->prepare("DELETE FROM budget_items WHERE id=? AND user_id=?")->execute([$id, $uid]);
                json_respond('success', [], 'Budget item deleted.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'submit_approval':
            require_auth('supervisor');
            $sup_uid        = intval($_SESSION['user_id']);
            $student_uid    = intval($input['student_user_id'] ?? 0);
            $supervisor_name= trim($input['supervisor_name']   ?? '');
            $qualification  = trim($input['qualification']     ?? '');
            $status         = trim($input['status']            ?? 'Pending');
            $feedback       = trim($input['feedback']          ?? '');
            $date           = date('Y-m-d H:i:s');

            if (!$student_uid) json_respond('error', [], 'student_user_id is required.');
            if (empty($supervisor_name)||empty($qualification)) json_respond('error', [], 'Supervisor name and qualification are required.');

            // Verify the student exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id=? AND role='student'");
            $check->execute([$student_uid]);
            if (!$check->fetchColumn()) json_respond('error', [], 'Invalid student ID.');

            try {
                $pdo->prepare("UPDATE supervisor_approval SET supervisor_name=?, qualification=?, status=?, approval_date=?, feedback=? WHERE user_id=?")
                    ->execute([$supervisor_name, $qualification, $status, $date, $feedback, $student_uid]);
                
                notify($pdo, $student_uid, "Your project approval status was updated to: {$status}.", "approval");

                json_respond('success', [], 'Project review and approval status updated.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'submit_chapter_review':
            require_auth('supervisor');
            $sup_uid    = intval($_SESSION['user_id']);
            $student_uid= intval($input['student_user_id'] ?? 0);
            $chapter_id = trim($input['chapter_id']        ?? '');
            $comment    = trim($input['comment']           ?? '');
            $status     = trim($input['status']            ?? 'Reviewed');
            if (!$student_uid||empty($chapter_id)||empty($comment)) json_respond('error', [], 'student_user_id, chapter_id and comment are required.');
            try {
                $pdo->prepare("INSERT INTO chapter_reviews (student_user_id, chapter_id, supervisor_user_id, comment, status) VALUES (?,?,?,?,?)")
                    ->execute([$student_uid, $chapter_id, $sup_uid, $comment, $status]);
                
                notify($pdo, $student_uid, "Your supervisor added a review for Chapter: {$chapter_id}.", "review");

                json_respond('success', [], 'Review submitted successfully.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'seed_data':
            require_auth('student');
            $uid = intval($_SESSION['user_id']);
            try {
                $pdo->prepare("DELETE FROM employee_surveys WHERE user_id=?")->execute([$uid]);
                $pdo->prepare("DELETE FROM owner_interviews  WHERE user_id=?")->execute([$uid]);
                seed_employee_surveys($pdo, $uid, 51);
                seed_owner_interviews($pdo, $uid, 30);
                json_respond('success', [], 'Mock survey data re-seeded for your account!');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        // ------------------------------------------------------------------
        case 'clear_data':
            require_auth('student');
            $uid = intval($_SESSION['user_id']);
            try {
                $pdo->prepare("DELETE FROM employee_surveys WHERE user_id=?")->execute([$uid]);
                $pdo->prepare("DELETE FROM owner_interviews  WHERE user_id=?")->execute([$uid]);
                $pdo->prepare("DELETE FROM milestones        WHERE user_id=?")->execute([$uid]);
                $pdo->prepare("DELETE FROM messages          WHERE sender_id=? OR receiver_id=?")->execute([$uid, $uid]);
                _seed_milestones_for_user($pdo, $uid);
                json_respond('success', [], 'All your survey and interview responses cleared.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'send_message':
            require_auth();
            $uid = $_SESSION['user_id'];
            $recipient_id = intval($input['recipient_id'] ?? 0);
            $message = trim($input['message'] ?? '');
            if ($recipient_id <= 0 || empty($message)) {
                json_respond('error', [], 'recipient_id and message are required.');
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)");
                $stmt->execute([$uid, $recipient_id, $message]);
                
                notify($pdo, $recipient_id, "New message from " . $_SESSION['fullname'], "chat");
                json_respond('success', [], 'Message sent.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'add_milestone':
            require_auth('student');
            $uid = $_SESSION['user_id'];
            $title = trim($input['title'] ?? '');
            $due_date = trim($input['due_date'] ?? '');
            if (empty($title) || empty($due_date)) {
                json_respond('error', [], 'Milestone title and due date are required.');
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO milestones (user_id, title, due_date, status) VALUES (?,?,?, 'Pending')");
                $stmt->execute([$uid, $title, $due_date]);
                json_respond('success', ['id' => $pdo->lastInsertId()], 'Milestone created.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'update_milestone':
            require_auth('student');
            $uid = $_SESSION['user_id'];
            $id = intval($input['id'] ?? 0);
            $title = trim($input['title'] ?? '');
            $due_date = trim($input['due_date'] ?? '');
            $status = trim($input['status'] ?? 'Pending');
            if ($id <= 0 || empty($title) || empty($due_date)) {
                json_respond('error', [], 'Invalid input parameters.');
            }
            try {
                $stmt = $pdo->prepare("UPDATE milestones SET title=?, due_date=?, status=? WHERE id=? AND user_id=?");
                $stmt->execute([$title, $due_date, $status, $id, $uid]);
                json_respond('success', [], 'Milestone updated.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'delete_milestone':
            require_auth('student');
            $uid = $_SESSION['user_id'];
            $id = intval($input['id'] ?? 0);
            if ($id <= 0) json_respond('error', [], 'Invalid ID.');
            try {
                $stmt = $pdo->prepare("DELETE FROM milestones WHERE id=? AND user_id=?");
                $stmt->execute([$id, $uid]);
                json_respond('success', [], 'Milestone deleted.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'admin_create_supervisor':
            require_auth('admin');
            $username = trim($input['username'] ?? '');
            $fullname = trim($input['fullname'] ?? '');
            $password = trim($input['password'] ?? '');
            $email    = trim($input['email'] ?? '');
            $phone    = trim($input['phone'] ?? '');
            $bio      = trim($input['bio'] ?? '');

            if (empty($username) || empty($fullname) || empty($password)) {
                json_respond('error', [], 'Username, fullname, and password are required.');
            }
            try {
                $dup = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $dup->execute([$username]);
                if ($dup->fetchColumn() > 0) {
                    json_respond('error', [], 'Username is already taken.');
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins  = $pdo->prepare("INSERT INTO users (username, password_hash, role, fullname, email, phone, bio, status) VALUES (?,?,?,?,?,?,?, 'active')");
                $ins->execute([$username, $hash, 'supervisor', $fullname, $email, $phone, $bio]);
                json_respond('success', [], 'Supervisor created successfully.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'admin_update_supervisor':
            require_auth('admin');
            $id       = intval($input['id'] ?? 0);
            $username = trim($input['username'] ?? '');
            $fullname = trim($input['fullname'] ?? '');
            $password = trim($input['password'] ?? '');
            $email    = trim($input['email'] ?? '');
            $phone    = trim($input['phone'] ?? '');
            $bio      = trim($input['bio'] ?? '');

            if ($id <= 0 || empty($username) || empty($fullname)) {
                json_respond('error', [], 'ID, username, and fullname are required.');
            }
            try {
                $dup = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                $dup->execute([$username, $id]);
                if ($dup->fetchColumn() > 0) {
                    json_respond('error', [], 'Username is already taken by another account.');
                }
                
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $up   = $pdo->prepare("UPDATE users SET username=?, password_hash=?, fullname=?, email=?, phone=?, bio=? WHERE id=? AND role='supervisor'");
                    $up->execute([$username, $hash, $fullname, $email, $phone, $bio, $id]);
                } else {
                    $up   = $pdo->prepare("UPDATE users SET username=?, fullname=?, email=?, phone=?, bio=? WHERE id=? AND role='supervisor'");
                    $up->execute([$username, $fullname, $email, $phone, $bio, $id]);
                }
                json_respond('success', [], 'Supervisor updated successfully.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'admin_toggle_supervisor_status':
            require_auth('admin');
            $id     = intval($input['id'] ?? 0);
            $status = trim($input['status'] ?? '');
            if ($id <= 0 || !in_array($status, ['active', 'deactivated'])) {
                json_respond('error', [], 'Invalid input parameters.');
            }
            try {
                $up = $pdo->prepare("UPDATE users SET status=? WHERE id=? AND role='supervisor'");
                $up->execute([$status, $id]);
                json_respond('success', [], 'Supervisor status updated successfully.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        case 'admin_delete_supervisor':
            require_auth('admin');
            $id = intval($input['id'] ?? 0);
            if ($id <= 0) {
                json_respond('error', [], 'Invalid ID.');
            }
            try {
                // Dissociate assigned students
                $up = $pdo->prepare("UPDATE users SET supervisor_id = NULL WHERE supervisor_id = ?");
                $up->execute([$id]);

                // Delete supervisor
                $del = $pdo->prepare("DELETE FROM users WHERE id=? AND role='supervisor'");
                $del->execute([$id]);

                json_respond('success', [], 'Supervisor deleted successfully.');
            } catch (PDOException $e) { json_respond('error', [], $e->getMessage()); }
            break;

        // ------------------------------------------------------------------
        default:
            json_respond('error', [], 'Invalid POST action.');
    }
}
?>
