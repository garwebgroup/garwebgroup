<?php
// ==========================================
// 1. DATABASE CONNECTION CONFIGURATION
// ==========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'company_info_system'); // <-- Update to your database name
define('DB_USER', 'root');                // <-- Update to your username
define('DB_PASS', '');                    // <-- Update to your password
define('DB_CHAR', 'utf8mb4');

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    exit("Database Connection Failed: " . $e->getMessage());
}

// Helper function to sanitize output against XSS[cite: 3]
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper function to format datetime strings for HTML datetime-local input fields[cite: 3]
function formatForInput($datetime) {
    if (!$datetime) return '';
    return date('Y-m-d\TH:i', strtotime($datetime));
}

$message = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// ==========================================
// 2. HANDLE ACTIONS (CREATE, UPDATE, DELETE)
// ==========================================

// Handle DELETE[cite: 3]
if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: users.php?msg=deleted");
        exit;
    }
}

// Handle Form Submission (CREATE & UPDATE)[cite: 3]
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username        = trim($_POST['username'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $full_name       = trim($_POST['full_name'] ?? '');
    $role            = trim($_POST['role'] ?? 'user');
    $password        = $_POST['password'] ?? '';
    $failed_attempts = (int)($_POST['failed_attempts'] ?? 0);
    $locked_until    = !empty($_POST['locked_until']) ? $_POST['locked_until'] : null;

    if (!empty($username) && !empty($email) && !empty($full_name)) {
        if (!empty($_POST['id'])) {
            $user_id = $_POST['id'];

            // Update existing record[cite: 3]
            if (!empty($password)) {
                // Password provided; hash and update password_hash
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = ?, email = ?, password_hash = ?, full_name = ?, role = ?, failed_attempts = ?, locked_until = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $email, $password_hash, $full_name, $role, $failed_attempts, $locked_until, $user_id]);
            } else {
                // Keep existing password_hash
                $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, failed_attempts = ?, locked_until = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $email, $full_name, $role, $failed_attempts, $locked_until, $user_id]);
            }
            header("Location: users.php?msg=updated");
            exit;
        } else {
            // Insert new record[cite: 3]
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, email, password_hash, full_name, role, failed_attempts, locked_until) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $email, $password_hash, $full_name, $role, $failed_attempts, $locked_until]);
                header("Location: users.php?msg=created");
                exit;
            } else {
                $message = "Password is required for new users.";
            }
        }
    } else {
        $message = "Username, Email, and Full Name are required.";
    }
}

// Fetch single record if editing[cite: 3]
$editRecord = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $editRecord = $stmt->fetch();
}

// Fetch all records for listing[cite: 3]
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management System</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; 
            margin: 0; 
            padding: 40px 20px;
            /* Modern Indigo Gradient */
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            min-height: 100vh;
            color: #333; 
        }
        .container { 
            max-width: 1100px; 
            margin: auto; 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.25); 
        }
        h2 { margin-top: 0; color: #1e1b4b; }
        .alert { padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; background: #e0e7ff; color: #3730a3; font-weight: 500; }
        form { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
            margin-bottom: 30px; 
            background: #f8fafc; 
            padding: 20px; 
            border-radius: 8px; 
            border: 1px solid #e2e8f0; 
        }
        form .full-width { grid-column: span 2; }
        label { font-weight: 600; font-size: 0.88em; display: block; margin-bottom: 5px; color: #475569; }
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="datetime-local"], select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #cbd5e1; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 0.95em;
        }
        button, .btn { padding: 9px 18px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.9em; font-weight: 600; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary { background: #64748b; color: #fff; }
        .btn-secondary:hover { background: #475569; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 0.92em; }
        th { background: #f1f5f9; color: #334155; }
        .actions { white-space: nowrap; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 600; }
        .badge-admin { background: #fef3c7; color: #92400e; }
        .badge-staff { background: #e0f2fe; color: #075985; }
        .badge-user { background: #f1f5f9; color: #475569; }
    </style>
</head>
<body>

<div class="container">
    <h2>User Management System</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert">
            <?php 
                if ($_GET['msg'] === 'created') echo "User created successfully.";
                if ($_GET['msg'] === 'updated') echo "User updated successfully.";
                if ($_GET['msg'] === 'deleted') echo "User removed.";
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="alert" style="background: #fee2e2; color: #991b1b;"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- CREATE / UPDATE FORM -->
    <form method="POST" action="users.php">
        <input type="hidden" name="id" value="<?= e($editRecord['id'] ?? '') ?>">

        <div>
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" maxlength="32" value="<?= e($editRecord['username'] ?? '') ?>" required>
        </div>

        <div>
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" maxlength="190" value="<?= e($editRecord['email'] ?? '') ?>" required>
        </div>

        <div>
            <label for="full_name">Full Name *</label>
            <input type="text" id="full_name" name="full_name" maxlength="100" value="<?= e($editRecord['full_name'] ?? '') ?>" required>
        </div>

        <div>
            <label for="role">Role *</label>
            <select id="role" name="role" required>
                <?php $currentRole = $editRecord['role'] ?? 'user'; ?>
                <option value="user" <?= $currentRole === 'user' ? 'selected' : '' ?>>User</option>
                <option value="staff" <?= $currentRole === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>

        <div>
            <label for="password">Password <?= $editRecord ? '(Leave blank to keep unchanged)' : '*' ?></label>
            <input type="password" id="password" name="password" <?= $editRecord ? '' : 'required' ?>>
        </div>

        <div>
            <label for="failed_attempts">Failed Attempts</label>
            <input type="number" id="failed_attempts" name="failed_attempts" value="<?= e($editRecord['failed_attempts'] ?? '0') ?>" min="0">
        </div>

        <div class="full-width">
            <label for="locked_until">Locked Until</label>
            <input type="datetime-local" id="locked_until" name="locked_until" value="<?= formatForInput($editRecord['locked_until'] ?? '') ?>">
        </div>

        <div class="full-width">
            <button type="submit" class="btn btn-primary">
                <?= $editRecord ? 'Update User' : 'Create User' ?>
            </button>
            <?php if ($editRecord): ?>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- READ / LIST TABLE -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name / Email</th>
                <th>Role</th>
                <th>Failed Attempts</th>
                <th>Locked Until</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['id']) ?></td>
                        <td><strong><?= e($u['username']) ?></strong></td>
                        <td><?= e($u['full_name']) ?><br><small style="color: #64748b;"><?= e($u['email']) ?></small></td>
                        <td>
                            <span class="badge badge-<?= e($u['role']) ?>">
                                <?= strtoupper(e($u['role'])) ?>
                            </span>
                        </td>
                        <td><?= e($u['failed_attempts']) ?></td>
                        <td><?= e($u['locked_until'] ?: '—') ?></td>
                        <td><?= e($u['created_at']) ?></td>
                        <td class="actions">
                            <a href="users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-secondary" style="padding: 4px 8px;">Edit</a>
                            <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-danger" style="padding: 4px 8px;" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #888;">No user records found in the database.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>