<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin(string $redirect = '../../login.php'): void {
    if (!isset($_SESSION['user_id'])) { header('Location: '.$redirect); exit; }
}
