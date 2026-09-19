<?php
// Legacy compatibility entry point.
// The admin seeder has moved to database/seeds/seed_admin.php.
// Do not run database seeders through the web in production.

http_response_code(403);
echo "Seeder disabled. Use database/seeds/seed_admin.php from a local CLI/development environment.";
