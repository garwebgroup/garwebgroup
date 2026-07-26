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
    $stmt = $pdo->prepare("DELETE FROM helpdesk WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: helpdesk.php?msg=deleted");
        exit;
    }
}

// Handle Form Submission (CREATE & UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $complaint  = trim($_POST['complaint'] ?? '');
    $assignedto = trim($_POST['assignedto'] ?? '');
    $entrydate  = trim($_POST['entrydate'] ?? '');
    $status     = trim($_POST['status'] ?? '');
    $feedback   = trim($_POST['feedback'] ?? '');

    if (!empty($name) && !empty($complaint) && !empty($assignedto) && !empty($entrydate) && !empty($status)) {        
        if (!empty($_POST['id'])) {
            // Update existing record
            $sql = "UPDATE helpdesk SET name = ?, complaint = ?, assignedto = ?, entrydate = ?, status = ?, feedback = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $complaint, $assignedto, $entrydate, $status, $feedback, $_POST['id']]);
            header("Location: helpdesk.php?msg=updated");
            exit;
        } else {
            // Insert new record
            $sql = "INSERT INTO helpdesk (name, complaint, assignedto, entrydate, status, feedback) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $complaint, $assignedto, $entrydate, $status, $feedback]);
            header("Location: helpdesk.php?msg=created");
            exit;
        }
    } else {
        $message = "Please fill in all required fields.";
    }
}

// Fetch single record if editing
$editRecord = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM helpdesk WHERE id = ?");
    $stmt->execute([$id]);
    $editRecord = $stmt->fetch();
}

// Fetch all records for listing
$tickets = $pdo->query("SELECT * FROM helpdesk ORDER BY entrydate DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk Management System</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; 
            margin: 0; 
            padding: 40px 20px;
            /* Nice modern gradient background */
            background: linear-gradient(135deg, #eef2f3 0%, #8e9eab 100%);
            min-height: 100vh;
            color: #333; 
        }
        .container { 
            max-width: 1100px; 
            margin: auto; 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.12); 
        }
        h2 { margin-top: 0; color: #2c3e50; }
        .alert { padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; background: #e3f2fd; color: #0d47a1; font-weight: 500; }
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
        input[type="text"], input[type="number"], input[type="datetime-local"], select, textarea { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #cbd5e1; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 0.95em;
        }
        textarea { resize: vertical; height: 75px; }
        button, .btn { padding: 9px 18px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.9em; font-weight: 600; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #64748b; color: #fff; }
        .btn-secondary:hover { background: #475569; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 0.92em; }
        th { background: #f1f5f9; color: #334155; }
        .actions { white-space: nowrap; }
    </style>
</head>
<body>

<div class="container">
    <h2>Helpdesk Support Tickets</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert">
            <?php 
                if ($_GET['msg'] === 'created') echo "Helpdesk ticket created successfully.";
                if ($_GET['msg'] === 'updated') echo "Helpdesk ticket updated successfully.";
                if ($_GET['msg'] === 'deleted') echo "Helpdesk ticket removed.";
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="alert" style="background: #fee2e2; color: #991b1b;"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- CREATE / UPDATE FORM -->
    <form method="POST" action="helpdesk.php">
        <input type="hidden" name="id" value="<?= e($editRecord['id'] ?? '') ?>">

        <div>
            <label for="name">Requester Name *</label>
            <input type="text" id="name" name="name" maxlength="50" value="<?= e($editRecord['name'] ?? '') ?>" required>
        </div>

        <div>
            <label for="assignedto">Assigned To (User ID) *</label>
            <input type="number" id="assignedto" name="assignedto" value="<?= e($editRecord['assignedto'] ?? '') ?>" required>
        </div>

        <div>
            <label for="entrydate">Entry Date & Time *</label>
            <input type="datetime-local" id="entrydate" name="entrydate" value="<?= formatForInput($editRecord['entrydate'] ?? date('Y-m-d H:i:s')) ?>" required>
        </div>

        <div>
            <label for="status">Status *</label>
            <select id="status" name="status" required>
                <?php $currentStatus = $editRecord['status'] ?? 'Open'; ?>
                <option value="Open" <?= $currentStatus === 'Open' ? 'selected' : '' ?>>Open</option>
                <option value="In Progress" <?= $currentStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="Resolved" <?= $currentStatus === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                <option value="Closed" <?= $currentStatus === 'Closed' ? 'selected' : '' ?>>Closed</option>
            </select>
        </div>

        <div class="full-width">
            <label for="complaint">Complaint Details *</label>
            <textarea id="complaint" name="complaint" maxlength="180" required><?= e($editRecord['complaint'] ?? '') ?></textarea>
        </div>

        <div class="full-width">
            <label for="feedback">Feedback</label>
            <input type="text" id="feedback" name="feedback" maxlength="50" value="<?= e($editRecord['feedback'] ?? '') ?>">
        </div>

        <div class="full-width">
            <button type="submit" class="btn btn-primary">
                <?= $editRecord ? 'Update Ticket' : 'Create Ticket' ?>
            </button>
            <?php if ($editRecord): ?>
                <a href="helpdesk.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- READ / LIST TABLE -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Complaint</th>
                <th>Assigned To</th>
                <th>Entry Date</th>
                <th>Status</th>
                <th>Feedback</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($tickets) > 0): ?>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?= e($ticket['id']) ?></td>
                        <td><strong><?= e($ticket['name']) ?></strong></td>
                        <td><?= e($ticket['complaint']) ?></td>
                        <td><?= e($ticket['assignedto']) ?></td>
                        <td><?= e($ticket['entrydate']) ?></td>
                        <td><?= e($ticket['status']) ?></td>
                        <td><?= e($ticket['feedback']) ?></td>
                        <td class="actions">
                            <a href="helpdesk.php?action=edit&id=<?= $ticket['id'] ?>" class="btn btn-secondary" style="padding: 4px 8px;">Edit</a>
                            <a href="helpdesk.php?action=delete&id=<?= $ticket['id'] ?>" class="btn btn-danger" style="padding: 4px 8px;" onclick="return confirm('Are you sure you want to delete this ticket?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #888;">No helpdesk records found in the database.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>