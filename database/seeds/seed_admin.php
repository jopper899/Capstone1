<?php
// Arandia College eLMS — Admin Seeder
// Run from CLI/local development only.
// Do not expose this script through a production web server.

require_once __DIR__ . '/../../config/conn.php';

$username = getenv('SEED_ADMIN_USERNAME') ?: 'admin';
$password = getenv('SEED_ADMIN_PASSWORD');

if ($password === false || $password === '') {
    exit("SEED_ADMIN_PASSWORD is not configured. Set it before running the seeder.\n");
}

$check = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$check->bind_param('s', $username);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "Admin account already exists. No changes made.\n";
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare(
    "INSERT INTO users (school_id, username, password, role, first_name, last_name, status)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

$school_id = 'ADMIN-001';
$role = 'Admin';
$first_name = 'System';
$last_name = 'Administrator';
$status = 'Active';

$stmt->bind_param(
    'sssssss',
    $school_id,
    $username,
    $hash,
    $role,
    $first_name,
    $last_name,
    $status
);

$stmt->execute();

echo "Admin account created successfully. Username: {$username}\n";
