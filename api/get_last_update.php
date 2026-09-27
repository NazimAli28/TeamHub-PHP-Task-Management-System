<?php
include '../config/db.php';
header('Content-Type: application/json');

$result = $conn->query("SELECT MAX(GREATEST(IFNULL(timestamp, 0), IFNULL(completion_time, 0), IFNULL(updated_at, 0))) as last_update FROM designer_points");
$row = $result->fetch_assoc();

// Fallback: if no rows, use current time
$last_update = $row && $row['last_update'] ? $row['last_update'] : date('Y-m-d H:i:s');

echo json_encode(['last_update' => $last_update]);
