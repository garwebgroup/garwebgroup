<?php
// api/auth.php
header('Content-Type: application/json');
require_once 'config.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON payload
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? ($input['action'] ?? '');

    if ($action === 'register') {
        $name = $input['full_name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $username = $input['username'] ?? explode('@', $email)[0];

        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit();
        }

        // Hashing the password for security
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already registered.']);
                exit();
            }

            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password_hash) VALUES (:name, :username, :email, :password)");
            $stmt->execute([
                'name' => $name,
                // generate a simple username from email if not provided by form
                'username' => explode('@', $email)[0],
                'email' => $email,
                'password' => $hashed_password
            ]);

            echo json_encode(['success' => true, 'message' => 'Registration successful']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'login') {
        $email = $input['identifier'] ?? ($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
            exit();
        }

        try {
            $stmt = $conn->prepare("SELECT id, full_name as name, email, password_hash FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Do not send password back to the frontend
                unset($user['password_hash']);
                echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method: ' . $_SERVER['REQUEST_METHOD']]);
}
?>
