<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// $timeout = 86400; // 1 day
// if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
//     session_unset();
//     session_destroy();
//     header("Location: ../index.php?timeout=true");
//     exit;
// }

$_SESSION['LAST_ACTIVITY'] = time();
