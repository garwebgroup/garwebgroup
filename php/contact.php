<?php
session_start();

// -----------------------------------------------------------------------------
// 1. DATABASE CONFIGURATION & CONNECTION
// -----------------------------------------------------------------------------
$db_host = 'localhost';
$db_user = 'root';        // Update with your DB username
$db_pass = '';            // Update with your DB password
$db_name = 'company_info_system'; // Update with your DB name

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Ensure the table exists

$message = '';

// -----------------------------------------------------------------------------
// 2. CREATE OPERATION (Handle HTML Form Submission)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fullname'])) {
    
        $fullname     = trim($_POST['fullname']);
        $emailaddress = trim($_POST['emailaddress']); // Fixed HTML field mismatch
        $subject      = trim($_POST['subject']);
        $yourmessage  = trim($_POST['Yourmessage']);

        if (!empty($fullname) && !empty($emailaddress) && !empty($subject) && !empty($yourmessage)) {
            $stmt = $pdo->prepare("INSERT INTO contacts (fullname, emailaddress, subject Yourmessage) VALUES (?, ?, ?, ?)");
            
            // Fixed typo in column mapping
            $stmt = $pdo->prepare("INSERT INTO contacts (fullname, emailaddress, subject, Yourmessage) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$fullname, $emailaddress, $subject, $yourmessage])) {
                $message = '<p style="color: green;">Message sent successfully!</p>';
            } else {
                $message = '<p style="color: red;">Failed to save message.</p>';
            }
        } else {
            $message = '<p style="color: red;">Please fill in all required fields.</p>';
        }
}

// -----------------------------------------------------------------------------
// 3. UPDATE OPERATION
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id          = (int)$_POST['id'];
    $fullname   = trim($_POST['fullname']);
    $email      = trim($_POST['emailaddress']);
    $subject    = trim($_POST['subject']);
    $yourmessage= trim($_POST['Yourmessage']);

    $stmt = $pdo->prepare("UPDATE contacts SET fullname = ?, emailaddress = ?, subject = ?, Yourmessage = ? WHERE id = ?");
    if ($stmt->execute([$fullname, $email, $subject, $yourmessage, $id])) {
        $message = '<p style="color: green;">Record updated successfully!</p>';
    }
}

// -----------------------------------------------------------------------------
// 4. DELETE OPERATION
// -----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = '<p style="color: green;">Record deleted successfully!</p>';
    }
}

// -----------------------------------------------------------------------------
// 5. READ OPERATION (Fetch all contacts to display)
// -----------------------------------------------------------------------------
$stmt = $pdo->query("SELECT * FROM contacts ORDER BY id DESC");
$contacts = $stmt->fetchAll();

// Check if an item is selected for editing
$edit_item = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $edit_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_item = $stmt->fetch();
}
?>

    