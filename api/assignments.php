<?php
// ============================================================
//  Arandia College eLMS — Assignments API
//  File: api/assignments.php
// ============================================================
session_start();
require_once '../config/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']); exit;
}

$teacher_id = (int)$_SESSION['user_id'];
$action     = $_GET['action'] ?? '';

function teacherOwnsCourse($conn, $teacher_id, $course_id) {
    $s = $conn->prepare("SELECT id FROM teacher_assignments WHERE teacher_id=? AND course_id=? LIMIT 1");
    $s->bind_param('ii', $teacher_id, $course_id); $s->execute();
    return (bool)$s->get_result()->fetch_assoc();
}

// ── GET: list assignments for a course ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $course_id = (int)($_GET['course_id'] ?? 0);
    if (!$course_id) { echo json_encode(['success'=>false,'message'=>'Course required.']); exit; }
    $rows = [];
    $res  = $conn->query(
        "SELECT a.id, a.course_id, a.title, a.instructions, a.description, a.due_date, a.max_score,
                a.file_path, DATE_FORMAT(a.created_at,'%b %d, %Y') AS created_at,
                (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS submission_count,
                (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id AND s.score IS NULL) AS pending_grades
         FROM assignments a
         WHERE a.course_id = $course_id
         ORDER BY a.created_at DESC"
    );
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

// ── POST: create assignment ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $course_id   = (int)($_POST['course_id']   ?? 0);
    $title       = trim($_POST['title']        ?? '');
    $description = trim($_POST['description']  ?? '');
    $due_date    = trim($_POST['due_date']      ?? '');
    $max_score   = (float)($_POST['max_score'] ?? 100);

    if (!$course_id || !$title) {
        echo json_encode(['success'=>false,'message'=>'Course and title required.']); exit;
    }
    if (!teacherOwnsCourse($conn, $teacher_id, $course_id)) {
        echo json_encode(['success'=>false,'message'=>'Not assigned to this course.']); exit;
    }

    $file_path = null;
    if (!empty($_FILES['file']['name'])) {
        $upload_dir = '../uploads/assignments/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
        $ext     = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','jpg','jpeg','png','zip'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success'=>false,'message'=>'File type not allowed.']); exit;
        }
        if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success'=>false,'message'=>'Max file size is 10MB.']); exit;
        }
        $filename = 'assign_'.uniqid().'.'.$ext;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir.$filename)) {
            echo json_encode(['success'=>false,'message'=>'File upload failed.']); exit;
        }
        $file_path = 'uploads/assignments/'.$filename;
    }

    $due = $due_date ?: null;
    $stmt = $conn->prepare(
        "INSERT INTO assignments (course_id, title, instructions, description, due_date, max_score, file_path)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('issssds', $course_id, $title, $description, $description, $due, $max_score, $file_path);
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>'Assignment created.','id'=>$conn->insert_id]);
    } else {
        echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
    }
    exit;
}

// ── POST: update assignment ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $id          = (int)($_POST['id']          ?? 0);
    $title       = trim($_POST['title']        ?? '');
    $description = trim($_POST['description']  ?? '');
    $due_date    = trim($_POST['due_date']      ?? '');
    $max_score   = (float)($_POST['max_score'] ?? 100);

    if (!$id || !$title) {
        echo json_encode(['success'=>false,'message'=>'ID and title required.']); exit;
    }

    $file_path = null;
    if (!empty($_FILES['file']['name'])) {
        $upload_dir = '../uploads/assignments/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
        $ext     = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','jpg','jpeg','png','zip'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success'=>false,'message'=>'File type not allowed.']); exit;
        }
        // Delete old file
        $old = $conn->query("SELECT file_path FROM assignments WHERE id=$id")->fetch_assoc();
        if ($old && $old['file_path'] && file_exists('../'.$old['file_path'])) unlink('../'.$old['file_path']);
        $filename = 'assign_'.uniqid().'.'.$ext;
        move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir.$filename);
        $file_path = 'uploads/assignments/'.$filename;
    }

    $due = $due_date ?: null;
    if ($file_path) {
        $stmt = $conn->prepare("UPDATE assignments SET title=?, instructions=?, description=?, due_date=?, max_score=?, file_path=? WHERE id=?");
        $stmt->bind_param('ssssdsi', $title, $description, $description, $due, $max_score, $file_path, $id);
    } else {
        $stmt = $conn->prepare("UPDATE assignments SET title=?, instructions=?, description=?, due_date=?, max_score=? WHERE id=?");
        $stmt->bind_param('ssssdi', $title, $description, $description, $due, $max_score, $id);
    }
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>'Assignment updated.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
    }
    exit;
}

// ── DELETE: remove assignment ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $old = $conn->query("SELECT file_path FROM assignments WHERE id=$id")->fetch_assoc();
    if ($old && $old['file_path'] && file_exists('../'.$old['file_path'])) unlink('../'.$old['file_path']);
    $conn->query("DELETE FROM submissions WHERE assignment_id=$id");
    $conn->query("DELETE FROM assignments WHERE id=$id");
    echo json_encode(['success'=>true,'message'=>'Assignment deleted.']); exit;
}

// ── GET: list submissions for an assignment ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'submissions') {
    $assign_id = (int)($_GET['id'] ?? 0);
    $rows = [];
    $res  = $conn->query(
        "SELECT s.id AS submission_id, s.student_id, s.file_path, s.remarks, s.score,
                s.submitted_at, s.graded_at,
                u.first_name, u.last_name, u.school_id, u.section_dept
         FROM submissions s
         JOIN users u ON u.id = s.student_id
         WHERE s.assignment_id = $assign_id
         ORDER BY u.last_name, u.first_name"
    );
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

// ── POST: save grades for submissions ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'grade') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $grades = $body['grades'] ?? [];
    $saved  = 0;
    foreach ($grades as $g) {
        $sid     = (int)$g['submission_id'];
        $score   = $g['score'] !== '' ? (float)$g['score'] : null;
        $remarks = $conn->real_escape_string($g['remarks'] ?? '');
        if ($score !== null) {
            $conn->query("UPDATE submissions SET score=$score, remarks='$remarks', graded_at=NOW() WHERE id=$sid");
        } else {
            $conn->query("UPDATE submissions SET remarks='$remarks' WHERE id=$sid");
        }
        $saved++;
    }
    echo json_encode(['success'=>true,'message'=>"$saved grade(s) saved."]); exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid request.']);