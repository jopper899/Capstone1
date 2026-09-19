<?php
// Shared authentication helpers.
// Use this file from module pages instead of duplicating session checks.

require_once __DIR__ . '/../../config/session.php';

function requireLogin(string $redirect = '../../login.php'): void
{
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}
