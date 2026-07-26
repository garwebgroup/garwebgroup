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

$message = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// ==========================================
// 2. HANDLE ACTIONS (CREATE, UPDATE, DELETE)
// ==========================================

// Handle DELETE
if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM employeebank WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: employeebank.php?msg=deleted");
        exit;
    }
}

// Handle Form Submission (CREATE & UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if (!empty($name)) {        
        if (!empty($_POST['id'])) {
            // Update existing record
            $sql = "UPDATE employeebank SET name = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $_POST['id']]);
            header("Location: employeebank.php?msg=updated");
            exit;
        } else {
            // Insert new record
            $sql = "INSERT INTO employeebank (name) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name]);
            header("Location: employeebank.php?msg=created");
            exit;
        }
    } else {
        $message = "Bank name is required.";
    }
}

// Fetch single record if editing
$editRecord = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM employeebank WHERE id = ?");
    $stmt->execute([$id]);
    $editRecord = $stmt->fetch();
}

// Fetch all records for listing
$banks = $pdo->query("SELECT * FROM employeebank ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Bank Management</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; 
            margin: 0; 
            padding: 40px 20px;
            /* Modern Teal-Emerald Gradient */
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            min-height: 100vh;
            color: #333; 
        }
        .container { 
            max-width: 800px; 
            margin: auto; 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.2); 
        }
        h2 { margin-top: 0; color: #1e293b; }
        .alert { padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; background: #e0f2fe; color: #0369a1; font-weight: 500; }
        form { 
            display: flex;
            flex-direction: column;
            gap: 15px; 
            margin-bottom: 30px; 
            background: #f8fafc; 
            padding: 20px; 
            border-radius: 8px; 
            border: 1px solid #e2e8f0; 
        }
        label { font-weight: 600; font-size: 0.88em; display: block; margin-bottom: 5px; color: #475569; }
        input[type="text"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #cbd5e1; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 0.95em;
        }
        .button-group { display: flex; gap: 10px; }
        button, .btn { padding: 9px 18px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.9em; font-weight: 600; }
        .btn-primary { background: #0d9488; color: #fff; }
        .btn-primary:hover { background: #0f766e; }
        .btn-secondary { background: #64748b; color: #fff; }
        .btn-secondary:hover { background: #475569; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 0.95em; }
        th { background: #f1f5f9; color: #334155; }
        .actions { white-space: nowrap; width: 150px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Employee Bank Management</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert">
            <?php 
                if ($_GET['msg'] === 'created') echo "Bank record added successfully.";
                if ($_GET['msg'] === 'updated') echo "Bank record updated successfully.";
                if ($_GET['msg'] === 'deleted') echo "Bank record removed.";
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="alert" style="background: #fee2e2; color: #991b1b;"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- CREATE / UPDATE FORM -->
    <form method="POST" action="employeebank.php">
        <input type="hidden" name="id" value="<?= e($editRecord['id'] ?? '') ?>">

        <div>
            <label for="name">Bank Name *</label>
            <input type="text" id="name" name="name" value="<?= e($editRecord['name'] ?? '') ?>" placeholder="Enter bank name" required>
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">
                <?= $editRecord ? 'Update Bank' : 'Add Bank' ?>
            </button>
            <?php if ($editRecord): ?>
                <a href="employeebank.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- READ / LIST TABLE -->
    <table>
        <thead>
            <tr>
                <th style="width: 80px;">ID</th>
                <th>Bank Name</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($banks) > 0): ?>
                <?php foreach ($banks as $bank): ?>
                    <tr>
                        <td><?= e($bank['id']) ?></td>
                        <td><strong><?= e($bank['name']) ?></strong></td>
                        <td class="actions">
                            <a href="employeebank.php?action=edit&id=<?= $bank['id'] ?>" class="btn btn-secondary" style="padding: 4px 8px;">Edit</a>
                            <a href="employeebank.php?action=delete&id=<?= $bank['id'] ?>" class="btn btn-danger" style="padding: 4px 8px;" onclick="return confirm('Are you sure you want to delete this bank?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: #888;">No bank records found in the database.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>