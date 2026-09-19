<?php
// ============================================================
//  Arandia College eLMS — Accounts API
//  File: api/accounts.php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/conn.php';

header('Content-Type: application/json');

// Auth check — Admin only
if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── POST: Create account ──────────────────────────────────
if ($method === 'POST' && $action === 'create') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON request body.']);
        exit;
    }

    $required = ['first_name', 'last_name', 'role', 'school_id', 'username', 'password'];
    foreach ($required as $f) {
        if (empty($data[$f])) {
            echo json_encode(['success' => false, 'message' => "Field '$f' is required."]);
            exit;
        }
    }

    if ($data['password'] !== ($data['confirm_password'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    if (strlen($data['password']) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }

    // Check for duplicate username or school_id
    $chk = $conn->prepare("SELECT id FROM users WHERE username = ? OR school_id = ? LIMIT 1");
    $chk->bind_param('ss', $data['username'], $data['school_id']);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or School ID already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    $middle = $data['middle_name'] ?? null;
    $email = $data['email'] ?? null;
    $contact = $data['contact'] ?? null;
    $sect = $data['section_dept'] ?? null;
    $status = in_array($data['status'] ?? '', ['Active', 'Inactive']) ? $data['status'] : 'Active';

    // Hash the password before storing
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO users (school_id, username, password, role, first_name, last_name,
         middle_name, email, contact, section_dept, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error while preparing account creation.']);
        exit;
    }

    $stmt->bind_param(
        'sssssssssss',
        $data['school_id'],
        $data['username'],
        $hashedPassword,
        $data['role'],
        $data['first_name'],
        $data['last_name'],
        $middle,
        $email,
        $contact,
        $sect,
        $status
    );

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Account created successfully.', 'id' => $newId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

// ── PUT: Update account ───────────────────────────────────
if ($method === 'PUT' && $action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }

    $middle = $data['middle_name'] ?? null;
    $email = $data['email'] ?? null;
    $contact = $data['contact'] ?? null;
    $sect = $data['section_dept'] ?? null;
    $status = in_array($data['status'] ?? '', ['Active', 'Inactive']) ? $data['status'] : 'Active';

    if (!empty($data['password'])) {
        if (strlen($data['password']) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
            exit;
        }
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "UPDATE users SET first_name=?, last_name=?, middle_name=?, role=?, school_id=?,
             email=?, contact=?, section_dept=?, status=?, password=? WHERE id=?"
        );
        $stmt->bind_param(
            'ssssssssssi',
            $data['first_name'],
            $data['last_name'],
            $middle,
            $data['role'],
            $data['school_id'],
            $email,
            $contact,
            $sect,
            $status,
            $hashedPassword,
            $id
        );
    } else {
        $stmt = $conn->prepare(
            "UPDATE users SET first_name=?, last_name=?, middle_name=?, role=?, school_id=?,
             email=?, contact=?, section_dept=?, status=? WHERE id=?"
        );
        $stmt->bind_param(
            'sssssssssi',
            $data['first_name'],
            $data['last_name'],
            $middle,
            $data['role'],
            $data['school_id'],
            $email,
            $contact,
            $sect,
            $status,
            $id
        );
    }

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Account updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

// ── DELETE: Remove account ────────────────────────────────
if ($method === 'DELETE' && $action === 'delete') {
    $id = (int) ($_GET['id'] ?? 0); 
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }

    // Prevent self-deletion
    if ($id === (int) $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Account deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

// ── GET: List accounts (optional) ────────────────────────
if ($method === 'GET' && $action === 'list') {
    $accounts = [];
    $res = $conn->query(
        "SELECT id, school_id, username, role, first_name, last_name, middle_name,
         email, contact, section_dept, status,
         DATE_FORMAT(created_at,'%b %d, %Y') AS created_at
         FROM users ORDER BY created_at DESC"
    );
    while ($r = $res->fetch_assoc()) {
        $accounts[] = $r;
    }
    echo json_encode(['success' => true, 'data' => $accounts]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);