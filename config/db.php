<?php
// Default connection settings (XAMPP defaults).
// To override locally, copy config/db.local.example.php to config/db.local.php.
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'teamhub';

if (file_exists(__DIR__ . '/db.local.php')) {
    require __DIR__ . '/db.local.php';
}

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Set MySQL timezone to Pakistan time (UTC+5)
mysqli_query($conn, "SET time_zone = '+05:00'");
?>
