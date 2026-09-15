<?php
// ============================================================
//  Arandia College eLMS — Database Seeder
//  File: seed.php
//  Run this ONCE via browser or CLI to create the default admin.
//  DELETE THIS FILE after running!
// ============================================================

require_once 'config/conn.php';

// Create users table if it doesn't exist
$conn->query("
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    school_id     VARCHAR(50)  NOT NULL UNIQUE,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    role          ENUM('Student','Teacher','Admin') NOT NULL DEFAULT 'Student',
    first_name    VARCHAR(100) NOT NULL,
    last_name     VARCHAR(100) NOT NULL,
    middle_name   VARCHAR(100) DEFAULT NULL,
    email         VARCHAR(150) DEFAULT NULL,
    contact       VARCHAR(30)  DEFAULT NULL,
    section_dept  VARCHAR(100) DEFAULT NULL,
    status        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Check if admin already exists
$check = $conn->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
if ($check->num_rows === 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (school_id, username, password, role, first_name, last_name, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $school_id  = 'ADMIN-001';
    $username   = 'admin';
    $role       = 'Admin';
    $first_name = 'System';
    $last_name  = 'Administrator';
    $status     = 'Active';
    $stmt->bind_param('sssssss', $school_id, $username, $hash, $role, $first_name, $last_name, $status);
    $stmt->execute();
    echo "<p style='color:green;font-family:sans-serif;'>✅ Default admin created! Username: <b>admin</b> | Password: <b>admin123</b></p>";
    echo "<p style='color:red;font-family:sans-serif;'>⚠️ Please change the password after logging in, then DELETE this file (seed.php)!</p>";
} else {
    echo "<p style='color:orange;font-family:sans-serif;'>ℹ️ Admin account already exists. No changes made.</p>";
}
echo "<p style='font-family:sans-serif;'><a href='login.php'>→ Go to Login</a></p>";
?>