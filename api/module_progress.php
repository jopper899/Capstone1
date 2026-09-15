<?php
// ============================================================
//  Arandia College eLMS — Module Progress API
//  File: api/module_progress.php
// ============================================================
session_start();
require_once '../config/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];
$action = $_GET['action'] ?? '';

// ── POST: mark module as done ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mark') {
    if ($role !== 'Student') {
        echo json_encode(['success' => false, 'message' => 'Students only.']);
        exit;
    }
    $body = json_decode(file_get_contents('php://input'), true);
    $module_id = (int) ($body['module_id'] ?? 0);
    $course_id = (int) ($body['course_id'] ?? 0);

    if (!$module_id || !$course_id) {
        echo json_encode(['success' => false, 'message' => 'Module and course required.']);
        exit;
    }

    // Verify student is enrolled in this course
    $chk = $conn->query("SELECT id FROM enrollments WHERE student_id=$user_id AND course_id=$course_id LIMIT 1")->fetch_assoc();
    if (!$chk) {
        echo json_encode(['success' => false, 'message' => 'Not enrolled in this course.']);
        exit;
    }

    // Insert or ignore if already done
    $conn->query(
        "INSERT IGNORE INTO module_progress (student_id, module_id, course_id)
         VALUES ($user_id, $module_id, $course_id)"
    );
    echo json_encode(['success' => true, 'message' => 'Module marked as done!']);
    exit;
}

// ── POST: unmark module ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'unmark') {
    if ($role !== 'Student') {
        echo json_encode(['success' => false, 'message' => 'Students only.']);
        exit;
    }
    $body = json_decode(file_get_contents('php://input'), true);
    $module_id = (int) ($body['module_id'] ?? 0);
    $conn->query("DELETE FROM module_progress WHERE student_id=$user_id AND module_id=$module_id");
    echo json_encode(['success' => true, 'message' => 'Unmarked.']);
    exit;
}

// ── GET: get progress for a student (per course) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'my_progress') {
    $course_id = (int) ($_GET['course_id'] ?? 0);
    $rows = [];
    $where = $course_id ? "AND mp.course_id = $course_id" : '';
    $res = $conn->query(
        "SELECT mp.module_id, mp.course_id,
                DATE_FORMAT(mp.completed_at,'%b %d, %Y') AS completed_at
         FROM module_progress mp
         WHERE mp.student_id = $user_id $where"
    );
    while ($r = $res->fetch_assoc())
        $rows[$r['module_id']] = $r;
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ── GET: teacher view — progress per course ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'course_progress') {
    if ($role !== 'Teacher') {
        echo json_encode(['success' => false, 'message' => 'Teachers only.']);
        exit;
    }
    $course_id = (int) ($_GET['course_id'] ?? 0);
    if (!$course_id) {
        echo json_encode(['success' => false, 'message' => 'Course required.']);
        exit;
    }

    // Total published modules in course
    $total = (int) $conn->query("SELECT COUNT(*) AS c FROM modules WHERE course_id=$course_id AND published=1")->fetch_assoc()['c'];

    // Per-student progress
    $rows = [];
    $res = $conn->query(
        "SELECT u.id, u.school_id, u.first_name, u.last_name, u.section_dept,
                COUNT(mp.id) AS done_count
         FROM enrollments e
         JOIN users u ON u.id = e.student_id
         LEFT JOIN module_progress mp ON mp.student_id = u.id AND mp.course_id = $course_id
         WHERE e.course_id = $course_id AND u.role = 'Student'
         GROUP BY u.id
         ORDER BY u.last_name, u.first_name"
    );
    while ($r = $res->fetch_assoc()) {
        $r['total'] = $total;
        $r['percent'] = $total > 0 ? round(($r['done_count'] / $total) * 100) : 0;
        $rows[] = $r;
    }
    echo json_encode(['success' => true, 'data' => $rows, 'total_modules' => $total]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);