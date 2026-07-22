<?php
// api/records.php
header('Content-Type: application/json');
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// Helper to determine action for POST requests since fetch API might send action inside FormData
$action = $_POST['action'] ?? '';

if ($method === 'GET') {
    // Retrieve Records
    try {
        $stmt = $conn->query("SELECT * FROM records ORDER BY id DESC");
        $records = $stmt->fetchAll();
        echo json_encode(['success' => true, 'records' => $records]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve records: ' . $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    
    if ($action === 'add') {
        // Add Record
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $details = $_POST['details'] ?? '';

        try {
            $stmt = $conn->prepare("INSERT INTO records (name, email, details) VALUES (:name, :email, :details)");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'details' => $details
            ]);
            echo json_encode(['success' => true, 'message' => 'Record added successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to add record: ' . $e->getMessage()]);
        }
    } elseif ($action === 'update') {
        // Update Record
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $details = $_POST['details'] ?? '';

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Record ID is required for update']);
            exit;
        }

        try {
            $stmt = $conn->prepare("UPDATE records SET name = :name, email = :email, details = :details WHERE id = :id");
            $stmt->execute([
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'details' => $details
            ]);
            echo json_encode(['success' => true, 'message' => 'Record updated successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to update record: ' . $e->getMessage()]);
        }
    } elseif ($action === 'delete') {
        // Delete Record
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Record ID is required for deletion']);
            exit;
        }

        try {
            $stmt = $conn->prepare("DELETE FROM records WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete record: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action for POST request']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unsupported request method']);
}
?>
