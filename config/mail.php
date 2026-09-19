<?php
// SMTP configuration for Arandia College eLMS.
// Values must be provided through environment variables.
// Never commit real SMTP credentials to the repository.

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int) (getenv('SMTP_PORT') ?: 465));
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: SMTP_USERNAME);
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Arandia College eLMS');
?>
