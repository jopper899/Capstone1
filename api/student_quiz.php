<?php
// ============================================================
//  Arandia College eLMS — Student Quiz API
//  File: api/student_quiz.php
// ============================================================
session_start();
require_once '../config/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']); exit;
}

$student_id = (int)$_SESSION['user_id'];
$action     = $_GET['action'] ?? '';

// ── GET: list quizzes available to student ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $rows = [];
    $res  = $conn->query(
        "SELECT q.id, q.title, q.description, q.time_limit, q.max_score,
                q.open_at, q.close_at, q.quiz_type, q.file_path,
                c.course_name, c.course_code,
                (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) AS question_count,
                (SELECT id FROM quiz_attempts
                 WHERE quiz_id = q.id AND student_id = $student_id
                 ORDER BY FIELD(status,'Graded','Submitted','In Progress') ASC, started_at DESC LIMIT 1) AS attempt_id,
                (SELECT status FROM quiz_attempts
                 WHERE quiz_id = q.id AND student_id = $student_id
                 ORDER BY FIELD(status,'Graded','Submitted','In Progress') ASC, started_at DESC LIMIT 1) AS attempt_status,
                (SELECT score FROM quiz_attempts
                 WHERE quiz_id = q.id AND student_id = $student_id
                 ORDER BY FIELD(status,'Graded','Submitted','In Progress') ASC, started_at DESC LIMIT 1) AS my_score
         FROM quizzes q
         JOIN courses c ON c.id = q.course_id
         JOIN enrollments e ON e.course_id = q.course_id AND e.student_id = $student_id
         ORDER BY q.created_at DESC"
    );
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}

// ── GET: get quiz questions for taking ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'start') {
    $quiz_id = (int)($_GET['id'] ?? 0);

    $chk = $conn->query(
        "SELECT q.id, q.title, q.time_limit, q.max_score, q.open_at, q.close_at,
                c.course_name
         FROM quizzes q
         JOIN courses c ON c.id = q.course_id
         JOIN enrollments e ON e.course_id = q.course_id AND e.student_id = $student_id
         WHERE q.id = $quiz_id LIMIT 1"
    );
    $quiz = $chk->fetch_assoc();
    if (!$quiz) { echo json_encode(['success'=>false,'message'=>'Quiz not found or not enrolled.']); exit; }

    // Check if already submitted — priority: Graded > Submitted > In Progress
    $prev = $conn->query(
        "SELECT id, status, score FROM quiz_attempts
         WHERE quiz_id = $quiz_id AND student_id = $student_id
         ORDER BY FIELD(status,'Graded','Submitted','In Progress') ASC, started_at DESC LIMIT 1"
    )->fetch_assoc();

    if ($prev && ($prev['status'] === 'Submitted' || $prev['status'] === 'Graded')) {
        echo json_encode(['success'=>false,'message'=>'Already submitted.','score'=>$prev['score'],'attempt_id'=>$prev['id']]); exit;
    }

    // Create or reuse In Progress attempt
    if (!$prev || $prev['status'] !== 'In Progress') {
        $conn->query("INSERT INTO quiz_attempts (quiz_id, student_id) VALUES ($quiz_id, $student_id)");
        $attempt_id = $conn->insert_id;
    } else {
        $attempt_id = $prev['id'];
    }

    // Fetch questions WITHOUT is_correct
    $questions = [];
    $qRes = $conn->query("SELECT id, question_text, question_type, points FROM quiz_questions WHERE quiz_id = $quiz_id ORDER BY id");
    while ($q = $qRes->fetch_assoc()) {
        $choices = [];
        $cRes = $conn->query("SELECT id, choice_text FROM quiz_choices WHERE question_id = {$q['id']} ORDER BY id");
        while ($c = $cRes->fetch_assoc()) $choices[] = $c;
        $q['choices'] = $choices;
        $questions[]  = $q;
    }

    $quiz['questions']  = $questions;
    $quiz['attempt_id'] = $attempt_id;
    echo json_encode(['success' => true, 'data' => $quiz]); exit;
}

// ── POST: submit quiz answers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit') {
    $body       = json_decode(file_get_contents('php://input'), true);
    $attempt_id = (int)($body['attempt_id'] ?? 0);
    $answers    = $body['answers'] ?? [];

    $att = $conn->query(
        "SELECT qa.id, qa.quiz_id, qa.status, q.max_score
         FROM quiz_attempts qa JOIN quizzes q ON q.id = qa.quiz_id
         WHERE qa.id = $attempt_id AND qa.student_id = $student_id LIMIT 1"
    )->fetch_assoc();

    if (!$att) { echo json_encode(['success'=>false,'message'=>'Invalid attempt.']); exit; }
    if ($att['status'] === 'Submitted' || $att['status'] === 'Graded') {
        echo json_encode(['success'=>false,'message'=>'Already submitted.']); exit;
    }

    // Calculate score
    $total_score = 0;
    foreach ($answers as $q_id => $c_id) {
        $q_id = (int)$q_id;
        $c_id = (int)$c_id;
        $correct = $conn->query(
            "SELECT qq.points FROM quiz_choices qc
             JOIN quiz_questions qq ON qq.id = qc.question_id
             WHERE qc.id = $c_id AND qc.question_id = $q_id AND qc.is_correct = 1"
        )->fetch_assoc();
        if ($correct) $total_score += (float)$correct['points'];
    }

    $max_pts = (float)$conn->query(
        "SELECT SUM(points) AS t FROM quiz_questions WHERE quiz_id = {$att['quiz_id']}"
    )->fetch_assoc()['t'];

    $scaled = $max_pts > 0 ? round(($total_score / $max_pts) * (float)$att['max_score'], 2) : 0;

    $conn->query(
        "UPDATE quiz_attempts SET score=$scaled, submitted_at=NOW(), finished_at=NOW(), status='Submitted'
         WHERE id=$attempt_id"
    );

    echo json_encode(['success'=>true,'score'=>$scaled,'max_score'=>$att['max_score'],'message'=>'Quiz submitted!']); exit;
}

// ── POST: submit file for file-type quiz ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit_file') {
    $quiz_id = (int)($_POST['quiz_id'] ?? 0);
    if (!$quiz_id) { echo json_encode(['success'=>false,'message'=>'Quiz ID required.']); exit; }

    // Verify quiz is file-type
    $quiz = $conn->query("SELECT * FROM quizzes WHERE id=$quiz_id AND quiz_type='file'")->fetch_assoc();
    if (!$quiz) { echo json_encode(['success'=>false,'message'=>'Quiz not found or not a file-type quiz.']); exit; }

    // Check enrollment
    $enroll = $conn->query("SELECT id FROM enrollments WHERE student_id=$student_id AND course_id={$quiz['course_id']} LIMIT 1")->fetch_assoc();
    if (!$enroll) { echo json_encode(['success'=>false,'message'=>'Not enrolled in this course.']); exit; }

    // Check if already graded — do not allow resubmission if graded
    $graded = $conn->query(
        "SELECT id FROM quiz_attempts
         WHERE quiz_id=$quiz_id AND student_id=$student_id AND status='Graded' LIMIT 1"
    )->fetch_assoc();
    if ($graded) {
        echo json_encode(['success'=>false,'message'=>'This quiz has already been graded. Resubmission is not allowed.']); exit;
    }

    // Handle file upload
    if (empty($_FILES['file']['name'])) {
        echo json_encode(['success'=>false,'message'=>'Please select a file to upload.']); exit;
    }
    $upload_dir = '../uploads/quiz_submissions/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
    $ext     = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','jpg','jpeg','png','zip','txt'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success'=>false,'message'=>'File type not allowed. Use PDF, DOCX, JPG, PNG, etc.']); exit;
    }
    if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success'=>false,'message'=>'Max file size is 10MB.']); exit;
    }
    $filename = 'qsub_'.$student_id.'_'.$quiz_id.'_'.uniqid().'.'.$ext;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir.$filename)) {
        echo json_encode(['success'=>false,'message'=>'File upload failed.']); exit;
    }
    $file_path = 'uploads/quiz_submissions/'.$filename;

    // Delete all old non-graded attempts to prevent duplicates
    $conn->query(
        "DELETE FROM quiz_attempts
         WHERE quiz_id=$quiz_id AND student_id=$student_id AND status != 'Graded'"
    );

    // Insert fresh submitted attempt
    $conn->query(
        "INSERT INTO quiz_attempts (quiz_id, student_id, file_path, started_at, submitted_at, finished_at, status)
         VALUES ($quiz_id, $student_id, '$file_path', NOW(), NOW(), NOW(), 'Submitted')"
    );

    echo json_encode(['success'=>true,'message'=>'File submitted successfully! Your teacher will review and grade it.']); exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid request.']);