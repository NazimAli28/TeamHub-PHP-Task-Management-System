<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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
