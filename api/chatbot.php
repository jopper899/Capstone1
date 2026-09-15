<?php
// ============================================================
//  Arandia College eLMS — AI Chatbot API
//  File: api/chatbot.php
//  Powered by: Groq API (free, ultra-fast)
// ============================================================
session_start();
require_once '../config/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']); exit;
}

$user_id   = (int)$_SESSION['user_id'];
$role      = $_SESSION['role'];
$firstName = $_SESSION['first_name'] ?? 'User';

$apiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : getenv('GROQ_API_KEY');

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'message' => 'API key not configured.']); exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$messages = $body['messages'] ?? [];

if (empty($messages)) {
    echo json_encode(['success' => false, 'message' => 'No messages provided.']); exit;
}

// ── Build context about the user ──────────────────────────────────────────
$contextParts = ["The user's name is $firstName and their role is $role."];

if ($role === 'Student') {
    $subjects = [];
    $res = $conn->query(
        "SELECT c.course_name, c.course_code FROM enrollments e
         JOIN courses c ON c.id = e.course_id
         WHERE e.student_id = $user_id AND e.status = 'Enrolled'"
    );
    while ($r = $res->fetch_assoc()) $subjects[] = $r['course_code'] . ' — ' . $r['course_name'];
    if ($subjects) $contextParts[] = 'The student is enrolled in: ' . implode(', ', $subjects) . '.';

    $uInfo = $conn->query("SELECT section_dept FROM users WHERE id=$user_id LIMIT 1")->fetch_assoc();
    if (!empty($uInfo['section_dept'])) $contextParts[] = 'Their section/grade is: ' . $uInfo['section_dept'] . '.';

} elseif ($role === 'Teacher') {
    $subjects = [];
    $res = $conn->query(
        "SELECT DISTINCT c.course_name, c.course_code FROM teacher_assignments ta
         JOIN courses c ON c.id = ta.course_id
         WHERE ta.teacher_id = $user_id"
    );
    while ($r = $res->fetch_assoc()) $subjects[] = $r['course_code'] . ' — ' . $r['course_name'];
    if ($subjects) $contextParts[] = 'The teacher handles: ' . implode(', ', $subjects) . '.';
}

$systemPrompt = "You are EduBot, a friendly and helpful AI assistant for Arandia College's eLMS for Senior High School (SHS) and High School (HS) students and teachers in the Philippines. "
    . implode(' ', $contextParts)
    . " Help students with homework, explain concepts, and assist teachers with lesson planning. Answer in Filipino or English depending on the user. Keep responses clear and appropriate for high school level. Only answer education-related questions. Always be encouraging and positive. School context: Arandia College eLMS, School Year 2025-2026.";

// ── Call Groq API ─────────────────────────────────────────────────────────
$payload = json_encode([
    'model'       => 'llama-3.3-70b-versatile',
    'messages'    => array_merge(
        [['role' => 'system', 'content' => $systemPrompt]],
        $messages
    ),
    'max_tokens'  => 1024,
    'temperature' => 0.7,
]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || !$response) {
    echo json_encode(['success' => false, 'message' => 'Failed to connect to AI service.']); exit;
}

$data  = json_decode($response, true);

if ($httpCode !== 200) {
    $errMsg = $data['error']['message'] ?? 'AI service error. (HTTP ' . $httpCode . ')';
    echo json_encode(['success' => false, 'message' => $errMsg]); exit;
}

$reply = $data['choices'][0]['message']['content'] ?? '';

if (!$reply) {
    echo json_encode(['success' => false, 'message' => 'Empty response from AI.']); exit;
}

echo json_encode(['success' => true, 'reply' => $reply]);