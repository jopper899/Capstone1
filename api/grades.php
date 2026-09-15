<?php
$sessionCookieParams = session_get_cookie_params();
session_set_cookie_params(
    $sessionCookieParams['lifetime'],
    '/',
    $sessionCookieParams['domain'],
    $sessionCookieParams['secure'],
    $sessionCookieParams['httponly']
);
session_name('PHPSESSID');
session_start();
require_once '../config/conn.php';

// ── Always return JSON, never HTML ──────────────────────────
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_exception_handler(function ($e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
});
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (error_reporting() === 0)
        return false;
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => "$errstr in $errfile line $errline"]);
    exit;
});

// ── Auth check ───────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please refresh the page and log in again.',
        'debug' => ['session_id' => session_id(), 'session_keys' => array_keys($_SESSION)]
    ]);
    exit;
}

$teacher_id = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

function teacherOwnsCourse(mysqli $conn, int $teacher_id, int $course_id): bool
{
    $stmt = $conn->prepare("SELECT id FROM teacher_assignments WHERE teacher_id = ? AND course_id = ? LIMIT 1");
    $stmt->bind_param('ii', $teacher_id, $course_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool) $row;
}

// ============================================================
//  ACTION: list
// ============================================================
if ($action === 'list') {
    header('Content-Type: application/json');

    $course_id = intval($_GET['course_id'] ?? 0);
    if (!$course_id || !teacherOwnsCourse($conn, $teacher_id, $course_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid course or access denied. teacher_id=' . $teacher_id . ' course_id=' . $course_id]);
        exit;
    }

    $columns = [];

    $qRes = $conn->query("SELECT id, title, max_score FROM quizzes WHERE course_id = $course_id ORDER BY created_at");
    if ($qRes)
        while ($q = $qRes->fetch_assoc())
            $columns[] = ['id' => 'q_' . $q['id'], 'label' => $q['title'], 'type' => 'quiz', 'source_id' => (int) $q['id'], 'max_score' => (int) $q['max_score']];

    $aRes = $conn->query("SELECT id, title, max_score FROM assignments WHERE course_id = $course_id ORDER BY created_at");
    if ($aRes)
        while ($a = $aRes->fetch_assoc())
            $columns[] = ['id' => 'a_' . $a['id'], 'label' => $a['title'], 'type' => 'assignment', 'source_id' => (int) $a['id'], 'max_score' => (int) $a['max_score']];

    $gRes = $conn->query("SELECT DISTINCT item_id, item_name, max_score FROM grades WHERE course_id = $course_id AND item_id IS NOT NULL AND item_name IS NOT NULL ORDER BY recorded_at");
    if ($gRes) {
        while ($g = $gRes->fetch_assoc()) {
            $exists = false;
            foreach ($columns as $c) {
                if ($c['source_id'] == $g['item_id']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists)
                $columns[] = ['id' => 'g_' . $g['item_id'], 'label' => $g['item_name'], 'type' => 'grade_entry', 'source_id' => (int) $g['item_id'], 'max_score' => (float) ($g['max_score'] ?? 100)];
        }
    }

    $stuRes = $conn->query("SELECT DISTINCT u.id AS student_id, u.first_name, u.last_name, u.school_id, u.section_dept FROM enrollments e JOIN users u ON u.id = e.student_id WHERE e.course_id = $course_id AND u.role = 'Student' ORDER BY u.last_name, u.first_name");
    $students = [];
    if ($stuRes)
        while ($s = $stuRes->fetch_assoc())
            $students[] = $s;

    if (empty($students)) {
        echo json_encode(['success' => true, 'data' => ['columns' => $columns, 'students' => []]]);
        exit;
    }

    $studentIds = implode(',', array_map('intval', array_column($students, 'student_id')));

    $quizScores = [];
    $quizCols = array_values(array_filter($columns, fn($c) => $c['type'] === 'quiz'));
    if (!empty($quizCols)) {
        $quizIds = implode(',', array_map(fn($c) => $c['source_id'], $quizCols));
        $qsRes = $conn->query("SELECT student_id, quiz_id, score FROM quiz_attempts WHERE quiz_id IN ($quizIds) AND student_id IN ($studentIds) AND score IS NOT NULL ORDER BY finished_at DESC");
        if ($qsRes)
            while ($qs = $qsRes->fetch_assoc())
                if (!isset($quizScores[$qs['student_id']][$qs['quiz_id']]))
                    $quizScores[$qs['student_id']][$qs['quiz_id']] = (float) $qs['score'];
    }

    $assignScores = [];
    $assignCols = array_values(array_filter($columns, fn($c) => $c['type'] === 'assignment'));
    if (!empty($assignCols)) {
        $assignIds = implode(',', array_map(fn($c) => $c['source_id'], $assignCols));
        $subRes = $conn->query("SELECT student_id, assignment_id, score FROM submissions WHERE assignment_id IN ($assignIds) AND student_id IN ($studentIds) AND score IS NOT NULL");
        if ($subRes)
            while ($sub = $subRes->fetch_assoc())
                $assignScores[$sub['student_id']][$sub['assignment_id']] = (float) $sub['score'];
    }

    $allGradeRows = [];
    $grRes = $conn->query("SELECT student_id, item_id, raw_score FROM grades WHERE course_id = $course_id AND student_id IN ($studentIds) AND item_id IS NOT NULL AND raw_score IS NOT NULL");
    if ($grRes)
        while ($gr = $grRes->fetch_assoc())
            $allGradeRows[$gr['student_id']][$gr['item_id']] = (float) $gr['raw_score'];

    foreach ($students as &$s) {
        $sid = $s['student_id'];
        $s['scores'] = [];
        foreach ($columns as $col) {
            if ($col['type'] === 'quiz')
                $score = $quizScores[$sid][$col['source_id']] ?? $allGradeRows[$sid][$col['source_id']] ?? null;
            elseif ($col['type'] === 'assignment')
                $score = $assignScores[$sid][$col['source_id']] ?? $allGradeRows[$sid][$col['source_id']] ?? null;
            else
                $score = $allGradeRows[$sid][$col['source_id']] ?? null;
            $s['scores'][$col['id']] = $score;
        }
    }
    unset($s);

    echo json_encode(['success' => true, 'data' => ['columns' => $columns, 'students' => $students]]);
    exit;
}

// ============================================================
//  ACTION: save
// ============================================================
if ($action === 'save') {
    header('Content-Type: application/json');
    $body = json_decode(file_get_contents('php://input'), true);
    $grades = $body['grades'] ?? [];
    if (empty($grades)) {
        echo json_encode(['success' => false, 'message' => 'No grades provided.']);
        exit;
    }

    $saved = 0;
    $errors = [];
    $quizNames = [];
    $assignNames = [];

    foreach ($grades as $entry) {
        $student_id = intval($entry['student_id'] ?? 0);
        $col_id = $entry['col_id'] ?? '';
        $score = ($entry['score'] !== null && $entry['score'] !== '') ? floatval($entry['score']) : null;
        $course_id = intval($entry['course_id'] ?? 0);
        if (!$student_id || !$col_id || !$course_id)
            continue;
        if (!teacherOwnsCourse($conn, $teacher_id, $course_id))
            continue;

        if (str_starts_with($col_id, 'q_')) {
            $item_id = intval(substr($col_id, 2));
            if (!isset($quizNames[$item_id])) {
                $r = $conn->query("SELECT title, max_score FROM quizzes WHERE id = $item_id LIMIT 1");
                $quizNames[$item_id] = ($r ? $r->fetch_assoc() : null) ?? ['title' => 'Quiz', 'max_score' => 100];
            }
            $item_name = $quizNames[$item_id]['title'];
            $max_score = $quizNames[$item_id]['max_score'];
        } elseif (str_starts_with($col_id, 'a_')) {
            $item_id = intval(substr($col_id, 2));
            if (!isset($assignNames[$item_id])) {
                $r = $conn->query("SELECT title, max_score FROM assignments WHERE id = $item_id LIMIT 1");
                $assignNames[$item_id] = ($r ? $r->fetch_assoc() : null) ?? ['title' => 'Assignment', 'max_score' => 100];
            }
            $item_name = $assignNames[$item_id]['title'];
            $max_score = $assignNames[$item_id]['max_score'];
        } elseif (str_starts_with($col_id, 'g_')) {
            $item_id = intval(substr($col_id, 2));
            $item_name = 'Grade Entry';
            $max_score = 100;
        } else
            continue;

        $final_score = ($score !== null && $max_score > 0) ? round(($score / $max_score) * 100, 4) : null;
        $stmt = $conn->prepare("INSERT INTO grades (student_id, course_id, item_id, item_name, max_score, raw_score, final_score, recorded_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE raw_score = VALUES(raw_score), final_score = VALUES(final_score), recorded_at = NOW()");
        if (!$stmt) {
            $errors[] = "Prepare: " . $conn->error;
            continue;
        }
        $stmt->bind_param('iiisddd', $student_id, $course_id, $item_id, $item_name, $max_score, $score, $final_score);
        if ($stmt->execute())
            $saved++;
        else
            $errors[] = $stmt->error;
        $stmt->close();
    }

    echo json_encode(['success' => true, 'message' => empty($errors) ? "Saved $saved entries." : "Saved $saved. Errors: " . implode('; ', $errors)]);
    exit;
}

// ============================================================
//  ACTION: export
// ============================================================
if ($action === 'export') {
    $course_id = intval($_GET['course_id'] ?? 0);
    if (!$course_id || !teacherOwnsCourse($conn, $teacher_id, $course_id)) {
        http_response_code(403);
        echo 'Unauthorized';
        exit;
    }

    $courseRow = $conn->query("SELECT course_name, course_code FROM courses WHERE id = $course_id LIMIT 1")->fetch_assoc();
    $courseName = ($courseRow['course_code'] ?? '') . '_' . ($courseRow['course_name'] ?? '');
    $columns = [];
    $qRes = $conn->query("SELECT id, title, max_score FROM quizzes WHERE course_id = $course_id ORDER BY created_at");
    if ($qRes)
        while ($q = $qRes->fetch_assoc())
            $columns[] = ['id' => 'q_' . $q['id'], 'label' => $q['title'], 'type' => 'quiz', 'source_id' => (int) $q['id'], 'max_score' => (int) $q['max_score']];
    $aRes = $conn->query("SELECT id, title, max_score FROM assignments WHERE course_id = $course_id ORDER BY created_at");
    if ($aRes)
        while ($a = $aRes->fetch_assoc())
            $columns[] = ['id' => 'a_' . $a['id'], 'label' => $a['title'], 'type' => 'assignment', 'source_id' => (int) $a['id'], 'max_score' => (int) $a['max_score']];

    $stuRes = $conn->query("SELECT DISTINCT u.id AS student_id, u.first_name, u.last_name, u.school_id, u.section_dept FROM enrollments e JOIN users u ON u.id = e.student_id WHERE e.course_id = $course_id AND u.role = 'Student' ORDER BY u.last_name, u.first_name");
    $students = [];
    if ($stuRes)
        while ($s = $stuRes->fetch_assoc())
            $students[] = $s;
    $studentIds = empty($students) ? '0' : implode(',', array_map(fn($s) => intval($s['student_id']), $students));

    $quizScores = [];
    $assignScores = [];
    $gradeRows = [];
    $quizCols = array_values(array_filter($columns, fn($c) => $c['type'] === 'quiz'));
    if (!empty($quizCols)) {
        $quizIds = implode(',', array_map(fn($c) => $c['source_id'], $quizCols));
        $qsRes = $conn->query("SELECT student_id, quiz_id, score FROM quiz_attempts WHERE quiz_id IN ($quizIds) AND student_id IN ($studentIds) AND score IS NOT NULL ORDER BY finished_at DESC");
        if ($qsRes)
            while ($qs = $qsRes->fetch_assoc())
                if (!isset($quizScores[$qs['student_id']][$qs['quiz_id']]))
                    $quizScores[$qs['student_id']][$qs['quiz_id']] = (float) $qs['score'];
    }
    $assignCols = array_values(array_filter($columns, fn($c) => $c['type'] === 'assignment'));
    if (!empty($assignCols)) {
        $assignIds = implode(',', array_map(fn($c) => $c['source_id'], $assignCols));
        $subRes = $conn->query("SELECT student_id, assignment_id, score FROM submissions WHERE assignment_id IN ($assignIds) AND student_id IN ($studentIds) AND score IS NOT NULL");
        if ($subRes)
            while ($sub = $subRes->fetch_assoc())
                if (!isset($assignScores[$sub['student_id']][$sub['assignment_id']]))
                    $assignScores[$sub['student_id']][$sub['assignment_id']] = (float) $sub['score'];
    }
    $grRes = $conn->query("SELECT student_id, item_id, raw_score FROM grades WHERE course_id = $course_id AND student_id IN ($studentIds) AND item_id IS NOT NULL AND raw_score IS NOT NULL");
    if ($grRes)
        while ($gr = $grRes->fetch_assoc())
            $gradeRows[$gr['student_id']][$gr['item_id']] = (float) $gr['raw_score'];

    $filename = 'grades_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $courseName) . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    $headerRow = ['Student Name', 'School ID', 'Section'];
    foreach ($columns as $col)
        $headerRow[] = $col['label'] . ' (/' . $col['max_score'] . ')';
    $headerRow[] = 'Average';
    $headerRow[] = 'Remarks';
    fputcsv($out, $headerRow);
    foreach ($students as $s) {
        $sid = $s['student_id'];
        $row = [$s['last_name'] . ', ' . $s['first_name'], $s['school_id'] ?? '', $s['section_dept'] ?? ''];
        $vals = [];
        foreach ($columns as $col) {
            $sc = $col['type'] === 'quiz' ? ($quizScores[$sid][$col['source_id']] ?? $gradeRows[$sid][$col['source_id']] ?? null) : ($assignScores[$sid][$col['source_id']] ?? $gradeRows[$sid][$col['source_id']] ?? null);
            $row[] = $sc !== null ? $sc : '';
            if ($sc !== null)
                $vals[] = $sc;
        }
        $avg = count($vals) ? round(array_sum($vals) / count($vals), 2) : '';
        $remark = $avg !== '' ? ($avg >= 90 ? 'Excellent' : ($avg >= 80 ? 'Good' : ($avg >= 70 ? 'Fair' : ($avg >= 60 ? 'Passing' : 'Failing')))) : '';
        $row[] = $avg;
        $row[] = $remark;
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Unknown action.']);