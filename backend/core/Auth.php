<?php
/**
 * Auth.php
 * Registration and login backend logic (item #7 in the assignment brief).
 *
 * Security features:
 *  - Passwords hashed with Argon2id/bcrypt, never stored or logged in plaintext.
 *  - All queries use PDO prepared statements with bound parameters.
 *  - Account lockout after MAX_LOGIN_ATTEMPTS failed logins (brute-force mitigation).
 *  - Session ID regenerated on login/privilege change (mitigates session fixation).
 *  - Generic error messages ("invalid username or password") so attackers can't
 *    enumerate which usernames/emails exist.
 *  - New self-registrations are always forced to role='user'; only an existing
 *    admin (via the users CRUD endpoint, RBAC-protected) can create staff/admin accounts.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Response.php';

class AuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function register(string $username, string $email, string $password, string $fullName): array
    {
        $username = Security::sanitizeString($username);
        $email    = Security::sanitizeString($email);
        $fullName = Security::sanitizeString($fullName);

        if (!Security::validateUsername($username)) {
            return ['success' => false, 'error' => 'Username must be 3-32 characters, start with a letter, and contain only letters, numbers, "_" or "."'];
        }
        if (!Security::validateEmail($email)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        if (!Security::validatePasswordStrength($password)) {
            return ['success' => false, 'error' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters and include letters and numbers'];
        }
        if ($fullName === '' || strlen($fullName) > 100) {
            return ['success' => false, 'error' => 'Full name is required (max 100 characters)'];
        }

        // Check for existing username/email (still generic on failure at login,
        // but at registration time it's normal/expected UX to say "taken").
        $stmt = $this->db->prepare('SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1');
        $stmt->execute([':u' => $username, ':e' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Username or email already registered'];
        }

        $hash = Security::hashPassword($password);

        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role, created_at)
             VALUES (:u, :e, :p, :f, :r, NOW())'
        );
        $stmt->execute([
            ':u' => $username,
            ':e' => $email,
            ':p' => $hash,
            ':f' => $fullName,
            ':r' => 'user', // force lowest privilege on self-registration
        ]);

        return ['success' => true, 'message' => 'Registration successful. You can now log in.'];
    }

    public function login(string $identifier, string $password): array
    {
        $identifier = Security::sanitizeString($identifier);

        $stmt = $this->db->prepare(
            'SELECT id, username, email, password_hash, full_name, role, failed_attempts, locked_until
             FROM users WHERE username = :identifier1 OR email = :identifier2 LIMIT 1'
        );
        $stmt->execute([':identifier1' => $identifier, ':identifier2' => $identifier]);
        $user = $stmt->fetch();

        // Constant-ish response path: don't reveal whether the account exists.
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid username/email or password'];
        }

        // Check lockout
        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $waitMins = ceil((strtotime($user['locked_until']) - time()) / 60);
            return ['success' => false, 'error' => "Account temporarily locked. Try again in {$waitMins} minute(s)."];
        }

        if (!Security::verifyPassword($password, $user['password_hash'])) {
            $this->registerFailedAttempt((int) $user['id'], (int) $user['failed_attempts']);
            return ['success' => false, 'error' => 'Invalid username/email or password'];
        }

        // Successful login: reset attempts, regenerate session id, store minimal session data.
        $this->resetFailedAttempts((int) $user['id']);

        session_regenerate_id(true);
        $_SESSION['user_id']  = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        return [
            'success' => true,
            'user' => [
                'id'        => (int) $user['id'],
                'username'  => $user['username'],
                'email'     => $user['email'],
                'full_name' => $user['full_name'],
                'role'      => $user['role'],
            ],
        ];
    }

    public function logout(): void
    {
        Security::destroySession();
    }

    public function currentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        return [
            'id'        => $_SESSION['user_id'],
            'username'  => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role'      => $_SESSION['role'],
        ];
    }

    public function requireAuth(): array
    {
        $user = $this->currentUser();
        if ($user === null) {
            Response::error('Authentication required', 401);
        }
        return $user;
    }

    private function registerFailedAttempt(int $userId, int $currentAttempts): void
    {
        $newAttempts = $currentAttempts + 1;
        if ($newAttempts >= MAX_LOGIN_ATTEMPTS) {
            $stmt = $this->db->prepare(
                'UPDATE users SET failed_attempts = :a, locked_until = DATE_ADD(NOW(), INTERVAL :m MINUTE) WHERE id = :id'
            );
            $stmt->execute([':a' => $newAttempts, ':m' => LOCKOUT_MINUTES, ':id' => $userId]);
        } else {
            $stmt = $this->db->prepare('UPDATE users SET failed_attempts = :a WHERE id = :id');
            $stmt->execute([':a' => $newAttempts, ':id' => $userId]);
        }
    }

    private function resetFailedAttempts(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }
}
