<?php
// ============================================================
//  Arandia College eLMS — Announcements API
//  File: api/announcements.php
// ============================================================

// Catch ALL errors and return them as JSON (never let PHP output raw HTML)
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "PHP Error [$errno]: $errstr in $errfile on line $errline"
    ]);
    exit;
});
set_exception_handler(function($e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()
    ]);
    exit;
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/conn.php';

header('Content-Type: application/json');

// ── Auth guard ────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Session user_id not set.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? '';
$action  = $_GET['action'] ?? '';

// ── Helper ────────────────────────────────────────────────────────────────
function respond(bool $ok, $data = null, string $msg = ''): void
{
    if ($ok) {
        $payload = ['success' => true];
        if ($data !== null) {
            is_int($data) ? $payload['id'] = $data : $payload['data'] = $data;
        }
        if ($msg) $payload['message'] = $msg;
    } else {
        $payload = ['success' => false, 'message' => $msg ?: 'An error occurred.'];
    }
    echo json_encode($payload);
    exit;
}

// ── Route ─────────────────────────────────────────────────────────────────
if      ($action === 'create') handleCreate();
elseif  ($action === 'list')   handleList();
elseif  ($action === 'delete') handleDelete();
else    respond(false, null, 'Unknown action: "' . htmlspecialchars($action) . '"');

// ── CREATE ────────────────────────────────────────────────────────────────
function handleCreate(): void
{
    global $conn, $user_id, $role;

    if (!in_array($role, ['Teacher', 'Admin'])) {
        respond(false, null, 'Only teachers and admins can post. Role detected: ' . $role);
    }

    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body)) {
        respond(false, null, 'Invalid JSON. Raw input: ' . substr($raw, 0, 200));
    }

    $course_id_raw = $body['course_id'] ?? null;
    $course_id     = ($course_id_raw !== null && $course_id_raw !== '' && (int)$course_id_raw > 0)
                     ? (int) $course_id_raw
                     : null;

    $title   = trim($body['title'] ?? '');
    $message = trim($body['body']  ?? '');

    if ($title   === '') respond(false, null, 'Title is required.');
    if ($message === '') respond(false, null, 'Message body is required.');

    // Teachers must be assigned to the course
    if ($role === 'Teacher' && $course_id !== null) {
        $chk = $conn->prepare(
            "SELECT id FROM teacher_assignments WHERE teacher_id = ? AND course_id = ? LIMIT 1"
        );
        if (!$chk) respond(false, null, 'Prepare error (teacher check): ' . $conn->error);
        $chk->bind_param('ii', $user_id, $course_id);
        $chk->execute();
        $found = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$found) respond(false, null, 'You are not assigned to that subject.');
    }

    // Use NULL literal in SQL when course_id is null to avoid bind_param type issues
    if ($course_id === null) {
        $stmt = $conn->prepare(
            "INSERT INTO announcements (author_id, course_id, title, body, posted_at)
             VALUES (?, NULL, ?, ?, NOW())"
        );
        if (!$stmt) respond(false, null, 'Prepare error (insert null course): ' . $conn->error);
        $stmt->bind_param('iss', $user_id, $title, $message);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO announcements (author_id, course_id, title, body, posted_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        if (!$stmt) respond(false, null, 'Prepare error (insert with course): ' . $conn->error);
        $stmt->bind_param('iiss', $user_id, $course_id, $title, $message);
    }

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        respond(false, null, 'Execute error: ' . $err);
    }

    $new_id = (int) $conn->insert_id;
    $stmt->close();

    respond(true, $new_id, 'Announcement posted successfully.');
}

// ── LIST ──────────────────────────────────────────────────────────────────
function handleList(): void
{
    global $conn, $user_id, $role;

    $course_id = isset($_GET['course_id']) && (int)$_GET['course_id'] > 0
                 ? (int) $_GET['course_id']
                 : null;

    $where = '';

    if ($role === 'Admin') {
        $where = $course_id ? "WHERE a.course_id = $course_id" : '';
    } elseif ($role === 'Teacher') {
        // Teachers see their own posts + all Admin announcements
        $where = $course_id
            ? "WHERE a.course_id = $course_id AND (a.author_id = $user_id OR u.role = 'Admin')"
            : "WHERE (a.author_id = $user_id OR u.role = 'Admin')";
    } elseif ($role === 'Student') {
        $where = $course_id
            ? "WHERE a.course_id = $course_id
                 AND EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id = $user_id AND e.course_id = a.course_id)"
            : "WHERE (a.course_id IS NULL OR EXISTS (
                 SELECT 1 FROM enrollments e WHERE e.student_id = $user_id AND e.course_id = a.course_id
               ))";
    } else {
        respond(false, null, 'Unauthorized role: ' . $role);
    }

    $sql = "SELECT a.id, a.title, a.body, a.course_id,
                DATE_FORMAT(a.posted_at,'%b %d, %Y %h:%i %p') AS posted_at,
                CONCAT(u.first_name,' ',u.last_name) AS author,
                u.role AS author_role, c.course_code, c.course_name
            FROM announcements a
            JOIN users u ON u.id = a.author_id
            LEFT JOIN courses c ON c.id = a.course_id
            $where
            ORDER BY a.posted_at DESC LIMIT 50";

    $res = $conn->query($sql);
    if (!$res) respond(false, null, 'Query error: ' . $conn->error);

    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;

    respond(true, $rows);
}

// ── DELETE ────────────────────────────────────────────────────────────────
function handleDelete(): void
{
    global $conn, $user_id, $role;

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) respond(false, null, 'Invalid announcement ID.');

    if ($role === 'Admin') {
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        if (!$stmt) respond(false, null, 'Prepare error: ' . $conn->error);
        $stmt->bind_param('i', $id);
    } elseif ($role === 'Teacher') {
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ? AND author_id = ?");
        if (!$stmt) respond(false, null, 'Prepare error: ' . $conn->error);
        $stmt->bind_param('ii', $id, $user_id);
    } else {
        respond(false, null, 'Permission denied.');
        return;
    }

    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected === 0) respond(false, null, 'Not found or permission denied.');
    respond(true, null, 'Announcement deleted.');
}