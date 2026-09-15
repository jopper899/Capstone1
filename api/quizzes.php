    <?php
    // ============================================================
    //  Arandia College eLMS — Quizzes API
    //  File: api/quizzes.php
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

    function teacherOwnsCourse($conn, $teacher_id, $course_id)
    {
        $s = $conn->prepare("SELECT id FROM teacher_assignments WHERE teacher_id=? AND course_id=? LIMIT 1");
        $s->bind_param('ii', $teacher_id, $course_id);
        $s->execute();
        return (bool) $s->get_result()->fetch_assoc();
    }

    // ── GET: list quizzes for a course ────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        $course_id = (int) ($_GET['course_id'] ?? 0);
        if (!$course_id) {
            echo json_encode(['success' => false, 'message' => 'Course required.']);
            exit;
        }
        $rows = [];
        $res = $conn->query(
            "SELECT q.id, q.course_id, q.title, q.description, q.time_limit, q.max_score,
                    q.open_at, q.close_at, q.quiz_type, q.file_path,
                    DATE_FORMAT(q.created_at,'%b %d, %Y') AS created_at,
                    (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id=q.id) AS question_count,
                    (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=q.id) AS attempt_count
            FROM quizzes q
            WHERE q.course_id = $course_id
            ORDER BY q.created_at DESC"
        );
        while ($r = $res->fetch_assoc())
            $rows[] = $r;
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    // ── GET: get quiz with questions ──────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
        $quiz_id = (int) ($_GET['id'] ?? 0);
        $quiz = $conn->query("SELECT * FROM quizzes WHERE id=$quiz_id")->fetch_assoc();
        if (!$quiz) {
            echo json_encode(['success' => false, 'message' => 'Not found.']);
            exit;
        }
        $questions = [];
        $qRes = $conn->query("SELECT * FROM quiz_questions WHERE quiz_id=$quiz_id ORDER BY id");
        while ($q = $qRes->fetch_assoc()) {
            $choices = [];
            $cRes = $conn->query("SELECT * FROM quiz_choices WHERE question_id={$q['id']} ORDER BY id");
            while ($c = $cRes->fetch_assoc())
                $choices[] = $c;
            $q['choices'] = $choices;
            $questions[] = $q;
        }
        $quiz['questions'] = $questions;
        echo json_encode(['success' => true, 'data' => $quiz]);
        exit;
    }

    // ── POST (multipart or JSON): create quiz ─────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
        $isMultipart = !empty($_POST);

        if ($isMultipart) {
            $course_id = (int) ($_POST['course_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $time_limit = $_POST['time_limit'] !== '' ? (int) $_POST['time_limit'] : null;
            $max_score = (float) ($_POST['max_score'] ?? 100);
            $open_at = $_POST['open_at'] ?: null;
            $close_at = $_POST['close_at'] ?: null;
            $quiz_type = 'file';

            if (!$course_id || !$title) {
                echo json_encode(['success' => false, 'message' => 'Course and title required.']);
                exit;
            }
            if (!teacherOwnsCourse($conn, $teacher_id, $course_id)) {
                echo json_encode(['success' => false, 'message' => 'Not assigned to this course.']);
                exit;
            }

            $file_path = null;
            if (!empty($_FILES['file']['name'])) {
                $upload_dir = '../uploads/quizzes/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0775, true);
                $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'];
                if (!in_array($ext, $allowed)) {
                    echo json_encode(['success' => false, 'message' => 'File type not allowed.']);
                    exit;
                }
                $filename = 'quiz_' . uniqid() . '.' . $ext;
                if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $filename)) {
                    echo json_encode(['success' => false, 'message' => 'File upload failed.']);
                    exit;
                }
                $file_path = 'uploads/quizzes/' . $filename;
            }

            $stmt = $conn->prepare(
                "INSERT INTO quizzes (course_id, title, description, time_limit, max_score, open_at, close_at, quiz_type, file_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('issidssss', $course_id, $title, $description, $time_limit, $max_score, $open_at, $close_at, $quiz_type, $file_path);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Quiz created.', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
            }
            exit;
        }

        // JSON (question builder)
        $body = json_decode(file_get_contents('php://input'), true);
        $course_id = (int) ($body['course_id'] ?? 0);
        $title = trim($body['title'] ?? '');
        $description = trim($body['description'] ?? '');
        $time_limit = !empty($body['time_limit']) ? (int) $body['time_limit'] : null;
        $max_score = !empty($body['max_score']) ? (float) $body['max_score'] : 100;
        $open_at = !empty($body['open_at']) ? $body['open_at'] : null;
        $close_at = !empty($body['close_at']) ? $body['close_at'] : null;
        $quiz_type = 'questions';
        $questions = $body['questions'] ?? [];

        if (!$course_id || !$title) {
            echo json_encode(['success' => false, 'message' => 'Course and title required.']);
            exit;
        }
        if (!teacherOwnsCourse($conn, $teacher_id, $course_id)) {
            echo json_encode(['success' => false, 'message' => 'Not assigned to this course.']);
            exit;
        }
        if (empty($questions)) {
            echo json_encode(['success' => false, 'message' => 'Add at least one question.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "INSERT INTO quizzes (course_id, title, description, time_limit, max_score, open_at, close_at, quiz_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('issidsss', $course_id, $title, $description, $time_limit, $max_score, $open_at, $close_at, $quiz_type);
            $stmt->execute();
            $quiz_id = $conn->insert_id;

            foreach ($questions as $q) {
                $qtext = trim($q['question_text'] ?? '');
                $qtype = $q['question_type'] ?? 'multiple_choice';
                $pts = (float) ($q['points'] ?? 1);
                if (!$qtext)
                    continue;
                $qs = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type, points) VALUES (?,?,?,?)");
                $qs->bind_param('issd', $quiz_id, $qtext, $qtype, $pts);
                $qs->execute();
                $q_id = $conn->insert_id;
                foreach ($q['choices'] ?? [] as $c) {
                    $ctext = trim($c['choice_text'] ?? '');
                    $is_cor = (int) ($c['is_correct'] ?? 0);
                    if (!$ctext)
                        continue;
                    $cs = $conn->prepare("INSERT INTO quiz_choices (question_id, choice_text, is_correct) VALUES (?,?,?)");
                    $cs->bind_param('isi', $q_id, $ctext, $is_cor);
                    $cs->execute();
                }
            }
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Quiz created.', 'id' => $quiz_id]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── POST: update quiz (settings only) ────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
        $isMultipart = !empty($_POST);

        $id = (int) (($_POST['id'] ?? 0) ?: (json_decode(file_get_contents('php://input'), true)['id'] ?? 0));
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Quiz ID required.']);
            exit;
        }

        if ($isMultipart) {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $time_limit = $_POST['time_limit'] !== '' ? (int) $_POST['time_limit'] : null;
            $max_score = (float) ($_POST['max_score'] ?? 100);
            $open_at = $_POST['open_at'] ?: null;
            $close_at = $_POST['close_at'] ?: null;

            $file_path = null;
            if (!empty($_FILES['file']['name'])) {
                $upload_dir = '../uploads/quizzes/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0775, true);
                $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                $old = $conn->query("SELECT file_path FROM quizzes WHERE id=$id")->fetch_assoc();
                if ($old && $old['file_path'] && file_exists('../' . $old['file_path']))
                    unlink('../' . $old['file_path']);
                $filename = 'quiz_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $filename);
                $file_path = 'uploads/quizzes/' . $filename;
            }

            if ($file_path) {
                $stmt = $conn->prepare("UPDATE quizzes SET title=?, description=?, time_limit=?, max_score=?, open_at=?, close_at=?, file_path=? WHERE id=?");
                $stmt->bind_param('ssidsssi', $title, $description, $time_limit, $max_score, $open_at, $close_at, $file_path, $id);
            } else {
                $stmt = $conn->prepare("UPDATE quizzes SET title=?, description=?, time_limit=?, max_score=?, open_at=?, close_at=? WHERE id=?");
                $stmt->bind_param('ssidssi', $title, $description, $time_limit, $max_score, $open_at, $close_at, $id);
            }
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Quiz updated.']);
            exit;
        }

        // JSON update
        $body = json_decode(file_get_contents('php://input'), true);
        $title = trim($body['title'] ?? '');
        $description = trim($body['description'] ?? '');
        $time_limit = !empty($body['time_limit']) ? (int) $body['time_limit'] : null;
        $max_score = !empty($body['max_score']) ? (float) $body['max_score'] : 100;
        $open_at = $body['open_at'] ?: null;
        $close_at = $body['close_at'] ?: null;

        $stmt = $conn->prepare("UPDATE quizzes SET title=?, description=?, time_limit=?, max_score=?, open_at=?, close_at=? WHERE id=?");
        $stmt->bind_param('ssidssi', $title, $description, $time_limit, $max_score, $open_at, $close_at, $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Quiz updated.']);
        exit;
    }

    // ── DELETE: remove quiz ───────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'delete') {
        $id = (int) ($_GET['id'] ?? 0);
        $old = $conn->query("SELECT file_path FROM quizzes WHERE id=$id")->fetch_assoc();
        if ($old && $old['file_path'] && file_exists('../' . $old['file_path']))
            unlink('../' . $old['file_path']);
        $conn->query("DELETE qc FROM quiz_choices qc JOIN quiz_questions qq ON qq.id=qc.question_id WHERE qq.quiz_id=$id");
        $conn->query("DELETE FROM quiz_questions WHERE quiz_id=$id");
        $conn->query("DELETE FROM quiz_attempts WHERE quiz_id=$id");
        $conn->query("DELETE FROM quizzes WHERE id=$id");
        echo json_encode(['success' => true, 'message' => 'Quiz deleted.']);
        exit;
    }

    // ── GET: results for a quiz ───────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'results') {
        $quiz_id = (int) ($_GET['id'] ?? 0);
        $rows = [];
        $res = $conn->query(
            "SELECT qa.id AS attempt_id, qa.student_id, qa.score, qa.remarks,
                    qa.started_at, qa.finished_at, qa.submitted_at, qa.status,
                    qa.file_path AS student_file_path,
                    u.first_name, u.last_name, u.school_id, u.section_dept,
                    q.max_score, q.quiz_type
            FROM quiz_attempts qa
            JOIN users u ON u.id = qa.student_id
            JOIN quizzes q ON q.id = qa.quiz_id
            WHERE qa.quiz_id = $quiz_id
            ORDER BY qa.submitted_at DESC"
        );
        while ($r = $res->fetch_assoc()) {
            // Rename to file_path so JS can use r.file_path as before
            $r['file_path'] = $r['student_file_path'];
            unset($r['student_file_path']);
            $rows[] = $r;
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    // ── POST: save grades for quiz attempts (file-type) ───────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'grade') {
        $body = json_decode(file_get_contents('php://input'), true);
        $grades = $body['grades'] ?? [];
        foreach ($grades as $g) {
            $aid = (int) $g['attempt_id'];
            $score = $g['score'] !== '' ? (float) $g['score'] : null;
            $remarks = $conn->real_escape_string($g['remarks'] ?? '');
            if ($score !== null) {
                $conn->query("UPDATE quiz_attempts SET score=$score, remarks='$remarks', status='Graded', finished_at=COALESCE(finished_at,NOW()) WHERE id=$aid");
            } else {
                $conn->query("UPDATE quiz_attempts SET remarks='$remarks' WHERE id=$aid");
            }
        }
        echo json_encode(['success' => true, 'message' => 'Grades saved.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request.']);