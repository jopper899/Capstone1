<?php
require_once __DIR__ . '/config/session.php';

destroySession();

setcookie('remember_login', '', time() - 3600, '/', '', false, true);

header('Location: login.php');
exit;
