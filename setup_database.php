<?php
// setup_database.php

$host = '127.0.0.1'; // XAMPP default host
$user = 'root';      // XAMPP default user
$pass = '';          // XAMPP default password (empty)

try {
    // 1. Connect to MySQL without specifying a database first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3>Connected to MySQL server successfully.</h3>";

    // 2. Read the schema.sql file
    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        die("Error: schema.sql file not found at " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);

    // 3. Generate a real hash for the admin password and replace the placeholder in the SQL
    $adminPassword = 'ChangeMe123!';
    $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT);
    $sql = str_replace('<paste-generated-hash-here>', $hashedPassword, $sql);

    // 4. Execute the SQL queries
    $pdo->exec($sql);
    
    echo "<h3>Database and tables created successfully!</h3>";
    echo "<p>Admin user created with username: <strong>admin</strong> and password: <strong>ChangeMe123!</strong></p>";
    echo "<p>Please delete this file (setup_database.php) for security purposes after you are done.</p>";

} catch (PDOException $e) {
    echo "<h3>Error executing database setup:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
