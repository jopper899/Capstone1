<?php
// ============================================================
//  Arandia College eLMS — Attendance API
//  File: api/attendance.php
//  Actions: list, save, export
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 0);

// ── Disable mysqli strict exceptions so failed prepares return
//    false instead of throwing, letting our guards work properly
mysqli_report(MYSQLI_REPORT_OFF);

// ── Global error handler — always return JSON on PHP errors ──
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => "PHP Error [$errno]: $errstr on line $errline",
    ]);
    exit;
});

// ── Global exception handler ─────────────────────────────────
set_exception_handler(function (Throwable $e): void {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage() . ' on line ' . $e->getLine(),
    ]);
    exit;
});

// ── Catch fatal errors (out-of-memory, parse errors, etc.) ───
register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error: ' . $err['message'] . ' on line ' . $err['line'],
        ]);
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conn.php';

// ── Auth check ────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$teacher_id = (int) $_SESSION['user_id'];
$action     = trim($_GET['action'] ?? '');

// ── Helper: respond with JSON error and exit ──────────────────
function jsonError(string $msg): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// ── Validate teacher is assigned to this course ───────────────
function validateCourseOwnership(mysqli $conn, int $teacher_id, int $course_id): bool
{
    $stmt = $conn->prepare(
        "SELECT 1 FROM teacher_assignments
         WHERE teacher_id = ? AND course_id = ? LIMIT 1"
    );
    if (!$stmt) return false;
    $stmt->bind_param('ii', $teacher_id, $course_id);
    $stmt->execute();
    $count = $stmt->get_result()->num_rows;
    $stmt->close();
    return $count > 0;
}

// ═══════════════════════════════════════════════════════════════
//  LIST  —  students + existing attendance for a course & date
// ═══════════════════════════════════════════════════════════════
if ($action === 'list') {
    header('Content-Type: application/json; charset=utf-8');

    $course_id = (int) ($_GET['course_id'] ?? 0);
    $date      = trim($_GET['date'] ?? date('Y-m-d'));

    if (!$course_id) jsonError('Missing course_id.');

    if (!validateCourseOwnership($conn, $teacher_id, $course_id))
        jsonError('You do not teach this subject.');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
        jsonError('Invalid date format. Expected YYYY-MM-DD.');

    // ── Fetch enrolled, active students ──────────────────────
    $stmt = $conn->prepare(
        "SELECT u.id,
                COALESCE(u.school_id,    '') AS school_id,
                COALESCE(u.first_name,   '') AS first_name,
                COALESCE(u.last_name,    '') AS last_name,
                COALESCE(u.section_dept, '') AS section_dept
         FROM   enrollments e
         JOIN   users       u ON u.id = e.student_id
         WHERE  e.course_id = ?
           AND  e.status    = 'Enrolled'
           AND  u.role      = 'Student'
           AND  u.status    = 'Active'
         ORDER  BY u.section_dept, u.last_name, u.first_name"
    );
    if (!$stmt) jsonError('DB prepare error (students): ' . $conn->error);

    $stmt->bind_param('i', $course_id);
    if (!$stmt->execute()) jsonError('DB execute error (students): ' . $stmt->error);

    $result   = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'id'           => (int) $row['id'],
            'school_id'    => $row['school_id'],
            'first_name'   => $row['first_name'],
            'last_name'    => $row['last_name'],
            'section_dept' => $row['section_dept'],
        ];
    }
    $stmt->close();

    // ── Fetch existing attendance for that date ───────────────
    // `date` is backticked — DATE is a reserved word in MySQL
    $stmt = $conn->prepare(
        "SELECT student_id,
                status,
                COALESCE(remark, '') AS remark
         FROM   attendance
         WHERE  course_id = ?
           AND  `date`    = ?"
    );
    if (!$stmt) jsonError('DB prepare error (attendance): ' . $conn->error);

    $stmt->bind_param('is', $course_id, $date);
    if (!$stmt->execute()) jsonError('DB execute error (attendance): ' . $stmt->error);

    $result     = $stmt->get_result();
    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        $attendance[(int) $row['student_id']] = [
            'status' => $row['status'],
            'remark' => $row['remark'],
        ];
    }
    $stmt->close();

    echo json_encode([
        'success'    => true,
        'students'   => $students,
        'attendance' => $attendance,
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  SAVE  —  upsert attendance records
// ═══════════════════════════════════════════════════════════════
if ($action === 'save') {
    header('Content-Type: application/json; charset=utf-8');

    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) jsonError('Invalid JSON body.');

    $course_id = (int)  ($input['course_id'] ?? 0);
    $date      = trim($input['date']         ?? '');
    $records   = $input['records']           ?? [];

    if (!$course_id) jsonError('Missing course_id.');
    if (!validateCourseOwnership($conn, $teacher_id, $course_id))
        jsonError('You do not teach this subject.');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
        jsonError('Invalid date format. Expected YYYY-MM-DD.');
    if (!is_array($records) || empty($records))
        jsonError('No records to save.');

    $validStatuses = ['Present', 'Absent', 'Late', 'Excused'];

    // Fetch all enrolled student IDs in ONE query (not inside loop)
    $enrollStmt = $conn->prepare(
        "SELECT student_id FROM enrollments
         WHERE  course_id = ? AND status = 'Enrolled'"
    );
    if (!$enrollStmt) jsonError('DB prepare error (enroll): ' . $conn->error);
    $enrollStmt->bind_param('i', $course_id);
    if (!$enrollStmt->execute()) jsonError('DB execute error (enroll): ' . $enrollStmt->error);

    $enrollResult = $enrollStmt->get_result();
    $enrolledIds  = [];
    while ($er = $enrollResult->fetch_assoc()) {
        $enrolledIds[(int) $er['student_id']] = true;
    }
    $enrollStmt->close();

    // Prepare upsert ONCE — reuse for every record.
    // `date` backticked (reserved word). updated_at handled by column DEFAULT.
    $upsert = $conn->prepare(
        "INSERT INTO attendance
             (student_id, course_id, `date`, status, remark, recorded_by)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             status      = VALUES(status),
             remark      = VALUES(remark),
             recorded_by = VALUES(recorded_by)"
    );
    if (!$upsert) jsonError('DB prepare error (upsert): ' . $conn->error);

    $saved = 0;
    foreach ($records as $rec) {
        if (!is_array($rec)) continue;

        $student_id = (int)  ($rec['student_id'] ?? 0);
        $status     =        trim($rec['status'] ?? 'Present');
        $remark     =        trim($rec['remark'] ?? '');

        if (!$student_id)                              continue;
        if (!in_array($status, $validStatuses, true))  continue;
        if (!isset($enrolledIds[$student_id]))          continue;

        $upsert->bind_param(
            'iisssi',
            $student_id, $course_id, $date, $status, $remark, $teacher_id
        );
        if ($upsert->execute()) $saved++;
    }
    $upsert->close();

    echo json_encode([
        'success' => true,
        'message' => "Attendance saved. {$saved} record(s) updated.",
        'saved'   => $saved,
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  EXPORT  —  download CSV
// ═══════════════════════════════════════════════════════════════
if ($action === 'export') {

    $course_id = (int) ($_GET['course_id'] ?? 0);
    $date      = trim($_GET['date'] ?? '');

    if (!$course_id) jsonError('Missing course_id.');
    if (!validateCourseOwnership($conn, $teacher_id, $course_id))
        jsonError('You do not teach this subject.');

    // ── Course info ───────────────────────────────────────────
    $cStmt = $conn->prepare(
        "SELECT course_code, course_name FROM courses WHERE id = ? LIMIT 1"
    );
    if (!$cStmt) jsonError('DB prepare error (course): ' . $conn->error);
    $cStmt->bind_param('i', $course_id);
    if (!$cStmt->execute()) jsonError('DB execute error (course): ' . $cStmt->error);
    $courseInfo = $cStmt->get_result()->fetch_assoc();
    $cStmt->close();

    // Guard against course not found
    if (!$courseInfo) jsonError('Course not found.');

    $safeCode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $courseInfo['course_code']);
    $safeDate = preg_replace('/[^0-9\-]/', '',          $date ?: 'all');
    $filename = 'attendance_' . $safeCode . '_' . $safeDate . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
    fputcsv($out, ['#', 'Student Name', 'School ID', 'Section', 'Date', 'Status', 'Remark']);

    // ── Enrolled students ─────────────────────────────────────
    $stmt = $conn->prepare(
        "SELECT u.id,
                COALESCE(u.school_id,    '') AS school_id,
                COALESCE(u.first_name,   '') AS first_name,
                COALESCE(u.last_name,    '') AS last_name,
                COALESCE(u.section_dept, '') AS section_dept
         FROM   enrollments e
         JOIN   users       u ON u.id = e.student_id
         WHERE  e.course_id = ?
           AND  e.status    = 'Enrolled'
           AND  u.role      = 'Student'
           AND  u.status    = 'Active'
         ORDER  BY u.section_dept, u.last_name, u.first_name"
    );
    if (!$stmt) { fclose($out); exit; }
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ── Attendance records ────────────────────────────────────
    $attendance = [];
    $validDate  = ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date));

    if ($validDate) {
        $stmt = $conn->prepare(
            "SELECT student_id,
                    status,
                    COALESCE(remark, '') AS remark,
                    DATE_FORMAT(`date`, '%Y-%m-%d') AS att_date
             FROM   attendance
             WHERE  course_id = ? AND `date` = ?"
        );
        if (!$stmt) { fclose($out); exit; }
        $stmt->bind_param('is', $course_id, $date);
    } else {
        $stmt = $conn->prepare(
            "SELECT student_id,
                    status,
                    COALESCE(remark, '') AS remark,
                    DATE_FORMAT(`date`, '%Y-%m-%d') AS att_date
             FROM   attendance
             WHERE  course_id = ?
             ORDER  BY `date` ASC"
        );
        if (!$stmt) { fclose($out); exit; }
        $stmt->bind_param('i', $course_id);
    }
    $stmt->execute();
    $attResult = $stmt->get_result();
    while ($row = $attResult->fetch_assoc()) {
        // Use att_date (always clean YYYY-MM-DD string from DATE_FORMAT)
        $key              = ((int) $row['student_id']) . '_' . $row['att_date'];
        $attendance[$key] = $row;
    }
    $stmt->close();

    // ── Write CSV rows ────────────────────────────────────────
    $i = 1;
    foreach ($students as $s) {
        if ($validDate) {
            $key = ((int) $s['id']) . '_' . $date;
            $att = $attendance[$key] ?? null;
            fputcsv($out, [
                $i++,
                $s['last_name'] . ', ' . $s['first_name'],
                $s['school_id'],
                $s['section_dept'],
                $date,
                $att ? $att['status'] : 'N/A',
                $att ? $att['remark'] : '',
            ]);
        } else {
            $hasRecord = false;
            foreach ($attendance as $att) {
                if ((int) $att['student_id'] === (int) $s['id']) {
                    fputcsv($out, [
                        $i++,
                        $s['last_name'] . ', ' . $s['first_name'],
                        $s['school_id'],
                        $s['section_dept'],
                        $att['att_date'],
                        $att['status'],
                        $att['remark'],
                    ]);
                    $hasRecord = true;
                }
            }
            if (!$hasRecord) {
                fputcsv($out, [
                    $i++,
                    $s['last_name'] . ', ' . $s['first_name'],
                    $s['school_id'],
                    $s['section_dept'],
                    'N/A', 'N/A', '',
                ]);
            }
        }
    }

    fclose($out);
    exit;
}

// ── Unknown action ────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => false,
    'message' => 'Unknown action: ' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8'),
]);