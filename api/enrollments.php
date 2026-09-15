<?php
// ============================================================
//  Arandia College eLMS — Student Enrollment API
//  File: api/enrollments.php
// ============================================================
session_start();
require_once '../config/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']); exit;
}

$action = $_GET['action'] ?? '';

// ── GET: list all students with their enrollments ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $student_id = (int)($_GET['student_id'] ?? 0);
    $where = $student_id ? "AND e.student_id = $student_id" : '';
    $rows  = [];
    $res   = $conn->query(
        "SELECT e.id, e.student_id, e.course_id, e.status AS enroll_status,
                DATE_FORMAT(e.enrolled_at,'%b %d, %Y') AS enrolled_at,
                CONCAT(u.last_name,', ',u.first_name) AS student_name,
                u.school_id, u.section_dept,
                c.course_code, c.course_name
         FROM enrollments e
         JOIN users u ON u.id = e.student_id
         JOIN courses c ON c.id = e.course_id
         WHERE u.role = 'Student' $where
         ORDER BY u.last_name, u.first_name, c.course_code"
    );
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}

// ── GET: list all students (for dropdown) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'students') {
    $rows = [];
    $res  = $conn->query(
        "SELECT id, school_id, section_dept,
                CONCAT(last_name,', ',first_name) AS name
         FROM users WHERE role='Student' AND status='Active'
         ORDER BY last_name, first_name"
    );
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}

// ── POST: enroll student in multiple subjects ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'enroll') {
    $body       = json_decode(file_get_contents('php://input'), true);
    $student_id = (int)($body['student_id'] ?? 0);
    $course_ids = array_map('intval', $body['course_ids'] ?? []);

    if (!$student_id || empty($course_ids)) {
        echo json_encode(['success' => false, 'message' => 'Student and at least one subject required.']); exit;
    }

    // Verify student exists
    $chk = $conn->query("SELECT id FROM users WHERE id=$student_id AND role='Student' LIMIT 1")->fetch_assoc();
    if (!$chk) { echo json_encode(['success'=>false,'message'=>'Student not found.']); exit; }

    $added    = 0;
    $skipped  = 0;
    foreach ($course_ids as $cid) {
        // Check if already enrolled
        $dup = $conn->query("SELECT id FROM enrollments WHERE student_id=$student_id AND course_id=$cid LIMIT 1")->fetch_assoc();
        if ($dup) { $skipped++; continue; }
        $conn->query("INSERT INTO enrollments (student_id, course_id) VALUES ($student_id, $cid)");
        $added++;
    }

    $msg = "$added subject(s) enrolled.";
    if ($skipped) $msg .= " $skipped already enrolled (skipped).";
    echo json_encode(['success' => true, 'message' => $msg, 'added' => $added, 'skipped' => $skipped]); exit;
}

// ── DELETE: remove enrollment ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'remove') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID.']); exit; }
    $conn->query("DELETE FROM enrollments WHERE id=$id");
    echo json_encode(['success' => $conn->affected_rows > 0, 'message' => 'Enrollment removed.']); exit;
}

// ── DELETE: remove all enrollments of a student ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'remove_all') {
    $student_id = (int)($_GET['student_id'] ?? 0);
    if (!$student_id) { echo json_encode(['success'=>false,'message'=>'Invalid student.']); exit; }
    $conn->query("DELETE FROM enrollments WHERE student_id=$student_id");
    echo json_encode(['success' => true, 'message' => 'All enrollments removed.']); exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);