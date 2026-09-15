<?php
// ============================================================
//  Arandia College eLMS — Session Helper
//  File: config/session.php
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(string $redirectTo = '../login.php'): void
{
    if (!isLoggedIn()) {
        header("Location: $redirectTo");
        exit;
    }
}

function requireRole(string $role, string $redirectTo = '../login.php'): void
{
    requireLogin($redirectTo);
    if ($_SESSION['role'] !== $role) {
        header("Location: $redirectTo");
        exit;
    }
}

function getCurrentUser(): array
{
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? null,
        'last_name' => $_SESSION['last_name'] ?? null,
    ];
}

function destroySession(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }
    session_destroy();
}
?>