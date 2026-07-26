<?php
// ==========================================
// 1. DATABASE CONNECTION CONFIGURATION
// ==========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'company_info_system'); // <-- Update this
define('DB_USER', 'root');      // <-- Update this
define('DB_PASS', '');      // <-- Update this
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

// Helper function to sanitize output against XSS
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

$message = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// ==========================================
// 2. HANDLE ACTIONS (CREATE, UPDATE, DELETE)
// ==========================================

// Handle DELETE
if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: employee.php?msg=deleted");
        exit;
    }
}

// Handle Form Submission (CREATE & UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $position   = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');

    if (!empty($full_name)) {		
        if (!empty($_POST['id'])) {
            // Update existing record
            $sql = "UPDATE employees SET full_name = ?, position = ?, department = ?, email = ?, phone = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$full_name, $position, $department, $email, $phone, $_POST['id']]);
            header("Location: employee.php?msg=updated");
            exit;
        } else {
            // Insert new record
            $sql = "INSERT INTO employees (full_name, position, department, email, phone) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$full_name, $position, $department, $email, $phone]);
            header("Location: employee.php?msg=created");
            exit;
        }
    } else {
        $message = "Full Name is required.";
    }
}

// Fetch single record if editing
$editRecord = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $editRecord = $stmt->fetch();
}

// Fetch all records for listing
$employees = $pdo->query("SELECT * FROM employees ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Information System</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; margin: 30px; background: #f4f6f8; color: #333; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #1a252f; }
        .alert { padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; background: #e3f2fd; color: #0d47a1; }
        form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; background: #fafafa; padding: 15px; border-radius: 6px; border: 1px solid #ddd; }
        form .full-width { grid-column: span 2; }
        label { font-weight: bold; font-size: 0.9em; display: block; margin-bottom: 5px; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button, .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.9em; }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-danger { background: #dc3545; color: #fff; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; }
        .actions { white-space: nowrap; }
    </style>
</head>
<body>

<div class="container">
    <h2>Employee Management System</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert">
            <?php 
                if ($_GET['msg'] === 'created') echo "Employee added successfully.";
                if ($_GET['msg'] === 'updated') echo "Employee details updated.";
                if ($_GET['msg'] === 'deleted') echo "Employee removed.";
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="alert" style="background: #f8d7da; color: #721c24;"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- CREATE / UPDATE FORM -->
    <form method="POST" action="employee.php">
        <input type="hidden" name="id" value="<?= e($editRecord['id'] ?? '') ?>">

        <div>
            <label for="full_name">Full Name *</label>
            <input type="text" id="full_name" name="full_name" value="<?= e($editRecord['full_name'] ?? '') ?>" required>
        </div>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($editRecord['email'] ?? '') ?>">
        </div>

        <div>
            <label for="position">Position</label>
            <input type="text" id="position" name="position" value="<?= e($editRecord['position'] ?? '') ?>">
        </div>

        <div>
            <label for="department">Department</label>
            <input type="text" id="department" name="department" value="<?= e($editRecord['department'] ?? '') ?>">
        </div>

        <div>
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?= e($editRecord['phone'] ?? '') ?>">
        </div>

        <div style="align-self: end;">
            <button type="submit" class="btn btn-primary">
                <?= $editRecord ? 'Update Employee' : 'Add Employee' ?>
            </button>
            <?php if ($editRecord): ?>
                <a href="employee.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- READ / LIST TABLE -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Position</th>
                <th>Department</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($employees) > 0): ?>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><?= e($emp['id']) ?></td>
                        <td><strong><?= e($emp['full_name']) ?></strong></td>
                        <td><?= e($emp['position']) ?></td>
                        <td><?= e($emp['department']) ?></td>
                        <td><?= e($emp['email']) ?></td>
                        <td><?= e($emp['phone']) ?></td>
                        <td><?= e($emp['created_at']) ?></td>
                        <td class="actions">
                            <a href="employee.php?action=edit&id=<?= $emp['id'] ?>" class="btn btn-secondary" style="padding: 4px 8px;">Edit</a>
                            <a href="employee.php?action=delete&id=<?= $emp['id'] ?>" class="btn btn-danger" style="padding: 4px 8px;" onclick="return confirm('Are you sure you want to delete this employee?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #888;">No employees found in the database.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>