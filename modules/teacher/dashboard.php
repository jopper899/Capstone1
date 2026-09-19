<?php
// ============================================================
//  Arandia College eLMS — Teacher Dashboard
//  File: teacher.php  |  Target: SHS & HS
// ============================================================
require_once __DIR__ . '/../../shared/middleware/teacher.php';
require_once __DIR__ . '/../../config/conn.php';

$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$user_id = $_SESSION['user_id'];

$uInfo = $conn->query("SELECT section_dept FROM users WHERE id = $user_id LIMIT 1")->fetch_assoc();
$dept = $uInfo['section_dept'] ?? '';

//  My Assigned Subjects 
$mySubjects = [];
$res = $conn->query(
    "SELECT ta.id AS assign_id, ta.section, ta.school_year, ta.semester,
            c.id AS course_id, c.course_code, c.course_name, c.description
    FROM teacher_assignments ta
    JOIN courses c ON c.id = ta.course_id
    WHERE ta.teacher_id = $user_id
    ORDER BY ta.school_year DESC, c.course_code, ta.section"
);
while ($r = $res->fetch_assoc())
    $mySubjects[] = $r;

//  My Students 
$myStudents = [];
if (!empty($mySubjects)) {
    $courseIds = implode(',', array_unique(array_map('intval', array_column($mySubjects, 'course_id'))));
    $res = $conn->query(
        "SELECT DISTINCT u.id, u.school_id, u.first_name, u.last_name, u.section_dept,
                c.course_name, c.course_code
        FROM enrollments e
        JOIN users u ON u.id = e.student_id
        JOIN courses c ON c.id = e.course_id
        WHERE e.course_id IN ($courseIds) AND u.role = 'Student'
        ORDER BY u.section_dept, u.last_name, u.first_name, c.course_code"
    );
    while ($r = $res->fetch_assoc())
        $myStudents[] = $r;
}

// Build per-student map
$studentMap = [];
foreach ($myStudents as $row) {
    $sid = $row['id'];
    if (!isset($studentMap[$sid])) {
        $studentMap[$sid] = [
            'id' => $row['id'],
            'school_id' => $row['school_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'section_dept' => $row['section_dept'],
            'subjects' => [],
        ];
    }
    $studentMap[$sid]['subjects'][] = $row['course_code'];
}

// Group students by section
$studentsBySection = [];
foreach ($studentMap as $st) {
    $sec = $st['section_dept'] ?: 'Unassigned';
    $studentsBySection[$sec][] = $st;
}
ksort($studentsBySection);

//  Stats 
$statSubjects = count(array_unique(array_column($mySubjects, 'course_id')));
$statSections = count($mySubjects);
$statStudents = count($studentMap);

$statPending = 0;
if (!empty($mySubjects)) {
    $courseIds = implode(',', array_unique(array_map('intval', array_column($mySubjects, 'course_id'))));
    $pRes = $conn->query(
        "SELECT COUNT(*) AS cnt FROM submissions s
        JOIN assignments a ON a.id = s.assignment_id
        LEFT JOIN grades g ON g.student_id = s.student_id AND g.course_id = a.course_id
        WHERE a.course_id IN ($courseIds) AND g.id IS NULL"
    );
    if ($pRes)
        $statPending = (int) $pRes->fetch_assoc()['cnt'];
}

//  Module count for teacher 
$statModules = 0;
if (!empty($mySubjects)) {
    $courseIds = implode(',', array_unique(array_map('intval', array_column($mySubjects, 'course_id'))));
    $mRes = $conn->query("SELECT COUNT(*) AS cnt FROM modules WHERE course_id IN ($courseIds)");
    if ($mRes)
        $statModules = (int) $mRes->fetch_assoc()['cnt'];
}

//  Quiz count 
$statQuizzes = 0;
if (!empty($mySubjects)) {
    $courseIds = implode(',', array_unique(array_map('intval', array_column($mySubjects, 'course_id'))));
    $qRes = $conn->query("SELECT COUNT(*) AS cnt FROM quizzes WHERE course_id IN ($courseIds)");
    if ($qRes)
        $statQuizzes = (int) $qRes->fetch_assoc()['cnt'];
}

//  My Announcements 
// Includes: school-wide announcements (course_id IS NULL) OR announcements
// posted to any course this teacher is assigned to (regardless of author),
// so admin/other-teacher announcements relevant to this teacher also show up.
// Runs even with no assigned subjects so school-wide posts still appear.
$myAnnouncements = [];
$courseIds = !empty($mySubjects)
    ? implode(',', array_unique(array_map('intval', array_column($mySubjects, 'course_id'))))
    : '';
$courseFilter = $courseIds !== '' ? "a.course_id IS NULL OR a.course_id IN ($courseIds)" : "a.course_id IS NULL";
$aRes = $conn->query(
    "SELECT a.id, a.title, a.body, a.course_id, a.posted_at AS posted_at_raw,
            DATE_FORMAT(a.posted_at,'%b %d, %Y %h:%i %p') AS posted_at,
            CONCAT(u.first_name,' ',u.last_name) AS author,
            c.course_name, c.course_code
     FROM announcements a
     LEFT JOIN courses c ON c.id = a.course_id
     LEFT JOIN users u ON u.id = a.author_id
     WHERE $courseFilter
     ORDER BY a.posted_at DESC
     LIMIT 20"
);
if ($aRes) {
    while ($r = $aRes->fetch_assoc())
        $myAnnouncements[] = $r;
}

//  Pending Submissions (ungraded) 
// Submissions from students in this teacher's courses that don't have a
// matching grade entry yet — surfaced in the notification bell so the
// teacher knows what needs grading.
$pendingSubmissions = [];
if (!empty($mySubjects)) {
    $courseIds = implode(',', array_unique(array_map('intval', array_column($mySubjects, 'course_id'))));
    $sRes = $conn->query(
        "SELECT s.id AS submission_id, s.submitted_at, a.id AS assignment_id, a.title, a.course_id,
                u.first_name, u.last_name, c.course_code
         FROM submissions s
         JOIN assignments a ON a.id = s.assignment_id
         JOIN users u ON u.id = s.student_id
         JOIN courses c ON c.id = a.course_id
         LEFT JOIN grades g ON g.student_id = s.student_id AND g.course_id = a.course_id
         WHERE a.course_id IN ($courseIds) AND g.id IS NULL
         ORDER BY s.submitted_at DESC
         LIMIT 20"
    );
    if ($sRes) {
        while ($r = $sRes->fetch_assoc())
            $pendingSubmissions[] = $r;
    }
}

//  Group subjects by school year 
$subjectsByYear = [];
foreach ($mySubjects as $s) {
    $subjectsByYear[$s['school_year']][] = $s;
}

//  Design tokens 
$thumbGrads = [
    'linear-gradient(135deg,#003087,var(--primary-light))',
    'linear-gradient(135deg,#00875a,#00c97f)',
    'linear-gradient(135deg,#b38600,#ffd000)',
    'linear-gradient(135deg,#7b2d8b,#c850c0)',
    'linear-gradient(135deg,#c0392b,#e74c3c)',
    'linear-gradient(135deg,#0097a7,#26c6da)',
];

function sectionColor(string $sec): array
{
    $s = strtolower($sec);
    if (str_contains($s, '12'))
        return ['#7b2d8b', '#f3e8ff'];
    if (str_contains($s, '11'))
        return ['#0097a7', '#e0f7fa'];
    if (str_contains($s, '10'))
        return ['#003087', '#e8f0ff'];
    if (str_contains($s, '9'))
        return ['#00875a', '#e6fff4'];
    if (str_contains($s, '8'))
        return ['#b38600', '#fffbe6'];
    if (str_contains($s, '7'))
        return ['#c0392b', '#fff0ec'];
    return ['#555555', '#f5f5f5'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <base href="../../">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard — Arandia College eLMS</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Open+Sans:wght@400;500;600&display=swap"
        rel="stylesheet">
    <style>
:root {
      --primary: #003087;
      --primary-dark: #001a4d;
      --primary-light: #0044cc;
      --accent: #FFD700;
      --text-dark: #0f172a;
      --text-light: #64748b;
      --bg-light: #f8fafc;
      --white: #ffffff;
    }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html,
        body {
            height: 100%
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            display: flex;
            flex-direction: row;
            min-height: 100vh
        }

        /*  ADMIN-STYLE SIDEBAR  */
        .sidebar {
            width: 240px;
            min-width: 240px;
            background: white;
            border-right: 1px solid #e8eaf0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: sticky;
            top: 0;
            z-index: 100;
            flex-shrink: 0;
        }

        .sidebar-header {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid #e8eaf0;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: .85rem;
            text-decoration: none;
            margin-bottom: 1rem;
        }

        .sidebar-logo {
            width: 46px;
            height: 46px;
            object-fit: contain;
            border-radius: 8px;
        }

        .sidebar-brand-text strong {
            display: block;
            font-family: 'Nunito', sans-serif;
            font-size: .95rem;
            font-weight: 900;
            color: #003087;
            line-height: 1.3;
        }

        .sidebar-brand-text span {
            font-size: .7rem;
            color: #888;
        }

        .sidebar-user-card {
            background: rgba(0,48,135,0.07);
            border: 1px solid rgba(0,48,135,0.12);
            border-radius: 10px;
            padding: .65rem .85rem;
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        .sidebar-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #003087, #00875a);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Nunito', sans-serif;
            font-size: .8rem;
            font-weight: 800;
            color: white;
            flex-shrink: 0;
        }

        .sidebar-user-info strong {
            display: block;
            font-family: 'Nunito', sans-serif;
            font-size: .8rem;
            font-weight: 700;
            color: #003087;
            line-height: 1.3;
        }

        .sidebar-user-info span {
            font-size: .68rem;
            color: #94a3b8;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: .1rem;
        }

        .sidebar-section {
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #aaa;
            padding: .75rem 1.5rem .25rem;
            margin-top: .35rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .65rem 1.5rem;
            font-size: .85rem;
            font-weight: 600;
            color: #555;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all .18s;
            cursor: pointer;
            border-top: none;
            border-right: none;
            border-bottom: none;
            border-left: 3px solid transparent;
            background: none;
            width: 100%;
            text-align: left;
        }

        .sidebar-link:hover {
            background: #eff6ff;
            color: #003087;
            border-left-color: #003087;
        }

        .sidebar-link.active {
            background: rgba(0,48,135,0.1);
            color: #003087;
            border-left-color: #003087;
            font-weight: 700;
        }

        .sidebar-link.danger { color: #c0392b; }
        .sidebar-link.danger:hover { background: rgba(192,57,43,.08); border-left-color: transparent; color: #c0392b; }

        .sidebar-icon {
            font-size: 1rem;
            width: 22px;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: .5rem 0;
            border-top: 1px solid #e8eaf0;
        }

        /*  TOPBAR (inside app-body)  */
        .app-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .topbar {
            background: var(--primary-dark);
            color: rgba(255,255,255,0.8);
            padding: 0 1.75rem;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 99;
            flex-shrink: 0;
        }

        .topbar-left {
            font-size: .82rem;
            color: rgba(255,255,255,.7);
        }

        .topbar-left strong {
            color: #FFD700;
            font-weight: 700;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .topbar-badge {
            background: rgba(255,215,0,0.2);
            color: #FFD700;
            font-family: 'Nunito', sans-serif;
            font-size: .72rem;
            font-weight: 800;
            padding: .25rem .75rem;
            border-radius: 100px;
        }

        .btn-logout {
            font-size: .78rem;
            font-weight: 700;
            color: #ff8a80;
            text-decoration: none;
            padding: .35rem .85rem;
            border: 1.5px solid rgba(255,138,128,.4);
            border-radius: 7px;
            transition: all .15s;
            background: none;
            cursor: pointer;
        }

        .btn-logout:hover {
            background: rgba(255,138,128,.15);
            color: white;
        }

        .main-content {
            flex: 1;
            padding: 2rem 2.5rem;
            overflow-y: auto;
        }

        /*  TYPOGRAPHY  */
        .page-title {
            font-family: 'Nunito', sans-serif;
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: .25rem
        }

        .page-sub {
            font-size: .85rem;
            color: #888;
            margin-bottom: 1.75rem
        }

        .panel-header {
            margin-bottom: 1.75rem
        }

        .section-panel {
            display: none
        }

        .section-panel.active {
            display: block
        }

        /*  WELCOME  */
        .welcome-card {
            background: linear-gradient(135deg, #003087, var(--primary-light));
            color: white;
            border-radius: 16px;
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem
        }

        .welcome-text h2 {
            font-family: 'Nunito', sans-serif;
            font-size: 1.4rem;
            font-weight: 900;
            margin-bottom: .3rem
        }

        .welcome-text p {
            font-size: .85rem;
            opacity: .85;
            line-height: 1.5
        }

        .welcome-meta {
            margin-top: .75rem;
            display: flex;
            gap: .5rem;
            flex-wrap: wrap
        }

        .welcome-meta span {
            background: rgba(255, 255, 255, .18);
            font-size: .72rem;
            font-weight: 700;
            padding: .25rem .7rem;
            border-radius: 100px
        }

        .welcome-icon {
            font-size: 4rem;
            opacity: .6
        }

        /*  STATS  */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.75rem
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
            border-left: 4px solid var(--accent);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform .2s
        }

        .stat-card:hover {
            transform: translateY(-2px)
        }

        .stat-card-1 {
            --accent: #003087
        }

        .stat-card-2 {
            --accent: #00C97F
        }

        .stat-card-3 {
            --accent: #FFB800
        }

        .stat-card-4 {
            --accent: #9B59B6
        }

        .stat-card-5 {
            --accent: #0097a7
        }

        .stat-card-6 {
            --accent: #c0392b
        }

        .stat-icon {
            font-size: 1.8rem
        }

        .stat-info .stat-num {
            font-family: 'Nunito', sans-serif;
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--accent)
        }

        .stat-info .stat-label {
            font-size: .75rem;
            color: #888;
            font-weight: 600
        }

        /*  DASHBOARD GRID  */
        .dash-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem
        }

        .dash-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem
        }

        .card-panel {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, .07);
            overflow: hidden
        }

        .card-panel-header {
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: space-between
        }

        .card-panel-header h3 {
            font-family: 'Nunito', sans-serif;
            font-size: .95rem;
            font-weight: 800;
            color: var(--text-dark)
        }

        .card-panel-body {
            padding: 1.1rem 1.5rem
        }

        .list-item {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .75rem 0;
            border-bottom: 1px solid #f8f9fc
        }

        .list-item:last-child {
            border-bottom: none
        }

        .list-icon {
            width: 36px;
            height: 36px;
            background: #e8f0ff;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0
        }

        .list-info .list-name {
            font-weight: 700;
            font-size: .83rem;
            color: var(--text-dark)
        }

        .list-info .list-meta {
            font-size: .7rem;
            color: #aaa;
            margin-top: .1rem
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #bbb;
            font-size: .85rem
        }

        .empty-icon {
            font-size: 2.5rem;
            display: block;
            margin-bottom: .5rem
        }

        /*  BADGES / PILLS  */
        .badge {
            display: inline-block;
            font-size: .68rem;
            font-weight: 800;
            padding: .2rem .65rem;
            border-radius: 100px;
            margin: .1rem
        }

        .badge-green {
            background: #e6fff4;
            color: #003087
        }

        .badge-blue {
            background: #e8f0ff;
            color: #003087
        }

        .badge-yellow {
            background: #fffbe6;
            color: #b38600
        }

        .badge-red {
            background: #fff0ec;
            color: #c0392b
        }

        .badge-gray {
            background: #f5f5f5;
            color: #888
        }

        .badge-purple {
            background: #f3e8ff;
            color: #7b2d8b
        }

        /*  BUTTONS  */
        .btn {
            font-family: 'Nunito', sans-serif;
            font-size: .82rem;
            font-weight: 700;
            padding: .52rem 1.1rem;
            border-radius: 9px;
            border: none;
            cursor: pointer;
            transition: all .2s;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            text-decoration: none
        }

        .btn-primary {
            background: var(--primary);
            color: white
        }

        .btn-primary:hover {
            background: #0044bb
        }

        .btn-green {
            background: var(--primary);
            color: white
        }

        .btn-green:hover {
            background: #0044bb
        }

        .btn-red {
            background: #fff0ec;
            color: #c0392b
        }

        .btn-red:hover {
            background: #c0392b;
            color: white
        }

        .btn-blue {
            background: #e8f0ff;
            color: #003087
        }

        .btn-blue:hover {
            background: var(--primary);
            color: white
        }

        .btn-gray {
            background: #f0f2f5;
            color: #555
        }

        .btn-gray:hover {
            background: #e0e4ee
        }

        .btn-sm {
            padding: .3rem .7rem;
            font-size: .75rem
        }

        .view-all-btn {
            background: none;
            border: none;
            font-size: .78rem;
            color: var(--primary);
            font-weight: 700;
            cursor: pointer;
            padding: 0
        }

        /*  SEARCH / FILTER  */
        .search-input {
            padding: .45rem .9rem;
            border: 1.5px solid #e0e4ee;
            border-radius: 8px;
            font-size: .82rem;
            outline: none;
            font-family: 'Open Sans', sans-serif;
            transition: border-color .2s
        }

        .search-input:focus {
            border-color: #003087
        }

        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: .9rem 1.25rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center
        }

        /* 
        SHARED ACCORDION
         */
        .panel-outer {
            display: grid;
            grid-template-columns: 1fr 270px;
            gap: 1.5rem;
            align-items: start
        }

        .acc-block {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .07);
            overflow: hidden;
            margin-bottom: 1.1rem
        }

        .acc-header {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            align-items: stretch;
            cursor: pointer;
            transition: background .15s
        }

        .acc-header:hover {
            background: #f6f9ff
        }

        .acc-thumb {
            width: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
            min-height: 88px
        }

        .acc-info {
            padding: .9rem 1.2rem;
            display: flex;
            flex-direction: column;
            justify-content: center
        }

        .acc-label {
            font-size: .68rem;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: .07em;
            margin-bottom: .15rem
        }

        .acc-name {
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: .98rem;
            color: var(--text-dark);
            line-height: 1.3;
            margin-bottom: .25rem
        }

        .acc-meta {
            font-size: .73rem;
            color: #aaa
        }

        .acc-right {
            padding: .9rem 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: .4rem;
            white-space: nowrap;
            min-width: 110px
        }

        .acc-mini-bar {
            width: 85px;
            height: 5px;
            background: #e8f0ff;
            border-radius: 100px;
            overflow: hidden
        }

        .acc-mini-bar div {
            height: 5px;
            border-radius: 100px;
            transition: width .4s
        }

        .acc-pct {
            font-family: 'Nunito', sans-serif;
            font-size: .75rem;
            font-weight: 800
        }

        .acc-chevron {
            font-size: .85rem;
            color: #bbb;
            transition: transform .22s
        }

        .acc-chevron.open {
            transform: rotate(180deg)
        }

        .acc-body {
            display: none;
            border-top: 1px solid #f0f2f5
        }

        .acc-body.open {
            display: block
        }

        .acc-thead {
            display: grid;
            gap: .5rem;
            align-items: center;
            padding: .5rem 1.25rem .5rem 1.4rem;
            font-size: .65rem;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: #bbb;
            background: #f8f9fc;
            border-bottom: 1px solid #f0f2f5
        }

        .acc-row {
            display: grid;
            gap: .5rem;
            align-items: center;
            padding: .85rem 1.25rem .85rem 1.4rem;
            border-bottom: 1px solid #f8f9fc;
            transition: background .15s
        }

        .acc-row:last-child {
            border-bottom: none
        }

        .acc-row:hover {
            background: #f6f9ff
        }

        .acc-row-left {
            display: flex;
            align-items: center;
            gap: .7rem
        }

        .acc-row-ico {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            flex-shrink: 0
        }

        .acc-row-title {
            font-size: .84rem;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.3
        }

        .acc-row-sub {
            font-size: .7rem;
            color: #aaa;
            margin-top: .05rem
        }

        .acc-row-cell {
            font-size: .75rem;
            color: #666;
            text-align: center
        }

        .acc-row-action {
            display: flex;
            justify-content: flex-end;
            gap: .4rem
        }

        /* Grid columns per panel type */
        .subj-thead,
        .subj-row {
            grid-template-columns: 1fr 100px 90px 90px 80px
        }

        .mod-thead,
        .mod-row {
            grid-template-columns: 1fr 60px 90px 90px 110px
        }

        .quiz-thead,
        .quiz-row {
            grid-template-columns: 1fr 65px 70px 65px 75px 165px
        }

        .assign-thead,
        .assign-row {
            grid-template-columns: 1fr 80px 100px 80px 110px
        }

        /* Score pill */
        .score-pill {
            display: inline-flex;
            align-items: center;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: .78rem;
            padding: .2rem .6rem;
            border-radius: 100px
        }

        .score-pill.good {
            background: #e6fff4;
            color: #003087
        }

        .score-pill.ok {
            background: #fffbe6;
            color: #b38600
        }

        .score-pill.low {
            background: #fff0ec;
            color: #c0392b
        }

        .pub-badge {
            display: inline-block;
            font-size: .65rem;
            font-weight: 800;
            padding: .15rem .55rem;
            border-radius: 100px
        }

        .pub-yes {
            background: #e8f0ff;
            color: #003087
        }

        .pub-no {
            background: #f5f5f5;
            color: #999
        }

        /*  SIDEBAR CARDS  */
        .panel-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: sticky;
            top: 88px
        }

        .sidebar-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .07);
            padding: 1.25rem
        }

        .sidebar-card h4 {
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .88rem;
            color: var(--text-dark);
            margin-bottom: .9rem
        }

        .legend-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .38rem 0;
            font-size: .78rem;
            color: #555;
            border-bottom: 1px solid #f8f9fc
        }

        .legend-row:last-child {
            border-bottom: none
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: .5rem;
            flex-shrink: 0;
            display: inline-block
        }

        .donut-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem
        }

        .donut {
            position: relative;
            width: 100px;
            height: 100px
        }

        .donut svg {
            transform: rotate(-90deg)
        }

        .donut-val {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 1.3rem;
            color: #003087
        }

        /*  STUDENTS PANEL  */
        .students-outer {
            display: grid;
            grid-template-columns: 1fr 260px;
            gap: 1.5rem;
            align-items: start
        }

        .stu-block {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .07);
            overflow: hidden;
            margin-bottom: 1.1rem
        }

        .stu-header {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            align-items: stretch;
            cursor: pointer;
            transition: background .15s
        }

        .stu-header:hover {
            background: #f6f9ff
        }

        .stu-swatch {
            width: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            min-height: 80px;
            flex-shrink: 0
        }

        .stu-info {
            padding: .9rem 1.2rem;
            display: flex;
            flex-direction: column;
            justify-content: center
        }

        .stu-grade {
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .07em;
            margin-bottom: .15rem
        }

        .stu-sec-name {
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 1rem;
            color: var(--text-dark);
            margin-bottom: .2rem
        }

        .stu-count {
            font-size: .73rem;
            color: #aaa
        }

        .stu-right {
            padding: .9rem 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: .4rem;
            white-space: nowrap;
            min-width: 90px
        }

        .stu-count-badge {
            font-family: 'Nunito', sans-serif;
            font-size: 1.6rem;
            font-weight: 900
        }

        .stu-chevron {
            font-size: .85rem;
            color: #bbb;
            transition: transform .22s
        }

        .stu-chevron.open {
            transform: rotate(180deg)
        }

        .stu-body {
            display: none;
            border-top: 1px solid #f0f2f5
        }

        .stu-body.open {
            display: block
        }

        .stu-sec-head {
            display: grid;
            grid-template-columns: 1fr 130px 1fr;
            gap: .5rem;
            align-items: center;
            padding: .45rem 1.25rem .45rem 1.4rem;
            font-size: .65rem;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: #bbb;
            background: #f8f9fc;
            border-bottom: 1px solid #f0f2f5
        }

        .stu-row {
            display: grid;
            grid-template-columns: 1fr 130px 1fr;
            gap: .5rem;
            align-items: center;
            padding: .8rem 1.25rem .8rem 1.4rem;
            border-bottom: 1px solid #f8f9fc;
            transition: background .15s
        }

        .stu-row:last-child {
            border-bottom: none
        }

        .stu-row:hover {
            background: #f6f9ff
        }

        .stu-row-name {
            display: flex;
            align-items: center;
            gap: .65rem
        }

        .stu-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            font-weight: 800;
            flex-shrink: 0;
            color: white
        }

        .stu-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: sticky;
            top: 88px
        }

        .stu-sidebar-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .07);
            padding: 1.25rem
        }

        .stu-sidebar-card h4 {
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .88rem;
            color: var(--text-dark);
            margin-bottom: .9rem
        }

        .sec-legend-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .4rem 0;
            font-size: .78rem;
            color: #555;
            border-bottom: 1px solid #f8f9fc
        }

        .sec-legend-row:last-child {
            border-bottom: none
        }

        .sec-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: .5rem;
            flex-shrink: 0;
            display: inline-block
        }

        /*  FORMS  */
        .mod-toolbar {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem
        }

        .form-row {
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            flex-wrap: wrap
        }

        .form-grp {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            flex: 1;
            min-width: 220px
        }

        .form-lbl {
            font-size: .75rem;
            font-weight: 700;
            color: #555
        }

        .finput {
            padding: .6rem .9rem;
            border: 1.5px solid #e0e4ee;
            border-radius: 9px;
            font-family: 'Open Sans', sans-serif;
            font-size: .85rem;
            outline: none;
            background: #fafbff;
            transition: border-color .2s
        }

        .finput:focus {
            border-color: var(--primary);
            background: white
        }

        .form-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem
        }

        /*  MODALS  */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            z-index: 600;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem
        }

        .modal-overlay.show {
            display: flex
        }

        .modal {
            background: white;
            border-radius: 18px;
            width: 100%;
            max-width: 680px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .25)
        }

        .modal-head {
            padding: 1.5rem 1.75rem;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            background: white;
            z-index: 1
        }

        .modal-head h2 {
            font-family: 'Nunito', sans-serif;
            font-size: 1.1rem;
            font-weight: 900;
            color: var(--text-dark)
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.4rem;
            cursor: pointer;
            color: #aaa
        }

        .modal-close:hover {
            color: #333
        }

        .modal-body {
            padding: 1.5rem 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1rem
        }

        .modal-footer {
            padding: 1.25rem 1.75rem;
            border-top: 1px solid #f0f2f5;
            display: flex;
            justify-content: flex-end;
            gap: .75rem;
            position: sticky;
            bottom: 0;
            background: white
        }

        .modal-err {
            background: #fff0ec;
            border: 1px solid #ffcfbf;
            color: #c0392b;
            font-size: .78rem;
            font-weight: 600;
            padding: .6rem .9rem;
            border-radius: 8px;
            display: none
        }

        .q-block {
            background: #f8f9fc;
            border-radius: 12px;
            padding: 1.1rem;
            margin-bottom: .75rem;
            border: 1.5px solid #e8eaf0
        }

        .q-block-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .75rem
        }

        .q-block-num {
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .82rem;
            color: #003087
        }

        .choice-row {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: .4rem
        }

        .choice-row input[type=text] {
            flex: 1;
            padding: .4rem .75rem;
            border: 1.5px solid #e0e4ee;
            border-radius: 7px;
            font-size: .82rem;
            outline: none;
            font-family: 'Open Sans', sans-serif
        }

        .choice-row input[type=text]:focus {
            border-color: #003087
        }

        .choice-row input[type=radio] {
            accent-color: var(--primary);
            width: 16px;
            height: 16px
        }

        .add-choice-btn {
            background: none;
            border: 1.5px dashed #ccc;
            color: #aaa;
            font-size: .78rem;
            font-weight: 600;
            padding: .35rem .75rem;
            border-radius: 7px;
            cursor: pointer;
            transition: all .2s
        }

        .add-choice-btn:hover {
            border-color: var(--primary);
            color: #003087
        }

        /*  ANNOUNCEMENTS PANEL  */
        .ann-item {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #003087
        }

        .ann-item-title {
            font-family: 'Nunito', sans-serif;
            font-size: .95rem;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: .35rem
        }

        .ann-item-body {
            font-size: .83rem;
            color: #555;
            line-height: 1.65;
            margin-bottom: .6rem;
            white-space: pre-wrap
        }

        .ann-item-meta {
            font-size: .72rem;
            color: #aaa;
            display: flex;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap
        }

        .footer-bar {
            background: var(--primary);
            color: rgba(255, 255, 255, .4);
            font-size: .75rem;
            text-align: center;
            padding: .75rem
        }

        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--primary);
            color: white;
            font-family: 'Nunito', sans-serif;
            font-size: .88rem;
            font-weight: 700;
            padding: .85rem 1.4rem;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .2);
            z-index: 999;
            transition: opacity .4s;
            opacity: 0;
            pointer-events: none
        }

        .toast.show {
            opacity: 1
        }

        @media(max-width:1100px) {

            .panel-outer,
            .students-outer {
                grid-template-columns: 1fr
            }
        }

        @media(max-width:900px) {
            .quick-stats {
                grid-template-columns: 1fr 1fr
            }

            .dash-grid,
            .dash-grid-3 {
                grid-template-columns: 1fr
            }

            .form-2col {
                grid-template-columns: 1fr
            }
        }

        @media(max-width:700px) {
            .dashboard-layout {
                flex-direction: column
            }

            .sidebar {
                width: 100%;
                min-height: auto
            }

            .main-content {
                padding: 1rem
            }

            .acc-header {
                grid-template-columns: 70px 1fr auto
            }

            .acc-thumb {
                width: 70px;
                min-height: 70px;
                font-size: 1.6rem
            }

            .subj-thead,
            .subj-row,
            .mod-thead,
            .mod-row,
            .quiz-thead,
            .quiz-row,
            .assign-thead,
            .assign-row {
                grid-template-columns: 1fr auto
            }

            .acc-row-cell {
                display: none
            }

            .stu-header {
                grid-template-columns: 60px 1fr auto
            }

            .stu-swatch {
                width: 60px;
                min-height: 65px;
                font-size: 1.5rem
            }

            .stu-sec-head,
            .stu-row {
                grid-template-columns: 1fr auto
            }
        }

        /*  NOTIFICATION BELL  */
        .notif-wrap {
            position: relative;
        }
        .notif-btn {
            background: rgba(255,255,255,0.12);
            border: 1.5px solid rgba(255,255,255,0.18);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
            position: relative;
            transition: background .18s;
            flex-shrink: 0;
        }
        .notif-btn:hover { background: rgba(255,255,255,0.22); }
        .notif-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #e74c3c;
            color: white;
            font-family: 'Nunito', sans-serif;
            font-size: .6rem;
            font-weight: 800;
            min-width: 17px;
            height: 17px;
            border-radius: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 3px;
            border: 2px solid var(--primary-dark);
            line-height: 1;
        }
        .notif-badge.hidden { display: none; }
        .notif-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 340px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.18);
            z-index: 500;
            display: none;
            overflow: hidden;
            border: 1px solid #e8eaf0;
        }
        .notif-dropdown.open { display: block; }
        .notif-dd-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .9rem 1.1rem .75rem;
            border-bottom: 1px solid #f0f2f5;
        }
        .notif-dd-head strong {
            font-family: 'Nunito', sans-serif;
            font-size: .9rem;
            font-weight: 900;
            color: #0f172a;
        }
        .notif-mark-all {
            font-size: .72rem;
            font-weight: 700;
            color: #003087;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }
        .notif-mark-all:hover { text-decoration: underline; }
        .notif-list {
            max-height: 320px;
            overflow-y: auto;
        }
        .notif-item {
            display: flex;
            gap: .75rem;
            padding: .75rem 1.1rem;
            border-bottom: 1px solid #f8f9fc;
            cursor: pointer;
            transition: background .15s;
            align-items: flex-start;
        }
        .notif-item:last-child { border-bottom: none; }
        .notif-item:hover { background: #f8fafc; }
        .notif-item.unread { background: #eff6ff; }
        .notif-item.unread:hover { background: #dbeafe; }
        .notif-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #003087;
            flex-shrink: 0;
            margin-top: 5px;
        }
        .notif-item:not(.unread) .notif-dot { background: #d1d5db; }
        .notif-item-title {
            font-size: .82rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: .15rem;
            line-height: 1.4;
        }
        .notif-item-meta {
            font-size: .7rem;
            color: #94a3b8;
        }
        .notif-empty {
            text-align: center;
            padding: 2.5rem 1rem;
            color: #aaa;
            font-size: .82rem;
        }
        .notif-empty span { display: block; font-size: 2rem; margin-bottom: .5rem; }
        .notif-dd-foot {
            padding: .65rem 1.1rem;
            border-top: 1px solid #f0f2f5;
            text-align: center;
        }
        .notif-dd-foot button {
            font-size: .78rem;
            font-weight: 700;
            color: #003087;
            background: none;
            border: none;
            cursor: pointer;
        }
        .notif-dd-foot button:hover { text-decoration: underline; }
    </style>
</head>

<body>

  <!-- ADMIN-STYLE SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <a href="index.php" class="sidebar-brand">
        <img src="picture/logo.jpg" alt="Logo" class="sidebar-logo" onerror="this.style.display='none'">
        <div class="sidebar-brand-text">
          <strong>Arandia College</strong>
          <span>eLMS Teacher Portal</span>
        </div>
      </a>
      <div class="sidebar-user-card">
        <div class="sidebar-avatar"><?= strtoupper(substr($first_name,0,1).substr($last_name,0,1)) ?></div>
        <div class="sidebar-user-info">
          <strong><?= htmlspecialchars($first_name . ' ' . $last_name) ?></strong>
          <span>[Teacher] Teacher<?= $dept ? ' · ' . htmlspecialchars($dept) : '' ?></span>
        </div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section">Main</div>
      <button class="sidebar-link active" onclick="showPanel('dashboard',this)"><span class="sidebar-icon">[Home]</span> Dashboard</button>

      <div class="sidebar-section">Teaching</div>
      <button class="sidebar-link" onclick="showPanel('courses',this)"><span class="sidebar-icon">[Book]</span> My Subjects</button>
      <button class="sidebar-link" onclick="showPanel('modules',this)"><span class="sidebar-icon">[Folder]</span> Modules</button>
      <button class="sidebar-link" onclick="showPanel('assignments',this)"><span class="sidebar-icon">[Clipboard]</span> Assignments</button>
      <button class="sidebar-link" onclick="showPanel('quizzes',this)"><span class="sidebar-icon">[Quiz]</span> Quizzes</button>
      <button class="sidebar-link" onclick="showPanel('announcements',this)"><span class="sidebar-icon">[Announce]</span> Announcements</button>

      <div class="sidebar-section">Students</div>
      <button class="sidebar-link" onclick="showPanel('students',this)"><span class="sidebar-icon">[Students]</span> My Students</button>
      <button class="sidebar-link" onclick="showPanel('grades',this)"><span class="sidebar-icon">[Chart]</span> Grades</button>
      <button class="sidebar-link" onclick="showPanel('attendance',this)"><span class="sidebar-icon">[Check]</span> Attendance</button>

      <div class="sidebar-section">Account</div>
      <button class="sidebar-link" onclick="showPanel('profile',this)"><span class="sidebar-icon">[User]</span> My Profile</button>

      <div class="sidebar-section">AI Assistant</div>
      <button class="sidebar-link" onclick="showPanel('chatbot',this)"><span class="sidebar-icon">[AI]</span> EduBot AI</button>
    </nav>

    <div class="sidebar-footer">
      <a href="logout.php" class="sidebar-link danger">
        <span class="sidebar-icon">[Exit]</span> Sign Out
      </a>
    </div>
  </aside>

  <!-- MAIN APP BODY -->
  <div class="app-body">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">SHS &amp; HS &nbsp;/&nbsp; <strong id="topbar-panel-name">Dashboard</strong></div>
      <div class="topbar-right">
        <span class="topbar-badge">[Teacher] Teacher Portal</span>

        <!-- NOTIFICATION BELL -->
        <div class="notif-wrap" id="notifWrap">
          <button class="notif-btn" id="notifBtn" onclick="toggleNotif(event)" title="Notifications" aria-label="Notifications">
            [Bell]
            <span class="notif-badge hidden" id="notifBadge">0</span>
          </button>
          <div class="notif-dropdown" id="notifDropdown">
            <div class="notif-dd-head">
              <strong>[Bell] Notifications</strong>
              <button class="notif-mark-all" onclick="markAllRead()">Mark all as read</button>
            </div>
            <div class="notif-list" id="notifList">
              <div class="notif-empty"><span>[Mute]</span>No notifications yet.</div>
            </div>
            <div class="notif-dd-foot">
              <button onclick="showPanel('announcements',document.querySelectorAll('.sidebar-link')[5]);closeNotif()">View all announcements →</button>
            </div>
          </div>
        </div>

        <a href="logout.php" class="btn-logout">Sign Out</a>
      </div>
    </header>

    <!-- Notification data for notification bell: announcements + pending submissions -->
    <script>
    const _notifData = <?php
        $notifItems = [];

        foreach ($myAnnouncements as $a) {
            $notifItems[] = [
                'id'        => 'ann_' . (int)$a['id'],
                'type'      => 'announcement',
                'ref_id'    => (int)$a['id'],
                'title'     => $a['title'],
                'author'    => $a['author'] ?? 'Admin',
                'posted_at' => $a['posted_at'],
                'course'    => $a['course_code'] ?? null,
                'sort_at'   => $a['posted_at_raw'],
            ];
        }

        foreach ($pendingSubmissions as $s) {
            $notifItems[] = [
                'id'        => 'sub_' . (int)$s['submission_id'],
                'type'      => 'submission',
                'ref_id'    => (int)$s['assignment_id'],
                'title'     => $s['title'],
                'author'    => htmlspecialchars($s['last_name'] . ', ' . $s['first_name']),
                'posted_at' => $s['submitted_at'] ? date('M d, Y h:i A', strtotime($s['submitted_at'])) : '',
                'course'    => $s['course_code'] ?? null,
                'sort_at'   => $s['submitted_at'],
            ];
        }

        // Newest first, across both types
        usort($notifItems, fn($a, $b) => strtotime($b['sort_at'] ?? '') - strtotime($a['sort_at'] ?? ''));

        echo json_encode(array_values($notifItems));
    ?>;
    </script>

    <main class="main-content">

            <!--  DASHBOARD  -->
            <div class="section-panel active" id="panel-dashboard">
                <div class="page-title">Dashboard</div>
                <div class="page-sub">Welcome back! Here's an overview of your classes.</div>

                <div class="welcome-card">
                    <div class="welcome-text">
                        <h2>Hello, <?= htmlspecialchars($first_name) ?>! </h2>
                        <p>Manage your subjects, upload modules, create quizzes, and track your students' progress all
                            in one place.</p>
                        <?php if ($dept): ?>
                            <div class="welcome-meta"><span>[Books] <?= htmlspecialchars($dept) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <div class="welcome-icon">[Teacher]</div>
                </div>

                <div class="quick-stats">
                    <div class="stat-card stat-card-1">
                        <div class="stat-icon">[Book]</div>
                        <div class="stat-info">
                            <div class="stat-num"><?= $statSubjects ?></div>
                            <div class="stat-label">Active Subjects</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-2">
                        <div class="stat-icon">[School]</div>
                        <div class="stat-info">
                            <div class="stat-num"><?= $statSections ?></div>
                            <div class="stat-label">Sections Handled</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-3">
                        <div class="stat-icon">[Students]</div>
                        <div class="stat-info">
                            <div class="stat-num"><?= $statStudents ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-4">
                        <div class="stat-icon">[Clipboard]</div>
                        <div class="stat-info">
                            <div class="stat-num"><?= $statPending ?></div>
                            <div class="stat-label">Pending Reviews</div>
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.75rem">
                    <div class="stat-card stat-card-5" style="cursor:pointer"
                        onclick="showPanel('modules',document.querySelectorAll('.sidebar-link')[3])">
                        <div class="stat-icon">[Folder]</div>
                        <div class="stat-info">
                            <div class="stat-num"><?= $statModules ?></div>
                            <div class="stat-label">Modules Uploaded</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-6" style="cursor:pointer"
                        onclick="showPanel('quizzes',document.querySelectorAll('.sidebar-link')[5])">
                        <div class="stat-icon">[Quiz]</div>
                        <div class="stat-info">
                            <div class="stat-num"><?= $statQuizzes ?></div>
                            <div class="stat-label">Quizzes Created</div>
                        </div>
                    </div>
                </div>

                <div class="dash-grid">
                    <div class="card-panel">
                        <div class="card-panel-header">
                            <h3>[Book] My Subjects</h3>
                            <button class="view-all-btn"
                                onclick="showPanel('courses',document.querySelectorAll('.sidebar-link')[2])">View All
                                →</button>
                        </div>
                        <div class="card-panel-body">
                            <?php if (empty($mySubjects)): ?>
                                <div class="empty-state"><span class="empty-icon">[Books]</span>No subjects assigned yet.</div>
                            <?php else:
                                foreach (array_slice($mySubjects, 0, 4) as $s): ?>
                                    <div class="list-item">
                                        <div class="list-icon">[Book]</div>
                                        <div class="list-info">
                                            <div class="list-name"><?= htmlspecialchars($s['course_name']) ?></div>
                                            <div class="list-meta"><?= htmlspecialchars($s['course_code']) ?> ·
                                                <?= htmlspecialchars($s['section']) ?> · <?= $s['school_year'] ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach;
                                if (count($mySubjects) > 4): ?>
                                    <div style="text-align:center;padding:.5rem;font-size:.75rem;color:#aaa">
                                        +<?= count($mySubjects) - 4 ?> more</div>
                                <?php endif; endif; ?>
                        </div>
                    </div>

                    <div class="card-panel">
                        <div class="card-panel-header">
                            <h3>[Students] Students by Section</h3>
                            <button class="view-all-btn"
                                onclick="showPanel('students',document.querySelectorAll('.sidebar-link')[8])">View All
                                →</button>
                        </div>
                        <div class="card-panel-body">
                            <?php if (empty($studentsBySection)): ?>
                                <div class="empty-state"><span class="empty-icon">[User]</span>No students enrolled yet.</div>
                            <?php else:
                                $pc = 0;
                                foreach ($studentsBySection as $sec => $stus):
                                    if ($pc >= 5)
                                        break;
                                    [$color, $bg] = sectionColor($sec);
                                    $pc++; ?>
                                    <div class="list-item">
                                        <div class="list-icon"
                                            style="background:<?= $bg ?>;color:<?= $color ?>;font-size:.85rem;font-weight:800">
                                            <?= count($stus) ?>
                                        </div>
                                        <div class="list-info">
                                            <div class="list-name"><?= htmlspecialchars($sec) ?></div>
                                            <div class="list-meta"><?= count($stus) ?>
                                                student<?= count($stus) != 1 ? 's' : '' ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <div class="dash-grid-3">
                    <div class="card-panel" style="cursor:pointer"
                        onclick="showPanel('modules',document.querySelectorAll('.sidebar-link')[3])">
                        <div class="card-panel-body" style="text-align:center;padding:1.5rem">
                            <div style="font-size:2.5rem;margin-bottom:.5rem">[Folder]</div>
                            <div
                                style="font-family:'Nunito',sans-serif;font-weight:800;font-size:.95rem;color:#1a1a2e;margin-bottom:.2rem">
                                Upload Module</div>
                            <div style="font-size:.75rem;color:#aaa">Add learning materials</div>
                        </div>
                    </div>
                    <div class="card-panel" style="cursor:pointer"
                        onclick="showPanel('quizzes',document.querySelectorAll('.sidebar-link')[5])">
                        <div class="card-panel-body" style="text-align:center;padding:1.5rem">
                            <div style="font-size:2.5rem;margin-bottom:.5rem">[Quiz]</div>
                            <div
                                style="font-family:'Nunito',sans-serif;font-weight:800;font-size:.95rem;color:#1a1a2e;margin-bottom:.2rem">
                                Create Quiz</div>
                            <div style="font-size:.75rem;color:#aaa">Build a new assessment</div>
                        </div>
                    </div>
                    <div class="card-panel" style="cursor:pointer"
                        onclick="showPanel('announcements',document.querySelectorAll('.sidebar-link')[6])">
                        <div class="card-panel-body" style="text-align:center;padding:1.5rem">
                            <div style="font-size:2.5rem;margin-bottom:.5rem">[Announce]</div>
                            <div
                                style="font-family:'Nunito',sans-serif;font-weight:800;font-size:.95rem;color:#1a1a2e;margin-bottom:.2rem">
                                Announcements</div>
                            <div style="font-size:.75rem;color:#aaa">Send messages to students</div>
                        </div>
                    </div>
                </div>
            </div>

            <!--  MY SUBJECTS  -->
            <div class="section-panel" id="panel-courses">
                <div class="panel-header">
                    <div class="page-title">My Subjects</div>
                    <div class="page-sub">All subjects assigned to you, grouped by school year.</div>
                </div>

                <?php if (empty($mySubjects)): ?>
                    <div class="card-panel">
                        <div class="card-panel-body">
                            <div class="empty-state"><span class="empty-icon">[Books]</span>No subjects assigned yet.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="filter-bar">
                        <input class="search-input" id="subj-search" type="text" placeholder=" Search subjects..."
                            oninput="filterAcc('.subj-block','.acc-row.subj-row',this.value,'subj-filter-yr','')"
                            style="flex:1;min-width:180px">
                        <select class="search-input" id="subj-filter-yr"
                            onchange="filterAcc('.subj-block','.acc-row.subj-row',document.getElementById('subj-search').value,'subj-filter-yr','')">
                            <option value="">All School Years</option>
                            <?php foreach (array_keys($subjectsByYear) as $yr): ?>
                                <option value="<?= htmlspecialchars($yr) ?>"><?= htmlspecialchars($yr) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="panel-outer">
                        <div>
                            <?php $si = 0;
                            foreach ($subjectsByYear as $yr => $subs):
                                $grad = $thumbGrads[$si % count($thumbGrads)];
                                $bid = 'sb' . $si;
                                $si++; ?>
                                <div class="acc-block subj-block" data-yr="<?= htmlspecialchars($yr) ?>">
                                    <div class="acc-header" onclick="toggleBlock('<?= $bid ?>')">
                                        <div class="acc-thumb" style="background:<?= $grad ?>"></div>
                                        <div class="acc-info">
                                            <div class="acc-label">School Year</div>
                                            <div class="acc-name"><?= htmlspecialchars($yr) ?></div>
                                            <div class="acc-meta"><?= count($subs) ?> subject<?= count($subs) != 1 ? 's' : '' ?>
                                                assigned</div>
                                        </div>
                                        <div class="acc-right">
                                            <span class="badge badge-blue" style="font-size:.63rem"><?= count($subs) ?>
                                                subjects</span>
                                            <span class="acc-chevron" id="chev-<?= $bid ?>"></span>
                                        </div>
                                    </div>
                                    <div class="acc-body" id="<?= $bid ?>">
                                        <div class="acc-thead subj-thead">
                                            <span>Subject</span>
                                            <span style="text-align:center">Code</span>
                                            <span style="text-align:center">Section</span>
                                            <span style="text-align:center">Semester</span>
                                            <span style="text-align:right">Students</span>
                                        </div>
                                        <?php foreach ($subs as $s):
                                            $sCnt = 0;
                                            foreach ($studentMap as $st) {
                                                if (in_array($s['course_code'], $st['subjects']))
                                                    $sCnt++;
                                            }
                                            [$sc, $sb] = sectionColor($s['section']); ?>
                                            <div class="acc-row subj-row" data-yr="<?= htmlspecialchars($yr) ?>"
                                                data-title="<?= strtolower(htmlspecialchars($s['course_name'] . ' ' . $s['course_code'])) ?>">
                                                <div class="acc-row-left">
                                                    <div class="acc-row-ico" style="background:#e8f0ff;color:#003087">[Book]</div>
                                                    <div>
                                                        <div class="acc-row-title"><?= htmlspecialchars($s['course_name']) ?></div>
                                                        <?php if ($s['description']): ?>
                                                            <div class="acc-row-sub"><?= htmlspecialchars($s['description']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="acc-row-cell"><span
                                                        class="badge badge-blue"><?= htmlspecialchars($s['course_code']) ?></span>
                                                </div>
                                                <div class="acc-row-cell"><span class="badge"
                                                        style="background:<?= $sb ?>;color:<?= $sc ?>"><?= htmlspecialchars($s['section']) ?></span>
                                                </div>
                                                <div class="acc-row-cell"><?= htmlspecialchars($s['semester']) ?> Sem</div>
                                                <div class="acc-row-action">
                                                    <span class="badge badge-blue">[Students] <?= $sCnt ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="panel-sidebar">
                            <div class="sidebar-card">
                                <h4>[Book] Subject Summary</h4>
                                <div style="text-align:center;margin-bottom:1rem">
                                    <div
                                        style="font-family:'Nunito',sans-serif;font-size:2.8rem;font-weight:900;color:#003087">
                                        <?= count($mySubjects) ?>
                                    </div>
                                    <div style="font-size:.78rem;color:#aaa">total assignments</div>
                                </div>
                                <?php foreach ($subjectsByYear as $yr => $subs): ?>
                                    <div class="legend-row">
                                        <span><span class="legend-dot"
                                                style="background:#003087"></span><?= htmlspecialchars($yr) ?></span>
                                        <strong style="color:#003087"><?= count($subs) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="sidebar-card" style="font-size:.78rem;color:#888;line-height:1.75">
                                <h4 style="margin-bottom:.5rem">[i] Note</h4>
                                Subjects are assigned by the administrator. Contact admin if you notice any discrepancies.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!--  MODULES  -->
            <div class="section-panel" id="panel-modules">
                <div class="panel-header">
                    <div class="page-title">Modules</div>
                    <div class="page-sub">Upload and manage learning materials for your subjects.</div>
                </div>

                <?php if (empty($mySubjects)): ?>
                    <div class="card-panel">
                        <div class="card-panel-body">
                            <div class="empty-state"><span class="empty-icon">[Books]</span>No subjects assigned yet.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mod-toolbar">
                        <div class="form-row">
                            <div class="form-grp">
                                <label class="form-lbl">Select Subject</label>
                                <select class="finput" id="mod-course-select" onchange="loadModules()">
                                    <option value="">— Choose Subject —</option>
                                    <?php foreach ($mySubjects as $s): ?>
                                        <option value="<?= $s['course_id'] ?>">
                                            <?= htmlspecialchars($s['course_code'] . ' — ' . $s['course_name'] . ' (' . $s['section'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button class="btn btn-green" onclick="openModModal()">+ Add Module</button>
                            <button class="btn btn-blue btn-sm" onclick="loadModuleProgress()" id="view-progress-btn"
                                style="display:none">[Chart] Student Progress</button>
                        </div>
                    </div>

                    <div id="mod-list-wrap">
                        <div
                            style="background:white;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.07);overflow:hidden;margin-bottom:1.1rem">
                            <div style="padding:3rem;text-align:center;color:#bbb">
                                <div style="font-size:2.5rem;margin-bottom:.5rem">[Folder]</div>
                                <div>Select a subject above to view its modules.</div>
                            </div>
                        </div>
                    </div>

                    <div id="mod-progress-wrap" style="display:none;margin-top:1.25rem">
                        <div
                            style="background:white;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,.07);overflow:hidden">
                            <div
                                style="padding:1.1rem 1.5rem;border-bottom:1px solid #f0f2f5;display:flex;align-items:center;justify-content:space-between">
                                <h3 style="font-family:'Nunito',sans-serif;font-size:.95rem;font-weight:800;color:#1a1a2e">
                                    [Chart] Student Module Progress</h3>
                                <button class="btn btn-gray btn-sm"
                                    onclick="document.getElementById('mod-progress-wrap').style.display='none'">Hide</button>
                            </div>
                            <div id="mod-progress-table">
                                <div class="empty-state">Loading...</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!--  ASSIGNMENTS  -->
            <div class="section-panel" id="panel-assignments">
                <div class="panel-header">
                    <div class="page-title">Assignments</div>
                    <div class="page-sub">Create and manage assignments for your subjects.</div>
                </div>

                <?php if (empty($mySubjects)): ?>
                    <div class="card-panel">
                        <div class="card-panel-body">
                            <div class="empty-state"><span class="empty-icon">[Books]</span>No subjects assigned yet.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mod-toolbar">
                        <div class="form-row">
                            <div class="form-grp">
                                <label class="form-lbl">Select Subject</label>
                                <select class="finput" id="assign-course-select" onchange="loadAssignments()">
                                    <option value="">— Choose Subject —</option>
                                    <?php foreach ($mySubjects as $s): ?>
                                        <option value="<?= $s['course_id'] ?>">
                                            <?= htmlspecialchars($s['course_code'] . ' — ' . $s['course_name'] . ' (' . $s['section'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button class="btn btn-green" onclick="openAssignModal()">+ Create Assignment</button>
                        </div>
                    </div>

                    <div id="assign-list-wrap">
                        <div
                            style="background:white;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.07);overflow:hidden;margin-bottom:1.1rem">
                            <div style="padding:3rem;text-align:center;color:#bbb">
                                <div style="font-size:2.5rem;margin-bottom:.5rem">[Clipboard]</div>
                                <div>Select a subject above to view its assignments.</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!--  QUIZZES  -->
            <div class="section-panel" id="panel-quizzes">
                <div class="panel-header">
                    <div class="page-title">Quizzes</div>
                    <div class="page-sub">Create and manage quizzes for your subjects.</div>
                </div>

                <?php if (empty($mySubjects)): ?>
                    <div class="card-panel">
                        <div class="card-panel-body">
                            <div class="empty-state"><span class="empty-icon">[Quiz]</span>No subjects assigned yet.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mod-toolbar">
                        <div class="form-row">
                            <div class="form-grp">
                                <label class="form-lbl">Select Subject</label>
                                <select class="finput" id="quiz-course-select" onchange="loadQuizzes()">
                                    <option value="">— Choose Subject —</option>
                                    <?php foreach ($mySubjects as $s): ?>
                                        <option value="<?= $s['course_id'] ?>">
                                            <?= htmlspecialchars($s['course_code'] . ' — ' . $s['course_name'] . ' (' . $s['section'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button class="btn btn-green" onclick="openQuizModal()">+ Create Quiz</button>
                        </div>
                    </div>

                    <div id="quiz-list-wrap">
                        <div
                            style="background:white;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.07);overflow:hidden;margin-bottom:1.1rem">
                            <div style="padding:3rem;text-align:center;color:#bbb">
                                <div style="font-size:2.5rem;margin-bottom:.5rem">[Quiz]</div>
                                <div>Select a subject above to view its quizzes.</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!--  ANNOUNCEMENTS  -->
            <div class="section-panel" id="panel-announcements">
                <div class="panel-header">
                    <div class="page-title">Announcements</div>
                    <div class="page-sub">Send announcements to students enrolled in your subjects.</div>
                </div>

                <?php if (empty($mySubjects)): ?>
                    <div class="card-panel">
                        <div class="card-panel-body">
                            <div class="empty-state"><span class="empty-icon">[Announce]</span>No subjects assigned yet. You need at least one subject to send announcements.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Compose toolbar -->
                    <div class="mod-toolbar" style="margin-bottom:1.5rem">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
                            <span style="font-family:'Nunito',sans-serif;font-weight:800;font-size:.92rem;color:#1a1a2e">[Announce] Send New Announcement</span>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:.85rem">
                            <div class="form-row" style="align-items:flex-start">
                                <div class="form-grp" style="flex:1">
                                    <label class="form-lbl">Target Subject / Class *</label>
                                    <div style="border:1px solid #d1d5db;border-radius:8px;padding:.6rem .9rem;background:#fff;max-height:160px;overflow-y:auto">
                                        <!-- Select All -->
                                        <label style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;font-weight:700;color:#1a1a2e;padding-bottom:.4rem;border-bottom:1px solid #eee;margin-bottom:.4rem;cursor:pointer">
                                            <input type="checkbox" id="ann-select-all" onchange="toggleAllSubjects(this)">
                                            [Books] All My Subjects
                                        </label>
                                        <?php foreach ($mySubjects as $s): ?>
                                            <label style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;color:#374151;padding:.2rem 0;cursor:pointer">
                                                <input type="checkbox" class="ann-subject-cb" value="<?= $s['course_id'] ?>"
                                                    data-label="<?= htmlspecialchars($s['course_code'] . ' — ' . $s['course_name']) ?>">
                                                <?= htmlspecialchars($s['course_code'] . ' — ' . $s['course_name'] . ' (' . $s['section'] . ')') ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="form-grp">
                                    <label class="form-lbl">Announcement Title *</label>
                                    <input class="finput" type="text" id="ann-title" placeholder="e.g. Quiz Reminder — Chapter 3">
                                </div>
                            </div>
                            <div class="form-grp">
                                <label class="form-lbl">Message *</label>
                                <textarea class="finput" id="ann-body" rows="4"
                                    placeholder="Type your announcement here. Students enrolled in the selected subject will see this."></textarea>
                            </div>
                            <div id="ann-err" style="background:#fff0ec;border:1px solid #ffcfbf;color:#c0392b;font-size:.78rem;font-weight:600;padding:.6rem .9rem;border-radius:8px;display:none"></div>
                            <div style="display:flex;justify-content:flex-end">
                                <button class="btn btn-green" onclick="sendAnnouncement()" id="ann-send-btn">[Announce] Send Announcement</button>
                            </div>
                        </div>
                    </div>

                    <!-- Sent announcements list -->
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
                        <div style="font-family:'Nunito',sans-serif;font-weight:800;font-size:1rem;color:#1a1a2e">[Clipboard] Sent Announcements</div>
                        <select class="search-input" id="ann-filter-course" onchange="filterAnnouncements()" style="min-width:200px">
                            <option value="">All Subjects</option>
                            <?php foreach ($mySubjects as $s): ?>
                                <option value="<?= $s['course_id'] ?>"><?= htmlspecialchars($s['course_code'] . ' — ' . $s['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="ann-list">
                        <?php if (empty($myAnnouncements)): ?>
                            <div class="card-panel">
                                <div class="card-panel-body">
                                    <div class="empty-state"><span class="empty-icon"></span>No announcements sent yet. Use the form above to send your first one!</div>
                                </div>
                            </div>
                        <?php else:
                            foreach ($myAnnouncements as $a): ?>
                                <div class="ann-item" data-course="<?= $a['course_id'] ?>">
                                    <div class="ann-item-title"><?= htmlspecialchars($a['title']) ?></div>
                                    <div class="ann-item-body"><?= htmlspecialchars($a['body']) ?></div>
                                    <div class="ann-item-meta">
                                        <span class="badge badge-blue">[Book] <?= htmlspecialchars($a['course_code'] . ' — ' . $a['course_name']) ?></span>
                                        <span> <?= $a['posted_at'] ?></span>
                                        <button onclick="deleteAnnouncement(<?= $a['id'] ?>, this)"
                                            style="background:none;border:none;color:#c0392b;font-size:.72rem;font-weight:700;cursor:pointer;padding:0"> Delete</button>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!--  MY STUDENTS  -->
            <div class="section-panel" id="panel-students">
                <div class="panel-header">
                    <div class="page-title">My Students</div>
                    <div class="page-sub">Students grouped by section / grade level.</div>
                </div>

                <?php if (empty($studentsBySection)): ?>
                    <div class="card-panel">
                        <div class="card-panel-body">
                            <div class="empty-state"><span class="empty-icon">[Students]</span>No students enrolled yet.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="filter-bar">
                        <input class="search-input" id="stu-search" type="text" placeholder=" Search students..."
                            oninput="filterStudents(this.value)" style="flex:1;min-width:200px">
                        <select class="search-input" id="stu-filter-sec"
                            onchange="filterStudents(document.getElementById('stu-search').value)">
                            <option value="">All Sections</option>
                            <?php foreach (array_keys($studentsBySection) as $sec): ?>
                                <option value="<?= htmlspecialchars($sec) ?>"><?= htmlspecialchars($sec) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="students-outer">
                        <div>
                            <?php $secIdx = 0;
                            foreach ($studentsBySection as $sec => $stus):
                                [$color, $bg] = sectionColor($sec);
                                $bid = 'ssb' . $secIdx;
                                $secIdx++;
                                $sl = strtolower($sec);
                                $emoji = str_contains($sl, '12') ? '[Grad]' : (str_contains($sl, '11') ? '[Grad]' : (str_contains($sl, '10') ? '' : (str_contains($sl, '9') ? '' : (str_contains($sl, '8') ? '' : (str_contains($sl, '7') ? '' : '[Books]')))));
                                preg_match('/grade\s*\d+/i', $sec, $gm);
                                $gradeLabel = $gm[0] ?? 'Section'; ?>
                                <div class="stu-block" data-sec="<?= htmlspecialchars(strtolower($sec)) ?>">
                                    <div class="stu-header" onclick="toggleStu('<?= $bid ?>')">
                                        <div class="stu-swatch" style="background:<?= $bg ?>"><?= $emoji ?></div>
                                        <div class="stu-info">
                                            <div class="stu-grade" style="color:<?= $color ?>">
                                                <?= htmlspecialchars($gradeLabel) ?>
                                            </div>
                                            <div class="stu-sec-name"><?= htmlspecialchars($sec) ?></div>
                                            <div class="stu-count"><?= count($stus) ?>
                                                student<?= count($stus) != 1 ? 's' : '' ?> enrolled</div>
                                        </div>
                                        <div class="stu-right">
                                            <span class="stu-count-badge" style="color:<?= $color ?>"><?= count($stus) ?></span>
                                            <span
                                                style="font-size:.68rem;font-weight:700;padding:.18rem .55rem;border-radius:100px;background:<?= $bg ?>;color:<?= $color ?>">students</span>
                                            <span class="stu-chevron" id="chev-<?= $bid ?>"></span>
                                        </div>
                                    </div>
                                    <div class="stu-body" id="<?= $bid ?>">
                                        <div class="stu-sec-head">
                                            <span>Student Name</span><span style="text-align:center">LRN / School
                                                ID</span><span>Subjects</span>
                                        </div>
                                        <?php foreach ($stus as $st):
                                            $initials = strtoupper(substr($st['first_name'], 0, 1) . substr($st['last_name'], 0, 1)); ?>
                                            <div class="stu-row" data-sec="<?= htmlspecialchars(strtolower($sec)) ?>"
                                                data-name="<?= strtolower(htmlspecialchars($st['last_name'] . ' ' . $st['first_name'])) ?>">
                                                <div style="display:flex;align-items:center;gap:.65rem">
                                                    <div class="stu-avatar" style="background:<?= $color ?>"><?= $initials ?></div>
                                                    <div style="font-size:.84rem;font-weight:700;color:#1a1a2e">
                                                        <?= htmlspecialchars($st['last_name'] . ', ' . $st['first_name']) ?>
                                                    </div>
                                                </div>
                                                <div style="font-size:.78rem;color:#666;text-align:center">
                                                    <?= htmlspecialchars($st['school_id'] ?: '—') ?>
                                                </div>
                                                <div style="display:flex;flex-wrap:wrap;gap:.25rem">
                                                    <?php foreach (array_unique($st['subjects']) as $subj): ?>
                                                        <span class="badge badge-blue"><?= htmlspecialchars($subj) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="stu-sidebar">
                            <div class="stu-sidebar-card">
                                <h4>[Students] Section Summary</h4>
                                <div style="text-align:center;margin-bottom:1rem">
                                    <div
                                        style="font-family:'Nunito',sans-serif;font-size:2.8rem;font-weight:900;color:#003087">
                                        <?= $statStudents ?>
                                    </div>
                                    <div style="font-size:.78rem;color:#aaa">total students</div>
                                </div>
                                <?php foreach ($studentsBySection as $sec => $stus):
                                    [$color, $bg] = sectionColor($sec); ?>
                                    <div class="sec-legend-row">
                                        <span><span class="sec-dot"
                                                style="background:<?= $color ?>"></span><?= htmlspecialchars($sec) ?></span>
                                        <strong style="color:<?= $color ?>"><?= count($stus) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="stu-sidebar-card" style="font-size:.78rem;color:#888;line-height:1.75">
                                <h4 style="margin-bottom:.5rem">[i] How to use</h4>
                                Click a section to expand and see all students. Use the search bar to find a specific
                                student.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!--  GRADES  -->
            <div class="section-panel" id="panel-grades">
                <div class="panel-header">
                    <div class="page-title">Grades</div>
                    <div class="page-sub">View and manage student grades for assignments and assessments.</div>
                </div>

                <?php if (empty($mySubjects)): ?>
                    <div class="card-panel">
                        <div class="card-panel-body">
                            <div class="empty-state"><span class="empty-icon">[Chart]</span>No subjects assigned yet.</div>
                        </div>
                    </div>
                <?php else: ?>

                    <!-- Toolbar -->
                    <div class="mod-toolbar">
                        <div class="form-row" style="flex-wrap:wrap;gap:.75rem">
                            <div class="form-grp">
                                <label class="form-lbl">Select Subject</label>
                                <select class="finput" id="grade-course-select" onchange="loadGrades()"
                                    style="min-width:280px">
                                    <option value="">— Choose Subject —</option>
                                    <?php foreach ($mySubjects as $s): ?>
                                        <option value="<?= $s['course_id'] ?>">
                                            <?= htmlspecialchars($s['course_code'] . ' — ' . $s['course_name'] . ' (' . $s['section'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button class="btn btn-blue" onclick="exportGrades()"> Export</button>
                            <button class="btn btn-blue" onclick="showToast('Import coming soon!')"> Import</button>
                            <button class="btn btn-green" onclick="openAddGradeEntry()">+ Add Grade Entry</button>
                        </div>
                    </div>

                    <!-- Main layout -->
                    <div style="display:grid;grid-template-columns:1fr 280px;gap:1.5rem;align-items:start"
                        id="grades-outer">

                        <!-- Left: Table + Distribution -->
                        <div>
                            <div id="grades-table-wrap">
                                <div
                                    style="background:white;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.07);padding:3rem;text-align:center;color:#bbb">
                                    <div style="font-size:2.5rem;margin-bottom:.5rem">[Chart]</div>
                                    <div>Select a subject above to view grades.</div>
                                </div>
                            </div>

                            <!-- Grade Distribution -->
                            <div id="grade-dist-wrap"
                                style="display:none;background:white;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,.07);padding:1.4rem;margin-top:1.25rem">
                                <div
                                    style="font-family:'Nunito',sans-serif;font-weight:800;font-size:.95rem;color:#1a1a2e;margin-bottom:1rem">
                                    [Chart] Grade Distribution</div>
                                <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.6rem"
                                    id="grade-dist-grid"></div>
                            </div>
                        </div>

                        <!-- Right: Summary + Quick Actions -->
                        <div style="display:flex;flex-direction:column;gap:1rem;position:sticky;top:88px">

                            <!-- Summary Card -->
                            <div class="sidebar-card">
                                <h4>[Clipboard] Grade Summary</h4>
                                <div style="text-align:center;margin-bottom:1rem">
                                    <div style="font-family:'Nunito',sans-serif;font-size:2.6rem;font-weight:900;color:#003087"
                                        id="gs-total">—</div>
                                    <div style="font-size:.75rem;color:#aaa">Total Students</div>
                                </div>
                                <div class="legend-row"><span>Graded</span><strong style="color:#003087"
                                        id="gs-graded">—</strong></div>
                                <div class="legend-row"><span>Pending</span><strong style="color:#b38600"
                                        id="gs-pending">—</strong></div>
                                <div class="legend-row"><span>Class Average</span><strong style="color:#003087"
                                        id="gs-avg">—</strong></div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="sidebar-card">
                                <h4> Quick Actions</h4>
                                <button onclick="generateReportCard()"
                                    style="display:flex;align-items:center;gap:.55rem;padding:.65rem .9rem;border-radius:10px;background:#f8f9fc;border:1.5px solid #f0f2f5;cursor:pointer;font-size:.8rem;font-weight:700;color:#1a1a2e;width:100%;margin-bottom:.5rem;transition:all .2s;font-family:'Nunito',sans-serif"
                                    onmouseover="this.style.background='#e8f0ff';this.style.borderColor='#c5d8ff';this.style.color='#003087'"
                                    onmouseout="this.style.background='#f8f9fc';this.style.borderColor='#f0f2f5';this.style.color='#1a1a2e'">
                                    <span style="font-size:1.05rem"></span> Generate Report Card
                                </button>
                                <button onclick="notifyStudents()"
                                    style="display:flex;align-items:center;gap:.55rem;padding:.65rem .9rem;border-radius:10px;background:#f8f9fc;border:1.5px solid #f0f2f5;cursor:pointer;font-size:.8rem;font-weight:700;color:#1a1a2e;width:100%;margin-bottom:.5rem;transition:all .2s;font-family:'Nunito',sans-serif"
                                    onmouseover="this.style.background='#e8f0ff';this.style.borderColor='#c5d8ff';this.style.color='#003087'"
                                    onmouseout="this.style.background='#f8f9fc';this.style.borderColor='#f0f2f5';this.style.color='#1a1a2e'">
                                    <span style="font-size:1.05rem"></span> Notify Students
                                </button>
                                <button onclick="exportGrades()"
                                    style="display:flex;align-items:center;gap:.55rem;padding:.65rem .9rem;border-radius:10px;background:#f8f9fc;border:1.5px solid #f0f2f5;cursor:pointer;font-size:.8rem;font-weight:700;color:#1a1a2e;width:100%;transition:all .2s;font-family:'Nunito',sans-serif"
                                    onmouseover="this.style.background='#e8f0ff';this.style.borderColor='#c5d8ff';this.style.color='#003087'"
                                    onmouseout="this.style.background='#f8f9fc';this.style.borderColor='#f0f2f5';this.style.color='#1a1a2e'">
                                    <span style="font-size:1.05rem">[Chart]</span> Export to Excel
                                </button>
                            </div>

                            <!-- Info -->
                            <div class="sidebar-card" style="font-size:.78rem;color:#888;line-height:1.75">
                                <h4 style="margin-bottom:.5rem">[i] How to use</h4>
                                Click any score cell to edit. Press <strong>Save Grades</strong> to commit all changes. The
                                class average updates automatically.
                            </div>
                        </div>
                    </div>

                <?php endif; ?>
            </div>

            <!--  ATTENDANCE  -->
            <div class="section-panel" id="panel-attendance">
                <div class="panel-header">
                    <div class="page-title">Attendance</div>
                    <div class="page-sub">Record and track student attendance per subject and date.</div>
                </div>

                <?php if (empty($mySubjects)): ?>
                    <div class="card-panel">
                        <div class="card-panel-body">
                            <div class="empty-state"><span class="empty-icon">[Books]</span>No subjects assigned yet.</div>
                        </div>
                    </div>
                <?php else: ?>
                <div style="display:grid;grid-template-columns:1fr 280px;gap:1.5rem;align-items:start">

                    <!-- LEFT: Controls + Table -->
                    <div>
                        <div class="filter-bar" style="margin-bottom:1.25rem;gap:.75rem;flex-wrap:wrap">
                            <select class="search-input" id="att-course-select" onchange="loadAttendance()" style="flex:1;min-width:200px">
                                <option value="">— Choose Subject —</option>
                                <?php foreach ($mySubjects as $s): ?>
                                    <option value="<?= $s['course_id'] ?>">
                                        <?= htmlspecialchars($s['course_code'] . ' — ' . $s['course_name'] . ' (' . $s['section'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="date" class="search-input" id="att-date" value="<?= date('Y-m-d') ?>" onchange="loadAttendance()" style="width:160px">
                            <button class="btn btn-green" onclick="saveAttendance()"> Save Attendance</button>
                            <button class="btn btn-blue" onclick="exportAttendance()"> Export</button>
                        </div>

                        <div id="att-table-wrap">
                            <div style="background:white;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.07);padding:3rem;text-align:center;color:#bbb">
                                <div style="font-size:2.5rem;margin-bottom:.5rem">[Check]</div>
                                <div>Select a subject and date to take attendance.</div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Summary + Legend -->
                    <div style="display:flex;flex-direction:column;gap:1rem;position:sticky;top:88px">
                        <div class="sidebar-card">
                            <h4>[Clipboard] Session Summary</h4>
                            <div style="text-align:center;margin-bottom:1rem">
                                <div style="font-family:'Nunito',sans-serif;font-size:2.6rem;font-weight:900;color:#003087" id="att-total">—</div>
                                <div style="font-size:.75rem;color:#aaa">Total Students</div>
                            </div>
                            <div class="legend-row"><span>Present</span><strong style="color:#00875a" id="att-present">—</strong></div>
                            <div class="legend-row"><span>Absent</span><strong style="color:#c0392b" id="att-absent">—</strong></div>
                            <div class="legend-row"><span>Late</span><strong style="color:#b38600" id="att-late">—</strong></div>
                            <div class="legend-row"><span>Excused</span><strong style="color:#7b2d8b" id="att-excused">—</strong></div>
                        </div>

                        <div class="sidebar-card">
                            <h4> Status Legend</h4>
                            <div style="display:flex;flex-direction:column;gap:.45rem;font-size:.8rem">
                                <div style="display:flex;align-items:center;gap:.5rem"><span style="width:10px;height:10px;border-radius:50%;background:#00875a;display:inline-block"></span><span>Present — attended on time</span></div>
                                <div style="display:flex;align-items:center;gap:.5rem"><span style="width:10px;height:10px;border-radius:50%;background:#c0392b;display:inline-block"></span><span>Absent — did not attend</span></div>
                                <div style="display:flex;align-items:center;gap:.5rem"><span style="width:10px;height:10px;border-radius:50%;background:#b38600;display:inline-block"></span><span>Late — arrived after start</span></div>
                                <div style="display:flex;align-items:center;gap:.5rem"><span style="width:10px;height:10px;border-radius:50%;background:#7b2d8b;display:inline-block"></span><span>Excused — valid reason</span></div>
                            </div>
                        </div>

                        <div class="sidebar-card">
                            <h4> Quick Actions</h4>
                            <button onclick="markAll('Present')" style="display:flex;align-items:center;gap:.55rem;padding:.65rem .9rem;border-radius:10px;background:#f8f9fc;border:1.5px solid #f0f2f5;cursor:pointer;font-size:.8rem;font-weight:700;color:#1a1a2e;width:100%;margin-bottom:.5rem;transition:all .2s;font-family:'Nunito',sans-serif" onmouseover="this.style.background='#e6fff4';this.style.borderColor='#a7f3d0';this.style.color='#00875a'" onmouseout="this.style.background='#f8f9fc';this.style.borderColor='#f0f2f5';this.style.color='#1a1a2e'">
                                <span>[Check]</span> Mark All Present
                            </button>
                            <button onclick="markAll('Absent')" style="display:flex;align-items:center;gap:.55rem;padding:.65rem .9rem;border-radius:10px;background:#f8f9fc;border:1.5px solid #f0f2f5;cursor:pointer;font-size:.8rem;font-weight:700;color:#1a1a2e;width:100%;transition:all .2s;font-family:'Nunito',sans-serif" onmouseover="this.style.background='#fff0f0';this.style.borderColor='#fbd5d5';this.style.color='#c0392b'" onmouseout="this.style.background='#f8f9fc';this.style.borderColor='#f0f2f5';this.style.color='#1a1a2e'">
                                <span></span> Mark All Absent
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!--  MY PROFILE  -->
            <div class="section-panel" id="panel-profile">
                <div class="panel-header">
                    <div class="page-title">My Profile</div>
                    <div class="page-sub">View and update your personal information.</div>
                </div>
                <?php include 'shared/components/profile_panel.php'; ?>
            </div>

            <?php include 'shared/components/chatbot.php'; ?>

        </main>
        <div class="footer-bar">© 2026 Arandia College eLMS — SHS &amp; HS Teacher Portal</div>
    </div><!-- end app-body -->
    <div class="toast" id="toast"></div>


    <!--  MODULE MODAL  -->
    <div class="modal-overlay" id="modModal">
        <div class="modal">
            <div class="modal-head">
                <h2 id="mod-modal-title">[Folder] Add Module</h2>
                <button class="modal-close" onclick="closeModal('modModal')"></button>
            </div>
            <div class="modal-body">
                <div class="modal-err" id="mod-err"></div>
                <input type="hidden" id="mod-edit-id">
                <div class="form-grp"><label class="form-lbl">Subject *</label><select class="finput"
                        id="mod-course"></select></div>
                <div class="form-grp"><label class="form-lbl">Module Title *</label><input class="finput" type="text"
                        id="mod-title" placeholder="e.g. Week 1 — Introduction"></div>
                <div class="form-grp"><label class="form-lbl">Description</label><textarea class="finput" id="mod-desc"
                        rows="3" placeholder="Short description..."></textarea></div>
                <div class="form-2col">
                    <div class="form-grp"><label class="form-lbl">Week Number</label><input class="finput" type="number"
                            id="mod-week" min="1" max="20" placeholder="e.g. 1"></div>
                    <div class="form-grp"
                        style="flex-direction:row;align-items:center;gap:.5rem;padding-top:1.2rem;justify-content:flex-start">
                        <input type="checkbox" id="mod-published" style="width:18px;height:18px;accent-color:#003087">
                        <label class="form-lbl" for="mod-published" style="margin:0">Publish immediately</label>
                    </div>
                </div>
                <div class="form-grp">
                    <label class="form-lbl">Attach / Replace File <span style="color:#aaa;font-weight:400">(leave blank
                            to keep existing)</span></label>
                    <input class="finput" type="file" id="mod-file"
                        accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.zip">
                </div>
                <div id="mod-current-file" style="display:none;font-size:.75rem;color:#aaa;margin-top:-.4rem">
                    Current file: <a id="mod-current-file-link" href="#" target="_blank"
                        style="color:#003087;font-weight:700">View</a>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-gray" onclick="closeModal('modModal')">Cancel</button>
                <button class="btn btn-green" onclick="submitModule()"> Save Module</button>
            </div>
        </div>
    </div>

    <!--  ASSIGNMENT MODAL  -->
    <div class="modal-overlay" id="assignModal">
        <div class="modal">
            <div class="modal-head">
                <h2 id="assign-modal-title">[Clipboard] Create Assignment</h2>
                <button class="modal-close" onclick="closeModal('assignModal')"></button>
            </div>
            <div class="modal-body">
                <div class="modal-err" id="assign-err"></div>
                <input type="hidden" id="assign-edit-id">
                <div class="form-grp"><label class="form-lbl">Subject *</label><select class="finput"
                        id="assign-course"></select></div>
                <div class="form-grp"><label class="form-lbl">Assignment Title *</label><input class="finput"
                        type="text" id="assign-title" placeholder="e.g. Research Paper — Chapter 1"></div>
                <div class="form-grp"><label class="form-lbl">Instructions / Description</label><textarea class="finput"
                        id="assign-desc" rows="3" placeholder="Detailed instructions..."></textarea></div>
                <div class="form-2col">
                    <div class="form-grp"><label class="form-lbl">Due Date</label><input class="finput"
                            type="datetime-local" id="assign-due"></div>
                    <div class="form-grp"><label class="form-lbl">Max Score</label><input class="finput" type="number"
                            id="assign-score" value="100" min="1"></div>
                </div>
                <div class="form-grp">
                    <label class="form-lbl">Attach / Replace File <span style="color:#aaa;font-weight:400">(optional
                            reference)</span></label>
                    <input class="finput" type="file" id="assign-file"
                        accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.png,.zip">
                </div>
                <div id="assign-current-file" style="display:none;font-size:.75rem;color:#aaa;margin-top:-.4rem">
                    Current file: <a id="assign-current-file-link" href="#" target="_blank"
                        style="color:#003087;font-weight:700">View</a>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-gray" onclick="closeModal('assignModal')">Cancel</button>
                <button class="btn btn-green" onclick="submitAssignment()"> Save Assignment</button>
            </div>
        </div>
    </div>

    <!--  SUBMISSIONS / GRADING MODAL  -->
    <div class="modal-overlay" id="submissionsModal">
        <div class="modal" style="max-width:760px">
            <div class="modal-head">
                <h2 id="submissions-title">[Clipboard] Submissions</h2>
                <button class="modal-close" onclick="closeModal('submissionsModal')"></button>
            </div>
            <div class="modal-body" id="submissions-body" style="padding:0">Loading...</div>
        </div>
    </div>

    <!--  QUIZ MODAL  -->
    <div class="modal-overlay" id="quizModal">
        <div class="modal" style="max-width:800px">
            <div class="modal-head">
                <h2 id="quiz-modal-title">[Quiz] Create Quiz</h2>
                <button class="modal-close" onclick="closeModal('quizModal')"></button>
            </div>
            <div class="modal-body">
                <div class="modal-err" id="quiz-err"></div>
                <input type="hidden" id="quiz-edit-id">
                <div class="form-grp"><label class="form-lbl">Subject *</label><select class="finput"
                        id="quiz-course"></select></div>
                <div class="form-grp"><label class="form-lbl">Quiz Title *</label><input class="finput" type="text"
                        id="quiz-title" placeholder="e.g. Chapter 1 Quiz"></div>
                <div class="form-grp"><label class="form-lbl">Instructions / Description</label><textarea class="finput"
                        id="quiz-desc" rows="2" placeholder="Optional instructions..."></textarea></div>
                <div class="form-2col">
                    <div class="form-grp"><label class="form-lbl">Time Limit (minutes)</label><input class="finput"
                            type="number" id="quiz-time" min="1" placeholder="blank = unlimited"></div>
                    <div class="form-grp"><label class="form-lbl">Max Score</label><input class="finput" type="number"
                            id="quiz-maxscore" value="100" min="1"></div>
                    <div class="form-grp"><label class="form-lbl">Open At</label><input class="finput"
                            type="datetime-local" id="quiz-open"></div>
                    <div class="form-grp"><label class="form-lbl">Close At</label><input class="finput"
                            type="datetime-local" id="quiz-close"></div>
                </div>

                <div style="border-top:1px solid #f0f2f5;padding-top:1rem;margin-top:.25rem">
                    <div style="display:flex;align-items:center;gap:1.25rem;margin-bottom:1rem;flex-wrap:wrap">
                        <span
                            style="font-family:'Nunito',sans-serif;font-weight:800;font-size:.88rem;color:#1a1a2e">Quiz
                            Type:</span>
                        <label
                            style="display:flex;align-items:center;gap:.4rem;cursor:pointer;font-size:.83rem;font-weight:600">
                            <input type="radio" name="quiz-mode" value="questions" checked onchange="toggleQuizMode()"
                                style="accent-color:#003087">
                            [Notes] Question Builder (auto-graded)
                        </label>
                        <label
                            style="display:flex;align-items:center;gap:.4rem;cursor:pointer;font-size:.83rem;font-weight:600">
                            <input type="radio" name="quiz-mode" value="file" onchange="toggleQuizMode()"
                                style="accent-color:#003087">
                             File Upload (teacher grades)
                        </label>
                    </div>

                    <div id="quiz-questions-section">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
                            <span style="font-size:.8rem;color:#888">Students answer online. Scores are
                                auto-computed.</span>
                            <button class="btn btn-blue btn-sm" onclick="addQuestion()">+ Add Question</button>
                        </div>
                        <div id="questions-wrap"></div>
                    </div>

                    <div id="quiz-file-section" style="display:none">
                        <div style="background:#f8f9fc;border-radius:12px;padding:1.25rem;border:1.5px dashed #e0e4ee">
                            <div style="font-size:.84rem;font-weight:700;color:#1a1a2e;margin-bottom:.35rem"> Upload
                                Quiz File</div>
                            <div style="font-size:.75rem;color:#888;margin-bottom:.9rem">Students download this file,
                                complete it, then upload their work. You manually enter scores in <strong>Grade
                                    Submissions</strong>.</div>
                            <div class="form-grp" style="margin-bottom:.5rem">
                                <label class="form-lbl">Quiz File (PDF, DOCX…) *</label>
                                <input class="finput" type="file" id="quiz-file"
                                    accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
                            </div>
                            <div id="quiz-current-file"
                                style="display:none;font-size:.75rem;color:#aaa;margin-bottom:.5rem">
                                Current: <a id="quiz-current-file-link" href="#" target="_blank"
                                    style="color:#003087;font-weight:700">View file</a>
                            </div>
                            <div
                                style="padding:.6rem .9rem;background:#fffbe6;border-radius:8px;font-size:.75rem;color:#b38600;font-weight:600">
                                [!] No auto-grading — you must enter scores manually in Results &amp; Grading.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-gray" onclick="closeModal('quizModal')">Cancel</button>
                <button class="btn btn-green" onclick="submitQuiz()"> Save Quiz</button>
            </div>
        </div>
    </div>

    <!--  RESULTS & GRADING MODAL  -->
    <div class="modal-overlay" id="resultsModal">
        <div class="modal" style="max-width:760px">
            <div class="modal-head">
                <h2 id="results-title">[Chart] Results &amp; Grading</h2>
                <button class="modal-close" onclick="closeModal('resultsModal')"></button>
            </div>
            <div class="modal-body" id="results-body" style="padding:0">Loading...</div>
        </div>
    </div>

    <script>
        /*  UTILITIES  */
        function showToast(msg, err = false) { const t = document.getElementById('toast'); t.textContent = msg; t.style.background = err ? '#c0392b' : '#003087'; t.classList.add('show'); setTimeout(() => t.classList.remove('show'), 3500); }
        function closeModal(id) { document.getElementById(id).classList.remove('show'); }
        function openModal(id) { document.getElementById(id).classList.add('show'); }
        function showPanel(name, btn) { document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active')); document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active')); document.getElementById('panel-' + name)?.classList.add('active'); if (btn) btn.classList.add('active'); const el = document.getElementById('topbar-panel-name'); if (el && btn) el.textContent = btn.textContent.trim(); }
        function escHtml(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
        function emptyCard(icon, msg) { return `<div style="background:white;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.07);padding:3rem;text-align:center;color:#bbb"><div style="font-size:2.5rem;margin-bottom:.5rem">${icon}</div><div>${msg}</div></div>`; }
        function loadingCard() { return '<div style="padding:2rem;text-align:center;color:#aaa">Loading...</div>'; }

        /*  ACCORDION  */
        function toggleBlock(id) { const b = document.getElementById(id); const c = document.getElementById('chev-' + id); const o = b.classList.toggle('open'); c.classList.toggle('open', o); }
        function toggleStu(id) { toggleBlock(id); }
        function filterAcc(blockSel, rowSel, q, filterSelId) {
            q = (q || '').toLowerCase();
            const fv = filterSelId ? (document.getElementById(filterSelId)?.value || '') : '';
            document.querySelectorAll(blockSel).forEach(block => {
                const ba = block.dataset.yr || block.dataset.sec || '';
                if (fv && ba !== fv) { block.style.display = 'none'; return; }
                block.style.display = ''; let any = false;
                block.querySelectorAll(rowSel).forEach(row => {
                    const mQ = !q || (row.dataset.title || '').includes(q) || (row.dataset.yr || '').toLowerCase().includes(q);
                    row.style.display = mQ ? '' : 'none'; if (mQ) any = true;
                });
                if (q && !any) block.style.display = 'none';
                else if (q && any) { block.querySelector('.acc-body,.stu-body')?.classList.add('open'); block.querySelector('.acc-chevron,.stu-chevron')?.classList.add('open'); }
            });
        }
        function filterStudents(q) {
            q = (q || '').toLowerCase(); const sf = document.getElementById('stu-filter-sec')?.value || '';
            document.querySelectorAll('.stu-block').forEach(block => {
                const bs = block.dataset.sec || '';
                if (sf && bs !== sf.toLowerCase()) { block.style.display = 'none'; return; }
                block.style.display = ''; let any = false;
                block.querySelectorAll('.stu-row').forEach(row => { const mQ = !q || (row.dataset.name || '').includes(q); row.style.display = mQ ? '' : 'none'; if (mQ) any = true; });
                if (q) { if (any) { block.querySelector('.stu-body')?.classList.add('open'); block.querySelector('.stu-chevron')?.classList.add('open'); } else block.style.display = 'none'; }
            });
        }

        /*  ANNOUNCEMENTS  */
        // Toggle all subject checkboxes
        function toggleAllSubjects(masterCb) {
            document.querySelectorAll('.ann-subject-cb').forEach(cb => cb.checked = masterCb.checked);
        }

        async function sendAnnouncement() {
            const errEl = document.getElementById('ann-err');
            errEl.style.display = 'none';

            // Collect checked subjects
            const checked = [...document.querySelectorAll('.ann-subject-cb:checked')];
            const title   = document.getElementById('ann-title').value.trim();
            const body    = document.getElementById('ann-body').value.trim();

            if (checked.length === 0) { errEl.textContent = '[!] Please select at least one subject.'; errEl.style.display = 'block'; return; }
            if (!title) { errEl.textContent = '[!] Title is required.'; errEl.style.display = 'block'; return; }
            if (!body)  { errEl.textContent = '[!] Message cannot be empty.'; errEl.style.display = 'block'; return; }

            const btn = document.getElementById('ann-send-btn');
            btn.disabled = true;
            btn.textContent = checked.length > 1 ? `[...] Sending to ${checked.length} subjects...` : '[...] Sending...';

            const annList  = document.getElementById('ann-list');
            let successCount = 0;
            let errors = [];

            // Send one API call per selected subject
            for (const cb of checked) {
                const courseId    = cb.value;
                const courseLabel = cb.dataset.label;
                try {
                    const res  = await fetch('api/announcements.php?action=create', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ course_id: courseId, title, body })
                    });
                    const data = await res.json();
                    if (data.success) {
                        successCount++;
                        // Prepend to list
                        const emptyEl = annList.querySelector('.card-panel');
                        if (emptyEl) emptyEl.remove();
                        const div = document.createElement('div');
                        div.className = 'ann-item';
                        div.dataset.course = courseId;
                        div.innerHTML = `
                            <div class="ann-item-title">${escHtml(title)}</div>
                            <div class="ann-item-body">${escHtml(body)}</div>
                            <div class="ann-item-meta">
                                <span class="badge badge-blue">[Book] ${escHtml(courseLabel)}</span>
                                <span> Just now</span>
                                <button onclick="deleteAnnouncement(${data.id}, this)"
                                    style="background:none;border:none;color:#c0392b;font-size:.72rem;font-weight:700;cursor:pointer;padding:0"> Delete</button>
                            </div>`;
                        annList.insertBefore(div, annList.firstChild);
                    } else {
                        errors.push(courseLabel + ': ' + data.message);
                    }
                } catch(e) {
                    errors.push(courseLabel + ': Network error');
                }
            }

            // Reset form
            document.getElementById('ann-title').value = '';
            document.getElementById('ann-body').value  = '';
            document.querySelectorAll('.ann-subject-cb').forEach(cb => cb.checked = false);
            document.getElementById('ann-select-all').checked = false;

            if (successCount > 0) {
                showToast(successCount > 1
                    ? `[Check] Announcement sent to ${successCount} subjects!`
                    : '[Check] Announcement sent!');
            }
            if (errors.length > 0) {
                errEl.textContent = '[!] Some failed: ' + errors.join('; ');
                errEl.style.display = 'block';
            }

            btn.disabled = false; btn.textContent = '[Announce] Send Announcement';
        }

        async function deleteAnnouncement(id, btn) {
            if (!confirm('Delete this announcement? Students will no longer see it.')) return;
            try {
                const res  = await fetch(`api/announcements.php?action=delete&id=${id}`, { method: 'DELETE' });
                const data = await res.json();
                if (data.success) {
                    btn.closest('.ann-item').remove();
                    showToast(' Announcement deleted.');
                    if (!document.querySelector('.ann-item')) {
                        document.getElementById('ann-list').innerHTML = `
                            <div class="card-panel"><div class="card-panel-body">
                                <div class="empty-state"><span class="empty-icon"></span>No announcements sent yet.</div>
                            </div></div>`;
                    }
                } else {
                    showToast('[!] Could not delete.', true);
                }
            } catch(e) {
                showToast('[!] Network error.', true);
            }
        }

        function filterAnnouncements() {
            const cid = document.getElementById('ann-filter-course').value;
            document.querySelectorAll('#ann-list .ann-item').forEach(item => {
                item.style.display = (!cid || item.dataset.course == cid) ? '' : 'none';
            });
        }

        /*  SUBJECTS  */
        const mySubjectsJS = <?= json_encode(array_values(array_unique(array_map(function ($s) {
            return ['id' => $s['course_id'], 'label' => $s['course_code'] . ' — ' . $s['course_name'] . ' (' . $s['section'] . ')'];
        }, $mySubjects), SORT_REGULAR))) ?>;
        function populateCourseSelect(selId) { const sel = document.getElementById(selId); sel.innerHTML = '<option value="">— Choose Subject —</option>'; mySubjectsJS.forEach(s => { const o = document.createElement('option'); o.value = s.id; o.textContent = s.label; sel.appendChild(o); }); }

        /*  MODULES  */
        function moduleRow(m) {
            return `<div style="display:grid;grid-template-columns:1fr 65px 80px 80px 165px;gap:.5rem;align-items:center;padding:.85rem 1.25rem;border-bottom:1px solid #f8f9fc;transition:background .15s" onmouseover="this.style.background='#f6f9ff'" onmouseout="this.style.background=''">
        <div style="display:flex;align-items:center;gap:.7rem">
            <div style="width:32px;height:32px;border-radius:8px;background:${m.published == '1' ? '#e8f0ff' : '#f5f5f5'};display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0">${m.published == '1' ? '' : '[Notes]'}</div>
            <div><div style="font-size:.84rem;font-weight:700;color:#1a1a2e">${escHtml(m.title)}</div>${m.description ? `<div style="font-size:.7rem;color:#aaa;margin-top:.04rem">${escHtml(m.description)}</div>` : ''}</div>
        </div>
        <div style="font-size:.75rem;color:#666;text-align:center">${m.week_number ? 'Wk ' + m.week_number : '—'}</div>
        <div style="font-size:.72rem;color:#aaa;text-align:center">${m.created_at}</div>
        <div style="text-align:center"><span class="pub-badge ${m.published == '1' ? 'pub-yes' : 'pub-no'}">${m.published == '1' ? 'Published' : 'Draft'}</span></div>
        <div style="display:flex;justify-content:flex-end;gap:.3rem;flex-wrap:wrap">
            ${m.file_path ? `<a href="${m.file_path}" target="_blank" class="btn btn-blue btn-sm"> View</a>` : ''}
            <button class="btn btn-gray btn-sm" onclick='editModule(${JSON.stringify(m)})'> Edit</button>
            <button class="btn btn-gray btn-sm" onclick="togglePublish(${m.id})">${m.published == '1' ? 'Unpublish' : 'Publish'}</button>
            <button class="btn btn-red btn-sm" onclick="deleteModule(${m.id})"></button>
        </div>
    </div>`;
        }

        async function loadModules() {
            const cid = document.getElementById('mod-course-select').value;
            const wrap = document.getElementById('mod-list-wrap');
            const pBtn = document.getElementById('view-progress-btn');
            if (!cid) { wrap.innerHTML = emptyCard('[Folder]', 'Select a subject to view modules.'); if (pBtn) pBtn.style.display = 'none'; return; }
            wrap.innerHTML = loadingCard();
            const res = await fetch(`api/modules.php?action=list&course_id=${cid}`);
            const data = await res.json();
            if (!data.success || !data.data.length) { wrap.innerHTML = emptyCard('', 'No modules yet. Click <strong>+ Add Module</strong>.'); if (pBtn) pBtn.style.display = 'none'; return; }
            if (pBtn) pBtn.style.display = '';
            wrap.innerHTML = `<div style="background:white;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.07);overflow:hidden">
        <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f0f2f5;font-family:'Nunito',sans-serif;font-size:.95rem;font-weight:800;color:#1a1a2e">[Folder] Modules (${data.data.length})</div>
        <div style="display:grid;grid-template-columns:1fr 65px 80px 80px 165px;gap:.5rem;padding:.5rem 1.25rem;font-size:.65rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#bbb;background:#f8f9fc;border-bottom:1px solid #f0f2f5">
            <span>Module</span><span style="text-align:center">Week</span><span style="text-align:center">Posted</span><span style="text-align:center">Status</span><span style="text-align:right">Actions</span>
        </div>
        ${data.data.map(moduleRow).join('')}
    </div>`;
        }

        function openModModal() {
            document.getElementById('mod-modal-title').textContent = '[Folder] Add Module';
            document.getElementById('mod-edit-id').value = '';
            populateCourseSelect('mod-course');
            const sel = document.getElementById('mod-course-select').value;
            if (sel) document.getElementById('mod-course').value = sel;
            ['mod-title', 'mod-desc', 'mod-week'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('mod-published').checked = false;
            document.getElementById('mod-file').value = '';
            document.getElementById('mod-current-file').style.display = 'none';
            document.getElementById('mod-err').style.display = 'none';
            openModal('modModal');
        }

        function editModule(m) {
            document.getElementById('mod-modal-title').textContent = ' Edit Module';
            document.getElementById('mod-edit-id').value = m.id;
            populateCourseSelect('mod-course');
            document.getElementById('mod-course').value = m.course_id;
            document.getElementById('mod-title').value = m.title || '';
            document.getElementById('mod-desc').value = m.description || '';
            document.getElementById('mod-week').value = m.week_number || '';
            document.getElementById('mod-published').checked = m.published == '1';
            document.getElementById('mod-file').value = '';
            if (m.file_path) { document.getElementById('mod-current-file').style.display = ''; document.getElementById('mod-current-file-link').href = m.file_path; }
            else { document.getElementById('mod-current-file').style.display = 'none'; }
            document.getElementById('mod-err').style.display = 'none';
            openModal('modModal');
        }

        async function submitModule() {
            const err = document.getElementById('mod-err'); err.style.display = 'none';
            const editId = document.getElementById('mod-edit-id').value;
            const fd = new FormData();
            if (editId) fd.append('id', editId);
            fd.append('course_id', document.getElementById('mod-course').value);
            fd.append('title', document.getElementById('mod-title').value.trim());
            fd.append('description', document.getElementById('mod-desc').value.trim());
            fd.append('week_number', document.getElementById('mod-week').value);
            if (document.getElementById('mod-published').checked) fd.append('published', '1');
            const file = document.getElementById('mod-file').files[0]; if (file) fd.append('file', file);
            if (!fd.get('course_id') || !fd.get('title')) { err.textContent = '[!] Subject and title required.'; err.style.display = 'block'; return; }
            const res = await fetch(`api/modules.php?action=${editId ? 'update' : 'create'}`, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) { closeModal('modModal'); showToast(editId ? '[Check] Module updated!' : '[Check] Module added!'); loadModules(); }
            else { err.textContent = '[!] ' + data.message; err.style.display = 'block'; }
        }

        async function togglePublish(id) { const fd = new FormData(); fd.append('id', id); await fetch('api/modules.php?action=toggle', { method: 'POST', body: fd }); showToast('[Check] Module updated!'); loadModules(); }
        async function deleteModule(id) { if (!confirm('Delete this module?')) return; await fetch(`api/modules.php?action=delete&id=${id}`, { method: 'DELETE' }); showToast(' Module deleted.'); loadModules(); }

        async function loadModuleProgress() {
            const cid = document.getElementById('mod-course-select').value; if (!cid) return;
            document.getElementById('mod-progress-wrap').style.display = '';
            const wrap = document.getElementById('mod-progress-table'); wrap.innerHTML = loadingCard();
            const res = await fetch(`api/module_progress.php?action=course_progress&course_id=${cid}`);
            const data = await res.json();
            if (!data.success || !data.data.length) { wrap.innerHTML = '<div style="padding:2rem;text-align:center;color:#bbb">No students enrolled yet.</div>'; return; }
            wrap.innerHTML = `<table width="100%"><thead><tr><th>Student</th><th>Section</th><th>Progress</th><th>Done / Total</th></tr></thead><tbody>
        ${data.data.map(s => `<tr>
            <td><strong>${escHtml(s.last_name + ', ' + s.first_name)}</strong></td>
            <td>${escHtml(s.section_dept || '—')}</td>
            <td style="min-width:140px"><div style="background:#f0f2f5;border-radius:100px;height:8px;overflow:hidden"><div style="height:8px;border-radius:100px;background:#003087;width:${s.percent}%;transition:width .4s"></div></div>
            <span style="font-size:.72rem;font-weight:700;color:#003087">${s.percent}%</span></td>
            <td><strong>${s.done_count}</strong> / ${s.total}</td>
        </tr>`).join('')}</tbody></table>`;
        }

        /*  ASSIGNMENTS  */
        function assignRow(a) {
            const pendingBadge = (a.pending_grades > 0) ? `<span class="badge badge-yellow" style="font-size:.63rem">[!] ${a.pending_grades} ungraded</span>` : `<span class="badge badge-green" style="font-size:.63rem"> All graded</span>`;
            return `<div style="display:grid;grid-template-columns:1fr 100px 120px 65px 155px;gap:.5rem;align-items:center;padding:.85rem 1.25rem;border-bottom:1px solid #f8f9fc;transition:background .15s" onmouseover="this.style.background='#f6f9ff'" onmouseout="this.style.background=''">
        <div style="display:flex;align-items:center;gap:.7rem">
            <div style="width:32px;height:32px;border-radius:8px;background:#fffbe6;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0">[Clipboard]</div>
            <div><div style="font-size:.84rem;font-weight:700;color:#1a1a2e">${escHtml(a.title)}</div>
            ${a.description ? `<div style="font-size:.7rem;color:#aaa;margin-top:.04rem">${escHtml(a.description.substring(0, 55))}${a.description.length > 55 ? '…' : ''}</div>` : ''}
            ${a.file_path ? `<a href="${a.file_path}" target="_blank" style="font-size:.7rem;color:#003087;font-weight:700"> Reference</a>` : ''}</div>
        </div>
        <div style="font-size:.72rem;color:#666;text-align:center">${a.due_date ? a.due_date.replace('T', ' ').substring(0, 16) : '—'}</div>
        <div style="text-align:center"><span class="badge badge-blue">${a.submission_count || 0} submitted</span><br>${pendingBadge}</div>
        <div style="font-size:.78rem;font-weight:700;color:#003087;text-align:center">${a.max_score}</div>
        <div style="display:flex;justify-content:flex-end;gap:.3rem;flex-wrap:wrap">
            <button class="btn btn-green btn-sm" onclick="viewSubmissions(${a.id},'${escHtml(a.title)}',${a.max_score})">[Clipboard] Grade</button>
            <button class="btn btn-gray btn-sm" onclick='editAssignment(${JSON.stringify(a)})'></button>
            <button class="btn btn-red btn-sm" onclick="deleteAssignment(${a.id})"></button>
        </div>
    </div>`;
        }

        async function loadAssignments() {
            const cid = document.getElementById('assign-course-select').value;
            const wrap = document.getElementById('assign-list-wrap');
            if (!cid) { wrap.innerHTML = emptyCard('[Clipboard]', 'Select a subject to view assignments.'); return; }
            wrap.innerHTML = loadingCard();
            const res = await fetch(`api/assignments.php?action=list&course_id=${cid}`);
            const data = await res.json();
            if (!data.success || !data.data.length) { wrap.innerHTML = emptyCard('', 'No assignments yet. Click <strong>+ Create Assignment</strong>.'); return; }
            wrap.innerHTML = `<div style="background:white;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.07);overflow:hidden">
        <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f0f2f5;font-family:'Nunito',sans-serif;font-size:.95rem;font-weight:800;color:#1a1a2e">[Clipboard] Assignments (${data.data.length})</div>
        <div style="display:grid;grid-template-columns:1fr 100px 120px 65px 155px;gap:.5rem;padding:.5rem 1.25rem;font-size:.65rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#bbb;background:#f8f9fc;border-bottom:1px solid #f0f2f5">
            <span>Assignment</span><span style="text-align:center">Due</span><span style="text-align:center">Submissions</span><span style="text-align:center">Max</span><span style="text-align:right">Actions</span>
        </div>
        ${data.data.map(assignRow).join('')}
    </div>`;
        }

        function openAssignModal() {
            document.getElementById('assign-modal-title').textContent = '[Clipboard] Create Assignment';
            document.getElementById('assign-edit-id').value = '';
            populateCourseSelect('assign-course');
            const sel = document.getElementById('assign-course-select').value;
            if (sel) document.getElementById('assign-course').value = sel;
            ['assign-title', 'assign-desc', 'assign-due'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('assign-score').value = '100';
            document.getElementById('assign-file').value = '';
            document.getElementById('assign-current-file').style.display = 'none';
            document.getElementById('assign-err').style.display = 'none';
            openModal('assignModal');
        }

        function editAssignment(a) {
            document.getElementById('assign-modal-title').textContent = ' Edit Assignment';
            document.getElementById('assign-edit-id').value = a.id;
            populateCourseSelect('assign-course');
            document.getElementById('assign-course').value = a.course_id;
            document.getElementById('assign-title').value = a.title || '';
            document.getElementById('assign-desc').value = a.description || '';
            document.getElementById('assign-due').value = a.due_date ? a.due_date.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('assign-score').value = a.max_score || 100;
            document.getElementById('assign-file').value = '';
            if (a.file_path) { document.getElementById('assign-current-file').style.display = ''; document.getElementById('assign-current-file-link').href = a.file_path; }
            else { document.getElementById('assign-current-file').style.display = 'none'; }
            document.getElementById('assign-err').style.display = 'none';
            openModal('assignModal');
        }

        async function submitAssignment() {
            const err = document.getElementById('assign-err'); err.style.display = 'none';
            const editId = document.getElementById('assign-edit-id').value;
            const fd = new FormData();
            if (editId) fd.append('id', editId);
            fd.append('course_id', document.getElementById('assign-course').value);
            fd.append('title', document.getElementById('assign-title').value.trim());
            fd.append('description', document.getElementById('assign-desc').value.trim());
            fd.append('due_date', document.getElementById('assign-due').value);
            fd.append('max_score', document.getElementById('assign-score').value || 100);
            const file = document.getElementById('assign-file').files[0]; if (file) fd.append('file', file);
            if (!fd.get('course_id') || !fd.get('title')) { err.textContent = '[!] Subject and title required.'; err.style.display = 'block'; return; }
            const res = await fetch(`api/assignments.php?action=${editId ? 'update' : 'create'}`, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) { closeModal('assignModal'); showToast(editId ? '[Check] Assignment updated!' : '[Check] Assignment created!'); loadAssignments(); }
            else { err.textContent = '[!] ' + data.message; err.style.display = 'block'; }
        }

        async function deleteAssignment(id) { if (!confirm('Delete this assignment and all submissions?')) return; await fetch(`api/assignments.php?action=delete&id=${id}`, { method: 'DELETE' }); showToast(' Assignment deleted.'); loadAssignments(); }

        async function viewSubmissions(assignId, title, maxScore) {
            document.getElementById('submissions-title').textContent = '[Clipboard] ' + title + ' — Submissions';
            document.getElementById('submissions-body').innerHTML = loadingCard();
            openModal('submissionsModal');
            const res = await fetch(`api/assignments.php?action=submissions&id=${assignId}`);
            const data = await res.json();
            if (!data.success || !data.data.length) { document.getElementById('submissions-body').innerHTML = '<div style="padding:3rem;text-align:center;color:#bbb"><div style="font-size:2.5rem;margin-bottom:.5rem"></div>No submissions yet.</div>'; return; }
            document.getElementById('submissions-body').innerHTML = `
        <div style="padding:.5rem 0">
            <div style="display:grid;grid-template-columns:1fr 80px 80px 120px 130px;gap:.5rem;padding:.5rem 1.25rem;font-size:.65rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#bbb;background:#f8f9fc;border-bottom:1px solid #f0f2f5">
                <span>Student</span><span style="text-align:center">Date</span><span style="text-align:center">File</span><span style="text-align:center">Score / ${maxScore}</span><span style="text-align:center">Remarks</span>
            </div>
            ${data.data.map(s => `
            <div style="display:grid;grid-template-columns:1fr 80px 80px 120px 130px;gap:.5rem;align-items:center;padding:.85rem 1.25rem;border-bottom:1px solid #f8f9fc">
                <div><div style="font-size:.84rem;font-weight:700;color:#1a1a2e">${escHtml(s.last_name + ', ' + s.first_name)}</div><div style="font-size:.7rem;color:#aaa">${escHtml(s.section_dept || '')}</div></div>
                <div style="font-size:.72rem;color:#666;text-align:center">${s.submitted_at ? s.submitted_at.substring(0, 10) : '—'}</div>
                <div style="text-align:center">${s.file_path ? `<a href="${s.file_path}" target="_blank" class="btn btn-blue btn-sm"> View</a>` : '<span style="color:#ccc;font-size:.75rem">—</span>'}</div>
                <div style="text-align:center">
                    <input type="number" min="0" max="${maxScore}" step="0.5" value="${s.score ?? ''}" placeholder="—"
                        data-submission-id="${s.submission_id}" data-student-id="${s.student_id}" data-assign-id="${assignId}"
                        style="width:72px;padding:.3rem .5rem;border:1.5px solid #e0e4ee;border-radius:7px;font-size:.82rem;text-align:center;font-family:'Nunito',sans-serif;font-weight:700;outline:none"
                        onfocus="this.style.borderColor='#003087'" onblur="this.style.borderColor='#e0e4ee'">
                </div>
                <div style="text-align:center">
                    <input type="text" value="${escHtml(s.remarks || '')}" placeholder="Remarks" data-remarks-for="${s.submission_id}"
                        style="width:100%;padding:.3rem .5rem;border:1.5px solid #e0e4ee;border-radius:7px;font-size:.78rem;outline:none"
                        onfocus="this.style.borderColor='#003087'" onblur="this.style.borderColor='#e0e4ee'">
                </div>
            </div>`).join('')}
        </div>
        <div style="padding:1rem 1.25rem;background:#f8f9fc;border-top:1px solid #f0f2f5;display:flex;justify-content:flex-end;gap:.75rem">
            <button class="btn btn-gray btn-sm" onclick="closeModal('submissionsModal')">Close</button>
            <button class="btn btn-green" onclick="saveSubmissionGrades(${assignId})"> Save All Grades</button>
        </div>`;
        }

        async function saveSubmissionGrades(assignId) {
            const inputs = document.querySelectorAll('#submissions-body input[data-submission-id]');
            const grades = [];
            inputs.forEach(inp => { const ri = document.querySelector(`input[data-remarks-for="${inp.dataset.submissionId}"]`); grades.push({ submission_id: inp.dataset.submissionId, student_id: inp.dataset.studentId, assign_id: inp.dataset.assignId, score: inp.value, remarks: ri ? ri.value : '' }); });
            const res = await fetch('api/assignments.php?action=grade', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ grades }) });
            const data = await res.json();
            if (data.success) { showToast('[Check] Grades saved!'); closeModal('submissionsModal'); loadAssignments(); }
            else showToast('[!] Could not save grades.', true);
        }

        /*  QUIZZES  */
        function toggleQuizMode() {
            const mode = document.querySelector('input[name="quiz-mode"]:checked').value;
            document.getElementById('quiz-questions-section').style.display = mode === 'questions' ? '' : 'none';
            document.getElementById('quiz-file-section').style.display = mode === 'file' ? '' : 'none';
        }

        function quizRow(q) {
            const isFile = q.quiz_type === 'file';
            const typeBadge = isFile ? `<span class="badge badge-blue" style="font-size:.63rem"> File</span>` : `<span class="badge badge-green" style="font-size:.63rem">[Notes] Auto</span>`;
            return `<div style="display:grid;grid-template-columns:1fr 65px 70px 65px 75px 165px;gap:.5rem;align-items:center;padding:.85rem 1.25rem;border-bottom:1px solid #f8f9fc;transition:background .15s" onmouseover="this.style.background='#f6f9ff'" onmouseout="this.style.background=''">
        <div style="display:flex;align-items:center;gap:.7rem">
            <div style="width:32px;height:32px;border-radius:8px;background:#e8f0ff;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0">${isFile ? '' : '[Quiz]'}</div>
            <div><div style="font-size:.84rem;font-weight:700;color:#1a1a2e">${escHtml(q.title)}</div>
            ${q.description ? `<div style="font-size:.7rem;color:#aaa;margin-top:.04rem">${escHtml(q.description.substring(0, 50))}${q.description.length > 50 ? '…' : ''}</div>` : ''}
            ${isFile && q.file_path ? `<a href="${q.file_path}" target="_blank" style="font-size:.7rem;color:#003087;font-weight:700"> Quiz file</a>` : ''}
            ${q.close_at ? `<div style="font-size:.67rem;color:#c0392b;font-weight:700"> Closes: ${q.close_at}</div>` : ''}</div>
        </div>
        <div style="text-align:center">${typeBadge}</div>
        <div style="font-size:.75rem;color:#666;text-align:center">${isFile ? '—' : ' ' + q.question_count}</div>
        <div style="font-size:.75rem;color:#666;text-align:center">${q.time_limit ? ' ' + q.time_limit + 'm' : '—'}</div>
        <div style="text-align:center"><span class="badge badge-blue">[Students] ${q.attempt_count}</span></div>
        <div style="display:flex;justify-content:flex-end;gap:.3rem;flex-wrap:wrap">
            <button class="btn btn-green btn-sm" onclick="viewResults(${q.id},'${escHtml(q.title)}',${q.max_score},'${q.quiz_type || 'questions'}')">[Chart] ${isFile ? 'Grade' : 'Results'}</button>
            <button class="btn btn-gray btn-sm" onclick='editQuiz(${JSON.stringify(q)})'></button>
            <button class="btn btn-red btn-sm" onclick="deleteQuiz(${q.id})"></button>
        </div>
    </div>`;
        }

        async function loadQuizzes() {
            const cid = document.getElementById('quiz-course-select').value;
            const wrap = document.getElementById('quiz-list-wrap');
            if (!cid) { wrap.innerHTML = emptyCard('[Quiz]', 'Select a subject to view quizzes.'); return; }
            wrap.innerHTML = loadingCard();
            const res = await fetch(`api/quizzes.php?action=list&course_id=${cid}`);
            const data = await res.json();
            if (!data.success || !data.data.length) { wrap.innerHTML = emptyCard('', 'No quizzes yet. Click <strong>+ Create Quiz</strong>.'); return; }
            wrap.innerHTML = `<div style="background:white;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.07);overflow:hidden">
        <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f0f2f5;font-family:'Nunito',sans-serif;font-size:.95rem;font-weight:800;color:#1a1a2e">[Quiz] Quizzes (${data.data.length})</div>
        <div style="display:grid;grid-template-columns:1fr 65px 70px 65px 75px 165px;gap:.5rem;padding:.5rem 1.25rem;font-size:.65rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#bbb;background:#f8f9fc;border-bottom:1px solid #f0f2f5">
            <span>Quiz</span><span style="text-align:center">Type</span><span style="text-align:center">Qs</span><span style="text-align:center">Time</span><span style="text-align:center">Attempts</span><span style="text-align:right">Actions</span>
        </div>
        ${data.data.map(quizRow).join('')}
    </div>`;
        }

        let questionCount = 0;

        function openQuizModal() {
            document.getElementById('quiz-modal-title').textContent = '[Quiz] Create Quiz';
            document.getElementById('quiz-edit-id').value = '';
            populateCourseSelect('quiz-course');
            const sel = document.getElementById('quiz-course-select').value;
            if (sel) document.getElementById('quiz-course').value = sel;
            ['quiz-title', 'quiz-desc', 'quiz-time', 'quiz-open', 'quiz-close'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('quiz-maxscore').value = '100';
            document.getElementById('quiz-err').style.display = 'none';
            document.getElementById('questions-wrap').innerHTML = '';
            document.getElementById('quiz-file').value = '';
            document.getElementById('quiz-current-file').style.display = 'none';
            document.querySelector('input[name="quiz-mode"][value="questions"]').checked = true;
            toggleQuizMode(); questionCount = 0; addQuestion();
            openModal('quizModal');
        }

        function editQuiz(q) {
            document.getElementById('quiz-modal-title').textContent = ' Edit Quiz';
            document.getElementById('quiz-edit-id').value = q.id;
            populateCourseSelect('quiz-course');
            document.getElementById('quiz-course').value = q.course_id;
            document.getElementById('quiz-title').value = q.title || '';
            document.getElementById('quiz-desc').value = q.description || '';
            document.getElementById('quiz-time').value = q.time_limit || '';
            document.getElementById('quiz-maxscore').value = q.max_score || 100;
            document.getElementById('quiz-open').value = q.open_at ? q.open_at.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('quiz-close').value = q.close_at ? q.close_at.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('quiz-err').style.display = 'none';
            const isFile = q.quiz_type === 'file';
            document.querySelector(`input[name="quiz-mode"][value="${isFile ? 'file' : 'questions'}"]`).checked = true;
            toggleQuizMode();
            document.getElementById('quiz-file').value = '';
            if (isFile && q.file_path) { document.getElementById('quiz-current-file').style.display = ''; document.getElementById('quiz-current-file-link').href = q.file_path; }
            else { document.getElementById('quiz-current-file').style.display = 'none'; }
            document.getElementById('questions-wrap').innerHTML = isFile ? '' : `<div style="background:#fffbe6;border-radius:9px;padding:.75rem 1rem;font-size:.78rem;color:#b38600;font-weight:600;margin-bottom:.75rem">[i] Editing questions on an existing quiz requires re-creating it. You can update settings here without affecting questions.</div>`;
            openModal('quizModal');
        }

        async function submitQuiz() {
            const err = document.getElementById('quiz-err'); err.style.display = 'none';
            const editId = document.getElementById('quiz-edit-id').value;
            const course_id = document.getElementById('quiz-course').value;
            const title = document.getElementById('quiz-title').value.trim();
            const mode = document.querySelector('input[name="quiz-mode"]:checked').value;
            if (!course_id || !title) { err.textContent = '[!] Subject and title required.'; err.style.display = 'block'; return; }

            if (mode === 'file') {
                const fd = new FormData();
                if (editId) fd.append('id', editId);
                fd.append('course_id', course_id); fd.append('title', title);
                fd.append('description', document.getElementById('quiz-desc').value.trim());
                fd.append('time_limit', document.getElementById('quiz-time').value || '');
                fd.append('max_score', document.getElementById('quiz-maxscore').value || 100);
                fd.append('open_at', document.getElementById('quiz-open').value || '');
                fd.append('close_at', document.getElementById('quiz-close').value || '');
                fd.append('quiz_type', 'file');
                const file = document.getElementById('quiz-file').files[0];
                if (file) fd.append('file', file);
                else if (!editId) { err.textContent = '[!] Please upload a quiz file.'; err.style.display = 'block'; return; }
                const res = await fetch(`api/quizzes.php?action=${editId ? 'update' : 'create'}`, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) { closeModal('quizModal'); showToast(editId ? '[Check] Quiz updated!' : '[Check] Quiz created!'); loadQuizzes(); }
                else { err.textContent = '[!] ' + data.message; err.style.display = 'block'; }
            } else {
                const questions = [];
                const blocks = document.querySelectorAll('.q-block');
                if (blocks.length > 0) {
                    blocks.forEach(block => {
                        const n = block.id.replace('q-block-', '');
                        const qt = document.getElementById(`q-text-${n}`)?.value.trim(); if (!qt) return;
                        const qtype = document.getElementById(`q-type-${n}`)?.value;
                        const pts = document.getElementById(`q-pts-${n}`)?.value;
                        const choices = [];
                        const cv = block.querySelector(`input[name="correct-${n}"]:checked`)?.value ?? '0';
                        block.querySelectorAll(`#q-choices-${n} .choice-row`).forEach((row, idx) => { const txt = row.querySelector('input[type=text]')?.value.trim(); if (txt) choices.push({ choice_text: txt, is_correct: idx == cv ? 1 : 0 }); });
                        questions.push({ question_text: qt, question_type: qtype, points: pts, choices });
                    });
                    if (!editId && !questions.length) { err.textContent = '[!] Add at least one question.'; err.style.display = 'block'; return; }
                }
                const payload = { course_id, title, description: document.getElementById('quiz-desc').value.trim(), time_limit: document.getElementById('quiz-time').value || null, max_score: document.getElementById('quiz-maxscore').value || 100, open_at: document.getElementById('quiz-open').value || null, close_at: document.getElementById('quiz-close').value || null, quiz_type: 'questions', questions };
                if (editId) payload.id = editId;
                const res = await fetch(`api/quizzes.php?action=${editId ? 'update' : 'create'}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) { closeModal('quizModal'); showToast(editId ? '[Check] Quiz updated!' : '[Check] Quiz created!'); loadQuizzes(); }
                else { err.textContent = '[!] ' + data.message; err.style.display = 'block'; }
            }
        }

        async function viewResults(quizId, title, maxScore, quizType) {
            document.getElementById('results-title').textContent = '[Chart] ' + title + (quizType === 'file' ? ' — Grade Submissions' : ' — Results');
            document.getElementById('results-body').innerHTML = loadingCard();
            openModal('resultsModal');
            const res = await fetch(`api/quizzes.php?action=results&id=${quizId}`);
            const data = await res.json();
            if (!data.success || !data.data.length) { document.getElementById('results-body').innerHTML = '<div style="padding:3rem;text-align:center;color:#bbb"><div style="font-size:2.5rem;margin-bottom:.5rem"></div>No submissions yet.</div>'; return; }
            const isFile = quizType === 'file';
            const cols = isFile ? '1fr 90px 90px 120px 130px' : '1fr 80px 100px 110px';
            document.getElementById('results-body').innerHTML = `
        <div style="padding:.5rem 0">
            <div style="display:grid;grid-template-columns:${cols};gap:.5rem;padding:.5rem 1.25rem;font-size:.65rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#bbb;background:#f8f9fc;border-bottom:1px solid #f0f2f5">
                <span>Student</span>
                ${isFile ? `<span style="text-align:center">Submitted</span><span style="text-align:center">File</span><span style="text-align:center">Score / ${maxScore}</span><span style="text-align:center">Remarks</span>` : `<span style="text-align:center">School ID</span><span style="text-align:center">Score / ${maxScore}</span><span style="text-align:center">Finished</span>`}
            </div>
            ${data.data.map(r => isFile ? `
            <div style="display:grid;grid-template-columns:${cols};gap:.5rem;align-items:center;padding:.85rem 1.25rem;border-bottom:1px solid #f8f9fc">
                <div><div style="font-size:.84rem;font-weight:700;color:#1a1a2e">${escHtml(r.last_name + ', ' + r.first_name)}</div><div style="font-size:.7rem;color:#aaa">${escHtml(r.section_dept || '')}</div></div>
                <div style="font-size:.72rem;color:#666;text-align:center">${r.submitted_at ? r.submitted_at.substring(0, 10) : '—'}</div>
                <div style="text-align:center">${r.file_path ? `<a href="${r.file_path}" target="_blank" class="btn btn-blue btn-sm"> View</a>` : '<span style="color:#ccc;font-size:.75rem">—</span>'}</div>
                <div style="text-align:center">
                    <input type="number" min="0" max="${maxScore}" step="0.5" value="${r.score ?? ''}" placeholder="—"
                        data-attempt-id="${r.attempt_id}" data-student-id="${r.student_id}" data-quiz-id="${quizId}"
                        style="width:72px;padding:.3rem .5rem;border:1.5px solid #e0e4ee;border-radius:7px;font-size:.82rem;text-align:center;font-family:'Nunito',sans-serif;font-weight:700;outline:none"
                        onfocus="this.style.borderColor='#003087'" onblur="this.style.borderColor='#e0e4ee'">
                </div>
                <div style="text-align:center">
                    <input type="text" value="${escHtml(r.remarks || '')}" placeholder="Remarks" data-remarks-for-attempt="${r.attempt_id}"
                        style="width:100%;padding:.3rem .5rem;border:1.5px solid #e0e4ee;border-radius:7px;font-size:.78rem;outline:none"
                        onfocus="this.style.borderColor='#003087'" onblur="this.style.borderColor='#e0e4ee'">
                </div>
            </div>` : `
            <div style="display:grid;grid-template-columns:${cols};gap:.5rem;align-items:center;padding:.85rem 1.25rem;border-bottom:1px solid #f8f9fc">
                <div><div style="font-size:.84rem;font-weight:700;color:#1a1a2e">${escHtml(r.last_name + ', ' + r.first_name)}</div><div style="font-size:.7rem;color:#aaa">${escHtml(r.section_dept || '')}</div></div>
                <div style="font-size:.75rem;color:#666;text-align:center">${escHtml(r.school_id || '—')}</div>
                <div style="text-align:center">${r.score !== null ? `<strong style="color:${(r.score / maxScore) >= .75 ? '#003087' : (r.score / maxScore) >= .5 ? '#b38600' : '#c0392b'};font-size:.9rem">${r.score}</strong><span style="color:#aaa;font-size:.78rem"> / ${maxScore}</span>` : '<span style="color:#ccc">—</span>'}</div>
                <div style="font-size:.72rem;color:#aaa;text-align:center">${r.finished_at ?? 'In progress'}</div>
            </div>`).join('')}
        </div>
        ${isFile ? `<div style="padding:1rem 1.25rem;background:#f8f9fc;border-top:1px solid #f0f2f5;display:flex;justify-content:flex-end;gap:.75rem">
            <button class="btn btn-gray btn-sm" onclick="closeModal('resultsModal')">Close</button>
            <button class="btn btn-green" onclick="saveQuizGrades(${quizId})"> Save All Grades</button>
        </div>` : ''}`;
        }

        async function saveQuizGrades(quizId) {
            const inputs = document.querySelectorAll('#results-body input[data-attempt-id]');
            const grades = [];
            inputs.forEach(inp => { const ri = document.querySelector(`input[data-remarks-for-attempt="${inp.dataset.attemptId}"]`); grades.push({ attempt_id: inp.dataset.attemptId, student_id: inp.dataset.studentId, quiz_id: quizId, score: inp.value, remarks: ri ? ri.value : '' }); });
            const res = await fetch('api/quizzes.php?action=grade', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ grades }) });
            const data = await res.json();
            if (data.success) { showToast('[Check] Grades saved!'); closeModal('resultsModal'); loadQuizzes(); }
            else showToast('[!] Could not save grades.', true);
        }

        async function deleteQuiz(id) { if (!confirm('Delete this quiz and all submissions?')) return; await fetch(`api/quizzes.php?action=delete&id=${id}`, { method: 'DELETE' }); showToast(' Quiz deleted.'); loadQuizzes(); }

        /*  QUESTION BUILDER  */
        function addQuestion() {
            questionCount++; const n = questionCount;
            const div = document.createElement('div'); div.className = 'q-block'; div.id = `q-block-${n}`;
            div.innerHTML = `
        <div class="q-block-head"><span class="q-block-num">Question ${n}</span><button class="btn btn-red btn-sm" onclick="document.getElementById('q-block-${n}').remove()">Remove</button></div>
        <div class="form-grp" style="margin-bottom:.6rem"><input class="finput" type="text" id="q-text-${n}" placeholder="Enter question here..."></div>
        <div style="display:flex;gap:.75rem;margin-bottom:.75rem;align-items:center">
            <select class="finput" id="q-type-${n}" style="width:auto" onchange="toggleType(${n})">
                <option value="multiple_choice">Multiple Choice</option>
                <option value="true_false">True / False</option>
                <option value="essay">Essay</option>
            </select>
            <input class="finput" type="number" id="q-pts-${n}" value="1" min="0.5" step="0.5" style="width:80px">
            <span class="form-lbl" style="margin:0">pts</span>
        </div>
        <div id="q-choices-${n}">
            <div class="choice-row"><input type="radio" name="correct-${n}" value="0" title="Mark correct"><input type="text" placeholder="Choice A"></div>
            <div class="choice-row"><input type="radio" name="correct-${n}" value="1" title="Mark correct"><input type="text" placeholder="Choice B"></div>
        </div>
        <button class="add-choice-btn" id="add-choice-btn-${n}" onclick="addChoice(${n})">+ Add Choice</button>`;
            document.getElementById('questions-wrap').appendChild(div);
        }

        function toggleType(n) {
            const type = document.getElementById(`q-type-${n}`).value;
            const wrap = document.getElementById(`q-choices-${n}`);
            const ab = document.getElementById(`add-choice-btn-${n}`);
            if (type === 'essay') { wrap.innerHTML = '<div style="font-size:.78rem;color:#aaa;padding:.5rem">Essay — students type their answer.</div>'; ab.style.display = 'none'; }
            else if (type === 'true_false') { wrap.innerHTML = `<div class="choice-row"><input type="radio" name="correct-${n}" value="0"><input type="text" value="True" readonly></div><div class="choice-row"><input type="radio" name="correct-${n}" value="1"><input type="text" value="False" readonly></div>`; ab.style.display = 'none'; }
            else { wrap.innerHTML = `<div class="choice-row"><input type="radio" name="correct-${n}" value="0"><input type="text" placeholder="Choice A"></div><div class="choice-row"><input type="radio" name="correct-${n}" value="1"><input type="text" placeholder="Choice B"></div>`; ab.style.display = ''; }
        }

        function addChoice(n) {
            const wrap = document.getElementById(`q-choices-${n}`); const idx = wrap.querySelectorAll('.choice-row').length;
            const row = document.createElement('div'); row.className = 'choice-row';
            row.innerHTML = `<input type="radio" name="correct-${n}" value="${idx}" title="Mark correct"><input type="text" placeholder="Choice ${String.fromCharCode(65 + idx)}">`;
            wrap.appendChild(row);
        }

        /*  GRADES PANEL  */
        let gradeData = {};

        async function loadGrades() {
            const cid = document.getElementById('grade-course-select').value;
            const wrap = document.getElementById('grades-table-wrap');
            const distWrap = document.getElementById('grade-dist-wrap');
            if (!cid) {
                wrap.innerHTML = emptyCard('[Chart]', 'Select a subject to view grades.');
                distWrap.style.display = 'none';
                resetGradeSummary();
                return;
            }
            wrap.innerHTML = loadingCard();
            gradeData = {};
            try {
                const res = await fetch(`api/grades.php?action=list&course_id=${cid}`);
                const data = await res.json();
                if (!data.success) {
                    wrap.innerHTML = emptyCard('[!]', data.message || 'Could not load grades.');
                    return;
                }
                const { columns, students } = data.data;
                if (!students.length) {
                    wrap.innerHTML = emptyCard('[User]', 'No students enrolled in this subject yet.');
                    distWrap.style.display = 'none';
                    resetGradeSummary();
                    return;
                }
                students.forEach(s => {
                    gradeData[s.student_id] = {};
                    columns.forEach(c => {
                        gradeData[s.student_id][c.id] = s.scores?.[c.id] ?? null;
                    });
                });
                if (!columns.length) {
                    wrap.innerHTML = emptyCard('[Clipboard]', 'No assignments or quizzes yet for this subject. Add some first!');
                    distWrap.style.display = 'none';
                    updateGradeSummary(students, columns);
                    return;
                }
                renderGradeTable(columns, students, cid);
                renderGradeDist(students, columns);
                distWrap.style.display = '';
                updateGradeSummary(students, columns);
            } catch (e) {
                wrap.innerHTML = emptyCard('[!]', 'Error loading grades: ' + e.message);
            }
        }

        function renderGradeTable(columns, students, cid) {
            const wrap = document.getElementById('grades-table-wrap');
            const colHeaders = columns.map(c => `<th style="text-align:center;white-space:nowrap;padding:.6rem 1rem;font-size:.67rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#aaa">${escHtml(c.label)}</th>`).join('');
            const rows = students.map(s => {
                const scoreCells = columns.map(col => {
                    const val = gradeData[s.student_id][col.id];
                    return `<td style="text-align:center;padding:10px 8px"><input type="number" min="0" max="${escHtml(col.max_score || 100)}" step="1" value="${val !== null ? val : ''}" placeholder="—" data-student="${s.student_id}" data-col="${col.id}" oninput="onScoreInput(this)" style="width:62px;padding:.3rem .4rem;border:1.5px solid #e0e4ee;border-radius:7px;font-size:.82rem;text-align:center;font-family:'Nunito',sans-serif;font-weight:700;color:#1a1a2e;outline:none;background:#fafbff;transition:border-color .2s" onfocus="this.style.borderColor='#003087';this.style.background='white'" onblur="this.style.borderColor='#e0e4ee';this.style.background='#fafbff'"></td>`;
                }).join('');
                const avg = computeAvg(s.student_id, columns);
                const { label: rmLabel, cls: rmCls } = gradeRemark(avg);
                const initials = ((s.first_name || '')[0] || '') + ((s.last_name || '')[0] || '');
                const avatarColor = sectionAvatarColor(s.section_dept);
                return `<tr id="row-${s.student_id}">
                <td style="padding:10px 16px"><div style="display:flex;align-items:center;gap:.6rem"><div style="width:30px;height:30px;border-radius:50%;background:${avatarColor};display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:white;flex-shrink:0">${initials}</div><div style="font-size:.84rem;font-weight:700;color:#1a1a2e">${escHtml(s.last_name + ', ' + s.first_name)}</div></div></td>
                <td style="text-align:center;padding:10px 8px"><span style="font-size:.7rem;font-weight:800;padding:.18rem .6rem;border-radius:100px;background:${avatarColor}22;color:${avatarColor}">${escHtml(s.section_dept || '—')}</span></td>
                ${scoreCells}
                <td style="text-align:center;padding:10px 8px;font-family:'Nunito',sans-serif;font-weight:900;font-size:.92rem" id="avg-${s.student_id}"><span style="color:${avgColor(avg)}">${avg !== null ? avg : '—'}</span></td>
                <td style="text-align:center;padding:10px 8px" id="rm-${s.student_id}"><span class="badge ${rmCls}">${rmLabel}</span></td>
            </tr>`;
            }).join('');
            wrap.innerHTML = `<div style="background:white;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.07);overflow:hidden"><div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:.82rem"><thead><tr style="background:#f8f9fc;border-bottom:1px solid #f0f2f5"><th style="padding:.6rem 1rem;font-size:.67rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#aaa;text-align:left;white-space:nowrap">Student Name</th><th style="padding:.6rem 1rem;font-size:.67rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#aaa;text-align:center">Section</th>${colHeaders}<th style="padding:.6rem 1rem;font-size:.67rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#aaa;text-align:center">Average</th><th style="padding:.6rem 1rem;font-size:.67rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#aaa;text-align:center">Remarks</th></tr></thead><tbody style="color:#555">${rows}</tbody></table></div><div id="grade-action-bar" style="padding:1rem 1.4rem;background:#f8f9fc;border-top:1px solid #f0f2f5;display:flex;justify-content:flex-end;gap:.75rem"><button class="btn btn-gray" onclick="loadGrades()">↺ Reset</button><button class="btn btn-green" onclick="saveGrades('${cid}')"> Save Grades</button></div></div>`;
        }

        function onScoreInput(inp) {
            const sid = inp.dataset.student;
            const col = inp.dataset.col;
            const val = inp.value !== '' ? parseFloat(inp.value) : null;
            if (!gradeData[sid]) gradeData[sid] = {};
            gradeData[sid][col] = val;
            const allInputs = document.querySelectorAll(`input[data-student="${sid}"]`);
            const cols = Array.from(allInputs).map(i => ({ id: i.dataset.col }));
            const avg = computeAvg(sid, cols);
            const { label: rmLabel, cls: rmCls } = gradeRemark(avg);
            const avgCell = document.getElementById(`avg-${sid}`);
            if (avgCell) avgCell.innerHTML = `<span style="color:${avgColor(avg)}">${avg !== null ? avg : '—'}</span>`;
            const rmCell = document.getElementById(`rm-${sid}`);
            if (rmCell) rmCell.innerHTML = `<span class="badge ${rmCls}">${rmLabel}</span>`;
            refreshGradeSummaryFromDOM();
            const bar = document.getElementById('grade-action-bar');
            if (bar) bar.style.display = 'flex';
        }

        function computeAvg(sid, columns) {
            const vals = columns.map(c => gradeData[sid]?.[c.id]).filter(v => v !== null && v !== undefined && v !== '');
            if (!vals.length) return null;
            return Math.round(vals.reduce((a, b) => a + Number(b), 0) / vals.length * 10) / 10;
        }

        function gradeRemark(avg) {
            if (avg === null) return { label: '—', cls: 'badge-gray' };
            if (avg >= 90) return { label: 'Excellent', cls: 'badge-blue' };
            if (avg >= 80) return { label: 'Good', cls: 'badge-green' };
            if (avg >= 70) return { label: 'Fair', cls: 'badge-yellow' };
            if (avg >= 60) return { label: 'Passing', cls: 'badge-red' };
            return { label: 'Failing', cls: 'badge-gray' };
        }

        function avgColor(avg) {
            if (avg === null) return '#aaa';
            if (avg >= 80) return '#003087';
            if (avg >= 70) return '#b38600';
            return '#c0392b';
        }

        function sectionAvatarColor(sec) {
            const s = (sec || '').toLowerCase();
            if (s.includes('12') || s.includes('humss')) return '#7b2d8b';
            if (s.includes('11') || s.includes('stem')) return '#0097a7';
            if (s.includes('10')) return '#003087';
            if (s.includes('9')) return '#00875a';
            if (s.includes('8') || s.includes('abm')) return '#003087';
            if (s.includes('7')) return '#c0392b';
            return '#555';
        }

        function renderGradeDist(students, columns) {
            const avgs = students.map(s => computeAvg(s.student_id, columns)).filter(a => a !== null);
            const exc = avgs.filter(a => a >= 90).length;
            const gd = avgs.filter(a => a >= 80 && a < 90).length;
            const fr = avgs.filter(a => a >= 70 && a < 80).length;
            const ps = avgs.filter(a => a >= 60 && a < 70).length;
            const fl = avgs.filter(a => a < 60).length;
            document.getElementById('grade-dist-grid').innerHTML =
                distBox(exc, '90–100', 'Excellent', '#e8f0ff', '#003087') +
                distBox(gd, '80–89', 'Good', '#e6fff4', '#00875a') +
                distBox(fr, '70–79', 'Fair', '#fffbe6', '#b38600') +
                distBox(ps, '60–69', 'Passing', '#fff0ec', '#c0392b') +
                distBox(fl, 'Below 60', 'Failing', '#f5f5f5', '#888');
        }

        function distBox(count, range, label, bg, color) {
            return `<div style="background:${bg};border-radius:10px;padding:.75rem .5rem;text-align:center"><div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:1.3rem;color:${color}">${count}</div><div style="font-size:.65rem;font-weight:800;color:#888;margin-top:.1rem">${range}</div><div style="font-size:.63rem;color:#bbb;margin-top:.05rem">${label}</div></div>`;
        }

        function updateGradeSummary(students, columns) {
            const total = students.length;
            const avgs = students.map(s => computeAvg(s.student_id, columns));
            const graded = avgs.filter(a => a !== null).length;
            const pending = total - graded;
            const classAvg = graded ? Math.round(avgs.filter(a => a !== null).reduce((a, b) => a + b, 0) / graded * 100) / 100 : null;
            document.getElementById('gs-total').textContent = total;
            document.getElementById('gs-graded').textContent = graded;
            document.getElementById('gs-pending').textContent = pending;
            document.getElementById('gs-avg').textContent = classAvg !== null ? classAvg : '—';
        }

        function refreshGradeSummaryFromDOM() {
            const allAvgCells = document.querySelectorAll('[id^="avg-"]');
            const vals = Array.from(allAvgCells).map(el => { const txt = el.textContent.trim(); return txt === '—' || txt === '' ? null : parseFloat(txt); });
            const total = vals.length;
            const graded = vals.filter(v => v !== null).length;
            const pending = total - graded;
            const classAvg = graded ? Math.round(vals.filter(v => v !== null).reduce((a, b) => a + b, 0) / graded * 100) / 100 : null;
            document.getElementById('gs-total').textContent = total;
            document.getElementById('gs-graded').textContent = graded;
            document.getElementById('gs-pending').textContent = pending;
            document.getElementById('gs-avg').textContent = classAvg !== null ? classAvg : '—';
        }

        function resetGradeSummary() {
            ['gs-total', 'gs-graded', 'gs-pending', 'gs-avg'].forEach(id => { document.getElementById(id).textContent = '—'; });
        }

        async function saveGrades(cid) {
            const payload = [];
            document.querySelectorAll('#grades-table-wrap input[data-student]').forEach(inp => {
                payload.push({ student_id: inp.dataset.student, col_id: inp.dataset.col, score: inp.value !== '' ? parseFloat(inp.value) : null, course_id: cid });
            });
            const res = await fetch('api/grades.php?action=save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ grades: payload }) });
            const data = await res.json();
            if (data.success) {
                showToast('[Check] Grades saved!');
                const bar = document.getElementById('grade-action-bar');
                if (bar) bar.style.display = 'none';
            } else {
                showToast('[!] ' + (data.message || 'Could not save grades.'), true);
            }
        }

        function openAddGradeEntry() { showToast('Add Grade Entry coming soon!'); }
        function exportGrades() {
            const cid = document.getElementById('grade-course-select').value;
            if (!cid) { showToast('[!] Select a subject first.', true); return; }
            window.open(`api/grades.php?action=export&course_id=${cid}`, '_blank');
        }
        function generateReportCard() { showToast('Generating report cards...'); }
        function notifyStudents() { showToast('Sending notifications...'); }

        /*  ATTENDANCE  */
        const attStatusColors = { Present: '#00875a', Absent: '#c0392b', Late: '#b38600', Excused: '#7b2d8b' };
        const attStatusBg    = { Present: '#e6fff4', Absent: '#fff0ec', Late: '#fffbe6', Excused: '#f3e8ff' };
        const attStatusEmoji = { Present: '[Check]',      Absent: '',      Late: '⏰',      Excused: '[Notes]'      };

        // Track selected status per student in a plain object — reliable across all browsers
        const attSelected = {};

        async function loadAttendance() {
            const cid  = document.getElementById('att-course-select').value;
            const date = document.getElementById('att-date').value;
            const wrap = document.getElementById('att-table-wrap');

            if (!cid) {
                wrap.innerHTML = emptyCard('[Check]', 'Select a subject and date to take attendance.');
                ['att-total','att-present','att-absent','att-late','att-excused'].forEach(id => document.getElementById(id).textContent = '—');
                return;
            }

            wrap.innerHTML = loadingCard();

            try {
                const res  = await fetch(`api/attendance.php?action=list&course_id=${cid}&date=${date}`);

                // Guard: parse error response even if it's not JSON
                let data;
                try { data = await res.json(); }
                catch (_) {
                    // Clone and read raw text to expose exact server output
                    const raw = await fetch(`api/attendance.php?action=list&course_id=${cid}&date=${date}`).then(r => r.text());
                    console.error('RAW SERVER RESPONSE:', raw);
                    wrap.innerHTML = emptyCard('[!]', 'Server error: ' + raw.substring(0, 300));
                    return;
                }

                if (!data.success) { wrap.innerHTML = emptyCard('[!]', data.message || 'Failed to load attendance.'); return; }

                const students = data.students || [];
                const existing = data.attendance || {};

                if (!students.length) { wrap.innerHTML = emptyCard('[Students]', 'No students enrolled in this subject.'); return; }

                // Reset selection state
                Object.keys(attSelected).forEach(k => delete attSelected[k]);

                let html = `<div style="background:white;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.07);overflow:hidden">
                    <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f0f2f5;display:flex;align-items:center;justify-content:space-between">
                        <h3 style="font-family:'Nunito',sans-serif;font-size:.95rem;font-weight:800;color:#1a1a2e">[Clipboard] Attendance — ${escHtml(date)}</h3>
                        <span class="badge badge-blue">${students.length} students</span>
                    </div>
                    <div style="display:grid;grid-template-columns:50px 1fr 130px 160px 120px;gap:.5rem;padding:.5rem 1.25rem;font-size:.65rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#bbb;background:#f8f9fc;border-bottom:1px solid #f0f2f5">
                        <span>#</span><span>Student Name</span><span style="text-align:center">LRN / School ID</span><span style="text-align:center">Status</span><span style="text-align:center">Remark</span>
                    </div>`;

                students.forEach((s, i) => {
                    const status = existing[s.id]?.status || 'Present';
                    const remark = existing[s.id]?.remark || '';
                    attSelected[s.id] = status; // store in state object
                    const initials = (s.first_name?.[0] || '').toUpperCase() + (s.last_name?.[0] || '').toUpperCase();

                    html += `<div style="display:grid;grid-template-columns:50px 1fr 130px 160px 120px;gap:.5rem;align-items:center;padding:.75rem 1.25rem;border-bottom:1px solid #f8f9fc;transition:background .15s" onmouseover="this.style.background='#f6f9ff'" onmouseout="this.style.background=''">
                        <div style="font-family:'Nunito',sans-serif;font-weight:800;font-size:.8rem;color:#aaa">${i + 1}</div>
                        <div style="display:flex;align-items:center;gap:.65rem">
                            <div id="att-avatar-${s.id}" style="width:30px;height:30px;border-radius:50%;background:${attStatusColors[status]};display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:800;color:white;flex-shrink:0">${escHtml(initials)}</div>
                            <div style="font-size:.84rem;font-weight:700;color:#1a1a2e">${escHtml(s.last_name + ', ' + s.first_name)}</div>
                        </div>
                        <div style="font-size:.78rem;color:#666;text-align:center">${escHtml(s.school_id || '—')}</div>
                        <div style="display:flex;gap:.3rem;justify-content:center;flex-wrap:wrap">
                            ${['Present','Absent','Late','Excused'].map(st => `
                                <button type="button" class="att-btn" data-student="${s.id}" data-status="${st}"
                                    onclick="setAttStatus(${s.id},'${st}')"
                                    style="background:${status === st ? attStatusBg[st] : '#f5f5f5'};color:${status === st ? attStatusColors[st] : '#999'};border:1.5px solid ${status === st ? attStatusColors[st] : '#e0e4ee'};font-size:.68rem;padding:.22rem .42rem;border-radius:7px;cursor:pointer;font-weight:700;font-family:'Nunito',sans-serif;transition:all .15s;white-space:nowrap">
                                    ${attStatusEmoji[st]} ${st}
                                </button>
                            `).join('')}
                        </div>
                        <div style="text-align:center">
                            <input type="text" id="att-remark-${s.id}" value="${escHtml(remark)}" placeholder="—"
                                style="width:92%;padding:.3rem .5rem;border:1.5px solid #e0e4ee;border-radius:7px;font-size:.75rem;text-align:center;outline:none;font-family:'Open Sans',sans-serif"
                                onfocus="this.style.borderColor='#003087'" onblur="this.style.borderColor='#e0e4ee'">
                        </div>
                    </div>`;
                });

                html += '</div>';
                wrap.innerHTML = html;

                // Use state object for accurate counts (no border-color string comparison)
                updateAttCounts();
                document.getElementById('att-total').textContent = students.length;

            } catch (err) {
                wrap.innerHTML = emptyCard('[!]', 'Error loading attendance: ' + err.message);
                console.error('loadAttendance error:', err);
            }
        }

        function setAttStatus(studentId, status) {
            // Update state object — single source of truth
            attSelected[studentId] = status;

            // Update button styles
            document.querySelectorAll(`.att-btn[data-student="${studentId}"]`).forEach(btn => {
                const st = btn.dataset.status;
                const active = (st === status);
                btn.style.background   = active ? attStatusBg[st]    : '#f5f5f5';
                btn.style.color        = active ? attStatusColors[st] : '#999';
                btn.style.borderColor  = active ? attStatusColors[st] : '#e0e4ee';
            });

            // Update avatar color to reflect current status
            const avatar = document.getElementById(`att-avatar-${studentId}`);
            if (avatar) avatar.style.background = attStatusColors[status] || '#555';

            updateAttCounts();
        }

        function updateAttCounts() {
            // Count directly from the state object — reliable, no DOM color comparison
            let p = 0, a = 0, l = 0, e = 0;
            Object.values(attSelected).forEach(st => {
                if (st === 'Present')  p++;
                else if (st === 'Absent')   a++;
                else if (st === 'Late')     l++;
                else if (st === 'Excused')  e++;
            });
            document.getElementById('att-present').textContent = p;
            document.getElementById('att-absent').textContent  = a;
            document.getElementById('att-late').textContent    = l;
            document.getElementById('att-excused').textContent = e;
            // Also update total in case called before load completes
            const total = Object.keys(attSelected).length;
            if (total > 0) document.getElementById('att-total').textContent = total;
        }

        function markAll(status) {
            if (!Object.keys(attSelected).length) {
                showToast('[!] Load a subject first before marking attendance.', true);
                return;
            }
            Object.keys(attSelected).forEach(sid => setAttStatus(parseInt(sid), status));
            showToast(`[Check] All students marked as ${status}`);
        }

        async function saveAttendance() {
            const cid  = document.getElementById('att-course-select').value;
            const date = document.getElementById('att-date').value;
            if (!cid)  { showToast('[!] Select a subject first.', true);  return; }
            if (!date) { showToast('[!] Select a date first.', true); return; }

            if (!Object.keys(attSelected).length) {
                showToast('[!] No attendance data to save. Load students first.', true);
                return;
            }

            const records = Object.entries(attSelected).map(([sid, status]) => {
                const remarkEl = document.getElementById(`att-remark-${sid}`);
                return {
                    student_id: parseInt(sid),
                    status,
                    remark: remarkEl ? remarkEl.value.trim() : ''
                };
            });

            // Disable save button to prevent double-submit
            const saveBtn = document.querySelector('[onclick="saveAttendance()"]');
            if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = '[...] Saving…'; }

            try {
                const res  = await fetch('api/attendance.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ course_id: parseInt(cid), date, records })
                });
                const data = await res.json();
                if (data.success) showToast('[Check] Attendance saved successfully!');
                else showToast('[!] ' + (data.message || 'Failed to save attendance.'), true);
            } catch (err) {
                showToast('[!] Error saving attendance.', true);
                console.error('saveAttendance error:', err);
            } finally {
                if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = ' Save Attendance'; }
            }
        }

        function exportAttendance() {
            const cid  = document.getElementById('att-course-select').value;
            const date = document.getElementById('att-date').value;
            if (!cid) { showToast('[!] Select a subject first.', true); return; }
            window.open(`api/attendance.php?action=export&course_id=${cid}&date=${date}`, '_blank');
        }

        //  Notification Bell 
        (function initNotif() {
            const STORAGE_KEY = 'elms_read_notifs_teacher';
            function getRead() {
                try { return new Set(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')); } catch { return new Set(); }
            }
            function saveRead(set) {
                try { localStorage.setItem(STORAGE_KEY, JSON.stringify([...set])); } catch {}
            }

            function renderNotifs() {
                const read  = getRead();
                const list  = document.getElementById('notifList');
                const badge = document.getElementById('notifBadge');
                if (!_notifData || _notifData.length === 0) {
                    list.innerHTML = '<div class="notif-empty"><span>[Mute]</span>No notifications yet.</div>';
                    badge.classList.add('hidden');
                    return;
                }
                let unread = 0;
                let html   = '';
                _notifData.forEach(n => {
                    const isUnread = !read.has(n.id);
                    if (isUnread) unread++;

                    let label;
                    if (n.type === 'submission') {
                        label = `<span style="color:#b38600;font-weight:700">[Submission]</span> `;
                    } else if (n.course) {
                        label = `<span style="color:#003087;font-weight:700">[${escHtml(n.course)}]</span> `;
                    } else {
                        label = '<span style="color:#1e7e34;font-weight:700">[School-wide]</span> ';
                    }

                    const titleText = n.type === 'submission'
                        ? `${escHtml(n.author)} submitted "${escHtml(n.title)}"`
                        : escHtml(n.title);

                    const metaText = n.type === 'submission'
                        ? `${escHtml(n.course || '')} · ${escHtml(n.posted_at)}`
                        : `${escHtml(n.author)} · ${escHtml(n.posted_at)}`;

                    html += `<div class="notif-item${isUnread ? ' unread' : ''}" onclick="notifClick('${n.id}', '${n.type}', ${n.ref_id})">
                        <div class="notif-dot"></div>
                        <div>
                            <div class="notif-item-title">${label}${titleText}</div>
                            <div class="notif-item-meta">${metaText}</div>
                        </div>
                    </div>`;
                });
                list.innerHTML = html;
                if (unread > 0) {
                    badge.textContent = unread > 99 ? '99+' : unread;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }

            window.toggleNotif = function(e) {
                e.stopPropagation();
                document.getElementById('notifDropdown').classList.toggle('open');
            };
            window.closeNotif = function() {
                document.getElementById('notifDropdown').classList.remove('open');
            };
            window.notifClick = function(id, type, refId) {
                const read = getRead();
                read.add(id);
                saveRead(read);
                renderNotifs();
                if (type === 'submission') {
                    showPanel('assignments', document.querySelectorAll('.sidebar-link')[3]);
                } else {
                    showPanel('announcements', document.querySelectorAll('.sidebar-link')[5]);
                }
                closeNotif();
            };
            window.markAllRead = function() {
                const read = getRead();
                _notifData.forEach(n => read.add(n.id));
                saveRead(read);
                renderNotifs();
            };

            document.addEventListener('click', function(e) {
                const wrap = document.getElementById('notifWrap');
                if (wrap && !wrap.contains(e.target)) closeNotif();
            });

            renderNotifs();
        })();
    </script>
</body>

</html>