<?php
// config/config.php - Global application settings.

// URL path of the project. If you rename the folder, change this too.
define('BASE_URL', '/bus_reservation');
define('APP_NAME', 'ABCOAD Bus');
define('APP_CURRENCY', '₦');

// Start a secure session once for the whole app.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,      // JavaScript cannot read the session cookie (XSS protection)
        'samesite' => 'Lax',     // helps against CSRF
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

date_default_timezone_set('Africa/Lagos');
