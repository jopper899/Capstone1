<?php
// ============================================================
//  Arandia College eLMS — Student Dashboard
//  File: student.php  |  Target: SHS & HS
// ============================================================
require_once __DIR__ . '/../../shared/middleware/student.php';
require_once __DIR__ . '/../../config/conn.php';

$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$user_id = $_SESSION['user_id'];

$uInfo = $conn->query("SELECT section_dept FROM users WHERE id = $user_id LIMIT 1")->fetch_assoc();
$section = $uInfo['section_dept'] ?? '';

$level = 'SHS / HS';
if (str_contains($section, 'Grade 11') || str_contains($section, 'Grade 12'))
    $level = 'Senior High School';
elseif (
    str_contains($section, 'Grade 7') || str_contains($section, 'Grade 8') ||
    str_contains($section, 'Grade 9') || str_contains($section, 'Grade 10')
)
    $level = 'High School';

// Enrolled Subjects 
$mySubjects = [];
$res = $conn->query(
    "SELECT c.id, c.course_code, c.course_name, c.description, c.school_year, c.semester,
            e.status AS enroll_status,
            CONCAT(u.last_name,', ',u.first_name) AS teacher_name
     FROM enrollments e
     JOIN courses c ON c.id = e.course_id
     LEFT JOIN teacher_assignments ta ON ta.course_id = c.id
     LEFT JOIN users u ON u.id = ta.teacher_id AND u.role = 'Teacher'
     WHERE e.student_id = $user_id
     GROUP BY c.id
     ORDER BY c.course_code"
);
while ($r = $res->fetch_assoc())
    $mySubjects[] = $r;

// Modules 
$myModules = [];
if (!empty($mySubjects)) {
    $cids = implode(',', array_map('intval', array_column($mySubjects, 'id')));
    $res = $conn->query(
        "SELECT m.id, m.course_id, m.title, m.description, m.file_path, m.week_number,
                DATE_FORMAT(m.created_at,'%b %d, %Y') AS posted_at,
                c.course_name, c.course_code
         FROM modules m
         JOIN courses c ON c.id = m.course_id
         WHERE m.course_id IN ($cids) AND m.published = 1
         ORDER BY m.course_id ASC, m.week_number ASC, m.created_at ASC"
    );
    while ($r = $res->fetch_assoc())
        $myModules[] = $r;
}

// Quizzes 
$myQuizzes = [];
if (!empty($mySubjects)) {
    $cids = implode(',', array_map('intval', array_column($mySubjects, 'id')));
    $res = $conn->query(
        "SELECT q.id, q.title, q.description, q.time_limit, q.max_score,
                q.open_at, q.close_at, q.quiz_type, q.file_path,
                c.course_name, c.course_code,
                (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id=q.id) AS question_count,
                (SELECT status FROM quiz_attempts
                 WHERE quiz_id=q.id AND student_id=$user_id
                 ORDER BY FIELD(status,'Graded','Submitted','In Progress') ASC, started_at DESC
                 LIMIT 1) AS attempt_status,
                (SELECT score FROM quiz_attempts
                 WHERE quiz_id=q.id AND student_id=$user_id
                 ORDER BY FIELD(status,'Graded','Submitted','In Progress') ASC, started_at DESC
                 LIMIT 1) AS my_score
         FROM quizzes q
         JOIN courses c ON c.id=q.course_id
         WHERE q.course_id IN ($cids)
         ORDER BY q.created_at DESC"
    );
    while ($r = $res->fetch_assoc())
        $myQuizzes[] = $r;
}

// Announcements 
$announcements = [];
$res = $conn->query(
    "SELECT a.id, a.title, a.body,
            DATE_FORMAT(a.posted_at,'%b %d, %Y %h:%i %p') AS posted_at,
            CONCAT(u.first_name,' ',u.last_name) AS author,
            u.role AS author_role,
            c.course_code, c.course_name
     FROM announcements a
     JOIN users u ON u.id = a.author_id
     LEFT JOIN courses c ON c.id = a.course_id
     WHERE (
         a.course_id IS NULL
         OR EXISTS (
             SELECT 1 FROM enrollments e
             WHERE e.student_id = $user_id
               AND e.course_id  = a.course_id
               AND e.status = 'Enrolled'
         )
     )
     ORDER BY a.posted_at DESC LIMIT 20"
);
while ($r = $res->fetch_assoc())
    $announcements[] = $r;

// Module Progress 
$myProgress = [];
if (!empty($myModules)) {
    $res = $conn->query(
        "SELECT module_id, DATE_FORMAT(completed_at,'%b %d, %Y') AS completed_at
         FROM module_progress WHERE student_id = $user_id"
    );
    while ($r = $res->fetch_assoc())
        $myProgress[$r['module_id']] = $r['completed_at'];
}

// Stats 
$statSubjects = count($mySubjects);
$statModules = count($myModules);
$statModulesDone = count($myProgress);
$statQuizDue = count(array_filter(
    $myQuizzes,
    fn($q) => $q['attempt_status'] !== 'Submitted' &&
    $q['attempt_status'] !== 'Graded' &&
    (!$q['close_at'] || strtotime($q['close_at']) > time())
));
$statDone = count(array_filter(
    $myQuizzes,
    fn($q) => $q['attempt_status'] === 'Submitted' || $q['attempt_status'] === 'Graded'
));

// Group data
$modulesByCourse = [];
foreach ($myModules as $m)
    $modulesByCourse[$m['course_code']][] = $m;

$quizzesByCourse = [];
foreach ($myQuizzes as $q)
    $quizzesByCourse[$q['course_code']][] = $q;

$gradesByCourse = [];
if (!empty($mySubjects)) {
    foreach ($mySubjects as $subj) {
        $cid = (int) $subj['id'];
        $code = $subj['course_code'];
        if (!isset($gradesByCourse[$code])) {
            $gradesByCourse[$code] = [
                'course_name' => $subj['course_name'],
                'school_year' => $subj['school_year'],
                'semester' => $subj['semester'],
                'teacher' => $subj['teacher_name'] ?? '—',
                'quiz_scores' => [],
                'assign_scores' => [],
            ];
        }
        $qRes = $conn->query(
            "SELECT q.title, qa.score, q.max_score, qa.status, q.quiz_type
             FROM quiz_attempts qa
             JOIN quizzes q ON q.id = qa.quiz_id
             WHERE qa.student_id = $user_id
               AND q.course_id   = $cid
               AND qa.status IN ('Submitted','Graded')
             ORDER BY qa.started_at ASC"
        );
        if ($qRes) {
            while ($r = $qRes->fetch_assoc())
                $gradesByCourse[$code]['quiz_scores'][] = $r;
        }
        $aRes = $conn->query(
            "SELECT g.raw_score AS score, g.max_score, g.remarks,
                    DATE_FORMAT(g.recorded_at,'%b %d, %Y') AS graded_at,
                    COALESCE(a.title, g.item_name, 'Grade Entry') AS title
             FROM grades g
             LEFT JOIN assignments a ON a.id = g.item_id
             WHERE g.student_id = $user_id AND g.course_id = $cid
             ORDER BY g.recorded_at ASC"
        );
        if ($aRes) {
            while ($r = $aRes->fetch_assoc())
                $gradesByCourse[$code]['assign_scores'][] = $r;
        }
    }
}

// Visual gradients for cards
$thumbGrads = [
    'linear-gradient(135deg,#003087,#0056d6)',
    'linear-gradient(135deg,#00875a,#00c97f)',
    'linear-gradient(135deg,#b38600,#ffd000)',
    'linear-gradient(135deg,#7b2d8b,#c850c0)',
    'linear-gradient(135deg,#c0392b,#e74c3c)',
    'linear-gradient(135deg,#0097a7,#26c6da)',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <base href="../../">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard — Arandia College eLMS</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #003087;
            --primary-light: #eef2ff;
            --primary-hover: #002466;
            --accent: #FFD700;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-body: #f1f5f9;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* SVG Icons Utility */
        .icon {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .icon-sm {
            width: 16px;
            height: 16px;
        }

        .icon-lg {
            width: 32px;
            height: 32px;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 50;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-box {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-family: 'Nunito', sans-serif;
        }

        .brand-text h1 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1.2;
        }

        .brand-text span {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .user-profile {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--primary-light);
            margin: 1rem;
            border-radius: var(--radius);
        }

        .avatar {
            width: 36px;
            height: 36px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .user-info div:first-child {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-main);
        }

        .user-info div:last-child {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .nav-menu {
            flex: 1;
            padding: 1rem 0.5rem;
            overflow-y: auto;
        }

        .nav-group-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            padding: 0.75rem 1rem 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .nav-link:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .nav-link.active {
            background: var(--primary);
            color: white;
            box-shadow: var(--shadow);
        }

        .nav-link.danger:hover {
            background: #fee2e2;
            color: var(--danger);
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid var(--border);
        }

        /* Main Content */
        .app-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            height: 64px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .breadcrumb {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .breadcrumb strong {
            color: var(--text-main);
            font-weight: 600;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .btn-icon {
            background: none;
            border: 1px solid var(--border);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-icon:hover {
            background: var(--bg-body);
            color: var(--primary);
            border-color: var(--primary);
        }

        /* User Avatar Button Style */
        .user-btn {
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid transparent;
            font-weight: 700;
        }

        .user-btn:hover {
            background: #e0e7ff;
            border-color: var(--primary);
        }

        .badge-dot {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 10px;
            height: 10px;
            background: var(--danger);
            border-radius: 50%;
            border: 2px solid white;
        }

        .main-scroll {
            padding: 2rem;
            overflow-y: auto;
            flex: 1;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-family: 'Nunito', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .page-sub {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Cards & Grid */
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary), #0044cc);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text h2 {
            font-family: 'Nunito', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-light);
            color: var(--primary);
        }

        .stat-info h3 {
            font-family: 'Nunito', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .stat-info p {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Components */
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
        }

        .badge-blue {
            background: var(--primary-light);
            color: var(--primary);
        }

        .badge-green {
            background: #d1fae5;
            color: #059669;
        }

        .badge-yellow {
            background: #fef3c7;
            color: #d97706;
        }

        .badge-red {
            background: #fee2e2;
            color: #dc2626;
        }

        .badge-gray {
            background: #f1f5f9;
            color: #64748b;
        }

        .list-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th {
            text-align: left;
            padding: 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
            background: #f8fafc;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
            vertical-align: middle;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
        }

        .btn-outline:hover {
            background: var(--bg-body);
        }

        .btn-sm {
            padding: 0.35rem 0.85rem;
            font-size: 0.8rem;
        }

        /* Accordions */
        .accordion {
            background: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .acc-head {
            padding: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            background: white;
            transition: 0.2s;
        }

        .acc-head:hover {
            background: #f8fafc;
        }

        .acc-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .acc-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .acc-title {
            font-weight: 700;
            color: var(--text-main);
        }

        .acc-meta {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .acc-body {
            display: none;
            border-top: 1px solid var(--border);
            background: #fafafa;
        }

        .acc-body.open {
            display: block;
        }

        .acc-row {
            display: grid;
            grid-template-columns: 1fr 80px 100px 120px;
            gap: 1rem;
            padding: 1rem 1.25rem;
            align-items: center;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }

        .acc-row:last-child {
            border-bottom: none;
        }

        /* Inputs */
        .form-control {
            padding: 0.6rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        /* Modals */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }

        .modal {
            background: white;
            width: 100%;
            max-width: 600px;
            border-radius: var(--radius);
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 1.25rem;
            background: #f8fafc;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Utilities */
        .hidden {
            display: none !important;
        }

        .section-panel {
            display: none;
        }

        .section-panel.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* Responsive */
        @media(max-width: 768px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .nav-menu {
                display: none;
            }

            /* Simplified mobile view for demo */
            .nav-menu.mobile-open {
                display: block;
            }

            .grid-4,
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .acc-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .acc-row>*:not(:first-child) {
                text-align: left;
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-box">AC</div>
            <div class="brand-text">
                <h1>Arandia College</h1>
                <span>eLMS Student Portal</span>
            </div>
        </div>

        <div class="user-profile">
            <div class="avatar"><?= strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) ?></div>
            <div class="user-info">
                <div><?= htmlspecialchars($first_name . ' ' . $last_name) ?></div>
                <div><?= htmlspecialchars($level) ?> <?= $section ? '· ' . htmlspecialchars($section) : '' ?></div>
            </div>
        </div>

        <nav class="nav-menu">
            <div class="nav-group-label">Main Menu</div>
            <button class="nav-link active" onclick="showPanel('dashboard',this)">
                <svg class="icon" viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Dashboard
            </button>

            <div class="nav-group-label">Learning</div>
            <button class="nav-link" onclick="showPanel('courses',this)">
                <svg class="icon" viewBox="0 0 24 24">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                My Subjects
            </button>
            <button class="nav-link" onclick="showPanel('modules',this)">
                <svg class="icon" viewBox="0 0 24 24">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
                Modules
            </button>
            <button class="nav-link" onclick="showPanel('quizzes',this)">
                <svg class="icon" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                Quizzes
            </button>

            <div class="nav-group-label">Academic</div>
            <button class="nav-link" onclick="showPanel('announcements',this)">
                <svg class="icon" viewBox="0 0 24 24">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                Announcements
            </button>
            <button class="nav-link" onclick="showPanel('grades',this)">
                <svg class="icon" viewBox="0 0 24 24">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                My Grades
            </button>

            <div class="nav-group-label">Account</div>
            <button class="nav-link" onclick="showPanel('profile',this)">
                <svg class="icon" viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                My Profile
            </button>
        </nav>

        <div class="sidebar-footer">
            <!-- Keeping Sign Out here too for redundancy, but main access is now in Topbar -->
            <a href="logout.php" class="nav-link danger" style="justify-content: center;">
                <svg class="icon" viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Sign Out
            </a>
        </div>
    </aside>

    <!-- MAIN APP -->
    <div class="app-body">
        <header class="topbar">
            <div class="breadcrumb">Student / <strong id="topbar-title">Dashboard</strong></div>

            <div class="topbar-actions" id="topbarArea">
                <!-- Notification Bell -->
                <button class="btn-icon" onclick="toggleNotif(event)" title="Notifications">
                    <svg class="icon" viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php if (!empty($announcements)): ?><span class="badge-dot" id="notifDot"></span><?php endif; ?>
                </button>

                <!-- User Avatar Button (New) -->
                <button class="btn-icon user-btn" onclick="toggleUserMenu(event)" title="My Account">
                    <?= strtoupper(substr($first_name, 0, 1)) ?>
                </button>
            </div>
        </header>

        <!-- Notification Dropdown -->
        <div id="notifDropdown" class="modal-overlay"
            style="position:fixed; top:64px; right:2rem; width:320px; height:auto; max-height:400px; z-index:100; display:none;"
            onclick="event.stopPropagation()">
            <div class="card" style="padding:0; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                <div style="padding:1rem; border-bottom:1px solid var(--border); font-weight:700;">Notifications</div>
                <div style="max-height:250px; overflow-y:auto;">
                    <?php if (empty($announcements)): ?>
                        <div style="padding:1.5rem; text-align:center; color:var(--text-muted); font-size:0.9rem;">No new
                            updates.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($announcements, 0, 5) as $a): ?>
                            <div style="padding:1rem; border-bottom:1px solid #f1f5f9; cursor:pointer;"
                                onclick="goToAnnouncement(<?= $a['id'] ?>)">
                                <div style="font-weight:600; font-size:0.9rem; margin-bottom:0.25rem;">
                                    <?= htmlspecialchars($a['title']) ?></div>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><?= $a['author'] ?> ·
                                    <?= $a['posted_at'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div style="padding:0.75rem; text-align:center; border-top:1px solid #f1f5f9;">
                    <button onclick="closeNotifAndGoToPanel()" class="btn btn-outline btn-sm" style="width:100%">View
                        All Announcements</button>
                </div>
            </div>
        </div>

        <!-- User Menu Dropdown (New) -->
        <div id="userDropdown" class="modal-overlay"
            style="position:fixed; top:64px; right:2rem; width:220px; height:auto; z-index:100; display:none;"
            onclick="event.stopPropagation()">
            <div class="card" style="padding:0; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                <div style="padding:1rem; border-bottom:1px solid var(--border);">
                    <div style="font-weight:700; color:var(--text-main);">
                        <?= htmlspecialchars($first_name . ' ' . $last_name) ?></div>
                    <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($level) ?></div>
                </div>
                <div style="padding:0.5rem 0;">
                    <button
                        onclick="showPanel('profile', document.querySelectorAll('.nav-link')[7]); toggleUserMenu(event);"
                        class="btn" style="width:100%; justify-content:flex-start; border-radius:0;">
                        <svg class="icon" viewBox="0 0 24 24" style="margin-right:0.5rem">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        My Profile
                    </button>
                    <div style="border-top:1px solid var(--border); margin:0.25rem 1rem;"></div>
                    <button onclick="window.location.href='logout.php'" class="btn"
                        style="width:100%; justify-content:flex-start; border-radius:0; color:var(--danger);">
                        <svg class="icon" viewBox="0 0 24 24" style="margin-right:0.5rem">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </button>
                </div>
            </div>
        </div>

        <main class="main-scroll">

            <!-- DASHBOARD PANEL -->
            <div id="panel-dashboard" class="section-panel active">
                <div class="page-header">
                    <h2 class="page-title">Welcome back, <?= htmlspecialchars($first_name) ?>!</h2>
                    <p class="page-sub">Here is an overview of your academic progress.</p>
                </div>

                <div class="welcome-card">
                    <div class="welcome-text">
                        <h2>Ready to learn?</h2>
                        <p>You have <?= $statQuizDue ?> quizzes due this week. Keep up the good work!</p>
                        <div style="display:flex; gap:0.5rem;">
                            <span class="badge"
                                style="background:rgba(255,255,255,0.2); color:white;"><?= htmlspecialchars($level) ?></span>
                            <?php if ($section): ?><span class="badge"
                                    style="background:rgba(255,255,255,0.2); color:white;"><?= htmlspecialchars($section) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <svg style="width:100px; height:100px; opacity:0.8; color:white;" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10v6"></path>
                        <path d="M20 20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12z"></path>
                        <path d="M10 4h4"></path>
                        <path d="M8 4v4"></path>
                        <path d="M16 4v4"></path>
                    </svg>
                </div>

                <div class="grid-4">
                    <div class="card stat-card">
                        <div class="stat-icon-wrap"><svg class="icon" viewBox="0 0 24 24">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg></div>
                        <div class="stat-info">
                            <h3><?= $statSubjects ?></h3>
                            <p>Subjects</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon-wrap" style="color:#059669; background:#d1fae5;"><svg class="icon"
                                viewBox="0 0 24 24">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg></div>
                        <div class="stat-info">
                            <h3><?= $statModulesDone ?>/<?= $statModules ?></h3>
                            <p>Modules Done</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon-wrap" style="color:#d97706; background:#fef3c7;"><svg class="icon"
                                viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg></div>
                        <div class="stat-info">
                            <h3><?= $statQuizDue ?></h3>
                            <p>Pending Quizzes</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon-wrap" style="color:#7c3aed; background:#ede9fe;"><svg class="icon"
                                viewBox="0 0 24 24">
                                <path
                                    d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3">
                                </path>
                            </svg></div>
                        <div class="stat-info">
                            <h3><?= $statDone ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card">
                        <div
                            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                            <h3 style="font-size:1.1rem; font-weight:700;">Recent Subjects</h3>
                            <button onclick="showPanel('courses',document.querySelectorAll('.nav-link')[1])"
                                style="border:none; background:none; color:var(--primary); font-weight:600; cursor:pointer;">View
                                All</button>
                        </div>
                        <?php if (empty($mySubjects)): ?>
                            <p style="color:var(--text-muted);">No enrolled subjects.</p>
                        <?php else:
                            foreach (array_slice($mySubjects, 0, 3) as $s): ?>
                                <div class="list-item">
                                    <div
                                        style="background:var(--primary-light); padding:8px; border-radius:8px; color:var(--primary);">
                                        <svg class="icon" viewBox="0 0 24 24">
                                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                        </svg>
                                    </div>
                                    <div style="flex:1;">
                                        <div style="font-weight:600;"><?= htmlspecialchars($s['course_name']) ?></div>
                                        <div style="font-size:0.8rem; color:var(--text-muted);">
                                            <?= htmlspecialchars($s['course_code']) ?></div>
                                    </div>
                                    <span class="badge badge-green">Enrolled</span>
                                </div>
                            <?php endforeach; endif; ?>
                    </div>

                    <div class="card">
                        <div
                            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                            <h3 style="font-size:1.1rem; font-weight:700;">Updates</h3>
                            <button onclick="closeNotifAndGoToPanel()"
                                style="border:none; background:none; color:var(--primary); font-weight:600; cursor:pointer;">View
                                All</button>
                        </div>
                        <?php if (empty($announcements)): ?>
                            <p style="color:var(--text-muted);">No announcements.</p>
                        <?php else:
                            foreach (array_slice($announcements, 0, 3) as $a): ?>
                                <div class="list-item" onclick="goToAnnouncement(<?= $a['id'] ?>)" style="cursor:pointer;">
                                    <div style="width:8px; height:8px; background:var(--primary); border-radius:50%;"></div>
                                    <div style="flex:1;">
                                        <div style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($a['title']) ?>
                                        </div>
                                        <div style="font-size:0.75rem; color:var(--text-muted);"><?= $a['author'] ?> ·
                                            <?= $a['posted_at'] ?></div>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- SUBJECTS PANEL -->
            <div id="panel-courses" class="section-panel">
                <div class="page-header">
                    <h2 class="page-title">My Subjects</h2>
                    <p class="page-sub">Manage your enrolled courses and schedules.</p>
                </div>
                <div class="card">
                    <div
                        style="display:flex; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
                        <h3 style="font-weight:700;">Enrolled Courses (<?= count($mySubjects) ?>)</h3>
                        <input type="text" class="form-control" placeholder="Search subjects..."
                            oninput="filterTbl('subj-tbody', this.value)" style="width:250px;">
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Code</th>
                                    <th>Teacher</th>
                                    <th>School Year</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="subj-tbody">
                                <?php foreach ($mySubjects as $s): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;"><?= htmlspecialchars($s['course_name']) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);">
                                                <?= htmlspecialchars($s['description']) ?></div>
                                        </td>
                                        <td><span class="badge badge-blue"><?= htmlspecialchars($s['course_code']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($s['teacher_name'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($s['school_year']) ?></td>
                                        <td><span
                                                class="badge badge-green"><?= htmlspecialchars($s['enroll_status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- MODULES PANEL -->
            <div id="panel-modules" class="section-panel">
                <div class="page-header">
                    <h2 class="page-title">Modules</h2>
                    <p class="page-sub">Access learning materials and resources.</p>
                </div>

                <div style="margin-bottom:1.5rem; display:flex; gap:1rem; flex-wrap:wrap;">
                    <input type="text" id="mod-search" class="form-control" placeholder="Search modules..."
                        oninput="filterMod()" style="flex:1;">
                    <select id="mod-filter-course" class="form-control" onchange="filterMod()">
                        <option value="">All Subjects</option>
                        <?php foreach ($mySubjects as $s): ?>
                            <option value="<?= htmlspecialchars($s['course_code']) ?>">
                                <?= htmlspecialchars($s['course_code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php
                $si = 0;
                foreach ($modulesByCourse as $courseCode => $mods):
                    $cd = count(array_filter($mods, fn($m) => isset($myProgress[$m['id']])));
                    $ct = count($mods);
                    $cp = $ct > 0 ? round($cd / $ct * 100) : 0;
                    $grad = $thumbGrads[$si % count($thumbGrads)];
                    $bid = 'mb' . $si;
                    $si++;
                    ?>
                    <div class="accordion" data-code="<?= htmlspecialchars($courseCode) ?>" data-total="<?= $ct ?>"
                        data-done="<?= $cd ?>">
                        <div class="acc-head" onclick="toggleBlock('<?= $bid ?>')">
                            <div class="acc-left">
                                <div class="acc-icon" style="background:<?= $grad ?>">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="acc-title"><?= htmlspecialchars($mods[0]['course_name']) ?></div>
                                    <div class="acc-meta"><span class="mod-count"><?= $cd ?></span>/<?= $ct ?> Completed
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <span class="mod-pct" style="font-weight:700; color:var(--primary);"><?= $cp ?>%</span>
                                <svg class="icon" style="transition:0.3s;" id="chev-<?= $bid ?>" viewBox="0 0 24 24">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="acc-body" id="<?= $bid ?>">
                            <?php foreach ($mods as $m):
                                $isDone = isset($myProgress[$m['id']]);
                                $mId = (int) $m['id'];
                                $mCid = (int) $m['course_id'];
                                ?>
                                <div class="acc-row" data-id="<?= $m['id'] ?>"
                                    data-title="<?= strtolower(htmlspecialchars($m['title'])) ?>"
                                    data-status="<?= $isDone ? 'done' : 'pending' ?>">
                                    <div style="display:flex; align-items:center; gap:0.75rem;">
                                        <div class="status-icon"
                                            style="width:24px; height:24px; border-radius:50%; background:<?= $isDone ? '#d1fae5' : '#e2e8f0' ?>; display:flex; align-items:center; justify-content:center; color:<?= $isDone ? '#059669' : '#94a3b8' ?>">
                                            <?php if ($isDone): ?><svg class="icon icon-sm" viewBox="0 0 24 24">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg><?php else: ?><svg class="icon icon-sm" viewBox="0 0 24 24">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                </svg><?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;"><?= htmlspecialchars($m['title']) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);">Week
                                                <?= $m['week_number'] ?> · <?= $m['posted_at'] ?></div>
                                        </div>
                                    </div>
                                    <div style="text-align:center;">
                                        <span
                                            class="status-badge badge <?= $isDone ? 'badge-green' : 'badge-gray' ?>"><?= $isDone ? 'Done' : 'Pending' ?></span>
                                    </div>
                                    <div style="text-align:right;">
                                        <?php if ($m['file_path']): ?>
                                            <a href="<?= htmlspecialchars($m['file_path']) ?>" target="_blank"
                                                class="btn btn-primary btn-sm" onclick="autoMarkDone(<?= $mId ?>,<?= $mCid ?>)">
                                                <span class="btn-text"><?= $isDone ? 'View' : 'Open' ?></span>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline btn-sm" disabled> No File</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- QUIZZES PANEL -->
            <div id="panel-quizzes" class="section-panel">
                <div class="page-header">
                    <h2 class="page-title">Quizzes</h2>
                    <p class="page-sub">Test your knowledge and submit requirements.</p>
                </div>

                <div style="margin-bottom:1.5rem; display:flex; gap:1rem; flex-wrap:wrap;">
                    <input type="text" id="quiz-search" class="form-control" placeholder="Search quizzes..."
                        oninput="filterQuiz()" style="flex:1;">
                    <select id="quiz-filter-course" class="form-control" onchange="filterQuiz()">
                        <option value="">All Subjects</option>
                        <?php foreach ($mySubjects as $s): ?>
                            <option value="<?= htmlspecialchars($s['course_code']) ?>">
                                <?= htmlspecialchars($s['course_code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php $qi = 0;
                foreach ($quizzesByCourse as $courseCode => $quizzes):
                    $qcd = count(array_filter($quizzes, fn($q) => $q['attempt_status'] === 'Submitted' || $q['attempt_status'] === 'Graded'));
                    $qct = count($quizzes);
                    $qcp = $qct > 0 ? round($qcd / $qct * 100) : 0;
                    $grad = $thumbGrads[$qi % count($thumbGrads)];
                    $qbid = 'qb' . $qi;
                    $qi++;
                    ?>
                    <div class="accordion" data-code="<?= htmlspecialchars($courseCode) ?>" data-total="<?= $qct ?>"
                        data-done="<?= $qcd ?>">
                        <div class="acc-head" onclick="toggleBlock('<?= $qbid ?>')">
                            <div class="acc-left">
                                <div class="acc-icon" style="background:<?= $grad ?>">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                    </svg>
                                </div>
                                <div>
                                    <div class="acc-title"><?= htmlspecialchars($quizzes[0]['course_name']) ?></div>
                                    <div class="acc-meta"><span class="quiz-count"><?= $qcd ?></span>/<?= $qct ?> Submitted
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <span class="quiz-pct" style="font-weight:700; color:var(--primary);"><?= $qcp ?>%</span>
                                <svg class="icon" style="transition:0.3s;" id="chev-<?= $qbid ?>" viewBox="0 0 24 24">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="acc-body" id="<?= $qbid ?>">
                            <?php foreach ($quizzes as $q):
                                $done = $q['attempt_status'] === 'Submitted' || $q['attempt_status'] === 'Graded';
                                $inProg = $q['attempt_status'] === 'In Progress';
                                $sp = ($done && $q['max_score'] > 0 && $q['my_score'] !== null) ? round(($q['my_score'] / $q['max_score']) * 100) : null;
                                $attemptStatus = $q['attempt_status'] ?? 'Not Started';
                                $isFile = ($q['quiz_type'] ?? 'questions') === 'file';
                                ?>
                                <div class="acc-row" data-id="<?= $q['id'] ?>"
                                    data-title="<?= strtolower(htmlspecialchars($q['title'])) ?>"
                                    data-status="<?= htmlspecialchars($attemptStatus) ?>">
                                    <div style="display:flex; align-items:center; gap:0.75rem;">
                                        <div class="quiz-icon-wrap"
                                            style="width:24px; height:24px; border-radius:50%; background:<?= $done ? '#d1fae5' : '#e2e8f0' ?>; display:flex; align-items:center; justify-content:center; color:<?= $done ? '#059669' : '#94a3b8' ?>">
                                            <?php if ($done): ?><svg class="icon icon-sm" viewBox="0 0 24 24">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            <?php elseif ($inProg): ?><svg class="icon icon-sm" viewBox="0 0 24 24">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                </svg>
                                            <?php else: ?><svg class="icon icon-sm" viewBox="0 0 24 24">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                </svg><?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;"><?= htmlspecialchars($q['title']) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><?= $q['question_count'] ?>
                                                Questions · <?= $q['time_limit'] ?> mins</div>
                                        </div>
                                    </div>
                                    <div style="text-align:center;">
                                        <span
                                            class="quiz-score badge <?= $sp !== null ? ($sp >= 75 ? 'badge-green' : 'badge-yellow') : 'badge-gray' ?>">
                                            <?= $sp !== null ? $sp . '%' : htmlspecialchars($attemptStatus) ?>
                                        </span>
                                    </div>
                                    <div style="text-align:right;">
                                        <div class="quiz-actions">
                                            <?php if ($isFile && !empty($q['file_path'])): ?>
                                                <a href="<?= htmlspecialchars($q['file_path']) ?>" target="_blank"
                                                    class="btn btn-outline btn-sm">Download</a>
                                            <?php elseif ($done): ?>
                                                <button class="btn btn-outline btn-sm" disabled>Completed</button>
                                            <?php elseif (!$isFile): ?>
                                                <button class="btn btn-primary btn-sm"
                                                    onclick="startQuiz(<?= (int) $q['id'] ?>,'<?= htmlspecialchars(addslashes($q['title'])) ?>',<?= (int) $q['time_limit'] ?>)">
                                                    <?= $inProg ? 'Resume' : 'Start' ?>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-primary btn-sm"
                                                    onclick="openSubmitModal(<?= (int) $q['id'] ?>,'<?= htmlspecialchars(addslashes($q['title'])) ?>')">Upload</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ANNOUNCEMENTS PANEL -->
            <div id="panel-announcements" class="section-panel">
                <div class="page-header">
                    <h2 class="page-title">Announcements</h2>
                    <p class="page-sub">Latest news and updates.</p>
                </div>
                <div class="card">
                    <?php if (empty($announcements)): ?>
                        <div style="padding:3rem; text-align:center; color:var(--text-muted);">No announcements found.</div>
                    <?php else:
                        foreach ($announcements as $a): ?>
                            <div style="padding:1.5rem; border-bottom:1px solid var(--border);" id="ann-<?= $a['id'] ?>">
                                <div
                                    style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem;">
                                    <h3 style="font-size:1.1rem; font-weight:700;"><?= htmlspecialchars($a['title']) ?></h3>
                                    <?php if (!empty($a['course_code'])): ?><span
                                            class="badge badge-blue"><?= htmlspecialchars($a['course_code']) ?></span><?php endif; ?>
                                </div>
                                <div style="margin-bottom:1rem; line-height:1.6;"><?= nl2br(htmlspecialchars($a['body'])) ?>
                                </div>
                                <div style="font-size:0.8rem; color:var(--text-muted);">
                                    <span style="font-weight:600;"><?= htmlspecialchars($a['author']) ?></span> ·
                                    <?= $a['posted_at'] ?>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- GRADES PANEL -->
            <div id="panel-grades" class="section-panel">
                <div class="page-header">
                    <h2 class="page-title">My Grades</h2>
                    <p class="page-sub">Academic performance history.</p>
                </div>

                <?php
                $allScores = [];
                foreach ($gradesByCourse as $gData) {
                    foreach (array_merge($gData['quiz_scores'], $gData['assign_scores']) as $item) {
                        if ($item['max_score'] > 0 && $item['score'] !== null)
                            $allScores[] = round($item['score'] / $item['max_score'] * 100, 2);
                    }
                }
                $gwa = !empty($allScores) ? round(array_sum($allScores) / count($allScores), 1) : 0;
                ?>

                <?php if (empty($gradesByCourse)): ?>
                    <div class="card">
                        <p style="padding:1.5rem;">No grades recorded yet.</p>
                    </div>
                <?php else: ?>
                    <div
                        style="background:var(--primary); color:white; padding:2rem; border-radius:var(--radius); margin-bottom:2rem; display:flex; align-items:center; justify-content:space-between;">
                        <div>
                            <div
                                style="font-size:0.85rem; opacity:0.8; margin-bottom:0.5rem; text-transform:uppercase; font-weight:700; letter-spacing:1px;">
                                General Average</div>
                            <div style="font-size:3rem; font-weight:800; font-family:'Nunito',sans-serif;"><?= $gwa ?>%
                            </div>
                        </div>
                        <div
                            style="background:rgba(255,255,255,0.2); padding:1rem; border-radius:var(--radius); text-align:center;">
                            <div style="font-size:0.8rem; opacity:0.9;">Total Items</div>
                            <div style="font-size:1.5rem; font-weight:700;"><?= count($allScores) ?></div>
                        </div>
                    </div>

                    <?php $gi = 0;
                    foreach ($gradesByCourse as $code => $gData):
                        $grad = $thumbGrads[$gi % count($thumbGrads)];
                        $gbid = 'gb' . $gi;
                        $gi++;

                        // Merge items
                        $allItems = [];
                        foreach ($gData['quiz_scores'] as $qs)
                            $allItems[] = ['type' => 'Quiz', 'title' => $qs['title'], 'score' => $qs['score'], 'max' => $qs['max_score']];
                        foreach ($gData['assign_scores'] as $as)
                            $allItems[] = ['type' => 'Task', 'title' => $as['title'], 'score' => $as['score'], 'max' => $as['max_score']];

                        $cAvg = 0;
                        if (!empty($allItems)) {
                            $cScores = array_filter($allItems, fn($i) => $i['max'] > 0 && $i['score'] !== null);
                            if (!empty($cScores))
                                $cAvg = round(array_sum(array_map(fn($i) => ($i['score'] / $i['max']) * 100, $cScores)) / count($cScores), 1);
                        }
                        ?>
                        <div class="accordion" style="margin-bottom:1rem;">
                            <div class="acc-head" onclick="toggleBlock('<?= $gbid ?>')">
                                <div class="acc-left">
                                    <div class="acc-icon" style="background:<?= $grad ?>">
                                        <svg class="icon" viewBox="0 0 24 24">
                                            <line x1="18" y1="20" x2="18" y2="10"></line>
                                            <line x1="12" y1="20" x2="12" y2="4"></line>
                                            <line x1="6" y1="20" x2="6" y2="14"></line>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="acc-title"><?= htmlspecialchars($code) ?> -
                                            <?= htmlspecialchars($gData['course_name']) ?></div>
                                        <div class="acc-meta"><?= $gData['teacher'] ?></div>
                                    </div>
                                </div>
                                <div style="font-weight:800; font-family:'Nunito', sans-serif;"><?= $cAvg ?>%</div>
                            </div>
                            <div class="acc-body" id="<?= $gbid ?>">
                                <?php if (empty($allItems)): ?>
                                    <div style="padding:1rem; text-align:center;">No items yet.</div>
                                <?php else: ?>
                                    <table style="font-size:0.9rem;">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Type</th>
                                                <th>Score</th>
                                                <th>%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($allItems as $item):
                                                $pct = ($item['max'] > 0 && $item['score'] !== null) ? round($item['score'] / $item['max'] * 100) : 0;
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['title']) ?></td>
                                                    <td><span class="badge badge-gray"><?= $item['type'] ?></span></td>
                                                    <td><?= $item['score'] ?? '-' ?> / <?= $item['max'] ?></td>
                                                    <td style="font-weight:700; color:<?= $pct >= 75 ? 'var(--success)' : 'var(--danger)' ?>">
                                                        <?= $pct ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PROFILE PANEL -->
            <div id="panel-profile" class="section-panel">
                <div class="page-header">
                    <h2 class="page-title">My Profile</h2>
                    <p class="page-sub">Manage your account settings.</p>
                </div>
                <div class="card">
                    <div style="display:flex; gap:1.5rem; align-items:center; margin-bottom:2rem;">
                        <div class="avatar" style="width:80px; height:80px; font-size:1.5rem;">
                            <?= strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) ?></div>
                        <div>
                            <h3 style="font-size:1.25rem; font-weight:700;">
                                <?= htmlspecialchars($first_name . ' ' . $last_name) ?></h3>
                            <p style="color:var(--text-muted);">Student ID: <?= $user_id ?></p>
                        </div>
                    </div>
                    <?php include 'shared/components/profile_panel.php'; ?>
                </div>
            </div>

        </main>
    </div>

    <!-- Quiz Modal -->
    <div class="modal-overlay" id="quizModal">
        <div class="modal">
            <div class="modal-header">
                <span id="quiz-modal-title" style="font-size:1.1rem;">Quiz</span>
                <span id="quiz-timer" style="font-family:monospace; font-weight:700; color:var(--danger);"></span>
            </div>
            <div class="modal-body" id="quiz-modal-body">Loading...</div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeQuizModal()">Cancel</button>
                <button class="btn btn-primary" id="quiz-submit-btn" onclick="submitQuiz()">Submit Quiz</button>
            </div>
        </div>
    </div>

    <!-- File Upload Modal -->
    <div class="modal-overlay" id="submitModal">
        <div class="modal">
            <div class="modal-header">
                <span>Submit Answer</span>
                <button onclick="document.getElementById('submitModal').classList.add('hidden')"
                    style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <p id="submit-quiz-name" style="margin-bottom:1rem; font-weight:600;"></p>
                <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Choose File</label>
                <input type="file" id="submit-file" class="form-control" style="width:100%;">
                <div id="submit-err" style="color:var(--danger); font-size:0.85rem; margin-top:0.5rem; display:none;">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline"
                    onclick="document.getElementById('submitModal').classList.add('hidden')">Cancel</button>
                <button class="btn btn-primary" onclick="submitQuizFile()">Upload File</button>
            </div>
        </div>
    </div>

    <script>
        // Navigation Logic
        function showPanel(name, btn) {
            document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));

            const panel = document.getElementById('panel-' + name);
            if (panel) panel.classList.add('active');
            if (btn) btn.classList.add('active');

            const title = btn ? btn.innerText.trim() : name.charAt(0).toUpperCase() + name.slice(1);
            document.getElementById('topbar-title').innerText = title;
        }

        // Accordion Logic
        function toggleBlock(id) {
            const el = document.getElementById(id);
            const chev = document.getElementById('chev-' + id);
            el.classList.toggle('open');
            if (chev) chev.style.transform = el.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
        }

        // Filtering
        function filterTbl(tbodyId, val) {
            val = val.toLowerCase();
            document.querySelectorAll('#' + tbodyId + ' tr').forEach(tr => {
                tr.style.display = tr.innerText.toLowerCase().includes(val) ? '' : 'none';
            });
        }

        function filterMod() {
            const q = document.getElementById('mod-search').value.toLowerCase();
            const c = document.getElementById('mod-filter-course').value;
            document.querySelectorAll('#panel-modules .accordion').forEach(acc => {
                const code = acc.dataset.code;
                if (c && code !== c) { acc.style.display = 'none'; return; }
                acc.style.display = ''; let any = false;
                acc.querySelectorAll('.acc-row').forEach(row => {
                    const txt = row.dataset.title;
                    const show = !q || txt.includes(q);
                    row.style.display = show ? '' : 'none';
                    if (show) any = true;
                });
                if (q && !any) acc.style.display = 'none';
            });
        }

        function filterQuiz() {
            const q = document.getElementById('quiz-search').value.toLowerCase();
            const c = document.getElementById('quiz-filter-course').value;
            document.querySelectorAll('#panel-quizzes .accordion').forEach(acc => {
                const code = acc.dataset.code;
                if (c && code !== c) { acc.style.display = 'none'; return; }
                acc.style.display = ''; let any = false;
                acc.querySelectorAll('.acc-row').forEach(row => {
                    const txt = row.dataset.title;
                    const show = !q || txt.includes(q);
                    row.style.display = show ? '' : 'none';
                    if (show) any = true;
                });
                if (q && !any) acc.style.display = 'none';
            });
        }

        // --- NOTIFICATION & USER MENU LOGIC ---

        function toggleNotif(e) {
            e.stopPropagation();
            const d = document.getElementById('notifDropdown');
            const ud = document.getElementById('userDropdown');
            // Close user menu if open
            if (ud.style.display === 'block') ud.style.display = 'none';

            d.style.display = d.style.display === 'block' ? 'none' : 'block';
            if (d.style.display === 'block') {
                const dot = document.getElementById('notifDot');
                if (dot) dot.style.display = 'none';
            }
        }

        function toggleUserMenu(e) {
            e.stopPropagation();
            const d = document.getElementById('userDropdown');
            const nd = document.getElementById('notifDropdown');
            // Close notif menu if open
            if (nd.style.display === 'block') nd.style.display = 'none';

            d.style.display = d.style.display === 'block' ? 'none' : 'block';
        }

        function goToAnnouncement(id) {
            closeAllDropdowns();
            showPanel('announcements', document.querySelectorAll('.nav-link')[5]);
            setTimeout(() => {
                const el = document.getElementById('ann-' + id);
                if (el) {
                    el.scrollIntoView({ behavior: "smooth", block: "center" });
                    el.style.background = '#eef2ff';
                    setTimeout(() => el.style.background = 'transparent', 1000);
                }
            }, 100);
        }

        function closeNotifAndGoToPanel() {
            closeAllDropdowns();
            showPanel('announcements', document.querySelectorAll('.nav-link')[5]);
        }

        function closeAllDropdowns() {
            document.getElementById('notifDropdown').style.display = 'none';
            document.getElementById('userDropdown').style.display = 'none';
        }

        // --- END MENU LOGIC ---

        // --- MODULE LOGIC (No Refresh) ---

        function autoMarkDone(mid, cid) {
            const row = document.querySelector(`.acc-row[data-id="${mid}"]`);
            if (row && row.dataset.status !== 'done') {
                const iconContainer = row.querySelector('.status-icon');
                iconContainer.innerHTML = '<svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                iconContainer.style.background = '#d1fae5';
                iconContainer.style.color = '#059669';

                const badge = row.querySelector('.status-badge');
                badge.className = 'badge badge-green';
                badge.innerText = 'Done';

                const btnText = row.querySelector('.btn-text');
                if (btnText) btnText.innerText = 'View';

                row.dataset.status = 'done';

                const accordion = row.closest('.accordion');
                if (accordion) {
                    const countSpan = accordion.querySelector('.mod-count');
                    const pctSpan = accordion.querySelector('.mod-pct');
                    let currentDone = parseInt(accordion.dataset.done);
                    let total = parseInt(accordion.dataset.total);

                    let newDone = currentDone + 1;
                    let newPct = Math.round((newDone / total) * 100);

                    countSpan.innerText = newDone;
                    pctSpan.innerText = newPct + '%';
                    accordion.dataset.done = newDone;
                }
            }

            fetch('api/module_progress.php?action=mark', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ module_id: mid, course_id: cid })
            }).catch(err => console.error(err));
        }

        // --- QUIZ LOGIC (No Refresh) ---

        let currentQuizId = null;
        let timerInterval = null;
        let answers = {};

        async function startQuiz(id, title, limit) {
            document.getElementById('quizModal').classList.remove('hidden');
            document.getElementById('quiz-modal-title').innerText = title;
            document.getElementById('quiz-modal-body').innerHTML = 'Loading questions...';
            document.getElementById('quiz-submit-btn').disabled = true;

            try {
                const res = await fetch(`api/student_quiz.php?action=start&id=${id}`);
                const data = await res.json();

                if (!data.success) {
                    document.getElementById('quiz-modal-body').innerHTML = '<p style="color:red;">' + data.message + '</p>';
                    return;
                }

                currentQuizId = data.data.attempt_id;
                const quiz = data.data;
                let html = '';

                quiz.questions.forEach((q, i) => {
                    html += `<div style="margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1px solid #eee;">
                        <div style="font-weight:700; margin-bottom:0.5rem;">${i + 1}. ${q.question_text}</div>`;
                    if (q.question_type === 'essay') {
                        html += `<textarea id="q-${q.id}" class="form-control" rows="3"></textarea>`;
                    } else {
                        html += `<div style="display:flex; flex-direction:column; gap:0.5rem;">`;
                        q.choices.forEach(c => {
                            html += `<label style="display:flex; gap:0.5rem; align-items:center; cursor:pointer; padding:0.5rem; border:1px solid #eee; border-radius:6px;">
                                <input type="radio" name="q_${q.id}" value="${c.id}" onchange="answers[${q.id}] = ${c.id}"> ${c.choice_text}
                            </label>`;
                        });
                        html += `</div>`;
                    }
                    html += `</div>`;
                });

                document.getElementById('quiz-modal-body').innerHTML = html;
                document.getElementById('quiz-submit-btn').disabled = false;

                if (limit > 0) {
                    let time = limit * 60;
                    timerInterval = setInterval(() => {
                        time--;
                        const m = Math.floor(time / 60);
                        const s = time % 60;
                        document.getElementById('quiz-timer').innerText = `${m}:${s < 10 ? '0' + s : s}`;
                        if (time <= 0) submitQuiz();
                    }, 1000);
                }
            } catch (e) {
                document.getElementById('quiz-modal-body').innerHTML = 'Error loading quiz.';
            }
        }

        async function submitQuiz() {
            clearInterval(timerInterval);
            const btn = document.getElementById('quiz-submit-btn');
            btn.disabled = true; btn.innerText = 'Submitting...';

            try {
                const res = await fetch('api/student_quiz.php?action=submit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ attempt_id: currentQuizId, answers })
                });
                const data = await res.json();

                if (data.success) {
                    const scorePct = Math.round(data.score / data.max_score * 100);
                    closeQuizModal();

                    const row = document.querySelector(`.acc-row[data-id="${currentQuizId}"]`);
                    if (row) {
                        const iconWrap = row.querySelector('.quiz-icon-wrap');
                        iconWrap.innerHTML = '<svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                        iconWrap.style.background = '#d1fae5';
                        iconWrap.style.color = '#059669';

                        const badge = row.querySelector('.quiz-score');
                        badge.className = 'badge ' + (scorePct >= 75 ? 'badge-green' : 'badge-yellow');
                        badge.innerText = scorePct + '%';

                        const actions = row.querySelector('.quiz-actions');
                        actions.innerHTML = '<button class="btn btn-outline btn-sm" disabled>Completed</button>';
                    }

                    const accordion = row.closest('.accordion');
                    if (accordion) {
                        const countSpan = accordion.querySelector('.quiz-count');
                        const pctSpan = accordion.querySelector('.quiz-pct');
                        let currentDone = parseInt(accordion.dataset.done);
                        let total = parseInt(accordion.dataset.total);

                        let newDone = currentDone + 1;
                        let newPct = Math.round((newDone / total) * 100);

                        countSpan.innerText = newDone;
                        pctSpan.innerText = newPct + '%';
                        accordion.dataset.done = newDone;
                    }

                } else {
                    alert('Error: ' + (data.message || 'Could not submit'));
                    btn.disabled = false; btn.innerText = 'Submit Quiz';
                }
            } catch (e) {
                alert('Connection error.');
                btn.disabled = false; btn.innerText = 'Submit Quiz';
            }
        }

        function closeQuizModal() {
            clearInterval(timerInterval);
            document.getElementById('quizModal').classList.add('hidden');
        }

        // --- FILE UPLOAD LOGIC (No Refresh) ---

        function openSubmitModal(id, title) {
            currentQuizId = id;
            document.getElementById('submit-quiz-name').innerText = title;
            document.getElementById('submit-file').value = '';
            document.getElementById('submitModal').classList.remove('hidden');
        }

        async function submitQuizFile() {
            const fileInput = document.getElementById('submit-file');
            if (fileInput.files.length === 0) return alert('Please select a file');

            const fd = new FormData();
            fd.append('quiz_id', currentQuizId);
            fd.append('file', fileInput.files[0]);

            const btn = document.querySelector('#submitModal .btn-primary');
            const originalText = btn.innerText;
            btn.disabled = true; btn.innerText = 'Uploading...';

            try {
                const res = await fetch('api/student_quiz.php?action=submit_file', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    document.getElementById('submitModal').classList.add('hidden');

                    const row = document.querySelector(`.acc-row[data-id="${currentQuizId}"]`);
                    if (row) {
                        const badge = row.querySelector('.quiz-score');
                        badge.className = 'badge badge-yellow';
                        badge.innerText = 'Submitted';
                    }
                    alert('File uploaded successfully!');
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Upload failed.');
            } finally {
                btn.disabled = false; btn.innerText = originalText;
            }
        }

        // GLOBAL CLICK HANDLER (Updated to handle both dropdowns)
        window.onclick = function (e) {
            const nd = document.getElementById('notifDropdown');
            const ud = document.getElementById('userDropdown');

            const btnNotif = e.target.closest('.btn-icon');
            const isInsideNotif = e.target.closest('#notifDropdown');
            const isInsideUser = e.target.closest('#userDropdown');

            // If we didn't click inside a dropdown, and didn't click the bell/user button...
            if (!isInsideNotif && !isInsideUser && !btnNotif) {
                // Close both if they are open
                if (nd.style.display === 'block') nd.style.display = 'none';
                if (ud.style.display === 'block') ud.style.display = 'none';
            }
        }
    </script>
</body>

</html>