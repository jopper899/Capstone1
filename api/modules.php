<?php
// ============================================================
//  Arandia College eLMS — Modules API
//  File: api/modules.php
// ============================================================
session_start();
require_once '../config/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$teacher_id = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// ── Helper: verify teacher owns the course via teacher_assignments ─────────
function teacherOwnsCourse($conn, $teacher_id, $course_id)
{
    $s = $conn->prepare("SELECT id FROM teacher_assignments WHERE teacher_id=? AND course_id=? LIMIT 1");
    $s->bind_param('ii', $teacher_id, $course_id);
    $s->execute();
    return (bool) $s->get_result()->fetch_assoc();
}

// ── GET: list modules for a course ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $course_id = (int) ($_GET['course_id'] ?? 0);
    if (!$course_id) {
        echo json_encode(['success' => false, 'message' => 'Course required.']);
        exit;
    }
    $rows = [];
    $res = $conn->query(
        "SELECT id, course_id, title, description, file_path, week_number, published,
                DATE_FORMAT(created_at,'%b %d, %Y') AS created_at
         FROM modules WHERE course_id = $course_id ORDER BY week_number, created_at DESC"
    );
    while ($r = $res->fetch_assoc())
        $rows[] = $r;
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ── POST: create module (multipart form with optional file) ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $course_id = (int) ($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $week = (int) ($_POST['week_number'] ?? 0) ?: null;
    $published = isset($_POST['published']) ? 1 : 0;

    if (!$course_id || !$title) {
        echo json_encode(['success' => false, 'message' => 'Course and title are required.']);
        exit;
    }
    if (!teacherOwnsCourse($conn, $teacher_id, $course_id)) {
        echo json_encode(['success' => false, 'message' => 'You are not assigned to this course.']);
        exit;
    }

    $file_path = null;
    if (!empty($_FILES['file']['name'])) {
        $upload_dir = '../uploads/modules/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0775, true);
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'zip'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'File type not allowed.']);
            exit;
        }
        $filename = uniqid('mod_') . '.' . $ext;
        $dest = $upload_dir . $filename;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'message' => 'File upload failed.']);
            exit;
        }
        $file_path = 'uploads/modules/' . $filename;
    }

    $stmt = $conn->prepare(
        "INSERT INTO modules (course_id, title, description, file_path, week_number, published)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isssii', $course_id, $title, $description, $file_path, $week, $published);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Module created.', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
    }
    exit;
}

// ── POST: toggle publish ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle') {
    $id = (int) ($_POST['id'] ?? 0);
    $conn->query("UPDATE modules SET published = 1 - published WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

// ── DELETE: remove module ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'delete') {
    $id = (int) ($_GET['id'] ?? 0);
    $r = $conn->query("SELECT file_path FROM modules WHERE id = $id")->fetch_assoc();
    if ($r && $r['file_path'] && file_exists('../' . $r['file_path'])) {
        unlink('../' . $r['file_path']);
    }
    $conn->query("DELETE FROM modules WHERE id = $id");
    echo json_encode(['success' => true, 'message' => 'Module deleted.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);