<?php
// ============================================================
//  Arandia College eLMS — Profile API
//  File: api/profile.php
//  Accessible by: Student, Teacher
// ============================================================
session_start();
require_once '../config/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// ── GET: fetch profile info ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $row = $conn->query(
        "SELECT id, school_id, username, role, first_name, last_name, middle_name,
                email, contact, section_dept, profile_picture, status,
                DATE_FORMAT(created_at,'%b %d, %Y') AS member_since
         FROM users WHERE id = $user_id LIMIT 1"
    )->fetch_assoc();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $row]);
    exit;
}

// ── POST: update profile info ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true);
    $fname = trim($body['first_name'] ?? '');
    $lname = trim($body['last_name'] ?? '');
    $mname = trim($body['middle_name'] ?? '');
    $email = trim($body['email'] ?? '');
    $contact = trim($body['contact'] ?? '');

    if (!$fname || !$lname) {
        echo json_encode(['success' => false, 'message' => 'First and last name required.']);
        exit;
    }

    // Check email uniqueness (exclude self)
    if ($email) {
        $chk = $conn->prepare("SELECT id FROM users WHERE email=? AND id!=? LIMIT 1");
        $chk->bind_param('si', $email, $user_id);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'message' => 'Email already used by another account.']);
            exit;
        }
    }

    $stmt = $conn->prepare(
        "UPDATE users SET first_name=?, last_name=?, middle_name=?, email=?, contact=? WHERE id=?"
    );
    $stmt->bind_param('sssssi', $fname, $lname, $mname, $email, $contact, $user_id);

    if ($stmt->execute()) {
        // Update session name
        $_SESSION['first_name'] = $fname;
        $_SESSION['last_name'] = $lname;
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// ── POST: upload profile picture ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_pic') {
    if (empty($_FILES['picture']['name'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
        exit;
    }

    $ext = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Only image files allowed (JPG, PNG, GIF, WEBP).']);
        exit;
    }

    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($_FILES['picture']['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File too large. Max 2MB.']);
        exit;
    }

    $upload_dir = '../uploads/profiles/';
    if (!is_dir($upload_dir))
        mkdir($upload_dir, 0775, true);

    // Delete old picture
    $old = $conn->query("SELECT profile_picture FROM users WHERE id=$user_id LIMIT 1")->fetch_assoc();
    if ($old && $old['profile_picture'] && file_exists('../' . $old['profile_picture'])) {
        unlink('../' . $old['profile_picture']);
    }

    $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
    $dest = $upload_dir . $filename;
    if (!move_uploaded_file($_FILES['picture']['tmp_name'], $dest)) {
        echo json_encode(['success' => false, 'message' => 'Upload failed.']);
        exit;
    }

    $path = 'uploads/profiles/' . $filename;
    $conn->query("UPDATE users SET profile_picture='$path' WHERE id=$user_id");

    echo json_encode(['success' => true, 'message' => 'Profile picture updated.', 'path' => $path]);
    exit;
}


// ── POST: change password ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'change_password') {
    $body = json_decode(file_get_contents('php://input'), true);

    $current_password = trim($body['current_password'] ?? '');
    $new_password     = trim($body['new_password'] ?? '');

    if (!$current_password || !$new_password) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }
    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
        exit;
    }

    // Fetch current hashed password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    // Verify current password
    if (!password_verify($current_password, $row['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }

    // Prevent reuse of same password
    if (password_verify($new_password, $row['password'])) {
        echo json_encode(['success' => false, 'message' => 'New password must be different from your current password.']);
        exit;
    }

    // Hash and save new password
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt2  = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt2->bind_param('si', $hashed, $user_id);

    if ($stmt2->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
    $stmt2->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);