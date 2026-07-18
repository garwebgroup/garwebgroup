<?php
/**
 * config.php
 * Central configuration for the Information System backend.
 *
 * SECURITY NOTE:
 * In a real deployment, never hard-code credentials in a file that sits
 * inside your web root or gets committed to version control. Instead,
 * load these values from environment variables (e.g. via a .env file
 * read with a library, or actual server environment variables) and keep
 * this file outside the public web root if possible.
 *
 * For a student project, it's fine to edit the values below directly,
 * but rename this file's real values out of any public git repo.
 */

// ---- Database configuration ----
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'company_info_system');
define('DB_USER', getenv('DB_USER') ?: 'root');       // do NOT use root in the app
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ---- App / session configuration ----
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // 'development' | 'production'
define('SESSION_NAME', 'isapp_session');
define('SESSION_LIFETIME_SECONDS', 60 * 60 * 2); // 2 hours

// ---- Account lockout policy (brute-force protection) ----
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);

// ---- Password policy ----
define('PASSWORD_MIN_LENGTH', 8);

// ---- CORS (only needed if frontend is served from a different origin) ----
// Leave empty if frontend and backend are served from the same domain.
define('ALLOWED_ORIGIN', getenv('ALLOWED_ORIGIN') ?: '');

// Error visibility: never leak stack traces / DB errors to the client in production
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// DEBUG_API: when true AND APP_ENV is not 'production', JSON error responses
// include the real exception message instead of a generic "Internal server error".
// This is ONLY for local troubleshooting — set APP_ENV to 'production' (or
// DEBUG_API=0 in the environment) before deploying/submitting, so real errors
// are never exposed to end users.
define('DEBUG_API', APP_ENV !== 'production' && (getenv('DEBUG_API') !== '0'));
