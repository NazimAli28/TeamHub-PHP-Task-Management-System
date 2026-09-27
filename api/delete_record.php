<?php
include '../admin/includes/verify_admin.php';
include '../config/db.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM designer_points WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../admin/pages/records.php");
exit;
