<?php
// ==========================================
// 1. DATABASE CONNECTION CONFIGURATION
// ==========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'company_info_system'); // Update to your database name
define('DB_USER', 'root');                // Update to your database username
define('DB_PASS', '');                    // Update to your database password
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
    $stmt = $pdo->prepare("DELETE FROM payroll WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: payroll.php?msg=deleted");
        exit;
    }
}

// Handle Form Submission (CREATE & UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month       = trim($_POST['month'] ?? '');
    $employee    = (int)($_POST['employee'] ?? 0);
    $grosssalary = (float)($_POST['grosssalary'] ?? 0);
    $deductions  = (float)($_POST['deductions'] ?? 0);
    $netsalary   = $grosssalary - $deductions; // Automatically calculate net salary
    $bank        = (int)($_POST['bank'] ?? 0);
    $accountno   = trim($_POST['accountno'] ?? '');
    $ssnitid     = trim($_POST['ssnitid'] ?? '');

    if (!empty($month) && $employee > 0) {		
        if (!empty($_POST['id'])) {
            // Update existing record
            $sql = "UPDATE payroll SET month = ?, employee = ?, grosssalary = ?, deductions = ?, netsalary = ?, bank = ?, accountno = ?, ssnitid = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$month, $employee, $grosssalary, $deductions, $netsalary, $bank, $accountno, $ssnitid, $_POST['id']]);
            header("Location: payroll.php?msg=updated");
            exit;
        } else {
            // Insert new record
            $sql = "INSERT INTO payroll (month, employee, grosssalary, deductions, netsalary, bank, accountno, ssnitid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$month, $employee, $grosssalary, $deductions, $netsalary, $bank, $accountno, $ssnitid]);
            header("Location: payroll.php?msg=created");
            exit;
        }
    } else {
        $message = "Please fill in all required fields (Month & Employee ID).";
    }
}

// Fetch single record if editing
$editRecord = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM payroll WHERE id = ?");
    $stmt->execute([$id]);
    $editRecord = $stmt->fetch();
}

// Fetch all records for listing
$payrolls = $pdo->query("SELECT * FROM payroll ORDER BY entrydate DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management System</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; 
            margin: 0; 
            padding: 30px 15px; 
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); 
            min-height: 100vh;
            color: #333; 
        }
        .container { 
            max-width: 1100px; 
            margin: auto; 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); 
        }
        h2 { 
            margin-top: 0; 
            color: #0f172a; 
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .alert { 
            padding: 12px 16px; 
            margin-bottom: 20px; 
            border-radius: 6px; 
            background: #dbeafe; 
            color: #1e40af; 
            border: 1px solid #bfdbfe;
        }
        form { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
            margin-bottom: 30px; 
            background: #f8fafc; 
            padding: 20px; 
            border-radius: 8px; 
            border: 1px solid #e2e8f0; 
        }
        label { 
            font-weight: 600; 
            font-size: 0.85em; 
            display: block; 
            margin-bottom: 6px; 
            color: #475569;
        }
        input[type="text"], input[type="number"], input[type="month"] { 
            width: 100%; 
            padding: 9px; 
            border: 1px solid #cbd5e1; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 0.9em;
        }
        input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        button, .btn { 
            padding: 9px 18px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            font-size: 0.9em; 
            font-weight: 500;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #64748b; color: #fff; }
        .btn-secondary:hover { background: #475569; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        
        .table-responsive {
            overflow-x: auto;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        th, td { 
            text-align: left; 
            padding: 12px; 
            border-bottom: 1px solid #f1f5f9; 
            font-size: 0.9em;
        }
        th { 
            background: #f8fafc; 
            color: #475569;
            font-weight: 600;
        }
        .actions { white-space: nowrap; }
    </style>
</head>
<body>

<div class="container">
    <h2>Payroll Management System</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert">
            <?php 
                if ($_GET['msg'] === 'created') echo "Payroll record created successfully.";
                if ($_GET['msg'] === 'updated') echo "Payroll record updated successfully.";
                if ($_GET['msg'] === 'deleted') echo "Payroll record deleted successfully.";
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="alert" style="background: #fef2f2; color: #991b1b; border-color: #fecaca;"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- CREATE / UPDATE FORM -->
    <form method="POST" action="payroll.php">
        <input type="hidden" name="id" value="<?= e($editRecord['id'] ?? '') ?>">

        <div>
            <label for="month">Month *</label>
            <input type="text" id="month" name="month" placeholder="e.g. October 2023" value="<?= e($editRecord['month'] ?? '') ?>" required>
        </div>

        <div>
            <label for="employee">Employee ID *</label>
            <input type="number" id="employee" name="employee" value="<?= e($editRecord['employee'] ?? '') ?>" required>
        </div>

        <div>
            <label for="grosssalary">Gross Salary ($)</label>
            <input type="number" step="0.01" id="grosssalary" name="grosssalary" value="<?= e($editRecord['grosssalary'] ?? '') ?>" required>
        </div>

        <div>
            <label for="deductions">Deductions ($)</label>
            <input type="number" step="0.01" id="deductions" name="deductions" value="<?= e($editRecord['deductions'] ?? '0') ?>" required>
        </div>

        <div>
            <label for="bank">Bank Code/ID</label>
            <input type="number" id="bank" name="bank" value="<?= e($editRecord['bank'] ?? '') ?>" required>
        </div>

        <div>
            <label for="accountno">Account Number</label>
            <input type="text" id="accountno" name="accountno" value="<?= e($editRecord['accountno'] ?? '') ?>" required>
        </div>

        <div>
            <label for="ssnitid">SSNIT ID</label>
            <input type="text" id="ssnitid" name="ssnitid" value="<?= e($editRecord['ssnitid'] ?? '') ?>" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $editRecord ? 'Update Payroll' : 'Add Payroll' ?>
            </button>
            <?php if ($editRecord): ?>
                <a href="payroll.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- READ / LIST TABLE -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Month</th>
                    <th>Employee ID</th>
                    <th>Gross Salary</th>
                    <th>Deductions</th>
                    <th>Net Salary</th>
                    <th>Bank Code</th>
                    <th>Account No</th>
                    <th>SSNIT ID</th>
                    <th>Entry Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($payrolls) > 0): ?>
                    <?php foreach ($payrolls as $row): ?>
                        <tr>
                            <td><?= e($row['id']) ?></td>
                            <td><strong><?= e($row['month']) ?></strong></td>
                            <td><?= e($row['employee']) ?></td>
                            <td>$<?= number_format((float)$row['grosssalary'], 2) ?></td>
                            <td>$<?= number_format((float)$row['deductions'], 2) ?></td>
                            <td><strong>$<?= number_format((float)$row['netsalary'], 2) ?></strong></td>
                            <td><?= e($row['bank']) ?></td>
                            <td><?= e($row['accountno']) ?></td>
                            <td><?= e($row['ssnitid']) ?></td>
                            <td><?= e($row['entrydate']) ?></td>
                            <td class="actions">
                                <a href="payroll.php?action=edit&id=<?= $row['id'] ?>" class="btn btn-secondary" style="padding: 4px 8px;">Edit</a>
                                <a href="payroll.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-danger" style="padding: 4px 8px;" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" style="text-align: center; color: #888;">No payroll records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>