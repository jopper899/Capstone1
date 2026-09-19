<?php
require_once __DIR__ . '/auth.php';
requireLogin();
if (($_SESSION['role'] ?? '') !== 'Admin') { header('Location: ../../login.php'); exit; }
