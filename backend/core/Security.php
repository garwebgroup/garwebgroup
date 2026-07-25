<?php
/**
 * Security.php
 * Cross-cutting security helpers used by Auth and CrudService.
 *
 * Covers:
 *  - Hardened session start (HttpOnly, Secure, SameSite cookies)
 *  - CSRF token generation / verification
 *  - Input sanitization & validation
 *  - Password hashing (Argon2id, falls back to bcrypt if unavailable)
 */

require_once __DIR__ . '/../config/config.php';

class Security
{
    /** Call this once, before any output, at the top of every entry-point script. */
    public static function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443);

        session_name(SESSION_NAME);

        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME_SECONDS,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,      // only sent over HTTPS in production
            'httponly' => true,          // not readable from JavaScript -> mitigates XSS session theft
            'samesite' => 'Strict',      // mitigates CSRF via cross-site requests
        ]);

        ini_set('session.use_strict_mode', '1');
        session_start();

        // Expire idle sessions server-side too, not just via the cookie.
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME_SECONDS) {
            self::destroySession();
        }
        $_SESSION['last_activity'] = time();
    }

    public static function destroySession(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    // ---------------- CSRF ----------------

    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /** Reads token from either a JSON body field or the X-CSRF-Token header. */
    public static function extractCsrfToken(array $body): ?string
    {
        if (!empty($body['csrf_token'])) {
            return (string) $body['csrf_token'];
        }
        return $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }

    // ---------------- Sanitization / validation ----------------

    public static function sanitizeString(?string $value): string
    {
        $value = $value ?? '';
        $value = trim($value);
        // Strip control characters and null bytes; keep normal unicode text.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
        return $value;
    }

    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateUsername(string $username): bool
    {
        // 3-32 chars, letters/numbers/underscore/dot, must start with a letter
        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9_.]{2,31}$/', $username);
    }

    public static function validatePasswordStrength(string $password): bool
    {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return false;
        }
        $hasLetter = preg_match('/[A-Za-z]/', $password);
        $hasDigit  = preg_match('/[0-9]/', $password);
        return (bool) ($hasLetter && $hasDigit);
    }

    // ---------------- Passwords ----------------

    public static function hashPassword(string $password): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_hash($password, $algo);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /** Output-encode text that will be placed into HTML by the frontend (defense in depth). */
    public static function escapeForHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
