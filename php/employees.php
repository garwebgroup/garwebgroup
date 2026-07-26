<?php
// ==========================================
// 1. DATABASE CONNECTION CONFIGURATION
// ==========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name'); // <-- Update this
define('DB_USER', 'your_username');      // <-- Update this
define('DB_PASS', 'your_password');      // <-- Update this
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
        header("Location: index.php?msg=deleted");
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
            header("Location: index.php?msg=updated");
            exit;
        } else {
            // Insert new record
            $sql = "INSERT INTO employees (full_name, position, department, email, phone) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$full_name, $position, $department, $email, $phone]);
            header("Location: index.php?msg=created");
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