<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../../index.php");
    exit;
}

// Auto logout after 1 day
// $timeout = 86400;
// if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
//     session_unset();
//     session_destroy();
//     header("Location: ../../index.php?timeout=true");
//     exit;
// }

$_SESSION['LAST_ACTIVITY'] = time();

// Set employee name from database
$employee_name = '';
if (isset($_SESSION['user_id'])) {
    include '../../config/db.php';
    $user_id = $_SESSION['user_id'];
    $user_query = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result()->fetch_assoc();
    $user_query->close();
    
    $employee_name = $user_result['name'] ?? $_SESSION['username'] ?? 'Employee';
}
