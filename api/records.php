<?php
// api/records.php
header('Content-Type: application/json');
require_once 'config.php';

$table = $_GET['table'] ?? '';
$allowed_tables = [
    'users', 'company_profile', 'employees', 'contact_messages', 
    'attendance', 'employeebank', 'helpdesk', 'payroll'
];

if (!in_array($table, $allowed_tables)) {
    echo json_encode(['success' => false, 'message' => 'Invalid table requested.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'GET') {
    try {
        $stmt = $conn->query("SELECT * FROM `$table` ORDER BY id DESC");
        $records = $stmt->fetchAll();
        // Don't leak password_hash to frontend
        if ($table === 'users') {
            foreach ($records as &$rec) {
                unset($rec['password_hash']);
            }
        }
        echo json_encode(['success' => true, 'records' => $records]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve records: ' . $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    if (empty($input)) {
        echo json_encode(['success' => false, 'message' => 'No data provided for insert.']);
        exit;
    }
    
    // Remove id from insert payload if present
    unset($input['id']);
    
    // Hash password if the table is users and a password is provided
    if ($table === 'users' && isset($input['password'])) {
        $input['password_hash'] = password_hash($input['password'], PASSWORD_BCRYPT);
        unset($input['password']);
    }

    $columns = array_keys($input);
    $placeholders = array_map(function($col) { return ":$col"; }, $columns);
    
    $sql = "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $placeholders) . ")";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($input);
        echo json_encode(['success' => true, 'message' => 'Record added successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to add record: ' . $e->getMessage()]);
    }

} elseif ($method === 'PUT') {
    $id = $_GET['id'] ?? ($input['id'] ?? null);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Record ID is required for update.']);
        exit;
    }
    unset($input['id']); // Do not update ID
    
    if (empty($input)) {
        echo json_encode(['success' => false, 'message' => 'No data provided for update.']);
        exit;
    }

    // Hash password if updating users and password is provided
    if ($table === 'users' && isset($input['password'])) {
        if (!empty($input['password'])) {
            $input['password_hash'] = password_hash($input['password'], PASSWORD_BCRYPT);
        }
        unset($input['password']);
    }

    $setClause = [];
    foreach ($input as $key => $value) {
        $setClause[] = "`$key` = :$key";
    }
    
    $sql = "UPDATE `$table` SET " . implode(", ", $setClause) . " WHERE id = :id";
    $input['id'] = $id;

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($input);
        echo json_encode(['success' => true, 'message' => 'Record updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update record: ' . $e->getMessage()]);
    }

} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Record ID is required for deletion.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete record: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unsupported request method']);
}
?>
