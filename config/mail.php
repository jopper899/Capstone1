<?php
// SMTP configuration for Arandia College eLMS.
// Local credentials may be provided in config/mail.local.php (not committed).
// Environment variables are also supported for production/server setups.

$localMailConfig = __DIR__ . '/mail.local.php';
if (file_exists($localMailConfig)) {
    require_once $localMailConfig;
}

define('SMTP_HOST', defined('LOCAL_SMTP_HOST') ? LOCAL_SMTP_HOST : (getenv('SMTP_HOST') ?: 'smtp.gmail.com'));
define('SMTP_PORT', defined('LOCAL_SMTP_PORT') ? (int) LOCAL_SMTP_PORT : (int) (getenv('SMTP_PORT') ?: 465));
define('SMTP_USERNAME', defined('LOCAL_SMTP_USERNAME') ? LOCAL_SMTP_USERNAME : (getenv('SMTP_USERNAME') ?: ''));
define('SMTP_PASSWORD', defined('LOCAL_SMTP_PASSWORD') ? LOCAL_SMTP_PASSWORD : (getenv('SMTP_PASSWORD') ?: ''));
define('SMTP_FROM_EMAIL', defined('LOCAL_SMTP_FROM_EMAIL') ? LOCAL_SMTP_FROM_EMAIL : (getenv('SMTP_FROM_EMAIL') ?: SMTP_USERNAME));
define('SMTP_FROM_NAME', defined('LOCAL_SMTP_FROM_NAME') ? LOCAL_SMTP_FROM_NAME : (getenv('SMTP_FROM_NAME') ?: 'Arandia College eLMS'));
?>
