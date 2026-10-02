<?php
/**
 * Pinkventory configuration template.
 *
 * Copy this file to config.php (same folder) and fill in real values,
 * or set the same keys as environment variables (they take priority).
 * config.php is git-ignored and blocked from web access by .htaccess.
 */
return [
    // 'production' hides error details from visitors; use 'development' locally.
    'APP_ENV'     => 'production',

    'DB_HOST'     => 'localhost',
    'DB_PORT'     => 3306,
    'DB_NAME'     => 'pinkventory',
    'DB_USER'     => 'pinkventory_app',
    'DB_PASS'     => 'change-me',

    // Redirect every HTTP request to HTTPS. Enable once the site has an SSL certificate.
    'FORCE_HTTPS' => false,
];
