<?php
// ============================================================
//  Arandia College eLMS — Teacher Assignments API
//  File: api/teacher_assignments.php
// ============================================================
session_start();
require_once __DIR__ . '/../config/conn.php';

header('Content-Type: application/json');
ob_start();
function apiJson($payload, $status = 200) { if (ob_get_length()) ob_clean(); http_response_code($status); echo json_encode($payload); exit; }
set_error_handler(function($errno, $errstr) { apiJson(['success'=>false,'message'=>'PHP error: '.$errstr], 500); });

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? '';

// ── GET: list all assignments (optionally filtered by teacher) ────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $teacher_id = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
    $where = $teacher_id ? "WHERE ta.teacher_id = $teacher_id" : '';
    $rows = [];
    $res = $conn->query(
        "SELECT ta.id, ta.teacher_id, ta.course_id, ta.section, ta.school_year, ta.semester,
                CONCAT(u.last_name, ', ', u.first_name) AS teacher_name,
                c.course_code, c.course_name
         FROM teacher_assignments ta
         JOIN users u ON u.id = ta.teacher_id
         JOIN courses c ON c.id = ta.course_id
         $where
         ORDER BY u.last_name, c.course_name, ta.section"
    );
    while ($r = $res->fetch_assoc())
        $rows[] = $r;
    apiJson(['success' => true, 'data' => $rows]);
    exit;
}

// ── POST: assign ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'assign') {
    $body = json_decode(file_get_contents('php://input'), true);
    $teacher_id = (int) ($body['teacher_id'] ?? 0);
    $course_id = (int) ($body['course_id'] ?? 0);
    $section = trim($body['section'] ?? '');
    $school_year = trim($body['school_year'] ?? '2025-2026');
    $semester = trim($body['semester'] ?? '1st');

    if (!$teacher_id || !$course_id || !$section) {
        echo json_encode(['success' => false, 'message' => 'Teacher, subject, and section are required.']);
        exit;
    }
    // Verify teacher exists and is actually a Teacher
    $chk = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'Teacher'");
    $chk->bind_param('i', $teacher_id);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Invalid teacher.']);
        exit;
    }
    // Check duplicate
    $dup = $conn->prepare(
        "SELECT id FROM teacher_assignments
         WHERE teacher_id=? AND course_id=? AND section=? AND school_year=? AND semester=?"
    );
    $dup->bind_param('iisss', $teacher_id, $course_id, $section, $school_year, $semester);
    $dup->execute();
    if ($dup->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'This assignment already exists.']);
        exit;
    }
    $stmt = $conn->prepare(
        "INSERT INTO teacher_assignments (teacher_id, course_id, section, school_year, semester)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('iisss', $teacher_id, $course_id, $section, $school_year, $semester);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Assignment saved.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

// ── DELETE: remove assignment ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'remove') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM teacher_assignments WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode(['success' => $stmt->affected_rows > 0, 'message' => $stmt->affected_rows > 0 ? 'Removed.' : 'Not found.']);
    exit;
}

apiJson(['success' => false, 'message' => 'Invalid request.'], 400);