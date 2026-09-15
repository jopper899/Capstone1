<?php
$conn = new mysqli('localhost', 'root', '', 'elmsdb');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// ── Groq API Key (for EduBot AI Chatbot) ─────────────────────────────────
define('GROQ_API_KEY', getenv('GROQ_API_KEY'));
?>