<?php
require_once __DIR__ . '/auth.php';
requireLogin();
if (($_SESSION['role'] ?? '') !== 'Student') { header('Location: ../../login.php'); exit; }
