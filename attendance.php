<?php
// ==========================================
// 1. DATABASE CONNECTION CONFIGURATION
// ==========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'company_info_system'); // <-- Update this
define('DB_USER', 'root');                // <-- Update this
define('DB_PASS', '');                    // <-- Update this
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

// Helper function to format datetime strings for HTML datetime-local input fields
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

// Handle DELETE
if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM attendance WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: attendance.php?msg=deleted");
        exit;
    }
}

// Handle Form Submission (CREATE & UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empid     = trim($_POST['empid'] ?? '');
    $deptid    = trim($_POST['deptid'] ?? '');
    $workstart = trim($_POST['workstart'] ?? '');
    $workend   = trim($_POST['workend'] ?? '');

    if (!empty($empid) && !empty($deptid) && !empty($workstart) && !empty($workend)) {        
        if (!empty($_POST['id'])) {
            // Update existing record
            $sql = "UPDATE attendance SET empid = ?, deptid = ?, workstart = ?, workend = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$empid, $deptid, $workstart, $workend, $_POST['id']]);
            header("Location: attendance.php?msg=updated");
            exit;
        } else {
            // Insert new record
            $sql = "INSERT INTO attendance (empid, deptid, workstart, workend) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$empid, $deptid, $workstart, $workend]);
            header("Location: attendance.php?msg=created");
            exit;
        }
    } else {
        $message = "All input fields are required.";
    }
}

// Fetch single record if editing
$editRecord = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE id = ?");
    $stmt->execute([$id]);
    $editRecord = $stmt->fetch();
}

// Fetch all records for listing
$attendances = $pdo->query("SELECT * FROM attendance ORDER BY entrydate DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management System</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; margin: 30px; background: #f4f6f8; color: #333; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #1a252f; }
        .alert { padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; background: #e3f2fd; color: #0d47a1; }
        form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; background: #fafafa; padding: 15px; border-radius: 6px; border: 1px solid #ddd; }
        form .full-width { grid-column: span 2; }
        label { font-weight: bold; font-size: 0.9em; display: block; margin-bottom: 5px; }
        input[type="number"], input[type="datetime-local"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
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
    <h2>Attendance Management System</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert">
            <?php 
                if ($_GET['msg'] === 'created') echo "Attendance record added successfully.";
                if ($_GET['msg'] === 'updated') echo "Attendance record updated successfully.";
                if ($_GET['msg'] === 'deleted') echo "Attendance record removed.";
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="alert" style="background: #f8d7da; color: #721c24;"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- CREATE / UPDATE FORM -->
    <form method="POST" action="attendance.php">
        <input type="hidden" name="id" value="<?= e($editRecord['id'] ?? '') ?>">

        <div>
            <label for="empid">Employee ID *</label>
            <input type="number" id="empid" name="empid" value="<?= e($editRecord['empid'] ?? '') ?>" required>
        </div>

        <div>
            <label for="deptid">Department ID/Date *</label>
            <input type="datetime-local" id="deptid" name="deptid" value="<?= formatForInput($editRecord['deptid'] ?? '') ?>" required>
        </div>

        <div>
            <label for="workstart">Work Start *</label>
            <input type="datetime-local" id="workstart" name="workstart" value="<?= formatForInput($editRecord['workstart'] ?? '') ?>" required>
        </div>

        <div>
            <label for="workend">Work End *</label>
            <input type="datetime-local" id="workend" name="workend" value="<?= formatForInput($editRecord['workend'] ?? '') ?>" required>
        </div>

        <div class="full-width">
            <button type="submit" class="btn btn-primary">
                <?= $editRecord ? 'Update Attendance' : 'Add Attendance' ?>
            </button>
            <?php if ($editRecord): ?>
                <a href="attendance.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- READ / LIST TABLE -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Employee ID</th>
                <th>Dept ID/Date</th>
                <th>Work Start</th>
                <th>Work End</th>
                <th>Entry Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($attendances) > 0): ?>
                <?php foreach ($attendances as $att): ?>
                    <tr>
                        <td><?= e($att['id']) ?></td>
                        <td><strong><?= e($att['empid']) ?></strong></td>
                        <td><?= e($att['deptid']) ?></td>
                        <td><?= e($att['workstart']) ?></td>
                        <td><?= e($att['workend']) ?></td>
                        <td><?= e($att['entrydate']) ?></td>
                        <td class="actions">
                            <a href="attendance.php?action=edit&id=<?= $att['id'] ?>" class="btn btn-secondary" style="padding: 4px 8px;">Edit</a>
                            <a href="attendance.php?action=delete&id=<?= $att['id'] ?>" class="btn btn-danger" style="padding: 4px 8px;" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #888;">No attendance records found in the database.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>